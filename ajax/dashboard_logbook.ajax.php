<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['UserID'])) {
    echo json_encode(['ok' => false, 'message' => 'Unauthorized.']);
    exit;
}

$pdo = require __DIR__ . '/../config/db_pdo.php';

function normalizeKey(string $value): string
{
    $value = strtolower(trim($value));
    return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
}

function bucketPersonCategory(array $row): string
{
    $personType = strtolower(trim((string)($row['PersonType'] ?? '')));

    if ($personType === 'student') {
        return 'Student';
    }

    if ($personType === 'faculty') {
        return 'Faculty';
    }

    if ($personType === 'staff') {
        return 'Staff';
    }

    // Guards, visitors, ROMAC, and any other allowed non-student
    // consults are grouped under ASP for the logbook.
    return 'ASP';
}

function classifyConsultationRow(array $row): ?string
{
    $service = strtolower(trim((string)($row['ServiceType'] ?? '')));
    $complaint = strtolower(trim((string)($row['Complaint'] ?? '')));
    $notes = strtolower(trim((string)($row['Notes'] ?? '')));
    $haystack = trim($service . ' ' . $complaint . ' ' . $notes);

    if ($haystack === '') {
        return null;
    }

    if (str_contains($service, 'physical')) {
        return 'PE';
    }

    if (str_contains($service, 'dental')) {
        return '__DENTAL__';
    }

    if (preg_match('/viral|flu|fever|cold|influenza|cough with fever/i', $haystack)) {
        return 'Systemic Viral Illness';
    }

    if (preg_match('/cardio|heart|hypertension|high blood pressure|palpitation|chest pain/i', $haystack)) {
        return 'Cardiovascular Problems';
    }

    if (preg_match('/respiratory|cough|asthma|wheeze|bronch|shortness of breath|sore throat/i', $haystack)) {
        return 'Respiratory Problems';
    }

    if (preg_match('/gastro|stomach|abdominal|diarrh|vomit|nausea|gastr|constipation/i', $haystack)) {
        return 'GastroIntestinal Problems';
    }

    if (preg_match('/gyne|menstru|dysmen|vaginal|pregnan|ob[- ]?gyn/i', $haystack)) {
        return 'Gynecologic Problems';
    }

    if (preg_match('/allerg|hypersens|hives|rash|itch/i', $haystack)) {
        return 'Allergy/Hypersensitivity Problems';
    }

    if (preg_match('/infect|uti|wound infection|abscess|sepsis/i', $haystack)) {
        return 'Infectious Problems';
    }

    if (preg_match('/trauma|accident|sprain|fracture|injury|bruise|laceration|cut/i', $haystack)) {
        return 'Minor Accidents / Trauma';
    }

    return '__DAILY__';
}

function addBucket(array &$matrix, string $rowName, string $bucket, int $qty = 1): void
{
    if (!isset($matrix[$rowName])) {
        $matrix[$rowName] = ['Student' => 0, 'Faculty' => 0, 'Staff' => 0, 'ASP' => 0];
    }

    if (!isset($matrix[$rowName][$bucket])) {
        $matrix[$rowName][$bucket] = 0;
    }

    $matrix[$rowName][$bucket] += $qty;
}

$month = (int)($_GET['month'] ?? date('n'));
$year = (int)($_GET['year'] ?? date('Y'));

if ($month < 1 || $month > 12) {
    $month = (int)date('n');
}
if ($year < 2000 || $year > 2100) {
    $year = (int)date('Y');
}

$start = sprintf('%04d-%02d-01', $year, $month);
$end = (new DateTimeImmutable($start))->modify('first day of next month')->format('Y-m-d');

$serviceRows = [
    'PE',
    'Systemic Viral Illness',
    'Cardiovascular Problems',
    'Respiratory Problems',
    'GastroIntestinal Problems',
    'Gynecologic Problems',
    'Allergy/Hypersensitivity Problems',
    'Infectious Problems',
    'Minor Accidents / Trauma',
];

$medicineRows = [
    'Ambroxol',
    'Biogesic',
    'Buscopan',
    'Cetirizine',
    'Clonidine',
    'Diatabs',
    'Domperidone',
    'Gaviscon',
    'Ibuprofen',
    'Kremil-S',
    'Lozenges',
    'Mefenamic Acid',
    'Neozep',
    'ORS',
    'Sinecod',
    'Serc',
    'Ventolin Nebules',
    'Prednisone',
    'Benadryl',
    'Diphenhydramine',
    'Norgesic Forte',
];

$supplyRows = [
    'Betadine',
    'Cotton Balls',
    'Cotton Buds',
    'Elastic Bandage',
    'Gauze',
];

$consultations = [];
try {
    $stmt = $pdo->prepare("
        SELECT
            ct.ClinicTransactionID,
            ct.VisitDate,
            ct.ServiceType,
            ct.Complaint,
            ct.Notes,
            ct.ConsultationStatus,
            sp.PersonType,
            sp.SchoolID,
            se.ProgramID,
            se.AcademicYear,
            se.Semester,
            pr.ProgramName,
            pr.Department AS StudentDepartment
        FROM clinic_transactions ct
        INNER JOIN school_people sp
            ON sp.SchoolPersonID = ct.SchoolPersonID
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
        WHERE COALESCE(ct.VisitDate, DATE(ct.CreatedAt)) >= :start
          AND COALESCE(ct.VisitDate, DATE(ct.CreatedAt)) < :end
          AND COALESCE(ct.ConsultationStatus, '') <> 'Cancelled'
        ORDER BY COALESCE(ct.VisitDate, DATE(ct.CreatedAt)) ASC, ct.ClinicTransactionID ASC
    ");
    $stmt->execute([':start' => $start, ':end' => $end]);
    $consultations = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $consultations = [];
}

$dispenses = [];
try {
    $stmt = $pdo->prepare("
        SELECT
            md.ClinicTransactionID,
            md.QuantityDispensed,
            m.MedicineName,
            m.GenericName,
            mi.InventoryID
        FROM medicine_dispensing md
        INNER JOIN clinic_transactions ct
            ON ct.ClinicTransactionID = md.ClinicTransactionID
        INNER JOIN medicine_inventory mi
            ON mi.InventoryID = md.InventoryID
        INNER JOIN medicines m
            ON m.MedicineID = mi.MedicineID
        WHERE COALESCE(ct.VisitDate, DATE(ct.CreatedAt)) >= :start
          AND COALESCE(ct.VisitDate, DATE(ct.CreatedAt)) < :end
        ORDER BY md.DispensedAt ASC, md.DispensingID ASC
    ");
    $stmt->execute([':start' => $start, ':end' => $end]);
    $dispenses = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $dispenses = [];
}

$serviceMatrix = [];
foreach ($serviceRows as $rowName) {
    $serviceMatrix[$rowName] = ['Student' => 0, 'Faculty' => 0, 'Staff' => 0, 'ASP' => 0];
}

$dentalMatrix = ['Student' => 0, 'Faculty' => 0, 'Staff' => 0, 'ASP' => 0];
$dailyMatrix = ['Student' => 0, 'Faculty' => 0, 'Staff' => 0, 'ASP' => 0];

$medicineTotals = array_fill_keys($medicineRows, 0);
$supplyTotals = array_fill_keys($supplyRows, 0);
$medicineLookup = [];
foreach ($medicineRows as $label) {
    $medicineLookup[normalizeKey($label)] = $label;
}
$medicineLookup[normalizeKey('Diphenhydramine Amp')] = 'Diphenhydramine';
$supplyLookup = [];
foreach ($supplyRows as $label) {
    $supplyLookup[normalizeKey($label)] = $label;
}

$consultationTotal = 0;
$medicineUnitsTotal = 0;
$unclassifiedConsultations = 0;

foreach ($consultations as $row) {
    $consultationTotal++;
    $bucket = bucketPersonCategory($row);
    $serviceRow = classifyConsultationRow($row);

    if ($serviceRow !== null && $serviceRow !== '__DENTAL__' && $serviceRow !== '__DAILY__') {
        addBucket($serviceMatrix, $serviceRow, $bucket, 1);
        continue;
    }

    if ($serviceRow === '__DENTAL__') {
        $dentalMatrix[$bucket]++;
        continue;
    }

    if ($serviceRow === '__DAILY__') {
        $dailyMatrix[$bucket]++;
        continue;
    }

    if (str_contains(strtolower((string)($row['ServiceType'] ?? '')), 'physical')) {
        addBucket($serviceMatrix, 'PE', $bucket, 1);
        continue;
    }

    $unclassifiedConsultations++;
}

// Keep the logbook honest: medicine dispensing is counted by quantity, not by row count.
foreach ($dispenses as $row) {
    $qty = max(0, (int)($row['QuantityDispensed'] ?? 0));
    if ($qty <= 0) {
        continue;
    }

    $medicineUnitsTotal += $qty;

    $medicineName = trim((string)($row['MedicineName'] ?? ''));
    $genericName = trim((string)($row['GenericName'] ?? ''));
    $normalizedMedicine = normalizeKey($medicineName);
    $normalizedGeneric = normalizeKey($genericName);

    if (isset($medicineLookup[$normalizedMedicine])) {
        $medicineTotals[$medicineLookup[$normalizedMedicine]] += $qty;
        continue;
    }

    if ($normalizedGeneric !== '' && isset($medicineLookup[$normalizedGeneric])) {
        $medicineTotals[$medicineLookup[$normalizedGeneric]] += $qty;
        continue;
    }

    if (isset($supplyLookup[$normalizedMedicine])) {
        $supplyTotals[$supplyLookup[$normalizedMedicine]] += $qty;
        continue;
    }

    if ($normalizedGeneric !== '' && isset($supplyLookup[$normalizedGeneric])) {
        $supplyTotals[$supplyLookup[$normalizedGeneric]] += $qty;
    }
}

$values = [];
foreach ($serviceRows as $rowName) {
    foreach (['Student', 'Faculty', 'Staff', 'ASP'] as $bucket) {
        $values[] = $serviceMatrix[$rowName][$bucket] ?? 0;
    }
}

// Dental consult row
foreach (['Student', 'Faculty', 'Staff', 'ASP'] as $bucket) {
    $values[] = $dentalMatrix[$bucket] ?? 0;
}

// Daily consult row
foreach (['Student', 'Faculty', 'Staff', 'ASP'] as $bucket) {
    $values[] = $dailyMatrix[$bucket] ?? 0;
}

foreach ($medicineRows as $label) {
    $values[] = $medicineTotals[$label] ?? 0;
}

foreach ($supplyRows as $label) {
    $values[] = $supplyTotals[$label] ?? 0;
}

echo json_encode([
    'ok' => true,
    'period' => [
        'year' => $year,
        'month' => $month,
        'start' => $start,
        'end' => $end,
    ],
    'summary' => [
        'consultations_total' => $consultationTotal,
        'medicine_units_total' => $medicineUnitsTotal,
        'unclassified_consultations' => $unclassifiedConsultations,
    ],
    'values' => $values,
], JSON_UNESCAPED_UNICODE);
