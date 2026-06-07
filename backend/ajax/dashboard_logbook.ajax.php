<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) {
 session_start();
}

if (!isset($_SESSION['UserID'])) {
 echo json_encode(['ok' => false, 'message' => 'Unauthorized.']);
 exit;
}

require_once __DIR__ . '/../../backend/includes/logbook_data.php';

$pdo = require __DIR__ . '/../../database/config/db_pdo.php';
$month = (int)($_GET['month'] ?? date('n'));
$year = (int)($_GET['year'] ?? date('Y'));
$data = nucareBuildLogbookData($pdo, $year, $month);

echo json_encode([
 'ok' => true,
 'period' => $data['period'],
 'summary' => $data['summary'],
 'values' => $data['values'],
], JSON_UNESCAPED_UNICODE);




