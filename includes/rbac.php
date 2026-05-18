<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db_pdo.php';

function rbacGetUserIdFromSession(): ?int {
    return isset($_SESSION['UserID']) ? (int)$_SESSION['UserID'] : null;
}

function rbacGetSchoolPersonIdFromSession(): ?int {
    return isset($_SESSION['SchoolPersonID']) ? (int)$_SESSION['SchoolPersonID'] : null;
}

function getUserRoles(int $userId): array {
    $pdo = require __DIR__ . '/../config/db_pdo.php';
    $sql = "
        SELECT r.RoleName
        FROM user_roles ur
        INNER JOIN roles r ON r.RoleID = ur.RoleID
        WHERE ur.UserID = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    return array_map(static fn($row) => (string)$row['RoleName'], $stmt->fetchAll());
}

function getUserPermissions(int $userId): array {
    $pdo = require __DIR__ . '/../config/db_pdo.php';
    $sql = "
        SELECT DISTINCT p.PermissionName, m.ModuleName
        FROM user_roles ur
        INNER JOIN role_permissions rp ON rp.RoleID = ur.RoleID
        INNER JOIN permissions p ON p.PermissionID = rp.PermissionID
        INNER JOIN modules m ON m.ModuleID = rp.ModuleID
        WHERE ur.UserID = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function hasPermission(int $userId, string $moduleName, string $permissionName): bool {
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

function rbacLoadSessionPermissions(PDO $pdo, int $userId): void {
    $_SESSION['UserID'] = $userId;
    $_SESSION['Roles'] = getUserRoles($userId);

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
    $_SESSION['AccessibleModules'] = array_keys($accessibleModules);
}

/**
 * Enrollment access rules:
 * - If student is no longer enrolled, disable booking access and consultation requests.
 * - Keep historical records intact.
 */
function rbacStudentHasActiveEnrollment(PDO $pdo, int $schoolPersonId): bool {
    // Prefer new table if it exists
    try {
        $sql = "
            SELECT 1
            FROM student_enrollments
            WHERE SchoolPersonID = ?
              AND EnrollmentStatus = 'Enrolled'
            LIMIT 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$schoolPersonId]);
        return $stmt->fetchColumn() !== false;
    } catch (Throwable $e) {
        // ignore and fallback
    }

    // Fallback to legacy enrolled_students table
    try {
        $sql = "
            SELECT 1
            FROM enrolled_students
            WHERE StudentID = ?
              AND EnrollmentStatus = 'Enrolled'
            LIMIT 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$schoolPersonId]);
        return $stmt->fetchColumn() !== false;
    } catch (Throwable $e) {
        return false;
    }
}

function rbacGetPersonType(PDO $pdo, int $schoolPersonId): ?string {
    $stmt = $pdo->prepare("SELECT PersonType FROM school_people WHERE SchoolPersonID = ? LIMIT 1");
    $stmt->execute([$schoolPersonId]);
    $val = $stmt->fetchColumn();
    return $val !== false ? (string)$val : null;
}

function requirePermission(string $moduleName, string $permissionName): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $userId = isset($_SESSION['UserID']) ? (int)$_SESSION['UserID'] : 0;
    if ($userId <= 0) {
        header('Location: ../auth/login.php');
        exit;
    }

    $pdo = require __DIR__ . '/../config/db_pdo.php';

    // Enrollment override: students who are not enrolled cannot book/consult.
    // This is NOT a role-name hardcode; it’s based on school_people.PersonType.
    if ($permissionName === 'access') {
        $schoolPersonId = isset($_SESSION['SchoolPersonID']) ? (int)$_SESSION['SchoolPersonID'] : 0;
        if ($schoolPersonId > 0) {
            $personType = rbacGetPersonType($pdo, $schoolPersonId);
            if ($personType === 'Student') {
                $active = rbacStudentHasActiveEnrollment($pdo, $schoolPersonId);
                if (
                    !$active
                    && ($moduleName === 'Consultation' || $moduleName === 'Schedule')
                ) {
                    http_response_code(403);
                    echo 'Access denied: student is not currently enrolled for booking/consultation.';
                    exit;
                }
            }
        }
    }

    if (!hasPermission($userId, $moduleName, $permissionName)) {
        http_response_code(403);
        header('Location: ../../modules/dashboard/dashboard.php');
        exit;
    }
}

// Backward-compat wrapper: existing module_guard calls this name.
function rbacRequireModulePermission(string $moduleName, string $permissionName): void {
    requirePermission($moduleName, $permissionName);
}
