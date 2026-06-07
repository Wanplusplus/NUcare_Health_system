<?php
/**
 * schedule_ajax.php - NUCARE Schedule AJAX Endpoint
 *
 * Actions:
 * get_professionals - list all medical professionals
 * get_slots - fetch availability + bookings for a professional/week
 * save_slot - INSERT or UPDATE medical_professional_availability
 * get_stats - booking/open/blocked counts for a week
 * get_pending_bookings - list all Pending bookings for a professional
 * respond_booking - Accept or Decline a patient booking request
 */

require_once __DIR__ . '/../../backend/includes/audit.php';


ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (session_status() === PHP_SESSION_NONE) {
 session_start();
}

/* ---
 DB CREDENTIALS <- EDIT THESE
 --- */
define('_DB_HOST', 'localhost');
define('_DB_PORT', '3306');
define('_DB_NAME', 'nucaredb');
define('_DB_USER', 'root');
define('_DB_PASS', '');

/* ---
 AUTH GUARD
 --- */
define('DEV_BYPASS', true); // <- set false in production

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
 'status' => 'error',
 'message' => 'Not logged in. Session keys found: ['
 . implode(', ', array_keys($_SESSION)) . ']. '
 . 'Set DEV_BYPASS=true in schedule_ajax.php to test without login.',
 ]);
 exit;
}

/* ---
 DB CONNECTION
 --- */
try {
 $pdo = new PDO(
 'mysql:host=' . _DB_HOST . ';port=' . _DB_PORT
 . ';dbname=' . _DB_NAME . ';charset=utf8mb4',
 _DB_USER,
 _DB_PASS,
 [
 PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
 PDO::ATTR_EMULATE_PREPARES => false,
 ]
 );
} catch (PDOException $e) {
 ob_end_clean();
 echo json_encode([
 'status' => 'error',
 'message' => 'DB connection failed: ' . $e->getMessage()
 . ' - check _DB_USER / _DB_PASS / _DB_NAME in schedule_ajax.php',
 ]);
 exit;
}

/* ---
 ROUTE
 --- */
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
 case 'get_professionals': getProfessionals($pdo); break;
 case 'get_slots': getSlots($pdo, $params); break;
 case 'save_slot': saveSlot($pdo, $params); break;
 case 'get_stats': getStats($pdo, $params); break;
 case 'get_pending_bookings': getPendingBookings($pdo, $params); break;
 case 'respond_booking': respondBooking($pdo, $params); break;
 case 'patient_respond_reschedule': patientRespondReschedule($pdo, $params); break;
 default:
 echo json_encode([
 'status' => 'error',
 'message' => "Unknown action: '{$action}'.",
 ]);
}

/* ---
 get_professionals
 --- */
function getProfessionals(PDO $pdo): void
{
 $sql = "
 SELECT
 mp.MedProfID AS id,
 mp.Profession AS specialty,
 mp.Unit AS unit,
 COALESCE(
 NULLIF(TRIM(CONCAT(sp.FirstName, ' ', COALESCE(sp.LastName,''))), ''),
 CONCAT(mp.Profession, ' #', mp.MedProfID)
 ) AS raw_name,
 sp.FirstName AS first_name,
 sp.LastName AS last_name
 FROM medical_professionals mp
 LEFT JOIN users u ON u.UserID = mp.UserID
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
 'status' => 'error',
 'message' => 'No medical professionals found in the database.',
 ]);
 return;
 }

 foreach ($rows as &$r) {
 $prefix = in_array($r['specialty'], ['Doctor', 'Dentist'], true) ? 'Dr. ' : '';
 $r['name'] = $prefix . $r['raw_name'];
 unset($r['raw_name'], $r['first_name'], $r['last_name']);
 }
 unset($r);

 echo json_encode(['status' => 'ok', 'professionals' => array_values($rows)]);
}

/* ---
 get_slots
 --- */
function getSlots(PDO $pdo, array $p): void
{
 $profId = (int) ($p['professional_id'] ?? 0);
 $weekStart = trim($p['week_start'] ?? '');

 if (!$profId || !$weekStart || strtotime($weekStart) === false) {
 echo json_encode(['status' => 'error', 'message' => 'Missing or invalid: professional_id, week_start']);
 return;
 }

 $ws = new DateTime($weekStart);
 $weekEnd = (clone $ws)->modify('+6 days');
 $wsFmt = $ws->format('Y-m-d');
 $weFmt = $weekEnd->format('Y-m-d');

 try {
 $stmtA = $pdo->prepare("
 SELECT AvailabilityID, AvailableDate, StartTime, AvailabilityStatus, Notes
 FROM medical_professional_availability
 WHERE MedProfID = :prof
 AND AvailableDate BETWEEN :ws AND :we
 ORDER BY AvailableDate, StartTime
 ");
 $stmtA->execute([':prof' => $profId, ':ws' => $wsFmt, ':we' => $weFmt]);
 $availRows = $stmtA->fetchAll();
 } catch (PDOException $e) {
 echo json_encode(['status' => 'error', 'message' => 'DB error (availability): ' . $e->getMessage()]);
 return;
 }

 // Identify the current patient so we can flag their own bookings
 $currentSchoolPersonId = isset($_SESSION['SchoolPersonID']) ? (int)$_SESSION['SchoolPersonID'] : 0;

 $availIndex = [];
 foreach ($availRows as $row) {
 $availIndex[$row['AvailableDate']][substr($row['StartTime'], 0, 5)] = $row;
 }

 try {
 $stmtB = $pdo->prepare("
 SELECT
 b.BookingID,
 b.SchoolPersonID,
 b.AppointmentDate,
 b.AppointmentStart,
 b.ServiceType,
 b.ReasonForVisit,
 b.BookingStatus,
 b.RescheduleStatus,
 b.RescheduleProposedDate,
 b.RescheduleProposedStart,
 COALESCE(
 NULLIF(TRIM(CONCAT(sp.FirstName, ' ', sp.LastName)), ''),
 'Unknown Patient'
 ) AS patient_name,
 COALESCE(sp.SchoolID, '') AS school_id,
 COALESCE(sp.PersonType, '') AS person_type,
 COALESCE(
 (SELECT pr.ProgramName
 FROM student_enrollments se
 JOIN programs pr ON pr.ProgramID = se.ProgramID
 WHERE se.SchoolPersonID = b.SchoolPersonID
 ORDER BY se.EnrollmentID DESC LIMIT 1),
 (SELECT ea.Department
 FROM employee_assignments ea
 WHERE ea.SchoolPersonID = b.SchoolPersonID
 AND ea.EmploymentStatus = 'Employed'
 LIMIT 1),
 sp.PersonType
 ) AS program_or_dept
 FROM bookings b
 LEFT JOIN school_people sp ON sp.SchoolPersonID = b.SchoolPersonID
 WHERE b.MedProfID = :prof
 AND b.AppointmentDate BETWEEN :ws AND :we
 AND b.BookingStatus NOT IN ('Cancelled')
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
 '8:00' => '08:00',
 '8:30' => '08:30',
 '9:00' => '09:00',
 '9:30' => '09:30',
 '10:00' => '10:00',
 '10:30' => '10:30',
 '11:00' => '11:00',
 '11:30' => '11:30',
 '1:00' => '13:00',
 '1:30' => '13:30',
 '2:00' => '14:00',
 '2:30' => '14:30',
 '3:00' => '15:00',
 '3:30' => '15:30',
 '4:00' => '16:00',
 '4:30' => '16:30',
 '5:00' => '17:00',
 '5:30' => '17:30',
 ];

 $slots = [];

 for ($di = 0; $di <= 6; $di++) {
 $date = (clone $ws)->modify("+{$di} days")->format('Y-m-d');

 if ($di === 0 || $di === 6) {
 foreach (array_keys($timeMap) as $label) {
 $slots["{$di}-{$label}"] = [
 'availability_id' => null,
 'disabled' => true,
 'notes' => 'Weekend - clinic closed',
 'booking' => null,
 ];
 }
 continue;
 }

 foreach ($timeMap as $label => $hhmm) {
 $availRow = $availIndex[$date][$hhmm] ?? null;

 if ($availRow === null) {
 $disabled = false;
 $notes = '';
 $availId = null;
 } else {
 $disabled = ($availRow['AvailabilityStatus'] !== 'Available');
 $notes = $availRow['Notes'] ?? '';
 $availId = (int) $availRow['AvailabilityID'];
 }

 $bkRow = $bookIndex[$date][$hhmm] ?? null;
 $booking = null;

 if ($bkRow) {
 $booking = [
 'booking_id' => (int) $bkRow['BookingID'],
 'is_own_booking' => ($currentSchoolPersonId > 0 && (int)$bkRow['SchoolPersonID'] === $currentSchoolPersonId),
 'patient' => $bkRow['patient_name'],
 'id' => $bkRow['school_id'],
 'program' => $bkRow['program_or_dept'] ?: $bkRow['person_type'],
 'type' => mapServiceType($bkRow['ServiceType'] ?? ''),
 'purpose' => $bkRow['ReasonForVisit'] ?? '',
 'status' => $bkRow['BookingStatus'],
 'reschedule_status' => $bkRow['RescheduleStatus'] ?? null,
 'reschedule_proposed_date' => $bkRow['RescheduleProposedDate'] ?? null,
 'reschedule_proposed_start'=> $bkRow['RescheduleProposedStart'] ?? null,
 ];
 }

 $slots["{$di}-{$label}"] = [
 'availability_id' => $availId,
 'disabled' => $disabled,
 'notes' => $notes,
 'booking' => $booking,
 ];
 }
 }

 echo json_encode(['status' => 'ok', 'slots' => $slots]);
}

/* ---
 save_slot
 --- */
function saveSlot(PDO $pdo, array $p): void
{
 $profId = (int) ($p['professional_id'] ?? 0);
 $ws = $p['week_start'] ?? '';
 $dayIdx = (int) ($p['day_index'] ?? -1);
 $timeLabel = trim( $p['time_label'] ?? '');
 $notes = trim( $p['notes'] ?? '');
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
 echo json_encode(['status' => 'error', 'message' => 'day_index must be 0-6']);
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
 '8:00' => '08:00:00', '8:30' => '08:30:00',
 '9:00' => '09:00:00', '9:30' => '09:30:00',
 '10:00' => '10:00:00', '10:30' => '10:30:00',
 '11:00' => '11:00:00', '11:30' => '11:30:00',
 '1:00' => '13:00:00', '1:30' => '13:30:00',
 '2:00' => '14:00:00', '2:30' => '14:30:00',
 '3:00' => '15:00:00', '3:30' => '15:30:00',
 '4:00' => '16:00:00', '4:30' => '16:30:00',
 '5:00' => '17:00:00', '5:30' => '17:30:00',
 ];
 $endMap = [
 '8:00' => '08:30:00', '8:30' => '09:00:00',
 '9:00' => '09:30:00', '9:30' => '10:00:00',
 '10:00' => '10:30:00', '10:30' => '11:00:00',
 '11:00' => '11:30:00', '11:30' => '12:00:00',
 '1:00' => '13:30:00', '1:30' => '14:00:00',
 '2:00' => '14:30:00', '2:30' => '15:00:00',
 '3:00' => '15:30:00', '3:30' => '16:00:00',
 '4:00' => '16:30:00', '4:30' => '17:00:00',
 '5:00' => '17:30:00', '5:30' => '18:00:00',
 ];

 if (!array_key_exists($timeLabel, $startMap)) {
 echo json_encode(['status' => 'error', 'message' => "Unrecognised time_label: '{$timeLabel}'"]);
 return;
 }

 $slotDate = (new DateTime($ws))->modify("+{$dayIdx} days")->format('Y-m-d');
 $startTime = $startMap[$timeLabel];
 $endTime = $endMap[$timeLabel];
 $status = $disabled ? 'Unavailable' : 'Available';

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
 FROM medical_professional_availability
 WHERE MedProfID = :prof
 AND AvailableDate = :date
 AND StartTime = :start
 LIMIT 1
 ");
 $check->execute([':prof' => $profId, ':date' => $slotDate, ':start' => $startTime]);
 $existing = $check->fetch();

 if ($existing) {
 $upd = $pdo->prepare("
 UPDATE medical_professional_availability
 SET AvailabilityStatus = :status,
 Notes = :notes,
 EndTime = :end
 WHERE AvailabilityID = :id
 ");
 $upd->execute([
 ':status' => $status,
 ':notes' => ($notes !== '') ? $notes : null,
 ':end' => $endTime,
 ':id' => (int) $existing['AvailabilityID'],
 ]);
 $message = 'Slot updated';
 $availId = (int) $existing['AvailabilityID'];
 } else {
 $ins = $pdo->prepare("
 INSERT INTO medical_professional_availability
 (MedProfID, AvailableDate, StartTime, EndTime,
 SlotDurationMinutes, AvailabilityStatus, Notes)
 VALUES
 (:prof, :date, :start, :end, 30, :status, :notes)
 ");
 $ins->execute([
 ':prof' => $profId,
 ':date' => $slotDate,
 ':start' => $startTime,
 ':end' => $endTime,
 ':status' => $status,
 ':notes' => ($notes !== '') ? $notes : null,
 ]);
 $message = 'Slot created';
 $availId = (int) $pdo->lastInsertId();
 }

 // Audit log: set/update availability (human readable mapping happens in includes/audit.php)
 // Module must be "Schedule".
 try {
 $slotAction = ($status === 'Unavailable') ? 'Set availability' : 'Set availability';
 auditLog(
 isset($_SESSION['UserID']) ? (int)$_SESSION['UserID'] : null,
 isset($_SESSION['SchoolPersonID']) ? (int)$_SESSION['SchoolPersonID'] : null,
 'availability_updated',
 'Schedule',
 null,
 'Set availability for ' . $slotDate,
 null
 );
 } catch (Throwable $auditE) {
 // non-fatal
 }

 echo json_encode([
 'status' => 'ok',
 'message' => $message,
 'availability_id' => $availId,
 'slot_date' => $slotDate,
 'start_time' => $startTime,
 'avail_status' => $status,
 ]);

 } catch (PDOException $e) {
 echo json_encode(['status' => 'error', 'message' => 'DB error (save_slot): ' . $e->getMessage()]);
 }
}

/* ---
 get_stats
 --- */
function getStats(PDO $pdo, array $p): void
{
 $profId = (int) ($p['professional_id'] ?? 0);
 $weekStart = trim($p['week_start'] ?? '');

 if (!$profId || !$weekStart) {
 echo json_encode(['status' => 'error', 'message' => 'Missing professional_id or week_start']);
 return;
 }

 $ws = new DateTime($weekStart);
 $weekEnd = (clone $ws)->modify('+6 days');
 $wsFmt = $ws->format('Y-m-d');
 $weFmt = $weekEnd->format('Y-m-d');

 try {
 $stmtBk = $pdo->prepare("
 SELECT COUNT(*) FROM bookings
 WHERE MedProfID = :prof
 AND AppointmentDate BETWEEN :ws AND :we
 AND BookingStatus NOT IN ('Cancelled')
 ");
 $stmtBk->execute([':prof' => $profId, ':ws' => $wsFmt, ':we' => $weFmt]);
 $bookings = (int) $stmtBk->fetchColumn();

 $stmtBl = $pdo->prepare("
 SELECT COUNT(*) FROM medical_professional_availability
 WHERE MedProfID = :prof
 AND AvailableDate BETWEEN :ws AND :we
 AND AvailabilityStatus IN ('Unavailable', 'Cancelled')
 ");
 $stmtBl->execute([':prof' => $profId, ':ws' => $wsFmt, ':we' => $weFmt]);
 $blocked = (int) $stmtBl->fetchColumn();

 /* Pending count */
 $stmtPd = $pdo->prepare("
 SELECT COUNT(*) FROM bookings
 WHERE MedProfID = :prof
 AND AppointmentDate BETWEEN :ws AND :we
 AND BookingStatus = 'Pending'
 ");
 $stmtPd->execute([':prof' => $profId, ':ws' => $wsFmt, ':we' => $weFmt]);
 $pending = (int) $stmtPd->fetchColumn();

 $totalWeekdaySlots = 5 * 18;
 $open = max(0, $totalWeekdaySlots - $bookings - $blocked);
 $professionals = (int) $pdo->query("SELECT COUNT(*) FROM medical_professionals")->fetchColumn();

 echo json_encode([
 'status' => 'ok',
 'bookings' => $bookings,
 'open' => $open,
 'blocked' => $blocked,
 'pending' => $pending,
 'professionals' => $professionals,
 ]);

 } catch (PDOException $e) {
 echo json_encode(['status' => 'error', 'message' => 'DB error (get_stats): ' . $e->getMessage()]);
 }
}

/* ---
 get_pending_bookings
 Returns all Pending bookings for a given professional.
 Used to populate the Booking Requests panel.
 --- */
function getPendingBookings(PDO $pdo, array $p): void
{
 $profId = (int) ($p['professional_id'] ?? 0);

 if (!$profId) {
 echo json_encode(['status' => 'error', 'message' => 'professional_id is required']);
 return;
 }

 try {
 /* Inside schedule.ajax.php, modify getPendingBookings() function */

// Replace the SQL in getPendingBookings() with this version:
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
 b.RescheduleProposedDate,
 b.RescheduleProposedStart,
 b.RescheduleProposedEnd,
 b.RescheduleStatus,
 COALESCE(
 NULLIF(TRIM(CONCAT(sp.FirstName, ' ', sp.LastName)), ''),
 'Unknown Patient'
 ) AS patient_name,
 COALESCE(sp.SchoolID, '') AS school_id,
 COALESCE(sp.PersonType, '') AS person_type,
 COALESCE(
 (SELECT pr.ProgramName
 FROM student_enrollments se
 JOIN programs pr ON pr.ProgramID = se.ProgramID
 WHERE se.SchoolPersonID = b.SchoolPersonID
 ORDER BY se.EnrollmentID DESC LIMIT 1),
 (SELECT ea.Department
 FROM employee_assignments ea
 WHERE ea.SchoolPersonID = b.SchoolPersonID
 AND ea.EmploymentStatus = 'Employed'
 LIMIT 1),
 sp.PersonType
 ) AS program_or_dept
 FROM bookings b
 LEFT JOIN school_people sp ON sp.SchoolPersonID = b.SchoolPersonID
 WHERE b.MedProfID = :prof
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

/* ---
 respond_booking
 Medical staff accepts or declines a patient booking.

 Expected params:
 booking_id - int
 response - 'accept' | 'decline'
 decline_reason (optional) - string, shown to patient

 On Accept -> BookingStatus = 'Approved'
 -> availability slot remains Unavailable
 On Decline -> BookingStatus = 'Declined'
 -> availability slot flipped back to 'Available'
 -> DeclineReason stored (requires column - see note)
 --- */
/* ---
 respond_booking
 Medical staff accepts, declines, or reschedules a patient booking.
 --- */

function respondBooking(PDO $pdo, array $p): void
{
 $bookingId = (int) ($p['booking_id'] ?? 0);
 $response = strtolower(trim($p['response'] ?? ''));
 $declineReason = trim($p['decline_reason'] ?? '');
 
 // Reschedule parameters
 $newDate = trim($p['new_date'] ?? '');
 $newStart = trim($p['new_start'] ?? '');

 if (!$bookingId) {
 echo json_encode(['status' => 'error', 'message' => 'booking_id is required']);
 return;
 }

 if (!in_array($response, ['accept', 'decline', 'reschedule'], true)) {
 echo json_encode(['status' => 'error', 'message' => "response must be 'accept', 'decline' or 'reschedule'"]);
 return;
 }

 try {
 // Fetch the booking to verify it exists and is still Pending
 $fetch = $pdo->prepare("
 SELECT BookingID, BookingStatus, AvailabilityID, MedProfID, AppointmentDate, AppointmentStart
 FROM bookings
 WHERE BookingID = :id
 LIMIT 1
 ");
 $fetch->execute([':id' => $bookingId]);
 $booking = $fetch->fetch();

 if (!$booking) {
 echo json_encode(['status' => 'error', 'message' => "Booking #{$bookingId} not found"]);
 return;
 }

 // --- ACCEPT ---
 if ($response === 'accept') {
 $upd = $pdo->prepare("
 UPDATE bookings
 SET BookingStatus = 'Approved', RescheduleStatus = NULL
 WHERE BookingID = :id
 ");
 $upd->execute([':id' => $bookingId]);

 // Audit log
 auditLog(
 isset($_SESSION['UserID']) ? (int)$_SESSION['UserID'] : null,
 isset($_SESSION['SchoolPersonID']) ? (int)$_SESSION['SchoolPersonID'] : null,
 'booking_approved',
 'Schedule',
 (int)$bookingId,
 'Approved appointment booking #' . $bookingId,
 null
 );

 echo json_encode([
 'status' => 'ok',
 'action' => 'accepted',
 'booking_id' => $bookingId,
 'message' => "Booking #{$bookingId} has been accepted.",
 ]);
 return;
 }

 // --- RESCHEDULE ---
 // In respondBooking function - ensure this section exists:
if ($response === 'reschedule') {
 if (!$newDate || !$newStart) {
 echo json_encode(['status' => 'error', 'message' => 'New date and time are required']);
 return;
 }

 // Map 24-hour start -> 30-min-later end (matches saveSlot endMap)
 $endMap = [
 '08:00:00' => '08:30:00', '08:30:00' => '09:00:00',
 '09:00:00' => '09:30:00', '09:30:00' => '10:00:00',
 '10:00:00' => '10:30:00', '10:30:00' => '11:00:00',
 '11:00:00' => '11:30:00', '11:30:00' => '12:00:00',
 '13:00:00' => '13:30:00', '13:30:00' => '14:00:00',
 '14:00:00' => '14:30:00', '14:30:00' => '15:00:00',
 '15:00:00' => '15:30:00', '15:30:00' => '16:00:00',
 '16:00:00' => '16:30:00', '16:30:00' => '17:00:00',
 '17:00:00' => '17:30:00', '17:30:00' => '18:00:00',
 ];
 // Normalise incoming start to HH:MM:SS (it should already be from JS map)
 $newStart = strlen($newStart) === 5 ? $newStart . ':00' : $newStart;
 $newEnd = $endMap[$newStart] ?? null;
 if (!$newEnd) {
 // Fallback: add 30 minutes via DateTime
 $newEnd = (new DateTime($newStart))->modify('+30 minutes')->format('H:i:s');
 }

 // Save reschedule proposal
 $upd = $pdo->prepare("
 UPDATE bookings
 SET RescheduleProposedDate = :newDate,
 RescheduleProposedStart = :newStart,
 RescheduleProposedEnd = :newEnd,
 RescheduleStatus = 'Proposed'
 WHERE BookingID = :id
 ");
 $upd->execute([
 ':newDate' => $newDate,
 ':newStart' => $newStart,
 ':newEnd' => $newEnd,
 ':id' => $bookingId
 ]);

 echo json_encode([
 'status' => 'ok',
 'action' => 'rescheduled',
 'booking_id' => $bookingId,
 'message' => "Reschedule request sent to patient."
 ]);
 return;
}
 // --- DECLINE ---
 if ($response === 'decline') {
 try {
 $upd = $pdo->prepare("
 UPDATE bookings
 SET BookingStatus = 'Cancelled',
 DeclineReason = :reason
 WHERE BookingID = :id
 ");
 $upd->execute([':reason' => $declineReason ?: null, ':id' => $bookingId]);
 } catch (PDOException $colErr) {
 $upd = $pdo->prepare("UPDATE bookings SET BookingStatus = 'Cancelled' WHERE BookingID = :id");
 $upd->execute([':id' => $bookingId]);
 }

 if (!empty($booking['AvailabilityID'])) {
 try {
 $rel = $pdo->prepare("UPDATE medical_professional_availability SET AvailabilityStatus = 'Available' WHERE AvailabilityID = :avid");
 $rel->execute([':avid' => (int) $booking['AvailabilityID']]);
 } catch (PDOException $e2) { /* non-fatal */ }
 }

 echo json_encode([
 'status' => 'ok',
 'action' => 'declined',
 'booking_id' => $bookingId,
 'message' => "Booking #{$bookingId} has been declined.",
 ]);
 }

 } catch (PDOException $e) {
 echo json_encode(['status' => 'error', 'message' => 'DB error (respond_booking): ' . $e->getMessage()]);
 }
}

/* ---
 patient_respond_reschedule
 Patient accepts or declines a reschedule proposal from staff.

 Expected params:
 booking_id - int
 response - 'accept' | 'decline'

 On Accept -> AppointmentDate/Start/End updated with proposed values
 -> RescheduleStatus = 'Accepted'
 -> BookingStatus = 'Approved'
 On Decline -> RescheduleStatus = 'Rejected'
 -> BookingStatus stays 'Pending' (staff can propose again)
 --- */
function patientRespondReschedule(PDO $pdo, array $p): void
{
 $bookingId = (int) ($p['booking_id'] ?? 0);
 $response = strtolower(trim($p['response'] ?? ''));

 if (!$bookingId) {
 echo json_encode(['status' => 'error', 'message' => 'booking_id is required']);
 return;
 }
 if (!in_array($response, ['accept', 'decline'], true)) {
 echo json_encode(['status' => 'error', 'message' => "response must be 'accept' or 'decline'"]);
 return;
 }

 try {
 $fetch = $pdo->prepare("
 SELECT BookingID, BookingStatus, RescheduleStatus,
 RescheduleProposedDate, RescheduleProposedStart, RescheduleProposedEnd,
 AppointmentDate, AppointmentStart, MedProfID,
 AvailabilityID, SchoolPersonID
 FROM bookings
 WHERE BookingID = :id
 LIMIT 1
 ");
 $fetch->execute([':id' => $bookingId]);
 $booking = $fetch->fetch();

 if (!$booking) {
 echo json_encode(['status' => 'error', 'message' => "Booking #{$bookingId} not found"]);
 return;
 }

 if (($booking['RescheduleStatus'] ?? '') !== 'Proposed') {
 echo json_encode(['status' => 'error', 'message' => 'No pending reschedule proposal for this booking']);
 return;
 }

 // Verify the patient owns this booking
 $sessionSPID = isset($_SESSION['SchoolPersonID']) ? (int)$_SESSION['SchoolPersonID'] : 0;
 if (!DEV_BYPASS && $sessionSPID && (int)$booking['SchoolPersonID'] !== $sessionSPID) {
 echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
 return;
 }

 if ($response === 'accept') {
 $newDate = $booking['RescheduleProposedDate'];
 $newStart = $booking['RescheduleProposedStart'];
 $newEnd = $booking['RescheduleProposedEnd'];
 $profId = (int) $booking['MedProfID'];
 $oldAvailId = !empty($booking['AvailabilityID']) ? (int)$booking['AvailabilityID'] : null;

 // 1. Free the OLD availability slot so it shows as open again
 if ($oldAvailId) {
 try {
 $freeOld = $pdo->prepare("
 UPDATE medical_professional_availability
 SET AvailabilityStatus = 'Available'
 WHERE AvailabilityID = :avid
 ");
 $freeOld->execute([':avid' => $oldAvailId]);
 } catch (PDOException $e2) { /* non-fatal */ }
 }

 // 2. Find or create the NEW availability slot and mark it Unavailable
 $newAvailId = null;
 try {
 $findNew = $pdo->prepare("
 SELECT AvailabilityID FROM medical_professional_availability
 WHERE MedProfID = :prof
 AND AvailableDate = :date
 AND StartTime = :start
 LIMIT 1
 ");
 $findNew->execute([':prof' => $profId, ':date' => $newDate, ':start' => $newStart]);
 $existingSlot = $findNew->fetch();

 if ($existingSlot) {
 // Slot exists - mark it unavailable and link to this booking
 $newAvailId = (int) $existingSlot['AvailabilityID'];
 $blockNew = $pdo->prepare("
 UPDATE medical_professional_availability
 SET AvailabilityStatus = 'Unavailable'
 WHERE AvailabilityID = :avid
 ");
 $blockNew->execute([':avid' => $newAvailId]);
 } else {
 // Slot doesn't exist yet - insert it as Unavailable
 $insNew = $pdo->prepare("
 INSERT INTO medical_professional_availability
 (MedProfID, AvailableDate, StartTime, EndTime, AvailabilityStatus, Notes)
 VALUES (:prof, :date, :start, :end, 'Unavailable', 'Rescheduled appointment')
 ");
 $insNew->execute([
 ':prof' => $profId,
 ':date' => $newDate,
 ':start' => $newStart,
 ':end' => $newEnd ?? '',
 ]);
 $newAvailId = (int) $pdo->lastInsertId();
 }
 } catch (PDOException $e2) { /* non-fatal - booking update still proceeds */ }

 // 3. Update the booking: move to new date/time, set status Approved, link new slot
 $updSql = "
 UPDATE bookings
 SET AppointmentDate = :newDate,
 AppointmentStart = :newStart,
 AppointmentEnd = :newEnd,
 RescheduleStatus = 'Accepted',
 BookingStatus = 'Approved'
 ";
 $updParams = [':newDate' => $newDate, ':newStart' => $newStart, ':newEnd' => $newEnd, ':id' => $bookingId];
 if ($newAvailId) {
 $updSql .= ", AvailabilityID = :availId";
 $updParams[':availId'] = $newAvailId;
 }
 $updSql .= " WHERE BookingID = :id";
 $upd = $pdo->prepare($updSql);
 $upd->execute($updParams);

 auditLog(
 isset($_SESSION['UserID']) ? (int)$_SESSION['UserID'] : null,
 isset($_SESSION['SchoolPersonID']) ? (int)$_SESSION['SchoolPersonID'] : null,
 'reschedule_accepted',
 'Schedule',
 $bookingId,
 "Patient accepted reschedule for booking #{$bookingId}",
 null
 );

 echo json_encode([
 'status' => 'ok',
 'action' => 'accepted',
 'booking_id' => $bookingId,
 'new_date' => $booking['RescheduleProposedDate'],
 'new_start' => $booking['RescheduleProposedStart'],
 'message' => "Reschedule accepted. Appointment confirmed for {$booking['RescheduleProposedDate']}.",
 ]);

 } else {
 $upd = $pdo->prepare("
 UPDATE bookings
 SET RescheduleStatus = 'Rejected'
 WHERE BookingID = :id
 ");
 $upd->execute([':id' => $bookingId]);

 auditLog(
 isset($_SESSION['UserID']) ? (int)$_SESSION['UserID'] : null,
 isset($_SESSION['SchoolPersonID']) ? (int)$_SESSION['SchoolPersonID'] : null,
 'reschedule_rejected',
 'Schedule',
 $bookingId,
 "Patient declined reschedule for booking #{$bookingId}",
 null
 );

 echo json_encode([
 'status' => 'ok',
 'action' => 'declined',
 'booking_id' => $bookingId,
 'message' => "Reschedule declined. The clinic will be notified.",
 ]);
 }

 } catch (PDOException $e) {
 echo json_encode(['status' => 'error', 'message' => 'DB error (patient_respond_reschedule): ' . $e->getMessage()]);
 }
}


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



