<?php
declare(strict_types=1);

$host = 'localhost';
$dbname = 'nucaredb';
$username = 'root';
$dbPassword = '';


$options = [
 PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
 PDO::ATTR_EMULATE_PREPARES => false,
 PDO::ATTR_PERSISTENT => false,
];

$pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $username, $dbPassword, $options);

return $pdo;

