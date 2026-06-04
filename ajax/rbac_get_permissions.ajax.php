<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['UserID'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
    exit;
}

$pdo = require __DIR__ . '/../config/db_pdo.php';

$hasSuperAdmin = false;
if (isset($_SESSION['Roles']) && is_array($_SESSION['Roles'])) {
    $hasSuperAdmin = in_array('Super Admin', $_SESSION['Roles'], true);
}
if (!$hasSuperAdmin) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Access denied. Only Super Administrators can manage RBAC permissions.']);
    exit;
}

$roleId = (int)($_POST['role_id'] ?? 0);
if ($roleId <= 0) {
    echo json_encode(['ok' => false, 'message' => 'Invalid role ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT RoleName FROM roles WHERE RoleID = ? LIMIT 1");
    $stmt->execute([$roleId]);
    $roleRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $roleName = $roleRow ? (string)$roleRow['RoleName'] : '';

    $roleModuleMap = [
        'Student' => ['Records', 'Schedule'],
        'Staff'   => ['Records', 'Schedule'],
        'Faculty' => ['Records', 'Schedule'],
        'Doctor'  => ['Records', 'Reports', 'Schedule', 'Consultation', 'Medicine'],
        'Dentist' => ['Records', 'Reports', 'Schedule', 'Consultation', 'Medicine'],
        'Nurse'   => ['Records', 'Reports', 'Schedule', 'Consultation', 'Medicine'],
        'Admin'   => ['Admin Panel', 'User Management', 'Reports', 'Audit Logs'],
        'Super Admin' => ['Admin Panel', 'User Management', 'RBAC Management', 'Reports', 'Audit Logs'],
    ];

    // RBAC simplification: only Access and Manage are shown in the UI.
    // (Other permissions remain in the DB for future use.)
    $rolePermissionMap = [
        'Student'     => ['access'],
        'Staff'       => ['access'],
        'Faculty'     => ['access'],
        'Doctor'      => ['access', 'manage'],
        'Dentist'     => ['access', 'manage'],
        'Nurse'       => ['access', 'manage'],
        'Admin'       => ['access'],
        'Super Admin' => ['access'],
    ];

    $allowedModules = $roleModuleMap[$roleName] ?? [];
    $allowedPermissions = $rolePermissionMap[$roleName] ?? [];
    $isSuperAdminRole = ($roleName === 'Super Admin');

    $stmt = $pdo->prepare("SELECT ModuleID, ModuleName, Description FROM modules ORDER BY ModuleName ASC");
    $stmt->execute();
    $allModuleRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $modules = [];
    if ($isSuperAdminRole) {
        $modules = $allModuleRows;
    } else {
        $allowedSet = array_flip($allowedModules);
        foreach ($allModuleRows as $mod) {
            if (isset($allowedSet[$mod['ModuleName']])) {
                $modules[] = $mod;
            }
        }
    }

    $stmt = $pdo->prepare("SELECT PermissionID, PermissionName, Description FROM permissions ORDER BY PermissionName ASC");
    $stmt->execute();
    $allPermissionsRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $permissions = [];
    if ($isSuperAdminRole) {
        $permissions = $allPermissionsRaw;
    } else {
        // Case-insensitive match against allowed permission names
        $allowedPermSetLower = array_map('strtolower', $allowedPermissions);
        foreach ($allPermissionsRaw as $perm) {
            if (in_array(strtolower((string)$perm['PermissionName']), $allowedPermSetLower, true)) {
                $permissions[] = $perm;
            }
        }
    }

    $stmt = $pdo->prepare("SELECT rp.ModuleID, rp.PermissionID FROM role_permissions rp WHERE rp.RoleID = ?");
    $stmt->execute([$roleId]);
    $currentPerms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $permissionsMap = [];
    foreach ($currentPerms as $perm) {
        $key = ((int)$perm['ModuleID']) . '_' . ((int)$perm['PermissionID']);
        $permissionsMap[$key] = true;
    }

    echo json_encode([
        'ok' => true,
        'modules' => $modules,
        'allPermissions' => $permissions,
        'permissions' => $permissionsMap,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}