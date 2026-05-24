<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$pdo = require __DIR__ . '/../../config/db_pdo.php';

$id = (int)($_GET['school_person_id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['ok' => false, 'message' => 'Invalid school_person_id']);
    exit;
}

$sql = "
SELECT
    ct.ClinicTransactionID AS TransactionNumber,
    sp.SchoolID,
    sp.Sex,
    pe.ExamDate,
    pe.BloodPressure,
    pe.PulseRate,
    pe.Weight
FROM clinic_transactions ct
JOIN physical_examinations pe
    ON pe.ClinicTransactionID = ct.ClinicTransactionID
JOIN school_people sp
    ON sp.SchoolPersonID = ct.SchoolPersonID
WHERE ct.SchoolPersonID = :id
ORDER BY ct.ClinicTransactionID DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $id]);

echo json_encode([
    'ok' => true,
    'transactions' => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);