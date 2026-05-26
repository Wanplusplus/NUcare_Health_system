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
   DETECT actual columns in physical_examinations
══════════════════════════════════════════════════════════════ */
try {
    $peCols = $pdo->query("SHOW COLUMNS FROM physical_examinations")
                  ->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    $peCols = [];
}

// All PE columns we'd like — only include ones that exist
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
    $bare = explode('.', $col)[1]; // strip "pe." prefix
    return in_array($bare, $peCols, true);
});

$peSelect = empty($safePeCols) ? '' : ', ' . implode(', ', $safePeCols);

/* ══════════════════════════════════════════════════════════════
   MAIN QUERY
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
        sp.Sex
        $peSelect

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

if (empty($rows)) {
    echo json_encode(['ok' => true, 'transactions' => []]);
    exit;
}

/* ══════════════════════════════════════════════════════════════
   MEDICINES QUERY
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

/* ══════════════════════════════════════════════════════════════
   ATTACHMENTS QUERY
══════════════════════════════════════════════════════════════ */
$attachSql = "
    SELECT
        AttachmentID,
        FileName,
        StoredName,
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

$dispStmt   = null;
$attachStmt = null;
try { $dispStmt   = $pdo->prepare($dispSql);   } catch (Throwable $e) {}
try { $attachStmt = $pdo->prepare($attachSql); } catch (Throwable $e) {}

/* ══════════════════════════════════════════════════════════════
   ASSEMBLE RESPONSE
══════════════════════════════════════════════════════════════ */

// Which PE columns actually exist (bare names, no "pe." prefix)
$existingPeBare = array_map(fn($c) => explode('.', $c)[1], array_values($safePeCols));

$transactions = [];

foreach ($rows as $tx) {
    $ctid = $tx['TransactionNumber'];

    // Medicines
    if ($dispStmt) {
        try { $dispStmt->execute([':ctid' => $ctid]); $tx['medicines'] = $dispStmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (Throwable $e) { $tx['medicines'] = []; }
    } else {
        $tx['medicines'] = [];
    }

    // Attachments
    if ($attachStmt) {
        try { $attachStmt->execute([':ctid' => $ctid]); $tx['attachments'] = $attachStmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (Throwable $e) { $tx['attachments'] = []; }
    } else {
        $tx['attachments'] = [];
    }

    // Build physicalExam sub-object from whatever columns exist
    $tx['physicalExam'] = buildPE($tx, $existingPeBare);

    // Remove flat PE columns from top-level
    foreach ($existingPeBare as $col) {
        unset($tx[$col]);
    }

    $transactions[] = $tx;
}

echo json_encode(['ok' => true, 'transactions' => $transactions]);

/* ══════════════════════════════════════════════════════════════
   HELPER
══════════════════════════════════════════════════════════════ */
function buildPE(array $tx, array $peFields): ?array
{
    $hasData = false;
    $pe      = [];
    foreach ($peFields as $f) {
        $val    = $tx[$f] ?? null;
        $pe[$f] = $val;
        if ($val !== null && $val !== '') $hasData = true;
    }
    return $hasData ? $pe : null;
}