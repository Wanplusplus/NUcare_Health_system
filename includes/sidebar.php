<!-- Shared Sidebar UI (NUCARE) -->
<!-- Required by assets/js/app.js: #hamburgerBtn, #sidebar, #sidebarOverlay -->
<button class="hamburger-btn" id="hamburgerBtn" type="button" aria-label="Toggle menu"></button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-mark">NU</div>
    <div>
      <h1>NUCARE</h1>
      <p>Clinic Management</p>
    </div>
  </div>

  <nav class="nav-menu">
    <!-- These hrefs follow the module structure in this project.
         Modules may override active state independently. -->
    <a class="nav-item" href="/NUcare_Health_system/modules/dashboard/admin_dashboard.php">
      <span class="nav-dot"></span>Dashboard
    </a>
    <a class="nav-item" href="/NUcare_Health_system/modules/consultation/consultation.php">
      <span class="nav-dot"></span>Consultation
    </a>
    <a class="nav-item" href="/NUcare_Health_system/modules/records/records.php">
      <span class="nav-dot"></span>Records
    </a>
    <a class="nav-item" href="/NUcare_Health_system/modules/reports/reports.php">
      <span class="nav-dot"></span>Reports
    </a>
    <a class="nav-item" href="/NUcare_Health_system/modules/medicine/medicine.php">
      <span class="nav-dot"></span>Medicine
    </a>
    <a class="nav-item" href="/NUcare_Health_system/modules/schedule/schedule.php">
      <span class="nav-dot"></span>Schedule
    </a>
  </nav>

  <div class="sidebar-footer">
    <p class="footer-title">System Status</p>
    <div class="status-pill status-good">Operational</div>
  </div>
</aside>
