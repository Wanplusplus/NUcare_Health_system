<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo = require __DIR__ . '/../../config/db_pdo.php';

$q = trim((string)($_GET['q'] ?? ''));

if ($q === '') {
    echo json_encode(['ok' => false, 'message' => 'Missing query']);
    exit;
}

$like = "%$q%";

/*
 * Returns medicines that:
 *  - are NOT expired
 *  - have Quantity > 0
 * Ordered by best name match first.
 */
$sql = "
    SELECT
        mi.InventoryID,
        m.MedicineID,
        m.MedicineName,
        m.GenericName,
        m.MedicineType,
        m.Dosage,
        m.Unit,
        mi.Quantity      AS AvailableQty,
        mi.ReorderLevel,
        mi.BatchNumber,
        mi.ExpiryDate,
        mi.Status
    FROM medicines m
    JOIN medicine_inventory mi ON mi.MedicineID = m.MedicineID
    WHERE
        mi.Quantity > 0
        AND (mi.Status != 'Expired' AND mi.Status != 'Out Of Stock')
        AND (
            m.MedicineName  LIKE :q1
            OR m.GenericName LIKE :q2
            OR m.Dosage      LIKE :q3
        )
    ORDER BY
        CASE WHEN m.MedicineName LIKE :q4 THEN 0 ELSE 1 END,
        mi.ExpiryDate ASC,
        m.MedicineName ASC
    LIMIT 10
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':q1' => $like,
    ':q2' => $like,
    ':q3' => $like,
    ':q4' => $like,
]);

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'ok'      => true,
    'results' => $results,
]);