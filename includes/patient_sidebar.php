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

  .sidebar-student .nav-settings {
    display: flex;
    flex-direction: column;
    gap: 6px;
  }

  .sidebar-student .nav-settings summary {
    list-style: none;
    cursor: pointer;
  }

  .sidebar-student .nav-settings summary::-webkit-details-marker {
    display: none;
  }

  .sidebar-student .nav-settings summary::after {
    content: '\f078';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    margin-left: auto;
    font-size: .7rem;
    transition: transform .15s ease;
  }

  .sidebar-student .nav-settings[open] summary::after {
    transform: rotate(180deg);
  }

  .sidebar-student .nav-submenu {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding-left: 14px;
  }

  .sidebar-student .nav-subitem {
    font-size: .88rem;
    padding-top: 9px;
    padding-bottom: 9px;
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
    <a class="nav-item <?php echo $activeSidebarItem === 'dashboard' ? 'active' : ''; ?>" href="/NUcare_Health_system/modules/dashboard/patient_dashboard.php">
      <span class="nav-dot"></span>Dashboard
    </a>
    <a class="nav-item <?php echo $activeSidebarItem === 'schedule' ? 'active' : ''; ?>" href="/NUcare_Health_system/modules/dashboard/my_schedule.php">
      <span class="nav-dot"></span>My Schedule
    </a>
    <a class="nav-item <?php echo $activeSidebarItem === 'records' ? 'active' : ''; ?>" href="/NUcare_Health_system/modules/dashboard/my_records.php">
      <span class="nav-dot"></span>My Records
    </a>
    <details class="nav-settings" <?php echo in_array($activeSidebarItem, ['profile', 'settings'], true) ? 'open' : ''; ?>>
      <summary class="nav-item <?php echo in_array($activeSidebarItem, ['profile', 'settings'], true) ? 'active' : ''; ?>">
        <span class="nav-dot"></span>Settings
      </summary>
      <div class="nav-submenu">
        <a class="nav-item nav-subitem <?php echo $activeSidebarItem === 'profile' ? 'active' : ''; ?>" href="/NUcare_Health_system/modules/dashboard/profile.php">
          <span class="nav-dot"></span>My Profile
        </a>
        <a class="nav-item nav-subitem <?php echo $activeSidebarItem === 'settings' ? 'active' : ''; ?>" href="/NUcare_Health_system/modules/dashboard/update_password.php">
          <span class="nav-dot"></span>Update Password
        </a>
        <a class="nav-item nav-subitem" href="/NUcare_Health_system/auth/logout.php">
          <span class="nav-dot"></span>Logout
        </a>
      </div>
    </details>

  </nav>

  <div class="sidebar-footer">
    <p class="footer-title">Student Access</p>
    <div class="status-pill">Enrolled Portal</div>
  </div>
</aside>
