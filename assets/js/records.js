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
 * Version  : 3.0
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

        fetch('../../ajax/records/get_records_ajax.php')
            .then(r => r.json())
            .then(data => {
                if (!data.ok) throw new Error(data.message || 'Server error');
                allRecords = data.records || [];
                updateStats(data.stats || {});
                applyFilters();
            })
            .catch(err => {
                console.warn('[Records] Falling back to demo data:', err.message);
                allRecords = getDemoRecords();
                updateStats({
                    total    : allRecords.length,
                    students : allRecords.filter(r => r.personType === 'Student').length,
                    faculty  : allRecords.filter(r => r.personType === 'Faculty').length,
                    staff    : allRecords.filter(r => r.personType === 'Staff').length,
                });
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
            <tr onclick="openRecord(${r.userID})" data-userid="${r.userID}" tabindex="0"
                onkeydown="if(event.key==='Enter')openRecord(${r.userID})">
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
                            onclick="event.stopPropagation();openRecord(${r.userID})"
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
     */
    window.openRecord = function (userID) {
        const backdrop = document.getElementById('recordModal');
        if (!backdrop) return;

        backdrop.classList.add('open');
        document.body.style.overflow = 'hidden';
        activeModal = userID;

        switchTab('tabInfo');
        showModalSkeleton();

        fetch(`../../ajax/records/get_patient_record_ajax.php?school_person_id=${userID}`)
            .then(r => r.json())
            .then(data => {
                if (!data.ok) throw new Error(data.message || 'Not found');
                populateModal(data);
            })
            .catch(err => {
                console.warn('[Records] Falling back to demo patient record:', err.message);
                populateModal(getDemoPatientRecord(userID));
            });
    };

    function closeModal() {
        document.getElementById('recordModal')?.classList.remove('open');
        document.body.style.overflow = '';
        activeModal = null;
    }

    function bindModalClose() {
        document.getElementById('modalCloseBtn')?.addEventListener('click', closeModal);

        document.getElementById('modalPrintBtn')?.addEventListener('click', function () {
            const name = document.getElementById('modalPatientName')?.textContent || 'Record';
            const id   = document.getElementById('modalPatientID')?.textContent   || '';
            window._nucarePrintTarget = { name, id };
            window.print();
        });

        // Click outside modal box
        document.getElementById('recordModal')?.addEventListener('click', function (e) {
            if (e.target === this) closeModal();
        });

        // Esc key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && activeModal !== null) closeModal();
        });

        // Tab switching
        document.querySelectorAll('.modal-tab').forEach(btn => {
            btn.addEventListener('click', function () {
                switchTab(this.dataset.tab);
            });
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
        }
        document.getElementById(tabId)?.classList.add('active');
    }

    /* ── Filter callbacks (called from inline onclick in records.php) ── */

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
        const p = data.patient || {};

        /* ── Modal header ── */
        const fullName = [p.firstName, p.middleName, p.lastName].filter(Boolean).join(' ') || '—';
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
        setEl('infoContact',     p.contactNumber || '—');
        setEl('infoPersonType',  p.personType    || '—');
        setEl('infoProgram',     p.program || p.department || '—');
        setEl('infoDepartment',  p.department    || '—');
        setEl('infoSection',     p.yearSection || p.position || '—');
        setEl('infoStatus',      p.enrollmentStatus || p.employmentStatus || 'Active');
        setEl('infoAcadYear',    p.academicYear  || '—');

        /* Known conditions */
        const diseasesEl = document.getElementById('infoDiseases');
        if (diseasesEl) {
            diseasesEl.innerHTML = (data.diseases && data.diseases.length)
                ? data.diseases.map(d =>
                    `<span class="disease-tag">${escHtml(d.diseaseName)}${d.notes
                        ? ` <small>(${escHtml(d.notes)})</small>` : ''}</span>`).join('')
                : '<span class="disease-tag empty">No known conditions on record</span>';
        }

        /* Summary strip */
        const txList = data.transactions || [];
        setEl('summaryVisits',      txList.length);
        setEl('summaryEmergencies', (data.emergencies || []).length);
        setEl('summaryCerts',       (data.certificates || []).length);
        setEl('summaryLastVisit',   txList[0]?.visitDate ? formatDate(txList[0].visitDate) : '—');

        /* Tab count badges */
        setEl('tabHistoryCount',   txList.length || '');
        setEl('tabEmergencyCount', (data.emergencies || []).length || '');
        setEl('tabCertsCount',     (data.certificates || []).length || '');

        /* ── Other tabs ── */
        renderTimeline(txList);
        renderEmergencies(data.emergencies || []);
        renderCertificates(data.certificates || []);
    }

    /* ══════════════════════════════════════════════════════════
       4.  TIMELINE (Clinic History)
    ══════════════════════════════════════════════════════════ */

    function renderTimeline(transactions) {
        const container = document.getElementById('clinicTimeline');
        const countEl   = document.getElementById('historyVisibleCount');
        if (!container) return;

        if (!transactions.length) {
            container.innerHTML = `
                <div class="no-history">
                    <i class="fa-solid fa-notes-medical"></i>
                    <p>No clinic history available</p>
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
                    ${escHtml(m.medicineName)} ×${escHtml(String(m.qty))}
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

            return `
            <div class="timeline-item" data-type="${type}">
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
                            <span class="consult-status-badge ${(tx.consultationStatus || '').toLowerCase().replace(/\s+/g, '-')}">
                                ${escHtml(tx.consultationStatus || '—')}
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

                        ${medChips
                            ? `<div class="medicines-dispensed">
                                   <div class="medicines-label"><i class="fa-solid fa-pills"></i> Medicines Dispensed</div>
                                   <div class="med-chips">${medChips}</div>
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
                    <p>No certificates available</p>
                    <span>No attachments or certificates have been uploaded for this patient.</span>
                </div>`;
            if (countEl) countEl.textContent = '0 files';
            return;
        }

        if (countEl) countEl.textContent = `${certs.length} file${certs.length !== 1 ? 's' : ''}`;

        container.innerHTML = certs.map(c => {
            const fileIcon = resolveFileIcon(c.fileType);
            const fileSize = c.fileSizeBytes ? formatFileSize(c.fileSizeBytes) : '';
            const cat      = resolveCertCategory(c.certificateType);

            return `
            <div class="cert-card" data-cat="${cat}">
                <div class="cert-icon ${cat}">
                    <i class="fa-solid ${fileIcon}"></i>
                </div>
                <div class="cert-info">
                    <div class="cert-type">${escHtml(c.certificateType || 'Medical Document')}</div>
                    ${c.fileName
                        ? `<div class="cert-filename">
                               ${escHtml(c.fileName)}${fileSize ? ` <span class="cert-filesize">· ${fileSize}</span>` : ''}
                           </div>`
                        : ''}
                    <div class="cert-meta">
                        <span><i class="fa-regular fa-calendar"></i> ${formatDate(c.createdAt)}</span>
                        ${c.issuedByName ? `<span><i class="fa-solid fa-user-doctor"></i> ${escHtml(c.issuedByName)}</span>` : ''}
                        ${c.remarks     ? `<span><i class="fa-solid fa-comment-medical"></i> ${escHtml(c.remarks)}</span>` : ''}
                        ${c.validUntil  ? `<span class="cert-valid"><i class="fa-regular fa-clock"></i> Valid until ${formatDate(c.validUntil)}</span>` : ''}
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

    /* Cached tabInfo static HTML — preserved across opens so DOM IDs stay in place */
    let _tabInfoStaticHTML = null;

    function showModalSkeleton() {
        /* Header */
        setEl('modalPatientName',    'Loading…');
        setEl('modalPatientID',      '—');
        setEl('modalPatientType',    '—');
        setEl('modalPatientSex',     '—');
        setEl('modalPatientProgram', '—');

        const avatarEl = document.getElementById('modalAvatarIcon');
        if (avatarEl) avatarEl.innerHTML = '<i class="fa-solid fa-user-nurse"></i>';

        /* Tab count badges */
        ['tabHistoryCount', 'tabEmergencyCount', 'tabCertsCount'].forEach(id => setEl(id, ''));

        /* Preserve tabInfo static HTML so field IDs survive */
        const tabInfoEl = document.getElementById('tabInfo');
        if (tabInfoEl && !_tabInfoStaticHTML) _tabInfoStaticHTML = tabInfoEl.innerHTML;
        if (tabInfoEl && _tabInfoStaticHTML)  tabInfoEl.innerHTML = _tabInfoStaticHTML;

        /* Reset info-field values to dash */
        ['infoSchoolID','infoFullName','infoSex','infoBirthday','infoEmail','infoContact',
         'infoPersonType','infoProgram','infoDepartment','infoSection','infoStatus','infoAcadYear',
         'summaryVisits','summaryEmergencies','summaryCerts','summaryLastVisit'
        ].forEach(id => setEl(id, '—'));
        const diseasesEl = document.getElementById('infoDiseases');
        if (diseasesEl) diseasesEl.innerHTML = '<span class="disease-tag empty">Loading…</span>';

        /* Skeleton-only the data-driven tabs */
        const skeletonLine = (w) => `<div class="skeleton" style="height:14px;width:${w}%;border-radius:5px;margin-bottom:10px;"></div>`;
        const skel = `<div style="padding:20px 0;">${[80,60,90,50,70].map(skeletonLine).join('')}</div>`;
        ['tabHistory', 'tabEmergency', 'tabCerts'].forEach(id => {
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
        if (s.includes('dental'))                        return 'dental';
        if (s.includes('emergency') || s.includes('first aid')) return 'emergency';
        if (s.includes('physical'))                      return 'physical';
        return 'general';
    }

    function typeIcon(service) {
        if (!service) return 'fa-stethoscope';
        const s = service.toLowerCase();
        if (s.includes('dental'))   return 'fa-tooth';
        if (s.includes('first aid') || s.includes('emergency')) return 'fa-kit-medical';
        if (s.includes('physical')) return 'fa-clipboard-list';
        if (s.includes('certif'))   return 'fa-file-shield';
        if (s.includes('lab'))      return 'fa-flask';
        if (s.includes('immun'))    return 'fa-syringe';
        return 'fa-stethoscope';
    }

    function resolveCertCategory(label) {
        const s = String(label || '').toLowerCase();
        if (s.includes('certificate')) return 'certificate';
        if (s.includes('clearance'))   return 'clearance';
        return 'other';
    }

    function resolveFileIcon(fileType) {
        if (!fileType) return 'fa-file-shield';
        const t = fileType.toLowerCase();
        if (t.includes('pdf'))                             return 'fa-file-pdf';
        if (t.includes('image') || t.includes('jpg') || t.includes('png') || t.includes('webp')) return 'fa-file-image';
        if (t.includes('word') || t.includes('doc'))       return 'fa-file-word';
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

    /* ══════════════════════════════════════════════════════════
       9.  DEMO / FALLBACK DATA
    ══════════════════════════════════════════════════════════ */

    function getDemoRecords() {
        return [
            { userID: 4,  schoolID: '2024-001', fullName: 'Juan A. Santos',       email: 'juan@nucare.edu',   personType: 'Student', program: 'BS Nursing',     yearSection: '3-A', status: 'Active',   visitCount: 5, lastVisit: '2026-05-18' },
            { userID: 6,  schoolID: '2024-002', fullName: 'Maria B. Reyes',        email: 'maria@nucare.edu',  personType: 'Student', program: 'BS Nursing',     yearSection: '2-B', status: 'Active',   visitCount: 3, lastVisit: '2026-05-15' },
            { userID: 7,  schoolID: '2024-003', fullName: 'Liam C. Cruz',          email: 'liam@nucare.edu',   personType: 'Student', program: 'BS Nursing',     yearSection: '1-C', status: 'Active',   visitCount: 1, lastVisit: '2026-04-30' },
            { userID: 9,  schoolID: '2024-004', fullName: 'Sophia D. Dela Cruz',   email: 'sophia@nucare.edu', personType: 'Student', program: 'BS Nursing',     yearSection: '4-A', status: 'Inactive', visitCount: 0, lastVisit: null },
            { userID: 10, schoolID: '2024-005', fullName: 'Noah E. Garcia',        email: 'noah@nucare.edu',   personType: 'Student', program: 'BS Medicine',    yearSection: '1-D', status: 'Active',   visitCount: 7, lastVisit: '2026-05-20' },
            { userID: 11, schoolID: 'FAC-001',  fullName: 'Dr. Ana F. Mendoza',    email: 'ana@nucare.edu',    personType: 'Faculty', department: 'Nursing Dept', yearSection: null,  status: 'Active',   visitCount: 2, lastVisit: '2026-05-10' },
            { userID: 12, schoolID: 'STA-001',  fullName: 'Carlos G. Bautista',    email: 'carlos@nucare.edu', personType: 'Staff',   department: 'Registrar',   yearSection: null,  status: 'Active',   visitCount: 0, lastVisit: null },
        ];
    }

    function getDemoPatientRecord(userID) {
        const base = getDemoRecords().find(r => r.userID === userID) || getDemoRecords()[0];
        return {
            ok: true,
            patient: {
                schoolID:         base.schoolID,
                firstName:        base.fullName.split(' ')[0],
                middleName:       base.fullName.split(' ')[1] || '',
                lastName:         base.fullName.split(' ').slice(2).join(' '),
                sex:              ['Male', 'Female'][userID % 2],
                birthday:         'March 14, 2002',
                email:            base.email,
                contactNumber:    '09123456789',
                personType:       base.personType,
                program:          base.program || null,
                department:       base.department || 'College of Health Sciences',
                yearSection:      base.yearSection || null,
                enrollmentStatus: base.status === 'Active' ? 'Enrolled' : 'Not Enrolled',
                academicYear:     '2025–2026',
            },
            diseases: [
                { diseaseName: 'Asthma',        notes: 'mild, controlled' },
                { diseaseName: 'Hypertension',  notes: 'under medication' },
            ],
            transactions: [
                {
                    visitDate:          '2026-05-21',
                    createdAt:          '2026-05-21T08:30:00',
                    serviceType:        'General Consultation',
                    consultationStatus: 'Completed',
                    complaint:          'Fever and headache',
                    bloodPressure:      '118/76',
                    temperature:        '37.8',
                    pulseRate:          '82',
                    weight:             '58',
                    height:             '163',
                    notes:              'Prescribed paracetamol. Advised rest and hydration. Follow-up in 3 days if no improvement.',
                    medProfName:        'Dr. Rafael Bautista',
                    medicines: [
                        { medicineName: 'Paracetamol 500mg', qty: 10 },
                        { medicineName: 'Ascorbic Acid 500mg', qty: 7 },
                    ],
                },
                {
                    visitDate:          '2026-04-10',
                    createdAt:          '2026-04-10T10:15:00',
                    serviceType:        'Dental',
                    consultationStatus: 'Completed',
                    complaint:          'Toothache upper right molar',
                    notes:              'Prophylaxis performed. Advised possible extraction on follow-up.',
                    medProfName:        'Dr. Lena Santos',
                    medicines:          [],
                },
                {
                    visitDate:          '2026-03-05',
                    createdAt:          '2026-03-05T09:00:00',
                    serviceType:        'Physical Examination',
                    consultationStatus: 'Completed',
                    complaint:          'Annual PE for enrollment clearance',
                    bloodPressure:      '120/80',
                    temperature:        '36.6',
                    pulseRate:          '76',
                    weight:             '57',
                    height:             '163',
                    notes:              'Fit for enrollment. No significant findings.',
                    medProfName:        'Dr. Rafael Bautista',
                    medicines:          [],
                },
            ],
            emergencies: [],
            certificates: [
                {
                    attachmentID:    1,
                    certificateType: 'Medical Certificate',
                    createdAt:       '2026-02-14',
                    issuedByName:    'Dr. Rafael Bautista',
                    remarks:         'Fit to attend classes',
                    validUntil:      '2026-08-14',
                    fileName:        'medcert_2026.pdf',
                    fileType:        'application/pdf',
                    fileSizeBytes:   245760,    
                    viewUrl:         '#',
                    downloadUrl:     '#',
                },
                {
                    attachmentID:    2,
                    certificateType: 'Clearance — PE',
                    createdAt:       '2026-03-05',
                    issuedByName:    'Dr. Rafael Bautista',
                    remarks:         'Annual PE clearance for enrollment',
                    validUntil:      null,
                    fileName:        'pe_clearance.pdf',
                    fileType:        'application/pdf',
                    fileSizeBytes:   180224,
                    viewUrl:         '#',
                    downloadUrl:     '#',
                },
            ],
        };
    }

})();