(function () {

    const now = new Date();
    const ts  = now.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) +
                ' ' + now.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' });

    /* ── Resolve AJAX endpoint from the DOM (set by PHP) ── */
    const searchBtn = document.querySelector('[data-search-url]');
    const SEARCH_URL = searchBtn ? searchBtn.dataset.searchUrl : 'consultation_search_patient.ajax.php';

    /* ── Search patient ── */
    window.searchPatient = function () {
        const query    = document.getElementById('consultSearchInput').value.trim();
        const feedback = document.getElementById('searchFeedback');

        if (!query) {
            feedback.className = 'search-feedback not-found';
            feedback.innerHTML = 'Please enter a patient ID or name.';
            return;
        }

        feedback.className = 'search-feedback';
        feedback.innerHTML = 'Searching…';

        // Hide any previous results dropdown
        closeResultsList();

        fetch(SEARCH_URL + '?q=' + encodeURIComponent(query))
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status + ' — ' + r.statusText);
                return r.json();
            })
            .then(data => {
                if (data.error) throw new Error(data.error);
                if (!data.found) {
                    patientNotFound();
                } else if (data.single) {
                    // Exact or only one match — load directly
                    loadPatient(data.patients[0]);
                } else {
                    // Multiple matches — show picker list
                    showResultsList(data.patients);
                }
            })
            .catch(err => {
                feedback.className = 'search-feedback not-found';
                feedback.innerHTML = 'Error: ' + err.message;
                console.error('Patient search error:', err);
            });
    };

    /* ── Results picker list ── */
    function showResultsList(patients) {
        closeResultsList();

        const feedback = document.getElementById('searchFeedback');
        feedback.className = 'search-feedback found';
        feedback.innerHTML = patients.length + ' patient(s) found. Select one below.';

        const wrapper = document.getElementById('consultSearchInput').closest('.search-input-wrapper') ||
                        document.getElementById('consultSearchInput').parentElement;

        const list = document.createElement('ul');
        list.id = 'patientResultsList';
        list.className = 'patient-results-list';

        patients.forEach(p => {
            const fullName = [p.patientFname, p.patientMname, p.patientLname].filter(Boolean).join(' ');
            const li = document.createElement('li');
            li.className = 'patient-result-item';
            li.innerHTML =
                '<span class="pri-name">' + escHtml(fullName) + '</span>' +
                '<span class="pri-meta">' + escHtml(p.patientID) + ' · ' + escHtml(p.patientProgram) + ' · ' + escHtml(p.patientSex) + '</span>';
            li.addEventListener('click', () => {
                loadPatient(p);
                closeResultsList();
                document.getElementById('consultSearchInput').value = fullName;
            });
            list.appendChild(li);
        });

        // Insert right after the search row
        const searchRow = document.querySelector('.consult-search-row');
        if (searchRow) {
            searchRow.insertAdjacentElement('afterend', list);
        }

        // Close on outside click
        setTimeout(() => {
            document.addEventListener('click', outsideClickClose);
        }, 0);
    }

    function closeResultsList() {
        const existing = document.getElementById('patientResultsList');
        if (existing) existing.remove();
        document.removeEventListener('click', outsideClickClose);
    }

    function outsideClickClose(e) {
        const list  = document.getElementById('patientResultsList');
        const input = document.getElementById('consultSearchInput');
        if (list && !list.contains(e.target) && e.target !== input) {
            closeResultsList();
        }
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    /* ── Load a selected patient into the card + unlock form ── */
    function loadPatient(data) {
        document.getElementById('cpcID').textContent      = data.patientID;
        document.getElementById('cpcSex').textContent     = data.patientSex;
        document.getElementById('cpcName').textContent    = [data.patientFname, data.patientMname, data.patientLname].filter(Boolean).join(' ');
        document.getElementById('cpcBday').textContent    = data.patientBirthday;
        document.getElementById('cpcProgram').textContent = data.patientProgram;
        document.getElementById('cpcTel').textContent     = data.patientPhone;
        document.getElementById('cpcTime').textContent    = ts;

        document.getElementById('consultPatientID').value = data.patientID;
        document.getElementById('consultPatientCard').classList.add('visible');

        const area    = document.getElementById('consultFormArea');
        const actions = document.getElementById('consultFormActions');
        const overlay = document.getElementById('disabledOverlay');
        area.classList.remove('disabled');
        overlay.classList.remove('show');
        actions.style.opacity       = '1';
        actions.style.pointerEvents = 'auto';

        const feedback = document.getElementById('searchFeedback');
        feedback.className = 'search-feedback found';
        feedback.innerHTML = 'Patient loaded — consultation form is now active.';
    }

    function patientNotFound() {
        document.getElementById('consultPatientCard').classList.remove('visible');
        document.getElementById('consultPatientID').value = '';

        const area    = document.getElementById('consultFormArea');
        const actions = document.getElementById('consultFormActions');
        const overlay = document.getElementById('disabledOverlay');
        area.classList.add('disabled');
        overlay.classList.add('show');
        actions.style.opacity       = '0.4';
        actions.style.pointerEvents = 'none';

        const feedback = document.getElementById('searchFeedback');
        feedback.className = 'search-feedback not-found';
        feedback.innerHTML = 'No patient found. Please check the ID or name and try again.';
    }

    /* ── Medicine rows ── */
    let medCount = 1;

    window.addConsultMed = function () {
        const i   = medCount++;
        const div = document.createElement('div');
        div.className = 'med-entry';
        div.id = 'med-' + i;
        div.innerHTML =
            '<div class="input-group">' +
                '<label for="consultMedName' + i + '">Medicine Name</label>' +
                '<input type="text" id="consultMedName' + i + '" name="consultMedName[]" placeholder="Medicine name (optional)">' +
                '<span class="err-msg" id="medNameErr' + i + '" style="display:none;"></span>' +
            '</div>' +
            '<div class="input-group med-qty-group">' +
                '<label for="consultMedQty' + i + '">Qty</label>' +
                '<input type="number" id="consultMedQty' + i + '" name="consultMedQty[]" placeholder="0" min="1">' +
                '<span class="err-msg" id="medQtyErr' + i + '" style="display:none;"></span>' +
            '</div>' +
            '<button type="button" class="med-remove-btn" onclick="this.closest(\'.med-entry\').remove()" title="Remove medicine">' +
                '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
            '</button>';
        document.getElementById('medsList').appendChild(div);
    };

    window.removeConsultMed = function (id) {
        const el = document.getElementById(id);
        if (el) el.remove();
    };

    /* ── Toggle "Other" service field ── */
    window.toggleOtherService = function (sel) {
        const w = document.getElementById('otherServiceWrap');
        if (w) w.style.display = sel.value === 'Other' ? '' : 'none';
    };

    /* ── Validation helpers ── */
    function showErr(id, msg) {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = msg;
        el.style.display = 'block';
    }
    function clearErr(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = '';
        el.style.display = 'none';
    }
    function setInputErr(el, isErr) {
        if (!el) return;
        isErr ? el.classList.add('error') : el.classList.remove('error');
    }

    function showToast(msg, type) {
        const t = document.getElementById('consultToast');
        if (!t) return;
        t.textContent = msg;
        t.className = 'consult-toast ' + (type === 'success' ? 'success' : 'error-toast');
        clearTimeout(window._cToastTimer);
        window._cToastTimer = setTimeout(() => { t.className = 'consult-toast'; }, 3200);
    }

    /* ── Form submit validation ── */
    document.getElementById('consultationForm').addEventListener('submit', function (e) {
        let valid = true;

        const concern = document.getElementById('consultConcern');
        const service = document.getElementById('consultService');

        clearErr('consultConcernErr');
        clearErr('consultServiceErr');
        setInputErr(concern, false);
        setInputErr(service, false);

        if (!concern.value.trim()) {
            showErr('consultConcernErr', 'This field is required');
            setInputErr(concern, true);
            valid = false;
        }
        if (!service.value) {
            showErr('consultServiceErr', 'This field is required');
            setInputErr(service, true);
            valid = false;
        }

        document.querySelectorAll('.med-entry').forEach(entry => {
            const idx = entry.id.split('-')[1];
            const nm  = document.getElementById('consultMedName' + idx);
            const qty = document.getElementById('consultMedQty' + idx);
            clearErr('medNameErr' + idx);
            clearErr('medQtyErr' + idx);
            setInputErr(nm, false);
            setInputErr(qty, false);

            const hasName = nm  && nm.value.trim() !== '';
            const hasQty  = qty && qty.value.trim() !== '' && parseInt(qty.value) >= 1;

            if (hasName && !hasQty) {
                showErr('medQtyErr' + idx, 'Must be a positive number');
                setInputErr(qty, true);
                valid = false;
            }
            if (hasQty && !hasName) {
                showErr('medNameErr' + idx, 'Medicine name required if qty is set');
                setInputErr(nm, true);
                valid = false;
            }
        });

        if (!valid) {
            e.preventDefault();
            showToast('Please fix the errors before saving', 'error');
        }
    });

    /* ── Clear form ── */
    document.getElementById('clearConsultForm').addEventListener('click', function () {
        ['consultBP','consultTemp','consultPulse','consultWeight',
         'consultConcern','consultService','consultNotes','consultServiceOther']
            .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });

        ['consultConcernErr','consultServiceErr'].forEach(clearErr);
        ['consultConcern','consultService'].forEach(id => setInputErr(document.getElementById(id), false));

        const wrap = document.getElementById('otherServiceWrap');
        if (wrap) wrap.style.display = 'none';

        const list = document.getElementById('medsList');
        Array.from(list.querySelectorAll('.med-entry')).forEach((entry, i) => {
            if (i > 0) { entry.remove(); return; }
            const n = document.getElementById('consultMedName0');
            const q = document.getElementById('consultMedQty0');
            if (n) { n.value = ''; setInputErr(n, false); }
            if (q) { q.value = ''; setInputErr(q, false); }
            clearErr('medNameErr0');
            clearErr('medQtyErr0');
        });
    });

})();