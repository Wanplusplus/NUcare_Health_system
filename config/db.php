<?php
// Database connection for NUcare Health System
$host     = 'localhost';
$dbname   = 'nucaredb';      // ← Change to your actual database name
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode([
        'status'  => 'error',
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]));
}

$conn->set_charset('utf8mb4');
?>
