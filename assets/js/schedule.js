/* =============================================
   NUCARE — Schedule JS
   Handles grid rendering, slot modal,
   availability toggling, and API integration
   ============================================= */

'use strict';

/* ── Constants ──────────────────────────────── */
const TIMES = [
    { label: '8:00',  period: 'AM' },
    { label: '9:00',  period: 'AM' },
    { label: '10:00', period: 'AM' },
    { label: '11:00', period: 'AM' },
    // Lunch break inserted after index 3
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

/* ── API Endpoints ──────────────────────────── */
/*  Replace these with your real PHP API paths.  */
const API = {
    professionals : '../../api/schedule/professionals.php',
    slots         : '../../api/schedule/slots.php',         // ?professional_id=&week_start=YYYY-MM-DD
    saveSlot      : '../../api/schedule/save_slot.php',     // POST
    stats         : '../../api/schedule/stats.php',         // ?professional_id=&week_start=YYYY-MM-DD
};

/* ── State ──────────────────────────────────── */
let weekOffset       = 0;          // weeks relative to current
let currentProfId    = null;       // selected professional id
let professionals    = [];         // [ { id, name, specialty } ]
let slotsData        = {};         // keyed by "dayIndex-timeLabel"  → { booking, disabled, notes }
let activeSlot       = null;       // { dayIdx, timeLabel }
let pendingAvail     = null;       // true = available, false = blocked

/* ── DOM Refs ───────────────────────────────── */
const $  = id => document.getElementById(id);
const el = (tag, cls, html) => { const e = document.createElement(tag); if (cls) e.className = cls; if (html !== undefined) e.innerHTML = html; return e; };

/* ── Init ───────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    loadProfessionals();
    bindUI();
});

/* ── Bind static UI events ──────────────────── */
function bindUI() {
    $('prevWeek').addEventListener('click', () => { weekOffset--; refreshGrid(); });
    $('nextWeek').addEventListener('click', () => { weekOffset++; refreshGrid(); });
    $('professionalSelect').addEventListener('change', e => { currentProfId = e.target.value; refreshGrid(); });
    $('modalCloseBtn').addEventListener('click', closeModal);
    $('modalCancelBtn').addEventListener('click', closeModal);
    $('modalSaveBtn').addEventListener('click', saveSlot);
    $('btnEnableSlot').addEventListener('click', () => setAvailDisplay(true));
    $('btnDisableSlot').addEventListener('click', () => setAvailDisplay(false));
    $('slotModal').addEventListener('click', e => { if (e.target === $('slotModal')) closeModal(); });
    $('btnExport').addEventListener('click', exportSchedule);
    $('btnAddBooking').addEventListener('click', () => showToast('Select a time slot on the grid to manage it.', 'info'));
}

/* ── Load Professionals ─────────────────────── */
async function loadProfessionals() {
    try {
        const res  = await fetch(API.professionals);
        const data = await res.json();

        if (data.status === 'ok' && data.professionals.length) {
            professionals = data.professionals;
        } else {
            /* ── Fallback sample data (remove when API is live) ── */
            professionals = [
                { id: 1, name: 'Dr. Maria Santos',  specialty: 'General Medicine' },
                { id: 2, name: 'Dr. James Reyes',   specialty: 'Dental' },
                { id: 3, name: 'Dr. Clara Dizon',   specialty: 'Physical Exam' },
            ];
        }
    } catch (_) {
        /* ── Fallback when API is unreachable ── */
        professionals = [
            { id: 1, name: 'Dr. Maria Santos',  specialty: 'General Medicine' },
            { id: 2, name: 'Dr. James Reyes',   specialty: 'Dental' },
            { id: 3, name: 'Dr. Clara Dizon',   specialty: 'Physical Exam' },
        ];
    }

    populateProfessionalSelect();
}

function populateProfessionalSelect() {
    const sel = $('professionalSelect');
    sel.innerHTML = '';
    professionals.forEach(p => {
        const opt   = document.createElement('option');
        opt.value   = p.id;
        opt.textContent = p.name;
        sel.appendChild(opt);
    });
    currentProfId = professionals[0]?.id ?? null;
    refreshGrid();
}

/* ── Grid ───────────────────────────────────── */
function getWeekStart(offset) {
    const d = new Date();
    d.setDate(d.getDate() - d.getDay() + offset * 7);
    d.setHours(0, 0, 0, 0);
    return d;
}

function fmtDate(d, opts) {
    return d.toLocaleDateString('en-US', opts || { month: 'short', day: 'numeric' });
}

function isoDate(d) {
    return d.toISOString().slice(0, 10);
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
        th.innerHTML  = DAY_KEYS[i] + `<br><span style="font-size:.65rem;font-weight:600;color:${isToday ? 'var(--blue-600)' : 'var(--gray-300)'}">${dt.getDate()}</span>`;
        th.className  = isToday ? 'today-col' : '';
    });
}

async function refreshGrid() {
    updateWeekLabel();
    updateHeaders();
    await loadSlots();
    buildGrid();
    updateStats();
}

/* ── Load Slots from API ────────────────────── */
async function loadSlots() {
    const ws = isoDate(getWeekStart(weekOffset));
    try {
        const res  = await fetch(`${API.slots}?professional_id=${currentProfId}&week_start=${ws}`);
        const data = await res.json();
        if (data.status === 'ok') {
            slotsData = data.slots; // e.g. { "1-9:00": { booking: {...}, disabled: false, notes: "" } }
            return;
        }
    } catch (_) { /* fall through to sample */ }

    /* ── Sample slot data (remove when API is live) ── */
    slotsData = buildSampleSlots();
}

/* ── Sample Data Generator (remove when API ready) ── */
function buildSampleSlots() {
    const sample = {};

    /* Default: Sunday & Saturday fully blocked */
    TIMES.forEach(t => {
        [0, 6].forEach(d => {
            sample[`${d}-${t.label}`] = { disabled: true, booking: null, notes: '' };
        });
    });

    /* Specific bookings */
    if (String(currentProfId) === '1') {
        sample['1-8:00']  = { disabled: false, notes: 'Walk-in confirmed', booking: { patient: 'Lara Mendoza',     id: '2021-00142', program: 'BSCS 3A',        type: 'general',  purpose: 'Headache & low-grade fever' } };
        sample['1-10:00'] = { disabled: false, notes: '',                   booking: { patient: 'Rico Abellanosa', id: '2022-00398', program: 'BSBA 2B',        type: 'general',  purpose: 'Follow-up consultation' } };
        sample['3-9:00']  = { disabled: false, notes: '',                   booking: { patient: 'Sheena Calda',    id: '2020-00271', program: 'BSN 4A',         type: 'general',  purpose: 'Sore throat & colds' } };
        sample['4-2:00']  = { disabled: false, notes: 'Priority patient',   booking: { patient: 'Mark Villar',     id: '2023-00014', program: 'BSIT 1C',        type: 'general',  purpose: 'Allergic reaction check' } };
        sample['5-11:00'] = { disabled: false, notes: '',                   booking: { patient: 'Pia Torres',      id: '2021-00889', program: 'Faculty – CITE', type: 'general',  purpose: 'Routine check-up' } };
        sample['2-3:00']  = { disabled: true,  booking: null,               notes: 'Doctor on leave' };
    }
    if (String(currentProfId) === '2') {
        sample['1-9:00']  = { disabled: false, notes: '',                   booking: { patient: 'Ana Reyes',       id: '2022-00561', program: 'BSA 2A',         type: 'dental',   purpose: 'Toothache — lower molar' } };
        sample['3-10:00'] = { disabled: false, notes: '',                   booking: { patient: 'Carlo Delos Santos', id: '2021-00773', program: 'BSME 4B',     type: 'dental',   purpose: 'Tooth extraction follow-up' } };
        sample['2-3:00']  = { disabled: true,  booking: null,               notes: 'Equipment maintenance' };
    }
    if (String(currentProfId) === '3') {
        sample['2-8:00']  = { disabled: false, notes: '',                   booking: { patient: 'Ben Cruz',        id: '2023-00091', program: 'BSCRIM 1A',      type: 'physical', purpose: 'PE requirements — enrollment' } };
        sample['4-11:00'] = { disabled: false, notes: '',                   booking: { patient: 'Jessa Umali',     id: '2022-00344', program: 'BSEduc 3A',      type: 'physical', purpose: 'Annual physical examination' } };
    }

    return sample;
}

/* ── Build Grid DOM ─────────────────────────── */
function buildGrid() {
    const ws    = getWeekStart(weekOffset);
    const today = new Date(); today.setHours(0, 0, 0, 0);
    const tbody = $('scheduleBody');
    tbody.innerHTML = '';

    TIMES.forEach((timeObj, ti) => {
        /* Inject lunch break row before PM slots */
        if (ti === 4) {
            const lr = el('tr', 'lunch-row');
            lr.innerHTML = `<td colspan="8"><i class="fa-solid fa-utensils"></i>Lunch Break — 12:00 to 1:00 PM</td>`;
            tbody.appendChild(lr);
        }

        const tr = el('tr');

        /* Time label cell */
        const tc = el('td', 'time-cell');
        tc.innerHTML = `${timeObj.label}<span class="time-period">${timeObj.period}</span>`;
        tr.appendChild(tc);

        /* Day slot cells */
        DAY_KEYS.forEach((_, di) => {
            const dt      = new Date(ws); dt.setDate(dt.getDate() + di);
            const isToday = dt.getTime() === today.getTime();
            const key     = `${di}-${timeObj.label}`;
            const slot    = slotsData[key] || { disabled: false, booking: null, notes: '' };

            const td = el('td');
            td.className = 'slot-cell'
                + (isToday       ? ' today-col' : '')
                + (slot.disabled ? ' blocked'   : '');

            td.dataset.day  = di;
            td.dataset.time = timeObj.label;

            if (!slot.disabled) {
                td.addEventListener('click', () => openModal(di, timeObj.label));

                if (slot.booking) {
                    const chip = buildChip(slot.booking);
                    td.appendChild(chip);
                } else {
                    const hint = el('span', 'slot-add-hint');
                    hint.innerHTML = '<i class="fa-solid fa-plus" style="font-size:.65rem"></i>Add';
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

/* ── Stats ──────────────────────────────────── */
function updateStats() {
    const ws = isoDate(getWeekStart(weekOffset));

    /* Optimistic local count */
    let booked = 0, blocked = 0, open = 0;
    TIMES.forEach(t => {
        DAY_KEYS.forEach((_, di) => {
            const slot = slotsData[`${di}-${t.label}`] || {};
            if (slot.disabled)      blocked++;
            else if (slot.booking)  booked++;
            else                    open++;
        });
    });

    $('statBookings').textContent      = booked;
    $('statAvailable').textContent     = open;
    $('statDisabled').textContent      = blocked;
    $('statProfessionals').textContent = professionals.length;
}

/* ── Modal ──────────────────────────────────── */
function openModal(dayIdx, timeLabel) {
    const ws      = getWeekStart(weekOffset);
    const dt      = new Date(ws); dt.setDate(dt.getDate() + dayIdx);
    const slot    = slotsData[`${dayIdx}-${timeLabel}`] || { disabled: false, booking: null, notes: '' };
    const prof    = professionals.find(p => String(p.id) === String(currentProfId));

    activeSlot   = { dayIdx, timeLabel };
    pendingAvail = !slot.disabled;

    /* Header */
    const isPM = parseInt(timeLabel) >= 12 || timeLabel.includes('PM');
    $('modalSlotTime').textContent = timeLabel + (parseInt(timeLabel) < 12 ? ' AM' : ' PM');
    $('modalDayFull').textContent  = DAY_FULL[dayIdx] + ', ' + fmtDate(dt, { month: 'long', day: 'numeric', year: 'numeric' });
    $('modalProfName').innerHTML   = prof
        ? `<i class="fa-solid fa-user-doctor" style="margin-right:5px;color:var(--blue-500)"></i>${escHtml(prof.name)} — ${escHtml(prof.specialty)}`
        : '—';

    /* Notes */
    $('slotNotes').value = slot.notes || '';

    /* Availability UI */
    renderAvailDisplay(!slot.disabled);

    /* Booking content */
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
    const dot   = $('availDot');
    const txt   = $('availText');
    const btnE  = $('btnEnableSlot');
    const btnD  = $('btnDisableSlot');

    if (available) {
        dot.className       = 'avail-status-dot available';
        txt.textContent     = 'Slot is Available for Booking';
        btnE.className      = 'toggle-btn active-enable';
        btnD.className      = 'toggle-btn';
    } else {
        dot.className       = 'avail-status-dot unavailable';
        txt.textContent     = 'Slot is Blocked / Unavailable';
        btnE.className      = 'toggle-btn';
        btnD.className      = 'toggle-btn active-disable';
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
                    <div class="bd-type-badge ${booking.type}">${escHtml(typeLabel)}</div>
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
            </div>
        </div>`;
}

/* ── Save Slot ──────────────────────────────── */
async function saveSlot() {
    if (!activeSlot) return;

    const { dayIdx, timeLabel } = activeSlot;
    const ws    = isoDate(getWeekStart(weekOffset));
    const notes = $('slotNotes').value.trim();

    const payload = {
        professional_id : currentProfId,
        week_start      : ws,
        day_index       : dayIdx,
        time_label      : timeLabel,
        disabled        : !pendingAvail,
        notes           : notes,
    };

    try {
        const res  = await fetch(API.saveSlot, {
            method  : 'POST',
            headers : { 'Content-Type': 'application/json' },
            body    : JSON.stringify(payload),
        });
        const data = await res.json();

        if (data.status !== 'ok') throw new Error(data.message || 'Save failed');
    } catch (_) {
        /* ── Optimistic local update when API not yet live ── */
    }

    /* Apply locally */
    const key  = `${dayIdx}-${timeLabel}`;
    slotsData[key] = slotsData[key] || { booking: null };
    slotsData[key].disabled = !pendingAvail;
    slotsData[key].notes    = notes;

    closeModal();
    buildGrid();
    updateStats();
    showToast('Slot updated successfully', 'success');
}

/* ── Export (stub) ──────────────────────────── */
function exportSchedule() {
    const ws   = isoDate(getWeekStart(weekOffset));
    const prof = professionals.find(p => String(p.id) === String(currentProfId));
    showToast(`Exporting schedule for ${prof?.name ?? 'professional'} (${ws})…`, 'info');
    /* Wire to a real PDF/CSV export endpoint as needed */
}

/* ── Helpers ────────────────────────────────── */
function showToast(msg, type = 'success') {
    const t       = $('scheduleToast');
    const icons   = { success: 'fa-circle-check', error: 'fa-circle-exclamation', info: 'fa-circle-info' };
    t.innerHTML   = `<i class="fa-solid ${icons[type] || 'fa-circle-info'}" style="margin-right:6px"></i>${msg}`;
    t.className   = `schedule-toast ${type} show`;
    clearTimeout(t._timer);
    t._timer      = setTimeout(() => { t.className = 'schedule-toast'; }, 3000);
}

function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}