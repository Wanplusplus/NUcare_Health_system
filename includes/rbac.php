<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db_pdo.php';

function rbacGetUserIdFromSession(): ?int
{
    return isset($_SESSION['UserID']) ? (int)$_SESSION['UserID'] : null;
}

function rbacGetSchoolPersonIdFromSession(): ?int
{
    return isset($_SESSION['SchoolPersonID']) ? (int)$_SESSION['SchoolPersonID'] : null;
}

function rbacNormalizeRoleName(string $roleName): string
{
    // Normalize whitespace + case to avoid role/persontype mismatch issues.
    // Database values must still match after normalization.
    $roleName = trim($roleName);
    $roleName = preg_replace('/\s+/', ' ', $roleName) ?? $roleName;
    return $roleName;
}


function rbacGetUserRoles(PDO $pdo, int $userId): array
{
    $sql = "
        SELECT DISTINCT r.RoleName
        FROM user_roles ur
        INNER JOIN roles r ON r.RoleID = ur.RoleID
        WHERE ur.UserID = ?
        ORDER BY r.RoleName ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);

    $roles = array_map(static fn (array $row): string => (string)$row['RoleName'], $stmt->fetchAll());
    return array_values(array_unique(array_map('rbacNormalizeRoleName', $roles)));
}

function getUserRoles(int $userId): array
{
    $pdo = require __DIR__ . '/../config/db_pdo.php';
    return rbacGetUserRoles($pdo, $userId);
}

function getUserPermissions(int $userId): array
{
    $pdo = require __DIR__ . '/../config/db_pdo.php';
    $sql = "
        SELECT DISTINCT p.PermissionName, m.ModuleName
        FROM user_roles ur
        INNER JOIN role_permissions rp ON rp.RoleID = ur.RoleID
        INNER JOIN permissions p ON p.PermissionID = rp.PermissionID
        INNER JOIN modules m ON m.ModuleID = rp.ModuleID
        WHERE ur.UserID = ?
        ORDER BY m.ModuleName ASC, p.PermissionName ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function hasPermission(int $userId, string $moduleName, string $permissionName): bool
{
    $pdo = require __DIR__ . '/../config/db_pdo.php';
    $sql = "
        SELECT 1
        FROM user_roles ur
        INNER JOIN role_permissions rp ON rp.RoleID = ur.RoleID
        INNER JOIN permissions p ON p.PermissionID = rp.PermissionID
        INNER JOIN modules m ON m.ModuleID = rp.ModuleID
        WHERE ur.UserID = ?
          AND m.ModuleName = ?
          AND p.PermissionName = ?
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $moduleName, $permissionName]);
    return $stmt->fetchColumn() !== false;
}

function rbacGetPersonType(PDO $pdo, int $schoolPersonId): ?string
{
    $stmt = $pdo->prepare("SELECT PersonType FROM school_people WHERE SchoolPersonID = ? LIMIT 1");
    $stmt->execute([$schoolPersonId]);
    $val = $stmt->fetchColumn();
    if ($val === false) {
        return null;
    }
    $personType = (string)$val;
    $personType = trim($personType);
    $personType = preg_replace('/\s+/', ' ', $personType) ?? $personType;
    // Case normalization to ensure match against 'Student'|'Faculty'|'Staff'
    return ucwords(strtolower($personType));
}


function rbacGetRoleId(PDO $pdo, string $roleName): ?int
{
    $stmt = $pdo->prepare("SELECT RoleID FROM roles WHERE RoleName = ? LIMIT 1");
    // Normalize then also try case-normalized match.
    $normalized = rbacNormalizeRoleName($roleName);
    $stmt->execute([$normalized]);
    $val = $stmt->fetchColumn();
    if ($val !== false) {
        return (int)$val;
    }

    // Fallback: match case-insensitively (covers DB seeded variants like 'doctor').
    $stmt2 = $pdo->prepare("SELECT RoleID FROM roles WHERE LOWER(TRIM(RoleName)) = LOWER(TRIM(?)) LIMIT 1");
    $stmt2->execute([$normalized]);
    $val2 = $stmt2->fetchColumn();
    return $val2 !== false ? (int)$val2 : null;
}


function rbacGetUserSchoolPersonId(PDO $pdo, int $userId): ?int
{
    $stmt = $pdo->prepare("SELECT SchoolPersonID FROM users WHERE UserID = ? LIMIT 1");
    $stmt->execute([$userId]);
    $val = $stmt->fetchColumn();
    return $val !== false ? (int)$val : null;
}

function rbacInsertRoleByName(PDO $pdo, int $userId, string $roleName): void
{
    $roleName = rbacNormalizeRoleName($roleName);
    $roleId = rbacGetRoleId($pdo, $roleName);
    if ($roleId === null) {
        // If role doesn't exist in DB, we can't insert user_roles.
        // Keeping silent to avoid breaking existing flows.
        return;
    }

    $stmt = $pdo->prepare("INSERT IGNORE INTO user_roles (UserID, RoleID) VALUES (?, ?)");
    $stmt->execute([$userId, $roleId]);
}


function rbacEnsureDefaultRoleAssignment(PDO $pdo, int $userId): array
{
    $roles = rbacGetUserRoles($pdo, $userId);
    if ($roles !== []) {
        return $roles;
    }

    $schoolPersonId = rbacGetUserSchoolPersonId($pdo, $userId);
    if ($schoolPersonId === null) {
        return [];
    }

    $personType = rbacGetPersonType($pdo, $schoolPersonId);
    $defaultRole = match ($personType) {
        'Student' => 'Student',
        'Faculty' => 'Faculty',
        'Staff' => 'Staff',
        default => null,
    };

    // If PersonType is present but unrecognized, we do not assign.
    if ($defaultRole !== null) {
        rbacInsertRoleByName($pdo, $userId, $defaultRole);
    }

    return rbacGetUserRoles($pdo, $userId);
}


function rbacEnsureRolePermissionsForRole(PDO $pdo, string $roleName): void
{
    $roleName = rbacNormalizeRoleName($roleName);
    if ($roleName === '') {
        return;
    }

    $matrix = [
        'Student' => [
            'modules' => ['Records', 'Schedule'],
            'permissions' => ['access'],
        ],
        'Faculty' => [
            'modules' => ['Records', 'Schedule'],
            'permissions' => ['access'],
        ],
        'Staff' => [
            'modules' => ['Records', 'Schedule'],
            'permissions' => ['access'],
        ],
        'Doctor' => [
            'modules' => ['Consultation', 'Records', 'Reports', 'Medicine', 'Schedule'],
            'permissions' => ['access', 'manage'],
        ],
        'Dentist' => [
            'modules' => ['Consultation', 'Records', 'Reports', 'Medicine', 'Schedule'],
            'permissions' => ['access', 'manage'],
        ],
        'Nurse' => [
            'modules' => ['Consultation', 'Records', 'Reports', 'Medicine', 'Schedule'],
            'permissions' => ['access', 'manage'],
        ],
        'Admin' => [
            'modules' => ['Admin Panel', 'User Management', 'Reports', 'Audit Logs'],
            'permissions' => ['access'],
        ],
        'Super Admin' => [
            'modules' => ['Admin Panel', 'User Management', 'RBAC Management', 'Reports', 'Audit Logs'],
            'permissions' => ['access'],
        ],
    ];

    if (!isset($matrix[$roleName])) {
        return;
    }

    $modules = $matrix[$roleName]['modules'];
    $permissions = $matrix[$roleName]['permissions'];

    $modulePlaceholders = implode(',', array_fill(0, count($modules), '?'));
    $permissionPlaceholders = implode(',', array_fill(0, count($permissions), '?'));

    $sql = "
        INSERT IGNORE INTO role_permissions (RoleID, ModuleID, PermissionID)
        SELECT r.RoleID, m.ModuleID, p.PermissionID
        FROM roles r
        INNER JOIN modules m ON m.ModuleName IN ($modulePlaceholders)
        INNER JOIN permissions p ON p.PermissionName IN ($permissionPlaceholders)
        WHERE r.RoleName = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($modules, $permissions, [$roleName]));
}

function rbacEnsureRolePermissionsForRoles(PDO $pdo, array $roles): void
{
    foreach (array_unique(array_map('rbacNormalizeRoleName', $roles)) as $roleName) {
        rbacEnsureRolePermissionsForRole($pdo, $roleName);
    }
}

/**
 * Debug helper: log RBAC inputs without modifying UI.
 */
function rbacDebug(string $stage, array $context = []): void
{
    // Temporarily enabled for diagnosing signup/promotion sync issues.
    // Disable later by setting to false.
$enabled = false;
    if (!$enabled) {
        return;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $payload = json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    error_log('[RBAC DEBUG] ' . $stage . ' ip=' . $ip . ' ctx=' . $payload);
}


function rbacLoadSessionPermissions(PDO $pdo, int $userId): void
{
    // Standard session structure (required by requirements)

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $roles = rbacEnsureDefaultRoleAssignment($pdo, $userId);
    // FIX #1: Removed automatic permission re-seeding on login.
    // rbacEnsureRolePermissionsForRoles() was inserting a hardcoded matrix
    // into role_permissions on every login, overwriting RBAC Management changes.
    // role_permissions is now the sole source of truth.
    // rbacEnsureRolePermissionsForRole() / rbacEnsureRolePermissionsForRoles()
    // are retained in the file but no longer called from this path.

    // SchoolPersonID + SchoolID
    $schoolPersonId = rbacGetUserSchoolPersonId($pdo, $userId);
    $schoolId = null;
    if ($schoolPersonId !== null) {
        $stmt = $pdo->prepare("SELECT SchoolID FROM school_people WHERE SchoolPersonID = ? LIMIT 1");
        $stmt->execute([$schoolPersonId]);
        $schoolId = $stmt->fetchColumn();
    }

    $_SESSION['UserID'] = $userId;
    $_SESSION['SchoolPersonID'] = $schoolPersonId ?? null;
    $_SESSION['SchoolID'] = $schoolId !== false ? (string)$schoolId : null;

    $_SESSION['Roles'] = $roles;
    $_SESSION['Permissions'] = []; // filled below


    $perms = getUserPermissions($userId);
    $rolePermissions = [];
    $accessibleModules = [];

    foreach ($perms as $row) {
        $moduleName = (string)$row['ModuleName'];
        $permissionName = (string)$row['PermissionName'];
        $rolePermissions[] = ['module' => $moduleName, 'permission' => $permissionName];
        $accessibleModules[$moduleName] = true;
    }

    $_SESSION['Permissions'] = $rolePermissions;
    // Keep legacy key (used by some UI) but do not rely on it for auth.
    $_SESSION['AccessibleModules'] = array_keys($accessibleModules);

}

function rbacGetLandingDashboardKey(array $roles): string
{
    $roles = array_values(array_unique(array_map('rbacNormalizeRoleName', $roles)));

    if (array_intersect($roles, ['Admin', 'Super Admin']) !== []) {
        return 'admin';
    }

    if (array_intersect($roles, ['Doctor', 'Dentist', 'Nurse']) !== []) {
        return 'medical';
    }

    return 'patient';
}

/**
 * Enrollment access rules:
 * - If student is no longer enrolled, disable booking access and consultation requests.
 * - Keep historical records intact.
 */
// Enrollment checks removed from RBAC auth path per requirements.
// Access control must depend ONLY on user_roles + role_permissions.


function requirePermission(string $moduleName, string $permissionName): void
{
    // RBAC only: authorization depends ONLY on role_permissions via user_roles.

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $userId = isset($_SESSION['UserID']) ? (int)$_SESSION['UserID'] : 0;
    if ($userId <= 0) {
        header('Location: ../auth/login.php');
        exit;
    }

    $pdo = require __DIR__ . '/../config/db_pdo.php';

    // No additional enrollment/person-type overrides.


    if (!hasPermission($userId, $moduleName, $permissionName)) {
        showModuleUnavailable();
        exit;
    }
}

/**
 * Display a styled "module unavailable" page when access is denied.
 * Shows instead of redirecting to the dashboard.
 */
function showModuleUnavailable(): void
{
    http_response_code(403);
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE | Module Unavailable</title>
    <link rel="stylesheet" href="/NUcare_Health_system/assets/css/app.css">
    <link rel="stylesheet" href="/NUcare_Health_system/assets/css/admin_dashboard_overrides.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body, html {
            margin: 0; padding: 0;
            height: 100%;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f7fa;
        }
        .module-unavailable-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem;
        }
        .module-unavailable-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
            padding: 3rem 4rem;
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        .module-unavailable-card .icon {
            font-size: 4rem;
            margin-bottom: 1.25rem;
            display: block;
        }
        .module-unavailable-card h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a1a2e;
            margin: 0 0 0.75rem 0;
        }
        .module-unavailable-card p {
            font-size: 1rem;
            color: #6b7280;
            margin: 0 0 1.5rem 0;
            line-height: 1.6;
        }
        .module-unavailable-card .back-link {
            display: inline-block;
            padding: 0.6rem 1.5rem;
            background: #8b0000;
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: background 0.2s;
        }
        .module-unavailable-card .back-link:hover {
            background: #6b0000;
        }
    </style>
</head>
<body>
    <div class="module-unavailable-wrapper">
        <div class="module-unavailable-card">
            <span class="icon"><i class="bi bi-tools" style="font-size: 3rem; color: #8b0000;"></i></span>
            <h2>Oops!</h2>
            <p>This module is currently being reworked. Please come back later.</p>
            <a href="/NUcare_Health_system/modules/dashboard/patient_dashboard.php" class="back-link">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
    <?php
}

function rbacRequireModulePermission(string $moduleName, string $permissionName): void
{
    requirePermission($moduleName, $permissionName);
}
