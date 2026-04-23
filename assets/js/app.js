/* ============================================
   NUCARE Dashboard JavaScript
   ============================================ */

document.addEventListener('DOMContentLoaded', function() {
    const navItems = document.querySelectorAll('.nav-item');
    const panels = document.querySelectorAll('.panel');
    const headerBreadcrumb = document.querySelector('.breadcrumb');
    const headerTitle = document.querySelector('.page-header h2');
    const descriptionText = document.querySelector('.page-description');
    const newPatientButton = document.querySelector('.header-button.accent');

    const panelTitles = {
        dashboardPanel: {
            breadcrumb: 'Home / Dashboard',
            title: 'NUCARE Clinic Portal',
            description: 'Manage patients, records, reports, and clinical workflows from one polished interface.'
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

    navItems.forEach(item => {
        item.addEventListener('click', function() {
            navItems.forEach(nav => nav.classList.remove('active'));
            panels.forEach(panel => panel.classList.remove('active'));

            this.classList.add('active');
            const targetPanel = document.getElementById(this.dataset.panel);
            if (targetPanel) {
                targetPanel.classList.add('active');
                const panelConfig = panelTitles[this.dataset.panel];
                headerBreadcrumb.textContent = panelConfig.breadcrumb;
                headerTitle.textContent = panelConfig.title;
                descriptionText.textContent = panelConfig.description;
            }
        });
    });

    if (newPatientButton) {
        newPatientButton.addEventListener('click', function() {
            document.querySelector('[data-panel="patientsPanel"]').click();
        });
    }
});
