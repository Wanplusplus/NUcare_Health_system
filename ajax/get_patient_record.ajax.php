<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = require __DIR__ . '/../config/db_pdo.php';
require_once __DIR__ . '/../includes/patients_info_helpers.php';

$schoolPersonID = (int)($_GET['school_person_id'] ?? $_GET['schoolpersonid'] ?? $_GET['id'] ?? 0);

if ($schoolPersonID <= 0) {
    echo json_encode(['ok' => false, 'message' => 'Invalid school_person_id.'], JSON_UNESCAPED_UNICODE);
    exit;
}

function latestColumnName(PDO $pdo, string $table, array $candidates): ?string
{
    try {
        $stmt = $pdo->query('SHOW COLUMNS FROM ' . $table);
        $columns = array_map(static fn(array $row): string => (string)$row['Field'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Throwable $e) {
        return null;
    }

    foreach ($candidates as $candidate) {
        if (in_array($candidate, $columns, true)) {
            return $candidate;
        }
    }

    return null;
}

function buildFullName(array $row): string
{
    $parts = array_filter([
        trim((string)($row['FirstName'] ?? '')),
        !empty($row['MiddleName']) ? substr(trim((string)$row['MiddleName']), 0, 1) . '.' : '',
        trim((string)($row['LastName'] ?? '')),
    ]);

    return trim(implode(' ', $parts));
}

function buildInClause(array $ids, string $prefix): array
{
    $placeholders = [];
    $params = [];

    foreach (array_values($ids) as $index => $id) {
        $key = ':' . $prefix . $index;
        $placeholders[] = $key;
        $params[$key] = (int)$id;
    }

    return [$placeholders, $params];
}

function attachmentServeUrl(int $attachmentID, bool $download = false): string
{
    return '../../ajax/consultation/serve_attachment.ajax.php?id=' . $attachmentID . ($download ? '&dl=1' : '');
}

function formatYearSection(?string $academicYear, ?string $semester): string
{
    $academicYear = trim((string)$academicYear);
    $semester = trim((string)$semester);

    if ($academicYear === '' && $semester === '') {
        return '';
    }

    if ($academicYear !== '' && $semester !== '') {
        return $academicYear . ' • ' . $semester;
    }

    return $academicYear !== '' ? $academicYear : $semester;
}

function normalizeStudentStatus(?string $status): ?string
{
    $value = strtolower(trim((string)$status));
    if ($value === '') {
        return null;
    }

    return $value === 'enrolled' ? 'Active' : 'Inactive';
}

function normalizeEmploymentStatus(?string $status): ?string
{
    $value = strtolower(trim((string)$status));
    if ($value === '') {
        return null;
    }

    return $value === 'employed' ? 'Active' : 'Inactive';
}

function attachmentLabel(array $row): string
{
    $documentType = trim((string)($row['DocumentType'] ?? ''));
    if ($documentType !== '') {
        return $documentType;
    }

    $category = trim((string)($row['DocumentCategory'] ?? ''));
    if ($category !== '') {
        return $category;
    }

    $attachmentCategory = trim((string)($row['AttachmentCategory'] ?? ''));
    if ($attachmentCategory !== '') {
        return $attachmentCategory;
    }

    return 'Attachment';
}

function isCertificateAttachment(array $row): bool
{
    $text = strtolower(trim(
        (string)($row['DocumentCategory'] ?? '') . ' ' .
        (string)($row['DocumentType'] ?? '') . ' ' .
        (string)($row['AttachmentCategory'] ?? '')
    ));

    return str_contains($text, 'medical certificate')
        || str_contains($text, 'certificate')
        || str_contains($text, 'clearance');
}

try {
    $birthdayColumn = latestColumnName($pdo, 'school_people', ['Birthday', 'BirthDate', 'BirthDay']);
    $contactColumn = latestColumnName($pdo, 'school_people', ['ContactNumber', 'ContactNo', 'Phone', 'Mobile']);

    $birthdaySelect = $birthdayColumn ? 'sp.`' . $birthdayColumn . '` AS Birthday' : 'NULL AS Birthday';
    $contactSelect = $contactColumn ? 'sp.`' . $contactColumn . '` AS ContactNumber' : 'NULL AS ContactNumber';

    $personStmt = $pdo->prepare("
        SELECT
            sp.SchoolPersonID,
            sp.SchoolID,
            sp.FirstName,
            sp.MiddleName,
            sp.LastName,
            sp.Email,
            sp.PersonType,
            sp.Sex,
            {$birthdaySelect},
            {$contactSelect},

            se.ProgramID,
            se.AcademicYear,
            se.Semester,
            se.EnrollmentStatus,
            pr.ProgramName,
            pr.Department AS StudentDepartment,

            ea.Department AS EmployeeDepartment,
            ea.PositionTitle,
            ea.EmploymentStatus,
            u.UserID
        FROM school_people sp
        LEFT JOIN users u
            ON u.SchoolPersonID = sp.SchoolPersonID
        LEFT JOIN (
            SELECT se1.*
            FROM student_enrollments se1
            INNER JOIN (
                SELECT SchoolPersonID, MAX(EnrollmentID) AS EnrollmentID
                FROM student_enrollments
                GROUP BY SchoolPersonID
            ) latest_enrollment
                ON latest_enrollment.EnrollmentID = se1.EnrollmentID
        ) se
            ON se.SchoolPersonID = sp.SchoolPersonID
        LEFT JOIN programs pr
            ON pr.ProgramID = se.ProgramID
        LEFT JOIN (
            SELECT ea1.*
            FROM employee_assignments ea1
            INNER JOIN (
                SELECT SchoolPersonID, MAX(AssignmentID) AS AssignmentID
                FROM employee_assignments
                GROUP BY SchoolPersonID
            ) latest_assignment
                ON latest_assignment.AssignmentID = ea1.AssignmentID
        ) ea
            ON ea.SchoolPersonID = sp.SchoolPersonID
        WHERE sp.SchoolPersonID = :id
        LIMIT 1
    ");
    $personStmt->execute([':id' => $schoolPersonID]);
    $person = $personStmt->fetch(PDO::FETCH_ASSOC);

    if (!$person) {
        echo json_encode(['ok' => false, 'message' => 'Patient not found.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'message' => 'Failed to load patient profile.'], JSON_UNESCAPED_UNICODE);
    exit;
}

/* Known medical conditions (derived from clinic_transactions; read-only) */
// SPEC NOTE: Only tables listed in the 3NF rules may be used.
// This schema does not provide an allowed "diseases" mapping table for known medical conditions.
// Derive a lightweight set from clinic_transactions.complaint.
$diseases = [];
try {
    $dStmt = $pdo->prepare("
        SELECT
            ct.Complaint AS diseaseName,
            NULL AS notes
        FROM clinic_transactions ct
        WHERE ct.SchoolPersonID = :id
          AND ct.Complaint IS NOT NULL
          AND TRIM(ct.Complaint) <> ''
        ORDER BY ct.ClinicTransactionID DESC
        LIMIT 12
    ");
    $dStmt->execute([':id' => $schoolPersonID]);
    $dRows = $dStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $seen = [];
    foreach ($dRows as $r) {
        $name = trim((string)($r['diseaseName'] ?? ''));
        if ($name === '') continue;
        $key = mb_strtolower($name);
        if (isset($seen[$key])) continue;
        $seen[$key] = true;
        $diseases[] = [
            'diseaseName' => $name,
            'notes' => $r['notes'] ?? null,
        ];
    }
} catch (Throwable $e) {
    $diseases = [];
}

/* Emergencies (derived from clinic_transactions; read-only) */
$emergencies = [];
try {
    $eStmt = $pdo->prepare("
        SELECT
            ct.ClinicTransactionID AS EmergencyID,
            ct.VisitDate AS IncidentDate,
            NULL AS IncidentTime,
            NULL AS IncidentLocation,
            pe.BloodPressure AS BP,
            NULL AS RR,
            pe.PulseRate AS HR,
            pe.Temperature AS Temperature,
            ct.Notes AS TreatmentGiven,
            NULL AS AmbulanceNo,
            NULL AS TimeDispatched,
            NULL AS TimeArrived,
            ct.CreatedAt AS CreatedAt
        FROM clinic_transactions ct
        LEFT JOIN physical_examinations pe ON pe.ClinicTransactionID = ct.ClinicTransactionID
        WHERE ct.SchoolPersonID = :id
          AND (
            LOWER(ct.ServiceType) LIKE '%emergency%'
            OR LOWER(ct.ServiceType) LIKE '%first aid%'
            OR LOWER(ct.Complaint) LIKE '%emergency%'
            OR LOWER(ct.Complaint) LIKE '%first aid%'
          )
        ORDER BY ct.VisitDate DESC, ct.ClinicTransactionID DESC
        LIMIT 20
    ");
    $eStmt->execute([':id' => $schoolPersonID]);

    foreach (($eStmt->fetchAll(PDO::FETCH_ASSOC) ?: []) as $row) {
        $emergencies[] = [
            'emergencyID' => (int)($row['EmergencyID'] ?? 0),
            'incidentDate' => $row['IncidentDate'] ?? null,
            'incidentTime' => $row['IncidentTime'] ?? null,
            'incidentLocation' => $row['IncidentLocation'] ?? null,
            'bp' => $row['BP'] ?? null,
            'rr' => $row['RR'] ?? null,
            'hr' => $row['HR'] ?? null,
            'temperature' => $row['Temperature'] ?? null,
            'treatmentGiven' => $row['TreatmentGiven'] ?? null,
            'ambulanceNo' => $row['AmbulanceNo'] ?? null,
            'timeDispatched' => $row['TimeDispatched'] ?? null,
            'timeArrived' => $row['TimeArrived'] ?? null,
            'createdAt' => $row['CreatedAt'] ?? null,
        ];
    }
} catch (Throwable $e) {
    $emergencies = [];
}


/* Clinic history + physical exam */
$transactions = [];
$txRows = [];

try {
    $txStmt = $pdo->prepare("
        SELECT
            ct.ClinicTransactionID,
            ct.BookingID,
            ct.VisitDate,
            ct.CreatedAt,
            ct.ServiceType,
            ct.ConsultationStatus,
            ct.Complaint,
            ct.Notes,
            ct.MedProfID,
            COALESCE(
                NULLIF(TRIM(CONCAT_WS(' ',
                    msp.FirstName,
                    CASE
                        WHEN msp.MiddleName IS NOT NULL AND msp.MiddleName <> ''
                            THEN CONCAT(LEFT(msp.MiddleName, 1), '.')
                        ELSE NULL
                    END,
                    msp.LastName
                )), ''),
                CONCAT('Medical Professional #', ct.MedProfID)
            ) AS medProfName,

            pe.PhysicalExamID,
            pe.ExamDate,
            pe.Height,
            pe.Weight,
            pe.BloodPressure,
            pe.PulseRate,
            pe.Ears,
            pe.EyesPupil,
            pe.Heart,
            pe.Nose,
            pe.Thorax,
            pe.Abdomen,
            pe.Lungs,
            pe.Skin,
            pe.Extremities,
            pe.Deformities,
            pe.CardioClearance
        FROM clinic_transactions ct
        LEFT JOIN medical_professionals mp
            ON mp.MedProfID = ct.MedProfID
        LEFT JOIN users mu
            ON mu.UserID = mp.UserID
        LEFT JOIN school_people msp
            ON msp.SchoolPersonID = mu.SchoolPersonID
        LEFT JOIN physical_examinations pe
            ON pe.ClinicTransactionID = ct.ClinicTransactionID
        WHERE ct.SchoolPersonID = :id
        ORDER BY ct.VisitDate DESC, ct.ClinicTransactionID DESC
        LIMIT 100
    ");
    $txStmt->execute([':id' => $schoolPersonID]);
    $txRows = $txStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $txRows = [];
}

$transactionIds = array_values(array_filter(array_map(
    static fn(array $row): int => (int)($row['ClinicTransactionID'] ?? 0),
    $txRows
)));

$medicinesByTransaction = [];
if (!empty($transactionIds)) {
    try {
        [$placeholders, $params] = buildInClause($transactionIds, 'med');
        $medSql = "
            SELECT
                md.ClinicTransactionID,
                md.DispensingID,
                md.QuantityDispensed,
                md.Instructions,
                md.DispensedAt,
                mi.InventoryID,
                m.MedicineID,
                m.MedicineName,
                m.GenericName,
                m.Dosage,
                m.Unit
            FROM medicine_dispensing md
            JOIN medicine_inventory mi
                ON mi.InventoryID = md.InventoryID
            JOIN medicines m
                ON m.MedicineID = mi.MedicineID
            WHERE md.ClinicTransactionID IN (" . implode(', ', $placeholders) . ")
            ORDER BY md.DispensedAt ASC, md.DispensingID ASC
        ";
        $medStmt = $pdo->prepare($medSql);
        $medStmt->execute($params);

        foreach (($medStmt->fetchAll(PDO::FETCH_ASSOC) ?: []) as $row) {
            $ctid = (int)($row['ClinicTransactionID'] ?? 0);
            $medicinesByTransaction[$ctid][] = [
                'dispensingID' => (int)($row['DispensingID'] ?? 0),
                'inventoryID' => isset($row['InventoryID']) ? (int)$row['InventoryID'] : null,
                'medicineID' => isset($row['MedicineID']) ? (int)$row['MedicineID'] : null,
                'medicineName' => $row['MedicineName'] ?? null,
                'genericName' => $row['GenericName'] ?? null,
                'dosage' => $row['Dosage'] ?? null,
                'unit' => $row['Unit'] ?? null,
                'quantityDispensed' => isset($row['QuantityDispensed']) ? (int)$row['QuantityDispensed'] : null,
                'instructions' => $row['Instructions'] ?? null,
                'dispensedAt' => $row['DispensedAt'] ?? null,
                'viewUrl' => null,
                'downloadUrl' => null,
            ];
        }
    } catch (Throwable $e) {
        $medicinesByTransaction = [];
    }
}

$attachmentsByTransaction = [];
if (!empty($transactionIds)) {
    try {
        [$placeholders, $params] = buildInClause($transactionIds, 'att');
        $attachSql = "
            SELECT
                ca.AttachmentID,
                ca.ClinicTransactionID,
                ca.FileName,
                ca.StoredName,
                ca.FilePath,
                ca.FileType,
                ca.FileSizeBytes,
                ca.AttachmentCategory,
                ca.DocumentTypeID,
                adt.Category AS DocumentCategory,
                adt.DocumentType,
                ca.Notes,
                ca.CreatedAt
            FROM consultation_attachments ca
            LEFT JOIN attachment_document_types adt
                ON adt.DocumentTypeID = ca.DocumentTypeID
            WHERE ca.ClinicTransactionID IN (" . implode(', ', $placeholders) . ")
            ORDER BY ca.CreatedAt ASC, ca.AttachmentID ASC
        ";
        $attachStmt = $pdo->prepare($attachSql);
        $attachStmt->execute($params);

        foreach (($attachStmt->fetchAll(PDO::FETCH_ASSOC) ?: []) as $row) {
            $ctid = (int)($row['ClinicTransactionID'] ?? 0);
            $attachmentID = (int)($row['AttachmentID'] ?? 0);

            $attachment = [
                'attachmentID' => $attachmentID,
                'clinicTransactionID' => $ctid,
                'fileName' => $row['FileName'] ?? null,
                'storedName' => $row['StoredName'] ?? null,
                'filePath' => $row['FilePath'] ?? null,
                'fileType' => $row['FileType'] ?? null,
                'fileSizeBytes' => isset($row['FileSizeBytes']) ? (int)$row['FileSizeBytes'] : null,
                'attachmentCategory' => $row['AttachmentCategory'] ?? null,
                'documentTypeID' => isset($row['DocumentTypeID']) ? (int)$row['DocumentTypeID'] : null,
                'documentCategory' => $row['DocumentCategory'] ?? null,
                'documentType' => $row['DocumentType'] ?? null,
                'notes' => $row['Notes'] ?? null,
                'createdAt' => $row['CreatedAt'] ?? null,
                'viewUrl' => attachmentServeUrl($attachmentID),
                'downloadUrl' => attachmentServeUrl($attachmentID, true),
                'certificateType' => attachmentLabel($row),
            ];

            $attachmentsByTransaction[$ctid][] = $attachment;
        }
    } catch (Throwable $e) {
        $attachmentsByTransaction = [];
    }
}

foreach ($txRows as $tx) {
    $ctid = (int)($tx['ClinicTransactionID'] ?? 0);

    $physicalExam = null;
    $hasPhysicalExam = !empty($tx['PhysicalExamID'])
        || !empty($tx['ExamDate'])
        || !empty($tx['Height'])
        || !empty($tx['Weight'])
        || !empty($tx['BloodPressure'])
        || !empty($tx['PulseRate'])
        || !empty($tx['Ears'])
        || !empty($tx['EyesPupil'])
        || !empty($tx['Heart'])
        || !empty($tx['Nose'])
        || !empty($tx['Thorax'])
        || !empty($tx['Abdomen'])
        || !empty($tx['Lungs'])
        || !empty($tx['Skin'])
        || !empty($tx['Extremities'])
        || !empty($tx['Deformities'])
        || !empty($tx['CardioClearance']);

    if ($hasPhysicalExam) {
        $physicalExam = [
            'physicalExamID' => isset($tx['PhysicalExamID']) ? (int)$tx['PhysicalExamID'] : null,
            'examDate' => $tx['ExamDate'] ?? null,
            'height' => isset($tx['Height']) ? (float)$tx['Height'] : null,
            'weight' => isset($tx['Weight']) ? (float)$tx['Weight'] : null,
            'bloodPressure' => $tx['BloodPressure'] ?? null,
            'pulseRate' => isset($tx['PulseRate']) ? (int)$tx['PulseRate'] : null,
            'ears' => $tx['Ears'] ?? null,
            'eyesPupil' => $tx['EyesPupil'] ?? null,
            'heart' => $tx['Heart'] ?? null,
            'nose' => $tx['Nose'] ?? null,
            'thorax' => $tx['Thorax'] ?? null,
            'abdomen' => $tx['Abdomen'] ?? null,
            'lungs' => $tx['Lungs'] ?? null,
            'skin' => $tx['Skin'] ?? null,
            'extremities' => $tx['Extremities'] ?? null,
            'deformities' => $tx['Deformities'] ?? null,
            'cardioClearance' => $tx['CardioClearance'] ?? null,
            'remarks' => null,
        ];
    }

    $transactions[] = [
        'clinicTransactionID' => $ctid,
        'bookingID' => isset($tx['BookingID']) ? (int)$tx['BookingID'] : null,
        'visitDate' => $tx['VisitDate'] ?? null,
        'createdAt' => $tx['CreatedAt'] ?? null,
        'serviceType' => $tx['ServiceType'] ?? null,
        'status' => $tx['ConsultationStatus'] ?? null,
        'consultationStatus' => $tx['ConsultationStatus'] ?? null,
        'complaint' => $tx['Complaint'] ?? null,
        'notes' => $tx['Notes'] ?? null,
        'medProfID' => isset($tx['MedProfID']) ? (int)$tx['MedProfID'] : null,
        'medProfName' => $tx['medProfName'] ?? null,
        'physicalExam' => $physicalExam,
        'medicines' => $medicinesByTransaction[$ctid] ?? [],
        'attachments' => $attachmentsByTransaction[$ctid] ?? [],
    ];
}

$attachmentsFlat = [];
foreach ($attachmentsByTransaction as $ctid => $attachments) {
    $tx = null;
    foreach ($transactions as $candidate) {
        if ((int)$candidate['clinicTransactionID'] === (int)$ctid) {
            $tx = $candidate;
            break;
        }
    }

    foreach ($attachments as $attachment) {
        $attachmentsFlat[] = [
            'attachmentID' => $attachment['attachmentID'],
            'clinicTransactionID' => $attachment['clinicTransactionID'],
            'certificateType' => $attachment['certificateType'],
            'fileName' => $attachment['fileName'],
            'storedName' => $attachment['storedName'],
            'filePath' => $attachment['filePath'],
            'fileType' => $attachment['fileType'],
            'fileSizeBytes' => $attachment['fileSizeBytes'],
            'attachmentCategory' => $attachment['attachmentCategory'],
            'documentTypeID' => $attachment['documentTypeID'],
            'documentCategory' => $attachment['documentCategory'],
            'documentType' => $attachment['documentType'],
            'notes' => $attachment['notes'],
            'createdAt' => $attachment['createdAt'],
            'visitDate' => $tx['visitDate'] ?? null,
            'serviceType' => $tx['serviceType'] ?? null,
            'consultationStatus' => $tx['consultationStatus'] ?? null,
            'issuedByName' => $tx['medProfName'] ?? null,
            'viewUrl' => $attachment['viewUrl'],
            'downloadUrl' => $attachment['downloadUrl'],
        ];
    }
}

$personType = (string)($person['PersonType'] ?? '');
$personUserID = (int)($person['UserID'] ?? 0);
$isStudent = $personType === 'Student';
$isEmployee = $personType === 'Faculty' || $personType === 'Staff';

$program = null;
$department = null;
$positionTitle = null;
$academicYear = null;
$semester = null;
$enrollmentStatus = null;
$employmentStatus = null;
$status = null;

if ($isStudent) {
    $program = $person['ProgramName'] ?? null;
    $department = $person['StudentDepartment'] ?? null;
    $academicYear = $person['AcademicYear'] ?? null;
    $semester = $person['Semester'] ?? null;
    $enrollmentStatus = $person['EnrollmentStatus'] ?? null;
    $status = normalizeStudentStatus($person['EnrollmentStatus'] ?? null);
} elseif ($isEmployee) {
    $program = $person['PositionTitle'] ?? null;
    $department = $person['EmployeeDepartment'] ?? null;
    $positionTitle = $person['PositionTitle'] ?? null;
    $employmentStatus = $person['EmploymentStatus'] ?? null;
    $status = normalizeEmploymentStatus($person['EmploymentStatus'] ?? null);
}

echo json_encode([
    'ok' => true,
    'patient' => [
        'schoolPersonID' => (int)($person['SchoolPersonID'] ?? 0),
        'schoolID' => $person['SchoolID'] ?? null,
        'firstName' => $person['FirstName'] ?? null,
        'middleName' => $person['MiddleName'] ?? null,
        'lastName' => $person['LastName'] ?? null,
        'fullName' => buildFullName($person),
        'sex' => $person['Sex'] ?? null,
        'birthday' => $person['Birthday'] ?? null,
        'email' => $person['Email'] ?? null,
        'contactNumber' => $person['ContactNumber'] ?? null,
        'personType' => $personType,
        'program' => $program,
        'department' => $department,
        'positionTitle' => $positionTitle,
        'yearSection' => $isStudent ? formatYearSection($academicYear, $semester) : '',
        'academicYear' => $academicYear,
        'semester' => $semester,
        'enrollmentStatus' => $enrollmentStatus,
        'employmentStatus' => $employmentStatus,
        'status' => $status,
        'patientsInfo' => $personUserID > 0 ? patientsInfoLoad($pdo, $personUserID) : patientsInfoEmpty(),
    ],
    'diseases' => $diseases,
    'transactions' => $transactions,
    'emergencies' => $emergencies,
    'certificates' => $attachmentsFlat,
    'medicalProfile' => array_values(array_filter(array_map(
        static function (array $tx): ?array {
            $serviceType = trim((string)($tx['serviceType'] ?? ''));
            $isPhysicalExam = stripos($serviceType, 'physical') !== false;

            if (!$isPhysicalExam || empty($tx['physicalExam'])) {
                return null;
            }

            return array_merge($tx['physicalExam'], [
                'clinicTransactionID' => $tx['clinicTransactionID'],
                'visitDate' => $tx['visitDate'],
                'serviceType' => $tx['serviceType'],
                'status' => $tx['status'],
            ]);
        },
        $transactions
    ))),
    'familyHistory' => $personUserID > 0 ? familyHistoryLoad($pdo, $personUserID) : [],
    'stats' => [
        'totalVisits' => count($transactions),
        'emergencies' => count($emergencies),
        'certificates' => count($attachmentsFlat),
        'lastVisit' => $transactions[0]['visitDate'] ?? null,
    ],
], JSON_UNESCAPED_UNICODE);
