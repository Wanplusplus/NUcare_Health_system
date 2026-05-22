<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/db_pdo.php';
require_once __DIR__ . '/../../includes/auth_guard.php';

$schoolPersonID = isset($_GET['school_person_id']) ? (int)$_GET['school_person_id'] : 0;
if ($schoolPersonID <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Invalid school_person_id']);
    exit;
}

try {
    $pdo = getPDO();

    // Required display fields only:
    // School ID, Sex, TransactionNumber, CreatedAt
    $sql = "SELECT
                ct.TransactionNumber,
                ct.CreatedAt,
                sp.SchoolID,
                sp.Sex
            FROM consultation_transactions ct
            INNER JOIN school_people sp ON sp.SchoolPersonID = ct.SchoolPersonID
            WHERE ct.SchoolPersonID = :spid
            ORDER BY ct.TransactionNumber ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':spid' => $schoolPersonID]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['ok' => true, 'transactions' => $rows]);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Server error']);
    exit;
}

