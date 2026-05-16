<?php
session_start();

if (!isset($_SESSION['patient_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$patientName = $_SESSION['patient_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE | Reports</title>
    <link rel="stylesheet" href="../../assets/css/app.css">
</head>
<body>
<div class="app-shell">

    <?php
    $sidebarPath = __DIR__ . '/../../includes/sidebar.php';
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
