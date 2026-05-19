<?php
$activeSidebarItem = $activeSidebarItem ?? 'dashboard';
?>
<!-- Student Sidebar UI (NUCARE) -->
<!-- Required by assets/js/app.js: #hamburgerBtn, #sidebar, #sidebarOverlay -->
<style>
  .sidebar.sidebar-student {
    background: linear-gradient(180deg, #ffd84d 0%, #f2c300 100%);
    color: #1d1d1d;
  }

  .sidebar-student .sidebar-brand h1,
  .sidebar-student .sidebar-brand p,
  .sidebar-student .footer-title {
    color: #1d1d1d;
  }

  .sidebar-student .sidebar-brand p,
  .sidebar-student .footer-title {
    opacity: 0.78;
  }

  .sidebar-student .brand-mark {
    background: #1d1d1d;
    color: #ffd84d;
  }

  .sidebar-student .nav-item {
    background: rgba(255, 255, 255, 0.38);
    color: #1d1d1d;
    border-color: rgba(29, 29, 29, 0.10);
  }

  .sidebar-student .nav-item:hover,
  .sidebar-student .nav-item.active {
    background: rgba(255, 255, 255, 0.74);
    border-color: rgba(29, 29, 29, 0.18);
  }

  .sidebar-student .nav-dot {
    background: #1d1d1d;
    box-shadow: 0 0 0 6px rgba(29, 29, 29, 0.12);
  }

  .sidebar-student .status-pill {
    background: #1d1d1d;
    color: #ffd84d;
  }
</style>

<button class="hamburger-btn" id="hamburgerBtn" type="button" aria-label="Toggle menu"></button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar sidebar-student" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-mark">NU</div>
    <div>
      <h1>NUCARE</h1>
      <p>Student Portal</p>
    </div>
  </div>

  <nav class="nav-menu">
    <a class="nav-item <?php echo $activeSidebarItem === 'dashboard' ? 'active' : ''; ?>" href="/NUcare_Health_system/modules/dashboard/student_dashboard.php">
      <span class="nav-dot"></span>Dashboard
    </a>
    <a class="nav-item <?php echo $activeSidebarItem === 'schedule' ? 'active' : ''; ?>" href="/NUcare_Health_system/modules/schedule/schedule.php">
      <span class="nav-dot"></span>Schedule / Appointments
    </a>
    <a class="nav-item <?php echo $activeSidebarItem === 'records' ? 'active' : ''; ?>" href="/NUcare_Health_system/modules/records/records.php">
      <span class="nav-dot"></span>My Medical Records
    </a>
    <a class="nav-item" href="/NUcare_Health_system/auth/logout.php">
      <span class="nav-dot"></span>Logout
    </a>
  </nav>

  <div class="sidebar-footer">
    <p class="footer-title">Student Access</p>
    <div class="status-pill">Enrolled Portal</div>
  </div>
</aside>
