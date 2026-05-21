(function () {
    'use strict';

    /* ── State ── */
    let allRecords   = [];
    let filtered     = [];
    let currentPage  = 1;
    const PAGE_SIZE  = 15;
    let activeModal  = null;

    /* ── Boot ── */
    document.addEventListener('DOMContentLoaded', function () {
        loadRecords();
        bindFilters();
        bindModalClose();
    });

    /* ── Fetch records list ── */
    function loadRecords() {
        setTableLoading(true);

        fetch('../../ajax/records/get_records.ajax.php')
            .then(r => r.json())
            .then(data => {
                allRecords = data.records || [];
                updateStats(data.stats || {});
                applyFilters();
            })
            .catch(() => {
                /* Dev demo fallback */
                allRecords = getDemoRecords();
                updateStats({
                    total:    allRecords.length,
                    students: allRecords.filter(r => r.personType === 'Student').length,
                    faculty:  allRecords.filter(r => r.personType === 'Faculty').length,
                    staff:    allRecords.filter(r => r.personType === 'Staff').length,
                });
                applyFilters();
            });
    }

    /* ── Stats banner ── */
    function updateStats(stats) {
        setEl('statTotal',    stats.total    ?? 0);
        setEl('statStudents', stats.students ?? 0);
        setEl('statFaculty',  stats.faculty  ?? 0);
        setEl('statStaff',    stats.staff    ?? 0);
    }

    /* ── Filter / Search ── */
    function bindFilters() {
        document.getElementById('recordSearchInput')?.addEventListener('input',  debounce(applyFilters, 220));
        document.getElementById('filterType')?.addEventListener('change',  applyFilters);
        document.getElementById('filterStatus')?.addEventListener('change', applyFilters);
        document.getElementById('filterSort')?.addEventListener('change',  applyFilters);
    }

    function applyFilters() {
        const q      = (document.getElementById('recordSearchInput')?.value || '').toLowerCase().trim();
        const type   = document.getElementById('filterType')?.value   || '';
        const status = document.getElementById('filterStatus')?.value || '';
        const sort   = document.getElementById('filterSort')?.value   || 'name_asc';

        filtered = allRecords.filter(r => {
            const matchQ = !q ||
                r.fullName.toLowerCase().includes(q) ||
                r.schoolID.toLowerCase().includes(q) ||
                (r.program || '').toLowerCase().includes(q);
            const matchType   = !type   || r.personType === type;
            const matchStatus = !status || r.status     === status;
            return matchQ && matchType && matchStatus;
        });

        /* Sort */
        filtered.sort((a, b) => {
            if (sort === 'name_asc')    return a.fullName.localeCompare(b.fullName);
            if (sort === 'name_desc')   return b.fullName.localeCompare(a.fullName);
            if (sort === 'visits_desc') return (b.visitCount || 0) - (a.visitCount || 0);
            if (sort === 'recent')      return new Date(b.lastVisit || 0) - new Date(a.lastVisit || 0);
            return 0;
        });

        currentPage = 1;
        renderTable();
        renderPagination();
        setTableLoading(false);
    }

    /* ── Table Render ── */
    function renderTable() {
        const tbody = document.getElementById('recordsTbody');
        if (!tbody) return;

        const start = (currentPage - 1) * PAGE_SIZE;
        const page  = filtered.slice(start, start + PAGE_SIZE);

        if (page.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7">
                <div class="empty-state">
                    <i class="fa-solid fa-folder-open"></i>
                    <p>No records found</p>
                    <span>Try adjusting your search or filters</span>
                </div>
            </td></tr>`;
            return;
        }

        tbody.innerHTML = page.map(r => `
            <tr onclick="openRecord(${r.userID})" data-userid="${r.userID}">
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
                    <button class="btn-view-record" onclick="event.stopPropagation();openRecord(${r.userID})">
                        <i class="fa-solid fa-eye"></i> View
                    </button>
                </td>
            </tr>`).join('');
    }

    /* ── Pagination ── */
    function renderPagination() {
        const total  = filtered.length;
        const pages  = Math.max(1, Math.ceil(total / PAGE_SIZE));
        const start  = total === 0 ? 0 : (currentPage - 1) * PAGE_SIZE + 1;
        const end    = Math.min(currentPage * PAGE_SIZE, total);

        setEl('paginationInfo', `Showing ${start}–${end} of ${total} records`);

        const container = document.getElementById('paginationBtns');
        if (!container) return;

        let html = `<button class="page-btn" onclick="Records.goPage(${currentPage-1})" ${currentPage<=1?'disabled':''}>
                        <i class="fa-solid fa-chevron-left" style="font-size:.65rem;"></i>
                    </button>`;

        for (let i = 1; i <= pages; i++) {
            if (pages > 7 && i > 2 && i < pages - 1 && Math.abs(i - currentPage) > 1) {
                if (i === 3 || i === pages - 2) html += `<button class="page-btn" disabled>…</button>`;
                continue;
            }
            html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="Records.goPage(${i})">${i}</button>`;
        }

        html += `<button class="page-btn" onclick="Records.goPage(${currentPage+1})" ${currentPage>=pages?'disabled':''}>
                     <i class="fa-solid fa-chevron-right" style="font-size:.65rem;"></i>
                 </button>`;

        container.innerHTML = html;
    }

    window.Records = {
        goPage: function (p) {
            const pages = Math.ceil(filtered.length / PAGE_SIZE);
            if (p < 1 || p > pages) return;
            currentPage = p;
            renderTable();
            renderPagination();
            document.getElementById('recordsTbody')?.closest('.records-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };

    /* ─────────────────────────────────────────────
       MODAL — open individual patient record
    ───────────────────────────────────────────── */
    window.openRecord = function (userID) {
        const backdrop = document.getElementById('recordModal');
        if (!backdrop) return;

        backdrop.classList.add('open');
        document.body.style.overflow = 'hidden';
        activeModal = userID;

        /* Reset to first tab */
        switchTab('tabInfo');

        /* Show skeleton while loading */
        showModalSkeleton();

        fetch(`../../ajax/records/get_patient_record.ajax.php?user_id=${userID}`)
            .then(r => r.json())
            .then(data => populateModal(data))
            .catch(() => populateModal(getDemoPatientRecord(userID)));
    };

    function closeModal() {
        document.getElementById('recordModal')?.classList.remove('open');
        document.body.style.overflow = '';
        activeModal = null;
    }

    function bindModalClose() {
        document.getElementById('modalCloseBtn')?.addEventListener('click', closeModal);
        document.getElementById('recordModal')?.addEventListener('click', function (e) {
            if (e.target === this) closeModal();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && activeModal !== null) closeModal();
        });

        /* Tab switching */
        document.querySelectorAll('.modal-tab').forEach(btn => {
            btn.addEventListener('click', function () {
                switchTab(this.dataset.tab);
            });
        });
    }

    function switchTab(tabId) {
        document.querySelectorAll('.modal-tab').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.querySelector(`.modal-tab[data-tab="${tabId}"]`)?.classList.add('active');
        document.getElementById(tabId)?.classList.add('active');
    }

    /* ── History filter (inside modal) ── */
    window.filterHistory = function (type, btn) {
        document.querySelectorAll('.history-filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const items = document.querySelectorAll('.timeline-item');
        items.forEach(item => {
            item.style.display = (type === 'all' || item.dataset.type === type) ? '' : 'none';
        });
    };

    /* ── Populate modal ── */
    function populateModal(data) {
        const p = data.patient;

        /* Header */
        setEl('modalPatientName',    [p.firstName, p.middleName, p.lastName].filter(Boolean).join(' '));
        setEl('modalPatientID',      p.schoolID);
        setEl('modalPatientType',    p.personType);
        setEl('modalPatientSex',     p.sex);
        setEl('modalPatientProgram', p.program || p.department || '—');

        /* Tab: Patient Info */
        setEl('infoSchoolID',    p.schoolID);
        setEl('infoFullName',    [p.firstName, p.middleName, p.lastName].filter(Boolean).join(' '));
        setEl('infoSex',         p.sex);
        setEl('infoBirthday',    p.birthday  || '—');
        setEl('infoEmail',       p.email     || '—');
        setEl('infoPersonType',  p.personType);
        setEl('infoProgram',     p.program   || p.department || '—');
        setEl('infoSection',     p.yearSection || p.position || '—');
        setEl('infoStatus',      p.enrollmentStatus || p.employmentStatus || 'Active');
        setEl('infoAcadYear',    p.academicYear || '—');

        /* Diseases */
        const diseasesEl = document.getElementById('infoDiseases');
        if (diseasesEl) {
            if (data.diseases && data.diseases.length > 0) {
                diseasesEl.innerHTML = data.diseases.map(d =>
                    `<span class="disease-tag">${escHtml(d.diseaseName)}${d.notes ? ` <small>(${escHtml(d.notes)})</small>` : ''}</span>`
                ).join('');
            } else {
                diseasesEl.innerHTML = '<span class="disease-tag empty">No known conditions on record</span>';
            }
        }

        /* Tab: Clinic History */
        renderTimeline(data.transactions || []);

        /* Tab: Emergencies */
        renderEmergencies(data.emergencies || []);

        /* Tab: Certificates */
        renderCertificates(data.certificates || []);

        /* Update visit count badge */
        const vc = document.getElementById('tabHistoryCount');
        if (vc) vc.textContent = (data.transactions || []).length;
    }

    function renderTimeline(transactions) {
        const container = document.getElementById('clinicTimeline');
        if (!container) return;

        if (!transactions.length) {
            container.innerHTML = `<div class="no-history">
                <i class="fa-solid fa-notes-medical"></i>
                <p>No clinic visits on record yet.</p>
            </div>`;
            return;
        }

        container.innerHTML = transactions.map(tx => {
            const type = resolveTimelineType(tx.serviceType);
            const medicines = (tx.medicines || []).map(m =>
                `<span class="med-chip"><i class="fa-solid fa-pills"></i>${escHtml(m.medicineName)} ×${m.qty}</span>`
            ).join('');

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
                                ${tx.complaint ? `<div style="font-size:.78rem;color:var(--gray-500);margin-top:3px;">${escHtml(tx.complaint)}</div>` : ''}
                            </div>
                            <span class="consult-status-badge ${(tx.consultationStatus||'').toLowerCase()}">${escHtml(tx.consultationStatus||'—')}</span>
                        </div>

                        <div class="timeline-detail-row">
                            ${tx.bloodPressure ? detailField('Blood Pressure', tx.bloodPressure) : ''}
                            ${tx.temperature   ? detailField('Temperature',    tx.temperature + ' °C') : ''}
                            ${tx.pulseRate     ? detailField('Pulse Rate',     tx.pulseRate) : ''}
                            ${tx.weight        ? detailField('Weight',         tx.weight + ' kg') : ''}
                            ${tx.medProfName   ? detailField('Attended By',    tx.medProfName) : ''}
                        </div>

                        ${tx.notes ? `<div style="margin-top:10px;padding-top:10px;border-top:1px solid var(--gray-100);">
                            <div class="tl-label" style="margin-bottom:4px;">Clinical Notes</div>
                            <div style="font-size:.8rem;color:var(--gray-700);line-height:1.5;">${escHtml(tx.notes)}</div>
                        </div>` : ''}

                        ${medicines ? `<div class="medicines-dispensed">
                            <div class="medicines-label"><i class="fa-solid fa-pills"></i> Medicines Dispensed</div>
                            <div class="med-chips">${medicines}</div>
                        </div>` : ''}
                    </div>
                </div>
            </div>`;
        }).join('');
    }

    function renderEmergencies(emergencies) {
        const container = document.getElementById('emergencyList');
        if (!container) return;

        if (!emergencies.length) {
            container.innerHTML = `<div class="no-history">
                <i class="fa-solid fa-kit-medical"></i>
                <p>No emergency records found.</p>
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
                    <span class="emergency-date">${formatDate(e.incidentDate)} ${e.incidentTime || ''}</span>
                </div>
                <div class="emergency-fields">
                    ${e.incidentLocation ? detailField('Location',    e.incidentLocation)  : ''}
                    ${e.bp               ? detailField('Blood Pres.', e.bp)                : ''}
                    ${e.hr               ? detailField('Heart Rate',  e.hr)                : ''}
                    ${e.rr               ? detailField('Resp. Rate',  e.rr)                : ''}
                    ${e.temperature      ? detailField('Temperature', e.temperature+'°C')  : ''}
                    ${e.treatmentGiven   ? detailField('Treatment',   e.treatmentGiven)    : ''}
                    ${e.ambulanceNo      ? detailField('Ambulance #', e.ambulanceNo)       : ''}
                </div>
            </div>`).join('');
    }

    function renderCertificates(certs) {
        const container = document.getElementById('certList');
        if (!container) return;

        if (!certs.length) {
            container.innerHTML = `<div class="no-history">
                <i class="fa-solid fa-file-medical"></i>
                <p>No medical certificates issued.</p>
            </div>`;
            return;
        }

        container.innerHTML = certs.map(c => `
            <div class="cert-card">
                <div class="cert-icon"><i class="fa-solid fa-file-shield"></i></div>
                <div class="cert-info">
                    <div class="cert-type">${escHtml(c.certificateType || 'Medical Certificate')}</div>
                    <div class="cert-meta">
                        <span><i class="fa-regular fa-calendar"></i> Issued: ${formatDate(c.createdAt)}</span>
                        ${c.issuedByName ? `<span><i class="fa-solid fa-user-doctor"></i> ${escHtml(c.issuedByName)}</span>` : ''}
                        ${c.remarks ? `<span>${escHtml(c.remarks)}</span>` : ''}
                    </div>
                </div>
                ${c.validUntil ? `<span class="cert-valid">Valid until ${formatDate(c.validUntil)}</span>` : ''}
            </div>`).join('');
    }

    /* ── Skeleton loader ── */
    function showModalSkeleton() {
        setEl('modalPatientName', '');
        setEl('modalPatientID', '');

        ['tabInfo','tabHistory','tabEmergency','tabCerts'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = `<div style="display:flex;flex-direction:column;gap:10px;padding:20px 0;">
                ${[80,60,90,50,75].map(w => `<div class="skeleton" style="height:18px;width:${w}%;border-radius:6px;"></div>`).join('')}
            </div>`;
        });
    }

    /* ── Helpers ── */
    function detailField(label, val) {
        return `<div class="tl-field">
            <span class="tl-label">${label}</span>
            <span class="tl-val">${escHtml(String(val))}</span>
        </div>`;
    }

    function typeBadge(type) {
        const map = { Student: 'student', Faculty: 'faculty', Staff: 'staff' };
        const cls = map[type] || 'staff';
        const icons = { Student: 'fa-graduation-cap', Faculty: 'fa-chalkboard-teacher', Staff: 'fa-id-badge' };
        return `<span class="type-badge ${cls}"><i class="fa-solid ${icons[type]||'fa-user'}"></i>${type}</span>`;
    }

    function resolveTimelineType(service) {
        if (!service) return 'general';
        const s = service.toLowerCase();
        if (s.includes('dental'))    return 'dental';
        if (s.includes('emergency')) return 'emergency';
        if (s.includes('physical'))  return 'physical';
        return 'general';
    }

    function typeIcon(service) {
        if (!service) return 'fa-stethoscope';
        const s = service.toLowerCase();
        if (s.includes('dental'))    return 'fa-tooth';
        if (s.includes('first aid') || s.includes('emergency')) return 'fa-kit-medical';
        if (s.includes('physical'))  return 'fa-clipboard-list';
        if (s.includes('certif'))    return 'fa-file-shield';
        if (s.includes('lab'))       return 'fa-flask';
        if (s.includes('immun'))     return 'fa-syringe';
        return 'fa-stethoscope';
    }

    function setTableLoading(loading) {
        const tbody = document.getElementById('recordsTbody');
        if (!tbody || !loading) return;
        tbody.innerHTML = Array.from({length: 8}, () => `<tr>${Array.from({length: 8}, (_, i) =>
            `<td><div class="skeleton" style="height:14px;width:${[60,80,50,70,50,40,60,40][i]}%;border-radius:4px;"></div></td>`
        ).join('')}</tr>`).join('');
    }

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

    function setEl(id, val) {
        const el = document.getElementById(id);
        if (el) el.textContent = val ?? '';
    }

    function escHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function debounce(fn, ms) {
        let t;
        return function (...args) { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), ms); };
    }

    /* ─────────────────────────────────────────────
       DEMO DATA (fallback when API unavailable)
    ───────────────────────────────────────────── */
    function getDemoRecords() {
        return [
            { userID:4,  schoolID:'SCH-1001', fullName:'Juan A. Santos',       email:'juan.santos@nucare.edu',       personType:'Student', program:'BS Nursing',        yearSection:'3-A',  status:'Active',   visitCount:5,  lastVisit:'2026-05-18' },
            { userID:6,  schoolID:'SCH-1002', fullName:'Maria B. Reyes',        email:'maria.reyes@nucare.edu',        personType:'Student', program:'BS Nursing',        yearSection:'2-B',  status:'Active',   visitCount:3,  lastVisit:'2026-05-15' },
            { userID:7,  schoolID:'SCH-1003', fullName:'Liam C. Cruz',          email:'liam.cruz@nucare.edu',          personType:'Student', program:'BS Nursing',        yearSection:'1-C',  status:'Active',   visitCount:1,  lastVisit:'2026-04-30' },
            { userID:9,  schoolID:'SCH-1004', fullName:'Sophia D. Dela Cruz',   email:'sophia.delacruz@nucare.edu',   personType:'Student', program:'BS Nursing',        yearSection:'4-A',  status:'Inactive', visitCount:0,  lastVisit: null },
            { userID:10, schoolID:'SCH-1005', fullName:'Noah E. Garcia',        email:'noah.garcia@nucare.edu',        personType:'Student', program:'BS Medicine',       yearSection:'1-D',  status:'Active',   visitCount:7,  lastVisit:'2026-05-20' },
            { userID:11, schoolID:'SCH-1006', fullName:'Emma F. Mendoza',       email:'emma.mendoza@nucare.edu',       personType:'Student', program:'BS Medicine',       yearSection:'2-A',  status:'Active',   visitCount:2,  lastVisit:'2026-05-10' },
           
        ];
    }

    function getDemoPatientRecord(userID) {
        const base = getDemoRecords().find(r => r.userID === userID) || getDemoRecords()[0];
        return {
            patient: {
                schoolID:    base.schoolID,
                firstName:   base.fullName.split(' ')[0],
                middleName:  base.fullName.split(' ')[1] || '',
                lastName:    base.fullName.split(' ').slice(2).join(' '),
                sex:         ['Male','Female'][userID % 2],
                birthday:    'March 14, 2002',
                email:       base.email,
                personType:  base.personType,
                program:     base.program || base.department,
                yearSection: base.yearSection || base.position,
                enrollmentStatus: base.status === 'Active' ? 'Enrolled' : 'Not Enrolled',
                academicYear: '2025–2026',
            },
            diseases: [
                { diseaseName: 'Asthma', notes: 'mild, controlled' },
            ],
            transactions: [
                {
                    visitDate: '2026-05-21', createdAt: '2026-05-21T08:30:00',
                    serviceType: 'General Consultation', consultationStatus: 'Completed',
                    complaint: 'Fever and headache',
                    bloodPressure: '118/76', temperature: '37.8', pulseRate: '82', weight: '58',
                    notes: 'Prescribed paracetamol. Advised rest and hydration. Follow-up in 3 days if no improvement.',
                    medProfName: 'Dr. Rafael Bautista',
                    medicines: [
                        { medicineName: 'Paracetamol 500mg', qty: 10 },
                        { medicineName: 'Ascorbic Acid 500mg', qty: 7 },
                    ]
                },
                {
                    visitDate: '2026-04-10', createdAt: '2026-04-10T10:15:00',
                    serviceType: 'Dental', consultationStatus: 'Completed',
                    complaint: 'Toothache upper right molar',
                    bloodPressure: null, temperature: null, pulseRate: null, weight: null,
                    notes: 'Cleaning performed. Advised to return for possible extraction.',
                    medProfName: 'Demo Dentist',
                    medicines: []
                },
                {
                    visitDate: '2026-02-14', createdAt: '2026-02-14T13:00:00',
                    serviceType: 'Physical Examination', consultationStatus: 'Completed',
                    complaint: 'Annual physical exam',
                    bloodPressure: '120/80', temperature: '36.6', pulseRate: '74', weight: '57.5',
                    notes: 'Generally fit. Cardiac clearance granted.',
                    medProfName: 'Dr. Rafael Bautista',
                    medicines: []
                },
                {
                    visitDate: '2026-01-20', createdAt: '2026-01-20T09:00:00',
                    serviceType: 'General Consultation', consultationStatus: 'Completed',
                    complaint: 'Sprained ankle',
                    bloodPressure: '116/78', temperature: '36.4', pulseRate: '78', weight: '58',
                    notes: 'Cold compress recommended. Bandage applied.',
                    medProfName: 'Nurse Helen Reyes',
                    medicines: [
                        { medicineName: 'Ibuprofen 400mg', qty: 6 }
                    ]
                },
            ],
            emergencies: [],
            certificates: [
                {
                    certificateType: 'Medical Certificate',
                    createdAt: '2026-02-14',
                    issuedByName: 'Dr. Rafael Bautista',
                    remarks: 'Fit to attend classes',
                    validUntil: '2026-08-14',
                }
            ]
        };
    }

})();