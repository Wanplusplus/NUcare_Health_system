<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$pdo = require __DIR__ . '/../../config/db_pdo.php';

$consultationID = (int)($_POST['consultation_id'] ?? 0);

if ($consultationID <= 0) {
    echo json_encode(['ok' => false, 'message' => 'Invalid consultation']);
    exit;
}

$complaint = trim($_POST['complaint'] ?? '');
$serviceType = trim($_POST['service_type'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$status = $_POST['consultation_status'] ?? 'Waiting';

if ($complaint === '' || $serviceType === '') {
    echo json_encode(['ok' => false, 'message' => 'Missing required fields']);
    exit;
}

$allowed = ['Waiting', 'Consulting', 'Completed', 'Cancelled'];
if (!in_array($status, $allowed, true)) {
    $status = 'Waiting';
}

try {
    $stmt = $pdo->prepare("
        UPDATE physical_examinations
        SET
            Complaint = :c,
            ServiceType = :s,
            Notes = :n,
            ConsultationStatus = :st
        WHERE PhysicalExamID = :id
    ");

    $stmt->execute([
        ':c' => $complaint,
        ':s' => $serviceType,
        ':n' => $notes,
        ':st' => $status,
        ':id' => $consultationID
    ]);

    echo json_encode(['ok' => true, 'message' => 'Saved']);

} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'message' => 'Save failed']);
}