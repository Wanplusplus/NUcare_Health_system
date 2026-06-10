<?php
$activeSidebarItem = $activeSidebarItem ?? 'dashboard';
?>
<!-- Medical Staff Sidebar UI (NUCARE) -->
<!-- Required by assets/js/app.js: #hamburgerBtn, #sidebar, #sidebarOverlay -->
<style>
 .sidebar.sidebar-medical {
 background: linear-gradient(180deg, #0b3d91 0%, #06285e 100%);
 color: #f0f4ff;
 }
 .sidebar-medical .sidebar-brand h1,
 .sidebar-medical .sidebar-brand p,
 .sidebar-medical .footer-title { color: #f0f4ff; }
 .sidebar-medical .sidebar-brand p,
 .sidebar-medical .footer-title { opacity: 0.85; }
 .sidebar-medical .brand-mark { background: #f0f4ff; color: #06285e; }
 .sidebar-medical .nav-item { background: rgba(255, 255, 255, 0.10); color: #f0f4ff; }
 .sidebar-medical .nav-item:hover,
 .sidebar-medical .nav-item.active {
 background: rgba(255, 255, 255, 0.20);
 border-color: rgba(255, 255, 255, 0.28);
 }
 .sidebar-medical .nav-dot {
 background: #f0f4ff;
 box-shadow: 0 0 0 6px rgba(240, 244, 255, 0.12);
 }
 .sidebar-medical .status-pill { background: #f0f4ff; color: #06285e; }
 .sidebar-medical .nav-settings { display: flex; flex-direction: column; gap: 6px; }
 .sidebar-medical .nav-settings summary { list-style: none; cursor: pointer; }
 .sidebar-medical .nav-settings summary::-webkit-details-marker { display: none; }
 .sidebar-medical .nav-settings summary::after {
 content: '\f078'; font-family: 'Font Awesome 6 Free'; font-weight: 900;
 margin-left: auto; font-size: .7rem; transition: transform .15s ease;
 }
 .sidebar-medical .nav-settings[open] summary::after { transform: rotate(180deg); }
 .sidebar-medical .nav-submenu { display: flex; flex-direction: column; gap: 6px; padding-left: 14px; }
 .sidebar-medical .nav-subitem { font-size: .88rem; padding-top: 9px; padding-bottom: 9px; }
</style>

<button class="hamburger-btn" id="hamburgerBtn" type="button" aria-label="Toggle menu"></button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
try {
 if (window.localStorage.getItem('nucareMedicalSidebarCollapsed') === '1' && window.matchMedia('(min-width: 961px)').matches) {
 document.body.classList.add('sidebar-collapsed');
 }
} catch (error) {}
</script>

<aside class="sidebar sidebar-medical" id="sidebar">
 <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" type="button" aria-label="Collapse sidebar" aria-expanded="true">
 <i class="fa-solid fa-angles-left"></i>
 </button>

 <div class="sidebar-brand">
 <div class="brand-mark">MS</div>
 <div>
 <h1>NUCARE</h1>
 <p>Medical Staff Portal</p>
 </div>
 </div>

 <nav class="nav-menu">
 <a class="nav-item <?php echo $activeSidebarItem === 'dashboard' ? 'active' : ''; ?>" href="/NUcare_Health_system/frontend/medical_staff/dashboard/medical_staff_dashboard.php">
 <span class="nav-icon"><i class="fa-solid fa-gauge-high"></i></span>
 Dashboard
 </a>
 <a class="nav-item <?php echo $activeSidebarItem === 'consultation' ? 'active' : ''; ?>" href="/NUcare_Health_system/frontend/medical_staff/consultation/consultation.php">
 <span class="nav-icon"><i class="fa-solid fa-stethoscope"></i></span>
 Consultation
 </a>
 <a class="nav-item <?php echo $activeSidebarItem === 'records' ? 'active' : ''; ?>" href="/NUcare_Health_system/frontend/medical_staff/records/records.php">
 <span class="nav-icon"><i class="fa-solid fa-folder-open"></i></span>
 Records
 </a>
 <a class="nav-item <?php echo $activeSidebarItem === 'medicine' ? 'active' : ''; ?>" href="/NUcare_Health_system/frontend/medical_staff/medicine/medicine.php">
 <span class="nav-icon"><i class="fa-solid fa-pills"></i></span>
 Medicine
 </a>
 <a class="nav-item <?php echo $activeSidebarItem === 'schedule' ? 'active' : ''; ?>" href="/NUcare_Health_system/frontend/medical_staff/schedule/schedule.php">
 <span class="nav-icon"><i class="fa-solid fa-calendar-days"></i></span>
 Schedule
 </a>
 <a class="nav-item <?php echo $activeSidebarItem === 'reports' ? 'active' : ''; ?>" href="/NUcare_Health_system/frontend/medical_staff/reports/reports.php">
 <span class="nav-icon"><i class="fa-solid fa-chart-column"></i></span>
 Reports
 </a>
 <details class="nav-settings" <?php echo in_array($activeSidebarItem, ['my_profile', 'settings', 'simulate_lifecycle_audit'], true) ? 'open' : ''; ?>>
 <summary class="nav-item <?php echo in_array($activeSidebarItem, ['my_profile', 'settings', 'simulate_lifecycle_audit'], true) ? 'active' : ''; ?>">
 <span class="nav-icon"><i class="fa-solid fa-gear"></i></span>
 Settings
 </summary>
 <div class="nav-submenu">
 <a class="nav-item nav-subitem <?php echo $activeSidebarItem === 'my_profile' ? 'active' : ''; ?>" href="/NUcare_Health_system/frontend/medical_staff/settings/my_profile.php">
 <span class="nav-icon"><i class="fa-solid fa-user"></i></span>
 My Profile
 </a>
 <a class="nav-item nav-subitem <?php echo $activeSidebarItem === 'settings' ? 'active' : ''; ?>" href="/NUcare_Health_system/frontend/medical_staff/settings/settings.php">
 <span class="nav-icon"><i class="fa-solid fa-key"></i></span>
 Update Password
 </a>
 <a class="nav-item nav-subitem <?php echo $activeSidebarItem === 'simulate_lifecycle_audit' ? 'active' : ''; ?>" href="/NUcare_Health_system/frontend/medical_staff/tools/simulate_lifecycle_audit.php">
 <span class="nav-icon"><i class="fa-solid fa-flask"></i></span>
 Simulate Term Audit
 </a>
 <a class="nav-item nav-subitem" href="/NUcare_Health_system/frontend/auth/logout.php">
 <span class="nav-icon"><i class="fa-solid fa-right-from-bracket"></i></span>
 Logout
 </a>
 </div>
 </details>
 </nav>

 <div class="sidebar-footer">
 <p class="footer-title">Access</p>
 <div class="status-pill">Staff / Clinical</div>
 </div>
</aside>


