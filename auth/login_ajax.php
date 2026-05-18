<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db_pdo.php';
require_once __DIR__ . '/../includes/rbac.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../config/db.php'; // legacy patients lookup for UI compatibility

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method.',
    ]);
    exit;
}

$school_id = trim((string)($_POST['username'] ?? ''));
$loginPassword = (string)($_POST['password'] ?? '');

if ($school_id === '' || $loginPassword === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please enter your School ID and password.',
    ]);
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? null;

/**
 * Login rules (RBAC):
 * - authenticate using SchoolID + Password
 * - map identity via school_people -> users
 * - load RBAC permissions into session
 * - keep legacy session vars so existing UI keeps working (patient_id/patient_name/school_id/role)
 */
$pdo = require __DIR__ . '/../config/db_pdo.php';

try {
    // 1) Validate SchoolID exists in school_people (identity source)
    $spStmt = $pdo->prepare(
        "SELECT SchoolPersonID, SchoolID, Email, PersonType, FirstName, LastName
         FROM school_people
         WHERE SchoolID = ?
         LIMIT 1"
    );
    $spStmt->execute([$school_id]);
    $sp = $spStmt->fetch();

    if (!$sp) {
        auditLog(null, null, 'failed_login', 'auth', $school_id, 'SchoolID not found', $ip);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid School ID or password.',
        ]);
        exit;
    }

    // 2) Find user account by identity (Spec: school_people -> users)
    $userStmt = $pdo->prepare(
        "SELECT UserID, SchoolPersonID, PasswordHash, IsActive
         FROM users
         WHERE SchoolPersonID = ?
         LIMIT 1"
    );
    $userStmt->execute([(int)$sp['SchoolPersonID']]);
    $user = $userStmt->fetch();

    if (!$user || (int)$user['IsActive'] !== 1) {
        auditLog(null, (int)$sp['SchoolPersonID'], 'failed_login', 'auth', $school_id, 'User not found or inactive', $ip);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid School ID or password.',
        ]);
        exit;
    }

    // 3) Password verify
    if (empty($user['PasswordHash']) || !password_verify($loginPassword, (string)$user['PasswordHash'])) {
        auditLog((int)$user['UserID'], (int)$sp['SchoolPersonID'], 'failed_login', 'auth', $school_id, 'Password mismatch', $ip);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid School ID or password.',
        ]);
        exit;
    }

    // 4) Regenerate session to prevent fixation
    session_regenerate_id(true);

    $userId = (int)$user['UserID'];
    $schoolPersonId = (int)$user['SchoolPersonID'];

    // 5) Load RBAC permissions into session
    rbacLoadSessionPermissions($pdo, $userId);

    // Also store identity fields required by spec
    $_SESSION['SchoolPersonID'] = $schoolPersonId;

    // 6) Keep legacy session variables for UI compatibility
    //    Legacy modules check $_SESSION['patient_id'] existence.
    $patientId = null;
    $patientName = trim((string)($sp['FirstName'] ?? '')) . ' ' . trim((string)($sp['LastName'] ?? ''));
    $patientName = trim(preg_replace('/\s+/', ' ', $patientName));

    try {
        // mysqli legacy connection is created by config/db.php as $conn
        $legacyStmt = $conn->prepare(
            "SELECT PatientID, PatientFname, PatientLname, SchoolID, Role
             FROM patients
             WHERE SchoolID = ?
             LIMIT 1"
        );
        if ($legacyStmt) {
            $legacyStmt->bind_param('s', $school_id);
            $legacyStmt->execute();
            $legacyRes = $legacyStmt->get_result();
            if ($legacyRes && $legacyRes->num_rows === 1) {
                $legacyRow = $legacyRes->fetch_assoc();
                $patientId = (int)$legacyRow['PatientID'];
                $patientName = trim((string)$legacyRow['PatientFname'] . ' ' . (string)$legacyRow['PatientLname']);
                $_SESSION['school_id'] = (string)$legacyRow['SchoolID'];
                $_SESSION['role'] = (string)$legacyRow['Role'];
            } else {
                // Fallback: no legacy patient row; still set keys so UI can render, but guarded pages may block.
                $_SESSION['school_id'] = (string)($sp['SchoolID'] ?? $school_id);
                $_SESSION['role'] = 'Patient';
            }
            if ($patientId !== null) {
                $_SESSION['patient_id'] = $patientId;
            }
        }
    } catch (Throwable $e) {
        // Do not block RBAC login due to legacy compatibility issues.
    }

    // 7) Audit login
    auditLog($userId, $schoolPersonId, 'login', 'auth', $school_id, null, $ip);

    $redirect = '../modules/dashboard/dashboard.php';
    $roles = $_SESSION['Roles'] ?? [];
    if (is_array($roles) && array_intersect($roles, ['Admin', 'Super Admin']) !== []) {
        $redirect = '../modules/dashboard/admin_dashboard.php';
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
