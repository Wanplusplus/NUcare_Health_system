<?php
declare(strict_types=1);

// Central DB connection (MySQLi)
// Usage: require_once __DIR__ . '/../config/db.php'; $conn

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'nucaredb';

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

if ($conn->connect_error) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed',
        'details' => $conn->connect_error,
    ]);
    exit;
}

$conn->set_charset('utf8mb4');

