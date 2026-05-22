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
            feedback.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> Please enter a patient School ID.';
            return;
        }

        feedback.className = 'search-feedback';
        feedback.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Searching…';

        fetch('../../ajax/consultation/patient_search.ajax.php?q=' + encodeURIComponent(query))
            .then(r => r.json())
            .then(data => {
                if (!data.found) {
                    patientNotFound();
                    return;
                }

                // load minimal patient info + decide transaction creation mode
                loadPatient(data);
            })
            .catch(() => {
                patientNotFound();
            });
    };

    function loadPatient(data) {
        // UI fields now only require School ID + Sex (name fields removed from PHP)
        document.getElementById('cpcID').textContent      = data.SchoolID;
        document.getElementById('cpcSex').textContent     = data.Sex;
        document.getElementById('cpcTime').textContent    = ts;

        // Map to hidden inputs for backend
        document.getElementById('consultPatientID').value = data.SchoolPersonID;

        document.getElementById('consultPatientCard').classList.add('visible');

        // Load history for table
        loadHistory(data.SchoolPersonID);

        const feedback = document.getElementById('searchFeedback');
        feedback.className = 'search-feedback found';

        // Transaction flow:
        // - If no history => create Transaction #1 automatically, activate form.
        // - If history exists => show modal (Yes => create next transaction).
        fetch('../../ajax/consultation/create_transaction.ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: new URLSearchParams({
                school_person_id: data.SchoolPersonID,
                mode: 'auto'
            })
        })
        .then(r => r.json())
        .then(resp => {
            if (!resp.ok) {
                // History exists: show modal, but keep form disabled until YES
                if (resp.historyCount && resp.historyCount > 0) {
                    feedback.innerHTML = '<i class="fa-solid fa-circle-info"></i> Patient found. Existing history detected.';
                    openTxConfirm();
                    disableForm();
                    return;
                }
                feedback.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> ' + (resp.message || 'Unable to start transaction');
                disableForm();
                return;
            }

            // Success => activate form with created consultation_id
            document.getElementById('consultationID').value = resp.consultation_id;
            feedback.innerHTML = '<i class="fa-solid fa-circle-check"></i> Transaction #' + resp.transaction_number + ' started.';
            enableForm();
        })
        .catch(() => {
            feedback.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Server error while starting transaction.';
            disableForm();
        });
    }

    function patientNotFound() {
        document.getElementById('consultPatientCard').classList.remove('visible');
        document.getElementById('consultPatientID').value = '';
        document.getElementById('consultationID').value = '';

        disableForm();

        const feedback = document.getElementById('searchFeedback');
        feedback.className = 'search-feedback not-found';
        feedback.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> No patient found. Please check the ID and try again.';
    }

    function disableForm() {
        const form    = document.getElementById('consultationForm');
        const actions = document.getElementById('consultFormActions');
        const overlay = document.getElementById('disabledOverlay');
        form.classList.add('disabled');
        overlay.classList.add('show');
        actions.style.opacity      = '0.4';
        actions.style.pointerEvents = 'none';
    }

    function enableForm() {
        const form    = document.getElementById('consultationForm');
        const actions = document.getElementById('consultFormActions');
        const overlay = document.getElementById('disabledOverlay');
        form.classList.remove('disabled');
        overlay.classList.remove('show');
        actions.style.opacity      = '1';
        actions.style.pointerEvents = 'auto';
    }

    function openTxConfirm() {
        const modal = document.getElementById('txConfirmModal');
        if (modal) modal.style.display = 'block';
    }

    window.closeTxConfirm = function () {
        const modal = document.getElementById('txConfirmModal');
        if (modal) modal.style.display = 'none';
    };

    window.confirmAddAnother = function (yes) {
        // YES => create next transaction, set consultationID, enable form
        if (!yes) {
            closeTxConfirm();
            document.getElementById('consultationID').value = '';
            disableForm();
            const feedback = document.getElementById('searchFeedback');
            feedback.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Transaction not added.';
            return;
        }

        // YES
        const spid = parseInt(document.getElementById('consultPatientID').value || '0', 10);
        closeTxConfirm();

        fetch('../../ajax/consultation/create_transaction.ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: new URLSearchParams({
                school_person_id: spid,
                mode: 'next'
            })
        })
        .then(r => r.json())
        .then(resp => {
            if (!resp.ok) {
                disableForm();
                const feedback = document.getElementById('searchFeedback');
                feedback.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> ' + (resp.message || 'Could not create transaction');
                return;
            }

            document.getElementById('consultationID').value = resp.consultation_id;
            enableForm();

            const feedback = document.getElementById('searchFeedback');
            feedback.innerHTML = '<i class="fa-solid fa-circle-check"></i> Transaction #' + resp.transaction_number + ' started.';
        })
        .catch(() => {
            disableForm();
            const feedback = document.getElementById('searchFeedback');
            feedback.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Server error creating transaction.';
        });
    };

    function loadHistory(spid) {
        const tbody = document.getElementById('consultHistoryTbody');
        if (!tbody) return;

        tbody.innerHTML = '<tr><td colspan="4" class="muted">Loading consultation history…</td></tr>';

        fetch('../../ajax/consultation/list_transactions.ajax.php?school_person_id=' + encodeURIComponent(spid))
            .then(r => r.json())
            .then(resp => {
                if (!resp.ok || !Array.isArray(resp.transactions) || resp.transactions.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="muted">No consultation history yet.</td></tr>';
                    return;
                }

                tbody.innerHTML = '';
                resp.transactions.forEach(tx => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${tx.SchoolID ?? ''}</td>
                        <td>${tx.Sex ?? ''}</td>
                        <td>${tx.TransactionNumber ?? ''}</td>
                        <td>${tx.CreatedAt ?? ''}</td>
                    `;
                    tbody.appendChild(tr);
                });
            })
            .catch(() => {
                tbody.innerHTML = '<tr><td colspan="4" class="muted">Failed to load history.</td></tr>';
            });
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
        document.getElementById('consultationID').value = '';

        ['consultConcernErr','consultServiceErr'].forEach(clearErr);
        ['consultConcern','consultService'].forEach(id =>
            setInputErr(document.getElementById(id), false));

        document.getElementById('otherServiceWrap').style.display = 'none';

        /* Reset attachment upload */
        removeAttachment();

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

    /* ── Attachment Upload (JPG/PNG/PDF) ── */
    const MAX_ATTACHMENT_BYTES = 50 * 1024 * 1024; // 50 MB

    window.handleAttachmentSelect = function (input) {
        const file = input.files && input.files[0];
        setAttachmentFile(file);
    };

    function setAttachmentFile(file) {
        const zone    = document.getElementById('pdfUploadZone');
        const preview = document.getElementById('pdfFilePreview');
        const errMsg  = document.getElementById('pdfErrMsg');
        const errText = document.getElementById('pdfErrText');

        // Reset error
        errMsg.classList.remove('show');
        errText.textContent = '';

        if (!file) return;

        const allowed = ['image/jpeg','image/png','application/pdf'];
        if (!allowed.includes(file.type)) {
            errText.textContent = 'Allowed files: JPG, PNG, PDF';
            errMsg.classList.add('show');
            document.getElementById('consultAttachmentFile').value = '';
            return;
        }
        if (file.size > MAX_ATTACHMENT_BYTES) {
            errText.textContent = 'Attachment exceeds the 50 MB limit. Please choose a smaller file.';
            errMsg.classList.add('show');
            document.getElementById('consultAttachmentFile').value = '';
            return;
        }

        // Show preview
        document.getElementById('pdfFileName').textContent = file.name;
        document.getElementById('pdfFileSize').textContent = formatBytes(file.size);
        preview.classList.add('show');
        zone.classList.add('has-file');
        document.getElementById('pdfUploadLabel').textContent = 'File attached — click to replace';
    }

    window.removeAttachment = function () {
        const zone    = document.getElementById('pdfUploadZone');
        const preview = document.getElementById('pdfFilePreview');
        const errMsg  = document.getElementById('pdfErrMsg');

        document.getElementById('consultAttachmentFile').value = '';
        preview.classList.remove('show');
        zone.classList.remove('has-file');
        document.getElementById('pdfUploadLabel').textContent = 'Click to upload or drag & drop';
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
                document.getElementById('consultAttachmentFile').files = dt.files;
                setAttachmentFile(file);
            }
        });
    })();

    /* Also reset PDF on Clear Form */

    /* ── New Patient shortcut ── */
    document.getElementById('btnNewPatient')?.addEventListener('click', () => {
        window.location.href = '../../modules/patients/add_patient.php';
    });

})();