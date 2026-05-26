<?php
declare(strict_types=1);

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
   DETECT actual columns in physical_examinations
══════════════════════════════════════════════════════════════ */
try {
    $peCols = $pdo->query("SHOW COLUMNS FROM physical_examinations")
                  ->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    $peCols = [];
}

$wantedPeCols = [
    'pe.PhysicalExamID',
    'pe.ExamDate',
    'pe.BloodPressure',
    'pe.Temperature',
    'pe.PulseRate',
    'pe.Weight',
    'pe.Height',
    'pe.Ears',
    'pe.EyesPupil',
    'pe.Heart',
    'pe.Nose',
    'pe.Thorax',
    'pe.Abdomen',
    'pe.Lungs',
    'pe.Skin',
    'pe.Extremities',
    'pe.Deformities',
    'pe.Remarks',
    'pe.CardioClearance',
];

$safePeCols = array_filter($wantedPeCols, function($col) use ($peCols) {
    $bare = explode('.', $col)[1];
    return in_array($bare, $peCols, true);
});

$peSelect = empty($safePeCols) ? '' : ', ' . implode(', ', $safePeCols);

/* ══════════════════════════════════════════════════════════════
   QUERY 1 — Transaction + patient + physical exam
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
        sp.Sex
        $peSelect

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
   QUERY 2 — Medicines
══════════════════════════════════════════════════════════════ */
$medicines = [];
try {
    $medStmt = $pdo->prepare("
        SELECT md.DispensingID, md.QuantityDispensed, md.Instructions, md.DispensedAt,
               m.MedicineName, m.GenericName, m.Dosage, m.Unit
        FROM medicine_dispensing md
        JOIN medicine_inventory mi ON mi.InventoryID = md.InventoryID
        JOIN medicines m           ON m.MedicineID   = mi.MedicineID
        WHERE md.ClinicTransactionID = :ctid
        ORDER BY md.DispensedAt ASC
    ");
    $medStmt->execute([':ctid' => $ctid]);
    $medicines = $medStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

/* ══════════════════════════════════════════════════════════════
   QUERY 3 — Attachments
══════════════════════════════════════════════════════════════ */
$attachments = [];
try {
    $attachStmt = $pdo->prepare("
        SELECT ca.AttachmentID, ca.FileName, ca.FilePath, ca.FileType, ca.FileSizeBytes,
               ca.DocumentTypeID,
               adt.Category        AS AttachmentCategory,
               adt.DocumentType    AS DocumentTypeName,
               ca.Notes, ca.CreatedAt
        FROM consultation_attachments ca
        LEFT JOIN attachment_document_types adt ON adt.DocumentTypeID = ca.DocumentTypeID
        WHERE ca.ClinicTransactionID = :ctid
        ORDER BY ca.CreatedAt ASC
    ");
    $attachStmt->execute([':ctid' => $ctid]);
    $attachments = $attachStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

/* ══════════════════════════════════════════════════════════════
   QUERY 4 — Dental transaction (only when ServiceType = Dental)
══════════════════════════════════════════════════════════════ */
$dental = null;
if (($txRow['ServiceType'] ?? '') === 'Dental') {
    try {
        $dentalStmt = $pdo->prepare("
            SELECT dt.DentalTransactionID,
                   dt.ClinicTransactionID,
                   dt.InventoryID,
                   dt.AttachmentID,
                   dt.AttachmentCategory
            FROM dental_transactions dt
            WHERE dt.ClinicTransactionID = :ctid
            LIMIT 1
        ");
        $dentalStmt->execute([':ctid' => $ctid]);
        $dental = $dentalStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {}
}

/* ══════════════════════════════════════════════════════════════
   BUILD RESPONSE
══════════════════════════════════════════════════════════════ */
$existingPeBare = array_map(fn($c) => explode('.', $c)[1], array_values($safePeCols));

$hasPE = false;
$pe    = [];
foreach ($existingPeBare as $f) {
    $val    = $txRow[$f] ?? null;
    $pe[$f] = $val;
    if ($val !== null && $val !== '') $hasPE = true;
}

$parts    = array_filter([
    $txRow['FirstName']  ?? '',
    !empty($txRow['MiddleName']) ? mb_substr($txRow['MiddleName'], 0, 1) . '.' : '',
    $txRow['LastName']   ?? '',
]);
$fullName = trim(implode(' ', $parts));

$vitals = [];
foreach (['BloodPressure','Temperature','PulseRate','Weight','Height'] as $vc) {
    if (in_array($vc, $existingPeBare, true)) {
        $v = $txRow[$vc] ?? null;
        $vitals[$vc] = (in_array($vc, ['Temperature','Weight','Height'], true) && $v !== null)
            ? (float)$v : $v;
    }
}

echo json_encode([
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
        'UpdatedAt'           => $txRow['UpdatedAt'] ?? null,
        'patient' => [
            'SchoolPersonID' => (int)$txRow['SchoolPersonID'],
            'SchoolID'       => $txRow['SchoolID'],
            'FullName'       => $fullName,
            'FirstName'      => $txRow['FirstName'],
            'LastName'       => $txRow['LastName'],
            'Sex'            => $txRow['Sex'],
        ],
        'vitals'      => $vitals,
        'physicalExam'=> $hasPE ? $pe : null,
    ],
    'medicines'   => $medicines,
    'attachments' => $attachments,
    'dental'      => $dental,
], JSON_UNESCAPED_UNICODE);