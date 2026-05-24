<?php
/**
 * schedule_ajax.php  —  NUCARE Schedule AJAX Endpoint
 *
 * Actions:
 *   get_professionals  – list all medical professionals
 *   get_slots          – fetch availability + bookings for a professional/week
 *   save_slot          – INSERT or UPDATE medical_professional_availability
 *   get_stats          – booking/open/blocked counts for a week
 *   debug              – DB + session diagnostics (remove in production)
 */

/* ── Prevent ANY stray output before JSON headers ── */
ob_start();

/* ── Allow same-origin AJAX from any page in your project ── */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

/* ── Session (must start before reading $_SESSION) ── */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ════════════════════════════════════════════════════
   ① DB CREDENTIALS  ← EDIT THESE
   ════════════════════════════════════════════════════ */
define('_DB_HOST', 'localhost');
define('_DB_PORT', '3306');
define('_DB_NAME', 'nucaredb');
define('_DB_USER', 'root');   // ← your MySQL username
define('_DB_PASS', '');       // ← your MySQL password (blank if none)

/* ════════════════════════════════════════════════════
   ② AUTH GUARD
   Lists every $_SESSION key your login.php can set.
   If NONE of these are set the request is rejected.

   ▸ WHILE TESTING: set DEV_BYPASS = true to skip the
     auth check entirely. Set it back to false before
     going live.
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

/* Merge GET, POST, and JSON body — JSON body wins on conflict */
$params = array_merge($_GET, $_POST, $jsonBody);
$action = trim($params['action'] ?? '');

/* Discard any stray output from includes/session etc. */
ob_end_clean();

switch ($action) {
    case 'get_professionals': getProfessionals($pdo);        break;
    case 'get_slots':         getSlots($pdo, $params);       break;
    case 'save_slot':         saveSlot($pdo, $params);       break;
    case 'get_stats':         getStats($pdo, $params);       break;
    case 'debug':             debugInfo($pdo);               break;
    default:
        echo json_encode([
            'status'  => 'error',
            'message' => "Unknown action: '{$action}'. Valid: get_professionals, get_slots, save_slot, get_stats, debug",
        ]);
}

/* ════════════════════════════════════════════════════
   get_professionals
   Returns every medical professional with a display
   name built from school_people via users.
   Falls back to "Profession #ID" when join misses.
   ════════════════════════════════════════════════════ */
function getProfessionals(PDO $pdo): void
{
    /*
     * Strategy: try the full join first (medical_professionals → users → school_people).
     * If the users table lacks a SchoolPersonID column (older schema), fall back to a
     * direct join on school_people via a UserID-based sub-select, then finally fall back
     * to returning the professional with a generated display name so the dropdown is
     * never empty when professionals exist in the DB.
     */

    // Attempt 1 — standard 3-table join
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
        // If join fails (e.g. column doesn't exist), fall back to professionals only
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
            'message' => 'No medical professionals found in the database. '
                       . 'Ensure rows exist in medical_professionals and their '
                       . 'UserID values link to school_people via the users table.',
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
   Slot key format : "dayIndex-timeLabel"  e.g. "1-8:00"
   dayIndex        : 0 = Sun … 6 = Sat

   Logic:
   • Sun (0) / Sat (6)              → always disabled
   • Weekday with NO availability row → default Available
   • Row with AvailabilityStatus ≠ 'Available' → disabled
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

    /* 1 — Availability rows for this prof + week */
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

    /* Index: date → "HH:MM" → row */
    $availIndex = [];
    foreach ($availRows as $row) {
        $availIndex[$row['AvailableDate']][substr($row['StartTime'], 0, 5)] = $row;
    }

    /* 2 — Bookings for this prof + week */
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

    /* Index: date → "HH:MM" → booking */
    $bookIndex = [];
    foreach ($bookingRows as $bk) {
        $bookIndex[$bk['AppointmentDate']][substr($bk['AppointmentStart'], 0, 5)] = $bk;
    }

    /* 3 — Build slot map */
    /* Time label (JS) → 24h "HH:MM" (DB) */
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

        /* Weekends — always blocked, no DB check needed */
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

        /* Weekdays */
        foreach ($timeMap as $label => $hhmm) {
            $availRow = $availIndex[$date][$hhmm] ?? null;

            if ($availRow === null) {
                /* No row in DB → default: Available */
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
   INSERT or UPDATE medical_professional_availability.
   Called when the clinician clicks "Save Changes" in
   the slot modal. Stores Available / Unavailable and
   optional notes for the chosen professional + date.
   ════════════════════════════════════════════════════ */
function saveSlot(PDO $pdo, array $p): void
{
    /* ── Parse & validate input ── */
    $profId    = (int)  ($p['professional_id'] ?? 0);
    $ws        =         $p['week_start']       ?? '';
    $dayIdx    = (int)  ($p['day_index']        ?? -1);
    $timeLabel =  trim(  $p['time_label']       ?? '');
    $notes     =  trim(  $p['notes']            ?? '');

    /* filter_var handles both JSON booleans and "true"/"false" strings */
    $disabled = filter_var($p['disabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

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

    /* ── Time maps ── */
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

    /* ── Verify the professional exists ── */
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

    /* ── UPSERT ── */
    try {
        /* Check for an existing row for this prof + date + start time */
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
            /* UPDATE existing row */
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
            /* INSERT new row */
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
        /* Active bookings this week for this prof */
        $stmtBk = $pdo->prepare("
            SELECT COUNT(*) FROM bookings
            WHERE  MedProfID        = :prof
              AND  AppointmentDate  BETWEEN :ws AND :we
              AND  BookingStatus   NOT IN ('Cancelled')
        ");
        $stmtBk->execute([':prof' => $profId, ':ws' => $wsFmt, ':we' => $weFmt]);
        $bookings = (int) $stmtBk->fetchColumn();

        /* Blocked / unavailable slots this week for this prof */
        $stmtBl = $pdo->prepare("
            SELECT COUNT(*) FROM medical_professional_availability
            WHERE  MedProfID          = :prof
              AND  AvailableDate      BETWEEN :ws AND :we
              AND  AvailabilityStatus IN ('Unavailable', 'Cancelled')
        ");
        $stmtBl->execute([':prof' => $profId, ':ws' => $wsFmt, ':we' => $weFmt]);
        $blocked = (int) $stmtBl->fetchColumn();

        $totalWeekdaySlots = 5 * 9;   /* Mon–Fri × 9 time slots */
        $open              = max(0, $totalWeekdaySlots - $bookings - $blocked);
        $professionals     = (int) $pdo->query("SELECT COUNT(*) FROM medical_professionals")->fetchColumn();

        echo json_encode([
            'status'        => 'ok',
            'bookings'      => $bookings,
            'open'          => $open,
            'blocked'       => $blocked,
            'professionals' => $professionals,
        ]);

    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'DB error (get_stats): ' . $e->getMessage()]);
    }
}

/* ════════════════════════════════════════════════════
   debug  ⚠ REMOVE or RESTRICT in production
   Visit: schedule_ajax.php?action=debug
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

        /* Sample availability rows */
        $info['avail_sample'] = $pdo->query(
            "SELECT * FROM medical_professional_availability ORDER BY CreatedAt DESC LIMIT 10"
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