document.addEventListener('DOMContentLoaded', function() {
    const activePanelFromServer = document.body.dataset.activePanel || 'dashboardPanel';
    const navItems = document.querySelectorAll('.nav-item');
    const panels = document.querySelectorAll('.panel');
    const headerBreadcrumb = document.querySelector('.breadcrumb');
    const headerTitle = document.querySelector('.page-header h2');
    const descriptionText = document.querySelector('.page-description');

    if ('scrollRestoration' in window.history) {
        window.history.scrollRestoration = 'manual';
    }

    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    function toggleSidebar() {
        sidebar.classList.toggle('active');
        sidebarOverlay.classList.toggle('active');
    }

    function closeSidebar() {
        sidebar.classList.remove('active');
        sidebarOverlay.classList.remove('active');
    }

    if (hamburgerBtn) {
        hamburgerBtn.innerHTML = '☰';
        hamburgerBtn.addEventListener('click', toggleSidebar);
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
    }

    navItems.forEach(item => {
        item.addEventListener('click', function() {
            window.activateDashboardPanel(this.dataset.panel);
            closeSidebar();
        });
    });

    const panelTitles = {
        dashboardPanel: {
            breadcrumb: 'Home / Dashboard',
            title: 'NUCARE Clinic Portal',
            description: 'Manage patients, records, reports, and clinical workflows from one polished interface.'
        },
        consultationPanel: {
            breadcrumb: 'Home / Consultation',
            title: 'Patient Consultation',
            description: 'Search for a patient, review details, and record consultation data in one clean workspace.'
        },
        patientsPanel: {
            breadcrumb: 'Home / Patients / Add Patient',
            title: 'Patient Intake',
            description: 'Use the patient form to capture intake information aligned with your database schema.'
        },
        recordsPanel: {
            breadcrumb: 'Home / Records',
            title: 'Clinical Records',
            description: 'Review existing records and prepare the data structure for future backend integration.'
        },
        reportsPanel: {
            breadcrumb: 'Home / Reports',
            title: 'Reports & Analytics',
            description: 'Placeholder panel for report summaries, exports, and analytics dashboards.'
        },
        settingsPanel: {
            breadcrumb: 'Home / Settings',
            title: 'System Settings',
            description: 'Manage account preferences and system configuration in a future release.'
        }
    };

    const mainContent = document.querySelector('.main-content');

    function scrollToTop() {
        if (mainContent) {
            mainContent.scrollTop = 0;
        }
        document.documentElement.scrollTop = 0;
        document.body.scrollTop = 0;
        window.scrollTo(0, 0);
    }

    window.activateDashboardPanel = function(panelId) {
        navItems.forEach(nav => nav.classList.remove('active'));
        panels.forEach(panel => panel.classList.remove('active'));

        const selectedNav = document.querySelector(`[data-panel="${panelId}"]`);
        const targetPanel = document.getElementById(panelId);
        const panelConfig = panelTitles[panelId];

        if (selectedNav) {
            selectedNav.classList.add('active');
        }

        if (targetPanel) {
            targetPanel.classList.add('active');
        }

        if (panelConfig) {
            headerBreadcrumb.textContent = panelConfig.breadcrumb;
            headerTitle.textContent = panelConfig.title;
            descriptionText.textContent = panelConfig.description;
        }

        scrollToTop();
        requestAnimationFrame(scrollToTop);
        setTimeout(scrollToTop, 10);
    };

    if (activePanelFromServer && activePanelFromServer !== 'dashboardPanel') {
        window.activateDashboardPanel(activePanelFromServer);
    }
});

(function () {
    'use strict';

    /* ══════════════════════════════════════════════
       SEARCH PATIENT
    ══════════════════════════════════════════════ */
    window.searchPatient = function searchPatient() {
        const input    = document.getElementById('consultSearchInput');
        const feedback = document.getElementById('searchFeedback');
        if (!input || !feedback) return;

        const query = input.value.trim();
        if (!query) {
            setFeedback(feedback, 'not-found', 'Please enter a patient ID or name.');
            return;
        }

        setFeedback(feedback, '', 'Searching...');

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
                setFeedback(feedback, 'not-found', 'Patient lookup failed. Please try again.');
            });
    };

    /* ══════════════════════════════════════════════
       LOAD PATIENT INTO CARD
    ══════════════════════════════════════════════ */
    function loadPatient(data) {
        const card = document.getElementById('consultPatientCard');
        if (!card) return;

        const name = [data.patientFname, data.patientMname, data.patientLname]
            .filter(Boolean).join(' ');

        setText('cpcName',    name);
        setText('cpcID',      data.patientID   || '—');
        setText('cpcSex',     data.patientSex  || '—');
        setText('cpcBday',    data.patientBirthday || '—');
        setText('cpcProgram', data.patientProgram  || '—');
        setText('cpcTel',     data.patientPhone    || '—');
        setText('cpcTime',    getTimestamp());

        const hidden = document.getElementById('consultPatientID');
        if (hidden) hidden.value = data.patientID || '';

        card.classList.add('visible');

        enableConsultForm();

        const feedback = document.getElementById('searchFeedback');
        setFeedback(feedback, 'found', 'Patient found — consultation form is now active.');
    }

    /* ══════════════════════════════════════════════
       NOT FOUND
    ══════════════════════════════════════════════ */
    function patientNotFound() {
        const card = document.getElementById('consultPatientCard');
        if (card) card.classList.remove('visible');

        const hidden = document.getElementById('consultPatientID');
        if (hidden) hidden.value = '';

        disableConsultForm();

        const feedback = document.getElementById('searchFeedback');
        setFeedback(feedback, 'not-found', 'No patient found. Check the ID or name and try again.');
    }

    /* ══════════════════════════════════════════════
       ENABLE / DISABLE FORM
    ══════════════════════════════════════════════ */
    function enableConsultForm() {
        const area    = document.getElementById('consultFormArea');
        const actions = document.getElementById('consultFormActions');
        const overlay = document.getElementById('disabledOverlay');

        if (area)    { area.classList.remove('disabled'); }
        if (overlay) { overlay.classList.remove('show'); }
        if (actions) { actions.style.opacity = '1'; actions.style.pointerEvents = 'auto'; }
    }

    function disableConsultForm() {
        const area    = document.getElementById('consultFormArea');
        const actions = document.getElementById('consultFormActions');
        const overlay = document.getElementById('disabledOverlay');

        if (area)    { area.classList.add('disabled'); }
        if (overlay) { overlay.classList.add('show'); }
        if (actions) { actions.style.opacity = '0.4'; actions.style.pointerEvents = 'none'; }
    }

    /* ══════════════════════════════════════════════
       HELPERS
    ══════════════════════════════════════════════ */
    function setText(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    function setFeedback(el, cls, msg) {
        if (!el) return;
        el.className = 'search-feedback' + (cls ? ' ' + cls : '');
        el.textContent = msg;
    }

    function getTimestamp() {
        const now = new Date();
        return (
            now.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) +
            ' · ' +
            now.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' })
        );
    }

})();