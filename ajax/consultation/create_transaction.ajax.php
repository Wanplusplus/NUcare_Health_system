<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$pdo = require __DIR__ . '/../../config/db_pdo.php';

$spid = (int)($_POST['school_person_id'] ?? 0);
$mode = $_POST['mode'] ?? 'auto';

if ($spid <= 0) {
    echo json_encode(['ok' => false, 'message' => 'Invalid school_person_id']);
    exit;
}

try {
    $pdo->beginTransaction();

    // check history
    $check = $pdo->prepare("
        SELECT COUNT(*)
        FROM clinic_transactions
        WHERE SchoolPersonID = :id
    ");
    $check->execute([':id' => $spid]);

    $hasHistory = (int)$check->fetchColumn() > 0;

    if ($mode === 'auto' && $hasHistory) {
        $pdo->rollBack();
        echo json_encode(['ok' => false, 'message' => 'History exists', 'historyCount' => $hasHistory]);
        exit;
    }

    // create transaction header
    $ct = $pdo->prepare("
        INSERT INTO clinic_transactions (SchoolPersonID, VisitDate)
        VALUES (:id, CURDATE())
    ");
    $ct->execute([':id' => $spid]);

    $ctid = (int)$pdo->lastInsertId();

    // create physical exam (ONLY REAL COLUMNS)
    $pe = $pdo->prepare("
        INSERT INTO physical_examinations (
            ClinicTransactionID,
            ExamDate,
            BloodPressure,
            PulseRate,
            Weight
        )
        VALUES (
            :ctid,
            CURDATE(),
            NULL,
            NULL,
            NULL
        )
    ");

    $pe->execute([':ctid' => $ctid]);

    $peid = (int)$pdo->lastInsertId();

    $pdo->commit();

    echo json_encode([
        'ok' => true,
        'consultation_id' => $peid,
        'transaction_number' => $ctid
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    echo json_encode([
        'ok' => false,
        'message' => 'Transaction failed',
        'debug' => $e->getMessage()
    ]);
}   