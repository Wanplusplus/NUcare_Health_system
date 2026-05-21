<?php
session_start();

if (!isset($_SESSION['patient_id']) && !isset($_SESSION['UserID'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$patientName = $_SESSION['patient_name'] ?? 'User';

require_once __DIR__ . '/../../includes/module_guard.php';
requireModule('Records', 'access');

$activeSidebarItem = 'records';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE | Records</title>
    <link rel="stylesheet" href="../../assets/css/app.css">
</head>
<body>
<div class="app-shell">

    <?php
    $sidebarPath = __DIR__ . '/../../includes/sidebar_medical_staff.php';

    if (file_exists($sidebarPath)) {
        require_once $sidebarPath;
    }
    ?>

    <main class="main-content">
        <!-- Sidebar-only UI requested: intentionally left blank -->
    </main>

</div>
<script src="../../assets/js/app.js"></script>
</body>
</html>
