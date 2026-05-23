<?php
session_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['UserID'])) {
    header('Location: ../../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../includes/module_guard.php';
requireModule('Schedule', 'access');

$activeSidebarItem = 'schedule';

$schoolPersonId = isset($_SESSION['SchoolPersonID']) ? (int)$_SESSION['SchoolPersonID'] : 0;
$pdo = require __DIR__ . '/../../config/db_pdo.php';


$upcoming = [];
$pending = [];
$error = null;

if ($schoolPersonId > 0) {
    $stmt = $pdo->prepare(
        "SELECT b.BookingID,
                b.AppointmentDate,
                b.AppointmentStart,
                b.AppointmentEnd,
                b.BookingStatus,
                b.ServiceType,
                mp.Profession,
                sp.FirstName,
                sp.LastName
         FROM bookings b
         LEFT JOIN medical_professionals mp ON mp.MedProfID = b.MedProfID
         LEFT JOIN school_people sp ON sp.SchoolPersonID = b.SchoolPersonID
         LEFT JOIN users u ON u.UserID = b.SchoolPersonID
         WHERE b.SchoolPersonID = ?
         ORDER BY b.AppointmentDate DESC, b.AppointmentStart DESC
         LIMIT 50"
    );
    $stmt->execute([$schoolPersonId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Slot into cards by status
    foreach ($rows as $r) {
        $item = $r;
        if (isset($r['BookingStatus']) && strtolower((string)$r['BookingStatus']) === 'pending') {
            $pending[] = $item;
        } else {
            $upcoming[] = $item;
        }
    }
}

// Cancel booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking_id'])) {
    $bookingId = (int)$_POST['cancel_booking_id'];
    if ($bookingId > 0) {
        $check = $pdo->prepare("SELECT BookingID, SchoolPersonID, BookingStatus FROM bookings WHERE BookingID = ? LIMIT 1");
        $check->execute([$bookingId]);
        $b = $check->fetch(PDO::FETCH_ASSOC);

        if ($b && (int)$b['SchoolPersonID'] === $schoolPersonId && (string)$b['BookingStatus'] === 'Pending') {
            $upd = $pdo->prepare("UPDATE bookings SET BookingStatus = 'Cancelled' WHERE BookingID = ?");
            $upd->execute([$bookingId]);
        }

        header('Location: my_schedule.php');
        exit;
    }
}

$studentName = $_SESSION['patient_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE | My Schedule</title>
    <link rel="stylesheet" href="../../assets/css/app.css">
    <link rel="stylesheet" href="../../assets/css/consultation.css">
    <style>
        :root{--clinic-yellow:#FACC15;--clinic-yellow2:#EAB308;--clinic-bg:#FEF9C3;--clinic-text:#1f2937;}
        body{background:#fff;color:var(--clinic-text);}
        .clinic-wrap{max-width:1050px;margin:20px auto;padding:0 16px;}
        .clinic-card{background:#fff;border:1px solid rgba(234,179,8,.25);border-radius:16px;box-shadow:0 10px 25px rgba(250,204,21,.18);padding:16px 16px;margin-bottom:14px;}
        .clinic-title{margin:0 0 6px 0;font-size:1.25rem;}
        .badge{display:inline-block;padding:4px 10px;border-radius:999px;background:var(--clinic-bg);border:1px solid rgba(250,204,21,.5);font-weight:600;font-size:.85rem;}
        .grid-2{display:grid;grid-template-columns:1fr;gap:14px;}
        @media (min-width:900px){.grid-2{grid-template-columns:1.1fr .9fr;}}
        .list-row{display:flex;justify-content:space-between;gap:14px;padding:12px 10px;border-radius:12px;background:rgba(250,204,21,.08);border:1px solid rgba(250,204,21,.18);margin-bottom:10px;}
        .muted{color:#6b7280;}
        .btn{display:inline-flex;align-items:center;gap:8px;border-radius:12px;border:1px solid rgba(234,179,8,.35);background:var(--clinic-yellow);padding:10px 14px;font-weight:700;color:#1f2937;text-decoration:none;}
        .btn-outline{background:#fff;color:#111827;}
        .btn-danger{background:#fff;border-color:rgba(239,68,68,.35);color:#b91c1c;}
        form{margin:0;}
        .btn-sm{padding:8px 12px;font-weight:700;}
    </style>
</head>
<body>
<div class="app-shell">
    <?php
    require_once __DIR__ . '/../../includes/patient_sidebar.php';
    ?>

    <main class="main-content">
        <div class="clinic-wrap">
            <div class="clinic-card">
                <h1 class="clinic-title">My Schedule</h1>
                <div class="muted">Welcome, <?php echo htmlspecialchars($studentName); ?>. Track and manage your appointment requests.</div>
            </div>

            <div class="grid-2">
                <div class="clinic-card">
                    <h2 class="clinic-title">Upcoming / Completed</h2>
                    <?php if (!$upcoming): ?>
                        <div class="muted">No appointments yet.</div>
                    <?php else: ?>
                        <?php foreach ($upcoming as $a): ?>
                            <div class="list-row">
                                <div>
                                    <div style="font-weight:800;"><?php echo htmlspecialchars((string)($a['AppointmentDate'] ?? '—')); ?> @ <?php echo htmlspecialchars((string)($a['AppointmentStart'] ?? '—')); ?></div>
                                    <div class="muted">Status: <?php echo htmlspecialchars((string)($a['BookingStatus'] ?? '—')); ?></div>
                                </div>
                                <div style="text-align:right;">
                                    <div class="badge"><?php echo htmlspecialchars((string)($a['Profession'] ?? '')); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="clinic-card">
                    <h2 class="clinic-title">Pending Requests</h2>
                    <?php if (!$pending): ?>
                        <div class="muted">No pending requests.</div>
                    <?php else: ?>
                        <?php foreach ($pending as $p): ?>
                            <div class="list-row">
                                <div>
                                    <div style="font-weight:800;"><?php echo htmlspecialchars((string)($p['AppointmentDate'] ?? '—')); ?> @ <?php echo htmlspecialchars((string)($p['AppointmentStart'] ?? '—')); ?></div>
                                    <div class="muted">Status: <?php echo htmlspecialchars((string)($p['BookingStatus'] ?? '—')); ?></div>
                                </div>
                                <div style="display:flex;flex-direction:column;gap:8px;align-items:flex-end;">
                                    <form method="post" onsubmit="return confirm('Cancel this pending booking?');">
                                        <input type="hidden" name="cancel_booking_id" value="<?php echo (int)$p['BookingID']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Cancel</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <div class="muted" style="margin-top:10px;">You can cancel only pending requests.</div>
                </div>
            </div>
        </div>
    </main>
</div>
<script src="../../assets/js/app.js"></script>
</body>
</html>

