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
  .sidebar-medical .footer-title {
    color: #f0f4ff;
  }

  .sidebar-medical .sidebar-brand p,
  .sidebar-medical .footer-title {
    opacity: 0.85;
  }

  .sidebar-medical .brand-mark {
    background: #f0f4ff;
    color: #06285e;
  }

  .sidebar-medical .nav-item {
    background: rgba(255, 255, 255, 0.10);
    color: #f0f4ff;
  }

  .sidebar-medical .nav-item:hover,
  .sidebar-medical .nav-item.active {
    background: rgba(255, 255, 255, 0.20);
    border-color: rgba(255, 255, 255, 0.28);
  }

  .sidebar-medical .nav-dot {
    background: #f0f4ff;
    box-shadow: 0 0 0 6px rgba(240, 244, 255, 0.12);
  }

  .sidebar-medical .status-pill {
    background: #f0f4ff;
    color: #06285e;
  }
</style>

<button class="hamburger-btn" id="hamburgerBtn" type="button" aria-label="Toggle menu"></button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar sidebar-medical" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-mark">MS</div>
    <div>
      <h1>NUCARE</h1>
      <p>Medical Staff Portal</p>
    </div>
  </div>

  <nav class="nav-menu">
    <a class="nav-item <?php echo $activeSidebarItem === 'dashboard' ? 'active' : ''; ?>" href="/NUcare_Health_system/modules/dashboard/medical_staff_dashboard.php">
      <span class="nav-dot"></span>Dashboard
    </a>
    <a class="nav-item <?php echo $activeSidebarItem === 'consultation' ? 'active' : ''; ?>" href="/NUcare_Health_system/modules/consultation/consultation.php">
      <span class="nav-dot"></span>Consultation
    </a>
    <a class="nav-item <?php echo $activeSidebarItem === 'records' ? 'active' : ''; ?>" href="/NUcare_Health_system/modules/records/records.php">
      <span class="nav-dot"></span>Records
    </a>
    <a class="nav-item <?php echo $activeSidebarItem === 'medicine' ? 'active' : ''; ?>" href="/NUcare_Health_system/modules/medicine/medicine.php">
      <span class="nav-dot"></span>Medicine
    </a>
    <a class="nav-item <?php echo $activeSidebarItem === 'schedule' ? 'active' : ''; ?>" href="/NUcare_Health_system/modules/schedule/schedule.php">
      <span class="nav-dot"></span>Schedule
    </a>
    <a class="nav-item" href="/NUcare_Health_system/auth/logout.php">
      <span class="nav-dot"></span>Logout
    </a>
  </nav>

  <div class="sidebar-footer">
    <p class="footer-title">Access</p>
    <div class="status-pill">Staff / Clinical</div>
  </div>
</aside>

