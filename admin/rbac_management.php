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

$active = 'rbac_management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE | RBAC Management</title>
    <link rel="stylesheet" href="../assets/css/app.css">
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
                <p class="breadcrumb">Home / RBAC Management</p>
                <h2>RBAC Management</h2>
                <p class="page-description">Manage roles and permissions.</p>
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

