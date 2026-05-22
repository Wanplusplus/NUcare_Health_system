<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/db_pdo.php';
require_once __DIR__ . '/../../includes/auth_guard.php';

$schoolPersonID = isset($_POST['school_person_id']) ? (int)$_POST['school_person_id'] : 0;
$mode = isset($_POST['mode']) ? trim((string)$_POST['mode']) : 'auto';
// mode: 'auto' (create transaction #1 if none), 'next' (create next transaction)

if ($schoolPersonID <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Invalid school_person_id']);
    exit;
}

if (!in_array($mode, ['auto', 'next'], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Invalid mode']);
    exit;
}

try {
    $pdo = getPDO();
    $pdo->beginTransaction();

    // Determine next transaction number
    $countSql = "SELECT COUNT(*) AS c FROM consultation_transactions WHERE SchoolPersonID = :spid";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute([':spid' => $schoolPersonID]);
    $count = (int)($countStmt->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);

    if ($mode === 'auto') {
        if ($count > 0) {
            // Auto mode should not create when history exists.
            $pdo->rollBack();
            echo json_encode(['ok' => false, 'message' => 'History exists. Use mode=next after confirmation.', 'historyCount' => $count]);
            exit;
        }
    }

    if ($mode === 'next') {
        if ($count <= 0) {
            // If none exist and user asked next, treat as #1.
        }
    }

    $maxSql = "SELECT MAX(TransactionNumber) AS mx FROM consultation_transactions WHERE SchoolPersonID = :spid";
    $maxStmt = $pdo->prepare($maxSql);
    $maxStmt->execute([':spid' => $schoolPersonID]);
    $max = $maxStmt->fetch(PDO::FETCH_ASSOC)['mx'];
    $nextTransactionNumber = ((int)($max ?? 0)) + 1;

    // Create a placeholder consultation row? Spec says create transaction automatically when no history.
    // We'll create the transaction row with only FK + transaction number, and other fields will be saved later.
    // But to keep it consistent, we will create the row NOW with required NOT NULL fields defaults.

    $insertSql = "INSERT INTO consultation_transactions
        (SchoolPersonID, TransactionNumber, ServiceType, ConsultationStatus)
        VALUES (:spid, :tn, 'General Consultation', 'Waiting')";

    $ins = $pdo->prepare($insertSql);
    $ins->execute([
        ':spid' => $schoolPersonID,
        ':tn' => $nextTransactionNumber,
    ]);

    $consultationID = (int)$pdo->lastInsertId();

    $pdo->commit();

    echo json_encode([
        'ok' => true,
        'consultation_id' => $consultationID,
        'transaction_number' => $nextTransactionNumber,
        'historyCount' => $count,
    ]);
    exit;

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Server error']);
    exit;
}

