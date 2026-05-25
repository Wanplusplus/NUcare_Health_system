(function () {
    /* ══════════════════════════════════════════════════════
       NUCARE — Consultation JS (v3)
       1. Physical Examination workflow
       2. Medicine Dispensing (3NF, inventory-aware)
       3. Transactional saving (DB transaction wrapper)
       4. Consultation History (EMR read-only viewer)
       5. Service Type dynamic UI (5 service types)
    ══════════════════════════════════════════════════════ */

    /* ── DOM refs ─────────────────────────────────────── */
    const qInput   = document.getElementById('consultSearchInput');
    const feedback = document.getElementById('searchFeedback');
    const acList   = document.getElementById('searchAutocomplete');

    let debounceTimer = null;

    /* ── helpers ──────────────────────────────────────── */
    function setFeedback(html, cls) {
        if (!feedback) return;
        feedback.className = 'search-feedback ' + (cls || '');
        feedback.innerHTML = html;
    }

    function closeAutocomplete() {
        if (acList) { acList.innerHTML = ''; acList.classList.remove('open'); }
    }

    /* ── Populate patient banner ──────────────────────── */
    function populateBanner(p) {
        const fullName = p.FullName
            || [p.FirstName, p.MiddleName ? p.MiddleName[0] + '.' : '', p.LastName]
                .filter(Boolean).join(' ')
            || '—';

        const set = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val || '—';
        };

        set('cpcName',  fullName);
        set('cpcID',    p.SchoolID);
        set('cpcSex',   p.Sex);
        set('cpcType',  p.PersonType);
        set('cpcTime',  p.LoadedAt);
        set('cpcAge',   p.Age != null ? p.Age : null);

        const avatarEl = document.getElementById('patientAvatarInitials');
        if (avatarEl) {
            const parts    = fullName.trim().split(/\s+/);
            const initials = ((parts[0]?.[0] ?? '') + (parts[parts.length - 1]?.[0] ?? '')).toUpperCase();
            avatarEl.innerHTML = `<span class="avatar-text">${initials}</span>`;
        }

        const banner = document.getElementById('consultPatientCard');
        if (banner) banner.classList.add('visible');
    }

    /* ── Load patient then start transaction ──────────── */
    function loadPatient(p) {
        populateBanner(p);

        const spidInput = document.getElementById('consultPatientID');
        if (spidInput) spidInput.value = p.SchoolPersonID;

        unlockForm();
        loadHistory(p.SchoolPersonID);
        startTransaction(p.SchoolPersonID);
    }

    /* ── Main search ──────────────────────────────────── */
    window.searchPatient = function () {
        const query = qInput ? qInput.value.trim() : '';

        if (!query) {
            setFeedback('<i class="fa-solid fa-circle-exclamation"></i> Please enter a School ID or name.', 'not-found');
            return;
        }

        setFeedback('<i class="fa-solid fa-spinner fa-spin"></i> Searching…', '');
        closeAutocomplete();

        fetch('../../ajax/consultation/patient_search.ajax.php?q=' + encodeURIComponent(query))
            .then(r => r.text())
            .then(raw => {
                let data;
                try { data = JSON.parse(raw); } catch (e) {
                    console.error('Patient search raw response:', raw);
                    setFeedback('<i class="fa-solid fa-triangle-exclamation"></i> Server returned invalid response.', 'not-found');
                    return;
                }

                if (data.ok && data.multiple && Array.isArray(data.results) && data.results.length > 0) {
                    renderAutocomplete(data.results);
                    setFeedback('<i class="fa-solid fa-list"></i> Multiple patients found — select one below.', '');
                    return;
                }

                if (!data || !data.ok || !data.found) {
                    setFeedback('<i class="fa-solid fa-circle-exclamation"></i> No patient found. Check the ID and try again.', 'not-found');
                    return;
                }

                const p = data.patient || data;
                setFeedback('<i class="fa-solid fa-circle-check"></i> Patient found.', 'found');
                loadPatient(p);
            })
            .catch(err => {
                console.error('Patient search fetch error:', err);
                setFeedback('<i class="fa-solid fa-triangle-exclamation"></i> Server error while searching.', 'not-found');
            });
    };

    /* ── Autocomplete live search (debounced) ─────────── */
    window.onSearchInput = function (val) {
        clearTimeout(debounceTimer);
        const q = val.trim();
        if (q.length < 2) { closeAutocomplete(); return; }

        debounceTimer = setTimeout(() => {
            fetch('../../ajax/consultation/patient_search.ajax.php?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {
                    if (!data.ok) { closeAutocomplete(); return; }
                    if (data.found) {
                        renderAutocomplete([data.patient || data]);
                        return;
                    }
                    if (data.multiple && Array.isArray(data.results)) {
                        renderAutocomplete(data.results);
                    } else {
                        closeAutocomplete();
                    }
                })
                .catch(() => closeAutocomplete());
        }, 280);
    };

    function renderAutocomplete(results) {
        if (!acList) return;
        acList.innerHTML = '';

        results.forEach(p => {
            const li = document.createElement('li');
            li.className = 'ac-item';
            li.setAttribute('tabindex', '0');
            li.innerHTML = `
                <span class="ac-name">${p.FullName || p.LastName}</span>
                <span class="ac-id">${p.SchoolID}</span>
                <span class="ac-meta">${p.PersonType || ''}</span>
            `;
            li.addEventListener('click', () => {
                if (qInput) qInput.value = p.SchoolID;
                closeAutocomplete();
                setFeedback('<i class="fa-solid fa-circle-check"></i> Patient loaded.', 'found');
                loadPatient(p);
            });
            li.addEventListener('keydown', e => { if (e.key === 'Enter') li.click(); });
            acList.appendChild(li);
        });

        acList.classList.add('open');
    }

    document.addEventListener('click', e => {
        if (acList && !acList.contains(e.target) && e.target !== qInput) closeAutocomplete();
    });

    /* ══════════════════════════════════════════════════
       5. SERVICE TYPE DYNAMIC UI
    ══════════════════════════════════════════════════ */

    /**
     * Section keys → maps to id="section-{key}" OR data-section="{key}".
     * Hidden sections still exist in DOM but are invisible — they do NOT
     * submit data because the server ignores fields for other service types.
     */
    const ALL_SECTION_KEYS = [
        'vitals',
        'consultation-details',
        'medicines',
        'attachment',
        'physical-exam',
        'firstaid'
    ];

    /**
     * Per service type: list of section keys to HIDE.
     * Everything not listed stays visible.
     */
    const SERVICE_HIDE_MAP = {
        //                        consultation-details is NEVER hidden — always visible on all service types
        'General Consultation': ['physical-exam', 'firstaid'],
        'Dental':               ['physical-exam', 'firstaid'],
        'First Aid':            ['physical-exam', 'attachment'],
        'Medical Certificate':  ['vitals', 'physical-exam', 'medicines', 'firstaid'],
        'Physical Examination': ['vitals', 'firstaid'],
        'Other':                [],
    };

    function getSectionEl(key) {
        return document.getElementById('section-' + key)
            || document.querySelector('[data-section="' + key + '"]');
    }

    function setSectionVisible(key, visible) {
        const el = getSectionEl(key);
        if (!el) return;
        if (visible) {
            el.classList.remove('hidden');
            el.style.display = '';
        } else {
            el.classList.add('hidden');
            el.style.display = 'none';
        }
    }

    function applyServiceType(val) {
        const hideList = SERVICE_HIDE_MAP[val] ?? [];

        // First show ALL sections
        ALL_SECTION_KEYS.forEach(key => setSectionVisible(key, true));

        // Then hide only what this service type doesn't need
        hideList.forEach(key => setSectionVisible(key, false));

        // Show/hide attachment required badge for Medical Certificate
        const attachBadge = document.getElementById('attachmentRequiredBadge');
        if (attachBadge) attachBadge.style.display = (val === 'Medical Certificate') ? '' : 'none';

        // Update page-header service badge
        const badge = document.getElementById('serviceTypeBadge');
        if (badge) {
            const badgeMap = {
                'General Consultation': ['general',  'General'],
                'First Aid':            ['firstaid', 'First Aid'],
                'Medical Certificate':  ['medcert',  'Med Cert'],
                'Physical Examination': ['physexam', 'Physical Exam']
            };
            const [cls, label] = badgeMap[val] || ['general', val || 'General'];
            badge.className   = 'service-badge ' + cls;
            badge.textContent = label;
            badge.style.display = val ? '' : 'none';
        }
    }

    window.onServiceTypeChange = function (val) {
        const otherWrap = document.getElementById('otherServiceWrap');
        if (otherWrap) otherWrap.style.display = (val === 'Other') ? '' : 'none';
        applyServiceType(val);
    };

    // Attach directly to select for reliability
    (function initServiceType() {
        const sel = document.getElementById('consultService');
        if (!sel) return;

        sel.addEventListener('change', function () {
            window.onServiceTypeChange(this.value);
        });

        if (sel.value) {
            applyServiceType(sel.value);
        } else {
            applyServiceType('General Consultation');
        }
    })();


    /* ══════════════════════════════════════════════════
       START TRANSACTION
    ══════════════════════════════════════════════════ */
    function startTransaction(spid) {
        if (!spid) {
            setFeedback('<i class="fa-solid fa-triangle-exclamation"></i> Missing patient ID.', 'not-found');
            return;
        }

        fetch('../../ajax/consultation/create_transaction.ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: new URLSearchParams({ school_person_id: spid, mode: 'auto' })
        })
            .then(r => r.json())
            .then(resp => {
                if (!resp.ok) {
                    if (resp.historyCount && resp.historyCount > 0) {
                        const modal = document.getElementById('txConfirmModal');
                        if (modal) modal.style.display = 'block';
                        showOverlay();
                    } else {
                        console.error('Create transaction failed:', resp);
                        setFeedback('<i class="fa-solid fa-triangle-exclamation"></i> ' + (resp.debug || resp.message || 'Unable to start transaction.'), 'not-found');
                    }
                    return;
                }
                document.getElementById('consultationID').value = resp.consultation_id;
                unlockForm();
                setFeedback(
                    '<i class="fa-solid fa-circle-check"></i> Transaction #' + resp.transaction_number + ' started.',
                    'found'
                );
            })
            .catch(err => {
                console.error('Create transaction fetch error:', err);
                setFeedback('<i class="fa-solid fa-triangle-exclamation"></i> Server error while starting transaction.', 'not-found');
            });
    }

    window.confirmAddAnother = function (yes) {
        const modal = document.getElementById('txConfirmModal');
        if (modal) modal.style.display = 'none';

        if (!yes) {
            document.getElementById('consultationID').value = '';
            lockForm();
            return;
        }

        const spidEl = document.getElementById('consultPatientID');
        if (!spidEl || !spidEl.value) {
            alert('Patient ID missing. Please search again.');
            return;
        }
        const spid = parseInt(spidEl.value, 10);

        fetch('../../ajax/consultation/create_transaction.ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: new URLSearchParams({ school_person_id: spid, mode: 'next' })
        })
            .then(r => r.json())
            .then(resp => {
                if (!resp.ok) {
                    console.error('confirmAddAnother failed:', resp);
                    lockForm();
                    return;
                }
                document.getElementById('consultationID').value = resp.consultation_id;
                unlockForm();
                setFeedback(
                    '<i class="fa-solid fa-circle-check"></i> Transaction #' + resp.transaction_number + ' started.',
                    'found'
                );
            })
            .catch(() => {
                setFeedback('<i class="fa-solid fa-triangle-exclamation"></i> Server error creating transaction.', 'not-found');
            });
    };

    window.closeTxConfirm = function () {
        const modal = document.getElementById('txConfirmModal');
        if (modal) modal.style.display = 'none';
    };

    function unlockForm() {
        const area    = document.getElementById('consultFormArea') || document.getElementById('consultationForm');
        const overlay = document.getElementById('disabledOverlay');
        if (area)    { area.classList.remove('disabled'); area.style.pointerEvents = ''; area.style.opacity = ''; }
        if (overlay) { overlay.classList.remove('show'); overlay.style.display = 'none'; }
    }

    function lockForm() {
        const area    = document.getElementById('consultFormArea') || document.getElementById('consultationForm');
        const overlay = document.getElementById('disabledOverlay');
        if (area)    area.classList.add('disabled');
        if (overlay) { overlay.classList.add('show'); overlay.style.display = 'flex'; }
    }

    function showOverlay() {
        const overlay = document.getElementById('disabledOverlay');
        if (overlay) { overlay.classList.add('show'); overlay.style.display = 'flex'; }
    }

    /* ══════════════════════════════════════════════════
       3. TRANSACTIONAL FORM SUBMIT
       The server-side PHP must wrap everything in a DB
       transaction. This JS collects all visible section
       data and POSTs it in a single FormData request.
    ══════════════════════════════════════════════════ */
    const form = document.getElementById('consultationForm');

    /**
     * Called by the Save button (which lives OUTSIDE the form to avoid
     * the pointer-events:none lockout from .consult-form-area.disabled).
     */
    window.submitConsultForm = function () {
        if (!form) { showToast('Form not found.', 'error'); return; }

        const consultationID = document.getElementById('consultationID')?.value;
            if (!consultationID) {
                showToast('No active transaction. Please search a patient first.', 'error');
                return;
            }

            const serviceType = document.getElementById('consultService')?.value;
            const complaint   = document.getElementById('consultConcern')?.value.trim();

            const cErr = document.getElementById('consultConcernErr');
            const sErr = document.getElementById('consultServiceErr');
            if (cErr) cErr.textContent = '';
            if (sErr) sErr.textContent = '';

            /* Service type required */
            if (!serviceType) {
                if (sErr) sErr.textContent = 'Service type is required.';
                return;
            }

            /* Chief complaint required except Medical Certificate */
            if (serviceType !== 'Medical Certificate' && serviceType !== 'Physical Examination' && !complaint) {
                if (cErr) cErr.textContent = 'Chief complaint is required.';
                return;
            }

            /* Validate Medical Certificate needs an attachment */
            if (serviceType === 'Medical Certificate') {
                const fileInput = document.getElementById('consultAttachmentFile');
                if (!fileInput || !fileInput.files?.length) {
                    showToast('A document must be attached for Medical Certificate.', 'error');
                    return;
                }
            }

            /* Validate Physical Exam required fields */
            if (serviceType === 'Physical Examination') {
                const vErr = validatePhysExam();
                if (vErr) { showToast(vErr, 'error'); return; }
            }

            /* Validate numeric vitals when vitals section visible */
            const vitalsSection = document.getElementById('section-vitals');
            const vitalsVisible = vitalsSection && !vitalsSection.classList.contains('hidden');
            if (vitalsVisible) {
                const vErr = validateVitals();
                if (vErr) { showToast(vErr, 'error'); return; }
            }

            const btn = document.getElementById('btnSaveConsult');
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…'; }

            const formData = new FormData(form);

            /* Append PE fields if Physical Exam is active */
            if (serviceType === 'Physical Examination') {
                appendPhysExamData(formData);
            }

            /* Mark which sections are active so server can skip irrelevant ones */
            formData.set('active_service_type', serviceType);

            /*
             * SERVER MUST:
             * 1. BEGIN TRANSACTION
             * 2. INSERT / UPDATE clinic_transactions (use consultationID)
             * 3. If Physical Examination: INSERT into physical_examinations
             * 4. If medicines present: INSERT into medicine_dispensing, UPDATE medicine_inventory
             * 5. If attachment: save file, record path
             * 6. COMMIT — or ROLLBACK on any failure
             */
            fetch('../../ajax/consultation/save_consultation.ajax.php', {
                method: 'POST',
                body: formData
            })
                .then(r => r.json())
                .then(resp => {
                    if (resp.ok) {
                        showToast(
                            `Saved! ${resp.service_type ?? serviceType}` +
                            (resp.medicines_given ? ` · ${resp.medicines_given} medicine(s) dispensed` : ''),
                            'success'
                        );
                        const spid = document.getElementById('consultPatientID')?.value;
                        if (spid) loadHistory(parseInt(spid, 10));
                    } else {
                        // Show the real server-side error so staff can act on it
                        const errMsg = resp.message || 'Save failed. Check your connection and try again.';
                        showToast(errMsg, 'error');
                        console.error('Save failed:', resp);
                    }
                })
                .catch(err => {
                    console.error('Save error:', err);
                    showToast('Server error while saving.', 'error');
                })
                .finally(() => {
                    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save Consultation'; }
                });
    };

    /* ── Clear form ───────────────────────────────────── */
    window.clearForm = function () {
        if (!confirm('Clear the form? Unsaved data will be lost.')) return;
        if (form) form.reset();
        document.getElementById('medsList')?.replaceChildren();
        medRowIndex = 0;

        // Reset BMI
        const valEl = document.getElementById('bmiValue');
        const catEl = document.getElementById('bmiCategory');
        if (valEl) valEl.textContent = '—';
        if (catEl) { catEl.textContent = ''; catEl.className = 'bmi-display-cat'; }

        resetPhysExam();
        removeAttachment();

        // Re-apply service type (reset any leftover section visibility)
        const sel = document.getElementById('consultService');
        if (sel) applyServiceType(sel.value || 'General Consultation');
    };

    /* ══════════════════════════════════════════════════
       1. PHYSICAL EXAMINATION WORKFLOW
    ══════════════════════════════════════════════════ */

    /**
     * All exam field IDs used in the PE form.
     * Must match the id attributes in the HTML.
     */
    const EXAM_FIELDS = [
        { id: 'examEars',        label: 'Ears' },
        { id: 'examEyesPupil',   label: 'Eyes / Pupil' },
        { id: 'examHeart',       label: 'Heart' },
        { id: 'examNose',        label: 'Nose' },
        { id: 'examThorax',      label: 'Thorax' },
        { id: 'examAbdomen',     label: 'Abdomen' },
        { id: 'examLungs',       label: 'Lungs' },
        { id: 'examSkin',        label: 'Skin' },
        { id: 'examExtremities', label: 'Extremities' },
        { id: 'examDeformities', label: 'Deformities' }
    ];

    /**
     * Toggle Normal / Abnormal badge on a PE field.
     * Called by onclick="toggleExamBadge('examEars','normal')"
     */
    window.toggleExamBadge = function (fieldId, status) {
        const normalBtn   = document.querySelector(`[data-field="${fieldId}"][data-status="normal"]`);
        const abnormalBtn = document.querySelector(`[data-field="${fieldId}"][data-status="abnormal"]`);
        const inputEl     = document.getElementById(fieldId);

        if (!normalBtn || !abnormalBtn) return;

        if (status === 'normal') {
            normalBtn.classList.add('active');
            abnormalBtn.classList.remove('active');
            if (inputEl) inputEl.classList.remove('is-abnormal');
        } else {
            abnormalBtn.classList.add('active');
            normalBtn.classList.remove('active');
            if (inputEl) inputEl.classList.add('is-abnormal');
        }
    };

    /**
     * Select cardio-pulmonary clearance result.
     * Called by onclick="selectClearance('Fit')"
     */
    window.selectClearance = function (value) {
        document.querySelectorAll('.clearance-option').forEach(opt => {
            opt.classList.remove('selected-fit', 'selected-unfit', 'selected-pending');
        });
        const cls = { 'Fit': 'selected-fit', 'Unfit': 'selected-unfit', 'Pending': 'selected-pending' }[value];
        const target = document.querySelector(`.clearance-option[data-value="${value}"]`);
        if (target && cls) target.classList.add(cls);

        const hiddenInput = document.getElementById('examCardioClearance');
        if (hiddenInput) hiddenInput.value = value;
    };

    /**
     * Collect PE dropdown values + Normal/Abnormal status into FormData.
     * Inserts into physical_examinations on the server side.
     */
    function appendPhysExamData(formData) {
        // Body system exam fields (dropdowns + Normal/Abnormal status)
        EXAM_FIELDS.forEach(f => {
            const el = document.getElementById(f.id);
            if (el) formData.set(f.id, el.value || '');

            const abnormalBtn = document.querySelector(`[data-field="${f.id}"][data-status="abnormal"]`);
            if (abnormalBtn) {
                formData.set(f.id + '_status', abnormalBtn.classList.contains('active') ? 'Abnormal' : 'Normal');
            }
        });

        // PE-specific meta fields
        const remarksEl   = document.getElementById('examRemarks');
        const clearanceEl = document.getElementById('examCardioClearance');
        const dateEl      = document.getElementById('examDate');

        if (remarksEl)   formData.set('exam_remarks',          remarksEl.value   || '');
        if (clearanceEl) formData.set('exam_cardio_clearance', clearanceEl.value || '');
        if (dateEl)      formData.set('exam_date',             dateEl.value      || '');

        // Vitals — always send from the standalone vitals fields.
        // Even though #section-vitals is hidden for PE, the inputs are
        // still in the DOM and their values must reach physical_examinations.
        const vitalsMap = {
            blood_pressure: 'consultBP',
            temperature:    'consultTemp',
            pulse_rate:     'consultPulse',
            weight:         'consultWeight',
            height:         'consultHeight',
        };
        Object.entries(vitalsMap).forEach(([fieldName, elId]) => {
            const el = document.getElementById(elId);
            if (el) formData.set(fieldName, el.value || '');
        });
    }

    /** Validate required Physical Exam fields before submit */
    function validatePhysExam() {
        const examDate = document.getElementById('examDate')?.value;
        if (!examDate) return 'Examination date is required.';
        const clearance = document.getElementById('examCardioClearance')?.value;
        if (!clearance) return 'Please select a medical clearance result (Fit / Unfit / Pending).';
        return null;
    }

    /** Reset all physical exam fields and badges */
    function resetPhysExam() {
        EXAM_FIELDS.forEach(f => {
            const el = document.getElementById(f.id);
            if (el) { el.value = ''; el.classList.remove('is-abnormal'); }

            const normalBtn   = document.querySelector(`[data-field="${f.id}"][data-status="normal"]`);
            const abnormalBtn = document.querySelector(`[data-field="${f.id}"][data-status="abnormal"]`);
            if (normalBtn)   normalBtn.classList.remove('active');
            if (abnormalBtn) abnormalBtn.classList.remove('active');
        });
        document.querySelectorAll('.clearance-option').forEach(opt => {
            opt.classList.remove('selected-fit', 'selected-unfit', 'selected-pending');
        });
        const hiddenClearance = document.getElementById('examCardioClearance');
        if (hiddenClearance) hiddenClearance.value = '';

        const remarksEl = document.getElementById('examRemarks');
        if (remarksEl) remarksEl.value = '';
    }

    /* ══════════════════════════════════════════════════
       BMI AUTO-CALCULATION
    ══════════════════════════════════════════════════ */
    window.calcBMI = function () {
        const wEl = document.getElementById('consultWeight');
        const hEl = document.getElementById('consultHeight');

        if (!wEl || !hEl) return;

        const w  = parseFloat(wEl.value);
        const hm = parseFloat(hEl.value) / 100;

        const valEl = document.getElementById('bmiValue');
        const catEl = document.getElementById('bmiCategory');

        if (!w || !hm || hm <= 0) {
            if (valEl) valEl.textContent = '—';
            if (catEl) { catEl.textContent = ''; catEl.className = 'bmi-display-cat'; }
            return;
        }

        const bmi = w / (hm * hm);
        if (valEl) valEl.textContent = bmi.toFixed(1);

        let label = '', cls = '';
        if      (bmi < 18.5) { label = 'Underweight'; cls = 'bmi-low';  }
        else if (bmi < 25)   { label = 'Normal';       cls = 'bmi-ok';   }
        else if (bmi < 30)   { label = 'Overweight';   cls = 'bmi-warn'; }
        else                 { label = 'Obese';         cls = 'bmi-bad';  }

        if (catEl) { catEl.textContent = label; catEl.className = 'bmi-display-cat ' + cls; }
    };

    // Wire up height/weight inputs to BMI calc
    ['consultWeight', 'consultHeight'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', window.calcBMI);
    });

    /* ── Validate numeric vitals ──────────────────────── */
    function validateVitals() {
        const checks = [
            { id: 'consultWeight', label: 'Weight',      min: 1,  max: 300 },
            { id: 'consultHeight', label: 'Height',      min: 50, max: 250 },
            { id: 'consultTemp',   label: 'Temperature', min: 30, max: 45  },
            { id: 'consultPulse',  label: 'Pulse Rate',  min: 20, max: 300 },

        ];
        for (const f of checks) {
            const el = document.getElementById(f.id);
            if (!el || !el.value) continue;
            const v = parseFloat(el.value);
            if (isNaN(v) || v < f.min || v > f.max) {
                return `${f.label} value (${el.value}) is out of range (${f.min}–${f.max}).`;
            }
        }
        return null;
    }

    /* ══════════════════════════════════════════════════
       2. MEDICINE DISPENSING (3NF)
       Tables: medicines → medicine_inventory → medicine_dispensing
       Logic:
         - Search queries medicine_inventory (FIFO, in-stock only)
         - On select: show available stock, enforce max qty
         - On save: server deducts from inventory, prevents negatives
    ══════════════════════════════════════════════════ */
    let medRowIndex = 0;
    let medDebounce = {};

    window.addMedRow = function () {
        const container = document.getElementById('medsList');
        if (!container) return;

        const idx = medRowIndex++;
        const div = document.createElement('div');
        div.className = 'med-entry';
        div.id = 'medRow_' + idx;
        div.innerHTML = `
            <div class="form-group med-name-group">
                <label>Medicine / Drug</label>
                <input type="text"
                       id="medNameInput_${idx}"
                       placeholder="Search medicine name or generic…"
                       autocomplete="off"
                       oninput="onMedInput(this, ${idx})"
                       data-row="${idx}">
                <input type="hidden" name="med_inventory_id[]" id="medInvID_${idx}" value="">
                <ul class="med-autocomplete" id="medAC_${idx}"></ul>
                <div id="medStock_${idx}" style="margin-top:4px;"></div>
            </div>
            <div class="form-group">
                <label>Qty to Dispense</label>
                <input type="number" name="med_qty[]" id="medQty_${idx}" min="1" value="1" style="max-width:110px;">
            </div>
            <div class="form-group" style="grid-column:1/-1;">
                <label>Instructions / Sig</label>
                <input type="text" name="med_instructions[]"
                       placeholder="e.g. Take 1 tablet after meals, TID for 5 days">
            </div>
            <div style="display:flex; justify-content:flex-end; grid-column:1/-1;">
                <button type="button" class="remove-med" onclick="removeMedRow(${idx})" title="Remove medicine">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        `;
        container.appendChild(div);
    };

    window.removeMedRow = function (idx) {
        const row = document.getElementById('medRow_' + idx);
        if (row) row.remove();
    };

    /**
     * Medicine search autocomplete.
     * Queries: search_medicine.ajax.php
     * Returns: MedicineName, GenericName, MedicineType, Dosage, InventoryID,
     *          AvailableQty (FIFO earliest non-expired batch), ExpiryDate
     */
    window.onMedInput = function (inputEl, idx) {
        clearTimeout(medDebounce[idx]);
        const q   = inputEl.value.trim();
        const ac  = document.getElementById('medAC_' + idx);
        const inv = document.getElementById('medInvID_' + idx);

        if (inv) inv.value = '';
        if (!ac) return;
        if (q.length < 2) { ac.innerHTML = ''; ac.classList.remove('open'); return; }

        medDebounce[idx] = setTimeout(() => {
            fetch('../../ajax/consultation/search_medicine.ajax.php?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => {
                    ac.innerHTML = '';
                    if (!data.ok || !data.results?.length) { ac.classList.remove('open'); return; }

                    data.results.forEach(m => {
                        const li = document.createElement('li');
                        li.className = 'ac-item';
                        li.setAttribute('tabindex', '0');

                        const qty    = parseInt(m.AvailableQty ?? 0);
                        const isLow  = qty > 0 && qty <= (m.ReorderLevel ?? 10);
                        const isOut  = qty <= 0;
                        const stockCls   = isOut ? 'ac-stock-out' : isLow ? 'ac-stock-low' : 'ac-stock-ok';
                        const stockLabel = isOut ? 'Out of stock' : `${qty} in stock`;

                        li.innerHTML = `
                            <span class="ac-name">${m.MedicineName}${m.Dosage ? ' — ' + m.Dosage : ''}</span>
                            <span class="ac-meta">${m.GenericName ?? ''} · ${m.MedicineType ?? ''}</span>
                            <span class="ac-stock ${stockCls}">${stockLabel}</span>
                        `;

                        if (isOut) li.style.opacity = '.5';

                        li.addEventListener('click', () => {
                            if (isOut) { showToast('This medicine is currently out of stock.', 'error'); return; }

                            inputEl.value = m.MedicineName + (m.Dosage ? ' ' + m.Dosage : '');
                            if (inv) inv.value = m.InventoryID;

                            // Show stock badge
                            const stockEl = document.getElementById('medStock_' + idx);
                            if (stockEl) {
                                const badgeCls = isLow ? 'stock-low' : 'stock-ok';
                                stockEl.innerHTML = `
                                    <span class="stock-badge ${badgeCls}">
                                        <i class="fa-solid fa-box"></i> ${qty} in stock
                                    </span>
                                `;
                            }

                            // Enforce max qty = available stock (prevent over-dispensing)
                            const qtyEl = document.getElementById('medQty_' + idx);
                            if (qtyEl) {
                                qtyEl.max = qty;
                                if (parseInt(qtyEl.value) > qty) qtyEl.value = qty;
                            }

                            ac.innerHTML = '';
                            ac.classList.remove('open');
                        });

                        li.addEventListener('keydown', e => { if (e.key === 'Enter') li.click(); });
                        ac.appendChild(li);
                    });

                    ac.classList.add('open');
                })
                .catch(() => ac.classList.remove('open'));
        }, 260);
    };

    // Close medicine autocomplete on outside click
    document.addEventListener('click', e => {
        document.querySelectorAll('.med-autocomplete.open').forEach(ac => {
            if (!ac.contains(e.target)) {
                ac.innerHTML = '';
                ac.classList.remove('open');
            }
        });
    });

    /* ══════════════════════════════════════════════════
       ATTACHMENT UPLOAD / PREVIEW
    ══════════════════════════════════════════════════ */
    window.handleAttachmentSelect = function (input) {
        const file    = input.files?.[0];
        const errEl   = document.getElementById('pdfErrMsg');
        const errText = document.getElementById('pdfErrText');
        const preview = document.getElementById('pdfFilePreview');
        const zone    = document.getElementById('pdfUploadZone');
        const nameEl  = document.getElementById('pdfFileName');
        const sizeEl  = document.getElementById('pdfFileSize');

        if (errEl) errEl.style.display = 'none';
        if (!file) return;

        const allowed = ['image/jpeg', 'image/png', 'application/pdf'];
        if (!allowed.includes(file.type)) {
            if (errText) errText.textContent = 'Invalid file type. Allowed: JPG, PNG, PDF.';
            if (errEl)   errEl.style.display = 'flex';
            input.value = '';
            return;
        }
        if (file.size > 50 * 1024 * 1024) {
            if (errText) errText.textContent = 'File too large. Maximum 50 MB.';
            if (errEl)   errEl.style.display = 'flex';
            input.value = '';
            return;
        }

        if (nameEl) nameEl.textContent = file.name;
        if (sizeEl) sizeEl.textContent = (file.size / 1024).toFixed(1) + ' KB';
        if (zone)    zone.style.display    = 'none';
        if (preview) preview.style.display = 'flex';
    };

    window.removeAttachment = function () {
        const input   = document.getElementById('consultAttachmentFile');
        const preview = document.getElementById('pdfFilePreview');
        const zone    = document.getElementById('pdfUploadZone');
        if (input)   input.value = '';
        if (preview) preview.style.display = 'none';
        if (zone)    zone.style.display    = '';
    };

    // Drag-and-drop support for attachment zone
    (function initDragDrop() {
        const zone = document.getElementById('pdfUploadZone');
        if (!zone) return;

        zone.addEventListener('dragover', e => {
            e.preventDefault();
            zone.classList.add('dragover');
        });
        zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
        zone.addEventListener('drop', e => {
            e.preventDefault();
            zone.classList.remove('dragover');
            const dt    = e.dataTransfer;
            const input = document.getElementById('consultAttachmentFile');
            if (!dt || !input) return;
            // Assign dropped file to the file input and trigger handler
            try {
                const dataTransfer = new DataTransfer();
                if (dt.files[0]) dataTransfer.items.add(dt.files[0]);
                input.files = dataTransfer.files;
                window.handleAttachmentSelect(input);
            } catch (err) {
                console.warn('DataTransfer assignment not supported:', err);
            }
        });
    })();

    /* ══════════════════════════════════════════════════
       4. CONSULTATION HISTORY — EMR READ-ONLY VIEWER
       - Timeline view (primary) with clickable cards
       - Detail panel: read-only, cannot be edited
       - Filters: search, date range
       - Actions: print, export PDF
    ══════════════════════════════════════════════════ */
    let historyData         = [];
    let historyFilterTimer  = null;

    function loadHistory(spid) {
        const container    = document.getElementById('historyTimeline');
        const tableFallback = document.getElementById('consultHistoryTbody');

        if (container) {
            container.innerHTML = `<div style="padding:20px 0;text-align:center;color:var(--gray-400);">
                <i class="fa-solid fa-spinner fa-spin"></i> Loading history…
            </div>`;
        }

        fetch('../../ajax/consultation/list_transactions.ajax.php?school_person_id=' + encodeURIComponent(spid))
            .then(r => r.json())
            .then(resp => {
                if (!resp.ok || !Array.isArray(resp.transactions) || resp.transactions.length === 0) {
                    if (container) container.innerHTML = `<div style="padding:24px 0;text-align:center;color:var(--gray-400);font-weight:700;font-size:.85rem;">
                        <i class="fa-solid fa-clock-rotate-left" style="font-size:1.5rem;display:block;margin-bottom:8px;"></i>
                        No consultation history yet.
                    </div>`;
                    if (tableFallback) tableFallback.innerHTML = '<tr><td colspan="6" class="muted">No consultation history yet.</td></tr>';
                    historyData = [];
                    return;
                }

                historyData = resp.transactions;
                renderHistoryTimeline(historyData);
                if (tableFallback) renderHistoryTable(historyData, tableFallback);
            })
            .catch(err => {
                console.error('Load history error:', err);
                if (container) container.innerHTML = `<div style="padding:20px 0;text-align:center;color:var(--danger);">
                    Failed to load history.
                </div>`;
            });
    }

    function renderHistoryTimeline(transactions) {
        const container = document.getElementById('historyTimeline');
        if (!container) return;

        container.innerHTML = '';

        if (!transactions.length) {
            container.innerHTML = `<div style="padding:16px 0;text-align:center;color:var(--gray-400);font-size:.84rem;font-weight:700;">
                No records match your filter.
            </div>`;
            return;
        }

        transactions.forEach(tx => {
            const statusClass = {
                'Completed' : 'hist-status-done',
                'Cancelled' : 'hist-status-cancel',
                'Waiting'   : 'hist-status-wait',
                'Consulting': 'hist-status-active',
            }[tx.Status] || '';

            const medBadges = Array.isArray(tx.medicines) && tx.medicines.length
                ? tx.medicines.map(m =>
                    `<span class="hist-med">${m.MedicineName}${m.Dosage ? ' ' + m.Dosage : ''}</span>`
                  ).join('')
                : '<span class="muted-sm">No medicines</span>';

            const item = document.createElement('div');
            item.className = 'history-item';
            item.setAttribute('data-tx-id', tx.ClinicTransactionID || tx.TransactionNumber);
            item.innerHTML = `
                <div class="history-dot"></div>
                <div class="history-item-card">
                    <div class="history-item-top">
                        <span class="hist-tx-num">#${tx.TransactionNumber ?? tx.ClinicTransactionID ?? ''}</span>
                        <span class="hist-date">${tx.VisitDate ?? tx.CreatedAt ?? ''}</span>
                        <span class="hist-status ${statusClass}">${tx.Status ?? ''}</span>
                    </div>
                    <div class="hist-service-tag">
                        <i class="fa-solid fa-stethoscope"></i> ${tx.ServiceType ?? 'General'}
                    </div>
                    <div class="hist-complaint">${tx.Complaint ?? '—'}</div>
                    <div class="hist-meds">${medBadges}</div>
                </div>
            `;

            // Click → open READ-ONLY detail panel (no editing allowed)
            item.addEventListener('click', () => {
                document.querySelectorAll('.history-item.active').forEach(i => i.classList.remove('active'));
                item.classList.add('active');
                openHistoryDetail(tx);
            });

            container.appendChild(item);
        });
    }

    /** History detail panel — READ ONLY. Cannot be edited, overwritten, or deleted. */
    function openHistoryDetail(tx) {
        const panel = document.getElementById('historyDetailPanel');
        if (!panel) return;
        panel.classList.add('visible');

        const titleEl = document.getElementById('historyDetailTitle');
        if (titleEl) titleEl.textContent = `Transaction #${tx.TransactionNumber ?? tx.ClinicTransactionID} — ${tx.ServiceType ?? 'Consultation'}`;

        const bodyEl = document.getElementById('historyDetailBody');
        if (!bodyEl) return;

        // ── Vitals ──
        let vitalsHtml = '';
        if (tx.Height || tx.Weight || tx.BloodPressure || tx.Temperature || tx.PulseRate) {
            vitalsHtml = `
                <div>
                    <div class="card-section-label" style="margin-bottom:10px;">
                        <i class="fa-solid fa-heart-pulse"></i> Vital Signs
                    </div>
                    <div class="ro-grid">
                        ${roField('Height',       tx.Height       ? tx.Height + ' cm'   : '—')}
                        ${roField('Weight',       tx.Weight       ? tx.Weight + ' kg'   : '—')}
                        ${roField('Blood Pressure', tx.BloodPressure || '—')}
                        ${roField('Temperature',  tx.Temperature  ? tx.Temperature + ' °C' : '—')}
                        ${roField('Pulse Rate',   tx.PulseRate    ? tx.PulseRate + ' bpm'  : '—')}
                    </div>
                </div>
            `;
        }

        // ── Physical Exam ──
        let peHtml = '';
        if (tx.physicalExam) {
            const pe = tx.physicalExam;
            const peStatus = (fieldVal, statusVal) => {
                if (!statusVal) return fieldVal || '—';
                const cls = statusVal === 'Abnormal'
                    ? 'style="color:var(--danger);font-weight:700;"'
                    : 'style="color:var(--success);font-weight:700;"';
                return `<span ${cls}>${fieldVal || '—'}</span>`;
            };
            peHtml = `
                <div>
                    <div class="card-section-label" style="margin-bottom:10px;">
                        <i class="fa-solid fa-clipboard-list"></i> Physical Examination
                    </div>
                    <div class="ro-grid">
                        ${roField('Exam Date',    pe.ExamDate || '—')}
                        ${roField('Ears',         pe.Ears || '—')}
                        ${roField('Eyes / Pupil', pe.EyesPupil || '—')}
                        ${roField('Heart',        pe.Heart || '—')}
                        ${roField('Nose',         pe.Nose || '—')}
                        ${roField('Thorax',       pe.Thorax || '—')}
                        ${roField('Abdomen',      pe.Abdomen || '—')}
                        ${roField('Lungs',        pe.Lungs || '—')}
                        ${roField('Skin',         pe.Skin || '—')}
                        ${roField('Extremities',  pe.Extremities || '—')}
                        ${roField('Deformities',  pe.Deformities || '—')}
                        ${roField('Cardio Clearance', pe.CardioClearance || '—')}
                    </div>
                    ${pe.Remarks ? `<div style="margin-top:12px;"><div class="ro-label">Remarks</div><div style="font-size:.87rem;font-weight:600;color:var(--text);margin-top:4px;line-height:1.5;">${pe.Remarks}</div></div>` : ''}
                </div>
            `;
        }

        // ── Medicines Dispensed ──
        let medsHtml = '';
        if (Array.isArray(tx.medicines) && tx.medicines.length) {
            const rows = tx.medicines.map(m => `
                <div class="ro-field" style="background:var(--gray-50);border:1.5px solid var(--gray-200);border-radius:var(--r-md);padding:10px 14px;gap:4px;">
                    <span class="ro-label">Medicine</span>
                    <span class="ro-value">${m.MedicineName}${m.Dosage ? ' ' + m.Dosage : ''}</span>
                    <span style="font-size:.75rem;color:var(--gray-500);">${m.Instructions ?? ''}</span>
                    <span style="font-size:.7rem;color:var(--gray-400);">Qty: ${m.QuantityDispensed ?? '—'}</span>
                </div>
            `).join('');
            medsHtml = `
                <div>
                    <div class="card-section-label" style="margin-bottom:10px;">
                        <i class="fa-solid fa-pills"></i> Medicines Dispensed
                    </div>
                    <div class="ro-grid">${rows}</div>
                </div>
            `;
        }

        const notesHtml = tx.Notes
            ? `<div style="margin:8px 0 12px;"><span class="ro-label">Clinical Notes</span>
               <div style="font-size:.87rem;font-weight:600;color:var(--text);margin-top:4px;line-height:1.55;">${tx.Notes}</div></div>`
            : '';

        bodyEl.innerHTML = `
            <div class="readonly-notice">
                <i class="fa-solid fa-lock"></i>
                This record is <strong>read-only</strong>. Past consultations cannot be edited or deleted.
            </div>
            <div class="ro-grid" style="margin:12px 0;">
                ${roField('Date',            tx.VisitDate ?? tx.CreatedAt ?? '—')}
                ${roField('Service Type',    tx.ServiceType ?? '—')}
                ${roField('Status',          tx.Status ?? '—')}
                ${roField('Chief Complaint', tx.Complaint ?? '—')}
            </div>
            ${notesHtml}
            ${vitalsHtml}
            ${peHtml}
            ${medsHtml}
        `;
    }

    function roField(label, value) {
        return `<div class="ro-field"><span class="ro-label">${label}</span><span class="ro-value">${value}</span></div>`;
    }

    window.closeHistoryDetail = function () {
        const panel = document.getElementById('historyDetailPanel');
        if (panel) panel.classList.remove('visible');
        document.querySelectorAll('.history-item.active').forEach(i => i.classList.remove('active'));
    };

    /** History search filter */
    window.onHistorySearch = function (val) {
        clearTimeout(historyFilterTimer);
        historyFilterTimer = setTimeout(() => {
            const q = val.trim().toLowerCase();
            if (!q) { renderHistoryTimeline(historyData); return; }
            const filtered = historyData.filter(tx =>
                (tx.Complaint ?? '').toLowerCase().includes(q) ||
                (tx.ServiceType ?? '').toLowerCase().includes(q) ||
                String(tx.TransactionNumber ?? '').includes(q)
            );
            renderHistoryTimeline(filtered);
        }, 200);
    };

    /** History date range filter */
    window.filterHistoryByDate = function () {
        const from = document.getElementById('historyDateFrom')?.value;
        const to   = document.getElementById('historyDateTo')?.value;

        if (!from && !to) { renderHistoryTimeline(historyData); return; }

        const filtered = historyData.filter(tx => {
            const d = new Date(tx.VisitDate ?? tx.CreatedAt ?? '');
            if (isNaN(d)) return true;
            const dStr = d.toISOString().slice(0, 10);
            if (from && dStr < from) return false;
            if (to   && dStr > to)   return false;
            return true;
        });
        renderHistoryTimeline(filtered);
    };

    window.printHistory = function () { window.print(); };

    /**
     * Export history detail as PDF.
     * Future-ready: sends the current open transaction to a PDF-generation endpoint.
     * For now, falls back to print dialog.
     */
    window.exportHistoryPDF = function () {
        const titleEl = document.getElementById('historyDetailTitle');
        const bodyEl  = document.getElementById('historyDetailBody');
        if (!bodyEl) { window.print(); return; }

        // Attempt to call a PDF export endpoint if available
        const txId = document.querySelector('.history-item.active')?.getAttribute('data-tx-id');
        if (txId) {
            const url = `../../ajax/consultation/export_pdf.ajax.php?transaction_id=${encodeURIComponent(txId)}`;
            window.open(url, '_blank');
        } else {
            window.print();
        }
    };

    /** Legacy table fallback renderer (for pages still using old table HTML) */
    function renderHistoryTable(transactions, tbody) {
        tbody.innerHTML = '';
        transactions.forEach(tx => {
            const statusClass = {
                'Completed' : 'hist-status-done',
                'Cancelled' : 'hist-status-cancel',
                'Waiting'   : 'hist-status-wait',
                'Consulting': 'hist-status-active',
            }[tx.Status] || '';

            const medBadges = Array.isArray(tx.medicines) && tx.medicines.length
                ? tx.medicines.map(m =>
                    `<span class="hist-med">${m.MedicineName}${m.Dosage ? ' ' + m.Dosage : ''}</span>`
                  ).join('')
                : '<span class="muted-sm">None</span>';

            const tr = document.createElement('tr');
            tr.style.cursor = 'pointer';
            tr.innerHTML = `
                <td>#${tx.TransactionNumber ?? ''}</td>
                <td>${tx.VisitDate ?? tx.CreatedAt ?? ''}</td>
                <td>${tx.ServiceType ?? '—'}</td>
                <td class="hist-complaint" title="${tx.Complaint ?? ''}">${tx.Complaint ?? '—'}</td>
                <td><span class="hist-status ${statusClass}">${tx.Status ?? ''}</span></td>
                <td><div class="hist-meds">${medBadges}</div></td>
            `;
            tr.addEventListener('click', () => openHistoryDetail(tx));
            tbody.appendChild(tr);
        });
    }

    /* ── Toast ────────────────────────────────────────── */
    function showToast(msg, type) {
        const toast = document.getElementById('consultToast');
        if (!toast) return;
        toast.textContent = msg;
        toast.className   = 'consult-toast ' + (type === 'error' ? 'error-toast' : type || '');
        // Errors stay visible longer so staff can read them
        const duration = (type === 'error') ? 6000 : 3500;
        setTimeout(() => toast.className = 'consult-toast', duration);
    }

    // Expose globally for other modules
    window.showConsultToast = showToast;

})();