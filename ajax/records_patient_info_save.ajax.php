<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Support multiple session key names used by different login flows
$sessionUserID = $_SESSION['UserID'] ?? $_SESSION['user_id'] ?? $_SESSION['userid'] ?? null;
if (!$sessionUserID) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized. Please log in again.']);
    exit;
}

$pdo = require __DIR__ . '/../config/db_pdo.php';
require_once __DIR__ . '/../includes/patients_info_helpers.php';

$schoolPersonID = (int)($_POST['school_person_id'] ?? 0);
if ($schoolPersonID <= 0) {
    echo json_encode(['ok' => false, 'message' => 'Invalid patient.']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT UserID FROM users WHERE SchoolPersonID = :spid LIMIT 1');
    $stmt->execute([':spid' => $schoolPersonID]);
    $targetUserID = (int)($stmt->fetchColumn() ?: 0);

    if ($targetUserID <= 0) {
        echo json_encode(['ok' => false, 'message' => 'This patient does not have a linked user account.']);
        exit;
    }

    $familyRaw = (string)($_POST['family_history'] ?? '[]');
    $familyItems = json_decode($familyRaw, true);
    if (!is_array($familyItems)) {
        $familyItems = [];
    }

    $pdo->beginTransaction();
    if (($_POST['mode'] ?? '') !== 'family_only') {
        $payload = patientsInfoPayload($_POST);
        $errors = patientsInfoValidate($payload);
        if ($errors) {
            $pdo->rollBack();
            echo json_encode(['ok' => false, 'message' => implode(' ', $errors), 'errors' => $errors], JSON_UNESCAPED_UNICODE);
            exit;
        }
        patientsInfoSave($pdo, $targetUserID, $payload);
    }
    familyHistoryReplace($pdo, $targetUserID, $familyItems);
    $pdo->commit();

    echo json_encode([
        'ok' => true,
        'message' => 'Patient information saved.',
        'patientsInfo' => patientsInfoLoad($pdo, $targetUserID),
        'familyHistory' => familyHistoryLoad($pdo, $targetUserID),
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}