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

// pe.Notes removed — column does not exist in physical_examinations
// Only select columns that are guaranteed to exist
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
        pe.BloodPressure,
        pe.Temperature,
        pe.PulseRate,
        pe.Weight,
        pe.Height
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
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
    exit;
}

// For each transaction, load its dispensed medicines
$dispSql = "
    SELECT
        md.DispensingID,
        md.QuantityDispensed,
        md.Instructions,
        md.DispensedAt,
        m.MedicineName,
        m.GenericName,
        m.Unit,
        m.Dosage
    FROM medicine_dispensing md
    JOIN medicine_inventory mi ON mi.InventoryID = md.InventoryID
    JOIN medicines m           ON m.MedicineID   = mi.MedicineID
    WHERE md.ClinicTransactionID = :ctid
    ORDER BY md.DispensedAt ASC
";

try {
    $dispStmt = $pdo->prepare($dispSql);
    foreach ($transactions as &$tx) {
        $dispStmt->execute([':ctid' => $tx['TransactionNumber']]);
        $tx['medicines'] = $dispStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($tx);
} catch (Throwable $e) {
    // Medicines query failed — return transactions without medicines rather than crashing
    foreach ($transactions as &$tx) {
        $tx['medicines'] = [];
    }
    unset($tx);
}

echo json_encode([
    'ok'           => true,
    'transactions' => $transactions,
]);