<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_pdo.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/rbac.php';
require_once __DIR__ . '/../config/db.php'; // legacy fallback for medicalprofessionals

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$first_name = trim((string)($_POST['first_name'] ?? ''));
$last_name = trim((string)($_POST['last_name'] ?? ''));
$middle_name = trim((string)($_POST['middle_name'] ?? ''));
$sex = trim((string)($_POST['sex'] ?? ''));
$school_id = trim((string)($_POST['school_id'] ?? ''));
$email = strtolower(trim((string)($_POST['email'] ?? '')));
$password = (string)($_POST['password'] ?? '');
$confirm_password = (string)($_POST['confirm_password'] ?? '');

$ip = $_SERVER['REMOTE_ADDR'] ?? null;

if ($first_name === '' || $last_name === '' || $sex === '' || $school_id === '' || $email === '' || $password === '' || $confirm_password === '') {
    echo json_encode(['status' => 'error', 'message' => 'All required fields must be filled.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email address format.']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters.']);
    exit;
}

if ($password !== $confirm_password) {
    echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
    exit;
}

if ($sex !== 'Male' && $sex !== 'Female') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid sex selected.']);
    exit;
}

$pdo = require __DIR__ . '/../config/db_pdo.php';

try {
    $pdo->beginTransaction();

    $spStmt = $pdo->prepare(
        "SELECT SchoolPersonID, SchoolID, Email, PersonType
         FROM school_people
         WHERE SchoolID = ?
         LIMIT 1"
    );
    $spStmt->execute([$school_id]);
    $sp = $spStmt->fetch();

    if (!$sp) {
        $pdo->rollBack();
        auditLog(null, null, 'failed_signup', 'auth', $school_id, 'SchoolID not found in school_people', $ip);
        echo json_encode(['status' => 'error', 'message' => 'Invalid School ID.']);
        exit;
    }

    $dupStmt = $pdo->prepare("SELECT UserID FROM users WHERE SchoolPersonID = ? LIMIT 1");
    $dupStmt->execute([(int)$sp['SchoolPersonID']]);
    if ($dupStmt->fetch()) {
        $pdo->rollBack();
        auditLog(null, (int)$sp['SchoolPersonID'], 'failed_signup', 'auth', $school_id, 'User already exists for SchoolPersonID', $ip);
        echo json_encode(['status' => 'error', 'message' => 'Account already exists.']);
        exit;
    }

    $personType = rtrim((string)$sp['PersonType']);
    $roleName = match ($personType) {
        'Student' => 'Student',
        'Faculty' => 'Faculty',
        'Staff' => 'Staff',
        default => null,
    };

    if ($roleName === null) {
        $pdo->rollBack();
        auditLog(null, (int)$sp['SchoolPersonID'], 'failed_signup', 'auth', $school_id, 'Unsupported PersonType: ' . $personType, $ip);
        echo json_encode(['status' => 'error', 'message' => 'Signup role assignment failed: unsupported person type.']);
        exit;
    }

    // PasswordHash is stored as MySQL SHA2(password, 256)
    $hashedPassword = hash('sha256', $password);
    $middleNameVal = $middle_name !== '' ? $middle_name : null;

    $displayName = trim($first_name . ' ' . $middleNameVal . ' ' . $last_name);
    $displayName = preg_replace('/\s+/', ' ', $displayName);

    $userInsert = $pdo->prepare(
        "INSERT INTO users (SchoolPersonID, PasswordHash, IsActive)
         VALUES (?, ?, 1)"
    );
    $userInsert->execute([
        (int)$sp['SchoolPersonID'],
        $hashedPassword,
    ]);

    $userId = (int)$pdo->lastInsertId();

rbacInsertRoleByName($pdo, $userId, $roleName);
    rbacEnsureRolePermissionsForRole($pdo, $roleName);

    // Debug: confirm role and permission sync after signup
    rbacDebug('signup_after_insert', [
        'school_id' => (string)$school_id,
        'school_person_id' => (int)$sp['SchoolPersonID'],
        'personType' => $personType,
        'assigned_role' => $roleName,
        'userId' => $userId,
    ]);

    $pdo->commit();

    auditLog($userId, (int)$sp['SchoolPersonID'], 'signup', 'auth', $school_id, 'Signup successful. Assigned role: ' . $roleName, $ip);


    echo json_encode([
        'status' => 'success',
        'message' => 'Registration successful. Redirecting to login...',
        'role' => $roleName,
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    auditLog(null, null, 'failed_signup', 'auth', $school_id, 'Exception during signup: ' . $e->getMessage(), $ip);
    echo json_encode([
        'status' => 'error',
        'message' => 'Registration failed: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
}
