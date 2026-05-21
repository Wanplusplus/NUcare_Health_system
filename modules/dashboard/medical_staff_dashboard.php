<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['patient_id']) && !isset($_SESSION['UserID'])) {
    header('Location: ../../auth/login.php');
    exit;
}

// Redirect admins away from medical staff dashboard
if (isset($_SESSION['Roles']) && is_array($_SESSION['Roles']) && array_intersect($_SESSION['Roles'], ['Admin', 'Super Admin']) !== []) {
    header('Location: admin_dashboard.php');
    exit;
}

$activeSidebarItem = 'dashboard';
$patientName = $_SESSION['patient_name'] ?? 'Medical Staff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE | Medical Staff Dashboard</title>
    <link rel="icon" href="/NUcare_Health_system/assets/image/nucarelogo.png">
    <link rel="stylesheet" href="../../assets/css/app.css">
    <link rel="stylesheet" href="../../assets/css/medical_staff_notifications.css?v=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

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
                    Access your clinical modules from the sidebar.
                </p>
            </div>
            <div class="header-actions">
                <div class="notif-bell">
                    <button id="notifBellBtn" type="button" aria-haspopup="true" aria-expanded="false" aria-label="Notifications">
                        <i class="fa-solid fa-bell"></i>
                        <span>Notifications</span>
                        <span class="notif-badge" id="notifBadge" style="display:none;">0</span>
                        <span class="sr-only">Notifications</span>

                    </button>


                    <div id="notifDropdown" class="notif-dropdown" role="menu" aria-label="Notification list">


                        <div class="notif-header">
                            <h4>Real-time Alerts</h4>
                            <div class="notif-lastup" id="notifLastUpdated"></div>
                        </div>

                        <div class="notif-body">
                            <div class="notif-loading" id="notifLoading">Loading…</div>
                            <div class="notif-empty" id="notifEmpty">No alerts right now.</div>
                            <div id="notifList"></div>
                        </div>
                    </div>
                </div>
            </div>

        </header>

        <div class="cards-grid">
            <article class="status-card">
                <h3>Staff Role</h3>
                <p class="status-value" style="font-size: 26px;">Medical</p>
            </article>

            <article class="status-card">
                <h3>Consultation</h3>
                <p class="status-value" style="font-size: 26px;">Available</p>
            </article>

            <article class="status-card">
                <h3>Records</h3>
                <p class="status-value" style="font-size: 26px;">Available</p>
            </article>

            <article class="status-card">
                <h3>Schedule</h3>
                <p class="status-value" style="font-size: 26px;">Open</p>
            </article>
        </div>

        <div class="content-grid">
            <div class="panel-card">
                <div class="panel-card-header">
                    <h3>Quick Access</h3>
                </div>
                <div class="panel-card-body">
                    <p>Shortcuts to the modules typically used by medical staff.</p>
                    <div class="action-list">
                        <a href="../consultation/consultation.php" class="action-pill">Consultation</a>
                        <a href="../records/records.php" class="action-pill">Records</a>
                        <a href="../medicine/medicine.php" class="action-pill">Medicine</a>
                        <a href="../schedule/schedule.php" class="action-pill">Schedule</a>

                    </div>
                </div>
            </div>


            <div class="panel-card accent-card">
                <div class="panel-card-header">
                    <h3>Medical Staff Portal</h3>
                </div>
                <div class="panel-card-body">
                    <p>
                        This portal is intended for medical staff (Doctor/Dentist/Nurse) after admin promotion.
                    </p>
                </div>
            </div>
        </div>

    </main>

</div>
<script src="../../assets/js/app.js"></script>
<script src="../../assets/js/medical_staff_notifications.js?v=1"></script>
</body>
</html>


