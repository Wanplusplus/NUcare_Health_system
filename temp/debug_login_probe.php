<?php
declare(strict_types=1);

$schoolId = 'SCH-1003';
$password = '123123';

$pdo = require __DIR__ . '/../config/db_pdo.php';

$personStmt = $pdo->prepare("SELECT SchoolPersonID, SchoolID, Email, PersonType, FirstName, LastName FROM school_people WHERE SchoolID = ? LIMIT 1");
$personStmt->execute([$schoolId]);
$person = $personStmt->fetch(PDO::FETCH_ASSOC);

var_dump(['person' => $person]);

if ($person) {
    $userStmt = $pdo->prepare("SELECT UserID, SchoolPersonID, PasswordHash, IsActive FROM users WHERE SchoolPersonID = ? LIMIT 1");
    $userStmt->execute([(int)$person['SchoolPersonID']]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    var_dump(['user' => $user]);
    if ($user) {
        var_dump([
            'isStudent' => ((string)$person['PersonType'] === 'Student'),
            'isActive' => ((int)$user['IsActive'] === 1),
            'hashEmpty' => empty($user['PasswordHash']),
            'verify' => password_verify($password, (string)$user['PasswordHash']),
        ]);
    }
}
