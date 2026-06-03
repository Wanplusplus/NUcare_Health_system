<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['patient_id']) && !isset($_SESSION['UserID'])) {
    header('Location: ../../auth/login.php');
    exit;
}

require_once __DIR__ . '/../includes/module_guard.php';
requireModule('Admin Panel', 'access');

$activeSidebarItem = 'reports';
$active = 'reports';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE | Reports</title>
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="../assets/css/admin_dashboard_overrides.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* Bootstrap's CSS reset may override some app.css sidebar defaults.
           Ensure the sidebar font and spacing always match the other admin
           modules by explicitly resetting the few affected properties. */
        .sidebar .sidebar-brand h1 {
            font-size: 20px !important;
            margin-bottom: 4px !important;
            font-weight: 700 !important;
        }
        .sidebar .sidebar-brand p {
            font-size: 13px !important;
            margin-bottom: 0 !important;
        }
        .sidebar .sidebar-footer .footer-title {
            font-size: 13px !important;
            margin-bottom: 10px !important;
        }
        .sidebar .sidebar-footer .status-pill {
            padding: 10px 14px !important;
            font-size: 13px !important;
        }
    </style>
</head>
<body>
<div class="app-shell">
    <?php
    $sidebarPath = __DIR__ . '/../includes/sidebar_admin.php';
    if (file_exists($sidebarPath)) {
        require_once $sidebarPath;
    }
    ?>

    <main class="main-content">
        <header class="page-header">
            <div>
                <p class="breadcrumb">Home / Reports</p>
                <h2>Reports</h2>
                <p class="page-description">Generate and view system reports.</p>
            </div>
            <div class="header-actions">
                <a href="../auth/logout.php" class="header-button outline">Logout</a>
            </div>
        </header>

        <section class="panel-card">
            <div class="panel-card-header">
                <h3>Coming soon</h3>
            </div>
            <div class="panel-card-body">
                This page is now scaffolded to display the admin sidebar.
            </div>
        </section>
    </main>
</div>
<script src="../assets/js/app.js"></script>
</body>
</html>
</content>
<task_progress>
- [x] Compare the two sidebars from the user's new screenshots
- [x] Determine the actual reference (Group 2 uses the default app.css, NOT a tightened version)
- [x] Remove my "tightening" overrides from admin_dashboard.php
- [x] Remove my "tightening" overrides from reports.php
- [x] Final PHP lint verification
]<]minimax[>[</task_progress>]<]minimax[>[</invoke>
]<]minimax[>[</tool_call>