<?php
declare(strict_types=1);

/* ════════════════════════════════════════════════════════════════════
   register_walkin.ajax.php   →  PLACE IN:  ajax/consultation/
   ────────────────────────────────────────────────────────────────────
   Implements spec MODULE 1 (CRITICAL): manual patient registration when
   no record is found. A walk-in (Guard / Visitor / ROMAC / Staff) can be
   registered WITHOUT a School ID, then consultation proceeds immediately.

   Behaviour:
     • SchoolID is OPTIONAL — stored as NULL when blank.
     • SchoolPersonID (AUTO_INCREMENT) is the primary identity key.
     • Duplicate guard: FirstName + LastName + PersonType (case-insensitive).
       If a match already exists, the EXISTING record is returned instead of
       creating a second one (prevents duplicate walk-ins).
     • Returns the patient object in the SAME shape as patient_search.ajax.php
       so the existing consultation front-end (loadPatient) can consume it
       directly and auto-start the transaction.

   Expected table (see sql/school_people.sql):
     school_people(
       SchoolPersonID INT PK AUTO_INCREMENT,
       SchoolID VARCHAR(50) NULL UNIQUE,
       FirstName VARCHAR(100) NOT NULL,
       LastName  VARCHAR(100) NOT NULL,
       MiddleName VARCHAR(100) NULL,
       Email VARCHAR(255) NULL,
       PersonType ENUM('Staff','Guard','Visitor','ROMAC') ...,
       Sex ENUM('Male','Female'),
       CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
     )
══════════════════════════════════════════════════════════════════════ */

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['UserID'])) {
    echo json_encode(['ok' => false, 'message' => 'Session expired. Please sign in again.']);
    exit;
}

$pdo = require __DIR__ . '/../../config/db_pdo.php';

/* ── helpers ──────────────────────────────────────────────────────── */
function clean(mixed $v): string { return trim((string)($v ?? '')); }
function nz(string $s): ?string  { return $s !== '' ? $s : null; }

/* ── input ────────────────────────────────────────────────────────── */
$firstName  = clean($_POST['first_name']  ?? '');
$lastName   = clean($_POST['last_name']   ?? '');
$middleName = nz(clean($_POST['middle_name'] ?? ''));
$sex        = clean($_POST['sex']         ?? '');
$personType = clean($_POST['person_type'] ?? '');
$email      = nz(clean($_POST['email']    ?? ''));
$schoolId   = nz(clean($_POST['school_id']?? ''));   // optional → NULL when blank

/* ── validation ───────────────────────────────────────────────────── */
$errors = [];
if ($firstName === '') $errors['first_name'] = 'First name is required.';
if ($lastName  === '') $errors['last_name']  = 'Last name is required.';

$allowedSex = ['Male', 'Female'];
if (!in_array($sex, $allowedSex, true)) $errors['sex'] = 'Please select a valid sex.';

$allowedTypes = ['Staff', 'Guard', 'Visitor', 'ROMAC'];
if (!in_array($personType, $allowedTypes, true)) {
    $errors['person_type'] = 'Please select a valid person type.';
}
if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Please enter a valid email address.';
}

if ($errors) {
    echo json_encode(['ok' => false, 'message' => 'Please correct the highlighted fields.', 'errors' => $errors]);
    exit;
}

/* ── shape a patient object identical to patient_search.ajax.php ─────── */
function buildPatient(array $p): array {
    $parts = array_filter([
        $p['FirstName'] ?? '',
        !empty($p['MiddleName']) ? mb_substr((string)$p['MiddleName'], 0, 1) . '.' : '',
        $p['LastName'] ?? '',
    ]);
    return [
        'SchoolPersonID' => (int)$p['SchoolPersonID'],
        'SchoolID'       => $p['SchoolID']   ?? null,
        'FirstName'      => $p['FirstName']  ?? '',
        'MiddleName'     => $p['MiddleName'] ?? '',
        'LastName'       => $p['LastName']   ?? '',
        'FullName'       => trim(implode(' ', $parts)),
        'Sex'            => $p['Sex']        ?? '',
        'PersonType'     => $p['PersonType'] ?? '',
        'Age'            => null,
        'LoadedAt'       => date('h:i A'),
    ];
}

try {
    /* ── duplicate guard: FirstName + LastName + PersonType (case-insensitive) ── */
    $dup = $pdo->prepare("
        SELECT SchoolPersonID, SchoolID, FirstName, MiddleName, LastName, Sex, PersonType
        FROM school_people
        WHERE LOWER(FirstName) = LOWER(:fn)
          AND LOWER(LastName)  = LOWER(:ln)
          AND PersonType       = :pt
        LIMIT 1
    ");
    $dup->execute([':fn' => $firstName, ':ln' => $lastName, ':pt' => $personType]);
    $existing = $dup->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $patient = buildPatient($existing);
        echo json_encode([
            'ok'        => true,
            'found'     => true,
            'duplicate' => true,
            'message'   => 'A matching record already exists — loading it instead of creating a duplicate.',
            'patient'   => $patient,
        ] + $patient);
        exit;
    }

    /* ── optional: guard against a clashing non-null SchoolID ── */
    if ($schoolId !== null) {
        $chk = $pdo->prepare("SELECT SchoolPersonID FROM school_people WHERE SchoolID = :sid LIMIT 1");
        $chk->execute([':sid' => $schoolId]);
        if ($chk->fetchColumn()) {
            echo json_encode([
                'ok' => false,
                'message' => 'That School ID is already assigned to another record.',
                'errors' => ['school_id' => 'School ID already in use.'],
            ]);
            exit;
        }
    }

    /* ── insert (SchoolID may be NULL) ── */
    $ins = $pdo->prepare("
        INSERT INTO school_people (SchoolID, FirstName, LastName, MiddleName, Email, PersonType, Sex)
        VALUES (:sid, :fn, :ln, :mn, :em, :pt, :sex)
    ");
    $ins->execute([
        ':sid' => $schoolId,
        ':fn'  => $firstName,
        ':ln'  => $lastName,
        ':mn'  => $middleName,
        ':em'  => $email,
        ':pt'  => $personType,
        ':sex' => $sex,
    ]);

    $newId = (int)$pdo->lastInsertId();

    $patient = buildPatient([
        'SchoolPersonID' => $newId,
        'SchoolID'       => $schoolId,
        'FirstName'      => $firstName,
        'MiddleName'     => $middleName,
        'LastName'       => $lastName,
        'Sex'            => $sex,
        'PersonType'     => $personType,
    ]);

    /* optional audit (non-fatal if helper missing) */
    $auditPath = __DIR__ . '/../../includes/audit.php';
    if (file_exists($auditPath)) {
        require_once $auditPath;
        if (function_exists('auditLog')) {
            auditLog(
                (int)$_SESSION['UserID'],
                isset($_SESSION['SchoolPersonID']) ? (int)$_SESSION['SchoolPersonID'] : null,
                'Registered walk-in patient ' . $patient['FullName'],
                'Patient',
                null,
                'Created walk-in record (SchoolPersonID ' . $newId . ', Type: ' . $personType . ')',
                null
            );
        }
    }

    echo json_encode([
        'ok'      => true,
        'found'   => true,
        'message' => 'Patient registered. Starting consultation…',
        'patient' => $patient,
    ] + $patient);

} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'message' => 'Could not register the patient.', 'debug' => $e->getMessage()]);
}