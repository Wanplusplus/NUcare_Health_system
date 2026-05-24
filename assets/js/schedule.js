/* =============================================
   NUCARE — Schedule JS
   Grid rendering, slot modal, availability
   toggling, and DB save via schedule_ajax.php
   ============================================= */

'use strict';

/* ── Time slots ─────────────────────────────── */
const TIMES = [
    { label: '8:00',  period: 'AM' },
    { label: '9:00',  period: 'AM' },
    { label: '10:00', period: 'AM' },
    { label: '11:00', period: 'AM' },
    // Lunch break row injected before index 4
    { label: '1:00',  period: 'PM' },
    { label: '2:00',  period: 'PM' },
    { label: '3:00',  period: 'PM' },
    { label: '4:00',  period: 'PM' },
    { label: '5:00',  period: 'PM' },
];

const DAY_KEYS    = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
const DAY_FULL    = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
const HEAD_IDS    = ['hSun','hMon','hTue','hWed','hThu','hFri','hSat'];
const VISIT_TYPES = { general: 'General Consultation', dental: 'Dental Check-up', physical: 'Physical Exam' };
const CHIP_CLASS  = { general: 'chip-general', dental: 'chip-dental', physical: 'chip-physical' };

/* ── AJAX endpoint ──────────────────────────── */
// If schedule_ajax.php is in the same folder as the page, this is correct.
// If it's in a sub-folder change this path, e.g. 'modules/schedule/schedule_ajax.php'
const AJAX = '../../ajax/schedule.ajax.php';

/* ── State ──────────────────────────────────── */
let weekOffset    = 0;
let currentProfId = null;
let professionals = [];
let slotsData     = {};
let activeSlot    = null;   // { dayIdx, timeLabel }
let pendingAvail  = null;   // true = available, false = blocked

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
    $('prevWeek').addEventListener('click',  () => { weekOffset--; refreshGrid(); });
    $('nextWeek').addEventListener('click',  () => { weekOffset++; refreshGrid(); });
    $('professionalSelect').addEventListener('change', e => {
        currentProfId = e.target.value;
        refreshGrid();
    });
    $('modalCloseBtn').addEventListener('click',  closeModal);
    $('modalCancelBtn').addEventListener('click',  closeModal);
    $('modalSaveBtn').addEventListener('click',    saveSlot);
    $('btnEnableSlot').addEventListener('click',  () => setAvailDisplay(true));
    $('btnDisableSlot').addEventListener('click', () => setAvailDisplay(false));
    $('slotModal').addEventListener('click', e => {
        if (e.target === $('slotModal')) closeModal();
    });
    $('btnExport').addEventListener('click', exportSchedule);
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
            console.error('get_professionals HTTP error', res.status, txt);
            professionals = [];
            populateProfessionalSelect();
            return;
        }

        let data;
        try {
            data = await res.json();
        } catch {
            const txt = await res.clone().text();
            showToast('Server returned invalid JSON. Check PHP error log.', 'error');
            console.error('JSON parse error. Raw:', txt);
            professionals = [];
            populateProfessionalSelect();
            return;
        }

        if (data.status === 'ok' && Array.isArray(data.professionals) && data.professionals.length) {
            professionals = data.professionals;
        } else {
            const msg = data.message || 'No professionals found in the database.';
            showToast(msg, 'error');
            console.warn('get_professionals response:', data);
            professionals = [];
        }

    } catch (err) {
        showToast(
            'Network error — cannot reach schedule_ajax.php. '
            + 'Make sure the file exists in the same folder as this page '
            + 'and DEV_BYPASS is set to true in schedule_ajax.php.',
            'error'
        );
        console.error('fetch error (get_professionals):', err);
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

    /* Group by profession type for optgroup labels */
    const groups = {};
    professionals.forEach(p => {
        const grp = p.specialty || 'Other';
        if (!groups[grp]) groups[grp] = [];
        groups[grp].push(p);
    });

    const groupOrder  = ['Doctor', 'Dentist', 'Nurse', 'Other'];
    const sortedGroups = [
        ...groupOrder.filter(g => groups[g]),
        ...Object.keys(groups).filter(g => !groupOrder.includes(g)),
    ];

    if (sortedGroups.length === 1) {
        /* Only one profession type — flat list, no optgroups */
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
        /* Multiple profession types — use optgroups */
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
    refreshGrid();
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

/* Fallback when server is unreachable */
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
        /* Lunch break separator */
        if (ti === 4) {
            const lr = el('tr', 'lunch-row');
            lr.innerHTML = `<td colspan="8"><i class="fa-solid fa-utensils"></i>Lunch Break — 12:00 to 1:00 PM</td>`;
            tbody.appendChild(lr);
        }

        const tr = el('tr');

        /* Time label */
        const tc = el('td', 'time-cell');
        tc.innerHTML = `${timeObj.label}<span class="time-period">${timeObj.period}</span>`;
        tr.appendChild(tc);

        /* One cell per day */
        DAY_KEYS.forEach((_, di) => {
            const dt      = new Date(ws); dt.setDate(dt.getDate() + di);
            const isToday = dt.getTime() === today.getTime();
            const key     = `${di}-${timeObj.label}`;

            const slotDefault = { disabled: (di === 0 || di === 6), booking: null, notes: '', availability_id: null };
            const slot        = slotsData[key] ?? slotDefault;

            const td = el('td');
            td.className = 'slot-cell'
                + (isToday       ? ' today-col' : '')
                + (slot.disabled ? ' blocked'   : '');

            td.dataset.day  = di;
            td.dataset.time = timeObj.label;

            if (!slot.disabled) {
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
    const chip = el('div', `booking-chip ${CHIP_CLASS[booking.type] || 'chip-general'}`);
    chip.innerHTML = `
        <div class="chip-name">${escHtml(booking.patient)}</div>
        <div class="chip-type">${VISIT_TYPES[booking.type] || booking.type}</div>
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
            return;
        }
    } catch (_) { /* fall through */ }

    /* Local fallback count */
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

    /* Header */
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
                    <div class="bd-field-val">${escHtml(booking.status ?? '')}</div>
                </div>
            </div>
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

    /* Disable button while saving */
    const saveBtn = $('modalSaveBtn');
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…';

    const payload = {
        action          : 'save_slot',
        professional_id : currentProfId,   // MedProfID
        week_start      : ws,              // YYYY-MM-DD of Sunday
        day_index       : dayIdx,          // 0=Sun … 6=Sat
        time_label      : timeLabel,       // e.g. "8:00"
        disabled        : !pendingAvail,   // true = Unavailable
        notes           : notes,
    };

    let savedOk  = false;
    let savedId  = null;

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
            const raw = await res.clone().text();
            showToast('Server returned invalid JSON on save. Check PHP logs.', 'error');
            console.error('save_slot JSON error. Raw:', raw);
            restoreSaveBtn(saveBtn);
            return;
        }

        if (data.status === 'ok') {
            savedOk = true;
            savedId = data.availability_id ?? null;
            console.info('Slot saved →', data);
        } else {
            showToast('Save failed: ' + (data.message || 'Unknown error'), 'error');
            console.error('save_slot error response:', data);
        }

    } catch (err) {
        showToast('Network error — could not save slot. Is schedule_ajax.php reachable?', 'error');
        console.error('save_slot fetch error:', err);
    }

    restoreSaveBtn(saveBtn);
    if (!savedOk) return;

    /* Update local slotsData so grid reflects change immediately */
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
   EXPORT (stub — extend as needed)
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
    t._timer = setTimeout(() => { t.className = 'schedule-toast'; }, 4000);
}

function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}