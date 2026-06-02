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

// Verify RBAC access
require_once __DIR__ . '/../includes/module_guard.php';
try {
    requireModule('Admin Panel', 'access');
} catch (Throwable $e) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Access denied']);
    exit;
}

$roleId = (int)($_POST['role_id'] ?? 0);

if ($roleId <= 0) {
    echo json_encode(['ok' => false, 'message' => 'Invalid role ID']);
    exit;
}

try {
    // Fetch all modules
    $modulesSql = "
        SELECT ModuleID, ModuleName, Description
        FROM modules
        ORDER BY ModuleName ASC
    ";
    $stmt = $pdo->prepare($modulesSql);
    $stmt->execute();
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all permissions
    $permsSql = "
        SELECT PermissionID, PermissionName, Description
        FROM permissions
        ORDER BY PermissionName ASC
    ";
    $stmt = $pdo->prepare($permsSql);
    $stmt->execute();
    $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch all valid module-permission combinations from ANY role
    // This shows which module-permission pairs are used in the system
    $modulePermSql = "
        SELECT DISTINCT ModuleID, PermissionID
        FROM role_permissions
        ORDER BY ModuleID, PermissionID
    ";
    $stmt = $pdo->prepare($modulePermSql);
    $stmt->execute();
    $modulePermissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch current permissions for this role
    $currentPermsSql = "
        SELECT rp.ModuleID, rp.PermissionID
        FROM role_permissions rp
        WHERE rp.RoleID = ?
    ";
    $stmt = $pdo->prepare($currentPermsSql);
    $stmt->execute([$roleId]);
    $currentPerms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Build permission lookup: "moduleId_permissionId" => true
    $permissionsMap = [];
    foreach ($currentPerms as $perm) {
        $key = ((int)$perm['ModuleID']) . '_' . ((int)$perm['PermissionID']);
        $permissionsMap[$key] = true;
    }

    echo json_encode([
        'ok' => true,
        'modules' => $modules,
        'allPermissions' => $permissions,
        'modulePermissions' => $modulePermissions,
        'permissions' => $permissionsMap,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
