<?php
declare(strict_types=1);

require_once __DIR__ . '/rbac.php';

/**
 * Enforce permission for a module and (for students) logical access based on enrollment.
 * This is intentionally simple so we can inject it into existing module entry files
 * without redesigning any UI.
 */
function requireModule(string $moduleName, string $permissionName = 'access'): void {
 if (session_status() === PHP_SESSION_NONE) {
 session_start();
 }

 // Backward compatibility: legacy UI used patient_id.
 // If new RBAC session values exist, we use them; otherwise deny.
 if (!isset($_SESSION['UserID'])) {
 // Keep existing behavior: redirect to login.
 header('Location: /NUcare_Health_system/frontend/auth/login.php');
 exit;
 }

 // RBAC enforcement + student enrollment override
 rbacRequireModulePermission($moduleName, $permissionName);
}


