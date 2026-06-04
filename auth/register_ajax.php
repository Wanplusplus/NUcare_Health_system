<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_pdo.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/rbac.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? null;

// 1) Extract POST variables ONCE
$first_name = trim((string)($_POST['first_name'] ?? ''));
$last_name = trim((string)($_POST['last_name'] ?? ''));
$middle_name = trim((string)($_POST['middle_name'] ?? ''));
$sex = trim((string)($_POST['sex'] ?? ''));
$school_id = trim((string)($_POST['school_id'] ?? ''));
$email = strtolower(trim((string)($_POST['email'] ?? '')));
$password = (string)($_POST['password'] ?? '');


$confirm_password = (string)($_POST['confirm_password'] ?? '');
if ($confirm_password === '') {
    $confirm_password = $password;
}

// 2) Validate required fields
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

// 3) Validate password match
if ($password !== $confirm_password) {
    echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
    exit;
}

if ($sex !== 'Male' && $sex !== 'Female') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid sex selected.']);
    exit;
}

try {
    $pdo->beginTransaction();


    // 4) Lookup school_people
    $spStmt = $pdo->prepare(
        "SELECT SchoolPersonID, SchoolID, PersonType
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

    // 5) Ensure user does not already exist
    $dupStmt = $pdo->prepare('SELECT UserID FROM users WHERE SchoolPersonID = ? LIMIT 1');
    $dupStmt->execute([(int)$sp['SchoolPersonID']]);
    if ($dupStmt->fetch()) {
        $pdo->rollBack();
        auditLog(null, (int)$sp['SchoolPersonID'], 'failed_signup', 'auth', $school_id, 'User already exists for SchoolPersonID', $ip);
        echo json_encode(['status' => 'error', 'message' => 'Account already exists.']);
        exit;
    }

    // 6) Assign default role based on PersonType (per your spec: role only; PersonType is identifier)
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

    // 7) Compute hashed password (SHA2-compatible)
    $hashedPassword = hash('sha256', $password);



    // 8) INSERT into users using THAT hash
    $userInsert = $pdo->prepare(
        'INSERT INTO users (SchoolPersonID, PasswordHash, IsActive)
         VALUES (?, ?, 1)'
    );
    $userInsert->execute([(int)$sp['SchoolPersonID'], $hashedPassword]);
    $userId = (int)$pdo->lastInsertId();

    // 9) Assign RBAC role
    rbacInsertRoleByName($pdo, $userId, $roleName);
    rbacEnsureRolePermissionsForRole($pdo, $roleName);

    $pdo->commit();

    auditLog($userId, (int)$sp['SchoolPersonID'], 'signup', 'auth', $school_id, 'Signup successful. Assigned role: ' . $roleName, $ip);

    // 10) commit done, 11) return success JSON
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

