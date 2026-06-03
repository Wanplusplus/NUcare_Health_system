<!-- Admin Sidebar UI (NUCARE) -->
<!-- Required by assets/js/app.js: #hamburgerBtn, #sidebar, #sidebarOverlay -->
<style>
  .sidebar.sidebar-admin {
    background: linear-gradient(180deg, #8b0000 0%, #4b0000 100%);
  }
  .sidebar-admin .brand-mark { background: #ffd24d; color: #8b0000; }
  .sidebar-admin .nav-item { background: rgba(255, 255, 255, 0.08); }
  .sidebar-admin .nav-item:hover,
  .sidebar-admin .nav-item.active {
    background: rgba(255, 255, 255, 0.18);
    border-color: rgba(255, 255, 255, 0.22);
  }
  .sidebar-admin .nav-dot {
    background: #ffd24d;
    box-shadow: 0 0 0 6px rgba(255, 210, 77, 0.16);
  }
  .sidebar-admin .status-pill { background: #5c0000; color: #ffe0e0; }
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
  $activeKey = $activeSidebarItem ?? ($active ?? 'dashboard');

  // RBAC-driven: query role_permissions for 'access' permission.
  // Every sidebar item (except Dashboard) is gated on the underlying module.
  $pdoSide = require __DIR__ . '/../config/db_pdo.php';
  $userIdSide = isset($_SESSION['UserID']) ? (int)$_SESSION['UserID'] : 0;

  $accessibleModules = [];
  if ($userIdSide > 0) {
      $stmtSide = $pdoSide->prepare(
          "SELECT DISTINCT m.ModuleName
           FROM user_roles ur
           INNER JOIN role_permissions rp ON rp.RoleID = ur.RoleID
           INNER JOIN modules m ON m.ModuleID = rp.ModuleID
           INNER JOIN permissions p ON p.PermissionID = rp.PermissionID
           WHERE ur.UserID = ? AND p.PermissionName = 'access'"
      );
      $stmtSide->execute([$userIdSide]);
      foreach ($stmtSide->fetchAll(PDO::FETCH_ASSOC) as $row) {
          $accessibleModules[$row['ModuleName']] = true;
      }
  }

  // Sidebar link config: [label, URL, required_module, activeKey]
  // - 'dashboard' is always shown (no RBAC gate).
  // - 'Admin Panel' module gates User Management, Audit Logs.
  // - 'RBAC Management' module gates RBAC Management (Super Admin only).
  // - 'Reports' module gates Reports.
  $adminNav = [
      ['Dashboard',       '/NUcare_Health_system/modules/dashboard/admin_dashboard.php', null,        'dashboard'],
      ['User Management', '/NUcare_Health_system/admin/user_management.php',             'Admin Panel', 'user_management'],
      ['RBAC Management', '/NUcare_Health_system/admin/rbac_management.php',             'RBAC Management', 'rbac_management'],
      ['Reports',         '/NUcare_Health_system/admin/reports.php',                     'Reports',    'reports'],
      ['Audit Logs',      '/NUcare_Health_system/admin/audit_logs.php',                  'Admin Panel', 'audit_logs'],
  ];
?>

  <nav class="nav-menu">
    <?php foreach ($adminNav as $link): ?>
      <?php
        [$label, $url, $reqMod, $linkKey] = $link;
        $showLink = ($label === 'Dashboard')  // Dashboard always visible
                  || ($reqMod && isset($accessibleModules[$reqMod]));
      ?>
      <?php if ($showLink): ?>
    <a class="nav-item <?= $activeKey === $linkKey ? 'active' : '' ?>" href="<?= $url ?>">
      <span class="nav-dot"></span><?= $label ?>
    </a>
      <?php endif; ?>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-footer">
    <p class="footer-title">System Status</p>
    <div class="status-pill">Operational</div>
  </div>
</aside>