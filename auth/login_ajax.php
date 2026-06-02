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

$schoolId = trim((string)($_POST['username'] ?? ''));
$loginPassword = (string)($_POST['password'] ?? '');
// (debug variable removed)
$ip = $_SERVER['REMOTE_ADDR'] ?? null;

if ($schoolId === '' || $loginPassword === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please enter your School ID and password.',
    ]);
    exit;
}

$pdo = require __DIR__ . '/../config/db_pdo.php';

try {
    // Normalize input for SchoolID matching
    $debugUserInput = $schoolId;


    $personStmt = $pdo->prepare(
        "SELECT SchoolPersonID, SchoolID, Email, PersonType, FirstName, LastName
         FROM school_people
         WHERE SchoolID = ?
         LIMIT 1"
    );

    $person = null;
    $usedFallbackSchoolIdMatch = false;

    $personStmt->execute([$schoolId]);
    $person = $personStmt->fetch();

    if (!$person) {
        // Try a normalized match (trim + case-insensitive). This fixes “exists in users but not found via SchoolID exact match”.
        $fallbackStmt = $pdo->prepare(
            "SELECT SchoolPersonID, SchoolID, Email, PersonType, FirstName, LastName
             FROM school_people
             WHERE LOWER(TRIM(SchoolID)) = LOWER(TRIM(?))
             LIMIT 1"
        );

        $fallbackStmt->execute([$debugUserInput]);
        $person = $fallbackStmt->fetch();

        if ($person) {
            $usedFallbackSchoolIdMatch = true;
            auditLog(null, null, 'login_debug_school_match', 'auth', $schoolId, 'matched via LOWER(TRIM(SchoolID))', $ip);
        }
    }

    if (!$usedFallbackSchoolIdMatch && $person) {
        auditLog(null, null, 'login_debug_school_match', 'auth', $schoolId, 'matched via exact SchoolID', $ip);
    }

    if (!$person) {
        auditLog(null, null, 'failed_login', 'auth', $schoolId, 'SchoolID not found in school_people', $ip);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid School ID or password.',
        ]);
        exit;
    }



    $userStmt = $pdo->prepare(
        "SELECT UserID, SchoolPersonID, PasswordHash, IsActive
         FROM users
         WHERE SchoolPersonID = ?
         LIMIT 1"
    );
    $userStmt->execute([(int)$person['SchoolPersonID']]);
    $user = $userStmt->fetch();

    if (!$user) {
        auditLog(null, (int)$person['SchoolPersonID'], 'failed_login', 'auth', $schoolId, 'User record not found', $ip);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid School ID or password.',
        ]);
        exit;
    }

    // HARD debug: verify hashing format mismatch (no plaintext)
    $computedHash = hash('sha256', $loginPassword);
    $storedHash = (string)($user['PasswordHash'] ?? '');

    auditLog(
        (int)$user['UserID'],
        (int)$person['SchoolPersonID'],
        'login_hash_debug',
        'auth',
        $schoolId,
        'storedLen=' . strlen($storedHash) . '; storedPrefix=' . substr($storedHash, 0, 16) . '; computedLen=' . strlen($computedHash) . '; computedPrefix=' . substr($computedHash, 0, 16) . '; equals=' . (hash_equals($storedHash, $computedHash) ? '1' : '0'),
        $ip
    );

    if ($storedHash === '' || !hash_equals($storedHash, $computedHash)) {
        auditLog((int)$user['UserID'], (int)$person['SchoolPersonID'], 'failed_login', 'auth', $schoolId, 'Password mismatch (hash debug logged)', $ip);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid School ID or password.',
        ]);
        exit;
    }

    // Password is correct - now check account status
    if ((int)$user['IsActive'] !== 1) {
        auditLog((int)$user['UserID'], (int)$person['SchoolPersonID'], 'failed_login', 'auth', $schoolId, 'Account inactive - login blocked', $ip);
        echo json_encode([
            'status' => 'error',
            'message' => 'Your account is currently blocked. Please contact an administrator.',
        ]);
        exit;
    }


    session_regenerate_id(true);

    $userId = (int)$user['UserID'];
    $schoolPersonId = (int)$user['SchoolPersonID'];

rbacLoadSessionPermissions($pdo, $userId);

    $roles = isset($_SESSION['Roles']) && is_array($_SESSION['Roles']) ? $_SESSION['Roles'] : [];
    $landingKey = rbacGetLandingDashboardKey($roles);

    // Temporary debug audit events (inspect audit_logs table)
    auditLog(
        (int)$userId,
        (int)$schoolPersonId,
        'login_debug_rbac_loaded',
        'auth',
        $schoolId,
        'roles=' . implode(',', $roles) . '; accessibleModulesCount=' . count($_SESSION['AccessibleModules'] ?? []),
        $ip
    );



    $_SESSION['UserID'] = $userId;
    $_SESSION['SchoolPersonID'] = $schoolPersonId;
    $_SESSION['school_id'] = (string)$person['SchoolID'];
    $_SESSION['patient_name'] = trim((string)$person['FirstName'] . ' ' . (string)$person['LastName']);
    $_SESSION['person_type'] = (string)$person['PersonType'];
    $_SESSION['role'] = $_SESSION['Role'] ?? (string)$person['PersonType'];

    $updateLoginStmt = $pdo->prepare("UPDATE users SET LastLogin = NOW() WHERE UserID = ?");
    $updateLoginStmt->execute([$userId]);

    auditLog($userId, $schoolPersonId, 'login', 'auth', $schoolId, 'Login successful', $ip);

    $redirect = '../modules/dashboard/patient_dashboard.php';
    if ($landingKey === 'admin') {
        $redirect = '../modules/dashboard/admin_dashboard.php';
    } elseif ($landingKey === 'medical') {
        $redirect = '../modules/dashboard/medical_staff_dashboard.php';
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Login successful.',
        'redirect' => $redirect,
        'roles_debug' => $roles,
        'session_userid_debug' => $_SESSION['UserID'] ?? null,
        'session_schoolpersonid_debug' => $_SESSION['SchoolPersonID'] ?? null,
    ]);
} catch (Throwable $e) {
    auditLog(null, null, 'failed_login', 'auth', $schoolId, 'Exception during login: ' . $e->getMessage(), $ip);
    echo json_encode([
        'status' => 'error',
        'message' => 'Login failed: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
    
    exit;

    
}
