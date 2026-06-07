<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
 session_start();
}

if (!isset($_SESSION['patient_id']) && !isset($_SESSION['UserID'])) {
 header('Location: /NUcare_Health_system/frontend/auth/login.php');
 exit;
}

require_once __DIR__ . '/../../backend/includes/module_guard.php';
require_once __DIR__ . '/../../backend/includes/audit.php';

requireModule('RBAC Management', 'access');

// ---
// RBAC Management is a Super Admin-only feature.
// Admin accounts must never access this page.
// ---
$hasSuperAdminRole = false;
if (isset($_SESSION['Roles']) && is_array($_SESSION['Roles'])) {
 $hasSuperAdminRole = in_array('Super Admin', $_SESSION['Roles'], true);
}
if (!$hasSuperAdminRole) {
 $_SESSION['error_message'] = 'Access denied. Only Super Administrators can manage RBAC permissions.';
 header('Location: /NUcare_Health_system/frontend/admin/dashboard/admin_dashboard.php');
 exit;
}

$pdo = require __DIR__ . '/../../database/config/db_pdo.php';

// Current actor info for audit logs
$actorUserId = isset($_SESSION['UserID']) ? (int)$_SESSION['UserID'] : null;
$actorSchoolPersonId = isset($_SESSION['SchoolPersonID']) ? (int)$_SESSION['SchoolPersonID'] : null;

$activeSidebarItem = 'rbac_management';
$errors = [];
$success = null;

// =====================================================
// Helper functions
// =====================================================

function getIp(): ?string
{
 return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? null);
}

function fetchAll(PDO $pdo, string $sql, array $params = []): array
{
 $stmt = $pdo->prepare($sql);
 $stmt->execute($params);
 return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchOne(PDO $pdo, string $sql, array $params = []): ?array
{
 $stmt = $pdo->prepare($sql);
 $stmt->execute($params);
 $row = $stmt->fetch(PDO::FETCH_ASSOC);
 return $row ?: null;
}

function isSuperAdmin(PDO $pdo, int $roleId): bool
{
 $role = fetchOne($pdo, "SELECT RoleName FROM roles WHERE RoleID = ? LIMIT 1", [$roleId]);
 return $role && $role['RoleName'] === 'Super Admin';
}

// =====================================================
// Fetch all roles with user and permission counts
// =====================================================

$allRoles = [];
try {
 $rolesSql = "
 SELECT r.RoleID, r.RoleName, r.Description
 FROM roles r
 ORDER BY 
 CASE WHEN r.RoleName = 'Super Admin' THEN 0 ELSE 1 END ASC,
 r.RoleName ASC
 ";
 
 $roleRows = fetchAll($pdo, $rolesSql);
 
 foreach ($roleRows as $role) {
 $roleId = (int)$role['RoleID'];
 
 // Count users assigned to this role
 $userCountSql = "
 SELECT COUNT(DISTINCT ur.UserID) as user_count
 FROM user_roles ur
 WHERE ur.RoleID = ?
 ";
 $userCountRow = fetchOne($pdo, $userCountSql, [$roleId]);
 $userCount = $userCountRow ? (int)$userCountRow['user_count'] : 0;
 
 // Count permissions assigned to this role
 $permCountSql = "
 SELECT COUNT(*) as perm_count
 FROM role_permissions
 WHERE RoleID = ?
 ";
 $permCountRow = fetchOne($pdo, $permCountSql, [$roleId]);
 $permCount = $permCountRow ? (int)$permCountRow['perm_count'] : 0;
 
 $allRoles[] = [
 'RoleID' => $roleId,
 'RoleName' => $role['RoleName'],
 'Description' => $role['Description'] ?? '',
 'UserCount' => $userCount,
 'PermissionCount' => $permCount,
 ];
 }
} catch (Throwable $e) {
 $errors[] = 'Failed to load roles: ' . $e->getMessage();
}

// =====================================================
// Fetch all modules and their permissions
// =====================================================

$allModules = [];
$allPermissions = [];
try {
 $modulesSql = "
 SELECT ModuleID, ModuleName, Description
 FROM modules
 ORDER BY ModuleName ASC
 ";
 $allModules = fetchAll($pdo, $modulesSql);
 
 $permsSql = "
 SELECT PermissionID, PermissionName, Description
 FROM permissions
 ORDER BY PermissionName ASC
 ";
 $allPermissions = fetchAll($pdo, $permsSql);
} catch (Throwable $e) {
 $errors[] = 'Failed to load modules/permissions: ' . $e->getMessage();
}

// =====================================================
// Render
// =====================================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>NUCARE | RBAC Management</title>
 <link rel="stylesheet" href="/NUcare_Health_system/assets/css/app.css">
 <link rel="stylesheet" href="/NUcare_Health_system/assets/css/admin_dashboard_overrides.css">
 <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
<div class="app-shell">
 <?php
 $sidebarPath = __DIR__ . '/../../backend/includes/sidebar_admin.php';
 if (file_exists($sidebarPath)) {
 require_once $sidebarPath;
 }
 ?>

 <main class="main-content">
 <header class="page-header">
 <div>
 <p class="breadcrumb">Home / RBAC Management</p>
 <h2>RBAC Management</h2>
 <p class="page-description">Manage role-based access control, module permissions, and system access.</p>
 </div>
 <div class="header-actions">
 <a href="/NUcare_Health_system/frontend/auth/logout.php" class="header-button outline">Logout</a>
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

 <!-- Role Cards Grid -->
 <section style="margin-top: 20px;">
 <div class="rbac-cards-grid">
 <?php if (!$allRoles): ?>
 <div style="grid-column: 1/-1; padding: 40px; text-align: center; color: var(--admin-muted);">
 <p>No roles found in system.</p>
 </div>
 <?php endif; ?>
 
 <?php foreach ($allRoles as $role):
 $roleId = (int)$role['RoleID'];
 $isSuperAdminRole = isSuperAdmin($pdo, $roleId);
 ?>
 <div class="rbac-role-card">
 <div class="rbac-card-header">
 <?php if ($isSuperAdminRole): ?>
 <div class="rbac-card-title">
 <span style="font-size: 20px; margin-right: 8px;"></span>
 <span><?= htmlspecialchars($role['RoleName']) ?></span>
 </div>
 <?php else: ?>
 <h3 class="rbac-card-title"><?= htmlspecialchars($role['RoleName']) ?></h3>
 <?php endif; ?>
 </div>

 <div class="rbac-card-body">
 <?php if (!empty($role['Description'])): ?>
 <p class="rbac-card-description"><?= htmlspecialchars($role['Description']) ?></p>
 <?php else: ?>
 <p class="rbac-card-description" style="color: var(--admin-muted);">No description available.</p>
 <?php endif; ?>

 <div class="rbac-card-stats">
 <div class="rbac-stat">
 <span class="rbac-stat-label">Users</span>
 <span class="rbac-stat-value"><?= (int)$role['UserCount'] ?></span>
 </div>
 <div class="rbac-stat">
 <span class="rbac-stat-label">Permissions</span>
 <span class="rbac-stat-value"><?= (int)$role['PermissionCount'] ?></span>
 </div>
 </div>

 <?php if ($isSuperAdminRole): ?>
 <div class="rbac-protected-badge">
 Protected Role
 </div>
 <p style="font-size: 13px; color: var(--admin-muted); text-align: center; margin-top: 12px;">Full system access</p>
 <?php else: ?>
 <button class="btn-action btn-action-edit" onclick="openManageAccessModal(<?= $roleId ?>, '<?= htmlspecialchars($role['RoleName']) ?>')">
 Manage Access
 </button>
 <?php endif; ?>
 </div>
 </div>
 <?php endforeach; ?>
 </div>
 </section>
 </main>
</div>

<!-- Manage Access Modal -->
<div id="manageAccessModal" class="rbac-modal" style="display: none;">
 <div class="rbac-modal-backdrop" onclick="closeManageAccessModal()"></div>
 <div class="rbac-modal-content">
 <div class="rbac-modal-header">
 <h2 id="modalTitle" class="rbac-modal-title">Manage Access</h2>
 <button class="rbac-modal-close" onclick="closeManageAccessModal()"></button>
 </div>

 <div class="rbac-modal-body">
 <div id="modalModulesContainer">
 <!-- Modules and permissions will be loaded here by JavaScript -->
 </div>
 </div>

 <div class="rbac-modal-footer">
 <button class="btn admin-btn-ghost" onclick="closeManageAccessModal()">Cancel</button>
 <button id="savePermissionsBtn" class="btn admin-btn-primary" onclick="saveRolePermissions()">Save Changes</button>
 </div>
 </div>
</div>

<script>
// Global state
let currentRoleId = null;
let currentRoleName = null;
let selectedPermissions = {};

// Open Manage Access Modal
async function openManageAccessModal(roleId, roleName) {
 currentRoleId = roleId;
 currentRoleName = roleName;
 selectedPermissions = {};

 document.getElementById('modalTitle').textContent = `Manage Access - ${roleName}`;

 // Fetch current permissions for this role
 try {
 const response = await fetch('/NUcare_Health_system/backend/ajax/rbac_get_permissions.ajax.php', {
 method: 'POST',
 headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
 body: `role_id=${roleId}`,
 });
 
 const data = await response.json();
 
 if (!data.ok) {
 alert('Error loading permissions: ' + data.message);
 return;
 }

 // modules and allPermissions are already filtered by the backend
 // to show only relevant items for this role.
 const allModules = data.modules || [];
 const allPerms = data.allPermissions || [];
 const currentPermissions = data.permissions || {};

 let html = '';

 for (const module of allModules) {
 const moduleId = module.ModuleID;
 const moduleName = module.ModuleName;

 html += `<div class="rbac-module-section">
 <h4 class="rbac-module-title">${htmlEscape(moduleName)}</h4>
 <div class="rbac-permissions-grid">`;

 for (const perm of allPerms) {
 const permId = perm.PermissionID;
 const permName = perm.PermissionName;
 const key = `${moduleId}_${permId}`;
 const isChecked = currentPermissions[key] || false;

 if (isChecked) {
 selectedPermissions[key] = true;
 }

 html += `
 <label class="rbac-permission-checkbox">
 <input type="checkbox" name="permission" value="${key}" ${isChecked ? 'checked' : ''} 
 onchange="togglePermission('${key}', this.checked)">
 <span>${htmlEscape(permName)}</span>
 </label>`;
 }

 html += `</div></div>`;
 }

 document.getElementById('modalModulesContainer').innerHTML = html;
 document.getElementById('manageAccessModal').style.display = 'flex';
 } catch (error) {
 console.error('Error:', error);
 alert('Failed to load permissions');
 }
}

function togglePermission(key, checked) {
 if (checked) {
 selectedPermissions[key] = true;
 } else {
 delete selectedPermissions[key];
 }
}

function closeManageAccessModal() {
 document.getElementById('manageAccessModal').style.display = 'none';
 currentRoleId = null;
 selectedPermissions = {};
}

async function saveRolePermissions() {
 if (!currentRoleId) return;

 const permissionKeys = Object.keys(selectedPermissions);
 const permissions = permissionKeys.map(key => {
 const [moduleId, permissionId] = key.split('_');
 return { module_id: parseInt(moduleId), permission_id: parseInt(permissionId) };
 });

 try {
 document.getElementById('savePermissionsBtn').disabled = true;
 document.getElementById('savePermissionsBtn').textContent = 'Saving...';

 const response = await fetch('/NUcare_Health_system/backend/ajax/rbac_save_permissions.ajax.php', {
 method: 'POST',
 headers: { 'Content-Type': 'application/json' },
 body: JSON.stringify({
 role_id: currentRoleId,
 permissions: permissions,
 }),
 });

 const data = await response.json();

 if (!data.ok) {
 alert('Error saving permissions: ' + data.message);
 } else {
 closeManageAccessModal();
 // Reload page to show updated permission counts
 location.reload();
 }
 } catch (error) {
 console.error('Error:', error);
 alert('Failed to save permissions');
 } finally {
 document.getElementById('savePermissionsBtn').disabled = false;
 document.getElementById('savePermissionsBtn').textContent = 'Save Changes';
 }
}

function htmlEscape(str) {
 const div = document.createElement('div');
 div.textContent = str;
 return div.innerHTML;
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
 const modal = document.getElementById('manageAccessModal');
 if (event.target === modal) {
 closeManageAccessModal();
 }
});
</script>

<script src="/NUcare_Health_system/assets/js/app.js"></script>
</body>
</html>





