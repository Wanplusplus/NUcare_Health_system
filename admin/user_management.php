<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Match global app shell/sidebar sizing by using the same page wrapper as other admin modules.


// Allow both legacy patient_id and RBAC UserID sessions
if (!isset($_SESSION['patient_id']) && !isset($_SESSION['UserID'])) {
    header('Location: ../../auth/login.php');
    exit;
}

require_once __DIR__ . '/../includes/module_guard.php';
require_once __DIR__ . '/../includes/audit.php';

// RBAC: enforce that the user can access this admin module.
requireModule('Admin Panel', 'access');

$pdo = require __DIR__ . '/../config/db_pdo.php';

// Current actor info for audit logs
$actorUserId = isset($_SESSION['UserID']) ? (int)$_SESSION['UserID'] : null;
$actorSchoolPersonId = isset($_SESSION['SchoolPersonID']) ? (int)$_SESSION['SchoolPersonID'] : null;

$errors = [];
$success = null;

// -----------------------------
// Role assignment policy
// -----------------------------
// "Super Admin" must NEVER be assignable/creatable through this interface,
// regardless of who is logged in. This is the canonical list of roles that
// the User Management UI is allowed to offer for assignment.
const ASSIGNABLE_ROLES_SUPER_ADMIN = ['Admin', 'Doctor', 'Dentist', 'Nurse', 'Staff', 'Faculty', 'Student'];

// Admins may manage the same set EXCEPT they cannot assign "Admin" (and never "Super Admin").
const ASSIGNABLE_ROLES_ADMIN = ['Doctor', 'Dentist', 'Nurse', 'Staff', 'Faculty', 'Student'];

// Roles that can NEVER be assigned through User Management, under any circumstance.
const FORBIDDEN_ASSIGNABLE_ROLES = ['Super Admin'];


// Sidebar active state (UI consistency)
$activeSidebarItem = $activeSidebarItem ?? 'user_management';

// -----------------------------
// Helper functions
// -----------------------------
function getIp(): ?string
{
    return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? null);
}

/**
 * Returns the list of role names the given actor is permitted to ASSIGN through
 * the User Management interface. "Super Admin" is never included for anyone.
 */
function assignableRolesForActor(bool $actorIsSuperAdmin): array
{
    return $actorIsSuperAdmin
        ? ASSIGNABLE_ROLES_SUPER_ADMIN
        : ASSIGNABLE_ROLES_ADMIN;
}

function fetchAll(PDO $pdo, string $sql, array $params = []): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetchOne(PDO $pdo, string $sql, array $params = []): ?array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row === false ? null : $row;
}

function sanitizeLike(string $s): string
{
    // Keep minimal sanitation; PDO prepared statements handle injection.
    return trim($s);
}

function rolesForUser(PDO $pdo, int $userId): array
{
    $sql = "
        SELECT r.RoleName
        FROM user_roles ur
        INNER JOIN roles r ON r.RoleID = ur.RoleID
        WHERE ur.UserID = ?
        ORDER BY r.RoleName ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    return array_map(static fn($row) => (string)$row['RoleName'], $stmt->fetchAll());
}

function isSuperAdmin(PDO $pdo, int $userId): bool
{
    $roles = rolesForUser($pdo, $userId);
    return in_array('Super Admin', $roles, true);
}

function ensureMedicalProfessional(PDO $pdo, int $userId, string $profession, ?string $unit = null): void
{
    $unit = $unit ?? 'General';

    // medical_professionals has a UNIQUE(UserID) in current schema.
    // So we must treat this as upsert-by-user (single row per user), not per (UserID, Profession).
    $existing = fetchOne(
        $pdo,
        "SELECT 1 FROM medical_professionals WHERE UserID = ? LIMIT 1",
        [$userId]
    );

    if ($existing) {
        $stmt = $pdo->prepare(
            "UPDATE medical_professionals SET Profession = ?, Unit = ? WHERE UserID = ?"
        );
        $stmt->execute([$profession, $unit, $userId]);
        return;
    }

    // Schema compatibility: some deployments may not have UpdatedAt/CreatedAt columns on this table.
    $stmt = $pdo->prepare(
        "INSERT INTO medical_professionals (UserID, Profession, Unit) VALUES (?, ?, ?)"
    );
    $stmt->execute([$userId, $profession, $unit]);
}

function removeMedicalProfessionalEntry(PDO $pdo, int $userId, string $profession): void
{
    // Historical medical records should be preserved.
    // We will not delete; we only optionally deactivate if a field exists.
    // Fallback: do nothing if no obvious deactivation column.
    try {
        $columns = fetchOne($pdo, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'medical_professionals' AND COLUMN_NAME LIKE 'De%_' LIMIT 1");
        // Above is a weak detection; proceed with safe approach:
        // Prefer a common column name if it exists.
    $candidateCols = ['IsActive', 'Active', 'Status'];

            // Schema compatibility: current dump has no UpdatedAt column.
            // If we detect an *active/status* column, update only that column.

        foreach ($candidateCols as $col) {
            $has = fetchOne(
                $pdo,
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'medical_professionals' AND COLUMN_NAME = ? LIMIT 1",
                [$col]
            );
            if ($has) {
                $stmt = $pdo->prepare("UPDATE medical_professionals SET {$col} = 0, UpdatedAt = NOW() WHERE UserID = ? AND Profession = ?");
                $stmt->execute([$userId, $profession]);
                return;
            }
        }
    } catch (Throwable $e) {
        // ignore
    }

    // If we can't deactivate, keep record as-is.
}

function auditSimple(?int $actorUserId, ?int $actorSchoolPersonId, string $actionType, string $entityType, ?int $entityId, string $details): void
{
    auditLog(
        $actorUserId,
        $actorSchoolPersonId,
        $actionType,
        $entityType,
        $entityId !== null ? (string)$entityId : null,
        $details,
        getIp()
    );
}

// ---------------------------------------------------------------------------
// Determine actor's privileges (Super Admin vs Admin)
// ---------------------------------------------------------------------------
$actorIsSuperAdmin = $actorUserId !== null ? isSuperAdmin($pdo, $actorUserId) : false;

// The roles this actor is permitted to assign through the user management form.
$assignableRoles = assignableRolesForActor($actorIsSuperAdmin);

// Quick lookup set for forbidden assignments
$forbiddenRolesSet = array_flip(FORBIDDEN_ASSIGNABLE_ROLES);

// -----------------------------
// Handle role promotion/de-promotion and account activation
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    $actionType = (string)$_POST['action_type'];

    // Basic CSRF note: project does not show CSRF tokens elsewhere; keeping consistent.
    // Ensure we only act on integers.
    $targetUserId = isset($_POST['target_user_id']) && is_numeric($_POST['target_user_id']) ? (int)$_POST['target_user_id'] : 0;

    if ($targetUserId <= 0) {
        $errors[] = 'Invalid target user.';
    } elseif (isSuperAdmin($pdo, $targetUserId)) {
        // Prevent any modifications to Super Admin accounts
        $errors[] = 'Cannot modify Super Admin accounts. This account is protected.';
    } elseif (!$actorIsSuperAdmin && $targetUserId === $actorUserId) {
        // ------------------------------------------------------------------
        // ADMIN SELF-PROTECTION: An Admin cannot edit their own roles.
        // They cannot remove Admin from themselves.
        // ------------------------------------------------------------------
        if ($actionType === 'update_roles') {
            $errors[] = 'You cannot modify your own Admin role. The Admin role is locked on your account.';
        } elseif ($actionType === 'set_active') {
            $errors[] = 'You cannot deactivate/activate your own account.';
        } else {
            $errors[] = 'You cannot modify your own account.';
        }
    } elseif (!$actorIsSuperAdmin && $targetUserId !== $actorUserId) {
        // ------------------------------------------------------------------
        // ADMIN EDITING OTHERS: must check that no forbidden roles are being
        // assigned (Super Admin, Admin - since Admin cannot assign Admin).
        // ------------------------------------------------------------------
        if ($actionType === 'update_roles') {
            $rolesToAdd = isset($_POST['roles_to_add']) && is_array($_POST['roles_to_add']) ? array_values($_POST['roles_to_add']) : [];

            foreach ($rolesToAdd as $role) {
                if (isset($forbiddenRolesSet[$role])) {
                    $errors[] = 'Cannot assign Super Admin. This role is not available through User Management.';
                    break;
                }
            }

            foreach ($rolesToAdd as $role) {
                if ($role === 'Admin') {
                    $errors[] = 'You do not have permission to assign the Admin role.';
                    break;
                }
            }
        }
    }

    // ---- PROCESSING (applies to both Super Admin and Admin, if no errors) ----
    if (empty($errors)) {
        if ($actionType === 'update_roles') {
            // Fetch roles for mapping.
            $roleToProfessionMap = [
                'Doctor' => 'Doctor',
                'Dentist' => 'Dentist',
                'Nurse' => 'Nurse',
            ];

            $rolesToAdd = isset($_POST['roles_to_add']) && is_array($_POST['roles_to_add']) ? array_values($_POST['roles_to_add']) : [];
            $rolesToRemove = isset($_POST['roles_to_remove']) && is_array($_POST['roles_to_remove']) ? array_values($_POST['roles_to_remove']) : [];

            // Normalize to known role names only (prevent injecting unknown strings).
            $knownRoles = fetchAll($pdo, "SELECT RoleName FROM roles");
            $knownRoleNames = array_map(static fn($r) => (string)$r['RoleName'], $knownRoles);
            $knownSet = array_flip($knownRoleNames);

            $rolesToAdd = array_values(array_filter($rolesToAdd, fn($r) => isset($knownSet[(string)$r])));
            $rolesToRemove = array_values(array_filter($rolesToRemove, fn($r) => isset($knownSet[(string)$r])));

            // ------------------------------------------------------------------
            // SECURITY ENFORCEMENT: Block any attempt to assign/promote to a
            // forbidden role. This catches Super Admin assignments even if the
            // UI is bypassed (e.g. via curl/Postman).
            // ------------------------------------------------------------------
            foreach ($rolesToAdd as $role) {
                if (isset($forbiddenRolesSet[$role])) {
                    $errors[] = 'Forbidden: Cannot assign the role "' . htmlspecialchars($role) . '" through User Management.';
                }
            }
            foreach ($rolesToRemove as $role) {
                if (isset($forbiddenRolesSet[$role])) {
                    $errors[] = 'Forbidden: Cannot remove the role "' . htmlspecialchars($role) . '" through User Management.';
                }
            }

            // If actor is not a Super Admin, also forbid assigning "Admin"
            if (!$actorIsSuperAdmin) {
                $assignableSet = array_flip($assignableRoles);
                foreach ($rolesToAdd as $role) {
                    if (!isset($assignableSet[$role])) {
                        $errors[] = 'You do not have permission to assign the role "' . htmlspecialchars($role) . '".';
                    }
                }
            }

            if (empty($errors)) {
                $currentRoles = rolesForUser($pdo, $targetUserId);
                $desiredRoles = array_values(array_unique(array_merge(
                    array_values(array_diff($currentRoles, $rolesToRemove)),
                    $rolesToAdd
                )));

                // Enforce single role: pick highest priority among desired roles.
                // Priority order can be adjusted later.
                $priority = ['Doctor', 'Dentist', 'Nurse'];
                $chosenRole = null;

                foreach ($priority as $pRole) {
                    if (in_array($pRole, $desiredRoles, true)) {
                        $chosenRole = $pRole;
                        break;
                    }
                }

                // If none of the prioritized roles are present, pick the first desired role (if any)
                if ($chosenRole === null && !empty($desiredRoles)) {
                    $chosenRole = $desiredRoles[0];
                }

                $pdo->beginTransaction();
                try {
                    // Remove ALL existing roles for user first to guarantee single-role invariant.
                    $existingRoleNames = $currentRoles;
                    foreach ($existingRoleNames as $existingRoleName) {
                        $roleRow = fetchOne($pdo, "SELECT RoleID FROM roles WHERE RoleName = ? LIMIT 1", [$existingRoleName]);
                        if (!$roleRow) continue;
                        $roleId = (int)$roleRow['RoleID'];

                        $stmtDel = $pdo->prepare("DELETE FROM user_roles WHERE UserID = ? AND RoleID = ?");
                        $stmtDel->execute([$targetUserId, $roleId]);

                        auditSimple(
                            $actorUserId,
                            $actorSchoolPersonId,
                            'role_removal',
                            'users',
                            $targetUserId,
                            "Removed role: {$existingRoleName}"
                        );

                        if (isset($roleToProfessionMap[$existingRoleName])) {
                            removeMedicalProfessionalEntry($pdo, $targetUserId, $roleToProfessionMap[$existingRoleName]);
                        }
                    }

                    // Insert only chosen role.
                    if ($chosenRole !== null) {

                        // Debug: trace promotion sync timing
                        if (function_exists('rbacDebug')) {
                            rbacDebug('promotion_before_role_assignment', [

                                'actorUserId' => $actorUserId,
                                'targetUserId' => $targetUserId,
                                'desiredRolesToAdd' => $rolesToAdd,
                                'desiredRolesToRemove' => $rolesToRemove,
                                'chosenRole' => $chosenRole,
                            ]);
                        }

                        $roleRow = fetchOne($pdo, "SELECT RoleID FROM roles WHERE RoleName = ? LIMIT 1", [$chosenRole]);
                        if ($roleRow) {
                            $roleId = (int)$roleRow['RoleID'];

                            // user_roles schema compatibility: some dumps may not have CreatedAt/UpdatedAt.
                            $hasUserRolesTimestamps = fetchOne(
                                $pdo,
                                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_roles' AND COLUMN_NAME IN ('CreatedAt','UpdatedAt') LIMIT 1"
                            );

                            if ($hasUserRolesTimestamps) {
                                $stmt = $pdo->prepare(
                                    "INSERT INTO user_roles (UserID, RoleID, CreatedAt, UpdatedAt) VALUES (?, ?, NOW(), NOW())"
                                );
                                $stmt->execute([$targetUserId, $roleId]);
                            } else {
                                $stmt = $pdo->prepare("INSERT INTO user_roles (UserID, RoleID) VALUES (?, ?)");
                                $stmt->execute([$targetUserId, $roleId]);
                            }

                            auditSimple(
                                $actorUserId,
                                $actorSchoolPersonId,
                                'role_assignment',
                                'users',
                                $targetUserId,
                                "Assigned role: {$chosenRole}"
                            );

                            if (isset($roleToProfessionMap[$chosenRole])) {
                                ensureMedicalProfessional($pdo, $targetUserId, $roleToProfessionMap[$chosenRole]);
                            }

                            // Ensure RBAC permissions exist for promoted medical roles.
                            // This prevents promoted users from being blocked by module_guard.php.
                            if (in_array($chosenRole, ['Doctor', 'Dentist', 'Nurse'], true)) {
                                $requiredModules = ['Consultation', 'Records', 'Schedule', 'Medicine'];
                                $requiredPermissions = ['access', 'View', 'Create', 'Edit', 'Approve'];

                                // Remove existing entries for this role+module+permission set, then insert fresh.
                                // This avoids duplicate-key failures when the role is changed multiple times.

                                $deleteSql = "
                                    DELETE rp
                                    FROM role_permissions rp
                                    INNER JOIN roles rr ON rr.RoleID = rp.RoleID
                                    INNER JOIN modules mm ON mm.ModuleID = rp.ModuleID
                                    INNER JOIN permissions p ON p.PermissionID = rp.PermissionID
                                    WHERE rr.RoleName = ?
                                      AND mm.ModuleName IN ('" . implode("','", $requiredModules) . "')
                                      AND p.PermissionName IN ('" . implode("','", $requiredPermissions) . "')
                                ";

                                $delStmt = $pdo->prepare($deleteSql);
                                $delStmt->execute([$chosenRole]);

                                $insertSql = "
                                    INSERT INTO role_permissions (RoleID, ModuleID, PermissionID)
                                    SELECT rr.RoleID, mm.ModuleID, p.PermissionID
                                    FROM roles rr
                                    CROSS JOIN modules mm
                                    CROSS JOIN permissions p
                                    WHERE rr.RoleName = ?
                                      AND mm.ModuleName IN ('" . implode("','", $requiredModules) . "')
                                      AND p.PermissionName IN ('" . implode("','", $requiredPermissions) . "')
                                ";

                                $stmt = $pdo->prepare($insertSql);
                                $stmt->execute([$chosenRole]);
                            }
                        }
                    }


                    $pdo->commit();
                    $success = 'Role updated successfully (single-role enforced).';
                } catch (Throwable $e) {
                    $pdo->rollBack();
                    $errors[] = 'Failed to update roles.';
                    // Expose the real exception during development so we can fix the underlying issue.
                    $errors[] = 'Error detail: ' . $e->getMessage();
                }
            }
        } elseif ($actionType === 'set_active') {
            // Expected: is_active (0/1)
            $isActive = isset($_POST['is_active']) && is_numeric($_POST['is_active']) ? (int)$_POST['is_active'] : 0;
            $isActive = $isActive === 1 ? 1 : 0;

            $stmt = $pdo->prepare("UPDATE users SET IsActive = ? WHERE UserID = ?");
            $stmt->execute([$isActive, $targetUserId]);

            auditSimple(
                $actorUserId,
                $actorSchoolPersonId,
                $isActive === 1 ? 'account_activation' : 'account_deactivation',
                'users',
                $targetUserId,
                $isActive === 1 ? 'Account activated' : 'Account deactivated'
            );

            $success = 'Account status updated.';
        } elseif ($actionType === 'reset_password') {
            // Implementation depends on how passwords are stored.
            // We'll just scaffold a secure reset trigger by forcing a random token in your existing reset system.
            // Since the existing project has auth/reset_password.php etc., we redirect to that flow.
            // For now, we mark success and audit.
            auditSimple(
                $actorUserId,
                $actorSchoolPersonId,
                'password_reset',
                'users',
                $targetUserId,
                'Password reset requested (implementation delegated to auth reset flow)'
            );
            $success = 'Password reset requested.';
        }
    }
}

// -----------------------------
// Read filters and render user list
// -----------------------------
$search = sanitizeLike((string)($_GET['search'] ?? ''));
$roleFilter = (string)($_GET['role'] ?? '');
$statusFilter = (string)($_GET['status'] ?? '');

// Pagination (simple)
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$sqlWhere = [];
$params = [];

if ($search !== '') {
    $sqlWhere[] = "(sp.SchoolID LIKE ? OR sp.FirstName LIKE ? OR sp.LastName LIKE ? OR sp.Email LIKE ? OR CONCAT(sp.FirstName, ' ', COALESCE(sp.MiddleName, ''), ' ', sp.LastName) LIKE ?)";
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}



// Account status filter: uses existing tables as described
if ($statusFilter !== '') {
    switch ($statusFilter) {
        case 'Active':
            $sqlWhere[] = "u.IsActive = 1";
            break;
        case 'Disabled':
            $sqlWhere[] = "u.IsActive = 0";
            break;
        case 'Enrolled':
            $sqlWhere[] = "(se.EnrollmentStatus = 'Enrolled')";
            break;
        case 'Dropped':
            $sqlWhere[] = "(se.EnrollmentStatus = 'Dropped')";
            break;
        case 'Graduated':
            $sqlWhere[] = "(se.EnrollmentStatus = 'Graduated')";
            break;
        case 'Employed':
            // employment status not supported in current schema
            break;
        case 'Resigned':
            // employment status not supported in current schema
            break;

        case 'Inactive':
            $sqlWhere[] = "u.IsActive = 0";
            break;
        default:
            // no-op
            break;
    }
}

if ($roleFilter !== '') {
    $sqlWhere[] = "EXISTS (
        SELECT 1
        FROM user_roles ur
        INNER JOIN roles r ON r.RoleID = ur.RoleID
        WHERE ur.UserID = u.UserID
          AND r.RoleName = ?
    )";
    $params[] = $roleFilter;
}

$sqlWhereSql = $sqlWhere ? ('WHERE ' . implode(' AND ', $sqlWhere)) : '';

$totalCount = 0;
$rows = [];

try {
    $countSql = "
        SELECT COUNT(DISTINCT u.UserID) AS total
        FROM users u
        INNER JOIN school_people sp ON sp.SchoolPersonID = u.SchoolPersonID
        LEFT JOIN student_enrollments se ON se.SchoolPersonID = u.SchoolPersonID
        {$sqlWhereSql}

    ";
    $countRow = fetchOne($pdo, $countSql, $params);
    $totalCount = $countRow ? (int)$countRow['total'] : 0;

    $listSql = "
        SELECT DISTINCT
            u.UserID,
            sp.SchoolID,
            sp.Email,
            sp.FirstName,
            sp.MiddleName,
            sp.LastName,
            u.IsActive

        FROM users u
        INNER JOIN school_people sp ON sp.SchoolPersonID = u.SchoolPersonID
        LEFT JOIN student_enrollments se ON se.SchoolPersonID = sp.SchoolPersonID
        {$sqlWhereSql}
        ORDER BY u.UserID DESC

        LIMIT {$perPage} OFFSET {$offset}
    ";

    $rows = fetchAll($pdo, $listSql, $params);
} catch (Throwable $e) {
    $errors[] = 'Failed to load users: ' . $e->getMessage();
}


$allRoles = fetchAll($pdo, "SELECT RoleName FROM roles ORDER BY RoleName ASC");
$roleNames = array_map(static fn($r) => (string)$r['RoleName'], $allRoles);


$statusOptions = ['Active', 'Disabled', 'Enrolled', 'Dropped', 'Graduated', 'Employed', 'Resigned', 'Inactive'];

// -----------------------------
// Render
// -----------------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE | User Management</title>
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="../assets/css/admin_dashboard_overrides.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
<div class="app-shell">
    <?php
    $sidebarPath = __DIR__ . '/../includes/sidebar_admin.php';
    if (file_exists($sidebarPath)) {
        require_once $sidebarPath;
    }
    ?>

    <main class="main-content">
        <header class="page-header">
            <div>
                <p class="breadcrumb">Home / User Management</p>
                <h2>User Management</h2>
                <p class="page-description">Promote Faculty/Staff users into medical personnel using RBAC roles.</p>
            </div>
            <div class="header-actions">
                <a href="../auth/logout.php" class="header-button outline">Logout</a>
            </div>
        </header>

        <?php if ($errors): ?>
            <div class="alert alert-danger" role="alert" style="margin-top: 12px;">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div id="successAlert" class="alert alert-success" role="alert" style="margin-top: 12px;"><?= htmlspecialchars($success) ?></div>
            <script>
                // Auto-dismiss success message after 3 seconds with fade-out
                (function() {
                    const alert = document.getElementById('successAlert');
                    if (alert) {
                        setTimeout(() => {
                            alert.style.transition = 'opacity 0.3s ease-out';
                            alert.style.opacity = '0';
                            setTimeout(() => {
                                alert.style.display = 'none';
                            }, 300);
                        }, 3000);
                    }
                })();
            </script>
        <?php endif; ?>

        <div class="admin-filterbar user-filterbar-enterprise" style="margin-top: 12px;">

            <div class="admin-filter">
                <label>Search</label>
                <input type="text" id="userSearchInput" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="School ID, First name, Last name, Email">
            </div>

            <div class="admin-filter" style="min-width: 260px;">
                <label>Role</label>
                <select id="userRoleSelect" name="role">
                    <option value="">All Roles</option>
                    <?php foreach ($roleNames as $r): ?>
                        <option value="<?= htmlspecialchars($r) ?>" <?= $roleFilter === $r ? 'selected' : '' ?>><?= htmlspecialchars($r) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="admin-filter" style="min-width: 220px;">
                <label>Status</label>
                <select id="userStatusSelect" name="status">
                    <option value="">Any</option>
                    <?php foreach ($statusOptions as $st): ?>
                        <option value="<?= htmlspecialchars($st) ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= htmlspecialchars($st) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display:flex; gap:12px; align-items:center;">
                <a href="user_management.php" class="btn admin-btn-ghost">Reset</a>
            </div>
        </div>

        <section class="panel-card mt-3">
            <div class="panel-card-header d-flex align-items-center justify-content-between">
                <h3>User List</h3>
                <div class="text-muted">Total: <?= (int)$totalCount ?></div>
            </div>
            <div class="panel-card-body table-responsive">
                <table class="table table-striped table-hover align-middle admin-table">
                    <thead>

                    <tr>
                        <th>Full Name</th>
                        <th>School ID</th>
                        <th>Email</th>

                        <th>Current Roles</th>
                        <th>Account Status</th>
                        <th style="min-width: 280px;">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (!$rows): ?>
                        <tr><td colspan="7" class="text-center text-muted">No users found.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($rows as $u):
                        $uid = (int)$u['UserID'];
                        $roles = rolesForUser($pdo, $uid);
                        $rolesText = $roles ? implode(', ', $roles) : '—';
                        $isActive = (int)$u['IsActive'] === 1;
                        $isSuperAdmin = isSuperAdmin($pdo, $uid);
                        // Hide Edit Roles button when:
                        // 1. Target is a Super Admin (no one can edit them), OR
                        // 2. Actor is an Admin AND target is themselves (Admin cannot edit own Admin role).
                        $hideEditRoles = $isSuperAdmin || (!$actorIsSuperAdmin && $actorUserId === $uid);
                    ?>
                        <tr>
                            <td><?= htmlspecialchars((string)($u['FirstName'] ?? '') . (isset($u['MiddleName']) && $u['MiddleName'] !== null && $u['MiddleName'] !== '' ? ' ' . (string)$u['MiddleName'] : '') . ' ' . (string)($u['LastName'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)$u['SchoolID']) ?></td>
                            <td><?= htmlspecialchars((string)$u['Email']) ?></td>
                            <td><?= htmlspecialchars($rolesText) ?></td>

                            <td>
                                <?php if ($isActive): ?>
                                    <span class="admin-badge admin-badge-success">Active</span>

                                <?php else: ?>
                                    <span class="admin-badge admin-badge-muted">Disabled</span>

                                <?php endif; ?>
                                <?php if ($isSuperAdmin): ?>
                                    <span class="admin-badge" style="background: #dbeafe; color: #1e40af; border-color: #bfdbfe; margin-left: 8px;">Protected Account</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php if (!$hideEditRoles): ?>
                                        <button class="btn-action btn-action-edit" type="button" onclick="openRolesModal(<?= $uid ?>, <?= htmlspecialchars(json_encode($roles)) ?>)">Edit Roles</button>
                                    <?php endif; ?>

                                    <?php if (!$isSuperAdmin): ?>
                                        <form method="post" style="display:inline-block;" onsubmit="return confirm('<?= $isActive ? 'Deactivate' : 'Activate' ?> this account?');">
                                            <input type="hidden" name="action_type" value="set_active">
                                            <input type="hidden" name="target_user_id" value="<?= $uid ?>">
                                            <input type="hidden" name="is_active" value="<?= $isActive ? 0 : 1 ?>">
                                            <button class="btn-action <?= $isActive ? 'btn-action-deactivate' : 'btn-action-activate' ?>" type="submit"><?= $isActive ? 'Deactivate' : 'Activate' ?></button>
                                        </form>
                                    <?php endif; ?>
                                    <a class="btn-action btn-action-audit" href="audit_logs.php?school_id=<?= htmlspecialchars((string)$u['SchoolID']) ?>">Audit Logs</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <?php
                // Pagination links
                $totalPages = $perPage > 0 ? (int)ceil($totalCount / $perPage) : 1;
                if ($totalPages > 1):
                ?>
                    <nav aria-label="Pagination">
                        <ul class="pagination">
                            <?php for ($p = 1; $p <= $totalPages; $p++):
                                $activeP = $p === $page;
                                $q = $_GET;
                                $q['page'] = $p;
                                $qs = http_build_query($q);
                            ?>
                                <li class="page-item <?= $activeP ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= htmlspecialchars($qs) ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            </div>
        </section>

        <!-- Roles Modal -->
        <div class="modal fade" id="rolesModal" tabindex="-1" aria-labelledby="rolesModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="rolesModalLabel">Edit Roles</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="rolesForm" method="post">
                            <input type="hidden" name="action_type" value="update_roles">
                            <input type="hidden" name="target_user_id" id="target_user_id" value="">

                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="alert alert-info mb-3">
                                        Assigning <strong>Doctor</strong>, <strong>Dentist</strong>, or <strong>Nurse</strong> will auto-create
                                        a record in <code>medical_professionals</code> if missing.
                                    </div>
                                </div>

                                <div class="text-muted" style="font-size: 0.9rem;">
                                    Choose exactly <strong>one</strong> role.
                                </div>

                                <?php foreach ($assignableRoles as $roleName): ?>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input role-checkbox"
                                                   type="radio"
                                                   name="single_role_selection"
                                                   value="<?= htmlspecialchars($roleName) ?>"
                                                   id="role_add_<?= htmlspecialchars($roleName) ?>">
                                            <label class="form-check-label" for="role_add_<?= htmlspecialchars($roleName) ?>"><?= htmlspecialchars($roleName) ?></label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Hidden fields computed by JS -->
                            <div class="d-none">
                                <input type="hidden" name="roles_to_add" id="roles_to_add" value="">
                                <input type="hidden" name="roles_to_remove" id="roles_to_remove" value="">
                            </div>



                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-accent" onclick="submitRoles()">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    let rolesModal;
    let currentRoles = [];

    function openRolesModal(userId, roles) {
        currentRoles = Array.isArray(roles) ? roles : [];
        document.getElementById('target_user_id').value = userId;

        // Set checkboxes according to current roles
        document.querySelectorAll('.role-checkbox').forEach(cb => {
            cb.checked = currentRoles.includes(cb.value);
        });

        if (!rolesModal) {
            rolesModal = new bootstrap.Modal(document.getElementById('rolesModal'));
        }
        rolesModal.show();
    }

    function submitRoles() {
        const checkedRoles = [];
        // Enforce single role selection in the UI as well.
        const checkedBoxes = Array.from(document.querySelectorAll('.role-checkbox')).filter(cb => cb.checked);
        if (checkedBoxes.length > 0) {
            // Keep only the first checked value (consistent behavior)
            checkedBoxes.slice(1).forEach(cb => cb.checked = false);
            checkedRoles.push(checkedBoxes[0].value);
        }
        // Remaining logic expects roles_to_add/roles_to_remove.
        // (No-op if none checked)
        if (checkedRoles.length === 0) {
            document.querySelectorAll('.role-checkbox').forEach(cb => {
                if (cb.checked) checkedRoles.push(cb.value);
            });
        }

        const toAdd = checkedRoles.filter(r => !currentRoles.includes(r));
        const toRemove = currentRoles.filter(r => !checkedRoles.includes(r));

        document.getElementById('roles_to_add').value = JSON.stringify(toAdd);
        document.getElementById('roles_to_remove').value = JSON.stringify(toRemove);

        // Build proper POST arrays expected by PHP
        // PHP expects roles_to_add/roles_to_remove in array form.
        const form = document.getElementById('rolesForm');

        // Remove any previous dynamically added hidden fields
        form.querySelectorAll('input.dynamic-role').forEach(n => n.remove());

        toAdd.forEach(r => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'roles_to_add[]';
            inp.value = r;
            inp.className = 'dynamic-role';
            form.appendChild(inp);
        });

        toRemove.forEach(r => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'roles_to_remove[]';
            inp.value = r;
            inp.className = 'dynamic-role';
            form.appendChild(inp);
        });

        form.submit();
    }

    // Dynamic filtering for search and dropdowns
    let filterTimeout;

    function applyFilters() {
        const search = document.getElementById('userSearchInput').value.trim();
        const role = document.getElementById('userRoleSelect').value;
        const status = document.getElementById('userStatusSelect').value;

        // Build URL with filters
        const url = new URL(window.location.href);
        url.searchParams.set('page', '1'); // Reset to page 1 on filter change

        if (search !== '') {
            url.searchParams.set('search', search);
        } else {
            url.searchParams.delete('search');
        }

        if (role !== '') {
            url.searchParams.set('role', role);
        } else {
            url.searchParams.delete('role');
        }

        if (status !== '') {
            url.searchParams.set('status', status);
        } else {
            url.searchParams.delete('status');
        }

        window.location.href = url.toString();
    }

    function debounceFilter() {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(applyFilters, 300);
    }

    // Wire up filter inputs to auto-apply on change
    document.getElementById('userSearchInput')?.addEventListener('input', debounceFilter);
    document.getElementById('userRoleSelect')?.addEventListener('change', applyFilters);
    document.getElementById('userStatusSelect')?.addEventListener('change', applyFilters);
</script>
</body>
</html>