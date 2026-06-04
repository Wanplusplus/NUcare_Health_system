<?php
declare(strict_types=1);

/* ════════════════════════════════════════════════════════════════════
   dashboard.ajax.php   →  PLACE IN:  ajax/
   ────────────────────────────────────────────────────────────────────
   Handles AJAX calls from dashboard.js.
   Currently supports:
     GET ?action=searchPatients&q=QUERY

   Returns the patient in the field-name shape dashboard.js loadPatient()
   expects (patientFname, patientLname, patientID, etc.).

   NOTE: If your dashboard page is two levels deep (pages/medical/),
   dashboard.js's fetch URL "ajax/dashboard.ajax.php" resolves relative
   to the HTML page. Make sure the path matches — you may need to adjust
   the JS fetch URL to "../../ajax/dashboard.ajax.php" if the page is
   under pages/medical/.
══════════════════════════════════════════════════════════════════════ */

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['UserID'])) {
    echo json_encode(['success' => false, 'error' => 'Session expired.']);
    exit;
}

$pdo = require __DIR__ . '/../config/db_pdo.php';

$action = trim((string)($_GET['action'] ?? ''));

switch ($action) {

    case 'searchPatients':
        handleSearchPatients($pdo);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action.']);
        break;
}

/* ══════════════════════════════════════════════════════════════════
   searchPatients
   Searches school_people by SchoolID, SchoolPersonID, or name.
   Returns the field names dashboard.js loadPatient() expects.
══════════════════════════════════════════════════════════════════ */
function handleSearchPatients(PDO $pdo): void
{
    $q = trim((string)($_GET['q'] ?? ''));
    if ($q === '') {
        echo json_encode(['success' => false, 'error' => 'Please enter a patient ID or name.']);
        return;
    }

    $like = '%' . $q . '%';

    // Normalise SCH- prefix
    $normalized = trim((string)preg_replace('/^\s*SCH[-\s]+/i', '', $q));
    $likeNorm   = '%' . $normalized . '%';

    $sql = "
        SELECT
            sp.SchoolPersonID,
            sp.SchoolID,
            sp.FirstName,
            sp.MiddleName,
            sp.LastName,
            sp.Sex,
            sp.PersonType,
            pi.BirthDate,
            pi.ContactNo
        FROM school_people sp
        LEFT JOIN patients_info pi ON pi.SchoolPersonID = sp.SchoolPersonID
        WHERE
            sp.SchoolID = :exact1
            OR sp.SchoolID = :exact_norm
            OR sp.SchoolID LIKE :like1
            OR sp.SchoolID LIKE :like_norm
            OR CAST(sp.SchoolPersonID AS CHAR) = :pid
            OR CONCAT_WS(' ', sp.FirstName, sp.MiddleName, sp.LastName) LIKE :like2
            OR CONCAT_WS(' ', sp.FirstName, sp.LastName) LIKE :like3
        ORDER BY
            CASE
                WHEN sp.SchoolID = :exact2 THEN 0
                WHEN sp.SchoolID = :exact_norm2 THEN 1
                WHEN sp.SchoolID LIKE :like4 THEN 2
                ELSE 3
            END ASC,
            sp.LastName ASC
        LIMIT 1
    ";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':exact1'      => $q,
            ':exact_norm'  => $normalized,
            ':like1'       => $like,
            ':like_norm'   => $likeNorm,
            ':pid'         => $q,
            ':like2'       => $like,
            ':like3'       => $like,
            ':exact2'      => $q,
            ':exact_norm2' => $normalized,
            ':like4'       => $like,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => 'Database error.']);
        return;
    }

    if (!$row) {
        echo json_encode(['success' => true, 'found' => false, 'patient' => null]);
        return;
    }

    // Map to the field names dashboard.js loadPatient() uses:
    //   patientFname, patientMname, patientLname, patientID,
    //   patientSex, patientBirthday, patientProgram, patientPhone
    $patient = [
        'patientFname'    => $row['FirstName']  ?? '',
        'patientMname'    => $row['MiddleName'] ?? '',
        'patientLname'    => $row['LastName']   ?? '',
        'patientID'       => $row['SchoolID'] ?? (string)$row['SchoolPersonID'],
        'patientSex'      => $row['Sex']        ?? '',
        'patientBirthday' => $row['BirthDate']  ?? '',
        'patientProgram'  => $row['PersonType'] ?? '',
        'patientPhone'    => $row['ContactNo']  ?? '',
    ];

    echo json_encode([
        'success' => true,
        'found'   => true,
        'patient' => $patient,
    ]);
}