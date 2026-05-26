<?php
/**
 * schedule_ajax.php  —  NUCARE Schedule AJAX Endpoint
 *
 * Actions:
 *   get_professionals    – list all medical professionals
 *   get_slots            – fetch availability + bookings for a professional/week
 *   save_slot            – INSERT or UPDATE medical_professional_availability
 *   get_stats            – booking/open/blocked counts for a week
 *   get_pending_bookings – list all Pending bookings for a professional
 *   respond_booking      – Accept or Decline a patient booking request
 *   debug                – DB + session diagnostics (remove in production)
 */

ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ════════════════════════════════════════════════════
   ① DB CREDENTIALS  ← EDIT THESE
   ════════════════════════════════════════════════════ */
define('_DB_HOST', 'localhost');
define('_DB_PORT', '3306');
define('_DB_NAME', 'nucaredb');
define('_DB_USER', 'root');
define('_DB_PASS', '');

/* ════════════════════════════════════════════════════
   ② AUTH GUARD
   ════════════════════════════════════════════════════ */
define('DEV_BYPASS', true);   // ← set false in production

$isLoggedIn = DEV_BYPASS
    || isset($_SESSION['UserID'])
    || isset($_SESSION['user_id'])
    || isset($_SESSION['MedProfID'])
    || isset($_SESSION['patient_id'])
    || isset($_SESSION['SchoolPersonID']);

if (!$isLoggedIn) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Not logged in. Session keys found: ['
                   . implode(', ', array_keys($_SESSION)) . ']. '
                   . 'Set DEV_BYPASS=true in schedule_ajax.php to test without login.',
    ]);
    exit;
}

/* ════════════════════════════════════════════════════
   ③ DB CONNECTION
   ════════════════════════════════════════════════════ */
try {
    $pdo = new PDO(
        'mysql:host=' . _DB_HOST . ';port=' . _DB_PORT
            . ';dbname=' . _DB_NAME . ';charset=utf8mb4',
        _DB_USER,
        _DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    ob_end_clean();
    echo json_encode([
        'status'  => 'error',
        'message' => 'DB connection failed: ' . $e->getMessage()
                   . ' — check _DB_USER / _DB_PASS / _DB_NAME in schedule_ajax.php',
    ]);
    exit;
}

/* ════════════════════════════════════════════════════
   ④ ROUTE
   ════════════════════════════════════════════════════ */
$rawInput = file_get_contents('php://input');
$jsonBody = [];
if ($rawInput !== '' && $rawInput !== false) {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $jsonBody = $decoded;
    }
}

$params = array_merge($_GET, $_POST, $jsonBody);
$action = trim($params['action'] ?? '');

ob_end_clean();

switch ($action) {
    case 'get_professionals':    getProfessionals($pdo);              break;
    case 'get_slots':            getSlots($pdo, $params);             break;
    case 'save_slot':            saveSlot($pdo, $params);             break;
    case 'get_stats':            getStats($pdo, $params);             break;
    case 'get_pending_bookings': getPendingBookings($pdo, $params);   break;
    case 'respond_booking':      respondBooking($pdo, $params);       break;
    case 'debug':                debugInfo($pdo);                     break;
    default:
        echo json_encode([
            'status'  => 'error',
            'message' => "Unknown action: '{$action}'. Valid: get_professionals, get_slots, save_slot, get_stats, get_pending_bookings, respond_booking, debug",
        ]);
}

/* ════════════════════════════════════════════════════
   get_professionals
   ════════════════════════════════════════════════════ */
function getProfessionals(PDO $pdo): void
{
    $sql = "
        SELECT
            mp.MedProfID  AS id,
            mp.Profession AS specialty,
            mp.Unit       AS unit,
            COALESCE(
                NULLIF(TRIM(CONCAT(sp.FirstName, ' ', COALESCE(sp.LastName,''))), ''),
                CONCAT(mp.Profession, ' #', mp.MedProfID)
            ) AS raw_name,
            sp.FirstName  AS first_name,
            sp.LastName   AS last_name
        FROM medical_professionals mp
        LEFT JOIN users         u  ON u.UserID          = mp.UserID
        LEFT JOIN school_people sp ON sp.SchoolPersonID = u.SchoolPersonID
        ORDER BY sp.LastName, sp.FirstName, mp.MedProfID
    ";

    try {
        $rows = $pdo->query($sql)->fetchAll();
    } catch (PDOException $e) {
        try {
            $rows = $pdo->query("
                SELECT
                    MedProfID AS id,
                    Profession AS specialty,
                    Unit AS unit,
                    CONCAT(Profession, ' #', MedProfID) AS raw_name,
                    NULL AS first_name,
                    NULL AS last_name
                FROM medical_professionals
                ORDER BY MedProfID
            ")->fetchAll();
        } catch (PDOException $e2) {
            echo json_encode(['status' => 'error', 'message' => 'DB error (get_professionals): ' . $e2->getMessage()]);
            return;
        }
    }

    if (empty($rows)) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'No medical professionals found in the database.',
        ]);
        return;
    }

    foreach ($rows as &$r) {
        $prefix    = in_array($r['specialty'], ['Doctor', 'Dentist'], true) ? 'Dr. ' : '';
        $r['name'] = $prefix . $r['raw_name'];
        unset($r['raw_name'], $r['first_name'], $r['last_name']);
    }
    unset($r);

    echo json_encode(['status' => 'ok', 'professionals' => array_values($rows)]);
}

/* ════════════════════════════════════════════════════
   get_slots
   ════════════════════════════════════════════════════ */
function getSlots(PDO $pdo, array $p): void
{
    $profId    = (int) ($p['professional_id'] ?? 0);
    $weekStart = trim($p['week_start'] ?? '');

    if (!$profId || !$weekStart || strtotime($weekStart) === false) {
        echo json_encode(['status' => 'error', 'message' => 'Missing or invalid: professional_id, week_start']);
        return;
    }

    $ws      = new DateTime($weekStart);
    $weekEnd = (clone $ws)->modify('+6 days');
    $wsFmt   = $ws->format('Y-m-d');
    $weFmt   = $weekEnd->format('Y-m-d');

    try {
        $stmtA = $pdo->prepare("
            SELECT AvailabilityID, AvailableDate, StartTime, AvailabilityStatus, Notes
            FROM   medical_professional_availability
            WHERE  MedProfID     = :prof
              AND  AvailableDate BETWEEN :ws AND :we
            ORDER  BY AvailableDate, StartTime
        ");
        $stmtA->execute([':prof' => $profId, ':ws' => $wsFmt, ':we' => $weFmt]);
        $availRows = $stmtA->fetchAll();
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'DB error (availability): ' . $e->getMessage()]);
        return;
    }

    $availIndex = [];
    foreach ($availRows as $row) {
        $availIndex[$row['AvailableDate']][substr($row['StartTime'], 0, 5)] = $row;
    }

    try {
        $stmtB = $pdo->prepare("
            SELECT
                b.BookingID,
                b.AppointmentDate,
                b.AppointmentStart,
                b.ServiceType,
                b.ReasonForVisit,
                b.BookingStatus,
                COALESCE(
                    NULLIF(TRIM(CONCAT(sp.FirstName, ' ', sp.LastName)), ''),
                    'Unknown Patient'
                )                           AS patient_name,
                COALESCE(sp.SchoolID,   '') AS school_id,
                COALESCE(sp.PersonType, '') AS person_type,
                COALESCE(
                    (SELECT pr.ProgramName
                     FROM   student_enrollments se
                     JOIN   programs pr ON pr.ProgramID = se.ProgramID
                     WHERE  se.SchoolPersonID = b.SchoolPersonID
                     ORDER  BY se.EnrollmentID DESC LIMIT 1),
                    (SELECT ea.Department
                     FROM   employee_assignments ea
                     WHERE  ea.SchoolPersonID  = b.SchoolPersonID
                       AND  ea.EmploymentStatus = 'Employed'
                     LIMIT 1),
                    sp.PersonType
                )                           AS program_or_dept
            FROM bookings b
            LEFT JOIN school_people sp ON sp.SchoolPersonID = b.SchoolPersonID
            WHERE b.MedProfID        = :prof
              AND b.AppointmentDate  BETWEEN :ws AND :we
              AND b.BookingStatus   NOT IN ('Cancelled')
        ");
        $stmtB->execute([':prof' => $profId, ':ws' => $wsFmt, ':we' => $weFmt]);
        $bookingRows = $stmtB->fetchAll();
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'DB error (bookings): ' . $e->getMessage()]);
        return;
    }

    $bookIndex = [];
    foreach ($bookingRows as $bk) {
        $bookIndex[$bk['AppointmentDate']][substr($bk['AppointmentStart'], 0, 5)] = $bk;
    }

    $timeMap = [
        '8:00'  => '08:00',
        '9:00'  => '09:00',
        '10:00' => '10:00',
        '11:00' => '11:00',
        '1:00'  => '13:00',
        '2:00'  => '14:00',
        '3:00'  => '15:00',
        '4:00'  => '16:00',
        '5:00'  => '17:00',
    ];

    $slots = [];

    for ($di = 0; $di <= 6; $di++) {
        $date = (clone $ws)->modify("+{$di} days")->format('Y-m-d');

        if ($di === 0 || $di === 6) {
            foreach (array_keys($timeMap) as $label) {
                $slots["{$di}-{$label}"] = [
                    'availability_id' => null,
                    'disabled'        => true,
                    'notes'           => 'Weekend — clinic closed',
                    'booking'         => null,
                ];
            }
            continue;
        }

        foreach ($timeMap as $label => $hhmm) {
            $availRow = $availIndex[$date][$hhmm] ?? null;

            if ($availRow === null) {
                $disabled = false;
                $notes    = '';
                $availId  = null;
            } else {
                $disabled = ($availRow['AvailabilityStatus'] !== 'Available');
                $notes    = $availRow['Notes'] ?? '';
                $availId  = (int) $availRow['AvailabilityID'];
            }

            $bkRow   = $bookIndex[$date][$hhmm] ?? null;
            $booking = null;

            if ($bkRow) {
                $booking = [
                    'booking_id' => (int) $bkRow['BookingID'],
                    'patient'    => $bkRow['patient_name'],
                    'id'         => $bkRow['school_id'],
                    'program'    => $bkRow['program_or_dept'] ?: $bkRow['person_type'],
                    'type'       => mapServiceType($bkRow['ServiceType'] ?? ''),
                    'purpose'    => $bkRow['ReasonForVisit'] ?? '',
                    'status'     => $bkRow['BookingStatus'],
                ];
            }

            $slots["{$di}-{$label}"] = [
                'availability_id' => $availId,
                'disabled'        => $disabled,
                'notes'           => $notes,
                'booking'         => $booking,
            ];
        }
    }

    echo json_encode(['status' => 'ok', 'slots' => $slots]);
}

/* ════════════════════════════════════════════════════
   save_slot
   ════════════════════════════════════════════════════ */
function saveSlot(PDO $pdo, array $p): void
{
    $profId    = (int)  ($p['professional_id'] ?? 0);
    $ws        =         $p['week_start']       ?? '';
    $dayIdx    = (int)  ($p['day_index']        ?? -1);
    $timeLabel =  trim(  $p['time_label']       ?? '');
    $notes     =  trim(  $p['notes']            ?? '');
    $disabled  = filter_var($p['disabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

    if (!$profId) {
        echo json_encode(['status' => 'error', 'message' => 'professional_id is required']);
        return;
    }
    if (!$ws || strtotime($ws) === false) {
        echo json_encode(['status' => 'error', 'message' => 'week_start is missing or invalid']);
        return;
    }
    if ($dayIdx < 0 || $dayIdx > 6) {
        echo json_encode(['status' => 'error', 'message' => 'day_index must be 0–6']);
        return;
    }
    if ($dayIdx === 0 || $dayIdx === 6) {
        echo json_encode(['status' => 'error', 'message' => 'Weekend slots cannot be modified']);
        return;
    }
    if ($timeLabel === '') {
        echo json_encode(['status' => 'error', 'message' => 'time_label is required']);
        return;
    }

    $startMap = [
        '8:00'  => '08:00:00', '9:00'  => '09:00:00',
        '10:00' => '10:00:00', '11:00' => '11:00:00',
        '1:00'  => '13:00:00', '2:00'  => '14:00:00',
        '3:00'  => '15:00:00', '4:00'  => '16:00:00',
        '5:00'  => '17:00:00',
    ];
    $endMap = [
        '8:00'  => '09:00:00', '9:00'  => '10:00:00',
        '10:00' => '11:00:00', '11:00' => '12:00:00',
        '1:00'  => '14:00:00', '2:00'  => '15:00:00',
        '3:00'  => '16:00:00', '4:00'  => '17:00:00',
        '5:00'  => '18:00:00',
    ];

    if (!array_key_exists($timeLabel, $startMap)) {
        echo json_encode(['status' => 'error', 'message' => "Unrecognised time_label: '{$timeLabel}'"]);
        return;
    }

    $slotDate  = (new DateTime($ws))->modify("+{$dayIdx} days")->format('Y-m-d');
    $startTime = $startMap[$timeLabel];
    $endTime   = $endMap[$timeLabel];
    $status    = $disabled ? 'Unavailable' : 'Available';

    try {
        $chkProf = $pdo->prepare("SELECT MedProfID FROM medical_professionals WHERE MedProfID = :id LIMIT 1");
        $chkProf->execute([':id' => $profId]);
        if (!$chkProf->fetch()) {
            echo json_encode(['status' => 'error', 'message' => "MedProfID {$profId} not found in medical_professionals"]);
            return;
        }
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'DB error (prof check): ' . $e->getMessage()]);
        return;
    }

    try {
        $check = $pdo->prepare("
            SELECT AvailabilityID
            FROM   medical_professional_availability
            WHERE  MedProfID     = :prof
              AND  AvailableDate = :date
              AND  StartTime     = :start
            LIMIT 1
        ");
        $check->execute([':prof' => $profId, ':date' => $slotDate, ':start' => $startTime]);
        $existing = $check->fetch();

        if ($existing) {
            $upd = $pdo->prepare("
                UPDATE medical_professional_availability
                SET    AvailabilityStatus = :status,
                       Notes             = :notes,
                       EndTime           = :end
                WHERE  AvailabilityID    = :id
            ");
            $upd->execute([
                ':status' => $status,
                ':notes'  => ($notes !== '') ? $notes : null,
                ':end'    => $endTime,
                ':id'     => (int) $existing['AvailabilityID'],
            ]);
            $message = 'Slot updated';
            $availId = (int) $existing['AvailabilityID'];
        } else {
            $ins = $pdo->prepare("
                INSERT INTO medical_professional_availability
                    (MedProfID, AvailableDate, StartTime, EndTime,
                     SlotDurationMinutes, AvailabilityStatus, Notes)
                VALUES
                    (:prof, :date, :start, :end, 60, :status, :notes)
            ");
            $ins->execute([
                ':prof'   => $profId,
                ':date'   => $slotDate,
                ':start'  => $startTime,
                ':end'    => $endTime,
                ':status' => $status,
                ':notes'  => ($notes !== '') ? $notes : null,
            ]);
            $message = 'Slot created';
            $availId = (int) $pdo->lastInsertId();
        }

        echo json_encode([
            'status'          => 'ok',
            'message'         => $message,
            'availability_id' => $availId,
            'slot_date'       => $slotDate,
            'start_time'      => $startTime,
            'avail_status'    => $status,
        ]);

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'DB error (save_slot): ' . $e->getMessage()]);
    }
}

/* ════════════════════════════════════════════════════
   get_stats
   ════════════════════════════════════════════════════ */
function getStats(PDO $pdo, array $p): void
{
    $profId    = (int) ($p['professional_id'] ?? 0);
    $weekStart = trim($p['week_start'] ?? '');

    if (!$profId || !$weekStart) {
        echo json_encode(['status' => 'error', 'message' => 'Missing professional_id or week_start']);
        return;
    }

    $ws      = new DateTime($weekStart);
    $weekEnd = (clone $ws)->modify('+6 days');
    $wsFmt   = $ws->format('Y-m-d');
    $weFmt   = $weekEnd->format('Y-m-d');

    try {
        $stmtBk = $pdo->prepare("
            SELECT COUNT(*) FROM bookings
            WHERE  MedProfID        = :prof
              AND  AppointmentDate  BETWEEN :ws AND :we
              AND  BookingStatus   NOT IN ('Cancelled')
        ");
        $stmtBk->execute([':prof' => $profId, ':ws' => $wsFmt, ':we' => $weFmt]);
        $bookings = (int) $stmtBk->fetchColumn();

        $stmtBl = $pdo->prepare("
            SELECT COUNT(*) FROM medical_professional_availability
            WHERE  MedProfID          = :prof
              AND  AvailableDate      BETWEEN :ws AND :we
              AND  AvailabilityStatus IN ('Unavailable', 'Cancelled')
        ");
        $stmtBl->execute([':prof' => $profId, ':ws' => $wsFmt, ':we' => $weFmt]);
        $blocked = (int) $stmtBl->fetchColumn();

        /* Pending count */
        $stmtPd = $pdo->prepare("
            SELECT COUNT(*) FROM bookings
            WHERE  MedProfID       = :prof
              AND  AppointmentDate BETWEEN :ws AND :we
              AND  BookingStatus   = 'Pending'
        ");
        $stmtPd->execute([':prof' => $profId, ':ws' => $wsFmt, ':we' => $weFmt]);
        $pending = (int) $stmtPd->fetchColumn();

        $totalWeekdaySlots = 5 * 9;
        $open              = max(0, $totalWeekdaySlots - $bookings - $blocked);
        $professionals     = (int) $pdo->query("SELECT COUNT(*) FROM medical_professionals")->fetchColumn();

        echo json_encode([
            'status'        => 'ok',
            'bookings'      => $bookings,
            'open'          => $open,
            'blocked'       => $blocked,
            'pending'       => $pending,
            'professionals' => $professionals,
        ]);

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'DB error (get_stats): ' . $e->getMessage()]);
    }
}

/* ════════════════════════════════════════════════════
   get_pending_bookings
   Returns all Pending bookings for a given professional.
   Used to populate the Booking Requests panel.
   ════════════════════════════════════════════════════ */
function getPendingBookings(PDO $pdo, array $p): void
{
    $profId = (int) ($p['professional_id'] ?? 0);

    if (!$profId) {
        echo json_encode(['status' => 'error', 'message' => 'professional_id is required']);
        return;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT
                b.BookingID,
                b.AppointmentDate,
                b.AppointmentStart,
                b.AppointmentEnd,
                b.ServiceType,
                b.ReasonForVisit,
                b.BookingStatus,
                b.BookingType,
                COALESCE(
                    NULLIF(TRIM(CONCAT(sp.FirstName, ' ', sp.LastName)), ''),
                    'Unknown Patient'
                )                           AS patient_name,
                COALESCE(sp.SchoolID,   '') AS school_id,
                COALESCE(sp.PersonType, '') AS person_type,
                COALESCE(
                    (SELECT pr.ProgramName
                     FROM   student_enrollments se
                     JOIN   programs pr ON pr.ProgramID = se.ProgramID
                     WHERE  se.SchoolPersonID = b.SchoolPersonID
                     ORDER  BY se.EnrollmentID DESC LIMIT 1),
                    (SELECT ea.Department
                     FROM   employee_assignments ea
                     WHERE  ea.SchoolPersonID  = b.SchoolPersonID
                       AND  ea.EmploymentStatus = 'Employed'
                     LIMIT 1),
                    sp.PersonType
                )                           AS program_or_dept
            FROM bookings b
            LEFT JOIN school_people sp ON sp.SchoolPersonID = b.SchoolPersonID
            WHERE b.MedProfID     = :prof
              AND b.BookingStatus = 'Pending'
            ORDER BY b.AppointmentDate ASC, b.AppointmentStart ASC
        ");
        $stmt->execute([':prof' => $profId]);
        $rows = $stmt->fetchAll();

        echo json_encode(['status' => 'ok', 'bookings' => array_values($rows)]);

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'DB error (get_pending_bookings): ' . $e->getMessage()]);
    }
}

/* ════════════════════════════════════════════════════
   respond_booking
   Medical staff accepts or declines a patient booking.

   Expected params:
     booking_id  – int
     response    – 'accept' | 'decline'
     decline_reason (optional) – string, shown to patient

   On Accept  → BookingStatus = 'Approved'
              → availability slot remains Unavailable
   On Decline → BookingStatus = 'Declined'
              → availability slot flipped back to 'Available'
              → DeclineReason stored (requires column — see note)
   ════════════════════════════════════════════════════ */
function respondBooking(PDO $pdo, array $p): void
{
    $bookingId     = (int)  ($p['booking_id']     ?? 0);
    $response      = strtolower(trim($p['response'] ?? ''));
    $declineReason = trim($p['decline_reason'] ?? '');

    if (!$bookingId) {
        echo json_encode(['status' => 'error', 'message' => 'booking_id is required']);
        return;
    }
    if (!in_array($response, ['accept', 'decline'], true)) {
        echo json_encode(['status' => 'error', 'message' => "response must be 'accept' or 'decline'"]);
        return;
    }

    try {
        /* Fetch the booking to verify it exists and is still Pending */
        $fetch = $pdo->prepare("
            SELECT BookingID, BookingStatus, AvailabilityID, MedProfID
            FROM   bookings
            WHERE  BookingID = :id
            LIMIT  1
        ");
        $fetch->execute([':id' => $bookingId]);
        $booking = $fetch->fetch();

        if (!$booking) {
            echo json_encode(['status' => 'error', 'message' => "Booking #{$bookingId} not found"]);
            return;
        }

        if (strtolower($booking['BookingStatus']) !== 'pending') {
            echo json_encode([
                'status'  => 'error',
                'message' => "Booking #{$bookingId} is already '{$booking['BookingStatus']}' and cannot be changed",
            ]);
            return;
        }

        /* ── ACCEPT ── */
        if ($response === 'accept') {
            $upd = $pdo->prepare("
                UPDATE bookings
                SET    BookingStatus = 'Approved'
                WHERE  BookingID     = :id
            ");
            $upd->execute([':id' => $bookingId]);

            echo json_encode([
                'status'     => 'ok',
                'action'     => 'accepted',
                'booking_id' => $bookingId,
                'message'    => "Booking #{$bookingId} has been accepted. The patient will be notified.",
            ]);
            return;
        }

        /* ── DECLINE ── */
        /* BookingStatus ENUM only allows: Pending, Approved, Completed, Cancelled.
           'Declined' is not a valid value — use 'Cancelled' instead. */
        try {
            $upd = $pdo->prepare("
                UPDATE bookings
                SET    BookingStatus  = 'Cancelled',
                       DeclineReason = :reason
                WHERE  BookingID     = :id
            ");
            $upd->execute([':reason' => $declineReason ?: null, ':id' => $bookingId]);
        } catch (PDOException $colErr) {
            /* Column DeclineReason doesn't exist yet — update without it */
            $upd = $pdo->prepare("
                UPDATE bookings
                SET    BookingStatus = 'Cancelled'
                WHERE  BookingID    = :id
            ");
            $upd->execute([':id' => $bookingId]);
        }

        /* Release the availability slot back to Available */
        if (!empty($booking['AvailabilityID'])) {
            try {
                $rel = $pdo->prepare("
                    UPDATE medical_professional_availability
                    SET    AvailabilityStatus = 'Available'
                    WHERE  AvailabilityID     = :avid
                ");
                $rel->execute([':avid' => (int) $booking['AvailabilityID']]);
            } catch (PDOException $e2) {
                /* Non-fatal — log but don't fail the whole request */
                error_log('schedule_ajax respond_booking (release slot): ' . $e2->getMessage());
            }
        }

        echo json_encode([
            'status'     => 'ok',
            'action'     => 'declined',
            'booking_id' => $bookingId,
            'message'    => "Booking #{$bookingId} has been declined. The slot has been released and the patient will be notified.",
        ]);

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'DB error (respond_booking): ' . $e->getMessage()]);
    }
}

/* ════════════════════════════════════════════════════
   debug  ⚠ REMOVE or RESTRICT in production
   ════════════════════════════════════════════════════ */
function debugInfo(PDO $pdo): void
{
    $info = [
        'status'        => 'debug',
        'dev_bypass'    => DEV_BYPASS,
        'session_keys'  => array_keys($_SESSION),
        'php_version'   => PHP_VERSION,
        'db_connected'  => false,
        'db_name'       => _DB_NAME,
        'tables'        => [],
        'prof_count'    => null,
        'prof_rows'     => [],
    ];

    try {
        $pdo->query("SELECT 1");
        $info['db_connected'] = true;
        $info['tables']       = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $info['prof_count']   = (int) $pdo->query("SELECT COUNT(*) FROM medical_professionals")->fetchColumn();
        $info['prof_rows']    = $pdo->query("
            SELECT
                mp.MedProfID, mp.UserID, mp.Profession, mp.Unit,
                u.SchoolPersonID,
                sp.FirstName, sp.LastName
            FROM   medical_professionals mp
            LEFT JOIN users         u  ON u.UserID          = mp.UserID
            LEFT JOIN school_people sp ON sp.SchoolPersonID = u.SchoolPersonID
        ")->fetchAll();

        $info['avail_sample'] = $pdo->query(
            "SELECT * FROM medical_professional_availability ORDER BY CreatedAt DESC LIMIT 10"
        )->fetchAll();

        /* Sample of pending bookings */
        $info['pending_bookings_sample'] = $pdo->query(
            "SELECT BookingID, MedProfID, BookingStatus, AppointmentDate FROM bookings WHERE BookingStatus = 'Pending' LIMIT 10"
        )->fetchAll();

    } catch (PDOException $e) {
        $info['db_error'] = $e->getMessage();
    }

    echo json_encode($info, JSON_PRETTY_PRINT);
}

/* ════════════════════════════════════════════════════
   Helper — map ServiceType string → JS chip type key
   ════════════════════════════════════════════════════ */
function mapServiceType(string $svc): string
{
    $s = strtolower($svc);
    if (str_contains($s, 'dental') || str_contains($s, 'dent') || str_contains($s, 'tooth')) {
        return 'dental';
    }
    if (str_contains($s, 'physical') || str_contains($s, 'exam')) {
        return 'physical';
    }
    return 'general';
}