<?php
declare(strict_types=1);

$pdo = require __DIR__ . '/../config/db_pdo.php';

$stmt = $pdo->prepare("
    SELECT sp.SchoolID, sp.PersonType, u.UserID, u.PasswordHash, u.IsActive
    FROM school_people sp
    INNER JOIN users u ON u.SchoolPersonID = sp.SchoolPersonID
    WHERE sp.SchoolID = ?
    LIMIT 1
");
$stmt->execute(['SCH-8001']);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo "MISSING" . PHP_EOL;
    exit(0);
}

echo json_encode($row, JSON_UNESCAPED_SLASHES) . PHP_EOL;
echo 'VERIFY=' . (password_verify('DemoPass123!', (string)$row['PasswordHash']) ? 'YES' : 'NO') . PHP_EOL;
