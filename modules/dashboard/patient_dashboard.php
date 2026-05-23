<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['UserID'])) {
    header('Location: ../../auth/login.php');
    exit;
}


$activeSidebarItem = 'dashboard';
$patientName = $_SESSION['patient_name'] ?? 'Student';
$studentId = $_SESSION['SchoolID'] ?? $_SESSION['school_id'] ?? 'N/A';

require_once __DIR__ . '/../../includes/rbac.php';

// If this is a medical professional, redirect immediately.
if (isset($_SESSION['Roles']) && is_array($_SESSION['Roles'])) {
    $roles = $_SESSION['Roles'];
    $landingKey = rbacGetLandingDashboardKey($roles);
    if ($landingKey === 'medical') {
        header('Location: medical_staff_dashboard.php');
        exit;
    }
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE | Student Dashboard</title>
    <link rel="icon" href="/NUcare_Health_system/assets/image/nucarelogo.png">
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
        <header class="page-header">
            <div>
                <p class="breadcrumb">Home / Dashboard</p>
                    <h2>Medical Staff Dashboard</h2>

                <p class="page-description">
                    Welcome back, <?php echo htmlspecialchars($patientName); ?>.
                    Your schedule and medical records are available from the sidebar.
                </p>
            </div>
            <div class="header-actions">
                <a href="../../auth/logout.php" class="header-button outline">Logout</a>
            </div>
        </header>

        <div class="cards-grid">
            <article class="status-card">
                <h3>Staff ID</h3>
                <p class="status-value" style="font-size: 26px;"><?php echo htmlspecialchars((string)$studentId); ?></p>
            </article>


            <article class="status-card">
                <h3>Schedule</h3>
                <p class="status-value" style="font-size: 26px;">Open</p>
            </article>

            <article class="status-card">
                <h3>Medical Records</h3>
                <p class="status-value" style="font-size: 26px;">Available</p>
            </article>

            <article class="status-card">
                <h3>Account Status</h3>
                <p class="status-value" style="font-size: 26px;">Active</p>
            </article>
        </div>

        <div class="content-grid">
            <div class="panel-card">
                <div class="panel-card-header">
                    <h3>Quick Access</h3>
                </div>
                <div class="panel-card-body">
                    <p>Use these shortcuts to move directly to the modules you can access.</p>
                    <div class="action-list">
                        <a href="../schedule/schedule.php" class="action-pill">Schedule / Appointments</a>
                        <a href="../records/records.php" class="action-pill">My Medical Records</a>
                        <a href="../../auth/logout.php" class="action-pill">Logout</a>
                    </div>
                </div>
            </div>

            <div class="panel-card accent-card">
                <div class="panel-card-header">
                    <h3>Student Portal</h3>
                </div>
                <div class="panel-card-body">
                    <p>
                        This portal is focused on the student view only. Dashboard is a landing page,
                        while scheduling and records are the accessible modules from the sidebar.
                    </p>
                </div>
            </div>
        </div>
    </main>

</div>
<script src="../../assets/js/app.js"></script>
</body>
</html>
