<?php
declare(strict_types=1);
/**
 * get_transaction_ajax.php
 * ------------------------
 * READ-ONLY single-transaction detail endpoint.
 * Called when a history item is clicked to load full details.
 *
 * GET ?clinic_transaction_id=N
 *
 * Returns: { ok, schema_version, transaction, medicines[], attachments[] }
 */

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo = require __DIR__ . '/../../config/db_pdo.php';

$ctid = (int)($_GET['clinic_transaction_id'] ?? 0);
if ($ctid <= 0) {
    echo json_encode(['ok' => false, 'message' => 'Invalid clinic_transaction_id']);
    exit;
}

/* ══════════════════════════════════════════════════════════════
   QUERY 1 — Transaction + patient + physical exam (1:1)
══════════════════════════════════════════════════════════════ */
$txSql = "
    SELECT
        ct.ClinicTransactionID,
        ct.VisitDate,
        ct.ServiceType,
        ct.Complaint,
        ct.Notes,
        ct.ConsultationStatus,
        ct.CreatedAt,
        ct.UpdatedAt,

        sp.SchoolPersonID,
        sp.SchoolID,
        sp.FirstName,
        sp.MiddleName,
        sp.LastName,
        sp.Sex,

        pe.PhysicalExamID,
        pe.ExamDate,
        pe.BloodPressure,
        pe.Temperature,
        pe.PulseRate,
        pe.Weight,
        pe.Height,
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
        pe.Remarks,
        pe.CardioClearance

    FROM clinic_transactions ct
    JOIN school_people sp ON sp.SchoolPersonID = ct.SchoolPersonID
    LEFT JOIN physical_examinations pe ON pe.ClinicTransactionID = ct.ClinicTransactionID
    WHERE ct.ClinicTransactionID = :ctid
    LIMIT 1
";

try {
    $txStmt = $pdo->prepare($txSql);
    $txStmt->execute([':ctid' => $ctid]);
    $txRow = $txStmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'message' => 'Database error.', 'debug' => $e->getMessage()]);
    exit;
}

if (!$txRow) {
    echo json_encode(['ok' => false, 'message' => "Transaction #$ctid not found."]);
    exit;
}

/* ══════════════════════════════════════════════════════════════
   QUERY 2 — Medicines (1:M)
══════════════════════════════════════════════════════════════ */
$medSql = "
    SELECT
        md.DispensingID,
        md.QuantityDispensed,
        md.Instructions,
        md.DispensedAt,
        m.MedicineName,
        m.GenericName,
        m.Dosage,
        m.Unit
    FROM medicine_dispensing md
    JOIN medicine_inventory mi ON mi.InventoryID = md.InventoryID
    JOIN medicines m           ON m.MedicineID   = mi.MedicineID
    WHERE md.ClinicTransactionID = :ctid
    ORDER BY md.DispensedAt ASC
";

$medicines = [];
try {
    $medStmt = $pdo->prepare($medSql);
    $medStmt->execute([':ctid' => $ctid]);
    $medicines = $medStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { /* medicines table unavailable */ }

/* ══════════════════════════════════════════════════════════════
   QUERY 3 — Attachments (1:M — consultation_attachments)
══════════════════════════════════════════════════════════════ */
$attachSql = "
    SELECT
        AttachmentID,
        FileName,
        FilePath,
        FileType,
        FileSizeBytes,
        AttachmentCategory,
        Notes,
        CreatedAt
    FROM consultation_attachments
    WHERE ClinicTransactionID = :ctid
    ORDER BY CreatedAt ASC
";

$attachments = [];
try {
    $attachStmt = $pdo->prepare($attachSql);
    $attachStmt->execute([':ctid' => $ctid]);
    $attachments = $attachStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) { /* table may not exist yet — return empty */ }

/* ══════════════════════════════════════════════════════════════
   BUILD RESPONSE — nested shape, no flat columns
══════════════════════════════════════════════════════════════ */
$peFields = ['ExamDate','Ears','EyesPupil','Heart','Nose','Thorax',
             'Abdomen','Lungs','Skin','Extremities','Deformities','Remarks','CardioClearance'];

$hasPE = false;
$pe    = [];
foreach ($peFields as $f) {
    $val = $txRow[$f] ?? null;
    $pe[$f] = $val;
    if ($val !== null && $val !== '') $hasPE = true;
}

// Build full name
$parts    = array_filter([
    $txRow['FirstName']  ?? '',
    !empty($txRow['MiddleName']) ? mb_substr($txRow['MiddleName'], 0, 1) . '.' : '',
    $txRow['LastName']   ?? '',
]);
$fullName = trim(implode(' ', $parts));

$response = [
    'ok'             => true,
    'schema_version' => 1,
    'transaction'    => [
        'ClinicTransactionID' => (int)$txRow['ClinicTransactionID'],
        'VisitDate'           => $txRow['VisitDate'],
        'ServiceType'         => $txRow['ServiceType'],
        'Complaint'           => $txRow['Complaint'],
        'Notes'               => $txRow['Notes'],
        'ConsultationStatus'  => $txRow['ConsultationStatus'],
        'CreatedAt'           => $txRow['CreatedAt'],
        'UpdatedAt'           => $txRow['UpdatedAt'],
        'patient' => [
            'SchoolPersonID' => (int)$txRow['SchoolPersonID'],
            'SchoolID'       => $txRow['SchoolID'],
            'FullName'       => $fullName,
            'FirstName'      => $txRow['FirstName'],
            'LastName'       => $txRow['LastName'],
            'Sex'            => $txRow['Sex'],
        ],
        'vitals' => [
            'BloodPressure' => $txRow['BloodPressure'],
            'Temperature'   => $txRow['Temperature'] !== null ? (float)$txRow['Temperature'] : null,
            'PulseRate'     => $txRow['PulseRate'],
            'Weight'        => $txRow['Weight'] !== null ? (float)$txRow['Weight'] : null,
            'Height'        => $txRow['Height'] !== null ? (float)$txRow['Height'] : null,
        ],
        'physicalExam' => $hasPE ? $pe : null,
    ],
    'medicines'   => $medicines,
    'attachments' => $attachments,
];

echo json_encode($response, JSON_UNESCAPED_UNICODE);