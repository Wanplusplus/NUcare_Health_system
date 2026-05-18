<?php
declare(strict_types=1);

$pdo = require __DIR__ . '/../config/db_pdo.php';

$accounts = [
    [
        'SchoolID' => 'SCH-8001',
        'FirstName' => 'Super',
        'LastName' => 'Admin One',
        'MiddleName' => 'AA',
        'Email' => 'superadmin1@nucare.edu',
        'Sex' => 'Female',
    ],
    [
        'SchoolID' => 'SCH-8002',
        'FirstName' => 'Super',
        'LastName' => 'Admin Two',
        'MiddleName' => 'AB',
        'Email' => 'superadmin2@nucare.edu',
        'Sex' => 'Male',
    ],
    [
        'SchoolID' => 'SCH-8003',
        'FirstName' => 'Super',
        'LastName' => 'Admin Three',
        'MiddleName' => 'AC',
        'Email' => 'superadmin3@nucare.edu',
        'Sex' => 'Female',
    ],
];

$passwordHash = '$2y$10$MbHUvivnEtPN9vR/CIY7DezcYUW80wm8QBkvCqaB1EZXJmXUi6.9C';

$pdo->beginTransaction();

try {
    $roleStmt = $pdo->prepare("SELECT RoleID FROM roles WHERE RoleName = 'Super Admin' LIMIT 1");
    $roleStmt->execute();
    $roleId = (int)$roleStmt->fetchColumn();

    if ($roleId <= 0) {
        throw new RuntimeException('Super Admin role not found.');
    }

    $personSelect = $pdo->prepare("SELECT SchoolPersonID FROM school_people WHERE SchoolID = ? LIMIT 1");
    $personInsert = $pdo->prepare("
        INSERT INTO school_people (SchoolID, FirstName, LastName, MiddleName, Email, PersonType, Sex)
        VALUES (?, ?, ?, ?, ?, 'Staff', ?)
    ");
    $personUpdate = $pdo->prepare("
        UPDATE school_people
        SET FirstName = ?, LastName = ?, MiddleName = ?, Email = ?, PersonType = 'Staff', Sex = ?
        WHERE SchoolID = ?
    ");

    $userSelect = $pdo->prepare("SELECT UserID FROM users WHERE SchoolPersonID = ? LIMIT 1");
    $userInsert = $pdo->prepare("
        INSERT INTO users (SchoolPersonID, PasswordHash, IsActive)
        VALUES (?, ?, 1)
    ");
    $userUpdate = $pdo->prepare("
        UPDATE users
        SET PasswordHash = ?, IsActive = 1
        WHERE SchoolPersonID = ?
    ");

    $userRoleInsert = $pdo->prepare("
        INSERT INTO user_roles (UserID, RoleID)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE RoleID = VALUES(RoleID)
    ");

    $assignmentInsert = $pdo->prepare("
        INSERT INTO employee_assignments (SchoolPersonID, Department, PositionTitle, EmploymentStatus, StartDate)
        SELECT ?, 'Administration', 'System Admin', 'Employed', '2025-01-01'
        WHERE NOT EXISTS (
            SELECT 1 FROM employee_assignments
            WHERE SchoolPersonID = ? AND PositionTitle = 'System Admin'
        )
    ");

    foreach ($accounts as $account) {
        $personSelect->execute([$account['SchoolID']]);
        $personId = $personSelect->fetchColumn();

        if ($personId === false) {
            $personInsert->execute([
                $account['SchoolID'],
                $account['FirstName'],
                $account['LastName'],
                $account['MiddleName'],
                $account['Email'],
                $account['Sex'],
            ]);
            $personSelect->execute([$account['SchoolID']]);
            $personId = $personSelect->fetchColumn();
        } else {
            $personUpdate->execute([
                $account['FirstName'],
                $account['LastName'],
                $account['MiddleName'],
                $account['Email'],
                $account['Sex'],
                $account['SchoolID'],
            ]);
        }

        $personId = (int)$personId;

        $userSelect->execute([$personId]);
        $userId = $userSelect->fetchColumn();

        if ($userId === false) {
            $userInsert->execute([$personId, $passwordHash]);
            $userId = (int)$pdo->lastInsertId();
        } else {
            $userUpdate->execute([$passwordHash, $personId]);
            $userId = (int)$userId;
        }

        $userRoleInsert->execute([$userId, $roleId]);
        $assignmentInsert->execute([$personId, $personId]);
    }

    $pdo->commit();

    echo "Seeded/updated 3 Super Admin staff accounts successfully." . PHP_EOL;
    echo "Password: DemoPass123!" . PHP_EOL;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}
