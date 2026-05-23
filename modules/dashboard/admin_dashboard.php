<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['UserID'])) {
    header('Location: ../../auth/login.php');
    exit;
}


require_once __DIR__ . '/../../includes/module_guard.php';
requireModule('Admin Panel', 'access');

require_once __DIR__ . '/../../config/db.php';

/**
 * Safe count helper
 */
function getCount($conn, $sql) {
    try {
        $result = $conn->query($sql);
        if ($result && $row = $result->fetch_row()) {
            return (int) $row[0];
        }
    } catch (Throwable $e) {
        // Gracefully fall back when a legacy table is missing.
    }
    return 0;
}

$totalPatients = getCount($conn, "SELECT COUNT(*) FROM school_people WHERE PersonType = 'Student'");

$todayVisits = getCount(
    $conn,
    "SELECT COUNT(*) FROM clinic_transactions WHERE DATE(VisitDate) = CURDATE()"
);

$pendingReports = 0;

$todayAppointments = getCount(
    $conn,
    "SELECT COUNT(*) FROM bookings WHERE AppointmentDate = CURDATE() AND BookingStatus = 'Approved'"
);

$activePage = 'dashboard';
$patientName = $_SESSION['patient_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE | Dashboard</title>
    <link rel="icon" href="/NUcare_Health_system/assets/image/nucarelogo.png">
    <link rel="stylesheet" href="../../assets/css/app.css">
</head>
<body>
<div class="app-shell">

    <?php
    $sidebarPath = __DIR__ . '/../../includes/sidebar_admin.php';
    if (file_exists($sidebarPath)) {
        require_once $sidebarPath;
    }
    ?>

    <main class="main-content">
        <header class="page-header">
            <div>
                <p class="breadcrumb">Home / Dashboard</p>
                <h2>Dashboard</h2>
                <p class="page-description">
                    Welcome back, <?php echo htmlspecialchars($patientName); ?>.
                    Here's what's happening today.
                </p>
            </div>
            <div class="header-actions">
                <a href="../../auth/logout.php" class="header-button outline">Logout</a>
            </div>
        </header>

        <div class="cards-grid">
            <article class="status-card">
                <h3>Total Patients</h3>
                <p class="status-value"><?php echo number_format($totalPatients); ?></p>
            </article>

            <article class="status-card">
                <h3>Today's Visits</h3>
                <p class="status-value"><?php echo number_format($todayVisits); ?></p>
            </article>

            <article class="status-card">
                <h3>Pending Reports</h3>
                <p class="status-value"><?php echo number_format($pendingReports); ?></p>
            </article>

            <article class="status-card">
                <h3>Today's Appointments</h3>
                <p class="status-value"><?php echo number_format($todayAppointments); ?></p>
            </article>
        </div>

        <div class="content-grid">
            <div class="panel-card">
                <div class="panel-card-header">
                    <h3>Quick Actions</h3>
                </div>
                <div class="panel-card-body">
                    <p>Jump to any module using the left navigation or the shortcuts below.</p>
                    <div class="action-list">
                        <a href="../patients.php" class="action-pill">Patient intake</a>
                        <a href="../records.php" class="action-pill">Record review</a>
                        <a href="../reports.php" class="action-pill">Report generation</a>
                        <a href="../schedule.php" class="action-pill">Scheduling</a>
                        <a href="../consultation.php" class="action-pill">Consultation</a>
                        <a href="../medicine.php" class="action-pill">Medicine</a>
                    </div>
                </div>
            </div>

            <div class="panel-card accent-card">
                <div class="panel-card-header">
                    <h3>NUCARE Overview</h3>
                </div>
                <div class="panel-card-body">
                    <p>
                        NUCARE brings a clean, professional experience to clinic management.
                        Use the sidebar to navigate between modules. Each module is a
                        dedicated page with its own focused view and backend logic.
                    </p>
                </div>
            </div>
        </div>
    </main>

</div>
<script src="../../assets/js/app.js"></script>
</body>
</html>
