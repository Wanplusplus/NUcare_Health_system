<?php
require_once __DIR__ . '/../config/db_pdo.php';
$pdo = require __DIR__ . '/../config/db_pdo.php';
$cnt = (int)$pdo->query('SELECT COUNT(*) FROM medical_professionals')->fetchColumn();
echo "medical_professionals count: {$cnt}\n";
$res = $pdo->query('SELECT MedProfID, UserID, Profession, Unit FROM medical_professionals ORDER BY MedProfID DESC LIMIT 20');
foreach ($res->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo json_encode($row, JSON_UNESCAPED_SLASHES) . "\n";
}

