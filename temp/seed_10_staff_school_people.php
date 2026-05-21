<?php
declare(strict_types=1);

/**
 * Seeds ONLY `school_people` rows for 10 STAFF accounts (signup test basis).
 *
 * Intentionally does NOT seed `users`, `user_roles`, or any RBAC tables.
 *
 * Run:
 *   php temp/seed_10_staff_school_people.php
 */

ini_set('display_errors', '1');
error_reporting(E_ALL);

$pdo = require __DIR__ . '/../config/db_pdo.php';

$staff = [
    ['SchoolID' => 'STF-10001', 'FirstName' => 'Staff', 'MiddleName' => null, 'LastName' => 'One',   'Email' => 'staff.one@example.com',   'PersonType' => 'Staff', 'Sex' => 'Male'],
    ['SchoolID' => 'STF-10002', 'FirstName' => 'Staff', 'MiddleName' => null, 'LastName' => 'Two',   'Email' => 'staff.two@example.com',   'PersonType' => 'Staff', 'Sex' => 'Female'],
    ['SchoolID' => 'STF-10003', 'FirstName' => 'Staff', 'MiddleName' => null, 'LastName' => 'Three', 'Email' => 'staff.three@example.com', 'PersonType' => 'Staff', 'Sex' => 'Male'],
    ['SchoolID' => 'STF-10004', 'FirstName' => 'Staff', 'MiddleName' => null, 'LastName' => 'Four',  'Email' => 'staff.four@example.com',  'PersonType' => 'Staff', 'Sex' => 'Female'],
    ['SchoolID' => 'STF-10005', 'FirstName' => 'Staff', 'MiddleName' => null, 'LastName' => 'Five',  'Email' => 'staff.five@example.com',  'PersonType' => 'Staff', 'Sex' => 'Male'],
    ['SchoolID' => 'STF-10006', 'FirstName' => 'Staff', 'MiddleName' => null, 'LastName' => 'Six',   'Email' => 'staff.six@example.com',   'PersonType' => 'Staff', 'Sex' => 'Female'],
    ['SchoolID' => 'STF-10007', 'FirstName' => 'Staff', 'MiddleName' => null, 'LastName' => 'Seven', 'Email' => 'staff.seven@example.com', 'PersonType' => 'Staff', 'Sex' => 'Male'],
    ['SchoolID' => 'STF-10008', 'FirstName' => 'Staff', 'MiddleName' => null, 'LastName' => 'Eight', 'Email' => 'staff.eight@example.com', 'PersonType' => 'Staff', 'Sex' => 'Female'],
    ['SchoolID' => 'STF-10009', 'FirstName' => 'Staff', 'MiddleName' => null, 'LastName' => 'Nine',  'Email' => 'staff.nine@example.com',  'PersonType' => 'Staff', 'Sex' => 'Male'],
    ['SchoolID' => 'STF-10010', 'FirstName' => 'Staff', 'MiddleName' => null, 'LastName' => 'Ten',   'Email' => 'staff.ten@example.com',   'PersonType' => 'Staff', 'Sex' => 'Female'],
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

foreach ($staff as $s) {
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

echo "Seeded 10 staff into school_people only.\n";
foreach ($staff as $s) {
    echo "- {$s['SchoolID']} ({$s['FirstName']} {$s['LastName']})\n";
}

