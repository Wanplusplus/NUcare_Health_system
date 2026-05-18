<?php
require __DIR__ . '/../config/db_pdo.php';

$pdo = require __DIR__ . '/../config/db_pdo.php';

$stmt = $pdo->query("
    SELECT sp.SchoolID, sp.PersonType, u.UserID, u.IsActive, u.PasswordHash, r.RoleName
    FROM school_people sp
    LEFT JOIN users u ON u.SchoolPersonID = sp.SchoolPersonID
    LEFT JOIN user_roles ur ON ur.UserID = u.UserID
    LEFT JOIN roles r ON r.RoleID = ur.RoleID
    WHERE sp.SchoolID IN ('SCH-8001', 'SCH-8002', 'SCH-8003')
    ORDER BY sp.SchoolID
");

foreach ($stmt->fetchAll() as $row) {
    echo json_encode($row, JSON_UNESCAPED_SLASHES), PHP_EOL;
}
