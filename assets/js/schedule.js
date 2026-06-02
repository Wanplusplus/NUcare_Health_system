/* =============================================
   NUCARE — Schedule JS
   Grid rendering, slot modal, availability
   toggling, DB save, and Booking Requests
   (accept / decline patient bookings)
   ============================================= */

'use strict';

/* ── Time slots ─────────────────────────────── */
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

const DAY_KEYS    = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
const DAY_FULL    = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
const HEAD_IDS    = ['hSun','hMon','hTue','hWed','hThu','hFri','hSat'];
const VISIT_TYPES = { general: 'General Consultation', dental: 'Dental Check-up', physical: 'Physical Exam' };
const CHIP_CLASS  = { general: 'chip-general', dental: 'chip-dental', physical: 'chip-physical' };

/* ── AJAX endpoint ──────────────────────────── */
const AJAX = '../../ajax/schedule.ajax.php';

/* ── State ──────────────────────────────────── */
let weekOffset    = 0;
let currentProfId = null;
let professionals = [];
let slotsData     = {};
let activeSlot    = null;
let pendingAvail  = null;

/* ── DOM helpers ────────────────────────────── */
const $  = id => document.getElementById(id);
const el = (tag, cls, html) => {
    const e = document.createElement(tag);
    if (cls)              e.className = cls;
    if (html !== undefined) e.innerHTML = html;
    return e;
};

/* ════════════════════════════════════════════
   INIT
   ════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
    bindUI();
    loadProfessionals();
});

function bindUI() {
    /* Week navigation */
    $('prevWeek').addEventListener('click',  () => { weekOffset--; refreshGrid(); });
    $('nextWeek').addEventListener('click',  () => { weekOffset++; refreshGrid(); });
    $('professionalSelect').addEventListener('change', e => {
        currentProfId = e.target.value;
        refreshGrid();
        loadPendingBookings();
    });
    $('btnExport').addEventListener('click', exportSchedule);

    /* Slot Modal */
    $('modalCloseBtn').addEventListener('click',  closeModal);
    $('modalCancelBtn').addEventListener('click',  closeModal);
    $('modalSaveBtn').addEventListener('click',    saveSlot);
    $('btnEnableSlot').addEventListener('click',  () => setAvailDisplay(true));
    $('btnDisableSlot').addEventListener('click', () => setAvailDisplay(false));
    $('slotModal').addEventListener('click', e => {
        if (e.target === $('slotModal')) closeModal();
    });

    /* Respond Modal - Close */
    $('respondModalCloseBtn').addEventListener('click',  closeRespondModal);
    $('respondModalCancelBtn').addEventListener('click', closeRespondModal);
    $('respondModal').addEventListener('click', e => {
        if (e.target === $('respondModal')) closeRespondModal();
    });

    /* Accept Button */
    $('btnAcceptBooking').addEventListener('click', () => {
        submitBookingResponse('accept');
    });

    /* Decline Toggle */
    $('btnDeclineBooking').addEventListener('click', () => {
        const declineSection = $('declineReasonSection');
        const btnConfirm = $('btnDeclineConfirm');
        const btnToggle = $('btnDeclineBooking');
        
        // Properly check if hidden - handles both empty string and 'none'
        const isHidden = !declineSection.style.display || declineSection.style.display === 'none';
        
        if (isHidden) {
            declineSection.style.display = 'block';
            btnConfirm.style.display = 'inline-flex';
            btnToggle.style.display = 'none';
            
            // Hide reschedule
            $('rescheduleSection').style.display = 'none';
            $('btnRescheduleConfirm').style.display = 'none';
            $('btnRescheduleBooking').style.display = 'inline-flex';
            $('btnRescheduleBooking').innerHTML = '<i class="fa-solid fa-clock"></i> Reschedule';
        } else {
            declineSection.style.display = 'none';
            btnConfirm.style.display = 'none';
            btnToggle.style.display = 'inline-flex';
        }
    });

    /* Decline Confirm */
    $('btnDeclineConfirm').addEventListener('click', () => {
        submitBookingResponse('decline');
    });

// Reschedule Button Toggle
 $('btnRescheduleBooking').addEventListener('click', () => {
        const rescheduleSection = $('rescheduleSection');
        const btnConfirm = $('btnRescheduleConfirm');
        const btnToggle = $('btnRescheduleBooking');
        
        const isHidden = !rescheduleSection.style.display || rescheduleSection.style.display === 'none';
        
        if (isHidden) {
            rescheduleSection.style.display = 'block';
            btnConfirm.style.display = 'flex';
            btnToggle.innerHTML = '<i class="fa-solid fa-xmark"></i> Cancel Reschedule';
            btnToggle.style.background = 'var(--gray-200)';
            btnToggle.style.color = 'var(--gray-700)';
            
            // Hide decline
            $('declineReasonSection').style.display = 'none';
            $('btnDeclineConfirm').style.display = 'none';
            $('btnDeclineBooking').style.display = 'inline-flex';
            
            // Open weekly grid
            openRescheduleSection();
        } else {
            rescheduleSection.style.display = 'none';
            btnConfirm.style.display = 'none';
            btnToggle.innerHTML = '<i class="fa-solid fa-clock"></i> Reschedule';
            btnToggle.style.background = 'var(--blue-50)';
            btnToggle.style.color = 'var(--blue-600)';
            
            clearRescheduleSlot();
        }
    });

    /* Reschedule Confirm */
    $('btnRescheduleConfirm').addEventListener('click', () => {
        submitBookingResponse('reschedule');
    });
    
    /* Reschedule Week Navigation */
    $('reschedulePrevWeek').addEventListener('click', () => {
        rescheduleWeekOffset--;
        updateRescheduleWeekLabel();
        updateRescheduleHeaders();
        loadRescheduleSlots();
    });
    
    $('rescheduleNextWeek').addEventListener('click', () => {
        rescheduleWeekOffset++;
        updateRescheduleWeekLabel();
        updateRescheduleHeaders();
        loadRescheduleSlots();
    });
    
    $('rescheduleClearSlot').addEventListener('click', clearRescheduleSlot);
}
/* ════════════════════════════════════════════
   LOAD PROFESSIONALS
   ════════════════════════════════════════════ */
async function loadProfessionals() {
    try {
        const res = await fetch(`${AJAX}?action=get_professionals`);

        if (!res.ok) {
            const txt = await res.text();
            showToast(`Server error ${res.status} — ${txt.slice(0, 150)}`, 'error');
            professionals = [];
            populateProfessionalSelect();
            return;
        }

        let data;
        try {
            data = await res.json();
        } catch {
            showToast('Server returned invalid JSON. Check PHP error log.', 'error');
            professionals = [];
            populateProfessionalSelect();
            return;
        }

        if (data.status === 'ok' && Array.isArray(data.professionals) && data.professionals.length) {
            professionals = data.professionals;
        } else {
            const msg = data.message || 'No professionals found in the database.';
            showToast(msg, 'error');
            professionals = [];
        }

    } catch (err) {
        showToast('Network error — cannot reach schedule.ajax.php.', 'error');
        professionals = [];
    }

    populateProfessionalSelect();
}

function populateProfessionalSelect() {
    const sel = $('professionalSelect');
    sel.innerHTML = '';

    if (!professionals.length) {
        const opt = document.createElement('option');
        opt.textContent = '— No professionals found —';
        sel.appendChild(opt);
        $('statProfessionals').textContent = 0;
        return;
    }

    const groups = {};
    professionals.forEach(p => {
        const grp = p.specialty || 'Other';
        if (!groups[grp]) groups[grp] = [];
        groups[grp].push(p);
    });

    const groupOrder   = ['Doctor', 'Dentist', 'Nurse', 'Other'];
    const sortedGroups = [
        ...groupOrder.filter(g => groups[g]),
        ...Object.keys(groups).filter(g => !groupOrder.includes(g)),
    ];

    if (sortedGroups.length === 1) {
        professionals.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.id;
            opt.dataset.profession = p.specialty || '';
            opt.dataset.unit       = p.unit || '';
            const unitPart = p.unit ? ` · ${p.unit}` : '';
            opt.textContent = p.name + unitPart;
            sel.appendChild(opt);
        });
    } else {
        sortedGroups.forEach(grp => {
            const og = document.createElement('optgroup');
            og.label = grp + 's';
            groups[grp].forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.id;
                opt.dataset.profession = p.specialty || '';
                opt.dataset.unit       = p.unit || '';
                const unitPart = p.unit ? ` · ${p.unit}` : '';
                opt.textContent = p.name + unitPart;
                og.appendChild(opt);
            });
            sel.appendChild(og);
        });
    }

    currentProfId = professionals[0]?.id ?? null;
    if (currentProfId !== null) {
        sel.value = String(currentProfId);
    }
    refreshGrid();
    loadPendingBookings();
}

/* ════════════════════════════════════════════
   WEEK / GRID HELPERS
   ════════════════════════════════════════════ */
function getWeekStart(offset) {
    const d = new Date();
    d.setDate(d.getDate() - d.getDay() + offset * 7);
    d.setHours(0, 0, 0, 0);
    return d;
}

function isoDate(d) {
    const y  = d.getFullYear();
    const m  = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${dd}`;
}

function fmtDate(d, opts) {
    return d.toLocaleDateString('en-US', opts || { month: 'short', day: 'numeric' });
}

function updateWeekLabel() {
    const ws = getWeekStart(weekOffset);
    const we = new Date(ws); we.setDate(we.getDate() + 6);
    $('weekLabel').textContent = fmtDate(ws) + ' – ' + fmtDate(we);
}

function updateHeaders() {
    const ws    = getWeekStart(weekOffset);
    const today = new Date(); today.setHours(0, 0, 0, 0);
    HEAD_IDS.forEach((hid, i) => {
        const dt      = new Date(ws); dt.setDate(dt.getDate() + i);
        const isToday = dt.getTime() === today.getTime();
        const th      = $(hid);
        th.innerHTML  = DAY_KEYS[i]
            + `<br><span style="font-size:.65rem;font-weight:600;color:${
                isToday ? 'var(--blue-600)' : 'var(--gray-300)'
            }">${dt.getDate()}</span>`;
        th.className = isToday ? 'today-col' : '';
    });
}

async function refreshGrid() {
    if (!currentProfId) return;
    updateWeekLabel();
    updateHeaders();
    await loadSlots();
    buildGrid();
    await updateStats();
}

/* ════════════════════════════════════════════
   LOAD SLOTS FROM DB
   ════════════════════════════════════════════ */
async function loadSlots() {
    const ws = isoDate(getWeekStart(weekOffset));

    $('scheduleBody').innerHTML = `
        <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--gray-400)">
            <i class="fa-solid fa-spinner fa-spin" style="margin-right:6px"></i>Loading schedule…
        </td></tr>`;

    try {
        const res = await fetch(
            `${AJAX}?action=get_slots`
            + `&professional_id=${encodeURIComponent(currentProfId)}`
            + `&week_start=${encodeURIComponent(ws)}`
        );
        const data = await res.json();

        if (data.status === 'ok') {
            slotsData = data.slots;
        } else {
            showToast('Error loading slots: ' + (data.message || 'Unknown error'), 'error');
            slotsData = buildFallbackSlots();
        }
    } catch (err) {
        showToast('Network error loading schedule.', 'error');
        slotsData = buildFallbackSlots();
    }
}

function buildFallbackSlots() {
    const slots = {};
    TIMES.forEach(t => {
        [0, 6].forEach(d => {
            slots[`${d}-${t.label}`] = { availability_id: null, disabled: true, booking: null, notes: 'Weekend' };
        });
        for (let d = 1; d <= 5; d++) {
            slots[`${d}-${t.label}`] = { availability_id: null, disabled: false, booking: null, notes: '' };
        }
    });
    return slots;
}

/* ════════════════════════════════════════════
   BUILD GRID DOM
   ════════════════════════════════════════════ */
function buildGrid() {
    const ws    = getWeekStart(weekOffset);
    const today = new Date(); today.setHours(0, 0, 0, 0);
    const tbody = $('scheduleBody');
    tbody.innerHTML = '';

    TIMES.forEach((timeObj, ti) => {
        if (ti === 8) {
            const lr = el('tr', 'lunch-row');
            lr.innerHTML = `<td colspan="8"><i class="fa-solid fa-utensils"></i>Lunch Break — 12:00 to 1:00 PM</td>`;
            tbody.appendChild(lr);
        }

        const tr = el('tr');
        const tc = el('td', 'time-cell');
        tc.innerHTML = `${timeObj.label}<span class="time-period">${timeObj.period}</span>`;
        tr.appendChild(tc);

        DAY_KEYS.forEach((_, di) => {
            const dt      = new Date(ws); dt.setDate(dt.getDate() + di);
            const isToday = dt.getTime() === today.getTime();
            const key     = `${di}-${timeObj.label}`;

            const slotDefault = { disabled: (di === 0 || di === 6), booking: null, notes: '', availability_id: null };
            const slot        = slotsData[key] ?? slotDefault;

            const isPast = dt < today;

            const td = el('td');
            td.className = 'slot-cell'
                + (isToday       ? ' today-col' : '')
                + (slot.disabled || isPast ? ' blocked'   : '');

            td.dataset.day  = di;
            td.dataset.time = timeObj.label;

    if (!slot.disabled && !isPast) {
        td.addEventListener('click', () => openModal(di, timeObj.label));

        if (slot.booking) {
            td.appendChild(buildChip(slot.booking));
        } else {
            const hint = el('span', 'slot-add-hint');
            hint.innerHTML = '<i class="fa-solid fa-plus" style="font-size:.65rem"></i> Add';
            td.appendChild(hint);
        }
            }

            tr.appendChild(td);
        });

        tbody.appendChild(tr);
    });
}

function buildChip(booking) {
    const status = (booking.status || '').toLowerCase();
    const rescheduleStatus = (booking.reschedule_status || '').toLowerCase();
    const isPending = status === 'pending';
    const isApproved = status === 'approved';
    const isRescheduled = isApproved && rescheduleStatus === 'accepted';

    let chipClass;
    let statusLabel;

    if (isPending) {
        chipClass = 'booking-chip chip-pending';
        statusLabel = '⏳ Pending Review';
    } else if (isRescheduled) {
        chipClass = 'booking-chip chip-rescheduled';
        statusLabel = '🔁 Rescheduled';
    } else if (isApproved) {
        chipClass = 'booking-chip chip-approved';
        statusLabel = '✓ Confirmed';
    } else {
        chipClass = 'booking-chip ' + (CHIP_CLASS[booking.type] || 'chip-general');
        statusLabel = VISIT_TYPES[booking.type] || booking.type;
    }

    const chip = el('div', chipClass);
    chip.innerHTML = `
        <div class="chip-name">${escHtml(booking.patient)}</div>
        <div class="chip-type">${escHtml(statusLabel)}</div>
    `;
    return chip;
}

/* ════════════════════════════════════════════
   STATS
   ════════════════════════════════════════════ */
async function updateStats() {
    const ws = isoDate(getWeekStart(weekOffset));
    try {
        const res  = await fetch(
            `${AJAX}?action=get_stats`
            + `&professional_id=${encodeURIComponent(currentProfId)}`
            + `&week_start=${encodeURIComponent(ws)}`
        );
        const data = await res.json();
        if (data.status === 'ok') {
            $('statBookings').textContent      = data.bookings;
            $('statAvailable').textContent     = data.open;
            $('statDisabled').textContent      = data.blocked;
            $('statProfessionals').textContent = data.professionals;

            /* Update pending badge in requests panel */
            const badge = $('pendingBadge');
            if (badge) badge.textContent = data.pending ?? '';
            return;
        }
    } catch (_) { /* fall through */ }

    let booked = 0, blocked = 0, open = 0;
    TIMES.forEach(t => {
        DAY_KEYS.forEach((_, di) => {
            const slot = slotsData[`${di}-${t.label}`] ?? { disabled: (di === 0 || di === 6) };
            if      (slot.disabled) blocked++;
            else if (slot.booking)  booked++;
            else                    open++;
        });
    });
    $('statBookings').textContent      = booked;
    $('statAvailable').textContent     = open;
    $('statDisabled').textContent      = blocked;
    $('statProfessionals').textContent = professionals.length;
}

/* ════════════════════════════════════════════
   MODAL — OPEN / CLOSE / RENDER
   ════════════════════════════════════════════ */
function openModal(dayIdx, timeLabel) {
    const ws          = getWeekStart(weekOffset);
    const dt          = new Date(ws); dt.setDate(dt.getDate() + dayIdx);
    const slotDefault = { disabled: false, booking: null, notes: '', availability_id: null };
    const slot        = slotsData[`${dayIdx}-${timeLabel}`] ?? slotDefault;
    const prof        = professionals.find(p => String(p.id) === String(currentProfId));

    activeSlot   = { dayIdx, timeLabel };
    pendingAvail = !slot.disabled;

    $('modalSlotTime').textContent = timeLabel + (parseInt(timeLabel) < 12 ? ' AM' : ' PM');
    $('modalDayFull').textContent  = DAY_FULL[dayIdx] + ', '
        + fmtDate(dt, { month: 'long', day: 'numeric', year: 'numeric' });
    if (prof) {
        const profIcon  = prof.specialty === 'Dentist' ? 'fa-tooth'
                        : prof.specialty === 'Nurse'   ? 'fa-user-nurse'
                        : 'fa-user-doctor';
        const profLabel = [prof.specialty, prof.unit].filter(Boolean).join(' · ');
        $('modalProfName').innerHTML =
            `<i class="fa-solid ${profIcon}" style="margin-right:5px;color:var(--blue-500)"></i>`
            + `${escHtml(prof.name)}`
            + (profLabel ? ` <span style="opacity:.65;font-size:.8em">— ${escHtml(profLabel)}</span>` : '');
    } else {
        $('modalProfName').textContent = '—';
    }

    $('slotNotes').value = slot.notes || '';

    renderAvailDisplay(!slot.disabled);
    renderBookingContent(slot.booking);

    $('slotModal').classList.add('open');
}

function closeModal() {
    $('slotModal').classList.remove('open');
    activeSlot   = null;
    pendingAvail = null;
}

function setAvailDisplay(available) {
    pendingAvail = available;
    renderAvailDisplay(available);
}

function renderAvailDisplay(available) {
    const dot  = $('availDot');
    const txt  = $('availText');
    const btnE = $('btnEnableSlot');
    const btnD = $('btnDisableSlot');

    if (available) {
        dot.className   = 'avail-status-dot available';
        txt.textContent = 'Slot is Available for Booking';
        btnE.className  = 'toggle-btn active-enable';
        btnD.className  = 'toggle-btn';
    } else {
        dot.className   = 'avail-status-dot unavailable';
        txt.textContent = 'Slot is Blocked / Unavailable';
        btnE.className  = 'toggle-btn';
        btnD.className  = 'toggle-btn active-disable';
    }
}

function renderBookingContent(booking) {
    const container = $('bookingContent');

    if (!booking) {
        container.innerHTML = `
            <div class="no-booking-state">
                <i class="fa-solid fa-calendar-xmark"></i>
                <p>No appointment scheduled</p>
                <span>This slot is currently open for booking</span>
            </div>`;
        return;
    }

    const initials  = booking.patient.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase();
    const typeLabel = VISIT_TYPES[booking.type] || booking.type;
    const isPending = booking.status === 'Pending';
    const isRescheduled = booking.status === 'Approved' && (booking.reschedule_status || '').toLowerCase() === 'accepted';

    const rescheduleInfo = isRescheduled && booking.reschedule_proposed_date
        ? `<div class="bd-field" style="grid-column:1/-1">
                <div class="bd-field-label">Rescheduled To</div>
                <div class="bd-field-val" style="color:var(--blue-600);font-weight:600">
                    <i class="fa-solid fa-calendar-check" style="margin-right:4px"></i>
                    ${escHtml(booking.reschedule_proposed_date)}${booking.reschedule_proposed_start ? ' at ' + escHtml(booking.reschedule_proposed_start.slice(0,5)) : ''}
                </div>
           </div>`
        : '';

    container.innerHTML = `
        <div class="booking-detail-card">
            <div class="bd-patient-row">
                <div class="bd-avatar">${escHtml(initials)}</div>
                <div>
                    <div class="bd-name">${escHtml(booking.patient)}</div>
                    <div class="bd-type-badge ${escHtml(booking.type)}">${escHtml(typeLabel)}</div>
                </div>
            </div>
            <div class="bd-fields">
                <div class="bd-field">
                    <div class="bd-field-label">Student / Staff ID</div>
                    <div class="bd-field-val">${escHtml(booking.id)}</div>
                </div>
                <div class="bd-field">
                    <div class="bd-field-label">Program / Dept.</div>
                    <div class="bd-field-val">${escHtml(booking.program)}</div>
                </div>
                <div class="bd-field" style="grid-column:1/-1">
                    <div class="bd-field-label">Purpose of Visit</div>
                    <div class="bd-field-val">${escHtml(booking.purpose)}</div>
                </div>
                <div class="bd-field">
                    <div class="bd-field-label">Booking Status</div>
                    <div class="bd-field-val">
                        <span class="inline-status-badge status-${(booking.status || '').toLowerCase()}${isRescheduled ? ' status-rescheduled' : ''}">
                            ${isRescheduled ? '🔁 Rescheduled' : escHtml(booking.status ?? '')}
                        </span>
                    </div>
                </div>
                ${rescheduleInfo}
            </div>
            ${isPending ? `
            <div class="slot-respond-actions">
                <div class="slot-respond-hint">
                    <i class="fa-solid fa-triangle-exclamation" style="color:var(--warning)"></i>
                    This booking is awaiting your response.
                </div>
                <div class="slot-respond-btns">
                    <button class="btn-accept-inline" onclick="openRespondModal(${booking.booking_id})">
                        <i class="fa-solid fa-circle-check"></i> Accept / Decline
                    </button>
                </div>
            </div>` : ''}
        </div>`;
}

/* ════════════════════════════════════════════
   SAVE SLOT → medical_professional_availability
   ════════════════════════════════════════════ */
async function saveSlot() {
    if (!activeSlot) return;

    const { dayIdx, timeLabel } = activeSlot;
    const ws    = isoDate(getWeekStart(weekOffset));
    const notes = $('slotNotes').value.trim();

    const saveBtn = $('modalSaveBtn');
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…';

    const payload = {
        action          : 'save_slot',
        professional_id : currentProfId,
        week_start      : ws,
        day_index       : dayIdx,
        time_label      : timeLabel,
        disabled        : !pendingAvail,
        notes           : notes,
    };

    let savedOk = false;
    let savedId = null;

    try {
        const res  = await fetch(AJAX, {
            method  : 'POST',
            headers : { 'Content-Type': 'application/json' },
            body    : JSON.stringify(payload),
        });

        let data;
        try {
            data = await res.json();
        } catch {
            showToast('Server returned invalid JSON on save.', 'error');
            restoreSaveBtn(saveBtn);
            return;
        }

        if (data.status === 'ok') {
            savedOk = true;
            savedId = data.availability_id ?? null;
        } else {
            showToast('Save failed: ' + (data.message || 'Unknown error'), 'error');
        }

    } catch (err) {
        showToast('Network error — could not save slot.', 'error');
    }

    restoreSaveBtn(saveBtn);
    if (!savedOk) return;

    const key = `${dayIdx}-${timeLabel}`;
    slotsData[key] = {
        ...(slotsData[key] || { booking: null }),
        availability_id : savedId,
        disabled        : !pendingAvail,
        notes           : notes,
    };

    closeModal();
    buildGrid();
    await updateStats();
    showToast(
        pendingAvail
            ? `Slot marked as Available and saved to database`
            : `Slot marked as Unavailable and saved to database`,
        'success'
    );
}

function restoreSaveBtn(btn) {
    btn.disabled  = false;
    btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save Changes';
}

/* ════════════════════════════════════════════
   BOOKING REQUESTS PANEL
   ════════════════════════════════════════════ */
async function loadPendingBookings() {
    if (!currentProfId) return;

    const list = $('bookingRequestsList');
    if (!list) return;

    list.innerHTML = `<div class="requests-loading">
        <i class="fa-solid fa-spinner fa-spin"></i> Loading requests…
    </div>`;

    try {
        const res  = await fetch(`${AJAX}?action=get_pending_bookings&professional_id=${encodeURIComponent(currentProfId)}`);
        const data = await res.json();

        if (data.status === 'ok') {
            renderPendingList(data.bookings || []);
        } else {
            list.innerHTML = `<div class="requests-empty">
                <i class="fa-solid fa-circle-exclamation" style="color:var(--danger)"></i>
                <p>${escHtml(data.message || 'Could not load requests.')}</p>
            </div>`;
        }
    } catch (err) {
        list.innerHTML = `<div class="requests-empty">
            <i class="fa-solid fa-wifi" style="color:var(--gray-400)"></i>
            <p>Network error loading requests.</p>
        </div>`;
    }
}

function renderPendingList(bookings) {
    const list = $('bookingRequestsList');

    /* Update count badge */
    const badge = $('pendingBadge');
    if (badge) badge.textContent = bookings.length || '';

    if (!bookings.length) {
        list.innerHTML = `<div class="requests-empty">
            <i class="fa-solid fa-inbox"></i>
            <p>No pending booking requests</p>
            <span>All caught up! New patient bookings will appear here.</span>
        </div>`;
        return;
    }

    list.innerHTML = '';
    bookings.forEach(bk => {
        const dateObj = new Date(bk.AppointmentDate + 'T00:00:00');
        const dateStr = dateObj.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
        const timeStr = fmtTime(bk.AppointmentStart);
        const initials = (bk.patient_name || '?').split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase();
        const card = el('div', 'request-card');

        card.innerHTML = `
            <div class="request-card-header">
                <div class="request-avatar">${escHtml(initials)}</div>
                <div class="request-info">
                    <div class="request-patient">${escHtml(bk.patient_name)}</div>
                    <div class="request-meta">
                        <span><i class="fa-solid fa-id-card"></i> ${escHtml(bk.school_id || '—')}</span>
                        <span><i class="fa-solid fa-graduation-cap"></i> ${escHtml(bk.program_or_dept || bk.person_type || '—')}</span>
                    </div>
                </div>
                <span class="request-badge">Pending</span>
            </div>
            <div class="request-details">
                <div class="request-detail-item">
                    <i class="fa-solid fa-calendar"></i>
                    <span>${escHtml(dateStr)}</span>
                </div>
                <div class="request-detail-item">
                    <i class="fa-solid fa-clock"></i>
                    <span>${escHtml(timeStr)}</span>
                </div>
                <div class="request-detail-item">
                    <i class="fa-solid fa-stethoscope"></i>
                    <span>${escHtml(bk.ServiceType || '—')}</span>
                </div>
                ${bk.ReasonForVisit ? `<div class="request-detail-item request-reason">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span>${escHtml(bk.ReasonForVisit)}</span>
                </div>` : ''}
            </div>
            <div class="request-actions">
                <button class="btn-accept" onclick="openRespondModal(${(int = bk.BookingID, int)})">
                    <i class="fa-solid fa-circle-check"></i> Accept
                </button>
                <button class="btn-decline" onclick="openRespondModal(${bk.BookingID}, true)">
                    <i class="fa-solid fa-circle-xmark"></i> Decline
                </button>
            </div>`;

        list.appendChild(card);
    });
}

/* helper: format "HH:MM:SS" or "HH:MM" → "h:MM AM/PM" */
function fmtTime(str) {
    if (!str) return '—';
    const [h, m] = str.split(':').map(Number);
    const ampm   = h >= 12 ? 'PM' : 'AM';
    const h12    = ((h % 12) || 12);
    return `${h12}:${String(m).padStart(2, '0')} ${ampm}`;
}

/* ════════════════════════════════════════════
   RESPOND MODAL — ACCEPT / DECLINE
   ════════════════════════════════════════════ */
let activeBookingId = null;

function openRespondModal(bookingId, showDeclineFirst = false) {
    activeBookingId = bookingId;

    /* Reset modal state */
    $('declineReasonSection').style.display  = 'none';
    $('declineReasonText').value             = '';
    $('btnDeclineConfirm').style.display     = 'none';
    $('btnDeclineBooking').style.display     = 'inline-flex';
    $('rescheduleSection').style.display     = 'none';
    $('btnRescheduleConfirm').style.display  = 'none';
    $('btnRescheduleBooking').innerHTML      = '<i class="fa-solid fa-clock"></i> Reschedule';
    if ($('rescheduleDate'))  $('rescheduleDate').value  = '';
    if ($('rescheduleTime'))  $('rescheduleTime').value  = '';
    $('respondBookingId').textContent        = `#${bookingId}`;
    $('respondModal').classList.add('open');

    /* Close the slot modal if open */
    $('slotModal').classList.remove('open');

    if (showDeclineFirst) {
        $('declineReasonSection').style.display = 'block';
        $('btnDeclineConfirm').style.display    = 'inline-flex';
        $('btnDeclineBooking').style.display    = 'none';
    }
}

function closeRespondModal() {
    $('respondModal').classList.remove('open');
    activeBookingId = null;
}

async function submitBookingResponse(action) {
    if (!activeBookingId) return;

    // Validate reschedule
    if (action === 'reschedule') {
        if (!selectedRescheduleSlot) {
            showToast('Please select a new date and time for rescheduling.', 'error');
            return;
        }
    }

    // Get button
    let btn;
    if (action === 'accept') btn = $('btnAcceptBooking');
    else if (action === 'decline') btn = $('btnDeclineConfirm');
    else if (action === 'reschedule') btn = $('btnRescheduleConfirm');
    
    if (!btn) return;

    // Show loading
    btn.disabled = true;
    const origHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…';

    // Build payload
    const payload = {
        action: 'respond_booking',
        booking_id: activeBookingId,
        response: action,
    };

    if (action === 'decline') {
        payload.decline_reason = ($('declineReasonText').value || '').trim();
    }

    if (action === 'reschedule' && selectedRescheduleSlot) {
        // Map the display label (e.g. "1:00") to 24-hour time (e.g. "13:00:00")
        // so PHP stores and indexes it correctly.
        const reschedStartMap = {
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
        payload.new_date  = selectedRescheduleSlot.date;
        payload.new_start = reschedStartMap[selectedRescheduleSlot.time] || selectedRescheduleSlot.time + ':00';
    }

    console.log('Sending:', payload);

    try {
        const res = await fetch(AJAX, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });

        const data = await res.json();
        console.log('Response:', data);

        if (data.status === 'ok') {
            await refreshGrid();
            await loadPendingBookings();
            closeRespondModal();
            
            const msgs = {
                accept: `Booking #${activeBookingId} accepted!`,
                reschedule: 'Reschedule request sent to patient',
                decline: `Booking #${activeBookingId} declined`
            };
            showToast(msgs[action], action === 'accept' ? 'success' : 'info');
        } else {
            showToast(data.message || 'Error', 'error');
            btn.disabled = false;
            btn.innerHTML = origHTML;
        }
    } catch (err) {
        console.error(err);
        showToast('Network error', 'error');
        btn.disabled = false;
        btn.innerHTML = origHTML;
    }
}
/* ════════════════════════════════════════════
   EXPORT (stub)
   ════════════════════════════════════════════ */
function exportSchedule() {
    const ws   = isoDate(getWeekStart(weekOffset));
    const prof = professionals.find(p => String(p.id) === String(currentProfId));
    showToast(`Exporting schedule for ${prof?.name ?? 'professional'} — week of ${ws}`, 'info');
}

/* ════════════════════════════════════════════
   HELPERS
   ════════════════════════════════════════════ */
function showToast(msg, type = 'success') {
    const t     = $('scheduleToast');
    const icons = { success: 'fa-circle-check', error: 'fa-circle-exclamation', info: 'fa-circle-info' };
    t.innerHTML = `<i class="fa-solid ${icons[type] || 'fa-circle-info'}" style="margin-right:6px"></i>${msg}`;
    t.className = `schedule-toast ${type} show`;
    clearTimeout(t._timer);
    t._timer = setTimeout(() => { t.className = 'schedule-toast'; }, 5000);
}

function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}
 /* ════════════════════════════════════════════
   RESCHEDULE WEEKLY GRID - Complete version
   ════════════════════════════════════════════ */
let rescheduleWeekOffset = 0;
let selectedRescheduleSlot = null;

function getRescheduleWeekStart(offset) {
    const d = new Date();
    d.setDate(d.getDate() - d.getDay() + offset * 7);
    d.setHours(0, 0, 0, 0);
    return d;
}

function isoDateReschedule(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${dd}`;
}

function updateRescheduleHeaders() {
    const ws = getRescheduleWeekStart(rescheduleWeekOffset);
    const today = new Date(); today.setHours(0, 0, 0, 0);
    const headIds = ['rsSun', 'rsMon', 'rsTue', 'rsWed', 'rsThu', 'rsFri', 'rsSat'];
    
    headIds.forEach((hid, i) => {
        const dt = new Date(ws);
        dt.setDate(dt.getDate() + i);
        const isToday = dt.getTime() === today.getTime();
        const th = $(hid);
        if (th) {
            th.innerHTML = DAY_KEYS[i]
                + `<br><span style="font-size:.65rem;font-weight:600;color:${
                    isToday ? 'var(--blue-600)' : 'var(--gray-300)'
                }">${dt.getDate()}</span>`;
            th.className = isToday ? 'today-col' : '';
        }
    });
}

function updateRescheduleWeekLabel() {
    const ws = getRescheduleWeekStart(rescheduleWeekOffset);
    const we = new Date(ws);
    we.setDate(we.getDate() + 6);
    if ($('rescheduleWeekLabel')) {
        $('rescheduleWeekLabel').textContent = fmtDate(ws) + ' – ' + fmtDate(we);
    }
}

async function loadRescheduleSlots() {
    const ws = isoDateReschedule(getRescheduleWeekStart(rescheduleWeekOffset));
    
    const tbody = $('rescheduleBody');
    if (!tbody) return;
    
    tbody.innerHTML = `
        <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--gray-400)">
            <i class="fa-solid fa-spinner fa-spin" style="margin-right:6px"></i>Loading…
        </td></tr>`;

    try {
        const res = await fetch(
            `${AJAX}?action=get_slots`
            + `&professional_id=${encodeURIComponent(currentProfId)}`
            + `&week_start=${encodeURIComponent(ws)}`
        );
        const data = await res.json();
        
        if (data.status === 'ok') {
            buildRescheduleGrid(data.slots || {});
        } else {
            buildRescheduleGrid({});
        }
    } catch (err) {
        console.error('Error loading reschedule slots:', err);
        buildRescheduleGrid({});
    }
}

/* ════════════════════════════════════════════
   RESCHEDULE WEEKLY GRID - Build Cells
   ════════════════════════════════════════════ */

function buildRescheduleGrid(slots) {
    const ws = getRescheduleWeekStart(rescheduleWeekOffset);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const tbody = $('rescheduleBody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    TIMES.forEach((timeObj, ti) => {
        // Lunch break row
        if (ti === 8) {
            const lr = el('tr', 'lunch-row');
            lr.innerHTML = `<td colspan="8"><i class="fa-solid fa-utensils"></i> Lunch Break (12:00 - 1:00 PM)</td>`;
            tbody.appendChild(lr);
        }
        
        const tr = el('tr');
        
        // Time column
        const tc = el('td', 'time-cell');
        tc.innerHTML = `${timeObj.label}<span class="time-period">${timeObj.period}</span>`;
        tr.appendChild(tc);
        
        // Day columns
        DAY_KEYS.forEach((_, di) => {
            const dt = new Date(ws);
            dt.setDate(dt.getDate() + di);
            
            // Skip weekends (0=Sun, 6=Sat)
            const isWeekend = (di === 0 || di === 6);
            const isToday = dt.getTime() === today.getTime();
            const isPast = dt < today;
            
            const key = `${di}-${timeObj.label}`;
            const slot = slots[key] || { disabled: false, booking: null };
            
            // Determine cell state
            const hasBooking = slot.booking !== null;
            const isDisabled = isWeekend || isPast || hasBooking || slot.disabled;
            
            const td = el('td', 'sched-cell');
            
            // Apply classes
            let cellClass = 'reschedule-cell';
            if (isDisabled) {
                cellClass += ' cell-blocked';
            } else {
                cellClass += ' cell-available';
            }
            if (isToday) cellClass += ' cell-today';
            
            td.className = cellClass;
            td.dataset.day = di;
            td.dataset.time = timeObj.label;
            
            // Add click only for available slots
            if (!isDisabled) {
                td.addEventListener('click', () => selectRescheduleSlot(di, timeObj.label, dt));
                td.title = `Select: ${DAY_FULL[di]}, ${timeObj.label} ${timeObj.period}`;
            } else {
                let reason = isWeekend ? 'Weekend' : (isPast ? 'Past date' : (hasBooking ? 'Already booked' : 'Unavailable'));
                td.title = reason;
            }
            
            tr.appendChild(td);
        });
        
        tbody.appendChild(tr);
    });
}

function selectRescheduleSlot(dayIdx, timeLabel, dateObj) {
    const dateStr = isoDateReschedule(dateObj);
    const timeStr = timeLabel;
    const dayName = DAY_FULL[dayIdx];
    const dateDisplay = dateObj.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
    
    selectedRescheduleSlot = {
        date: dateStr,
        time: timeStr,
        display: `${dayName}, ${dateDisplay} at ${timeStr}`
    };
    
    // Update selected display
    const selectedDisplay = $('rescheduleSelectedSlot');
    const selectedText = $('rescheduleSelectedText');
    
    if (selectedDisplay) selectedDisplay.style.display = 'flex';
    if (selectedText) selectedText.textContent = selectedRescheduleSlot.display;
    
    // Remove previous selection
    document.querySelectorAll('.reschedule-cell').forEach(cell => {
        cell.classList.remove('bk-slot-selected');
    });
    
    // Add selection to clicked cell
    const cell = document.querySelector(`.reschedule-cell[data-day="${dayIdx}"][data-time="${timeLabel}"]`);
    if (cell) {
        cell.classList.add('bk-slot-selected');
    }
    
    // Show toast
    showToast(`Selected: ${selectedRescheduleSlot.display}`, 'success');
}

function clearRescheduleSlot() {
    selectedRescheduleSlot = null;
    
    const selectedDisplay = $('rescheduleSelectedSlot');
    if (selectedDisplay) selectedDisplay.style.display = 'none';
    
    document.querySelectorAll('.reschedule-cell').forEach(cell => {
        cell.classList.remove('bk-slot-selected');
    });
}
function openRescheduleSection() {
    rescheduleWeekOffset = 0;
    clearRescheduleSlot();
    updateRescheduleWeekLabel();
    updateRescheduleHeaders();
    loadRescheduleSlots();
}