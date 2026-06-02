<?php
// ── Inline AJAX: fetch available slots ──
if (isset($_GET['action']) && $_GET['action'] === 'get_slots') {
    session_start();
    if (!isset($_SESSION['UserID'])) { http_response_code(403); echo json_encode([]); exit; }
    $pdo = require __DIR__ . '/../../config/db_pdo.php';
    $medProfId = (int)($_GET['med_prof_id'] ?? 0);
    $date      = $_GET['date'] ?? '';
    $slots = [];
    if ($medProfId && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $stmt = $pdo->prepare(
            "SELECT mpa.AvailabilityID, mpa.StartTime, mpa.EndTime,
                    mpa.SlotDurationMinutes, mpa.AvailabilityStatus, mpa.Notes
             FROM medical_professional_availability mpa
             WHERE mpa.MedProfID = ?
               AND mpa.AvailableDate = ?
               AND mpa.AvailabilityStatus = 'Available'
             ORDER BY mpa.StartTime"
        );
        $stmt->execute([$medProfId, $date]);
        $slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    header('Content-Type: application/json');
    echo json_encode($slots);
    exit;
}

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
require_once __DIR__ . '/../../includes/audit.php';

function getAuditIp(): ?string
{
    return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? null);
}

$activeSidebarItem = 'schedule';

$schoolPersonId = isset($_SESSION['SchoolPersonID']) ? (int)$_SESSION['SchoolPersonID'] : 0;
$pdo = require __DIR__ . '/../../config/db_pdo.php';

$upcoming = [];
$pending  = [];

if ($schoolPersonId > 0) {
    $stmt = $pdo->prepare(
        "SELECT b.BookingID,
                b.AppointmentDate,
                b.AppointmentStart,
                b.AppointmentEnd,
                b.BookingStatus,
                b.ServiceType,
                mp.MedProfID,
                mp.Profession,
                sp.FirstName,
                sp.LastName,
                COALESCE(
                    NULLIF(TRIM(CONCAT(sp.FirstName, ' ', COALESCE(sp.LastName,''))), ''),
                    CONCAT(mp.Profession, ' #', mp.MedProfID)
                ) AS raw_name
         FROM bookings b
         LEFT JOIN medical_professionals mp ON mp.MedProfID = b.MedProfID
         LEFT JOIN users         u  ON u.UserID          = mp.UserID
         LEFT JOIN school_people sp ON sp.SchoolPersonID = u.SchoolPersonID
         WHERE b.SchoolPersonID = ?
         ORDER BY b.AppointmentDate DESC, b.AppointmentStart DESC
         LIMIT 50"
    );
    $stmt->execute([$schoolPersonId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {
        if (strtolower((string)($r['BookingStatus'] ?? '')) === 'pending') {
            $pending[] = $r;
        } else {
            $upcoming[] = $r;
        }
    }
}

$studentName = $_SESSION['patient_name'] ?? 'User';

// Cancel booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking_id'])) {
    $bookingId = (int)$_POST['cancel_booking_id'];
    if ($bookingId > 0) {
        $check = $pdo->prepare("SELECT BookingID, SchoolPersonID, BookingStatus FROM bookings WHERE BookingID = ? LIMIT 1");
        $check->execute([$bookingId]);
        $b = $check->fetch(PDO::FETCH_ASSOC);

        if ($b && (int)$b['SchoolPersonID'] === $schoolPersonId && strtolower((string)$b['BookingStatus']) === 'pending') {
$upd = $pdo->prepare("UPDATE bookings SET BookingStatus = 'Cancelled' WHERE BookingID = ?");
            $upd->execute([$bookingId]);

            $actorUserId = isset($_SESSION['UserID']) ? (int)$_SESSION['UserID'] : null;
            $actorSchoolPersonId = isset($_SESSION['SchoolPersonID']) ? (int)$_SESSION['SchoolPersonID'] : null;
            auditLog(
                $actorUserId,
                $actorSchoolPersonId,
                'Cancelled appointment for ' . ($studentName ?: 'Patient'),
                'Schedule',
                null,
                'Cancelled pending appointment for ' . ($studentName ?: 'Patient'),
                getAuditIp()
            );
        }

        header('Location: my_schedule.php?cancelled=1');
        exit;
    }
}

$studentName = $_SESSION['patient_name'] ?? 'User';

// ── Fetch medical professionals for booking modal ──
$professionals = [];
try {
    $profStmt = $pdo->query(
        "SELECT mp.MedProfID, mp.Profession, mp.Unit,
                sp.FirstName, sp.LastName,
                COALESCE(
                    NULLIF(TRIM(CONCAT(sp.FirstName, ' ', COALESCE(sp.LastName,''))), ''),
                    CONCAT(mp.Profession, ' #', mp.MedProfID)
                ) AS raw_name
         FROM medical_professionals mp
         LEFT JOIN users         u  ON u.UserID          = mp.UserID
         LEFT JOIN school_people sp ON sp.SchoolPersonID = u.SchoolPersonID
         ORDER BY mp.Profession, sp.LastName, sp.FirstName"
    );
    $professionals = $profStmt->fetchAll(PDO::FETCH_ASSOC);
    /* Add proper title prefix */
    foreach ($professionals as &$pr) {
        $prefix = in_array($pr['Profession'] ?? '', ['Doctor', 'Dentist'], true) ? 'Dr. ' : '';
        $pr['DisplayName'] = $prefix . ($pr['raw_name'] ?? ($pr['Profession'] . ' #' . $pr['MedProfID']));
    }
    unset($pr);
} catch (Exception $e) { $professionals = []; }

// ── Handle Book Appointment POST ──
$bookError   = null;
$bookSuccess = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $bMedProf    = (int)($_POST['med_prof_id']      ?? 0);
    $bAvailId    = (int)($_POST['availability_id']  ?? 0);
    $bDate       = trim($_POST['appointment_date']  ?? '');
    $bStart      = trim($_POST['appointment_start'] ?? '');
    $bEnd        = trim($_POST['appointment_end']   ?? '');
    $bService    = trim($_POST['service_type']      ?? '');
    $bReason     = trim($_POST['reason_for_visit']  ?? '');
    $bType       = in_array($_POST['booking_type'] ?? '', ['Appointment','Walk-In']) ? $_POST['booking_type'] : 'Appointment';

    if (!$bMedProf || !$bDate || !$bStart || !$bService) {
        $bookError = 'Please fill in all required fields.';
    } else {
        try {
            $ins = $pdo->prepare(
                "INSERT INTO bookings
                    (SchoolPersonID, MedProfID, AvailabilityID, BookingType,
                     ServiceType, AppointmentDate, AppointmentStart, AppointmentEnd,
                     ReasonForVisit, BookingStatus)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')"
            );
$ins->execute([
                $schoolPersonId, $bMedProf,
                $bAvailId ?: null, $bType,
                $bService, $bDate, $bStart,
                $bEnd ?: null, $bReason ?: null
            ]);

            $actorUserId = isset($_SESSION['UserID']) ? (int)$_SESSION['UserID'] : null;
            $actorSchoolPersonId = isset($_SESSION['SchoolPersonID']) ? (int)$_SESSION['SchoolPersonID'] : null;
            auditLog(
                $actorUserId,
                $actorSchoolPersonId,
                'Booked appointment for ' . ($studentName ?: 'Patient'),
                'Schedule',
                null,
                'Booked appointment for ' . ($studentName ?: 'Patient')
                    . ' (' . $bService . ' • ' . $bDate . ' ' . $bStart . ')',
                getAuditIp()
            );
            // If an availability slot was picked, mark it Unavailable
            if ($bAvailId) {
                $pdo->prepare("UPDATE medical_professional_availability SET AvailabilityStatus='Unavailable' WHERE AvailabilityID=?")
                    ->execute([$bAvailId]);
            }
            header('Location: my_schedule.php?booked=1');
            exit;
        } catch (Exception $e) {
            $bookError = 'Could not save booking. Please try again.';
        }
    }
}

// Compute stats
$totalAppts     = count($upcoming) + count($pending);
$approvedCount  = count(array_filter($upcoming, fn($a) => strtolower($a['BookingStatus'] ?? '') === 'approved'));
$completedCount = count(array_filter($upcoming, fn($a) => strtolower($a['BookingStatus'] ?? '') === 'completed'));
$cancelledCount = count(array_filter($upcoming, fn($a) => strtolower($a['BookingStatus'] ?? '') === 'cancelled'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE | My Schedule</title>
    <link rel="icon" href="/NUcare_Health_system/assets/image/nucarelogo.png">
    <link rel="stylesheet" href="../../assets/css/app.css?v=1">
    <link rel="stylesheet" href="../../assets/css/my_schedule.css?v=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ── Scrollable appointment lists ── */
        .appt-list {
            max-height: 420px;
            overflow-y: auto;
            overflow-x: hidden;
            scroll-behavior: smooth;
        }
        .appt-list::-webkit-scrollbar { width: 6px; }
        .appt-list::-webkit-scrollbar-track { background: transparent; }
        .appt-list::-webkit-scrollbar-thumb { background: var(--gray-300, #d1d5db); border-radius: 3px; }
        .appt-list::-webkit-scrollbar-thumb:hover { background: var(--gray-400, #9ca3af); }

        /* ── Scrollable Step 2 body ── */
        .book-step-body--grid {
            overflow-y: auto !important;
            overflow-x: hidden !important;
            max-height: calc(70vh - 180px);
            scroll-behavior: smooth;
        }
        .book-step-body--grid::-webkit-scrollbar { width: 6px; }
        .book-step-body--grid::-webkit-scrollbar-track { background: transparent; }
        .book-step-body--grid::-webkit-scrollbar-thumb { background: var(--gray-300, #d1d5db); border-radius: 3px; }
        .book-step-body--grid::-webkit-scrollbar-thumb:hover { background: var(--gray-400, #9ca3af); }

        /* ── Past-date cell styling ── */
        .cell-past {
            opacity: 0.45;
            cursor: not-allowed !important;
        }
        .chip-past {
            font-size: 0.6rem;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            line-height: 1;
        }

        /* ── Disabled Prev Week button ── */
        #bkPrevWeek:disabled {
            opacity: 0.35;
            cursor: not-allowed;
            pointer-events: none;
        }
    </style>
</head>
<body>
<div class="app-shell">

    <?php require_once __DIR__ . '/../../includes/patient_sidebar.php'; ?>

    <main class="main-content">
    <div class="my-schedule-page">

        <!-- ══ PAGE HEADER ══ -->
        <div class="schedule-page-header">
            <div class="page-header-left">
                <nav class="breadcrumb">
                    <span>Home</span>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span class="bc-active">My Schedule</span>
                </nav>
                <h1 class="page-title">My Schedule</h1>
                <p class="page-desc">Welcome, <strong><?php echo htmlspecialchars($studentName); ?></strong>. Track and manage your appointment requests below.</p>
            </div>
            <div class="page-header-right">
                <button type="button" class="btn-primary" id="openBookModal">
                    <i class="fa-solid fa-plus"></i> Book Appointment
                </button>
            </div>
        </div>

        <!-- ══ STATS ══ -->
        <div class="schedule-stats">
            <div class="stat-card">
                <div class="stat-icon gold"><i class="fa-solid fa-calendar-days"></i></div>
                <div class="stat-info">
                    <div class="stat-val" id="statTotal"><?php echo $totalAppts; ?></div>
                    <div class="stat-label">Total Bookings</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
                <div class="stat-info">
                    <div class="stat-val" id="statUpcoming"><?php echo $approvedCount + $completedCount; ?></div>
                    <div class="stat-label">Approved / Done</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fa-solid fa-clock"></i></div>
                <div class="stat-info">
                    <div class="stat-val" id="statPending"><?php echo count($pending); ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon red"><i class="fa-solid fa-ban"></i></div>
                <div class="stat-info">
                    <div class="stat-val" id="statCancelled"><?php echo $cancelledCount; ?></div>
                    <div class="stat-label">Cancelled</div>
                </div>
            </div>
        </div>

        <!-- ══ CTA BOOK BANNER ══ -->
        <div class="cta-card">
            <div class="cta-icon">
                <i class="fa-solid fa-calendar-plus"></i>
            </div>
            <div class="cta-text">
                <h3>Need a new appointment?</h3>
                <p>Book a consultation with a doctor, dentist, or nurse at the NUCARE clinic.</p>
            </div>
            <button type="button" class="btn-cta" id="openBookModal2">
                <i class="fa-solid fa-arrow-right"></i> Book Now
            </button>
        </div>

        <!-- ══ MAIN GRID ══ -->
        <div class="schedule-main-grid">

            <!-- Upcoming / Completed -->
            <div class="schedule-card">
                <div class="card-toolbar">
                    <div class="card-section-label">
                        <i class="fa-solid fa-calendar-check"></i>
                        Upcoming &amp; Completed
                    </div>
                    <span class="card-count-badge"><?php echo count($upcoming); ?> records</span>
                </div>
                <div class="appt-list" id="upcomingList">
                    <?php if (!$upcoming): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="fa-solid fa-calendar-xmark"></i></div>
                            <p>No appointments yet.</p>
                            <span>Book your first appointment above.</span>
                        </div>
                    <?php else: ?>
                        <?php foreach ($upcoming as $a):
                            $prefix   = in_array($a['Profession'] ?? '', ['Doctor', 'Dentist'], true) ? 'Dr. ' : '';
                            $profName = $prefix . (trim(($a['raw_name'] ?? '') ?: (($a['FirstName'] ?? '') . ' ' . ($a['LastName'] ?? ''))) ?: ($a['Profession'] ?? 'Medical Professional'));
                            $sc = strtolower($a['BookingStatus'] ?? 'pending');
                            $iconMap = ['approved' => 'fa-circle-check', 'completed' => 'fa-flag-checkered', 'cancelled' => 'fa-ban', 'pending' => 'fa-clock'];
                            $icon = $iconMap[$sc] ?? 'fa-circle';
                            $dt = new DateTime($a['AppointmentDate'] ?? 'now');
                            $startFmt = '';
                            if ($a['AppointmentStart']) {
                                $t = new DateTime($a['AppointmentStart']);
                                $startFmt = $t->format('g:i A');
                            }
                            $endFmt = '';
                            if ($a['AppointmentEnd']) {
                                $t2 = new DateTime($a['AppointmentEnd']);
                                $endFmt = ' – ' . $t2->format('g:i A');
                            }
                        ?>
                        <div class="appt-card status-<?php echo $sc; ?>">
                            <div class="appt-date-block">
                                <div class="appt-date-day"><?php echo $dt->format('d'); ?></div>
                                <div class="appt-date-mon"><?php echo strtoupper($dt->format('M')); ?></div>
                            </div>
                            <div class="appt-info">
                                <div class="appt-doctor">
                                    <i class="fa-solid fa-user-doctor" style="color:var(--gold);font-size:.8rem;margin-right:4px;"></i>
                                    <?php echo htmlspecialchars($profName); ?>
                                </div>
                                <div class="appt-time">
                                    <i class="fa-solid fa-clock"></i>
                                    <?php echo htmlspecialchars($startFmt . $endFmt); ?>
                                </div>
                                <?php if ($a['ServiceType']): ?>
                                <span class="service-tag">
                                    <i class="fa-solid fa-stethoscope"></i>
                                    <?php echo htmlspecialchars($a['ServiceType']); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="appt-right">
                                <span class="status-badge <?php echo $sc; ?>">
                                    <i class="fa-solid <?php echo $icon; ?>"></i>
                                    <?php echo htmlspecialchars($a['BookingStatus'] ?? 'Pending'); ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pending Requests -->
            <div class="schedule-card">
                <div class="card-toolbar">
                    <div class="card-section-label">
                        <i class="fa-solid fa-hourglass-half"></i>
                        Pending Requests
                    </div>
                    <span class="card-count-badge"><?php echo count($pending); ?> pending</span>
                </div>
                <div class="appt-list" id="pendingList">
                    <?php if (!$pending): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="fa-solid fa-calendar-xmark"></i></div>
                            <p>No pending requests.</p>
                            <span>Your pending bookings will appear here.</span>
                        </div>
                    <?php else: ?>
                        <?php foreach ($pending as $p):
                            $prefix   = in_array($p['Profession'] ?? '', ['Doctor', 'Dentist'], true) ? 'Dr. ' : '';
                            $profName = $prefix . (trim(($p['raw_name'] ?? '') ?: (($p['FirstName'] ?? '') . ' ' . ($p['LastName'] ?? ''))) ?: ($p['Profession'] ?? 'Medical Professional'));
                            $dt = new DateTime($p['AppointmentDate'] ?? 'now');
                            $startFmt = '';
                            if ($p['AppointmentStart']) {
                                $t = new DateTime($p['AppointmentStart']);
                                $startFmt = $t->format('g:i A');
                            }
                            $endFmt = '';
                            if ($p['AppointmentEnd']) {
                                $t2 = new DateTime($p['AppointmentEnd']);
                                $endFmt = ' – ' . $t2->format('g:i A');
                            }
                        ?>
                        <div class="appt-card status-pending">
                            <div class="appt-date-block">
                                <div class="appt-date-day"><?php echo $dt->format('d'); ?></div>
                                <div class="appt-date-mon"><?php echo strtoupper($dt->format('M')); ?></div>
                            </div>
                            <div class="appt-info">
                                <div class="appt-doctor">
                                    <i class="fa-solid fa-user-doctor" style="color:var(--gold);font-size:.8rem;margin-right:4px;"></i>
                                    <?php echo htmlspecialchars($profName); ?>
                                </div>
                                <div class="appt-time">
                                    <i class="fa-solid fa-clock"></i>
                                    <?php echo htmlspecialchars($startFmt . $endFmt); ?>
                                </div>
                                <?php if ($p['ServiceType']): ?>
                                <span class="service-tag">
                                    <i class="fa-solid fa-stethoscope"></i>
                                    <?php echo htmlspecialchars($p['ServiceType']); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="appt-right">
                                <span class="status-badge pending">
                                    <i class="fa-solid fa-clock"></i>
                                    Pending
                                </span>
                                <button class="btn-danger js-cancel-btn"
                                    data-id="<?php echo (int)$p['BookingID']; ?>"
                                    data-date="<?php echo htmlspecialchars($dt->format('l, F j, Y')); ?>"
                                    data-time="<?php echo htmlspecialchars($startFmt); ?>"
                                    data-prof="<?php echo htmlspecialchars($profName); ?>"
                                    data-svc="<?php echo htmlspecialchars($p['ServiceType'] ?? ''); ?>">
                                    <i class="fa-solid fa-xmark"></i> Cancel
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <div class="card-footer-note">
                    <i class="fa-solid fa-circle-info"></i>
                    Only pending requests can be cancelled.
                </div>
            </div>

        </div><!-- /.schedule-main-grid -->

    </div><!-- /.my-schedule-page -->


    <!-- ══ BOOK APPOINTMENT MODAL ══ -->
    <div id="bookModal" class="modal-backdrop book-modal-backdrop">
        <div class="modal-box book-modal-box">

            <!-- Header -->
            <div class="modal-header book-modal-header">
                <div class="book-modal-header-left">
                    <div class="book-modal-icon">
                        <i class="fa-solid fa-calendar-plus"></i>
                    </div>
                    <div class="modal-header-info">
                        <div class="modal-title">Book an Appointment</div>
                        <div class="modal-subtitle">
                            <span class="book-step-indicator">
                                Step <span id="bookStepNum">1</span> of 3
                            </span>
                        </div>
                    </div>
                </div>
                <button class="modal-close" id="bookModalClose" title="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Step progress bar -->
            <div class="book-progress-bar">
                <div class="book-progress-track">
                    <div class="book-progress-fill" id="bookProgressFill"></div>
                </div>
                <div class="book-step-labels">
                    <span class="book-step-lbl active" data-step="1">
                        <i class="fa-solid fa-user-doctor"></i> Professional
                    </span>
                    <span class="book-step-lbl" data-step="2">
                        <i class="fa-solid fa-calendar-day"></i> Date &amp; Slot
                    </span>
                    <span class="book-step-lbl" data-step="3">
                        <i class="fa-solid fa-notes-medical"></i> Details
                    </span>
                </div>
            </div>

            <!-- Form -->
            <form id="bookForm" method="post">
                <input type="hidden" name="book_appointment" value="1">
                <input type="hidden" name="med_prof_id"      id="bfMedProfId"    value="">
                <input type="hidden" name="availability_id"  id="bfAvailId"      value="">
                <input type="hidden" name="appointment_date" id="bfDate"         value="">
                <input type="hidden" name="appointment_start" id="bfStart"       value="">
                <input type="hidden" name="appointment_end"  id="bfEnd"          value="">

                <!-- ── STEP 1: Pick Professional ── -->
                <div class="book-step" id="bookStep1">
                    <div class="book-step-body">
                        <p class="book-step-hint">Select the medical professional you'd like to see.</p>

                        <?php if ($bookError): ?>
                        <div class="book-error-banner">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <?php echo htmlspecialchars($bookError); ?>
                        </div>
                        <?php endif; ?>

                        <div class="prof-grid" id="profGrid">
                            <?php if (!$professionals): ?>
                                <div class="book-empty">No professionals available at this time.</div>
                            <?php else: ?>
                                <?php foreach ($professionals as $pr):
                                    $fullName = $pr['DisplayName'] ?? $pr['raw_name'] ?? trim(($pr['FirstName'] ?? '') . ' ' . ($pr['LastName'] ?? '')) ?: 'Unknown';
                                    $profIcon = match(strtolower($pr['Profession'] ?? '')) {
                                        'dentist' => 'fa-tooth',
                                        'nurse'   => 'fa-user-nurse',
                                        default   => 'fa-user-doctor'
                                    };
                                ?>
                                <div class="prof-card js-prof-card"
                                    data-id="<?php echo (int)$pr['MedProfID']; ?>"
                                    data-name="<?php echo htmlspecialchars($fullName); ?>"
                                    data-profession="<?php echo htmlspecialchars($pr['Profession'] ?? ''); ?>"
                                    data-unit="<?php echo htmlspecialchars($pr['Unit'] ?? ''); ?>">
                                    <div class="prof-card-icon">
                                        <i class="fa-solid <?php echo $profIcon; ?>"></i>
                                    </div>
                                    <div class="prof-card-info">
                                        <div class="prof-card-name"><?php echo htmlspecialchars($fullName); ?></div>
                                        <div class="prof-card-role"><?php echo htmlspecialchars($pr['Profession'] ?? ''); ?></div>
                                        <?php if ($pr['Unit']): ?>
                                        <div class="prof-card-unit">
                                            <i class="fa-solid fa-building-columns"></i>
                                            <?php echo htmlspecialchars($pr['Unit']); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="prof-card-check">
                                        <i class="fa-solid fa-circle-check"></i>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="book-step-footer">
                        <span></span>
                        <button type="button" class="btn-primary" id="bookStep1Next" disabled>
                            Next <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- ── STEP 2: Weekly Schedule Grid ── -->
                <div class="book-step" id="bookStep2" style="display:none;">
                    <div class="book-step-body book-step-body--grid">
                        <p class="book-step-hint">Browse the weekly schedule and click an <strong>available</strong> slot to select it.</p>

                        <!-- Week navigator -->
                        <div class="bk-week-nav">
                            <button type="button" class="nav-btn" id="bkPrevWeek" title="Previous Week">
                                <i class="fa-solid fa-chevron-left"></i>
                            </button>
                            <span class="week-label" id="bkWeekLabel">Loading…</span>
                            <button type="button" class="nav-btn" id="bkNextWeek" title="Next Week">
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </div>

                        <!-- Schedule Grid -->
                        <div class="bk-grid-loading" id="bkGridLoading">
                            <i class="fa-solid fa-spinner fa-spin"></i> Loading schedule…
                        </div>
                        <div class="bk-schedule-grid-wrap" id="bkGridWrap" style="display:none;">
                            <table class="schedule-grid bk-schedule-grid">
                                <thead>
                                    <tr>
                                        <th class="time-head">TIME</th>
                                        <th id="bkHSun">SUN</th>
                                        <th id="bkHMon">MON</th>
                                        <th id="bkHTue">TUE</th>
                                        <th id="bkHWed">WED</th>
                                        <th id="bkHThu">THU</th>
                                        <th id="bkHFri">FRI</th>
                                        <th id="bkHSat">SAT</th>
                                    </tr>
                                </thead>
                                <tbody id="bkScheduleBody">
                                    <!-- Populated by JS -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Legend -->
                        <div class="legend-row bk-legend-row">
                            <div class="legend-item"><div class="legend-dot dot-available"></div> Available (click to select)</div>
                            <div class="legend-item"><div class="legend-dot dot-booked"></div> Booked</div>
                            <div class="legend-item"><div class="legend-dot dot-blocked"></div> Blocked</div>
                            <div class="legend-item"><div class="legend-dot dot-today"></div> Today</div>
                        </div>

                        <!-- Selected slot display -->
                        <div id="bkSelectedSlot" class="bk-selected-slot" style="display:none;">
                            <i class="fa-solid fa-circle-check" style="color:var(--green-500)"></i>
                            <strong>Selected:</strong>
                            <span id="bkSelectedSlotText">—</span>
                            <button type="button" class="bk-clear-slot" id="bkClearSlot" title="Clear selection">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <div class="book-field-row" style="margin-top:14px;">
                            <label class="book-label">
                                <i class="fa-solid fa-person-walking-arrow-right"></i> Booking Type
                            </label>
                            <div class="book-radio-group">
                                <label class="book-radio-label">
                                    <input type="radio" name="booking_type" value="Appointment" checked> Appointment
                                </label>
                                <label class="book-radio-label">
                                    <input type="radio" name="booking_type" value="Walk-In"> Walk-In
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="book-step-footer">
                        <button type="button" class="btn-outline" id="bookStep2Back">
                            <i class="fa-solid fa-arrow-left"></i> Back
                        </button>
                        <button type="button" class="btn-primary" id="bookStep2Next" disabled>
                            Next <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- ── STEP 3: Details & Confirm ── -->
                <div class="book-step" id="bookStep3" style="display:none;">
                    <div class="book-step-body">

                        <!-- Summary pill -->
                        <div class="book-summary-pill">
                            <div class="bsp-item">
                                <i class="fa-solid fa-user-doctor"></i>
                                <span id="bsProf">—</span>
                            </div>
                            <div class="bsp-sep"></div>
                            <div class="bsp-item">
                                <i class="fa-solid fa-calendar"></i>
                                <span id="bsDate">—</span>
                            </div>
                            <div class="bsp-sep"></div>
                            <div class="bsp-item">
                                <i class="fa-solid fa-clock"></i>
                                <span id="bsTime">—</span>
                            </div>
                        </div>

                        <div class="book-field-row">
                            <label class="book-label" for="bServiceType">
                                <i class="fa-solid fa-stethoscope"></i> Service Type <span class="req">*</span>
                            </label>
                            <select name="service_type" id="bServiceType" class="book-input book-select">
                                <option value="">— Select service —</option>
                                <option value="General Check-up">General Check-up</option>
                                <option value="Dental Check-up">Dental Check-up</option>
                                <option value="Dental Cleaning">Dental Cleaning</option>
                                <option value="Tooth Extraction">Tooth Extraction</option>
                                <option value="Physical Examination">Physical Examination</option>
                                <option value="Medical Certificate">Medical Certificate</option>
                                <option value="Vaccination">Vaccination</option>
                                <option value="Wound Care">Wound Care</option>
                                <option value="Blood Pressure Check">Blood Pressure Check</option>
                                <option value="Consultation">Consultation</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="book-field-row">
                            <label class="book-label" for="bReason">
                                <i class="fa-solid fa-pen-to-square"></i> Reason for Visit
                                <span style="color:var(--gray-400);font-weight:600;">(optional)</span>
                            </label>
                            <textarea name="reason_for_visit" id="bReason"
                                class="book-input book-textarea"
                                placeholder="Briefly describe your concern or reason for the visit…"
                                rows="3"></textarea>
                        </div>
                    </div>
                    <div class="book-step-footer">
                        <button type="button" class="btn-outline" id="bookStep3Back">
                            <i class="fa-solid fa-arrow-left"></i> Back
                        </button>
                        <button type="submit" class="btn-book-submit" id="bookSubmitBtn">
                            <i class="fa-solid fa-calendar-check"></i> Confirm Booking
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <!-- ══ CANCEL CONFIRM MODAL ══ -->
    <div id="cancelModal" class="modal-backdrop">
        <div class="modal-box">
            <div class="modal-header">
                <div class="modal-header-icon">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <div class="modal-header-info">
                    <div class="modal-title">Cancel Appointment</div>
                    <div class="modal-subtitle">This action cannot be undone.</div>
                </div>
                <button class="modal-close" id="modalCloseBtn" title="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-body">
                <div class="modal-appt-detail">
                    <div class="appt-doctor" id="cancelModalProf">—</div>
                    <div class="appt-time" style="margin-bottom:4px;">
                        <i class="fa-solid fa-calendar" style="color:var(--gold);"></i>
                        <span id="cancelModalDate">—</span>
                    </div>
                    <div class="appt-time">
                        <i class="fa-solid fa-clock" style="color:var(--gold);"></i>
                        <span id="cancelModalTime">—</span>
                    </div>
                    <div class="appt-time" style="margin-top:4px;">
                        <i class="fa-solid fa-stethoscope" style="color:var(--gold);"></i>
                        <span id="cancelModalSvc">—</span>
                    </div>
                </div>
                <div class="modal-warn">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    Are you sure you want to cancel this pending appointment? The slot will be released and you will need to rebook if needed.
                </div>
            </div>

            <div class="modal-footer">
                <form id="cancelForm" method="post" style="display:contents;">
                    <input type="hidden" name="cancel_booking_id" id="cancelBookingIdInput" value="">
                    <button type="button" class="btn-outline" id="modalCancelBtn">Keep Booking</button>
                    <button type="submit" class="btn-danger">
                        <i class="fa-solid fa-xmark"></i> Yes, Cancel It
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div id="myScheduleToast" class="my-schedule-toast"></div>

    </main>
</div><!-- /.app-shell -->

<!-- Inject PHP data for JS -->
<script>
window.__myScheduleData = {
    upcoming: <?php echo json_encode($upcoming, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
    pending:  <?php echo json_encode($pending,  JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
};
window.__professionals = <?php echo json_encode($professionals, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
window.__bookedSuccess = <?php echo json_encode(isset($_GET['booked']) && $_GET['booked'] === '1'); ?>;
</script>

<script src="../../assets/js/app.js"></script>
<script src="../../assets/js/my_schedule.js"></script>
</body>
</html>