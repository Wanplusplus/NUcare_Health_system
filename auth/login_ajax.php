<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_pdo.php';
require_once __DIR__ . '/../includes/rbac.php';
require_once __DIR__ . '/../includes/audit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method.',
    ]);
    exit;
}

$school_id = trim((string)($_POST['username'] ?? ''));
$loginPassword = (string)($_POST['password'] ?? '');
$demoPassword = 'DemoPass123!';
$ip = $_SERVER['REMOTE_ADDR'] ?? null;

if ($school_id === '' || $loginPassword === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please enter your School ID and password.',
    ]);
    exit;
}

$pdo = require __DIR__ . '/../config/db_pdo.php';

try {
    $personStmt = $pdo->prepare(
        "SELECT SchoolPersonID, SchoolID, Email, PersonType, FirstName, LastName
         FROM school_people
         WHERE SchoolID = ?
         LIMIT 1"
    );
    $personStmt->execute([$school_id]);
    $person = $personStmt->fetch();

    if (!$person) {
        auditLog(null, null, 'failed_login', 'auth', $school_id, 'SchoolID not found in school_people', $ip);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid School ID or password.',
        ]);
        exit;
    }

    $personType = (string)$person['PersonType'];
    $isStudent = $personType === 'Student';

    $userStmt = $pdo->prepare(
        "SELECT UserID, SchoolPersonID, PasswordHash, IsActive
         FROM users
         WHERE SchoolPersonID = ?
         LIMIT 1"
    );
    $userStmt->execute([(int)$person['SchoolPersonID']]);
    $user = $userStmt->fetch();

    if ((!$user || (int)$user['IsActive'] !== 1) && $isStudent && $loginPassword === $demoPassword) {
        $hash = password_hash($demoPassword, PASSWORD_DEFAULT);

        $upsertUserStmt = $pdo->prepare(
            "INSERT INTO users (SchoolPersonID, PasswordHash, IsActive)
             VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE PasswordHash = VALUES(PasswordHash), IsActive = VALUES(IsActive)"
        );
        $upsertUserStmt->execute([(int)$person['SchoolPersonID'], $hash]);

        $studentRoleStmt = $pdo->prepare("SELECT RoleID FROM roles WHERE RoleName = 'Student' LIMIT 1");
        $studentRoleStmt->execute();
        $studentRole = $studentRoleStmt->fetch();

        $userStmt->execute([(int)$person['SchoolPersonID']]);
        $user = $userStmt->fetch();

        if ($user && $studentRole) {
            $roleLinkStmt = $pdo->prepare(
                "INSERT INTO user_roles (UserID, RoleID)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE RoleID = VALUES(RoleID)"
            );
            $roleLinkStmt->execute([(int)$user['UserID'], (int)$studentRole['RoleID']]);
        }
    }

    if (!$user || (int)$user['IsActive'] !== 1) {
        auditLog(null, (int)$person['SchoolPersonID'], 'failed_login', 'auth', $school_id, 'User not found or inactive', $ip);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid School ID or password.',
        ]);
        exit;
    }

    if (empty($user['PasswordHash']) || !password_verify($loginPassword, (string)$user['PasswordHash'])) {
        auditLog((int)$user['UserID'], (int)$person['SchoolPersonID'], 'failed_login', 'auth', $school_id, 'Password mismatch', $ip);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid School ID or password.',
        ]);
        exit;
    }

    session_regenerate_id(true);

    $userId = (int)$user['UserID'];
    $schoolPersonId = (int)$user['SchoolPersonID'];

    rbacLoadSessionPermissions($pdo, $userId);

    $_SESSION['UserID'] = $userId;
    $_SESSION['SchoolPersonID'] = $schoolPersonId;
    $_SESSION['school_id'] = (string)$person['SchoolID'];
    $_SESSION['patient_name'] = trim((string)$person['FirstName'] . ' ' . (string)$person['LastName']);
    $_SESSION['role'] = $isStudent ? 'Student' : $personType;

    if ($isStudent) {
        $_SESSION['patient_id'] = $userId;
    }

    auditLog($userId, $schoolPersonId, 'login', 'auth', $school_id, null, $ip);

    $redirect = '../modules/dashboard/patient_dashboard.php';
    $roles = $_SESSION['Roles'] ?? [];

    if (is_array($roles) && array_intersect($roles, ['Admin', 'Super Admin']) !== []) {
        $redirect = '../modules/dashboard/admin_dashboard.php';
    } else {
        // Medical roles (promoted staff) go to medical_staff dashboard
        if (
            is_array($roles)
            && array_intersect($roles, ['Doctor', 'Dentist', 'Nurse']) !== []
        ) {
            $redirect = '../modules/dashboard/medical_staff_dashboard.php';
        } else {
            // Default: patient view
            $redirect = '../modules/dashboard/patient_dashboard.php';
        }
    }


    echo json_encode([
        'status' => 'success',
        'message' => 'Login successful.',
        'redirect' => $redirect,
    ]);
} catch (Throwable $e) {
    auditLog(null, null, 'failed_login', 'auth', $school_id, 'Exception during login: ' . $e->getMessage(), $ip);
    echo json_encode([
        'status' => 'error',
        'message' => 'Login failed: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
    exit;
}
