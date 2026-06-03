<!-- Admin Sidebar UI (NUCARE) -->
<!-- Required by assets/js/app.js: #hamburgerBtn, #sidebar, #sidebarOverlay -->
<style>
  .sidebar.sidebar-admin {
    background: linear-gradient(180deg, #8b0000 0%, #4b0000 100%);
  }

  .sidebar-admin .brand-mark {
    background: #ffd24d;
    color: #8b0000;
  }

  .sidebar-admin .nav-item {
    background: rgba(255, 255, 255, 0.08);
  }

  .sidebar-admin .nav-item:hover,
  .sidebar-admin .nav-item.active {
    background: rgba(255, 255, 255, 0.18);
    border-color: rgba(255, 255, 255, 0.22);
  }

  .sidebar-admin .nav-dot {
    background: #ffd24d;
    box-shadow: 0 0 0 6px rgba(255, 210, 77, 0.16);
  }

  .sidebar-admin .status-pill {
    background: #5c0000;
    color: #ffe0e0;
  }
</style>

<button class="hamburger-btn" id="hamburgerBtn" type="button" aria-label="Toggle menu"></button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar sidebar-admin" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-mark">AD</div>
    <div>
      <h1>NUCARE Admin</h1>
      <p>System Management</p>
    </div>
  </div>


<?php
  // Active state for consistent sidebar UI across admin modules.
  // Prefer $activeSidebarItem, otherwise fall back to legacy $active.
  $activeKey = $activeSidebarItem ?? ($active ?? 'dashboard');
?>

  <nav class="nav-menu">

    <a class="nav-item <?= $activeKey === 'dashboard' ? 'active' : '' ?>" href="/NUcare_Health_system/modules/dashboard/admin_dashboard.php">
      <span class="nav-dot"></span>Dashboard
    </a>
    <a class="nav-item <?= $activeKey === 'user_management' ? 'active' : '' ?>" href="/NUcare_Health_system/admin/user_management.php">
      <span class="nav-dot"></span>User Management
    </a>
    <?php
    // RBAC Management is visible ONLY to Super Admin users.
    $showRbacMenu = false;
    if (isset($_SESSION['Roles']) && is_array($_SESSION['Roles'])) {
        $showRbacMenu = in_array('Super Admin', $_SESSION['Roles'], true);
    }
    ?>
    <?php if ($showRbacMenu): ?>
    <a class="nav-item <?= $activeKey === 'rbac_management' ? 'active' : '' ?>" href="/NUcare_Health_system/admin/rbac_management.php">
      <span class="nav-dot"></span>RBAC Management
    </a>
    <?php endif; ?>
    <a class="nav-item <?= $activeKey === 'reports' ? 'active' : '' ?>" href="/NUcare_Health_system/admin/reports.php">
      <span class="nav-dot"></span>Reports
    </a>
    <a class="nav-item <?= $activeKey === 'audit_logs' ? 'active' : '' ?>" href="/NUcare_Health_system/admin/audit_logs.php">
      <span class="nav-dot"></span>Audit Logs
    </a>




  </nav>

  <div class="sidebar-footer">
    <p class="footer-title">System Status</p>
    <div class="status-pill">Operational</div>
  </div>
</aside>