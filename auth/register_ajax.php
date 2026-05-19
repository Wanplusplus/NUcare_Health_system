<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_pdo.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../config/db.php'; // legacy fallback for medicalprofessionals

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$first_name       = trim((string)($_POST['first_name'] ?? ''));
$last_name        = trim((string)($_POST['last_name'] ?? ''));
$middle_name      = trim((string)($_POST['middle_name'] ?? ''));
$sex              = trim((string)($_POST['sex'] ?? ''));
$school_id        = trim((string)($_POST['school_id'] ?? ''));
$email            = strtolower(trim((string)($_POST['email'] ?? '')));
$password         = (string)($_POST['password'] ?? '');
$confirm_password= (string)($_POST['confirm_password'] ?? '');

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
    // 1) Validate school_id exists in school_people (spec: signup only if SchoolID exists)
    $spStmt = $pdo->prepare(
        "SELECT SchoolPersonID, SchoolID, Email, PersonType
         FROM school_people
         WHERE SchoolID = ?
         LIMIT 1"
    );
    $spStmt->execute([$school_id]);
    $sp = $spStmt->fetch();

    if (!$sp) {
        auditLog(null, null, 'failed_signup', 'auth', $school_id, 'SchoolID not found in school_people', $ip);
        echo json_encode(['status' => 'error', 'message' => 'Invalid School ID.']);
        exit;
    }

    // 2) Reject if account already exists in users (spec: by SchoolPersonID)
    $dupStmt = $pdo->prepare("SELECT UserID FROM users WHERE SchoolPersonID = ? LIMIT 1");
    $dupStmt->execute([(int)$sp['SchoolPersonID']]);
    if ($dupStmt->fetch()) {
        auditLog(null, (int)$sp['SchoolPersonID'], 'failed_signup', 'auth', $school_id, 'User already exists for SchoolPersonID', $ip);
        echo json_encode(['status' => 'error', 'message' => 'Account already exists.']);
        exit;
    }

    // 3) Determine role by PersonType only (spec)
    $personType = (string)$sp['PersonType'];
    $roleName = match ($personType) {
        'Student' => 'Student',
        'Faculty' => 'Faculty',
        'Staff' => 'Staff',
        default => 'Student',
    };

    // 5) Create users account
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

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

    // 6) Assign role automatically (user_roles)
    $roleIdStmt = $pdo->prepare("SELECT RoleID FROM roles WHERE RoleName = ? LIMIT 1");
    $roleIdStmt->execute([$roleName]);
    $roleIdRow = $roleIdStmt->fetch();

    if (!$roleIdRow) {
        auditLog($userId, (int)$sp['SchoolPersonID'], 'signup', 'auth', $school_id, 'Role mapping failed (role not found: ' . $roleName . ')', $ip);
        echo json_encode([
            'status' => 'error',
            'message' => 'Signup role assignment failed: role not found for roleName=' . $roleName,
        ]);
        exit;
    }

    $urInsert = $pdo->prepare("INSERT INTO user_roles (UserID, RoleID) VALUES (?, ?)");
    $urInsert->execute([$userId, (int)$roleIdRow['RoleID']]);

    auditLog($userId, (int)$sp['SchoolPersonID'], 'signup', 'auth', $school_id, 'Signup successful. Assigned role: ' . $roleName, $ip);

    echo json_encode([
        'status' => 'success',
        'message' => 'Registration successful. Redirecting to login...',
    ]);
} catch (Throwable $e) {
    auditLog(null, null, 'failed_signup', 'auth', $school_id, 'Exception during signup: ' . $e->getMessage(), $ip);
    echo json_encode([
        'status' => 'error',
        'message' => 'Registration failed: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
}
