(function () {

    const now = new Date();
    const ts  = now.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) +
                ' · ' + now.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' });

    /* ── Search patient ── */
    window.searchPatient = function () {
        const query    = document.getElementById('consultSearchInput').value.trim();
        const feedback = document.getElementById('searchFeedback');

        if (!query) {
            feedback.className = 'search-feedback not-found';
            feedback.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> Please enter a patient ID or name.';
            return;
        }

        feedback.className = 'search-feedback';
        feedback.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Searching…';

        fetch('consultation_search_patient.php?q=' + encodeURIComponent(query))
            .then(r => r.json())
            .then(data => {
                if (data.found) loadPatient(data);
                else patientNotFound();
            })
            .catch(() => {
                /* Dev fallback */
                const demo = {
                    found:           true,
                    patientID:       '2024-00142',
                    patientFname:    'Maria',
                    patientMname:    'L.',
                    patientLname:    'Santos',
                    patientSex:      'Female',
                    patientBirthday: 'March 14, 2003',
                    patientProgram:  'BS Nursing',
                    patientPhone:    '09XX-XXX-XXXX',
                };
                loadPatient(demo);
            });
    };

    function loadPatient(data) {
        document.getElementById('cpcID').textContent      = data.patientID;
        document.getElementById('cpcSex').textContent     = data.patientSex;
        document.getElementById('cpcName').textContent    = [data.patientFname, data.patientMname, data.patientLname].filter(Boolean).join(' ');
        document.getElementById('cpcBday').textContent    = data.patientBirthday;
        document.getElementById('cpcProgram').textContent = data.patientProgram || '—';
        document.getElementById('cpcTel').textContent     = data.patientPhone;
        document.getElementById('cpcTime').textContent    = ts;

        document.getElementById('consultPatientID').value = data.patientID;
        document.getElementById('consultPatientCard').classList.add('visible');

        /* Enable form */
        const form    = document.getElementById('consultationForm');
        const actions = document.getElementById('consultFormActions');
        const overlay = document.getElementById('disabledOverlay');
        form.classList.remove('disabled');
        overlay.classList.remove('show');
        actions.style.opacity      = '1';
        actions.style.pointerEvents = 'auto';

        const feedback = document.getElementById('searchFeedback');
        feedback.className = 'search-feedback found';
        feedback.innerHTML = '<i class="fa-solid fa-circle-check"></i> Patient found — consultation form is now active.';
    }

    function patientNotFound() {
        document.getElementById('consultPatientCard').classList.remove('visible');
        document.getElementById('consultPatientID').value = '';

        const form    = document.getElementById('consultationForm');
        const actions = document.getElementById('consultFormActions');
        const overlay = document.getElementById('disabledOverlay');
        form.classList.add('disabled');
        overlay.classList.add('show');
        actions.style.opacity      = '0.4';
        actions.style.pointerEvents = 'none';

        const feedback = document.getElementById('searchFeedback');
        feedback.className = 'search-feedback not-found';
        feedback.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> No patient found. Please check the ID or name and try again.';
    }

    /* ── Medicine rows ── */
    let medCount = 1;
    window.addConsultMed = function () {
        const i   = medCount++;
        const div = document.createElement('div');
        div.className = 'med-entry';
        div.id        = 'med-' + i;
        div.innerHTML = `
            <div class="form-group">
                <label for="consultMedName${i}">Medicine Name</label>
                <input type="text" id="consultMedName${i}" name="consultMedName[]" placeholder="Medicine name">
                <span class="err-msg" id="medNameErr${i}"></span>
            </div>
            <div class="form-group">
                <label for="consultMedQty${i}">Qty</label>
                <input type="number" id="consultMedQty${i}" name="consultMedQty[]" placeholder="0" min="1">
                <span class="err-msg" id="medQtyErr${i}"></span>
            </div>
            <button class="remove-med" type="button" onclick="removeConsultMed('med-${i}')" title="Remove">
                <i class="fa-solid fa-xmark"></i>
            </button>`;
        document.getElementById('medsList').appendChild(div);
    };

    window.removeConsultMed = function (id) {
        const el = document.getElementById(id);
        if (el) el.remove();
    };

    /* Service "Other" toggle */
    document.getElementById('consultService')?.addEventListener('change', function () {
        document.getElementById('otherServiceWrap').style.display =
            this.value === 'Other' ? 'block' : 'none';
    });

    /* ── Validation helpers ── */
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
        t.className   = 'consult-toast ' + (type === 'success' ? 'success' : 'error-toast');
        clearTimeout(window._cToastTimer);
        window._cToastTimer = setTimeout(() => { t.className = 'consult-toast'; }, 3200);
    }

    /* ── Form Submit ── */
    document.getElementById('consultationForm')?.addEventListener('submit', function (e) {
        let valid = true;

        const concern = document.getElementById('consultConcern');
        const service = document.getElementById('consultService');

        clearErr('consultConcernErr');
        clearErr('consultServiceErr');
        setInputErr(concern, false);
        setInputErr(service, false);

        if (!concern.value.trim()) {
            showErr('consultConcernErr', 'Chief complaint is required');
            setInputErr(concern, true);
            valid = false;
        }
        if (!service.value) {
            showErr('consultServiceErr', 'Service type is required');
            setInputErr(service, true);
            valid = false;
        }

        document.querySelectorAll('.med-entry').forEach(entry => {
            const idx = entry.id.split('-')[1];
            const nm  = document.getElementById('consultMedName' + idx);
            const qty = document.getElementById('consultMedQty'  + idx);
            clearErr('medNameErr' + idx);
            clearErr('medQtyErr'  + idx);
            setInputErr(nm,  false);
            setInputErr(qty, false);

            const hasName = nm  && nm.value.trim() !== '';
            const hasQty  = qty && qty.value.trim() !== '' && parseInt(qty.value) >= 1;

            if (hasName && !hasQty) {
                showErr('medQtyErr' + idx, 'Enter a valid quantity');
                setInputErr(qty, true);
                valid = false;
            }
            if (hasQty && !hasName) {
                showErr('medNameErr' + idx, 'Medicine name required');
                setInputErr(nm, true);
                valid = false;
            }
        });

        if (!valid) {
            e.preventDefault();
            showToast('Please fix the errors before saving.', 'error');
            return;
        }

        /* Append visit date */
        const visitDate = document.createElement('input');
        visitDate.type  = 'hidden';
        visitDate.name  = 'visit_date';
        visitDate.value = new Date().toISOString().slice(0, 10);
        this.appendChild(visitDate);
    });

    /* ── Clear Form ── */
    document.getElementById('clearConsultForm')?.addEventListener('click', function () {
        ['consultBP','consultTemp','consultPulse','consultWeight',
         'consultConcern','consultService','consultNotes','consultServiceOther']
            .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });

        document.getElementById('consultStatus').value = 'Waiting';

        ['consultConcernErr','consultServiceErr'].forEach(clearErr);
        ['consultConcern','consultService'].forEach(id =>
            setInputErr(document.getElementById(id), false));

        document.getElementById('otherServiceWrap').style.display = 'none';

        /* Reset PDF upload */
        removePdf();

        /* Reset medicine rows — keep row 0, remove the rest */
        const list = document.getElementById('medsList');
        Array.from(list.querySelectorAll('.med-entry')).forEach((entry, i) => {
            if (i > 0) { entry.remove(); return; }
            ['consultMedName0','consultMedQty0'].forEach(id => {
                const el = document.getElementById(id);
                if (el) { el.value = ''; setInputErr(el, false); }
            });
            clearErr('medNameErr0');
            clearErr('medQtyErr0');
        });
    });

    /* ── PDF Upload ── */
    const MAX_PDF_BYTES = 10 * 1024 * 1024; // 10 MB

    window.handlePdfSelect = function (input) {
        const file = input.files && input.files[0];
        setPdfFile(file);
    };

    function setPdfFile(file) {
        const zone    = document.getElementById('pdfUploadZone');
        const preview = document.getElementById('pdfFilePreview');
        const errMsg  = document.getElementById('pdfErrMsg');
        const errText = document.getElementById('pdfErrText');

        // Reset error
        errMsg.classList.remove('show');
        errText.textContent = '';

        if (!file) return;

        if (file.type !== 'application/pdf') {
            errText.textContent = 'Only PDF files are allowed.';
            errMsg.classList.add('show');
            document.getElementById('consultPdfFile').value = '';
            return;
        }
        if (file.size > MAX_PDF_BYTES) {
            errText.textContent = 'File exceeds the 10 MB limit. Please choose a smaller file.';
            errMsg.classList.add('show');
            document.getElementById('consultPdfFile').value = '';
            return;
        }

        // Show preview
        document.getElementById('pdfFileName').textContent = file.name;
        document.getElementById('pdfFileSize').textContent = formatBytes(file.size);
        preview.classList.add('show');
        zone.classList.add('has-file');
        document.getElementById('pdfUploadLabel').textContent = 'PDF attached — click to replace';
    }

    window.removePdf = function () {
        const zone    = document.getElementById('pdfUploadZone');
        const preview = document.getElementById('pdfFilePreview');
        const errMsg  = document.getElementById('pdfErrMsg');

        document.getElementById('consultPdfFile').value = '';
        preview.classList.remove('show');
        zone.classList.remove('has-file');
        document.getElementById('pdfUploadLabel').textContent = 'Click to upload or drag & drop a PDF';
        errMsg.classList.remove('show');
    };

    function formatBytes(bytes) {
        if (bytes < 1024)        return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
    }

    /* Drag-and-drop support */
    (function () {
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
            const file = e.dataTransfer.files && e.dataTransfer.files[0];
            if (file) {
                // Assign to input so it submits with the form
                const dt = new DataTransfer();
                dt.items.add(file);
                document.getElementById('consultPdfFile').files = dt.files;
                setPdfFile(file);
            }
        });
    })();

    /* Also reset PDF on Clear Form */

    /* ── New Patient shortcut ── */
    document.getElementById('btnNewPatient')?.addEventListener('click', () => {
        window.location.href = '../../modules/patients/add_patient.php';
    });

})();