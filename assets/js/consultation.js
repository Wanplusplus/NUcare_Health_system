(function () {

    const now = new Date();
    const ts  = now.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) +
                ' ' + now.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' });

    /* ── Search patient ── */
    window.searchPatient = function () {
        const query    = document.getElementById('consultSearchInput').value.trim();
        const feedback = document.getElementById('searchFeedback');

        if (!query) {
            feedback.className  = 'search-feedback not-found';
            feedback.innerHTML  = '<i class="ti ti-alert-circle"></i> Please enter a patient ID or name.';
            return;
        }

        feedback.className = 'search-feedback';
        feedback.innerHTML = '';

        fetch('consultation_search_patient.php?q=' + encodeURIComponent(query))
            .then(r => r.json())
            .then(data => {
                if (data.found) loadPatient(data);
                else patientNotFound();
            })
            .catch(() => {
                const demo = {
                    found: true,
                    patientID: '2024-00142',
                    patientFname: 'Maria',
                    patientMname: 'L.',
                    patientLname: 'Santos',
                    patientSex: 'Female',
                    patientBirthday: 'March 14, 2003',
                    patientProgram: 'BS Nursing',
                    patientPhone: '09XX-XXX-XXXX',
                };
                loadPatient(demo);
            });
    };

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

        const area = document.getElementById('consultFormArea');
        const actions = document.getElementById('consultFormActions');
        const overlay = document.getElementById('disabledOverlay');
        area.classList.remove('disabled');
        overlay.classList.remove('show');
        actions.style.opacity = '1';
        actions.style.pointerEvents = 'auto';

        const feedback = document.getElementById('searchFeedback');
        feedback.className = 'search-feedback found';
        feedback.innerHTML = '<i class="ti ti-circle-check"></i> Patient found — consultation form is now active.';
    }

    function patientNotFound() {
        document.getElementById('consultPatientCard').classList.remove('visible');
        document.getElementById('consultPatientID').value = '';

        const area = document.getElementById('consultFormArea');
        const actions = document.getElementById('consultFormActions');
        const overlay = document.getElementById('disabledOverlay');
        area.classList.add('disabled');
        overlay.classList.add('show');
        actions.style.opacity = '0.4';
        actions.style.pointerEvents = 'none';

        const feedback = document.getElementById('searchFeedback');
        feedback.className = 'search-feedback not-found';
        feedback.innerHTML = '<i class="ti ti-alert-circle"></i> No patient found. Please check the ID or name and try again.';
    }

    /* ── Medicine rows ── */
    let medCount = 1;
    window.addConsultMed = function () {
        const i = medCount++;
        const div = document.createElement('div');
        div.className = 'med-entry';
        div.id = 'med-' + i;
        div.innerHTML = `
            <div class="input-group">
                <label for="consultMedName${i}">Medicine Name</label>
                <input type="text" id="consultMedName${i}" name="consultMedName[]" placeholder="Medicine name (optional)">
                <span class="err-msg" id="medNameErr${i}"></span>
            </div>
            <div class="input-group">
                <label for="consultMedQty${i}">Qty</label>
                <input type="number" id="consultMedQty${i}" name="consultMedQty[]" placeholder="0" min="1">
                <span class="err-msg" id="medQtyErr${i}"></span>
            </div>
            <button class="remove-med" type="button" onclick="removeConsultMed('med-${i}')" title="Remove"><i class="ti ti-x"></i></button>`;
        document.getElementById('medsList').appendChild(div);
    };

    window.removeConsultMed = function (id) {
        const el = document.getElementById(id);
        if (el) el.remove();
    };

    document.getElementById('consultService').addEventListener('change', function () {
        document.getElementById('otherServiceWrap').style.display = this.value === 'Other' ? 'block' : 'none';
    });

    function showErr(id, msg) {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = msg;
        el.classList.add('show');
    }
    function clearErr(id) {
        const el = document.getElementById(id);
        if (!el) return;
        el.textContent = '';
        el.classList.remove('show');
    }
    function setInputErr(el, isErr) {
        if (!el) return;
        isErr ? el.classList.add('error') : el.classList.remove('error');
    }

    function showToast(msg, type) {
        const t = document.getElementById('consultToast');
        t.textContent = msg;
        t.className = 'toast ' + (type === 'success' ? 'success' : 'error-toast');
        clearTimeout(window._cToastTimer);
        window._cToastTimer = setTimeout(() => { t.className = ''; }, 3200);
    }

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
            const nm = document.getElementById('consultMedName' + idx);
            const qty = document.getElementById('consultMedQty' + idx);
            clearErr('medNameErr' + idx);
            clearErr('medQtyErr' + idx);
            setInputErr(nm, false);
            setInputErr(qty, false);

            const hasName = nm && nm.value.trim() !== '';
            const hasQty = qty && qty.value.trim() !== '' && parseInt(qty.value) >= 1;

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

    document.getElementById('clearConsultForm').addEventListener('click', function () {
        ['consultBP','consultTemp','consultPulse','consultWeight',
        'consultConcern','consultService','consultNotes','consultServiceOther']
            .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });

        ['consultConcernErr','consultServiceErr'].forEach(clearErr);
        ['consultConcern','consultService'].forEach(id => setInputErr(document.getElementById(id), false));

        document.getElementById('otherServiceWrap').style.display = 'none';

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