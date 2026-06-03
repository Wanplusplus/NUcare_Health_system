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

<aside class="sidebar sidebar-medical" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-mark">MS</div>
    <div>
      <h1>NUCARE</h1>
      <p>Medical Staff Portal</p>
    </div>
  </div>

<?php
// RBAC-driven: query role_permissions for 'access' permission
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

// Module name → URL mapping
$medicalNav = [
    'Consultation' => ['url' => '/NUcare_Health_system/modules/consultation/consultation.php', 'label' => 'Consultation', 'key' => 'consultation'],
    'Records'      => ['url' => '/NUcare_Health_system/modules/records/records.php',          'label' => 'Records',      'key' => 'records'],
    'Medicine'     => ['url' => '/NUcare_Health_system/modules/medicine/medicine.php',         'label' => 'Medicine',     'key' => 'medicine'],
    'Schedule'     => ['url' => '/NUcare_Health_system/modules/schedule/schedule.php',         'label' => 'Schedule',     'key' => 'schedule'],
];
?>

  <nav class="nav-menu">
    <!-- Dashboard: always visible -->
    <a class="nav-item <?php echo $activeSidebarItem === 'dashboard' ? 'active' : ''; ?>" href="/NUcare_Health_system/modules/dashboard/medical_staff_dashboard.php">
      <span class="nav-dot"></span>Dashboard
    </a>
    <?php foreach ($medicalNav as $modName => $info): ?>
      <?php if (isset($accessibleModules[$modName])): ?>
    <a class="nav-item <?php echo $activeSidebarItem === $info['key'] ? 'active' : ''; ?>" href="<?= $info['url'] ?>">
      <span class="nav-dot"></span><?= $info['label'] ?>
    </a>
      <?php endif; ?>
    <?php endforeach; ?>
    <details class="nav-settings" <?php echo in_array($activeSidebarItem, ['my_profile', 'settings'], true) ? 'open' : ''; ?>>
      <summary class="nav-item <?php echo in_array($activeSidebarItem, ['my_profile', 'settings'], true) ? 'active' : ''; ?>">
        <span class="nav-dot"></span>Settings
      </summary>
      <div class="nav-submenu">
        <a class="nav-item nav-subitem <?php echo $activeSidebarItem === 'my_profile' ? 'active' : ''; ?>" href="/NUcare_Health_system/modules/settings/my_profile.php">
          <span class="nav-dot"></span>My Profile
        </a>
        <a class="nav-item nav-subitem <?php echo $activeSidebarItem === 'settings' ? 'active' : ''; ?>" href="/NUcare_Health_system/modules/settings/settings.php">
          <span class="nav-dot"></span>Update Password
        </a>
        <a class="nav-item nav-subitem" href="/NUcare_Health_system/auth/logout.php">
          <span class="nav-dot"></span>Logout
        </a>
      </div>
    </details>
  </nav>

  <div class="sidebar-footer">
    <p class="footer-title">Access</p>
    <div class="status-pill">Staff / Clinical</div>
  </div>
</aside>