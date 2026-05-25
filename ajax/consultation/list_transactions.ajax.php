<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo = require __DIR__ . '/../../config/db_pdo.php';

$id = (int)($_GET['school_person_id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['ok' => false, 'message' => 'Invalid school_person_id']);
    exit;
}

/* ══════════════════════════════════════════════════════════════
   MAIN QUERY
   clinic_transactions  → core consultation record
   school_people        → patient info
   physical_examinations→ vitals + ALL body system fields
══════════════════════════════════════════════════════════════ */
$sql = "
    SELECT
        ct.ClinicTransactionID  AS TransactionNumber,
        ct.VisitDate,
        ct.ConsultationStatus   AS Status,
        ct.ServiceType,
        ct.Complaint,
        ct.Notes,
        ct.CreatedAt,

        sp.SchoolID,
        sp.FirstName,
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
    JOIN school_people sp
        ON sp.SchoolPersonID = ct.SchoolPersonID
    LEFT JOIN physical_examinations pe
        ON pe.ClinicTransactionID = ct.ClinicTransactionID
    WHERE ct.SchoolPersonID = :id
    ORDER BY ct.ClinicTransactionID DESC
    LIMIT 50
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
    exit;
}

/* ══════════════════════════════════════════════════════════════
   MEDICINES QUERY — per transaction
══════════════════════════════════════════════════════════════ */
$dispSql = "
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

try {
    $dispStmt = $pdo->prepare($dispSql);
} catch (Throwable $e) {
    // If prepare fails, return transactions without medicines rather than crashing
    echo json_encode([
        'ok'           => true,
        'transactions' => array_map(fn($tx) => array_merge($tx, ['medicines' => [], 'physicalExam' => buildPE($tx)]), $rows),
    ]);
    exit;
}

/* ══════════════════════════════════════════════════════════════
   ASSEMBLE — merge medicines + physicalExam sub-object into each tx
══════════════════════════════════════════════════════════════ */
$transactions = [];
foreach ($rows as $tx) {
    // Fetch medicines for this transaction
    try {
        $dispStmt->execute([':ctid' => $tx['TransactionNumber']]);
        $tx['medicines'] = $dispStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $tx['medicines'] = [];
    }

    // Pull PE body-system fields into a clean sub-object for the JS history viewer
    $tx['physicalExam'] = buildPE($tx);

    // Remove the flat PE columns from the top-level tx object (they're in physicalExam now)
    foreach (['ExamDate','Ears','EyesPupil','Heart','Nose','Thorax','Abdomen',
              'Lungs','Skin','Extremities','Deformities','Remarks','CardioClearance'] as $col) {
        unset($tx[$col]);
    }

    $transactions[] = $tx;
}

echo json_encode([
    'ok'           => true,
    'transactions' => $transactions,
]);

/* ══════════════════════════════════════════════════════════════
   HELPER: build physicalExam sub-object from flat row
   Returns null if no meaningful PE data exists
══════════════════════════════════════════════════════════════ */
function buildPE(array $tx): ?array
{
    $peFields = [
        'ExamDate', 'Ears', 'EyesPupil', 'Heart', 'Nose',
        'Thorax', 'Abdomen', 'Lungs', 'Skin',
        'Extremities', 'Deformities', 'Remarks', 'CardioClearance',
    ];

    $hasData = false;
    $pe = [];
    foreach ($peFields as $f) {
        $val = $tx[$f] ?? null;
        $pe[$f] = $val;
        if ($val !== null && $val !== '') $hasData = true;
    }

    return $hasData ? $pe : null;
}