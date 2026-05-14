<?php
/**
 * includes/sidebar.php
 *
 * Shared sidebar navigation included by every page.
 * Each page sets $activePage before including this file.
 *
 * Usage:
 *   $activePage = 'dashboard'; // matches keys in $navItems below
 *   require_once __DIR__ . '/includes/sidebar.php';
 */

$navItems = [
    'dashboard'    => ['label' => 'Dashboard',    'href' => 'dashboard.php'],
    'patients'     => ['label' => 'Patients',     'href' => 'patients.php'],
    'consultation' => ['label' => 'Consultation', 'href' => 'consultation.php'],
    'records'      => ['label' => 'Records',      'href' => 'records.php'],
    'reports'      => ['label' => 'Reports',      'href' => 'reports.php'],
    'medicine'     => ['label' => 'Medicine',     'href' => 'medicine.php'],
    'schedule'     => ['label' => 'Schedule',     'href' => 'schedule.php'],
];
?>
<button class="hamburger-btn" id="hamburgerBtn" type="button" aria-label="Toggle menu">
    <span></span><span></span><span></span>
</button>
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
        <?php foreach ($navItems as $key => $item): ?>
            <a class="nav-item <?php echo ($activePage ?? '') === $key ? 'active' : ''; ?>"
               href="<?php echo htmlspecialchars($item['href']); ?>">
                <span class="nav-dot"></span>
                <?php echo htmlspecialchars($item['label']); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <p class="footer-title">System Status</p>
        <div class="status-pill status-good">Operational</div>
    </div>
</aside>
