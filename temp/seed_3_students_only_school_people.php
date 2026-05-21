<?php
declare(strict_types=1);

/**
 * Seeds ONLY `school_people` rows for 3 new STUDENTS.
 *
 * Intentionally does NOT seed `users`, `user_roles`, or any RBAC tables.
 *
 * Run:
 *   php temp/seed_3_students_only_school_people.php
 */

ini_set('display_errors', '1');
error_reporting(E_ALL);

$pdo = require __DIR__ . '/../config/db_pdo.php';

$students = [
    [
        'SchoolID' => 'S-10001',
        'FirstName' => 'Student',
        'MiddleName' => null,
        'LastName' => 'One',
        'Email' => 'student.one@example.com',
        'Sex' => 'Male',
        'PersonType' => 'Student',
    ],
    [
        'SchoolID' => 'S-10002',
        'FirstName' => 'Student',
        'MiddleName' => null,
        'LastName' => 'Two',
        'Email' => 'student.two@example.com',
        'Sex' => 'Female',
        'PersonType' => 'Student',
    ],
    [
        'SchoolID' => 'S-10003',
        'FirstName' => 'Student',
        'MiddleName' => null,
        'LastName' => 'Three',
        'Email' => 'student.three@example.com',
        'Sex' => 'Male',
        'PersonType' => 'Student',
    ],
];

$sql = "
INSERT INTO school_people (
    SchoolID, FirstName, LastName, MiddleName, Email, PersonType, Sex
) VALUES (
    ?, ?, ?, ?, ?, ?, ?
)
ON DUPLICATE KEY UPDATE
    FirstName = VALUES(FirstName),
    LastName = VALUES(LastName),
    MiddleName = VALUES(MiddleName),
    Email = VALUES(Email),
    PersonType = VALUES(PersonType),
    Sex = VALUES(Sex)
";

$stmt = $pdo->prepare($sql);

foreach ($students as $s) {
    $stmt->execute([
        $s['SchoolID'],
        $s['FirstName'],
        $s['LastName'],
        $s['MiddleName'],
        $s['Email'],
        $s['PersonType'],
        $s['Sex'],
    ]);
}

echo "Seeded 3 students into school_people only.\n";
foreach ($students as $s) {
    echo "- {$s['SchoolID']} ({$s['FirstName']} {$s['LastName']})\n";
}

