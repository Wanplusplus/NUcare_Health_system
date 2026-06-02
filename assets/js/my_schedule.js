/* ============================================
   NUCARE — My Schedule (Patient View) JS
   ============================================ */

(function () {
    'use strict';

    /* ─── State ─── */
    const state = {
        pendingCancelId: null,
    };

    /* ─── DOM References ─── */
    const dom = {
        statTotal:      document.getElementById('statTotal'),
        statUpcoming:   document.getElementById('statUpcoming'),
        statPending:    document.getElementById('statPending'),
        statCancelled:  document.getElementById('statCancelled'),
        upcomingList:   document.getElementById('upcomingList'),
        pendingList:    document.getElementById('pendingList'),
        toast:          document.getElementById('myScheduleToast'),
        cancelModal:    document.getElementById('cancelModal'),
        cancelModalDate: document.getElementById('cancelModalDate'),
        cancelModalTime: document.getElementById('cancelModalTime'),
        cancelModalProf: document.getElementById('cancelModalProf'),
        cancelModalSvc:  document.getElementById('cancelModalSvc'),
        cancelBookingIdInput: document.getElementById('cancelBookingIdInput'),
        modalCloseBtn:  document.getElementById('modalCloseBtn'),
        modalCancelBtn: document.getElementById('modalCancelBtn'),
        cancelForm:     document.getElementById('cancelForm'),
    };

    /* ─── Helpers ─── */
    function esc(str) {
        const d = document.createElement('div');
        d.textContent = str ?? '—';
        return d.innerHTML;
    }

    function formatDate(dateStr) {
        if (!dateStr) return { day: '—', mon: '—', full: '—' };
        const d = new Date(dateStr + 'T00:00:00');
        return {
            day:  d.getDate().toString().padStart(2, '0'),
            mon:  d.toLocaleString('en-US', { month: 'short' }).toUpperCase(),
            full: d.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }),
        };
    }

    function formatTime(t) {
        if (!t) return '—';
        const [h, m] = t.split(':');
        const hr = parseInt(h);
        const ampm = hr >= 12 ? 'PM' : 'AM';
        return `${((hr % 12) || 12)}:${m} ${ampm}`;
    }

    function statusClass(s) {
        const map = { approved: 'approved', pending: 'pending', completed: 'completed', cancelled: 'cancelled' };
        return map[(s || '').toLowerCase()] || 'pending';
    }

    function statusIcon(s) {
        const map = { approved: 'fa-circle-check', pending: 'fa-clock', completed: 'fa-flag-checkered', cancelled: 'fa-ban' };
        return map[(s || '').toLowerCase()] || 'fa-circle';
    }

    function showToast(msg, type = 'info') {
        if (!dom.toast) return;
        dom.toast.className = `my-schedule-toast ${type} show`;
        dom.toast.innerHTML = `<i class="fa-solid fa-${type === 'success' ? 'circle-check' : type === 'error' ? 'triangle-exclamation' : 'circle-info'}"></i> ${msg}`;
        setTimeout(() => { dom.toast.classList.remove('show'); }, 3200);
    }

    /* ─── Render appointment card ─── */
    function renderCard(a, isPending) {
        const dt = formatDate(a.AppointmentDate);
        const sc = statusClass(a.BookingStatus);
        const prefix = ['Doctor', 'Dentist'].includes(a.Profession || '') ? 'Dr. ' : '';
        const rawName = (a.raw_name || '').trim()
            || ((a.FirstName || '') + ' ' + (a.LastName || '')).trim();
        const profName = (rawName ? prefix + rawName : null)
            || a.Profession
            || 'Medical Professional';

        return `
        <div class="appt-card status-${sc}" data-booking-id="${a.BookingID}">
            <div class="appt-date-block">
                <div class="appt-date-day">${esc(dt.day)}</div>
                <div class="appt-date-mon">${esc(dt.mon)}</div>
            </div>
            <div class="appt-info">
                <div class="appt-doctor">
                    <i class="fa-solid fa-user-doctor" style="color:var(--gold);font-size:.8rem;margin-right:4px;"></i>
                    ${esc(profName)}
                </div>
                <div class="appt-time">
                    <i class="fa-solid fa-clock"></i>
                    ${esc(formatTime(a.AppointmentStart))}${a.AppointmentEnd ? ' – ' + esc(formatTime(a.AppointmentEnd)) : ''}
                </div>
                ${a.ServiceType ? `<span class="service-tag"><i class="fa-solid fa-stethoscope"></i> ${esc(a.ServiceType)}</span>` : ''}
            </div>
            <div class="appt-right">
                <span class="status-badge ${sc}">
                    <i class="fa-solid ${statusIcon(a.BookingStatus)}"></i>
                    ${esc(a.BookingStatus || 'Pending')}
                </span>
                ${isPending ? `
                <button class="btn-danger js-cancel-btn"
                    data-id="${a.BookingID}"
                    data-date="${esc(dt.full)}"
                    data-time="${esc(formatTime(a.AppointmentStart))}"
                    data-prof="${esc(profName)}"
                    data-svc="${esc(a.ServiceType || '')}">
                    <i class="fa-solid fa-xmark"></i> Cancel
                </button>` : ''}
            </div>
        </div>`;
    }

    /* ─── Render lists ─── */
    function renderLists() {
        const data = window.__myScheduleData || { upcoming: [], pending: [] };

        const total     = data.upcoming.length + data.pending.length;
        const upcoming  = data.upcoming.filter(a => ['approved','completed'].includes((a.BookingStatus||'').toLowerCase())).length;
        const pending   = data.pending.length;
        const cancelled = data.upcoming.filter(a => (a.BookingStatus||'').toLowerCase() === 'cancelled').length;

        if (dom.statTotal)     dom.statTotal.textContent     = total;
        if (dom.statUpcoming)  dom.statUpcoming.textContent  = upcoming;
        if (dom.statPending)   dom.statPending.textContent   = pending;
        if (dom.statCancelled) dom.statCancelled.textContent = cancelled;

        if (dom.upcomingList) {
            if (!data.upcoming.length) {
                dom.upcomingList.innerHTML = emptyState('No appointments yet.', 'Book your first appointment below.');
            } else {
                dom.upcomingList.innerHTML = data.upcoming.map(a => renderCard(a, false)).join('');
            }
        }

        if (dom.pendingList) {
            if (!data.pending.length) {
                dom.pendingList.innerHTML = emptyState('No pending requests.', 'Your pending bookings will appear here.');
            } else {
                dom.pendingList.innerHTML = data.pending.map(a => renderCard(a, true)).join('');
            }
        }

        document.querySelectorAll('.js-cancel-btn').forEach(btn => {
            btn.addEventListener('click', openCancelModal);
        });
    }

    function emptyState(primary, secondary) {
        return `<div class="empty-state">
            <div class="empty-state-icon"><i class="fa-solid fa-calendar-xmark"></i></div>
            <p>${primary}</p>
            <span>${secondary}</span>
        </div>`;
    }

    /* ─── Cancel Modal ─── */
    function openCancelModal(e) {
        const btn = e.currentTarget;
        state.pendingCancelId = btn.dataset.id;
        if (dom.cancelModalDate) dom.cancelModalDate.textContent = btn.dataset.date || '—';
        if (dom.cancelModalTime) dom.cancelModalTime.textContent = btn.dataset.time || '—';
        if (dom.cancelModalProf) dom.cancelModalProf.textContent = btn.dataset.prof || '—';
        if (dom.cancelModalSvc)  dom.cancelModalSvc.textContent  = btn.dataset.svc  || '—';
        if (dom.cancelBookingIdInput) dom.cancelBookingIdInput.value = state.pendingCancelId;
        if (dom.cancelModal) dom.cancelModal.classList.add('open');
    }

    function closeCancelModal() {
        if (dom.cancelModal) dom.cancelModal.classList.remove('open');
        state.pendingCancelId = null;
    }

    if (dom.modalCloseBtn)  dom.modalCloseBtn.addEventListener('click', closeCancelModal);
    if (dom.modalCancelBtn) dom.modalCancelBtn.addEventListener('click', closeCancelModal);
    if (dom.cancelModal)    dom.cancelModal.addEventListener('click', e => { if (e.target === dom.cancelModal) closeCancelModal(); });

    /* ─── Toast for cancelled/booked success ─── */
    if (window.__bookedSuccess) {
        showToast('Appointment request submitted! It is now pending approval.', 'success');
    }
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('cancelled') === '1') {
        showToast('Appointment cancelled successfully.', 'info');
    }

    renderLists();

    /* ════════════════════════════════════════
       BOOK MODAL — Weekly Grid Step 2
       ════════════════════════════════════════ */

    const AJAX_URL = '../../ajax/schedule.ajax.php';
    const TIMES = [
        { label: '8:00',  period: 'AM' },
        { label: '8:30',  period: 'AM' },
        { label: '9:00',  period: 'AM' },
        { label: '9:30',  period: 'AM' },
        { label: '10:00', period: 'AM' },
        { label: '10:30', period: 'AM' },
        { label: '11:00', period: 'AM' },
        { label: '11:30', period: 'AM' },
        { label: '1:00',  period: 'PM' },
        { label: '1:30',  period: 'PM' },
        { label: '2:00',  period: 'PM' },
        { label: '2:30',  period: 'PM' },
        { label: '3:00',  period: 'PM' },
        { label: '3:30',  period: 'PM' },
        { label: '4:00',  period: 'PM' },
        { label: '4:30',  period: 'PM' },
        { label: '5:00',  period: 'PM' },
        { label: '5:30',  period: 'PM' },
    ];
    const DAY_KEYS  = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    const DAY_FULL  = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    const BK_HEAD_IDS = ['bkHSun','bkHMon','bkHTue','bkHWed','bkHThu','bkHFri','bkHSat'];

    /* ── Booking state ── */
    const bk = {
        step:      1,
        profId:    null,
        profName:  '',
        profRole:  '',
        availId:   null,
        date:      '',
        startTime: '',
        endTime:   '',
        weekOffset: 0,
        slotsData:  {},
        selectedDayIdx: null,
        selectedTimeLabel: null,
    };

    /* ── Elements ── */
    const bookModal     = document.getElementById('bookModal');
    const bookModalClose= document.getElementById('bookModalClose');
    const openBtns      = [document.getElementById('openBookModal'), document.getElementById('openBookModal2')];
    const stepEls       = [null,
        document.getElementById('bookStep1'),
        document.getElementById('bookStep2'),
        document.getElementById('bookStep3'),
    ];
    const progressFill  = document.getElementById('bookProgressFill');
    const stepNum       = document.getElementById('bookStepNum');
    const stepLabels    = document.querySelectorAll('.book-step-lbl');
    const step1Next     = document.getElementById('bookStep1Next');
    const step2Back     = document.getElementById('bookStep2Back');
    const step2Next     = document.getElementById('bookStep2Next');
    const step3Back     = document.getElementById('bookStep3Back');
    const bfMedProfId   = document.getElementById('bfMedProfId');
    const bfAvailId     = document.getElementById('bfAvailId');
    const bfDate        = document.getElementById('bfDate');
    const bfStart       = document.getElementById('bfStart');
    const bfEnd         = document.getElementById('bfEnd');
    const bsProf        = document.getElementById('bsProf');
    const bsDate        = document.getElementById('bsDate');
    const bsTime        = document.getElementById('bsTime');
    const bkPrevWeek    = document.getElementById('bkPrevWeek');
    const bkNextWeek    = document.getElementById('bkNextWeek');
    const bkWeekLabel   = document.getElementById('bkWeekLabel');
    const bkGridLoading = document.getElementById('bkGridLoading');
    const bkGridWrap    = document.getElementById('bkGridWrap');
    const bkScheduleBody= document.getElementById('bkScheduleBody');
    const bkSelectedSlot= document.getElementById('bkSelectedSlot');
    const bkSelectedSlotText = document.getElementById('bkSelectedSlotText');
    const bkClearSlot   = document.getElementById('bkClearSlot');

    /* ── Week helpers ── */
    function getWeekStart(offset) {
        const d = new Date();
        d.setDate(d.getDate() - d.getDay() + offset * 7);
        d.setHours(0, 0, 0, 0);
        return d;
    }

    function isoDate(d) {
        return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
    }

    function fmtDateShort(d) {
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }

    function fmtDateDisplay(str) {
        if (!str) return '—';
        const d = new Date(str + 'T00:00:00');
        return d.toLocaleDateString('en-US', { weekday: 'short', month: 'long', day: 'numeric', year: 'numeric' });
    }

    function fmtTime12(t) {
        if (!t) return '—';
        const [h, m] = t.split(':');
        const hr = parseInt(h);
        return `${(hr % 12) || 12}:${m} ${hr >= 12 ? 'PM' : 'AM'}`;
    }

    function updateBkWeekLabel() {
        const ws = getWeekStart(bk.weekOffset);
        const we = new Date(ws); we.setDate(we.getDate() + 6);
        if (bkWeekLabel) bkWeekLabel.textContent = fmtDateShort(ws) + ' – ' + fmtDateShort(we);
    }

    function updateBkHeaders() {
        const ws    = getWeekStart(bk.weekOffset);
        const today = new Date(); today.setHours(0, 0, 0, 0);
        BK_HEAD_IDS.forEach((hid, i) => {
            const th = document.getElementById(hid);
            if (!th) return;
            const d = new Date(ws); d.setDate(d.getDate() + i);
            const isToday = d.getTime() === today.getTime();
            th.innerHTML = `${DAY_KEYS[i]}<br><span class="head-date${isToday ? ' today-date' : ''}">${d.getDate()}</span>`;
            th.classList.toggle('col-today', isToday);
        });
    }

    /* ── Grid builder ── */
    function buildBkGrid() {
        if (!bkScheduleBody) return;
        const ws    = getWeekStart(bk.weekOffset);
        const today = new Date(); today.setHours(0, 0, 0, 0);
        const todayIdx = (today.getDay()); // 0=Sun

        let html = '';
        TIMES.forEach((t, ti) => {
            // Lunch break row before PM block
            if (ti === 8) {
                html += `<tr class="lunch-row">
                    <td class="time-cell lunch-label">
                        <span class="time-label">LUNCH</span>
                        <span class="time-period">12–1 PM</span>
                    </td>
                    ${Array(7).fill('<td class="lunch-cell"></td>').join('')}
                </tr>`;
            }

            html += `<tr>
                <td class="time-cell">
                    <span class="time-label">${t.label}</span>
                    <span class="time-period">${t.period}</span>
                </td>`;

            for (let di = 0; di < 7; di++) {
                const key  = `${di}-${t.label}`;
                const slot = bk.slotsData[key];
                const isWeekend = di === 0 || di === 6;
                const isToday   = di === todayIdx;
                const isSelected = bk.selectedDayIdx === di && bk.selectedTimeLabel === t.label;

                // Compute the actual date for this cell and check if it's in the past
                const cellDate = new Date(ws); cellDate.setDate(cellDate.getDate() + di);
                const isPast = cellDate < today;

                let cellClass = 'sched-cell';
                if (isToday)   cellClass += ' cell-today';
                if (isWeekend) cellClass += ' cell-disabled';

                let inner = '';
                if (!slot || isWeekend || isPast) {
                    cellClass += ' cell-blocked';
                    if (isPast && !isWeekend) cellClass += ' cell-past';
                    inner = `<div class="cell-dot dot-blocked"></div>${isPast && !isWeekend ? '<div class="cell-chip chip-past" title="Past date">Past</div>' : ''}`;
                // FIND THIS SECTION AND REPLACE:
} else if (slot.booking) {
    const status = (slot.booking.status || '').toLowerCase();
    const patient = slot.booking.patient || 'Unknown';
    const type = slot.booking.type || '';
    const purpose = slot.booking.purpose || '';
    
    const tooltip = `${patient}${type ? ' - ' + type : ''}${purpose ? ' - ' + purpose : ''}`;
    
    if (status === 'approved') {
        // APPROVED - green, show patient name
        cellClass += ' cell-booked cell-approved-booking';
        inner = `<div class="cell-dot dot-booked"></div>
                 <div class="cell-chip chip-approved" title="${tooltip}">
            ${patient.split(' ')[0]}
        </div>`;
    } else if (status === 'pending') {
        // PENDING - orange, show "Pending"
        cellClass += ' cell-booked cell-pending-booking';
        inner = `<div class="cell-dot dot-booked"></div>
                <div class="cell-chip chip-pending" title="${tooltip}">
            Pending
        </div>`;
    } else if (status === 'completed') {
        // COMPLETED - blue, show "Done"
        cellClass += ' cell-booked cell-completed-booking';
        inner = `<div class="cell-dot dot-booked"></div>
                <div class="cell-chip chip-completed" title="${tooltip}">
            Done
        </div>`;
    } else {
        // Other status - default booked
        cellClass += ' cell-booked';
        inner = `<div class="cell-dot dot-booked"></div>
                <div class="cell-chip chip-general" title="${tooltip}">
            ${patient.split(' ')[0]}
        </div>`;
    }
                } else if (slot.disabled) {
                    cellClass += ' cell-blocked';
                    inner = `<div class="cell-dot dot-blocked"></div>`;
                } else {
                    // Available — clickable
                    cellClass += ' cell-available bk-slot-clickable';
                    if (isSelected) cellClass += ' bk-slot-selected';
                    inner = `<div class="cell-dot dot-available"></div>`;
                    if (isSelected) inner += `<div class="cell-chip chip-selected">Selected</div>`;
                }

                html += `<td class="${cellClass}" data-key="${key}" data-di="${di}" data-tl="${t.label}">
                    <div class="cell-inner">${inner}</div>
                </td>`;
            }
            html += '</tr>';
        });

        bkScheduleBody.innerHTML = html;

        // Attach click handlers to available cells
        bkScheduleBody.querySelectorAll('.bk-slot-clickable').forEach(cell => {
            cell.addEventListener('click', () => {
                const di = parseInt(cell.dataset.di);
                const tl = cell.dataset.tl;
                selectBkSlot(di, tl);
            });
        });
    }

    function selectBkSlot(di, tl) {
        bk.selectedDayIdx   = di;
        bk.selectedTimeLabel = tl;

        // Compute date
        const ws = getWeekStart(bk.weekOffset);
        const d  = new Date(ws); d.setDate(d.getDate() + di);
        bk.date  = isoDate(d);

        // Map time label to HH:MM:SS
        const startMap = {
            '8:00':'08:00:00','8:30':'08:30:00',
            '9:00':'09:00:00','9:30':'09:30:00',
            '10:00':'10:00:00','10:30':'10:30:00',
            '11:00':'11:00:00','11:30':'11:30:00',
            '1:00':'13:00:00','1:30':'13:30:00',
            '2:00':'14:00:00','2:30':'14:30:00',
            '3:00':'15:00:00','3:30':'15:30:00',
            '4:00':'16:00:00','4:30':'16:30:00',
            '5:00':'17:00:00','5:30':'17:30:00',
        };
        const endMap = {
            '8:00':'08:30:00','8:30':'09:00:00',
            '9:00':'09:30:00','9:30':'10:00:00',
            '10:00':'10:30:00','10:30':'11:00:00',
            '11:00':'11:30:00','11:30':'12:00:00',
            '1:00':'13:30:00','1:30':'14:00:00',
            '2:00':'14:30:00','2:30':'15:00:00',
            '3:00':'15:30:00','3:30':'16:00:00',
            '4:00':'16:30:00','4:30':'17:00:00',
            '5:00':'17:30:00','5:30':'18:00:00',
        };

        bk.startTime = startMap[tl] || '';
        bk.endTime   = endMap[tl]   || '';

        // AvailabilityID from slotsData
        const slot = bk.slotsData[`${di}-${tl}`];
        bk.availId = slot?.availability_id ?? null;

        // Update hidden fields
        if (bfDate)  bfDate.value  = bk.date;
        if (bfStart) bfStart.value = bk.startTime;
        if (bfEnd)   bfEnd.value   = bk.endTime;
        if (bfAvailId) bfAvailId.value = bk.availId ?? '';

        // Show selected slot banner
        const dayLabel = DAY_FULL[di];
        const dateLabel = fmtDateDisplay(bk.date);
        if (bkSelectedSlotText)
            bkSelectedSlotText.textContent = `${dayLabel}, ${dateLabel} at ${fmtTime12(bk.startTime)} – ${fmtTime12(bk.endTime)}`;
        if (bkSelectedSlot) bkSelectedSlot.style.display = 'flex';

        if (step2Next) step2Next.disabled = false;

        buildBkGrid(); // redraw with selection highlight
    }

    function clearBkSelection() {
        bk.selectedDayIdx    = null;
        bk.selectedTimeLabel = null;
        bk.date = bk.startTime = bk.endTime = '';
        bk.availId = null;
        if (bfDate)    bfDate.value    = '';
        if (bfStart)   bfStart.value   = '';
        if (bfEnd)     bfEnd.value     = '';
        if (bfAvailId) bfAvailId.value = '';
        if (bkSelectedSlot) bkSelectedSlot.style.display = 'none';
        if (step2Next) step2Next.disabled = true;
        buildBkGrid();
    }

    /* ── Load grid from AJAX ── */
    async function loadBkGrid() {
        if (!bk.profId) return;

        clearBkSelection();
        updatePrevWeekBtn();
        if (bkGridWrap)    bkGridWrap.style.display    = 'none';
        if (bkGridLoading) bkGridLoading.style.display = 'flex';

        const ws = isoDate(getWeekStart(bk.weekOffset));
        updateBkWeekLabel();
        updateBkHeaders();

        try {
            const res  = await fetch(`${AJAX_URL}?action=get_slots&professional_id=${bk.profId}&week_start=${ws}`);
            const data = await res.json();
            if (data.status === 'ok') {
                bk.slotsData = data.slots || {};
            } else {
                bk.slotsData = {};
                showToast('Could not load schedule: ' + (data.message || 'Unknown error'), 'error');
            }
        } catch(e) {
            bk.slotsData = {};
            showToast('Network error loading schedule.', 'error');
        }

        if (bkGridLoading) bkGridLoading.style.display = 'none';
        if (bkGridWrap)    bkGridWrap.style.display    = 'block';
        buildBkGrid();
    }

    /* ── Week nav ── */
    function updatePrevWeekBtn() {
        if (bkPrevWeek) bkPrevWeek.disabled = bk.weekOffset <= 0;
    }

    if (bkPrevWeek) bkPrevWeek.addEventListener('click', () => {
        if (bk.weekOffset <= 0) return;
        bk.weekOffset--;
        updatePrevWeekBtn();
        loadBkGrid();
    });
    if (bkNextWeek) bkNextWeek.addEventListener('click', () => { bk.weekOffset++; updatePrevWeekBtn(); loadBkGrid(); });
    if (bkClearSlot) bkClearSlot.addEventListener('click', clearBkSelection);

    /* ── Progress bar ── */
    function setProgress(step) {
        const pcts = ['33.33%', '66.66%', '100%'];
        if (progressFill) progressFill.style.width = pcts[step - 1];
        if (stepNum) stepNum.textContent = step;
        stepLabels.forEach((lbl, i) => {
            lbl.classList.remove('active', 'complete');
            if (i + 1 < step) lbl.classList.add('complete');
            if (i + 1 === step) lbl.classList.add('active');
        });
    }

    function goToStep(n) {
        stepEls.forEach((el, i) => { if (i > 0 && el) el.style.display = i === n ? 'flex' : 'none'; });
        bk.step = n;
        setProgress(n);
    }

    /* ── Open / Close ── */
    function openModal() {
        if (bookModal) bookModal.classList.add('open');
        goToStep(1);
        resetModal();
    }

    function closeModal() {
        if (bookModal) bookModal.classList.remove('open');
    }

    function resetModal() {
        bk.profId = null; bk.profName = ''; bk.profRole = '';
        bk.availId = null; bk.date = ''; bk.startTime = ''; bk.endTime = '';
        bk.weekOffset = 0; bk.slotsData = {};
        bk.selectedDayIdx = null; bk.selectedTimeLabel = null;
        document.querySelectorAll('.js-prof-card').forEach(c => c.classList.remove('selected'));
        if (step1Next) step1Next.disabled = true;
        if (step2Next) step2Next.disabled = true;
        if (bkSelectedSlot) bkSelectedSlot.style.display = 'none';
        if (bkGridWrap)     bkGridWrap.style.display     = 'none';
        if (bkGridLoading)  bkGridLoading.style.display  = 'none';
        if (document.getElementById('bServiceType')) document.getElementById('bServiceType').value = '';
        if (document.getElementById('bReason')) document.getElementById('bReason').value = '';
    }

    openBtns.forEach(btn => { if (btn) btn.addEventListener('click', openModal); });
    if (bookModalClose) bookModalClose.addEventListener('click', closeModal);
    if (bookModal) bookModal.addEventListener('click', e => { if (e.target === bookModal) closeModal(); });

    /* ── Step 1: Professional selection ── */
    document.querySelectorAll('.js-prof-card').forEach(card => {
        card.addEventListener('click', () => {
            document.querySelectorAll('.js-prof-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            bk.profId   = card.dataset.id;
            bk.profName = card.dataset.name;
            bk.profRole = card.dataset.profession;
            if (step1Next) step1Next.disabled = false;
        });
    });

    if (step1Next) step1Next.addEventListener('click', () => {
        if (!bk.profId) return;
        if (bfMedProfId) bfMedProfId.value = bk.profId;
        goToStep(2);
        // Reset grid state when professional changes
        bk.weekOffset = 0;
        clearBkSelection();
        loadBkGrid();
    });

    /* ── Step 2 ── */
    if (step2Back) step2Back.addEventListener('click', () => goToStep(1));

    if (step2Next) step2Next.addEventListener('click', () => {
        if (!bk.date || !bk.startTime) return;
        // Populate step 3 summary
        if (bsProf) bsProf.textContent = `${bk.profName} (${bk.profRole})`;
        if (bsDate) bsDate.textContent = fmtDateDisplay(bk.date);
        if (bsTime) bsTime.textContent = fmtTime12(bk.startTime) + (bk.endTime ? ' – ' + fmtTime12(bk.endTime) : '');
        goToStep(3);
    });

    /* ── Step 3 ── */
    if (step3Back) step3Back.addEventListener('click', () => goToStep(2));

    const bServiceType = document.getElementById('bServiceType');
    if (bServiceType) {
        bServiceType.addEventListener('change', () => {
            const btn = document.getElementById('bookSubmitBtn');
            if (btn) btn.disabled = !bServiceType.value;
        });
        const btn = document.getElementById('bookSubmitBtn');
        if (btn) btn.disabled = true;
    }
/* ════════════════════════════════════════════
   RESCHEDULE RESPONSE (Patient)
   ════════════════════════════════════════════ */
const rescheduleAjax = '../../ajax/schedule.ajax.php';
let activeRescheduleId = null;

function renderLists() {
    const data = window.__myScheduleData || { upcoming: [], pending: [] };

    const total     = data.upcoming.length + data.pending.length;
    const upcoming  = data.upcoming.filter(a => ['approved','completed'].includes((a.BookingStatus||'').toLowerCase())).length;
    const pending   = data.pending.length;
    const cancelled = data.upcoming.filter(a => (a.BookingStatus||'').toLowerCase() === 'cancelled').length;

    if (dom.statTotal)     dom.statTotal.textContent     = total;
    if (dom.statUpcoming)  dom.statUpcoming.textContent  = upcoming;
    if (dom.statPending)   dom.statPending.textContent   = pending;
    if (dom.statCancelled) dom.statCancelled.textContent = cancelled;

    if (dom.upcomingList) {
        if (!data.upcoming.length) {
            dom.upcomingList.innerHTML = emptyState('No appointments yet.', 'Book your first appointment below.');
        } else {
            dom.upcomingList.innerHTML = data.upcoming.map(a => renderCard(a, false)).join('');
        }
    }

    if (dom.pendingList) {
        if (!data.pending.length) {
            dom.pendingList.innerHTML = emptyState('No pending requests.', 'Your pending bookings will appear here.');
        } else {
            dom.pendingList.innerHTML = data.pending.map(a => renderCard(a, true)).join('');
        }
    }

    // Cancel buttons
    document.querySelectorAll('.js-cancel-btn').forEach(btn => {
        btn.addEventListener('click', openCancelModal);
    });
    
    // Reschedule response buttons
    document.querySelectorAll('.js-reschedule-accept-btn').forEach(btn => {
        btn.addEventListener('click', () => respondReschedule(btn.dataset.id, 'accept'));
    });
    document.querySelectorAll('.js-reschedule-decline-btn').forEach(btn => {
        btn.addEventListener('click', () => respondReschedule(btn.dataset.id, 'decline'));
    });
}

// Updated renderCard with reschedule info
function renderCard(a, isPending) {
    const dt = formatDate(a.AppointmentDate);
    const sc = statusClass(a.BookingStatus);
    const prefix = ['Doctor', 'Dentist'].includes(a.Profession || '') ? 'Dr. ' : '';
    const rawName = (a.raw_name || '').trim()
        || ((a.FirstName || '') + ' ' + (a.LastName || '')).trim();
    const profName = (rawName ? prefix + rawName : null)
        || a.Profession
        || 'Medical Professional';

    // Check for reschedule proposal
    const hasReschedule = a.RescheduleStatus === 'Proposed' && a.RescheduleProposedDate;
    let rescheduleHTML = '';
    if (hasReschedule) {
        const newDt = formatDate(a.RescheduleProposedDate);
        const newTime = formatTime(a.RescheduleProposedStart);
        rescheduleHTML = `
        <div class="reschedule-proposal-banner">
            <div class="rp-banner-header">
                <i class="fa-solid fa-clock"></i>
                <span>New Time Proposed</span>
            </div>
            <div class="rp-banner-details">
                <div class="rp-new-date">
                    <i class="fa-solid fa-calendar"></i>
                    ${esc(newDt.full)} at ${esc(newTime)}
                </div>
            </div>
            <div class="rp-banner-actions">
                <button type="button" class="btn-success btn-sm js-reschedule-accept-btn" data-id="${a.BookingID}">
                    <i class="fa-solid fa-check"></i> Accept
                </button>
                <button type="button" class="btn-danger btn-sm js-reschedule-decline-btn" data-id="${a.BookingID}">
                    <i class="fa-solid fa-xmark"></i> Decline
                </button>
            </div>
        </div>`;
    }

    return `
    <div class="appt-card status-${sc}" data-booking-id="${a.BookingID}">
        <div class="appt-date-block">
            <div class="appt-date-day">${esc(dt.day)}</div>
            <div class="appt-date-mon">${esc(dt.mon)}</div>
        </div>
        <div class="appt-info">
            <div class="appt-doctor">
                <i class="fa-solid fa-user-doctor" style="color:var(--gold);font-size:.8rem;margin-right:4px;"></i>
                ${esc(profName)}
            </div>
            <div class="appt-time">
                <i class="fa-solid fa-clock"></i>
                ${esc(formatTime(a.AppointmentStart))}${a.AppointmentEnd ? ' – ' + esc(formatTime(a.AppointmentEnd)) : ''}
            </div>
            ${a.ServiceType ? `<span class="service-tag"><i class="fa-solid fa-stethoscope"></i> ${esc(a.ServiceType)}</span>` : ''}
        </div>
        <div class="appt-right">
            <span class="status-badge ${sc}">
                <i class="fa-solid ${statusIcon(a.BookingStatus)}"></i>
                ${esc(a.BookingStatus || 'Pending')}
            </span>
            ${isPending && !hasReschedule ? `
            <button class="btn-danger js-cancel-btn"
                data-id="${a.BookingID}"
                data-date="${esc(dt.full)}"
                data-time="${esc(formatTime(a.AppointmentStart))}"
                data-prof="${esc(profName)}"
                data-svc="${esc(a.ServiceType || '')}">
                <i class="fa-solid fa-xmark"></i> Cancel
            </button>` : ''}
        </div>
        ${rescheduleHTML}
    </div>`;
}

async function respondReschedule(bookingId, response) {
    if (!bookingId) return;
    
    activeRescheduleId = bookingId;
    
    const btnClass = response === 'accept' ? '.js-reschedule-accept-btn' : '.js-reschedule-decline-btn';
    const btns = document.querySelectorAll(btnClass + '[data-id="' + bookingId + '"]');
    const originalTexts = [];
    
    btns.forEach(btn => {
        originalTexts.push(btn.innerHTML);
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    });

    try {
        const res = await fetch(rescheduleAjax, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'patient_respond_reschedule',
                booking_id: parseInt(bookingId),
                response: response
            })
        });
        
        const data = await res.json();
        
        if (data.status === 'ok') {
            // Reload the page to get updated data
            showToast(
                response === 'accept' 
                    ? 'Reschedule accepted! Your appointment has been confirmed.' 
                    : 'Reschedule declined.',
                response === 'accept' ? 'success' : 'info'
            );
            
            // Reload after short delay
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showToast(data.message || 'Error processing response', 'error');
            btns.forEach((btn, i) => {
                btn.disabled = false;
                btn.innerHTML = originalTexts[i];
            });
        }
    } catch (err) {
        console.error(err);
        showToast('Network error', 'error');
        btns.forEach((btn, i) => {
            btn.disabled = false;
            btn.innerHTML = originalTexts[i];
        });
    }
}

// Handle reschedule responses from patient
document.querySelectorAll('.js-reschedule-accept-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const bookingId = this.dataset.id;
        if (confirm('Accept the new time? Your appointment will be rescheduled.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="booking_id" value="${bookingId}">
                <input type="hidden" name="respond_reschedule" value="accept">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
});

document.querySelectorAll('.js-reschedule-decline-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const bookingId = this.dataset.id;
        if (confirm('Decline the new time? Your original appointment remains.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="booking_id" value="${bookingId}">
                <input type="hidden" name="respond_reschedule" value="decline">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
});
})();