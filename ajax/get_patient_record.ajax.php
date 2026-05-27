<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = require __DIR__ . '/../../config/db_pdo.php';

$schoolPersonID = (int)($_GET['schoolpersonid'] ?? $_GET['school_person_id'] ?? 0);

if ($schoolPersonID <= 0) {
    echo json_encode(['ok' => false, 'message' => 'Invalid schoolpersonid.']);
    exit;
}

/* ─────────────────────────────────────────────
   HELPERS
───────────────────────────────────────────── */

function tableExists(PDO $pdo, string $table): bool
{
    try {
        $pdo->query("SELECT 1 FROM `{$table}` LIMIT 1");
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function columnsOf(PDO $pdo, string $table): array
{
    try {
        return $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {
        return [];
    }
}

function firstExistingTable(PDO $pdo, array $tables): ?string
{
    foreach ($tables as $table) {
        if (tableExists($pdo, $table)) return $table;
    }
    return null;
}

/* ─────────────────────────────────────────────
   PATIENT / PERSON PROFILE
───────────────────────────────────────────── */

try {
    $personSql = "
        SELECT
            sp.SchoolPersonID,
            sp.SchoolID,
            sp.FirstName,
            sp.MiddleName,
            sp.LastName,
            sp.Email,
            sp.PersonType,
            sp.Sex,
            se.AcademicYear,
            se.Semester,
            se.EnrollmentStatus,
            pr.ProgramName AS program,
            pr.Department   AS department
        FROM school_people sp
        LEFT JOIN student_enrollments se
            ON se.EnrollmentID = (
                SELECT MAX(se2.EnrollmentID)
                FROM student_enrollments se2
                WHERE se2.SchoolPersonID = sp.SchoolPersonID
            )
        LEFT JOIN programs pr ON pr.ProgramID = se.ProgramID
        WHERE sp.SchoolPersonID = :id
        LIMIT 1
    ";
    $stmt = $pdo->prepare($personSql);
    $stmt->execute([':id' => $schoolPersonID]);
    $person = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$person) {
        echo json_encode(['ok' => false, 'message' => 'Patient not found.']);
        exit;
    }
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'message' => 'Failed to load patient profile.', 'debug' => $e->getMessage()]);
    exit;
}

/* ─────────────────────────────────────────────
   KNOWN MEDICAL CONDITIONS  (user_diseases → diseases)
───────────────────────────────────────────── */

$diseases = [];
try {
    if (tableExists($pdo, 'user_diseases') && tableExists($pdo, 'diseases')) {
        $dStmt = $pdo->prepare("
            SELECT d.DiseaseName AS diseaseName, ud.Notes AS notes
            FROM user_diseases ud
            JOIN diseases d ON d.DiseaseID = ud.DiseaseID
            WHERE ud.SchoolPersonID = :id
            ORDER BY d.DiseaseName ASC
        ");
        $dStmt->execute([':id' => $schoolPersonID]);
        $diseases = $dStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
} catch (Throwable $e) {
    $diseases = [];
}

/* ─────────────────────────────────────────────
   CLINIC TRANSACTIONS  (+ physical_examinations + staff)
───────────────────────────────────────────── */

$peCols      = columnsOf($pdo, 'physical_examinations');
$wantedVitals = ['BloodPressure', 'Temperature', 'PulseRate', 'Weight', 'Height'];
$safeVitals  = array_values(array_filter($wantedVitals, fn($col) => in_array($col, $peCols, true)));

$peSelect = '';
if (!empty($safeVitals)) {
    $peSelect = ', ' . implode(', ', array_map(fn($c) => "pe.`{$c}`", $safeVitals));
}

$peJoin = tableExists($pdo, 'physical_examinations')
    ? 'LEFT JOIN physical_examinations pe ON pe.ClinicTransactionID = ct.ClinicTransactionID'
    : '';

// Detect medical-professional staff table and usable columns
$staffTable     = firstExistingTable($pdo, ['medical_professionals', 'staff_profiles', 'clinic_staff']);
$medProfSelect  = '';
$medProfJoin    = '';

if ($staffTable !== null) {
    $staffCols = columnsOf($pdo, $staffTable);
    // Identify the PK/FK column that links to ct.MedProfID
    $fkCol = null;
    foreach (['UserID', 'MedProfID', 'StaffID', 'SchoolPersonID'] as $candidate) {
        if (in_array($candidate, $staffCols, true)) { $fkCol = $candidate; break; }
    }
    if ($fkCol !== null && in_array('FirstName', $staffCols, true) && in_array('LastName', $staffCols, true)) {
        $medProfSelect = ", CONCAT_WS(' ', mp.FirstName, mp.LastName) AS medProfName";
        $medProfJoin   = "LEFT JOIN `{$staffTable}` mp ON mp.`{$fkCol}` = ct.MedProfID";
    }
}

$rawTransactions = [];
try {
    $txSql = "
        SELECT
            ct.ClinicTransactionID,
            ct.VisitDate,
            ct.CreatedAt,
            ct.ServiceType,
            ct.ConsultationStatus,
            ct.Complaint,
            ct.Notes
            {$medProfSelect}
            {$peSelect}
        FROM clinic_transactions ct
        {$peJoin}
        {$medProfJoin}
        WHERE ct.SchoolPersonID = :id
        ORDER BY ct.ClinicTransactionID DESC
        LIMIT 50
    ";
    $txStmt = $pdo->prepare($txSql);
    $txStmt->execute([':id' => $schoolPersonID]);
    $rawTransactions = $txStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $rawTransactions = [];
}

// Medicine dispensing prepared statement (reused per transaction)
$medStmt = null;
if (
    tableExists($pdo, 'medicine_dispensing') &&
    tableExists($pdo, 'medicine_inventory')  &&
    tableExists($pdo, 'medicines')
) {
    try {
        $medStmt = $pdo->prepare("
            SELECT
                m.MedicineName   AS medicineName,
                md.QuantityDispensed AS qty
            FROM medicine_dispensing md
            JOIN medicine_inventory mi ON mi.InventoryID = md.InventoryID
            JOIN medicines m           ON m.MedicineID   = mi.MedicineID
            WHERE md.ClinicTransactionID = :ctid
            ORDER BY md.DispensedAt ASC
        ");
    } catch (Throwable $e) {
        $medStmt = null;
    }
}

// Attachment prepared statement (reused per transaction)
$attachStmt = null;
$hasAttachDocTypes = tableExists($pdo, 'attachment_document_types');
if (tableExists($pdo, 'consultation_attachments')) {
    // Build LEFT JOIN to attachment_document_types only if that table exists
    $adtJoin   = $hasAttachDocTypes
        ? "LEFT JOIN attachment_document_types adt ON adt.DocumentTypeID = ca.DocumentTypeID"
        : '';
    $adtSelect = $hasAttachDocTypes
        ? "COALESCE(adt.Category, ca.AttachmentCategory, 'Medical Document') AS certificateType"
        : "COALESCE(ca.AttachmentCategory, 'Medical Document') AS certificateType";

    try {
        $attachStmt = $pdo->prepare("
            SELECT
                ca.AttachmentID,
                ca.FileName,
                ca.FileType,
                ca.FileSizeBytes,
                ca.Notes,
                ca.CreatedAt,
                {$adtSelect}
            FROM consultation_attachments ca
            {$adtJoin}
            WHERE ca.ClinicTransactionID = :ctid
            ORDER BY ca.CreatedAt ASC
        ");
    } catch (Throwable $e) {
        $attachStmt = null;
    }
}

// Build transactions array
$transactions = [];
foreach ($rawTransactions as $tx) {
    $ctid        = (int)($tx['ClinicTransactionID'] ?? 0);
    $medicines   = [];
    $attachments = [];

    if ($medStmt && $ctid > 0) {
        try {
            $medStmt->execute([':ctid' => $ctid]);
            $medicines = $medStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) { $medicines = []; }
    }

    if ($attachStmt && $ctid > 0) {
        try {
            $attachStmt->execute([':ctid' => $ctid]);
            foreach (($attachStmt->fetchAll(PDO::FETCH_ASSOC) ?: []) as $a) {
                $aid           = (int)($a['AttachmentID'] ?? 0);
                $attachments[] = [
                    'attachmentID'   => $aid,
                    'fileName'       => $a['FileName']      ?? null,
                    'fileType'       => $a['FileType']      ?? null,
                    'fileSizeBytes'  => isset($a['FileSizeBytes']) ? (int)$a['FileSizeBytes'] : null,
                    'certificateType'=> $a['certificateType'] ?? 'Medical Document',
                    'notes'          => $a['Notes']         ?? null,
                    'createdAt'      => $a['CreatedAt']     ?? null,
                    'viewUrl'        => "../../ajax/records/serve_attachment.ajax.php?id={$aid}",
                    'downloadUrl'    => "../../ajax/records/serve_attachment.ajax.php?id={$aid}&dl=1",
                ];
            }
        } catch (Throwable $e) { $attachments = []; }
    }

    $record = [
        'clinicTransactionID' => $ctid,
        'visitDate'           => $tx['VisitDate']           ?? null,
        'createdAt'           => $tx['CreatedAt']           ?? null,
        'serviceType'         => $tx['ServiceType']         ?? 'General Consultation',
        'consultationStatus'  => $tx['ConsultationStatus']  ?? '',
        'complaint'           => $tx['Complaint']           ?? null,
        'notes'               => $tx['Notes']               ?? null,
        'medProfName'         => $tx['medProfName']         ?? null,
        'medicines'           => $medicines,
        'attachments'         => $attachments,
    ];

    // Map vitals using lcfirst — matches JS keys (e.g. bloodPressure, pulseRate…)
    foreach ($safeVitals as $v) {
        $record[lcfirst($v)] = $tx[$v] ?? null;
    }

    $transactions[] = $record;
}

/* ─────────────────────────────────────────────
   EMERGENCIES
───────────────────────────────────────────── */

$emergencies = [];
try {
    if (tableExists($pdo, 'emergencies')) {
        $eStmt = $pdo->prepare("
            SELECT
                IncidentDate,
                IncidentTime,
                IncidentLocation,
                BP,
                RR,
                HR,
                Temperature,
                TreatmentGiven,
                AmbulanceNo
            FROM emergencies
            WHERE SchoolPersonID = :id
            ORDER BY IncidentDate DESC, EmergencyID DESC
            LIMIT 20
        ");
        $eStmt->execute([':id' => $schoolPersonID]);
        foreach (($eStmt->fetchAll(PDO::FETCH_ASSOC) ?: []) as $row) {
            $emergencies[] = [
                'incidentDate'     => $row['IncidentDate']     ?? null,
                'incidentTime'     => $row['IncidentTime']     ?? null,
                'incidentLocation' => $row['IncidentLocation'] ?? null,
                'bp'               => $row['BP']               ?? null,
                'hr'               => $row['HR']               ?? null,
                'rr'               => $row['RR']               ?? null,
                'temperature'      => $row['Temperature']      ?? null,
                'treatmentGiven'   => $row['TreatmentGiven']   ?? null,
                'ambulanceNo'      => $row['AmbulanceNo']      ?? null,
            ];
        }
    }
} catch (Throwable $e) {
    $emergencies = [];
}

/* ─────────────────────────────────────────────
   CERTIFICATES / ATTACHMENTS  (patient-level, across all transactions)
   Includes issuedByName via medProf JOIN on clinic_transactions
───────────────────────────────────────────── */

$certificates = [];
if (tableExists($pdo, 'consultation_attachments')) {

    $adtJoinCert   = $hasAttachDocTypes
        ? "LEFT JOIN attachment_document_types adt ON adt.DocumentTypeID = ca.DocumentTypeID"
        : '';
    $adtSelectCert = $hasAttachDocTypes
        ? "COALESCE(adt.Category, ca.AttachmentCategory, 'Medical Document') AS certificateType"
        : "COALESCE(ca.AttachmentCategory, 'Medical Document') AS certificateType";

    // Re-use staff table detection for issuedByName
    $issuedBySelect = '';
    $issuedByJoin   = '';
    if ($staffTable !== null && $medProfJoin !== '') {
        $issuedBySelect = ", CONCAT_WS(' ', mp2.FirstName, mp2.LastName) AS issuedByName";
        // Reuse same FK detection result (already determined above)
        $staffCols2 = columnsOf($pdo, $staffTable);
        $fkCol2     = null;
        foreach (['UserID', 'MedProfID', 'StaffID', 'SchoolPersonID'] as $c) {
            if (in_array($c, $staffCols2, true)) { $fkCol2 = $c; break; }
        }
        if ($fkCol2 !== null) {
            $issuedByJoin = "LEFT JOIN `{$staffTable}` mp2 ON mp2.`{$fkCol2}` = ct.MedProfID";
        }
    }

    try {
        $certStmt = $pdo->prepare("
            SELECT
                ca.AttachmentID,
                ca.FileName,
                ca.FileType,
                ca.FileSizeBytes,
                ca.Notes,
                ca.CreatedAt,
                {$adtSelectCert}
                {$issuedBySelect}
            FROM consultation_attachments ca
            {$adtJoinCert}
            JOIN clinic_transactions ct ON ct.ClinicTransactionID = ca.ClinicTransactionID
            {$issuedByJoin}
            WHERE ct.SchoolPersonID = :id
            ORDER BY ca.CreatedAt DESC
            LIMIT 50
        ");
        $certStmt->execute([':id' => $schoolPersonID]);

        foreach (($certStmt->fetchAll(PDO::FETCH_ASSOC) ?: []) as $c) {
            $aid            = (int)($c['AttachmentID'] ?? 0);
            $certificates[] = [
                'attachmentID'    => $aid,
                'certificateType' => $c['certificateType']  ?? 'Medical Document',
                'fileName'        => $c['FileName']         ?? null,
                'fileType'        => $c['FileType']         ?? null,
                'fileSizeBytes'   => isset($c['FileSizeBytes']) ? (int)$c['FileSizeBytes'] : null,
                'createdAt'       => $c['CreatedAt']        ?? null,
                'remarks'         => $c['Notes']            ?? null,
                'issuedByName'    => $c['issuedByName']     ?? null,
                'validUntil'      => null,
                'viewUrl'         => "../../ajax/records/serve_attachment.ajax.php?id={$aid}",
                'downloadUrl'     => "../../ajax/records/serve_attachment.ajax.php?id={$aid}&dl=1",
            ];
        }
    } catch (Throwable $e) {
        $certificates = [];
    }
}

/* ─────────────────────────────────────────────
   RESPONSE
───────────────────────────────────────────── */

echo json_encode([
    'ok'      => true,
    'patient' => [
        'schoolPersonID'   => (int)$person['SchoolPersonID'],
        'schoolID'         => $person['SchoolID']         ?? null,
        'firstName'        => $person['FirstName']        ?? null,
        'middleName'       => $person['MiddleName']       ?? null,
        'lastName'         => $person['LastName']         ?? null,
        'sex'              => $person['Sex']              ?? null,
        'birthday'         => null,          // not stored in school_people
        'email'            => $person['Email']            ?? null,
        'contactNumber'    => null,          // not stored in school_people
        'personType'       => $person['PersonType']       ?? null,
        'program'          => $person['program']          ?? null,
        'department'       => $person['department']       ?? null,
        'yearSection'      => $person['Semester']         ?? null,
        'enrollmentStatus' => $person['EnrollmentStatus'] ?? null,
        'academicYear'     => $person['AcademicYear']     ?? null,
    ],
    'diseases'     => $diseases,
    'transactions' => $transactions,
    'emergencies'  => $emergencies,
    'certificates' => $certificates,
], JSON_UNESCAPED_UNICODE);