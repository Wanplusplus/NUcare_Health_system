/**
 * records.js  —  NUcare Health System · Records Module
 * ─────────────────────────────────────────────────────────────
 * Handles:
 *   • Records list table (paginated, filterable, sortable)
 *   • VIEW button → Patient Record Modal
 *       Tab 1 – Patient Info   (personal + academic + diseases + summary strip)
 *       Tab 2 – Clinic History (filterable timeline)
 *       Tab 3 – Emergencies
 *       Tab 4 – Certificates / Attachments
 *
 * Data sources (all via AJAX):
 *   get_records_ajax.php          → list of all school_people + enrollment + visit stats
 *   get_patient_record_ajax.php   → full profile, transactions, emergencies, certificates
 *
 * Version  : 3.1
 * Depends  : FontAwesome 6, app.css, records.css
 */

(function () {
    'use strict';

    /* ══════════════════════════════════════════════════════════
       STATE
    ══════════════════════════════════════════════════════════ */
    let allRecords  = [];
    let filtered    = [];
    let currentPage = 1;
    const PAGE_SIZE = 15;
    let activeModal = null;           // SchoolPersonID of open modal (or null)
    let activeRecordData = null;

    /* ══════════════════════════════════════════════════════════
       BOOT
    ══════════════════════════════════════════════════════════ */
    document.addEventListener('DOMContentLoaded', function () {
        loadRecords();
        bindFilters();
        bindModalClose();
    });

    /* ══════════════════════════════════════════════════════════
       1.  RECORDS LIST
    ══════════════════════════════════════════════════════════ */

    function loadRecords() {
        setTableLoading(true);

        fetch('../../ajax/get_records.ajax.php')
            .then(r => r.json())
            .then(data => {
                if (!data.ok) throw new Error(data.message || 'Server error');
                allRecords = data.records || [];
                updateStats(data.stats || {});
                applyFilters();
            })
            .catch(err => {
                console.warn('[Records] Failed to load records list:', err.message);
                allRecords = [];
                updateStats({ total: 0, students: 0, faculty: 0, staff: 0 });
                applyFilters();
            });
    }

    function updateStats(stats) {
        setEl('statTotal',    stats.total    ?? 0);
        setEl('statStudents', stats.students ?? 0);
        setEl('statFaculty',  stats.faculty  ?? 0);
        setEl('statStaff',    stats.staff    ?? 0);
    }

    /* ── Filters ─────────────────────────────────────────── */

    function bindFilters() {
        document.getElementById('recordSearchInput')?.addEventListener('input',  debounce(applyFilters, 220));
        document.getElementById('filterType')?.addEventListener('change',   applyFilters);
        document.getElementById('filterStatus')?.addEventListener('change',  applyFilters);
        document.getElementById('filterSort')?.addEventListener('change',   applyFilters);
    }

    function applyFilters() {
        const q      = (document.getElementById('recordSearchInput')?.value || '').toLowerCase().trim();
        const type   = document.getElementById('filterType')?.value   || '';
        const status = document.getElementById('filterStatus')?.value || '';
        const sort   = document.getElementById('filterSort')?.value   || 'name_asc';

        filtered = allRecords.filter(r => {
            const matchQ = !q
                || (r.fullName   || '').toLowerCase().includes(q)
                || (r.schoolID   || '').toLowerCase().includes(q)
                || (r.program    || '').toLowerCase().includes(q)
                || (r.department || '').toLowerCase().includes(q);

            const matchType   = !type   || r.personType === type;
            const matchStatus = !status || r.status     === status;
            return matchQ && matchType && matchStatus;
        });

        filtered.sort((a, b) => {
            if (sort === 'name_asc')    return (a.fullName || '').localeCompare(b.fullName || '');
            if (sort === 'name_desc')   return (b.fullName || '').localeCompare(a.fullName || '');
            if (sort === 'visits_desc') return (b.visitCount || 0) - (a.visitCount || 0);
            if (sort === 'recent')      return new Date(b.lastVisit || 0) - new Date(a.lastVisit || 0);
            return 0;
        });

        currentPage = 1;
        renderTable();
        renderPagination();
        setTableLoading(false);
    }

    /* ── Table render ────────────────────────────────────── */

    function renderTable() {
        const tbody = document.getElementById('recordsTbody');
        if (!tbody) return;

        const start = (currentPage - 1) * PAGE_SIZE;
        const page  = filtered.slice(start, start + PAGE_SIZE);

        if (page.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8">
                <div class="empty-state">
                    <i class="fa-solid fa-folder-open"></i>
                    <p>No records found</p>
                    <span>Try adjusting your search or filters</span>
                </div>
            </td></tr>`;
            return;
        }

        tbody.innerHTML = page.map(r => `
            <tr onclick="openRecord(${r.schoolPersonID})" data-userid="${r.schoolPersonID}" tabindex="0"
                onkeydown="if(event.key==='Enter')openRecord(${r.schoolPersonID})">
                <td><span class="td-id">${escHtml(r.schoolID)}</span></td>
                <td>
                    <div class="td-name">${escHtml(r.fullName)}</div>
                    <div class="td-sub">${escHtml(r.email || '—')}</div>
                </td>
                <td>${typeBadge(r.personType)}</td>
                <td>
                    <div style="font-size:.82rem;font-weight:700;">${escHtml(r.program || r.department || '—')}</div>
                    ${r.yearSection ? `<div class="td-sub">${escHtml(r.yearSection)}</div>` : ''}
                </td>
                <td><span class="status-badge ${r.status === 'Active' ? 'active' : 'inactive'}">${escHtml(r.status)}</span></td>
                <td><span class="visits-count"><i class="fa-solid fa-calendar-check"></i>${r.visitCount ?? 0}</span></td>
                <td>${r.lastVisit ? formatDate(r.lastVisit) : '<span style="color:var(--gray-300)">—</span>'}</td>
                <td>
                    <button class="btn-view-record"
                            onclick="event.stopPropagation();openRecord(${r.schoolPersonID})"
                            aria-label="View record for ${escHtml(r.fullName)}">
                        <i class="fa-solid fa-eye"></i> View
                    </button>
                </td>
            </tr>`).join('');
    }

    /* ── Pagination ──────────────────────────────────────── */

    function renderPagination() {
        const total = filtered.length;
        const pages = Math.max(1, Math.ceil(total / PAGE_SIZE));
        const start = total === 0 ? 0 : (currentPage - 1) * PAGE_SIZE + 1;
        const end   = Math.min(currentPage * PAGE_SIZE, total);

        setEl('paginationInfo', `Showing ${start}–${end} of ${total} records`);

        const container = document.getElementById('paginationBtns');
        if (!container) return;

        let html = `<button class="page-btn" onclick="Records.goPage(${currentPage - 1})"
                        ${currentPage <= 1 ? 'disabled' : ''} aria-label="Previous page">
                        <i class="fa-solid fa-chevron-left" style="font-size:.65rem;"></i>
                    </button>`;

        for (let i = 1; i <= pages; i++) {
            if (pages > 7 && i > 2 && i < pages - 1 && Math.abs(i - currentPage) > 1) {
                if (i === 3 || i === pages - 2) html += `<button class="page-btn" disabled>…</button>`;
                continue;
            }
            html += `<button class="page-btn ${i === currentPage ? 'active' : ''}"
                             onclick="Records.goPage(${i})"
                             aria-label="Page ${i}" aria-current="${i === currentPage ? 'page' : 'false'}">${i}</button>`;
        }

        html += `<button class="page-btn" onclick="Records.goPage(${currentPage + 1})"
                    ${currentPage >= pages ? 'disabled' : ''} aria-label="Next page">
                    <i class="fa-solid fa-chevron-right" style="font-size:.65rem;"></i>
                 </button>`;

        container.innerHTML = html;
    }

    window.Records = {
        goPage(p) {
            const pages = Math.max(1, Math.ceil(filtered.length / PAGE_SIZE));
            if (p < 1 || p > pages) return;
            currentPage = p;
            renderTable();
            renderPagination();
            document.getElementById('recordsTbody')
                    ?.closest('.records-card')
                    ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };

    /* ══════════════════════════════════════════════════════════
       2.  MODAL — OPEN / CLOSE / TABS
    ══════════════════════════════════════════════════════════ */

    /**
     * openRecord(userID)
     * ──────────────────
     * Called by the VIEW button or row click.
     * Fetches get_patient_record_ajax.php and populates modal.
     *
     * FIX: URL corrected to match actual file location and name.
     *      (was: ../../ajax/get_patient_record.ajax.php)
     */
    window.openRecord = function (userID) {
        const backdrop = document.getElementById('recordModal');
        if (!backdrop) return;

        backdrop.classList.add('open');
        document.body.style.overflow = 'hidden';
        activeModal = userID;

        // Always hide the edit form when opening a new record (prevents stale state)
        const editForm = document.getElementById('recordsPatientInfoForm');
        if (editForm) editForm.style.display = 'none';

        switchTab('tabInfo');
        showModalSkeleton();

            fetch(`../../ajax/get_patient_record.ajax.php?school_person_id=${userID}`)
            .then(r => r.json())
            .then(data => {
                if (!data.ok) throw new Error(data.message || 'Not found');
                populateModal(data);
            })
            .catch(err => {
                console.warn('[Records] Failed to load patient record:', err.message);
                populateEmptyModal();
            });
    };

    function populateEmptyModal() {
        populateModal({
            ok: true,
            patient: {},
            diseases: [],
            transactions: [],
            emergencies: [],
            certificates: [],
        });
    }

    function closeModal() {
        document.getElementById('recordModal')?.classList.remove('open');
        document.body.style.overflow = '';
        activeModal = null;
    }

    function bindModalClose() {
        document.getElementById('modalCloseBtn')?.addEventListener('click', closeModal);
        document.getElementById('txDetailCloseBtn')?.addEventListener('click', closeTransactionModal);

        document.getElementById('modalPrintBtn')?.addEventListener('click', function () {
            const name = document.getElementById('modalPatientName')?.textContent || 'Record';
            const id   = document.getElementById('modalPatientID')?.textContent   || '';
            window._nucarePrintTarget = { name, id };
            window.print();
        });

        document.getElementById('recordModal')?.addEventListener('click', function (e) {
            if (e.target === this) closeModal();
        });

        document.getElementById('transactionDetailModal')?.addEventListener('click', function (e) {
            if (e.target === this) closeTransactionModal();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                if (document.getElementById('transactionDetailModal')?.classList.contains('open')) {
                    closeTransactionModal();
                    return;
                }
                if (activeModal !== null) closeModal();
            }
        });

        document.querySelector('.modal-tabs')?.addEventListener('click', function (e) {
            const tab = e.target.closest('.modal-tab');
            if (!tab || !this.contains(tab)) return;
            e.preventDefault();
            switchTab(tab.dataset.tab);
        });

        document.querySelector('.modal-tabs')?.addEventListener('wheel', function (e) {
            if (Math.abs(e.deltaY) <= Math.abs(e.deltaX)) return;
            this.scrollLeft += e.deltaY;
            e.preventDefault();
        }, { passive: false });

        // Event delegation: the edit button can be affected by modal re-rendering/skeleton swaps.
        // Delegate off the modal body so clicks always work.
        const modalBody = document.getElementById('recordModal');
        modalBody?.addEventListener('click', function (e) {
            const btn = e.target.closest('#togglePatientInfoEdit');
            if (!btn) return;

            try {
                e.preventDefault();
                e.stopPropagation();

                const form = document.getElementById('recordsPatientInfoForm');
                if (!form) {
                    console.warn('[Records] Edit form not found (recordsPatientInfoForm).');
                    return;
                }

                const isHidden = form.style.display === 'none' || getComputedStyle(form).display === 'none';
                
                // FIX: Populate form BEFORE showing it so fields have current data
                if (isHidden) {
                    populatePatientInfoForm();
                }
                
                form.style.display = isHidden ? 'block' : 'none';

                if (isHidden) {
                    form.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            } catch (err) {
                console.error('[Records] togglePatientInfoEdit failed:', err);
            }
        });

        document.getElementById('cancelPatientInfoEdit')?.addEventListener('click', function () {
            const form = document.getElementById('recordsPatientInfoForm');
            if (form) form.style.display = 'none';
        });

        document.getElementById('recordsPatientInfoForm')?.addEventListener('submit', saveRecordsPatientInfo);
        document.getElementById('recordsFamilyHistoryForm')?.addEventListener('submit', saveRecordsFamilyHistory);
        document.getElementById('addRecordsFamilyRow')?.addEventListener('click', function () {
            addRecordsFamilyRow();
        });
    }

    function switchTab(tabId) {
        document.querySelectorAll('.modal-tab').forEach(b => {
            b.classList.remove('active');
            b.setAttribute('aria-selected', 'false');
        });
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));

        const activeBtn = document.querySelector(`.modal-tab[data-tab="${tabId}"]`);
        if (activeBtn) {
            activeBtn.classList.add('active');
            activeBtn.setAttribute('aria-selected', 'true');
            activeBtn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
        document.getElementById(tabId)?.classList.add('active');
    }

    window.filterHistory = function (type, btn) {
        document.querySelectorAll('.history-filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const items = document.querySelectorAll('.timeline-item');
        let visible = 0;
        items.forEach(item => {
            const show = (type === 'all' || item.dataset.type === type);
            item.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        const countEl = document.getElementById('historyVisibleCount');
        if (countEl) countEl.textContent = `${visible} record${visible !== 1 ? 's' : ''}`;
    };

    window.filterCerts = function (type, btn) {
        document.querySelectorAll('.cert-filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const items = document.querySelectorAll('.cert-card');
        let visible = 0;
        items.forEach(item => {
            const show = (type === 'all' || item.dataset.cat === type);
            item.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        const countEl = document.getElementById('certVisibleCount');
        if (countEl) countEl.textContent = `${visible} file${visible !== 1 ? 's' : ''}`;
    };

    /* ══════════════════════════════════════════════════════════
       3.  MODAL POPULATION
    ══════════════════════════════════════════════════════════ */

    function populateModal(data) {
        activeRecordData = data;
        const p = data.patient || {};
        const pi = p.patientsInfo || {};

        /* ── Modal header ── */
        const fullName = p.fullName || [p.firstName, p.middleName, p.lastName].filter(Boolean).join(' ') || '—';
        setEl('modalPatientName',    fullName);
        setEl('modalPatientID',      p.schoolID   || '—');
        setEl('modalPatientType',    p.personType || '—');
        setEl('modalPatientSex',     p.sex        || '—');
        setEl('modalPatientProgram', p.program || p.department || '—');

        /* Initials avatar */
        const avatarEl = document.getElementById('modalAvatarIcon');
        if (avatarEl) {
            const parts    = fullName.trim().split(/\s+/);
            const initials = ((parts[0]?.[0] ?? '') + (parts[parts.length - 1]?.[0] ?? '')).toUpperCase();
            avatarEl.innerHTML = `<span class="avatar-initials-text">${escHtml(initials)}</span>`;
        }

        /* ── Tab: Patient Info ── */
        setEl('infoSchoolID',    p.schoolID      || '—');
        setEl('infoFullName',    fullName);
        setEl('infoSex',         p.sex           || '—');
        setEl('infoBirthday',    p.birthday      || '—');
        setEl('infoEmail',       p.email         || '—');
        setEl('infoContact',     pi.contact_no || p.contactNumber || '—');
        setEl('infoAge',         pi.age || '—');
        setEl('infoNationality', pi.nationality || '—');
        setEl('infoReligion',    pi.religion || '—');
        setEl('infoPatientStatus', pi.status || '—');
        setEl('infoAddress',     pi.address || '—');
        setEl('infoGuardianName', pi.guardian_name || '—');
        setEl('infoRelationship', pi.relationship || '—');
        setEl('infoMobileNo',     pi.mobile_no || '—');
        setEl('infoTelephone',    pi.telephone || '—');
        setEl('infoEmergencyAddress', pi.emergency_address || '—');
        setEl('infoPersonTypes', p.personType    || '—');
        setEl('infoProgram',     p.program || p.positionTitle || p.department || '—');
        setEl('infoDepartment',  p.department    || '—');
        setEl('infoSection',     p.yearSection   || '—');
        setEl('infoStatus',      p.status || p.enrollmentStatus || p.employmentStatus || '—');
        setEl('infoAcadYear',    p.academicYear  || '—');

        /* Known conditions */
        const diseasesEl = document.getElementById('infoDiseases');
        if (diseasesEl) {
            diseasesEl.innerHTML = (data.diseases && data.diseases.length)
                ? data.diseases.map(d =>
                    `<span class="disease-tag">${escHtml(d.diseaseName)}${d.notes
                        ? ` <small>(${escHtml(d.notes)})</small>` : ''}</span>`).join('')
                : '<span class="disease-tag empty">No known conditions on record.</span>';
        }

        /* Summary strip */
        const txList = data.transactions || [];
        setEl('summaryVisits',      txList.length);
        setEl('summaryEmergencies', (data.emergencies  || []).length);
        setEl('summaryCerts',       (data.certificates || []).length);
        setEl('summaryLastVisit',   txList[0]?.visitDate ? formatDate(txList[0].visitDate) : '—');

        /* Tab count badges */
        setEl('tabHistoryCount',   txList.length || '');
        setEl('tabMedicalProfileCount', (data.medicalProfile || []).length || '');
        setEl('tabFamilyHistoryCount', (data.familyHistory || []).length || '');
        setEl('tabEmergencyCount', (data.emergencies  || []).length || '');
        setEl('tabCertsCount',     (data.certificates || []).length || '');

        /* ── Other tabs ── */
        fillPatientInfoEditForm(p);
        renderTimeline(txList);
        renderMedicalProfile(data.medicalProfile || []);
        renderFamilyHistory(data.familyHistory || []);
        renderEmergencies(data.emergencies  || []);
        renderCertificates(data.certificates || []);
    }

    /* ══════════════════════════════════════════════════════════
       4.  TIMELINE (Clinic History)
    ══════════════════════════════════════════════════════════ */

    function fillPatientInfoEditForm(patient) {
        const pi = patient.patientsInfo || {};
        const map = {
            editSchoolPersonID: patient.schoolPersonID || activeModal || '',
            edit_contact_no: pi.contact_no || patient.contactNumber || '',
            edit_gender: pi.gender || patient.sex || '',
            edit_birth_date: pi.birth_date || patient.birthday || '',
            edit_age: pi.age || '',
            edit_nationality: pi.nationality || '',
            edit_status: pi.status || '',
            edit_religion: pi.religion || '',
            edit_address: pi.address || '',
            edit_guardian_name: pi.guardian_name || '',
            edit_relationship: pi.relationship || '',
            edit_mobile_no: pi.mobile_no || '',
            edit_telephone: pi.telephone || '',
            edit_emergency_address: pi.emergency_address || '',
        };

        Object.entries(map).forEach(([id, value]) => {
            const el = document.getElementById(id);
            if (el) el.value = value ?? '';
        });
    }

    function renderMedicalProfile(items) {
        const container = document.getElementById('medicalProfileList');
        if (!container) return;

        if (!items.length) {
            container.innerHTML = `<div class="no-history">
                <i class="fa-solid fa-clipboard-list"></i>
                <p>No physical examination data found.</p>
                <span>Physical examination records entered in Consultation will appear here.</span>
            </div>`;
            return;
        }

        container.innerHTML = items.map(item => `
            <div class="medical-profile-card">
                <div class="medical-profile-head">
                    <span><i class="fa-solid fa-calendar-check"></i> ${escHtml(formatDate(item.examDate || item.visitDate))}</span>
                    <span>${escHtml(item.cardioClearance || item.status || '—')}</span>
                </div>
                <div class="info-fields">
                    ${item.height ? detailField('Height', item.height + ' cm') : ''}
                    ${item.weight ? detailField('Weight', item.weight + ' kg') : ''}
                    ${item.bloodPressure ? detailField('Blood Pressure', item.bloodPressure) : ''}
                    ${item.pulseRate ? detailField('Pulse Rate', item.pulseRate + ' bpm') : ''}
                    ${item.ears ? detailField('Ears', item.ears) : ''}
                    ${item.eyesPupil ? detailField('Eyes / Pupil', item.eyesPupil) : ''}
                    ${item.heart ? detailField('Heart', item.heart) : ''}
                    ${item.nose ? detailField('Nose', item.nose) : ''}
                    ${item.thorax ? detailField('Thorax', item.thorax) : ''}
                    ${item.abdomen ? detailField('Abdomen', item.abdomen) : ''}
                    ${item.lungs ? detailField('Lungs', item.lungs) : ''}
                    ${item.skin ? detailField('Skin', item.skin) : ''}
                    ${item.extremities ? detailField('Extremities', item.extremities) : ''}
                    ${item.deformities ? detailField('Deformities', item.deformities) : ''}
                    ${item.cardioClearance ? detailField('Cardio Clearance', item.cardioClearance) : ''}
                </div>
            </div>
        `).join('');
    }

    function addRecordsFamilyRow(item = {}) {
        const container = document.getElementById('recordsFamilyRows');
        if (!container) return;

        const row = document.createElement('div');
        row.className = 'records-family-row';
        row.innerHTML = `
            <label>Condition <input name="condition_name" value="${escAttr(item.condition_name || '')}" placeholder="e.g. Diabetes"></label>
            <label>Relationship <input name="family_relationship" value="${escAttr(item.relationship || '')}" placeholder="e.g. Father"></label>
            <label>Notes <input name="family_notes" value="${escAttr(item.notes || '')}" placeholder="Optional notes"></label>
            <button type="button" class="records-family-remove" aria-label="Remove"><i class="fa-solid fa-xmark"></i></button>
        `;
        row.querySelector('.records-family-remove')?.addEventListener('click', () => row.remove());
        container.appendChild(row);
    }

    function renderFamilyHistory(items) {
        const container = document.getElementById('recordsFamilyRows');
        if (!container) return;
        container.innerHTML = '';
        (items || []).forEach(addRecordsFamilyRow);
        if (!container.children.length) addRecordsFamilyRow();
    }

    function collectRecordsFamilyHistory() {
        return Array.from(document.querySelectorAll('#recordsFamilyRows .records-family-row')).map(row => ({
            condition_name: row.querySelector('[name="condition_name"]')?.value.trim() || '',
            relationship: row.querySelector('[name="family_relationship"]')?.value.trim() || '',
            notes: row.querySelector('[name="family_notes"]')?.value.trim() || '',
        })).filter(item => item.condition_name || item.relationship || item.notes);
    }

    function saveRecordsPatientInfo(event) {
        event.preventDefault();
        if (!activeModal) return;

        const form = document.getElementById('recordsPatientInfoForm');
        if (!form) return;

        // Disable the save button to prevent double-submit
        const saveBtn = document.getElementById('savePatientInfoEdit');
        if (saveBtn) { 
            saveBtn.disabled = true; 
            saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…'; 
        }

        // Ensure school_person_id is in the form data
        const body = new FormData(form);
        body.set('school_person_id', String(activeModal));
        body.set('family_history', JSON.stringify(collectRecordsFamilyHistory()));

        fetch('../../ajax/records_patient_info_save.ajax.php', { method: 'POST', body })
            .then(r => {
                // Catch session/auth errors explicitly so nurses see the real reason
                if (r.status === 401) throw new Error('Session expired. Please refresh the page and log in again.');
                if (!r.ok) throw new Error('Server error (' + r.status + '). Please try again.');
                return r.json();
            })
            .then(resp => {
                if (!resp.ok) {
                    // Surface the real server error (e.g. "does not have a linked user account")
                    throw new Error(resp.message || 'Save failed.');
                }
                
                // Re-fetch patient record from server so the modal always reflects DB state
                const userID = String(activeModal);
                fetch(`../../ajax/get_patient_record.ajax.php?school_person_id=${encodeURIComponent(userID)}`)
                    .then(rr => rr.json())
                    .then(refreshed => {
                        if (!refreshed.ok) throw new Error(refreshed.message || 'Failed to reload record');
                        populateModal(refreshed);
                        form.style.display = 'none';
                        showToast(resp.message || 'Patient information saved.', 'success');
                    })
                    .catch(reloadErr => {
                        console.error('[Records] Reload after save failed:', reloadErr);
                        showToast(resp.message || 'Patient information saved.', 'success');
                    });
            })
            .catch(err => {
                console.error('[Records] Save error:', err);
                showToast(err.message || 'Could not save. Please try again.', 'error');
            })
            .finally(() => {
                if (saveBtn) { 
                    saveBtn.disabled = false; 
                    saveBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save Changes'; 
                }
            });
    }

    function saveRecordsFamilyHistory(event) {
        event.preventDefault();
        if (!activeModal) return;

        const body = new FormData();
        body.set('school_person_id', String(activeModal));
        body.set('mode', 'family_only');
        body.set('family_history', JSON.stringify(collectRecordsFamilyHistory()));

        fetch('../../ajax/records_patient_info_save.ajax.php', { method: 'POST', body })
            .then(r => r.json())
            .then(resp => {
                if (!resp.ok) throw new Error(resp.message || 'Save failed.');
                if (activeRecordData) {
                    activeRecordData.familyHistory = resp.familyHistory || [];
                    renderFamilyHistory(activeRecordData.familyHistory);
                    setEl('tabFamilyHistoryCount', activeRecordData.familyHistory.length || '');
                }
                showToast('Family history saved.', 'success');
            })
            .catch(err => showToast(err.message, 'error'));
    }

    /**
     * populatePatientInfoForm()
     * ────────────────────────────
     * Fills the edit form with current patient data from activeRecordData
     * Called before showing the edit form to ensure it has the latest data
     */
    function populatePatientInfoForm() {
        if (!activeRecordData?.patient || !activeModal) return;
        
        const info = activeRecordData.patient.patientsInfo || {};
        
        // Set the hidden school_person_id
        const spidInput = document.getElementById('editSchoolPersonID');
        if (spidInput) spidInput.value = String(activeModal);
        
        // Populate personal information fields
        document.getElementById('edit_contact_no').value     = info.contact_no || '';
        document.getElementById('edit_gender').value         = info.gender || '';
        document.getElementById('edit_birth_date').value     = info.birth_date || '';
        document.getElementById('edit_age').value            = info.age || '';
        document.getElementById('edit_nationality').value    = info.nationality || '';
        document.getElementById('edit_status').value         = info.status || '';
        document.getElementById('edit_religion').value       = info.religion || '';
        document.getElementById('edit_address').value        = info.address || '';
        
        // Populate emergency contact fields
        document.getElementById('edit_guardian_name').value  = info.guardian_name || '';
        document.getElementById('edit_relationship').value   = info.relationship || '';
        document.getElementById('edit_mobile_no').value      = info.mobile_no || '';
        document.getElementById('edit_telephone').value      = info.telephone || '';
        document.getElementById('edit_emergency_address').value = info.emergency_address || '';
    }

    function renderTimeline(transactions) {
        const container = document.getElementById('clinicTimeline');
        const countEl   = document.getElementById('historyVisibleCount');
        if (!container) return;

        if (!transactions.length) {
            container.innerHTML = `
                <div class="no-history">
                    <i class="fa-solid fa-notes-medical"></i>
                    <p>No clinic visits on record yet.</p>
                    <span>This patient has no recorded clinic visits yet.</span>
                </div>`;
            if (countEl) countEl.textContent = '0 records';
            return;
        }

        if (countEl) countEl.textContent = `${transactions.length} record${transactions.length !== 1 ? 's' : ''}`;

        container.innerHTML = transactions.map(tx => {
            const type = resolveTimelineType(tx.serviceType);

            /* Medicines chips */
            const medChips = (tx.medicines || []).map(m =>
                `<span class="med-chip">
                    <i class="fa-solid fa-pills"></i>
                    ${escHtml(m.medicineName || m.MedicineName || 'Medicine')} ×${escHtml(String(m.quantityDispensed ?? m.qty ?? ''))}
                 </span>`
            ).join('');

            /* Vitals row — only render fields that have values */
            const vitalsFields = [
                tx.bloodPressure && detailField('Blood Pressure', tx.bloodPressure),
                tx.temperature   && detailField('Temperature',    tx.temperature + ' °C'),
                tx.pulseRate     && detailField('Pulse Rate',     tx.pulseRate + ' bpm'),
                tx.weight        && detailField('Weight',         tx.weight + ' kg'),
                tx.height        && detailField('Height',         tx.height + ' cm'),
                tx.medProfName   && detailField('Attended By',    tx.medProfName),
            ].filter(Boolean).join('');

            const physicalExam = tx.physicalExam
                ? `<div class="timeline-notes">
                       <div class="tl-label">Physical Exam</div>
                       <div class="timeline-detail-row">
                           ${tx.physicalExam.examDate ? detailField('Exam Date', formatDate(tx.physicalExam.examDate)) : ''}
                           ${tx.physicalExam.height ? detailField('Height', tx.physicalExam.height + ' cm') : ''}
                           ${tx.physicalExam.weight ? detailField('Weight', tx.physicalExam.weight + ' kg') : ''}
                           ${tx.physicalExam.bloodPressure ? detailField('Blood Pressure', tx.physicalExam.bloodPressure) : ''}
                           ${tx.physicalExam.pulseRate ? detailField('Pulse Rate', tx.physicalExam.pulseRate + ' bpm') : ''}
                           ${tx.physicalExam.ears ? detailField('Ears', tx.physicalExam.ears) : ''}
                           ${tx.physicalExam.eyesPupil ? detailField('Eyes / Pupil', tx.physicalExam.eyesPupil) : ''}
                           ${tx.physicalExam.heart ? detailField('Heart', tx.physicalExam.heart) : ''}
                           ${tx.physicalExam.nose ? detailField('Nose', tx.physicalExam.nose) : ''}
                           ${tx.physicalExam.thorax ? detailField('Thorax', tx.physicalExam.thorax) : ''}
                           ${tx.physicalExam.abdomen ? detailField('Abdomen', tx.physicalExam.abdomen) : ''}
                           ${tx.physicalExam.lungs ? detailField('Lungs', tx.physicalExam.lungs) : ''}
                           ${tx.physicalExam.skin ? detailField('Skin', tx.physicalExam.skin) : ''}
                           ${tx.physicalExam.extremities ? detailField('Extremities', tx.physicalExam.extremities) : ''}
                           ${tx.physicalExam.deformities ? detailField('Deformities', tx.physicalExam.deformities) : ''}
                           ${tx.physicalExam.cardioClearance ? detailField('Cardio Clearance', tx.physicalExam.cardioClearance) : ''}
                       </div>
                       ${tx.physicalExam.remarks ? `<div class="timeline-notes"><div class="tl-label">Exam Remarks</div><div class="tl-notes-body">${escHtml(tx.physicalExam.remarks)}</div></div>` : ''}
                   </div>`
                : '';

            /* Per-transaction attachments */
            const attachRows = (tx.attachments || []).map(a => {
                const attachmentLabel = a.documentType || a.certificateType || a.attachmentCategory || a.fileName || 'Attachment';
                const attachmentCategory = a.documentCategory || a.attachmentCategory || a.documentType || 'Other';
                return `
                <div class="tl-attachment">
                    <i class="fa-solid ${resolveFileIcon(a.fileType)}"></i>
                    <span class="tl-attach-name">${escHtml(attachmentLabel)}</span>
                    <span class="tl-attach-size">${escHtml(attachmentCategory)}${a.createdAt ? ` · ${formatDate(a.createdAt)}` : ''}</span>
                    ${a.fileSizeBytes ? `<span class="tl-attach-size">${formatFileSize(a.fileSizeBytes)}</span>` : ''}
                    ${a.viewUrl
                        ? `<a href="${escAttr(a.viewUrl)}" target="_blank" rel="noopener" class="tl-attach-link" onclick="event.stopPropagation()">
                               <i class="fa-solid fa-eye"></i> View
                           </a>`
                        : ''}
                    ${a.downloadUrl
                        ? `<a href="${escAttr(a.downloadUrl)}" target="_blank" rel="noopener" class="tl-attach-link" onclick="event.stopPropagation()">
                               <i class="fa-solid fa-download"></i> Download
                           </a>`
                        : ''}
                </div>`;
            }).join('');

            return `
            <div class="timeline-item" data-type="${type}" role="button" tabindex="0" onclick="openTransactionDetail(${tx.clinicTransactionID})" onkeydown="if(event.key==='Enter'||event.key===' ')openTransactionDetail(${tx.clinicTransactionID})">
                <div class="timeline-dot ${type}">
                    <i class="fa-solid ${typeIcon(tx.serviceType)}"></i>
                </div>
                <div class="timeline-content">
                    <div class="timeline-card">
                        <div class="timeline-card-header">
                            <div>
                                <div class="timeline-date">
                                    <i class="fa-regular fa-calendar"></i>
                                    ${formatDate(tx.visitDate)}
                                    ${tx.createdAt ? `· ${formatTime(tx.createdAt)}` : ''}
                                </div>
                                <div class="timeline-service">${escHtml(tx.serviceType || 'General Consultation')}</div>
                                ${tx.complaint
                                    ? `<div class="timeline-complaint">${escHtml(tx.complaint)}</div>`
                                    : ''}
                            </div>
                            <span class="consult-status-badge ${String(tx.status || tx.consultationStatus || '').toLowerCase().replace(/\s+/g, '-')}">
                                ${escHtml(tx.status || tx.consultationStatus || '—')}
                            </span>
                        </div>

                        ${vitalsFields
                            ? `<div class="timeline-detail-row">${vitalsFields}</div>`
                            : ''}

                        ${tx.notes
                            ? `<div class="timeline-notes">
                                   <div class="tl-label">Clinical Notes</div>
                                   <div class="tl-notes-body">${escHtml(tx.notes)}</div>
                               </div>`
                            : ''}

                        ${physicalExam}

                        ${medChips
                            ? `<div class="medicines-dispensed">
                                   <div class="medicines-label"><i class="fa-solid fa-pills"></i> Medicines Dispensed</div>
                                   <div class="med-chips">${medChips}</div>
                               </div>`
                            : ''}

                        ${attachRows
                            ? `<div class="tl-attachments">
                                   <div class="tl-label"><i class="fa-solid fa-paperclip"></i> Attachments</div>
                                   ${attachRows}
                               </div>`
                            : ''}
                    </div>
                </div>
            </div>`;
        }).join('');
    }

    /* ══════════════════════════════════════════════════════════
       5.  EMERGENCIES
    ══════════════════════════════════════════════════════════ */

    function renderEmergencies(emergencies) {
        const container = document.getElementById('emergencyList');
        if (!container) return;

        if (!emergencies.length) {
            container.innerHTML = `
                <div class="no-history">
                    <i class="fa-solid fa-kit-medical"></i>
                    <p>No emergency records found.</p>
                    <span>No emergency incidents have been logged for this patient.</span>
                </div>`;
            return;
        }

        container.innerHTML = emergencies.map(e => `
            <div class="emergency-card">
                <div class="emergency-card-header">
                    <div class="emergency-title">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        Emergency Incident
                    </div>
                    <span class="emergency-date">
                        ${formatDate(e.incidentDate)}
                        ${e.incidentTime ? ' · ' + escHtml(e.incidentTime) : ''}
                    </span>
                </div>
                <div class="emergency-fields">
                    ${e.incidentLocation ? detailField('Location',    e.incidentLocation) : ''}
                    ${e.bp               ? detailField('Blood Pres.', e.bp)               : ''}
                    ${e.hr               ? detailField('Heart Rate',  e.hr)               : ''}
                    ${e.rr               ? detailField('Resp. Rate',  e.rr)               : ''}
                    ${e.temperature      ? detailField('Temperature', e.temperature + ' °C') : ''}
                    ${e.treatmentGiven   ? detailField('Treatment',   e.treatmentGiven)   : ''}
                    ${e.ambulanceNo      ? detailField('Ambulance #', e.ambulanceNo)      : ''}
                </div>
            </div>`).join('');
    }

    /* ══════════════════════════════════════════════════════════
       6.  CERTIFICATES / ATTACHMENTS
    ══════════════════════════════════════════════════════════ */

    function renderCertificates(certs) {
        const container = document.getElementById('certList');
        const countEl   = document.getElementById('certVisibleCount');
        if (!container) return;

        if (!certs.length) {
            container.innerHTML = `
                <div class="no-history">
                    <i class="fa-solid fa-file-medical"></i>
                    <p>No medical certificates issued.</p>
                    <span>No attachments or certificates have been uploaded for this patient.</span>
                </div>`;
            if (countEl) countEl.textContent = '0 files';
            return;
        }

        if (countEl) countEl.textContent = `${certs.length} file${certs.length !== 1 ? 's' : ''}`;

        container.innerHTML = certs.map(c => {
            const fileIcon = resolveFileIcon(c.fileType);
            const fileSize = c.fileSizeBytes ? formatFileSize(c.fileSizeBytes) : '';
            const attachmentType = c.documentType || c.certificateType || c.attachmentCategory || 'Medical Document';
            const attachmentCategory = c.documentCategory || c.attachmentCategory || 'Other';
            const cat = resolveCertCategory(attachmentCategory || attachmentType);

            return `
            <div class="cert-card" data-cat="${cat}">
                <div class="cert-icon ${cat}">
                    <i class="fa-solid ${fileIcon}"></i>
                </div>
                <div class="cert-info">
                    <div class="cert-type">${escHtml(attachmentType)}</div>
                    <div class="cert-filename">
                        ${escHtml(attachmentCategory)}${c.createdAt ? ` <span class="cert-filesize">· ${formatDate(c.createdAt)}</span>` : ''}${fileSize ? ` <span class="cert-filesize">· ${fileSize}</span>` : ''}
                    </div>
                    <div class="cert-meta">
                        ${c.fileName ? `<span><i class="fa-regular fa-file"></i> ${escHtml(c.fileName)}</span>` : ''}
                        ${c.issuedByName ? `<span><i class="fa-solid fa-user-doctor"></i> ${escHtml(c.issuedByName)}</span>` : ''}
                        ${c.notes ? `<span><i class="fa-solid fa-comment-medical"></i> ${escHtml(c.notes)}</span>` : ''}
                    </div>
                </div>
                <div class="cert-actions">
                    ${c.viewUrl
                        ? `<a href="${escAttr(c.viewUrl)}" target="_blank" rel="noopener"
                              class="btn-cert-action view" title="View ${escAttr(c.fileName || 'file')}">
                               <i class="fa-solid fa-eye"></i> View
                           </a>`
                        : ''}
                    ${c.downloadUrl
                        ? `<a href="${escAttr(c.downloadUrl)}" target="_blank" rel="noopener"
                              class="btn-cert-action download" title="Download ${escAttr(c.fileName || 'file')}">
                               <i class="fa-solid fa-download"></i> Download
                           </a>`
                        : ''}
                </div>
            </div>`;
        }).join('');
    }

    /* ══════════════════════════════════════════════════════════
       7.  SKELETON LOADER
    ══════════════════════════════════════════════════════════ */

    // Keep skeleton rendering limited to content areas.
    // Do NOT overwrite #tabInfo HTML (it contains the edit form and can interfere with event wiring).
    function showModalSkeleton() {
        setEl('modalPatientName',    'Loading…');
        setEl('modalPatientID',      '—');
        setEl('modalPatientType',    '—');
        setEl('modalPatientSex',     '—');
        setEl('modalPatientProgram', '—');

        const avatarEl = document.getElementById('modalAvatarIcon');
        if (avatarEl) avatarEl.innerHTML = '<i class="fa-solid fa-user-nurse"></i>';

        ['tabHistoryCount', 'tabMedicalProfileCount', 'tabFamilyHistoryCount', 'tabEmergencyCount', 'tabCertsCount'].forEach(id => setEl(id, ''));

        ['infoSchoolID','infoFullName','infoSex','infoBirthday','infoEmail','infoContact',
         'infoAge','infoNationality','infoReligion','infoPatientStatus','infoAddress',
         'infoGuardianName','infoRelationship','infoMobileNo','infoTelephone','infoEmergencyAddress',
         'infoPersonTypes','infoProgram','infoDepartment','infoSection','infoStatus','infoAcadYear',
         'summaryVisits','summaryEmergencies','summaryCerts','summaryLastVisit'
        ].forEach(id => setEl(id, '—'));

        const diseasesEl = document.getElementById('infoDiseases');
        if (diseasesEl) diseasesEl.innerHTML = '<span class="disease-tag empty">Loading…</span>';

        const skeletonLine = (w) => `<div class="skeleton" style="height:14px;width:${w}%;border-radius:5px;margin-bottom:10px;"></div>`;
        const skel = `<div style="padding:20px 0;">${[80,60,90,50,70].map(skeletonLine).join('')}</div>`;
        ['clinicTimeline', 'medicalProfileList', 'recordsFamilyRows', 'emergencyList', 'certList'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = skel;
        });
    }

    /* ══════════════════════════════════════════════════════════
       8.  HELPERS
    ══════════════════════════════════════════════════════════ */

    function detailField(label, val) {
        return `<div class="tl-field">
            <span class="tl-label">${escHtml(label)}</span>
            <span class="tl-val">${escHtml(String(val ?? '—'))}</span>
        </div>`;
    }

    function typeBadge(type) {
        const map   = { Student: 'student', Faculty: 'faculty', Staff: 'staff' };
        const icons = { Student: 'fa-graduation-cap', Faculty: 'fa-chalkboard-teacher', Staff: 'fa-id-badge' };
        const cls   = map[type]   || 'staff';
        const icon  = icons[type] || 'fa-user';
        return `<span class="type-badge ${cls}"><i class="fa-solid ${icon}"></i>${escHtml(type || 'Staff')}</span>`;
    }

    function resolveTimelineType(service) {
        if (!service) return 'general';
        const s = service.toLowerCase();
        if (s.includes('dental'))                              return 'dental';
        if (s.includes('emergency') || s.includes('first aid')) return 'emergency';
        if (s.includes('physical'))                            return 'physical';
        return 'general';
    }

    function typeIcon(service) {
        if (!service) return 'fa-stethoscope';
        const s = service.toLowerCase();
        if (s.includes('dental'))                              return 'fa-tooth';
        if (s.includes('first aid') || s.includes('emergency')) return 'fa-kit-medical';
        if (s.includes('physical'))                            return 'fa-clipboard-list';
        if (s.includes('certif'))                              return 'fa-file-shield';
        if (s.includes('lab'))                                 return 'fa-flask';
        if (s.includes('immun'))                               return 'fa-syringe';
        return 'fa-stethoscope';
    }

    function resolveCertCategory(label) {
        const s = String(label || '').toLowerCase();
        if (s.includes('certificate')) return 'certificate';
        if (s.includes('clearance'))   return 'clearance';
        if (s.includes('lab') || s.includes('x-ray') || s.includes('imaging') || s.includes('prescription') || s.includes('dental')) return 'other';
        return 'other';
    }

    function resolveFileIcon(fileType) {
        if (!fileType) return 'fa-file-shield';
        const t = fileType.toLowerCase();
        if (t.includes('pdf'))                                              return 'fa-file-pdf';
        if (t.includes('image') || t.includes('jpg') || t.includes('png') || t.includes('webp')) return 'fa-file-image';
        if (t.includes('word') || t.includes('doc'))                        return 'fa-file-word';
        if (t.includes('excel') || t.includes('sheet') || t.includes('csv')) return 'fa-file-excel';
        return 'fa-file-shield';
    }

    function setTableLoading(loading) {
        const tbody = document.getElementById('recordsTbody');
        if (!tbody || !loading) return;
        tbody.innerHTML = Array.from({ length: 8 }, () =>
            `<tr>${Array.from({ length: 8 }, (_, i) =>
                `<td><div class="skeleton" style="height:14px;width:${[60,80,50,70,50,40,60,40][i]}%;border-radius:4px;"></div></td>`
            ).join('')}</tr>`
        ).join('');
    }

    function showToast(message, type = 'success') {
        const toast = document.getElementById('recordsToast');
        if (!toast) return;
        toast.textContent = message || '';
        toast.className = 'records-toast ' + (type === 'error' ? 'error-toast' : 'success');
        clearTimeout(showToast._timer);
        showToast._timer = setTimeout(() => {
            toast.className = 'records-toast';
        }, 2600);
    }

    window.openTransactionDetail = openTransactionDetail;

    function openTransactionDetail(transactionID) {
        const modal = document.getElementById('transactionDetailModal');
        const body = document.getElementById('txDetailModalBody');
        const title = document.getElementById('txDetailModalTitle');
        const sub = document.getElementById('txDetailModalSub');

        if (!modal || !body) return;

        modal.classList.add('open');
        document.body.style.overflow = 'hidden';

        if (title) title.textContent = 'Loading transaction…';
        if (sub) sub.textContent = 'Read-only consultation record';
        body.innerHTML = '<div class="empty-state"><i class="fa-solid fa-spinner fa-spin"></i><p>Loading transaction details…</p></div>';

        fetch(`../../ajax/consultation/get_transaction.ajax.php?clinic_transaction_id=${encodeURIComponent(transactionID)}`)
            .then(r => r.json())
            .then(resp => {
                if (!resp.ok || !resp.transaction) throw new Error(resp.message || 'Not found');
                renderTransactionDetail(resp);
            })
            .catch(err => {
                if (title) title.textContent = 'Transaction Details';
                if (sub) sub.textContent = 'Read-only consultation record';
                body.innerHTML = `<div class="empty-state"><i class="fa-solid fa-triangle-exclamation"></i><p>Failed to load transaction.</p><span>${escHtml(err.message)}</span></div>`;
            });
    }

    function closeTransactionModal() {
        const modal = document.getElementById('transactionDetailModal');
        if (modal) modal.classList.remove('open');
        document.body.style.overflow = '';
    }

    function renderTransactionDetail(resp) {
        const body = document.getElementById('txDetailModalBody');
        const title = document.getElementById('txDetailModalTitle');
        const sub = document.getElementById('txDetailModalSub');
        if (!body || !title || !sub) return;

        const tx = resp.transaction || {};
        title.textContent = `Transaction #${tx.ClinicTransactionID || '—'}`;
        sub.textContent = `${tx.VisitDate || '—'} · ${tx.ServiceType || 'Consultation'} · ${tx.ConsultationStatus || '—'}`;

        const patient = tx.patient || {};
        const physicalExam = tx.physicalExam || {};
        const meds = resp.medicines || [];
        const attachments = resp.attachments || [];

        body.innerHTML = `
            <div class="info-block info-block--full" style="margin-bottom:14px;">
                <div class="info-section-title"><i class="fa-solid fa-lock"></i> Read-Only Transaction</div>
                <div class="info-fields">
                    ${detailField('Transaction #', `#${tx.ClinicTransactionID || '—'}`)}
                    ${detailField('Visit Date', tx.VisitDate || '—')}
                    ${detailField('Service Type', tx.ServiceType || '—')}
                    ${detailField('Status', tx.ConsultationStatus || '—')}
                    ${detailField('Patient', patient.FullName || '—')}
                </div>
                ${tx.Complaint ? `<div class="timeline-notes"><div class="tl-label">Complaint</div><div class="tl-notes-body">${escHtml(tx.Complaint)}</div></div>` : ''}
                ${tx.Notes ? `<div class="timeline-notes"><div class="tl-label">Clinical Notes</div><div class="tl-notes-body">${escHtml(tx.Notes)}</div></div>` : ''}
            </div>

            ${physicalExam && (physicalExam.examDate || physicalExam.bloodPressure || physicalExam.height || physicalExam.weight || physicalExam.pulseRate || physicalExam.remarks) ? `
                <div class="info-block info-block--full" style="margin-bottom:14px;">
                    <div class="info-section-title"><i class="fa-solid fa-clipboard-list"></i> Physical Exam</div>
                    <div class="info-fields">
                        ${physicalExam.examDate ? detailField('Exam Date', formatDate(physicalExam.examDate)) : ''}
                        ${physicalExam.height ? detailField('Height', physicalExam.height + ' cm') : ''}
                        ${physicalExam.weight ? detailField('Weight', physicalExam.weight + ' kg') : ''}
                        ${physicalExam.bloodPressure ? detailField('Blood Pressure', physicalExam.bloodPressure) : ''}
                        ${physicalExam.pulseRate ? detailField('Pulse Rate', physicalExam.pulseRate + ' bpm') : ''}
                        ${physicalExam.ears ? detailField('Ears', physicalExam.ears) : ''}
                        ${physicalExam.eyesPupil ? detailField('Eyes / Pupil', physicalExam.eyesPupil) : ''}
                        ${physicalExam.heart ? detailField('Heart', physicalExam.heart) : ''}
                        ${physicalExam.nose ? detailField('Nose', physicalExam.nose) : ''}
                        ${physicalExam.thorax ? detailField('Thorax', physicalExam.thorax) : ''}
                        ${physicalExam.abdomen ? detailField('Abdomen', physicalExam.abdomen) : ''}
                        ${physicalExam.lungs ? detailField('Lungs', physicalExam.lungs) : ''}
                        ${physicalExam.skin ? detailField('Skin', physicalExam.skin) : ''}
                        ${physicalExam.extremities ? detailField('Extremities', physicalExam.extremities) : ''}
                        ${physicalExam.deformities ? detailField('Deformities', physicalExam.deformities) : ''}
                        ${physicalExam.cardioClearance ? detailField('Cardio Clearance', physicalExam.cardioClearance) : ''}
                    </div>
                    ${physicalExam.remarks ? `<div class="timeline-notes"><div class="tl-label">Exam Remarks</div><div class="tl-notes-body">${escHtml(physicalExam.remarks)}</div></div>` : ''}
                </div>
            ` : ''}

            ${meds.length ? `
                <div class="info-block info-block--full" style="margin-bottom:14px;">
                    <div class="info-section-title"><i class="fa-solid fa-pills"></i> Medicines Dispensed</div>
                    <div class="cert-list">
                        ${meds.map(m => `
                            <div class="cert-card">
                                <div class="cert-icon image-icon"><i class="fa-solid fa-pills"></i></div>
                                <div class="cert-info">
                                    <div class="cert-type">${escHtml(m.medicineName || m.MedicineName || 'Medicine')}</div>
                                    <div class="cert-filename">${escHtml(m.genericName || m.GenericName || '')}${m.dosage || m.Dosage ? ` · ${escHtml(m.dosage || m.Dosage)}` : ''}</div>
                                    <div class="cert-meta">
                                        <span><i class="fa-solid fa-prescription-bottle-medical"></i> Qty: ${escHtml(String(m.quantityDispensed ?? m.QuantityDispensed ?? '—'))}</span>
                                        ${m.instructions || m.Instructions ? `<span><i class="fa-solid fa-comment-medical"></i> ${escHtml(m.instructions || m.Instructions)}</span>` : ''}
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            ` : ''}

            ${attachments.length ? `
                <div class="info-block info-block--full">
                    <div class="info-section-title"><i class="fa-solid fa-paperclip"></i> Attachments / PDFs</div>
                    <div class="cert-list">
                        ${attachments.map(a => {
                            const attachmentType = a.documentType || a.certificateType || a.attachmentCategory || a.fileName || 'Attachment';
                            const attachmentCategory = a.documentCategory || a.attachmentCategory || 'Other';
                            const certCat = resolveCertCategory(attachmentCategory || attachmentType);
                            return `
                            <div class="cert-card">
                                <div class="cert-icon ${certCat}">
                                    <i class="fa-solid ${resolveFileIcon(a.fileType)}"></i>
                                </div>
                                <div class="cert-info">
                                    <div class="cert-type">${escHtml(attachmentType)}</div>
                                    <div class="cert-filename">${escHtml(attachmentCategory)}${a.createdAt ? ` <span class="cert-filesize">· ${formatDate(a.createdAt)}</span>` : ''}</div>
                                    <div class="cert-meta">
                                        ${a.fileName ? `<span><i class="fa-regular fa-file"></i> ${escHtml(a.fileName)}</span>` : ''}
                                    </div>
                                </div>
                                <div class="cert-actions">
                                    ${a.viewUrl ? `<a href="${escAttr(a.viewUrl)}" target="_blank" rel="noopener" class="btn-cert-action view"><i class="fa-solid fa-eye"></i> View</a>` : ''}
                                    ${a.downloadUrl ? `<a href="${escAttr(a.downloadUrl)}" target="_blank" rel="noopener" class="btn-cert-action download"><i class="fa-solid fa-download"></i> Download</a>` : ''}
                                </div>
                            </div>`;
                        }).join('')}
                    </div>
                </div>
            ` : `<div class="no-history"><i class="fa-solid fa-file-medical"></i><p>No medical certificates issued.</p><span>No attachments or certificates have been uploaded for this transaction.</span></div>`}
        `;
    }

    /* ── Formatting ─────────────────────────────────────── */

    function formatDate(val) {
        if (!val) return '—';
        const d = new Date(val);
        if (isNaN(d)) return val;
        return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function formatTime(val) {
        if (!val) return '';
        const d = new Date(val);
        if (isNaN(d)) return '';
        return d.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' });
    }

    function formatFileSize(bytes) {
        if (!bytes || bytes <= 0) return '';
        if (bytes < 1024)         return bytes + ' B';
        if (bytes < 1048576)      return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    /* ── DOM / string utils ─────────────────────────────── */

    function setEl(id, val) {
        const el = document.getElementById(id);
        if (el) el.textContent = val ?? '';
    }

    function escHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function escAttr(str) { return escHtml(str); }

    function debounce(fn, ms) {
        let t;
        return function (...args) {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, args), ms);
        };
    }

})();
