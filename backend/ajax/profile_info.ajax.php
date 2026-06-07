<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) {
 session_start();
}

if (!isset($_SESSION['UserID']) || !is_numeric($_SESSION['UserID'])) {
 http_response_code(401);
 echo json_encode(['ok' => false, 'message' => 'Unauthorized.']);
 exit;
}

$pdo = require __DIR__ . '/../../database/config/db_pdo.php';
require_once __DIR__ . '/../../backend/includes/patients_info_helpers.php';

$userID = (int)$_SESSION['UserID'];

try {
 if ($_SERVER['REQUEST_METHOD'] === 'GET') {
 echo json_encode([
 'ok' => true,
 'patientsInfo' => patientsInfoLoad($pdo, $userID),
 'familyHistory' => familyHistoryLoad($pdo, $userID),
 ], JSON_UNESCAPED_UNICODE);
 exit;
 }

 $payload = patientsInfoPayload($_POST);
 $errors = patientsInfoValidate($payload);
 if ($errors) {
 echo json_encode(['ok' => false, 'message' => implode(' ', $errors), 'errors' => $errors], JSON_UNESCAPED_UNICODE);
 exit;
 }

 $familyRaw = (string)($_POST['family_history'] ?? '[]');
 $familyItems = json_decode($familyRaw, true);
 if (!is_array($familyItems)) {
 $familyItems = [];
 }

 $pdo->beginTransaction();
 patientsInfoSave($pdo, $userID, $payload);
 familyHistoryReplace($pdo, $userID, $familyItems);
 $pdo->commit();

 echo json_encode([
 'ok' => true,
 'message' => 'Profile saved.',
 'patientsInfo' => patientsInfoLoad($pdo, $userID),
 'familyHistory' => familyHistoryLoad($pdo, $userID),
 ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
 if ($pdo->inTransaction()) {
 $pdo->rollBack();
 }
 http_response_code(500);
 echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}




