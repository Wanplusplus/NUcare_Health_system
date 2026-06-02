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

$pdo = require __DIR__ . '/../config/db_pdo.php';
require_once __DIR__ . '/../includes/module_guard.php';
require_once __DIR__ . '/../includes/audit.php';

// Verify RBAC access
try {
    requireModule('Admin Panel', 'access');
} catch (Throwable $e) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Access denied']);
    exit;
}

// Get JSON body
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    echo json_encode(['ok' => false, 'message' => 'Invalid JSON']);
    exit;
}

$roleId = (int)($data['role_id'] ?? 0);
$permissions = $data['permissions'] ?? [];

if ($roleId <= 0) {
    echo json_encode(['ok' => false, 'message' => 'Invalid role ID']);
    exit;
}

// Check if role is Super Admin (protect it)
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
    echo json_encode(['ok' => false, 'message' => 'Database error']);
    exit;
}

// Validate permissions array
if (!is_array($permissions)) {
    echo json_encode(['ok' => false, 'message' => 'Invalid permissions format']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Step 1: Delete all existing permissions for this role
    $deleteStmt = $pdo->prepare("DELETE FROM role_permissions WHERE RoleID = ?");
    $deleteStmt->execute([$roleId]);

    // Step 2: Insert new permissions
    $insertStmt = $pdo->prepare(
        "INSERT INTO role_permissions (RoleID, ModuleID, PermissionID) VALUES (?, ?, ?)"
    );

    $insertCount = 0;
    foreach ($permissions as $perm) {
        $moduleId = (int)($perm['module_id'] ?? 0);
        $permissionId = (int)($perm['permission_id'] ?? 0);

        if ($moduleId <= 0 || $permissionId <= 0) {
            continue;
        }

        // Verify module and permission exist
        $moduleStmt = $pdo->prepare("SELECT ModuleID FROM modules WHERE ModuleID = ? LIMIT 1");
        $moduleStmt->execute([$moduleId]);
        if (!$moduleStmt->fetch()) {
            continue; // Skip invalid module
        }

        $permStmt = $pdo->prepare("SELECT PermissionID FROM permissions WHERE PermissionID = ? LIMIT 1");
        $permStmt->execute([$permissionId]);
        if (!$permStmt->fetch()) {
            continue; // Skip invalid permission
        }

        $insertStmt->execute([$roleId, $moduleId, $permissionId]);
        $insertCount++;
    }

    $pdo->commit();

    // Step 3: Audit log
    $actorUserId = isset($_SESSION['UserID']) ? (int)$_SESSION['UserID'] : null;
    $actorSchoolPersonId = isset($_SESSION['SchoolPersonID']) ? (int)$_SESSION['SchoolPersonID'] : null;

    auditLog(
        $actorUserId,
        $actorSchoolPersonId,
        'rbac_permissions_updated',
        'roles',
        $roleId,
        "Updated permissions for {$roleName} role ({$insertCount} permissions assigned)",
        $_SERVER['REMOTE_ADDR'] ?? null
    );

    echo json_encode([
        'ok' => true,
        'message' => 'Role permissions updated successfully',
        'permissions_count' => $insertCount,
    ]);
} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Failed to update permissions: ' . $e->getMessage()]);
}
