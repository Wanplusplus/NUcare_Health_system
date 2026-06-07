<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
 session_start();
}

// Verify session
if (!isset($_SESSION['UserID'])) {
 http_response_code(401);
 echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
 exit;
}

$pdo = require __DIR__ . '/../../database/config/db_pdo.php';

// Verify Super Admin access
$hasSuperAdmin = false;
if (isset($_SESSION['Roles']) && is_array($_SESSION['Roles'])) {
 $hasSuperAdmin = in_array('Super Admin', $_SESSION['Roles'], true);
}
if (!$hasSuperAdmin) {
 http_response_code(403);
 echo json_encode(['ok' => false, 'message' => 'Access denied. Only Super Administrators can save RBAC permissions.']);
 exit;
}

// Read input: support both JSON and form-encoded requests
$rawInput = file_get_contents('php://input');
$data = null;
if (is_string($rawInput) && $rawInput !== '') {
 $decoded = json_decode($rawInput, true);
 if (is_array($decoded)) {
 $data = $decoded;
 }
}

if (!is_array($data)) {
 // Fallback to form-encoded
 $data = $_POST;
}

if (!is_array($data) || empty($data)) {
 echo json_encode([
 'ok' => false,
 'message' => 'Invalid or empty request body. Raw input: ' . substr((string)$rawInput, 0, 200),
 ]);
 exit;
}

$roleId = (int)($data['role_id'] ?? 0);
$permissions = $data['permissions'] ?? [];

if ($roleId <= 0) {
 echo json_encode(['ok' => false, 'message' => 'Invalid role ID']);
 exit;
}

if (!is_array($permissions)) {
 echo json_encode(['ok' => false, 'message' => 'Invalid permissions format']);
 exit;
}

// Verify the role exists and is not Super Admin
try {
 $roleStmt = $pdo->prepare("SELECT RoleName FROM roles WHERE RoleID = ? LIMIT 1");
 $roleStmt->execute([$roleId]);
 $roleRow = $roleStmt->fetch(PDO::FETCH_ASSOC);

 if (!$roleRow) {
 echo json_encode(['ok' => false, 'message' => 'Role not found']);
 exit;
 }

 if ($roleRow['RoleName'] === 'Super Admin') {
 http_response_code(403);
 echo json_encode(['ok' => false, 'message' => 'Cannot modify Super Admin permissions']);
 exit;
 }

 $roleName = $roleRow['RoleName'];
} catch (Throwable $e) {
 http_response_code(500);
 echo json_encode(['ok' => false, 'message' => 'Database error: ' . $e->getMessage()]);
 exit;
}

try {
 $pdo->beginTransaction();

 // Delete all existing permissions for this role
 $deleteStmt = $pdo->prepare("DELETE FROM role_permissions WHERE RoleID = ?");
 $deleteStmt->execute([$roleId]);

 // Insert new permissions
 $insertStmt = $pdo->prepare(
 "INSERT INTO role_permissions (RoleID, ModuleID, PermissionID) VALUES (?, ?, ?)"
 );

 $insertCount = 0;
 $skipped = [];
 foreach ($permissions as $perm) {
 $moduleId = (int)($perm['module_id'] ?? 0);
 $permissionId = (int)($perm['permission_id'] ?? 0);

 if ($moduleId <= 0 || $permissionId <= 0) {
 $skipped[] = ['reason' => 'invalid_ids', 'data' => $perm];
 continue;
 }

 // Verify module exists
 $moduleStmt = $pdo->prepare("SELECT ModuleID FROM modules WHERE ModuleID = ? LIMIT 1");
 $moduleStmt->execute([$moduleId]);
 if (!$moduleStmt->fetch()) {
 $skipped[] = ['reason' => 'module_not_found', 'module_id' => $moduleId];
 continue;
 }

 // Verify permission exists
 $permStmt = $pdo->prepare("SELECT PermissionID FROM permissions WHERE PermissionID = ? LIMIT 1");
 $permStmt->execute([$permissionId]);
 if (!$permStmt->fetch()) {
 $skipped[] = ['reason' => 'permission_not_found', 'permission_id' => $permissionId];
 continue;
 }

 $insertStmt->execute([$roleId, $moduleId, $permissionId]);
 $insertCount++;
 }

 $pdo->commit();

 // Audit log
 $actorUserId = isset($_SESSION['UserID']) ? (int)$_SESSION['UserID'] : null;
 $actorSchoolPersonId = isset($_SESSION['SchoolPersonID']) ? (int)$_SESSION['SchoolPersonID'] : null;

 if (function_exists('auditLog')) {
 auditLog(
 $actorUserId,
 $actorSchoolPersonId,
 'rbac_permissions_updated',
 'roles',
 (string)$roleId,
 "Updated permissions for {$roleName} role ({$insertCount} permissions assigned)",
 $_SERVER['REMOTE_ADDR'] ?? null
 );
 }

 echo json_encode([
 'ok' => true,
 'message' => "Role permissions updated successfully. {$insertCount} permissions assigned.",
 'role' => $roleName,
 'inserted' => $insertCount,
 'skipped' => $skipped,
 ]);
} catch (Throwable $e) {
 $pdo->rollBack();
 http_response_code(500);
 echo json_encode(['ok' => false, 'message' => 'Failed to update permissions: ' . $e->getMessage()]);
}



