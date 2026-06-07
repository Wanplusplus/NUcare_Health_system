<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../database/config/db_pdo.php';
require_once __DIR__ . '/../../backend/includes/rbac.php';
require_once __DIR__ . '/../../backend/includes/audit.php';

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

$pdo = require __DIR__ . '/../../database/config/db_pdo.php';

try {
 // Normalize input for SchoolID matching
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
 // Try a normalized match (trim + case-insensitive). This fixes "exists in users but not found via SchoolID exact match".
 $fallbackStmt = $pdo->prepare(
 "SELECT SchoolPersonID, SchoolID, Email, PersonType, FirstName, LastName
 FROM school_people
 WHERE LOWER(TRIM(SchoolID)) = LOWER(TRIM(?))
 LIMIT 1"
 );

 $fallbackStmt->execute([$schoolId]);
 $person = $fallbackStmt->fetch();
 if ($person) {
 $usedFallbackSchoolIdMatch = true;
 }
 }

 if (!$person) {
 // SchoolID was not found in school_people. Try to attribute this failed
 // attempt to a known UserID by doing a SchoolID lookup, so the audit_logs
 // row can satisfy the NOT NULL UserID constraint and be reported later.
 // If no such user exists, no audit log is created (cannot be attributed).
 $flUserId = null;
 try {
 $flLookup = $pdo->prepare("
 SELECT u.UserID
 FROM users u
 INNER JOIN school_people sp ON sp.SchoolPersonID = u.SchoolPersonID
 WHERE sp.SchoolID = ? OR LOWER(TRIM(sp.SchoolID)) = LOWER(TRIM(?))
 LIMIT 1
 ");
 $flLookup->execute([$schoolId, $schoolId]);
 $flRow = $flLookup->fetch();
 if ($flRow) {
 $flUserId = (int)$flRow['UserID'];
 }
 } catch (Throwable $e) { /* ignore */ }

 if ($flUserId !== null) {
 auditLog(
 $flUserId,
 null,
 'failed_login',
 'Authentication',
 null,
 'Failed login attempt for School ID: ' . $schoolId . ' (SchoolID not found in school_people)',
 $ip
 );
 }
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
 // No users row exists for this person. We cannot satisfy the NOT NULL
 // UserID constraint on audit_logs, so this attempt is not logged.
 echo json_encode([
 'status' => 'error',
 'message' => 'Invalid School ID or password.',
 ]);
 exit;
 }

 $storedHash = (string)($user['PasswordHash'] ?? '');
 $isPasswordValid = false;
 $hashInfo = password_get_info($storedHash);

 if (($hashInfo['algoName'] ?? 'unknown') !== 'unknown') {
 $isPasswordValid = password_verify($loginPassword, $storedHash);
 } else {
 $computedHash = hash('sha256', $loginPassword);
 $isPasswordValid = hash_equals($storedHash, $computedHash);
 }

 if ($storedHash === '' || !$isPasswordValid) {
 auditLog(
 (int)$user['UserID'],
 (int)$person['SchoolPersonID'],
 'failed_login',
 'Authentication',
 null,
 'Failed login attempt for School ID: ' . $schoolId . ' (Password mismatch)',
 $ip
 );
 echo json_encode([
 'status' => 'error',
 'message' => 'Invalid School ID or password.',
 ]);
 exit;
 }

 // Password is correct - now check account status
 if ((int)$user['IsActive'] !== 1) {
 auditLog(
 (int)$user['UserID'],
 (int)$person['SchoolPersonID'],
 'failed_login',
 'Authentication',
 null,
 'Failed login attempt for School ID: ' . $schoolId . ' (Account inactive - login blocked)',
 $ip
 );
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

 $_SESSION['UserID'] = $userId;
 $_SESSION['SchoolPersonID'] = $schoolPersonId;
 $_SESSION['school_id'] = (string)$person['SchoolID'];
 $_SESSION['patient_name'] = trim((string)$person['FirstName'] . ' ' . (string)$person['LastName']);
 $_SESSION['person_type'] = (string)$person['PersonType'];
 $_SESSION['role'] = $_SESSION['Role'] ?? (string)$person['PersonType'];

 $updateLoginStmt = $pdo->prepare("UPDATE users SET LastLogin = NOW() WHERE UserID = ?");
 $updateLoginStmt->execute([$userId]);

 auditLog($userId, $schoolPersonId, 'login', 'auth', $schoolId, 'Login successful', $ip);

 $redirect = '/NUcare_Health_system/frontend/student/dashboard/patient_dashboard.php';
 if ($landingKey === 'admin') {
 $redirect = '/NUcare_Health_system/frontend/admin/dashboard/admin_dashboard.php';
 } elseif ($landingKey === 'medical') {
 $redirect = '/NUcare_Health_system/frontend/medical_staff/dashboard/medical_staff_dashboard.php';
 }

 echo json_encode([
 'status' => 'success',
 'message' => 'Login successful.',
 'redirect' => $redirect,
 ]);
} catch (Throwable $e) {
 // Best-effort attribution for system exceptions. We try to map the typed
 // SchoolID to a known UserID; if found we record a failed_login audit row
 // in the Authentication module. If no user can be attributed, the event is
 // skipped (audit_logs.UserID is NOT NULL by schema).
 $flUserId = null;
 try {
 $flLookup = $pdo->prepare("
 SELECT u.UserID
 FROM users u
 INNER JOIN school_people sp ON sp.SchoolPersonID = u.SchoolPersonID
 WHERE sp.SchoolID = ? OR LOWER(TRIM(sp.SchoolID)) = LOWER(TRIM(?))
 LIMIT 1
 ");
 $flLookup->execute([$schoolId, $schoolId]);
 $flRow = $flLookup->fetch();
 if ($flRow) {
 $flUserId = (int)$flRow['UserID'];
 }
 } catch (Throwable $e2) { /* ignore */ }

 if ($flUserId !== null) {
 auditLog(
 $flUserId,
 null,
 'failed_login',
 'Authentication',
 null,
 'Failed login attempt for School ID: ' . $schoolId . ' (Exception during login: ' . $e->getMessage() . ')',
 $ip
 );
 }
 echo json_encode([
 'status' => 'error',
 'message' => 'Login failed: ' . $e->getMessage(),
 'file' => $e->getFile(),
 'line' => $e->getLine(),
 ]);
 
 exit;

 
}




