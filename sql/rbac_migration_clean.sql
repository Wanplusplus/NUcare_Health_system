-- =====================================================================
-- RBAC Migration: Clean role_permissions to match final role model
-- =====================================================================
-- IMPORTANT: Backup existing role_permissions first:
--   CREATE TABLE role_permissions_backup_20260604 AS SELECT * FROM role_permissions;
-- =====================================================================

-- Step 1: Backup existing role_permissions
CREATE TABLE IF NOT EXISTS role_permissions_backup_20260604 AS SELECT * FROM role_permissions;

-- Step 2: Delete ALL existing role_permissions rows
-- We rebuild from scratch to ensure no legacy view/create/edit/delete/approve remain
DELETE FROM role_permissions;

-- Step 3: Insert correct permissions per role model
-- Using JOINs on names (not hardcoded IDs) for safety

-- STUDENT (RoleName = 'Student')
-- Modules: Records, Schedule
-- Permissions: access
INSERT INTO role_permissions (RoleID, ModuleID, PermissionID)
SELECT r.RoleID, m.ModuleID, p.PermissionID
FROM roles r
CROSS JOIN modules m
CROSS JOIN permissions p
WHERE r.RoleName = 'Student'
  AND m.ModuleName IN ('Records', 'Schedule')
  AND p.PermissionName = 'access';

-- FACULTY (RoleName = 'Faculty')
-- Modules: Records, Schedule
-- Permissions: access
INSERT INTO role_permissions (RoleID, ModuleID, PermissionID)
SELECT r.RoleID, m.ModuleID, p.PermissionID
FROM roles r
CROSS JOIN modules m
CROSS JOIN permissions p
WHERE r.RoleName = 'Faculty'
  AND m.ModuleName IN ('Records', 'Schedule')
  AND p.PermissionName = 'access';

-- STAFF (RoleName = 'Staff')
-- Modules: Records, Schedule
-- Permissions: access
INSERT INTO role_permissions (RoleID, ModuleID, PermissionID)
SELECT r.RoleID, m.ModuleID, p.PermissionID
FROM roles r
CROSS JOIN modules m
CROSS JOIN permissions p
WHERE r.RoleName = 'Staff'
  AND m.ModuleName IN ('Records', 'Schedule')
  AND p.PermissionName = 'access';

-- DOCTOR (RoleName = 'Doctor')
-- Modules: Consultation, Records, Reports, Medicine, Schedule
-- Permissions: access, manage
INSERT INTO role_permissions (RoleID, ModuleID, PermissionID)
SELECT r.RoleID, m.ModuleID, p.PermissionID
FROM roles r
CROSS JOIN modules m
CROSS JOIN permissions p
WHERE r.RoleName = 'Doctor'
  AND m.ModuleName IN ('Consultation', 'Records', 'Reports', 'Medicine', 'Schedule')
  AND p.PermissionName IN ('access', 'manage');

-- DENTIST (RoleName = 'Dentist')
-- Modules: Consultation, Records, Reports, Medicine, Schedule
-- Permissions: access, manage
INSERT INTO role_permissions (RoleID, ModuleID, PermissionID)
SELECT r.RoleID, m.ModuleID, p.PermissionID
FROM roles r
CROSS JOIN modules m
CROSS JOIN permissions p
WHERE r.RoleName = 'Dentist'
  AND m.ModuleName IN ('Consultation', 'Records', 'Reports', 'Medicine', 'Schedule')
  AND p.PermissionName IN ('access', 'manage');

-- NURSE (RoleName = 'Nurse')
-- Modules: Consultation, Records, Reports, Medicine, Schedule
-- Permissions: access, manage
INSERT INTO role_permissions (RoleID, ModuleID, PermissionID)
SELECT r.RoleID, m.ModuleID, p.PermissionID
FROM roles r
CROSS JOIN modules m
CROSS JOIN permissions p
WHERE r.RoleName = 'Nurse'
  AND m.ModuleName IN ('Consultation', 'Records', 'Reports', 'Medicine', 'Schedule')
  AND p.PermissionName IN ('access', 'manage');

-- ADMIN (RoleName = 'Admin')
-- Modules: Admin Panel, User Management, Reports, Audit Logs
-- Permissions: access
-- NOTE: Admin must NOT have Consultation, Records, Medicine, Schedule, RBAC Management
INSERT INTO role_permissions (RoleID, ModuleID, PermissionID)
SELECT r.RoleID, m.ModuleID, p.PermissionID
FROM roles r
CROSS JOIN modules m
CROSS JOIN permissions p
WHERE r.RoleName = 'Admin'
  AND m.ModuleName IN ('Admin Panel', 'User Management', 'Reports', 'Audit Logs')
  AND p.PermissionName = 'access';

-- SUPER ADMIN (RoleName = 'Super Admin')
-- Modules: Admin Panel, User Management, RBAC Management, Reports, Audit Logs
-- Permissions: access
-- NOTE: Super Admin must NOT have Consultation, Records, Medicine, Schedule
-- Super Admin has access to RBAC Management (the only role that does)
INSERT INTO role_permissions (RoleID, ModuleID, PermissionID)
SELECT r.RoleID, m.ModuleID, p.PermissionID
FROM roles r
CROSS JOIN modules m
CROSS JOIN permissions p
WHERE r.RoleName = 'Super Admin'
  AND m.ModuleName IN ('Admin Panel', 'User Management', 'RBAC Management', 'Reports', 'Audit Logs')
  AND p.PermissionName = 'access';

-- =====================================================================
-- Verification queries (run these to confirm)
-- =====================================================================
-- SELECT rolename FROM roles -- to confirm role IDs

-- Count per role:
-- SELECT r.RoleName, COUNT(*) as permission_count
-- FROM role_permissions rp
-- INNER JOIN roles r ON r.RoleID = rp.RoleID
-- GROUP BY r.RoleName
-- ORDER BY r.RoleName;

-- Show assigned module+permission for each role:
-- SELECT r.RoleName, m.ModuleName, p.PermissionName
-- FROM role_permissions rp
-- INNER JOIN roles r ON r.RoleID = rp.RoleID
-- INNER JOIN modules m ON m.ModuleID = rp.ModuleID
-- INNER JOIN permissions p ON p.PermissionID = rp.PermissionID
-- ORDER BY r.RoleName, m.ModuleName, p.PermissionName;

-- Super Admin should have 5 rows
-- Admin should have 4 rows
-- Student/Faculty/Staff should have 2 rows
-- Doctor/Dentist/Nurse should have 10 rows