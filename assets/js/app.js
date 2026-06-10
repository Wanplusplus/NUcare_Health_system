/* ============================================
 NUCARE Dashboard JavaScript
 ============================================ */

document.addEventListener('DOMContentLoaded', function() {
 const activePanelFromServer = document.body.dataset.activePanel || 'dashboardPanel';
 const navItems = document.querySelectorAll('.nav-item');
 const panels = document.querySelectorAll('.panel');
 const headerBreadcrumb = document.querySelector('.breadcrumb');
 const headerTitle = document.querySelector('.page-header h2');
 const descriptionText = document.querySelector('.page-description');

 // Disable browser scroll restoration
 if ('scrollRestoration' in window.history) {
 window.history.scrollRestoration = 'manual';
 }

 // Hamburger menu functionality
 const hamburgerBtn = document.getElementById('hamburgerBtn');
 const sidebar = document.getElementById('sidebar');
 const sidebarOverlay = document.getElementById('sidebarOverlay');
 const sidebarCollapseBtn = document.getElementById('sidebarCollapseBtn');
 const sidebarStorageKey = 'nucareMedicalSidebarCollapsed';

 function setSidebarCollapsed(collapsed) {
 if (!sidebar) return;
 document.body.classList.toggle('sidebar-collapsed', collapsed);
 sidebar.classList.toggle('collapsed', collapsed);
 if (sidebarCollapseBtn) {
 sidebarCollapseBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
 sidebarCollapseBtn.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
 sidebarCollapseBtn.innerHTML = collapsed
 ? '<i class="fa-solid fa-angles-right"></i>'
 : '<i class="fa-solid fa-angles-left"></i>';
 }
 try {
 window.localStorage.setItem(sidebarStorageKey, collapsed ? '1' : '0');
 } catch (error) {}
 }

 try {
 if (window.localStorage.getItem(sidebarStorageKey) === '1' && window.matchMedia('(min-width: 961px)').matches) {
 setSidebarCollapsed(true);
 }
 } catch (error) {}

 function toggleSidebar() {
 sidebar.classList.toggle('active');
 sidebarOverlay.classList.toggle('active');
 }

 function closeSidebar() {
 sidebar.classList.remove('active');
 sidebarOverlay.classList.remove('active');
 }

 if (hamburgerBtn) {
 hamburgerBtn.addEventListener('click', toggleSidebar);
 }

 if (sidebarOverlay) {
 sidebarOverlay.addEventListener('click', closeSidebar);
 }

 if (sidebarCollapseBtn) {
 sidebarCollapseBtn.addEventListener('click', function() {
 const isCollapsed = document.body.classList.contains('sidebar-collapsed');
 setSidebarCollapsed(!isCollapsed);
 });
 }

 window.addEventListener('resize', function() {
 if (!window.matchMedia('(min-width: 961px)').matches) {
 document.body.classList.remove('sidebar-collapsed');
 if (sidebar) sidebar.classList.remove('collapsed');
 }
 });

 // Close sidebar when a nav item is clicked (mobile)
 navItems.forEach(item => {
 item.addEventListener('click', function() {
 if (!this.dataset.panel) {
 return;
 }
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

 // Scroll main-content to top
 scrollToTop();
 requestAnimationFrame(scrollToTop);
 setTimeout(scrollToTop, 10);
 };

 if (activePanelFromServer && activePanelFromServer !== 'dashboardPanel') {
 window.activateDashboardPanel(activePanelFromServer);
 }
});

