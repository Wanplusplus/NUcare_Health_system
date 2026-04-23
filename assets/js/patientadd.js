document.addEventListener('DOMContentLoaded', function() {
    const newPatientButton = document.getElementById('newPatientButton');
    const patientForm = document.getElementById('patientForm');
    const firstPatientField = document.getElementById('patientFname');
    const clearPatientFormButton = document.getElementById('clearPatientForm');

    function openPatientPanel() {
        if (typeof window.activateDashboardPanel === 'function') {
            window.activateDashboardPanel('patientsPanel');
        } else {
            const patientNavItem = document.querySelector('[data-panel="patientsPanel"]');
            if (patientNavItem) {
                patientNavItem.click();
            }
        }

        if (patientForm) {
            patientForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        if (firstPatientField) {
            window.setTimeout(function() {
                firstPatientField.focus();
            }, 200);
        }
    }

    if (newPatientButton) {
        newPatientButton.addEventListener('click', function() {
            openPatientPanel();
        });
    }

    if (clearPatientFormButton) {
        clearPatientFormButton.addEventListener('click', function() {
            window.setTimeout(function() {
                if (firstPatientField) {
                    firstPatientField.focus();
                }
            }, 0);
        });
    }
});
