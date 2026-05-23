<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/rbac.php';

if (!isset($_SESSION['UserID'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$roles = isset($_SESSION['Roles']) && is_array($_SESSION['Roles']) ? $_SESSION['Roles'] : [];
$landingKey = rbacGetLandingDashboardKey($roles);

// Enforce correct dashboard based on role priority.
if ($landingKey === 'admin') {
    header('Location: admin_dashboard.php');
    exit;
}
if ($landingKey === 'medical') {
    header('Location: medical_staff_dashboard.php');
    exit;
}

// Patient-only content.
$activeSidebarItem = 'dashboard';
$patientName = $_SESSION['patient_name'] ?? 'User';
$schoolPersonId = isset($_SESSION['SchoolPersonID']) ? (int)$_SESSION['SchoolPersonID'] : 0;

$pdo = require __DIR__ . '/../../config/db_pdo.php';

$upcoming = [];
$latestRecord = null;
$notifications = [];

if ($schoolPersonId > 0) {
    // Upcoming appointment (request tracking)
    $stmt = $pdo->prepare(
        "SELECT b.BookingID,
                b.AppointmentDate,
                b.AppointmentStart,
                b.BookingStatus,
                mp.Profession,
                sp.FirstName,
                sp.LastName
         FROM bookings b
         LEFT JOIN medical_professionals mp ON mp.MedProfID = b.MedProfID
         LEFT JOIN school_people sp ON sp.SchoolPersonID = b.MedProfID
         WHERE b.SchoolPersonID = ?
         ORDER BY b.AppointmentDate DESC, b.AppointmentStart DESC
         LIMIT 5"
    );
    $stmt->execute([$schoolPersonId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {
        $status = strtolower((string)($r['BookingStatus'] ?? ''));
        if ($status === 'pending') {
            $upcoming[] = $r;
        } else {
            $upcoming[] = $r;
        }
    }

    // Latest record (clinic_transactions)
    $stmt2 = $pdo->prepare(
        "SELECT ct.ClinicTransactionID,
                ct.VisitDate,
                ct.Complaint,
                ct.ConsultationStatus
         FROM clinic_transactions ct
         WHERE ct.SchoolPersonID = ?
         ORDER BY ct.VisitDate DESC, ct.ClinicTransactionID DESC
         LIMIT 1"
    );
    $stmt2->execute([$schoolPersonId]);
    $latestRecord = $stmt2->fetch(PDO::FETCH_ASSOC);
}

// Simple announcements (static, safe)
$notifications = [
    ['title' => 'Clinic Portal', 'body' => 'Welcome to your clinic portal. Book appointments and track their status.'],
    ['title' => 'Reminder', 'body' => 'For urgent concerns, contact the clinic directly.'],
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE | Clinic Portal</title>
    <link rel="icon" href="/NUcare_Health_system/assets/image/nucarelogo.png">
    <link rel="stylesheet" href="../../assets/css/app.css">
    <style>
        :root{--clinic-yellow:#FACC15;--clinic-yellow2:#EAB308;--clinic-bg:#FEF9C3;--clinic-text:#1f2937;--soft:#f9fafb;}
        body{background:#fff;color:var(--clinic-text);}
        .clinic-shell{max-width:1100px;margin:18px auto;padding:0 16px;}
        .clinic-card{background:#fff;border:1px solid rgba(234,179,8,.25);border-radius:16px;box-shadow:0 10px 25px rgba(250,204,21,.18);padding:16px 16px;margin-bottom:14px;}
        .clinic-hero{display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:12px;}
        .clinic-title{margin:0;font-size:1.4rem;}
        .clinic-sub{margin-top:6px;color:#6b7280;}
        .badge{display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border-radius:999px;background:var(--clinic-bg);border:1px solid rgba(250,204,21,.55);font-weight:800;}
        .grid-2{display:grid;grid-template-columns:1fr;gap:14px;}
        @media (min-width:900px){.grid-2{grid-template-columns:1.05fr .95fr;}}
        .grid-3{display:grid;grid-template-columns:1fr;gap:14px;}
        @media (min-width:900px){.grid-3{grid-template-columns:1fr 1fr 1fr;}}
        .label{color:#6b7280;font-size:.95rem;}
        .row{display:flex;justify-content:space-between;gap:14px;padding:12px 12px;border-radius:12px;background:rgba(250,204,21,.08);border:1px solid rgba(250,204,21,.18);margin-top:10px;}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;border-radius:12px;border:1px solid rgba(234,179,8,.35);background:var(--clinic-yellow);padding:11px 14px;font-weight:900;color:#1f2937;text-decoration:none;cursor:pointer;}
        .btn-outline{background:#fff;color:#111827;}
        .muted{color:#6b7280;}
        .list{margin-top:10px;}
        .list-item{padding:10px 12px;border-radius:12px;background:rgba(250,204,21,.08);border:1px solid rgba(250,204,21,.15);margin-top:8px;}
        .h2{margin:0 0 8px 0;font-size:1.15rem;}
    </style>
</head>
<body>
<div class="app-shell">

    <?php
    // Patient sidebar only
    require_once __DIR__ . '/../../includes/patient_sidebar.php';
    ?>

    <main class="main-content">
        <div class="clinic-shell">
            <div class="clinic-hero clinic-card">
                <div>
                    <h1 class="clinic-title">Clinic Portal</h1>
                    <div class="clinic-sub">
                        Welcome, <b><?php echo htmlspecialchars($patientName); ?></b>
                        <span class="muted">•</span>
                        Role: <b><?php echo htmlspecialchars($roles[0] ?? 'Patient'); ?></b>
                        <span class="muted">•</span>
                        SchoolID: <b><?php echo htmlspecialchars((string)($_SESSION['SchoolID'] ?? $_SESSION['school_id'] ?? 'N/A')); ?></b>
                    </div>
                </div>
                <div class="badge">Appointments & Records</div>
            </div>

            <div class="grid-2">
                <section class="clinic-card">
                    <h2 class="h2">Upcoming Appointment</h2>
                    <?php if (!$upcoming): ?>
                        <div class="muted">No appointment requests yet.</div>
                    <?php else: ?>
                        <?php
                        // Show the first pending/upcoming item as “upcoming”.
                        $display = $upcoming[0];
                        $status = (string)($display['BookingStatus'] ?? '');
                        ?>
                        <div class="row">
                            <div>
                                <div style="font-weight:900;">Provider: <?php echo htmlspecialchars((string)($display['Profession'] ?? 'Clinic')); ?></div>
                                <div class="muted">Date: <?php echo htmlspecialchars((string)($display['AppointmentDate'] ?? '—')); ?></div>
                                <div class="muted">Time: <?php echo htmlspecialchars((string)($display['AppointmentStart'] ?? '—')); ?></div>
                            </div>
                            <div style="text-align:right;">
                                <div class="badge" style="font-weight:900;">Status: <?php echo htmlspecialchars($status); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="list" style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px;">
                        <a class="btn" href="my_schedule.php">Book Appointment</a>
                        <a class="btn btn-outline" href="my_records.php">View Records</a>
                    </div>
                </section>

                <section class="clinic-card">
                    <h2 class="h2">Latest Medical Record</h2>
                    <?php if (!$latestRecord): ?>
                        <div class="muted">No clinic records found.</div>
                    <?php else: ?>
                        <div class="row">
                            <div>
                                <div style="font-weight:900;">Complaint: <?php echo htmlspecialchars((string)($latestRecord['Complaint'] ?? '—')); ?></div>
                                <div class="muted">Visit Date: <?php echo htmlspecialchars((string)($latestRecord['VisitDate'] ?? '—')); ?></div>
                                <div class="muted">Status: <?php echo htmlspecialchars((string)($latestRecord['ConsultationStatus'] ?? '—')); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>

                </section>
            </div>

            <div class="grid-2">
                <section class="clinic-card">
                    <h2 class="h2">Quick Actions</h2>
                    <div style="display:flex;gap:12px;flex-wrap:wrap;">
                        <a class="btn" href="my_schedule.php">My Schedule</a>
                        <a class="btn btn-outline" href="my_records.php">My Records</a>
                        <a class="btn btn-outline" href="profile.php">Profile</a>
                        <a class="btn btn-outline" href="../../auth/logout.php">Logout</a>
                    </div>
                </section>

                <section class="clinic-card">
                    <h2 class="h2">Notifications</h2>
                    <?php foreach ($notifications as $n): ?>
                        <div class="list-item">
                            <div style="font-weight:900;"><?php echo htmlspecialchars((string)$n['title']); ?></div>
                            <div class="muted" style="margin-top:6px;"><?php echo htmlspecialchars((string)$n['body']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </section>
            </div>
        </div>
    </main>
</div>

<script src="../../assets/js/app.js"></script>
</body>
</html>

