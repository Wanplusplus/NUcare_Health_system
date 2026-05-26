<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = require __DIR__ . '/../../config/db_pdo.php';

$schoolPersonID = (int)($_GET['school_person_id'] ?? 0);
if ($schoolPersonID <= 0) {
    echo json_encode(['ok' => false, 'message' => 'Invalid school_person_id']);
    exit;
}

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
        if (tableExists($pdo, $table)) {
            return $table;
        }
    }
    return null;
}

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
            se.YearLevel,
            se.Section,
            se.AcademicYear,
            se.EnrollmentStatus,
            pr.ProgramName AS program,
            pr.Department AS department
        FROM school_people sp
        LEFT JOIN student_enrollments se
            ON se.SchoolPersonID = sp.SchoolPersonID
            AND se.EnrollmentID = (
                SELECT MAX(se2.EnrollmentID)
                FROM student_enrollments se2
                WHERE se2.SchoolPersonID = sp.SchoolPersonID
            )
        LEFT JOIN programs pr
            ON pr.ProgramID = se.ProgramID
        WHERE sp.SchoolPersonID = :id
        LIMIT 1
    ";

    $personStmt = $pdo->prepare($personSql);
    $personStmt->execute([':id' => $schoolPersonID]);
    $person = $personStmt->fetch(PDO::FETCH_ASSOC);

    if (!$person) {
        echo json_encode(['ok' => false, 'message' => 'Patient not found.']);
        exit;
    }
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'message' => 'Failed to load patient profile.', 'debug' => $e->getMessage()]);
    exit;
}

$diseases = [];
$diseaseTable = firstExistingTable($pdo, [
    'patient_diseases',
    'person_diseases',
    'school_person_diseases',
    'health_conditions'
]);

if ($diseaseTable !== null) {
    try {
        $diseaseSql = "
            SELECT
                d.DiseaseName AS diseaseName,
                pd.Notes AS notes
            FROM `{$diseaseTable}` pd
            JOIN diseases d ON d.DiseaseID = pd.DiseaseID
            WHERE pd.SchoolPersonID = :id
            ORDER BY d.DiseaseName ASC
        ";
        $diseaseStmt = $pdo->prepare($diseaseSql);
        $diseaseStmt->execute([':id' => $schoolPersonID]);
        $diseases = $diseaseStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        $diseases = [];
    }
}

$peTable = 'physical_examinations';
$peCols = columnsOf($pdo, $peTable);

$wantedVitals = ['BloodPressure', 'Temperature', 'PulseRate', 'Weight', 'Height'];
$safeVitals = array_values(array_filter($wantedVitals, fn($c) => in_array($c, $peCols, true)));
$peSelect = empty($safeVitals)
    ? ''
    : ', ' . implode(', ', array_map(fn($c) => "pe.`{$c}`", $safeVitals));

$staffTable = firstExistingTable($pdo, ['medical_professionals', 'staff_profiles', 'clinic_staff']);
$medProfSelect = '';
$medProfJoin = '';

if ($staffTable !== null) {
    $staffCols = columnsOf($pdo, $staffTable);
    if (in_array('UserID', $staffCols, true) && in_array('FirstName', $staffCols, true) && in_array('LastName', $staffCols, true)) {
        $medProfSelect = ", CONCAT_WS(' ', mp.FirstName, mp.LastName) AS medProfName";
        $medProfJoin = " LEFT JOIN `{$staffTable}` mp ON mp.UserID = ct.CreatedByUserID ";
    }
}

$transactions = [];

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
        LEFT JOIN physical_examinations pe
            ON pe.ClinicTransactionID = ct.ClinicTransactionID
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

$medStmt = null;
try {
    $medSql = "
        SELECT
            m.MedicineName AS medicineName,
            md.QuantityDispensed AS qty
        FROM medicine_dispensing md
        JOIN medicine_inventory mi ON mi.InventoryID = md.InventoryID
        JOIN medicines m ON m.MedicineID = mi.MedicineID
        WHERE md.ClinicTransactionID = :ctid
        ORDER BY md.DispensedAt ASC
    ";
    $medStmt = $pdo->prepare($medSql);
} catch (Throwable $e) {
    $medStmt = null;
}

foreach ($rawTransactions as $tx) {
    $ctid = (int)($tx['ClinicTransactionID'] ?? 0);
    $medicines = [];

    if ($medStmt && $ctid > 0) {
        try {
            $medStmt->execute([':ctid' => $ctid]);
            $medicines = $medStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            $medicines = [];
        }
    }

    $record = [
        'visitDate' => $tx['VisitDate'] ?? null,
        'createdAt' => $tx['CreatedAt'] ?? null,
        'serviceType' => $tx['ServiceType'] ?? 'General Consultation',
        'consultationStatus' => $tx['ConsultationStatus'] ?? '',
        'complaint' => $tx['Complaint'] ?? null,
        'notes' => $tx['Notes'] ?? null,
        'medProfName' => $tx['medProfName'] ?? null,
        'medicines' => $medicines,
    ];

    foreach ($safeVitals as $v) {
        $camel = lcfirst($v);
        $record[$camel] = $tx[$v] ?? null;
    }

    $transactions[] = $record;
}

$emergencies = [];
$emergencyTable = firstExistingTable($pdo, [
    'emergency_records',
    'clinic_emergencies',
    'emergency_consultations'
]);

if ($emergencyTable !== null) {
    try {
        $eStmt = $pdo->prepare("
            SELECT *
            FROM `{$emergencyTable}`
            WHERE SchoolPersonID = :id
            ORDER BY IncidentDate DESC, EmergencyID DESC
            LIMIT 20
        ");
        $eStmt->execute([':id' => $schoolPersonID]);

        foreach (($eStmt->fetchAll(PDO::FETCH_ASSOC) ?: []) as $row) {
            $emergencies[] = [
                'incidentDate' => $row['IncidentDate'] ?? $row['incidentDate'] ?? null,
                'incidentTime' => $row['IncidentTime'] ?? $row['incidentTime'] ?? null,
                'incidentLocation' => $row['Location'] ?? $row['IncidentLocation'] ?? $row['incidentLocation'] ?? null,
                'bp' => $row['BloodPressure'] ?? $row['BP'] ?? null,
                'hr' => $row['HeartRate'] ?? $row['HR'] ?? null,
                'rr' => $row['RespiratoryRate'] ?? $row['RR'] ?? null,
                'temperature' => $row['Temperature'] ?? null,
                'treatmentGiven' => $row['TreatmentGiven'] ?? $row['Treatment'] ?? null,
                'ambulanceNo' => $row['AmbulanceNumber'] ?? $row['AmbulanceNo'] ?? null,
            ];
        }
    } catch (Throwable $e) {
        $emergencies = [];
    }
}

$certificates = [];

try {
    $certSql = "
        SELECT
            ca.AttachmentID,
            ca.FileName,
            ca.FileType,
            ca.FileSizeBytes,
            ca.Notes,
            ca.CreatedAt,
            COALESCE(adt.Category, 'Medical Document') AS certificateType
        FROM consultation_attachments ca
        LEFT JOIN attachment_document_types adt
            ON adt.DocumentTypeID = ca.DocumentTypeID
        JOIN clinic_transactions ct
            ON ct.ClinicTransactionID = ca.ClinicTransactionID
        WHERE ct.SchoolPersonID = :id
        ORDER BY ca.CreatedAt DESC
        LIMIT 30
    ";
    $certStmt = $pdo->prepare($certSql);
    $certStmt->execute([':id' => $schoolPersonID]);
    $certRows = $certStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($certRows as $c) {
        $aid = (int)($c['AttachmentID'] ?? 0);
        $certificates[] = [
            'attachmentID' => $aid,
            'certificateType' => $c['certificateType'] ?: 'Medical Document',
            'fileName' => $c['FileName'] ?? null,
            'fileType' => $c['FileType'] ?? null,
            'fileSizeBytes' => $c['FileSizeBytes'] ?? null,
            'createdAt' => $c['CreatedAt'] ?? null,
            'remarks' => $c['Notes'] ?? null,
            'issuedByName' => null,
            'validUntil' => null,
            'viewUrl' => "../../ajax/records/serve_attachment.ajax.php?id={$aid}",
            'downloadUrl' => "../../ajax/records/serve_attachment.ajax.php?id={$aid}&dl=1",
        ];
    }
} catch (Throwable $e) {
    $certificates = [];
}

$yearSection = null;
if (!empty($person['YearLevel']) || !empty($person['Section'])) {
    $yearSection = trim(($person['YearLevel'] ?? '') . (!empty($person['Section']) ? ' - ' . $person['Section'] : ''));
}

$patient = [
    'schoolPersonID' => (int)$person['SchoolPersonID'],
    'schoolID' => $person['SchoolID'] ?? null,
    'firstName' => $person['FirstName'] ?? null,
    'middleName' => $person['MiddleName'] ?? null,
    'lastName' => $person['LastName'] ?? null,
    'sex' => $person['Sex'] ?? null,
    'birthday' => null,
    'email' => $person['Email'] ?? null,
    'contactNumber' => null,
    'personType' => $person['PersonType'] ?? null,
    'program' => $person['program'] ?? null,
    'department' => $person['department'] ?? null,
    'yearSection' => $yearSection,
    'enrollmentStatus' => !empty($person['EnrollmentStatus']) ? $person['EnrollmentStatus'] : 'Active',
    'academicYear' => $person['AcademicYear'] ?? null,
];

echo json_encode([
    'ok' => true,
    'patient' => $patient,
    'diseases' => $diseases,
    'transactions' => $transactions,
    'emergencies' => $emergencies,
    'certificates' => $certificates,
], JSON_UNESCAPED_UNICODE);