(function () {
    'use strict';

    // Expose only what needs to be global.
    window.searchPatient = function searchPatient() {
        const input = document.getElementById('consultSearchInput');
        const feedback = document.getElementById('searchFeedback');
        if (!input || !feedback) return;

        const query = input.value.trim();
        if (!query) {
            feedback.className = 'search-feedback not-found';
            feedback.innerHTML = '<i class="ti ti-alert-circle"></i> Please enter a patient ID or name.';
            return;
        }

        feedback.className = 'search-feedback';
        feedback.innerHTML = '';

        // Consultation UI expects single patient.
        const url = 'ajax/dashboard.ajax.php?action=searchPatients&q=' + encodeURIComponent(query);

        fetch(url, { method: 'GET', credentials: 'same-origin' })
            .then(async (r) => {
                const data = await r.json();
                if (!data || data.success !== true) {
                    throw new Error((data && data.error) ? data.error : 'Request failed');
                }
                if (data.found && data.patient) {
                    loadPatient(data.patient);
                } else {
                    patientNotFound();
                }
            })
            .catch(() => {
                patientNotFound();
                feedback.className = 'search-feedback not-found';
                feedback.innerHTML = '<i class="ti ti-alert-circle"></i> Patient lookup failed. Please try again.';
            });
    };

    function getTimestamp() {
        const now = new Date();
        return (
            now.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) +
            ' ' +
            now.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' })
        );
    }

    function loadPatient(data) {
        const card = document.getElementById('consultPatientCard');
        if (!card) return;

        document.getElementById('cpcID').textContent = data.patientID || '';
        document.getElementById('cpcSex').textContent = data.patientSex || '';

        const name = [data.patientFname, data.patientMname, data.patientLname]
            .filter(Boolean)
            .join(' ');
        document.getElementById('cpcName').textContent = name;

        document.getElementById('cpcBday').textContent = data.patientBirthday || '';
        document.getElementById('cpcProgram').textContent = data.patientProgram || '';
        document.getElementById('cpcTel').textContent = data.patientPhone || '';
        document.getElementById('cpcTime').textContent = getTimestamp();

        const hidden = document.getElementById('consultPatientID');
        if (hidden) hidden.value = data.patientID || '';

        card.classList.add('visible');

        const area = document.getElementById('consultFormArea');
        const actions = document.getElementById('consultFormActions');
        const overlay = document.getElementById('disabledOverlay');

        if (area) area.classList.remove('disabled');
        if (overlay) overlay.classList.remove('show');
        if (actions) {
            actions.style.opacity = '1';
            actions.style.pointerEvents = 'auto';
        }

        const feedback = document.getElementById('searchFeedback');
        if (feedback) {
            feedback.className = 'search-feedback found';
            feedback.innerHTML = '<i class="ti ti-circle-check"></i> Patient found — consultation form is now active.';
        }
    }

    function patientNotFound() {
        const card = document.getElementById('consultPatientCard');
        if (card) card.classList.remove('visible');

        const hidden = document.getElementById('consultPatientID');
        if (hidden) hidden.value = '';

        const area = document.getElementById('consultFormArea');
        const actions = document.getElementById('consultFormActions');
        const overlay = document.getElementById('disabledOverlay');

        if (area) area.classList.add('disabled');
        if (overlay) overlay.classList.add('show');
        if (actions) {
            actions.style.opacity = '0.4';
            actions.style.pointerEvents = 'none';
        }

        const feedback = document.getElementById('searchFeedback');
        if (feedback) {
            feedback.className = 'search-feedback not-found';
            feedback.innerHTML = '<i class="ti ti-alert-circle"></i> No patient found. Please check the ID or name and try again.';
        }
    }

    // Keep other consultation JS in dashboard.php for now (med add/remove, validations).
    // The request here focuses on connecting consultation search to DB.
})();

