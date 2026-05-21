<?php
declare(strict_types=1);

/**
 * Seed 3 medical staff accounts (Doctor/Nurse/Dentist) for login testing.
 *
 * Creates/updates:
 * - school_people
 * - users (PasswordHash hashed with bcrypt)
 * - user_roles (by RoleID from roles.RoleName)
 *
 * Usage:
 *   php temp/seed_doctor_accounts.php
 */

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../config/db_pdo.php';
$pdo = require __DIR__ . '/../config/db_pdo.php';

function tableExists(PDO $pdo, string $table): bool {
    $stmt = $pdo->query("SHOW TABLES LIKE '" . str_replace("'", "''", $table) . "'");
    return (bool)$stmt->fetchColumn();
}

function ensureRole(PDO $pdo, string $roleName): int {
    $roleNameEsc = str_replace("'", "''", $roleName);
    $sql = "SELECT RoleID FROM roles WHERE RoleName = '{$roleNameEsc}' LIMIT 1";
    $id = $pdo->query($sql)->fetchColumn();
    if (!$id) {
        throw new RuntimeException("Role not found in roles: {$roleName}");
    }
    return (int)$id;
}

function upsertUserBySchoolPersonId(PDO $pdo, int $schoolPersonId, string $password): int {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $hashEsc = str_replace("'", "''", $hash);

    // users: columns: UserID (PK auto), SchoolPersonID (unique), PasswordHash, IsActive
    $sql = "INSERT INTO users (SchoolPersonID, PasswordHash, IsActive)
            VALUES ({$schoolPersonId}, '{$hashEsc}', 1)
            ON DUPLICATE KEY UPDATE PasswordHash = VALUES(PasswordHash), IsActive = VALUES(IsActive)";
    $pdo->exec($sql);

    return (int)$pdo->query("SELECT UserID FROM users WHERE SchoolPersonID = {$schoolPersonId} LIMIT 1")->fetchColumn();
}

function upsertUserRole(PDO $pdo, int $userId, int $roleId): void {
    $sql = "INSERT INTO user_roles (UserID, RoleID)
            VALUES ({$userId}, {$roleId})
            ON DUPLICATE KEY UPDATE RoleID = VALUES(RoleID)";
    $pdo->exec($sql);
}

// --- staff demo ---
$staff = [
    ['schoolId' => 'STF-888', 'roleName' => 'Doctor',  'first' => 'Demo', 'last' => 'Doctor',  'personType' => 'Staff', 'email' => 'doctor888@example.com', 'sex' => 'Male',   'password' => '888888'],
    ['schoolId' => 'STF-999', 'roleName' => 'Nurse',   'first' => 'Demo', 'last' => 'Nurse',   'personType' => 'Staff', 'email' => 'nurse999@example.com',  'sex' => 'Female', 'password' => '999999'],
    ['schoolId' => 'STF-000', 'roleName' => 'Dentist', 'first' => 'Demo', 'last' => 'Dentist', 'personType' => 'Staff', 'email' => 'dentist000@example.com','sex' => 'Male',    'password' => '000000'],
];

try {
    $pdo->beginTransaction();

    foreach ($staff as $s) {
        $schoolIdEsc = str_replace("'", "''", $s['schoolId']);

        $schoolPersonId = $pdo->query(
            "SELECT SchoolPersonID FROM school_people WHERE SchoolID = '{$schoolIdEsc}' LIMIT 1"
        )->fetchColumn();

        if (!$schoolPersonId) {
            $schoolPersonId = $pdo->lastInsertId(); // just in case

            // school_people: SchoolID (unique), FirstName, LastName, MiddleName(optional), Email(optional), PersonType enum, Sex enum
            // required columns from schema: SchoolID, FirstName, LastName, PersonType, Sex; Email optional.
            $firstEsc = str_replace("'", "''", $s['first']);
            $lastEsc  = str_replace("'", "''", $s['last']);
            $emailEsc = str_replace("'", "''", $s['email']);
            $personTypeEsc = str_replace("'", "''", $s['personType']);
            $sexEsc = str_replace("'", "''", $s['sex']);

            $pdo->exec(
                "INSERT INTO school_people (SchoolID, FirstName, LastName, Email, PersonType, Sex)
                 VALUES ('{$schoolIdEsc}', '{$firstEsc}', '{$lastEsc}', '{$emailEsc}', '{$personTypeEsc}', '{$sexEsc}')"
            );

            $schoolPersonId = (int)$pdo->lastInsertId();
        } else {
            $schoolPersonId = (int)$schoolPersonId;
            // update fields in case they changed
            $firstEsc = str_replace("'", "''", $s['first']);
            $lastEsc  = str_replace("'", "''", $s['last']);
            $emailEsc = str_replace("'", "''", $s['email']);
            $personTypeEsc = str_replace("'", "''", $s['personType']);
            $sexEsc = str_replace("'", "''", $s['sex']);

            $pdo->exec(
                "UPDATE school_people
                 SET FirstName = '{$firstEsc}', LastName = '{$lastEsc}', Email = '{$emailEsc}', PersonType = '{$personTypeEsc}', Sex = '{$sexEsc}'
                 WHERE SchoolPersonID = {$schoolPersonId}"
            );
        }

        $roleId = ensureRole($pdo, $s['roleName']);
        $userId = upsertUserBySchoolPersonId($pdo, (int)$schoolPersonId, (string)$s['password']);
        upsertUserRole($pdo, $userId, $roleId);

        // If medical_professionals exists, populate it.
        // Schema (from sql/nucaredb.sql): medical_professionals(UserID UNIQUE, Profession enum).
        if (tableExists($pdo, 'medical_professionals')) {
            $professionEsc = str_replace("'", "''", $s['roleName']); // Doctor/Dentist/Nurse

            try {
                // Upsert by UserID (unique)
                $pdo->exec(
                    "INSERT INTO medical_professionals (UserID, Profession)
                     VALUES ({$userId}, '{$professionEsc}')
                     ON DUPLICATE KEY UPDATE Profession = VALUES(Profession)"
                );
            } catch (Throwable $e) {
                // ignore - schema might differ
            }
        }



        echo "Seeded {$s['roleName']} => SchoolID={$s['schoolId']} UserID={$userId}\n";
    }

    $pdo->commit();
    echo "DONE\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo 'Seeder failed: ' . $e->getMessage() . "\n";
    echo $e->getFile() . ':' . $e->getLine() . "\n";
    exit(1);
}

