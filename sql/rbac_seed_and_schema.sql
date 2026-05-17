-- Demo / seed data for nucaredb
-- Run AFTER sql/nucaredb.sql

USE nucaredb;

-- =========================================================
-- BASIC RBAC PERMISSIONS
-- Ensure permissions exist
-- =========================================================
INSERT INTO permissions (PermissionName, Description) VALUES
('access', 'Allow access to module'),
('View', 'Can view module'),
('Create', 'Can create records'),
('Edit', 'Can edit records'),
('Delete', 'Can delete records'),
('Manage', 'Full management permissions'),
('Approve', 'Can approve requests')
ON DUPLICATE KEY UPDATE PermissionName = PermissionName;

-- =========================================================
-- BASIC RBAC MODULES
-- =========================================================
INSERT INTO modules (ModuleName, Description) VALUES
('Consultation', 'Consultation module'),
('Records', 'Clinic records module'),
('Reports', 'Reports module'),
('Medicine', 'Medicine inventory module'),
('Schedule', 'Scheduling module'),
('Admin Panel', 'Administrative module'),
('RBAC Management', 'Role management module')
ON DUPLICATE KEY UPDATE ModuleName = ModuleName;

-- =========================================================
-- Ensure roles exist (these were already inserted in schema)
-- =========================================================
INSERT INTO roles (RoleName, Description) VALUES
('Student', 'Student user'),
('Faculty', 'Faculty user'),
('Staff', 'Staff user'),
('Doctor', 'Doctor access'),
('Dentist', 'Dentist access'),
('Nurse', 'Nurse access'),
('Admin', 'Admin access'),
('Super Admin', 'Full system access')
ON DUPLICATE KEY UPDATE RoleName = RoleName;

-- =========================================================
-- Map permissions to modules (module + permission => permissionID)
-- We rely on: permissions.PermissionName + permissions.Description being per-module
-- However your schema defines a single permissions table without module linkage.
-- We'll interpret your existing schema as:
-- permissions rows correspond to a specific PermissionName only (global),
-- BUT your RBAC join expects permissions.PermissionID -> role_permissions -> modules via role_permissions.ModuleID.
-- Therefore, role_permissions uses ModuleID, and permissions.PermissionID is permissionName.
-- This means we can safely keep permissions global per PermissionName.
-- =========================================================

-- =========================================================
-- role_permissions (RoleID, ModuleID, PermissionID)
-- Admin/Super Admin: reports, user/admin, rbac management
-- Doctor/Dentist/Nurse/Faculty/Staff: consultation/records/medicine/schedule as appropriate
-- Students: access + view records; booking blocked by enrollment logic in includes/rbac.php
-- =========================================================

-- Helper: insert role_permissions rows idempotently by checking existence
-- (MySQL doesn't have IF NOT EXISTS for multi-column unique reliably across versions,
--  so we use INSERT ... SELECT ... WHERE NOT EXISTS)
INSERT INTO role_permissions (RoleID, ModuleID, PermissionID)
SELECT r.RoleID, m.ModuleID, p.PermissionID
FROM roles r
CROSS JOIN modules m
JOIN permissions p
WHERE 1=1
  AND r.RoleName IN ('Student','Faculty','Staff','Doctor','Dentist','Nurse','Admin','Super Admin')
  AND m.ModuleName IN ('Consultation','Records','Reports','Medicine','Schedule','Admin Panel','RBAC Management')
  AND p.PermissionName IN ('access','View','Create','Edit','Manage','Approve','Delete')
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions rp
      WHERE rp.RoleID = r.RoleID AND rp.ModuleID = m.ModuleID AND rp.PermissionID = p.PermissionID
  );

-- Overwrite with explicit minimal RBAC (more deterministic):
-- We'll delete existing broad mapping above for clarity.
-- NOTE: This is safe because we just inserted with a cross product; if you already have real RBAC,
-- adjust this section to your needs. If you must preserve existing RBAC, remove this section.

DELETE rp
FROM role_permissions rp
JOIN roles r ON r.RoleID = rp.RoleID
JOIN modules m ON m.ModuleID = rp.ModuleID
JOIN permissions p ON p.PermissionID = rp.PermissionID
WHERE r.RoleName IN ('Student','Faculty','Staff','Doctor','Dentist','Nurse','Admin','Super Admin')
  AND m.ModuleName IN ('Consultation','Records','Reports','Medicine','Schedule','Admin Panel','RBAC Management');

-- Student: access/view records/consultation/schedule(booking blocked by enrollment rule)
INSERT INTO role_permissions (RoleID, ModuleID, PermissionID)
SELECT r.RoleID, m.ModuleID, p.PermissionID
FROM roles r
JOIN modules m ON m.ModuleName IN ('Records','Consultation','Schedule')
JOIN permissions p ON p.PermissionName IN ('access','View')
WHERE r.RoleName = 'Student'
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions rp
      WHERE rp.RoleID=r.RoleID AND rp.ModuleID=m.ModuleID AND rp.PermissionID=p.PermissionID
  );

-- Faculty/Staff: access/view + create/edit for consultation/records/schedule
INSERT INTO role_permissions (RoleID, ModuleID, PermissionID)
SELECT r.RoleID, m.ModuleID, p.PermissionID
FROM roles r
JOIN modules m ON m.ModuleName IN ('Consultation','Records','Schedule','Medicine')
JOIN permissions p ON p.PermissionName IN ('access','View','Create','Edit')
WHERE r.RoleName IN ('Faculty','Staff')
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions rp
      WHERE rp.RoleID=r.RoleID AND rp.ModuleID=m.ModuleID AND rp.PermissionID=p.PermissionID
  );

-- Doctor/Dentist/Nurse: access/view + create/edit for consultation/records/schedule/medicine
INSERT INTO role_permissions (RoleID, ModuleID, PermissionID)
SELECT r.RoleID, m.ModuleID, p.PermissionID
FROM roles r
JOIN modules m ON m.ModuleName IN ('Consultation','Records','Schedule','Medicine')
JOIN permissions p ON p.PermissionName IN ('access','View','Create','Edit','Approve')
WHERE r.RoleName IN ('Doctor','Dentist','Nurse')
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions rp
      WHERE rp.RoleID=r.RoleID AND rp.ModuleID=m.ModuleID AND rp.PermissionID=p.PermissionID
  );

-- Admin: full on Reports + User/Admin modules; also has general medical module access
INSERT INTO role_permissions (RoleID, ModuleID, PermissionID)
SELECT r.RoleID, m.ModuleID, p.PermissionID
FROM roles r
JOIN modules m ON m.ModuleName IN ('Reports','Admin Panel','RBAC Management','Consultation','Records','Medicine','Schedule')
JOIN permissions p ON p.PermissionName IN ('access','View','Create','Edit','Manage','Approve','Delete')
WHERE r.RoleName IN ('Admin')
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions rp
      WHERE rp.RoleID=r.RoleID AND rp.ModuleID=m.ModuleID AND rp.PermissionID=p.PermissionID
  );

-- Super Admin: full management everywhere
INSERT INTO role_permissions (RoleID, ModuleID, PermissionID)
SELECT r.RoleID, m.ModuleID, p.PermissionID
FROM roles r
JOIN modules m ON 1=1
JOIN permissions p ON p.PermissionName IN ('access','View','Create','Edit','Manage','Approve','Delete')
WHERE r.RoleName = 'Super Admin'
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions rp
      WHERE rp.RoleID=r.RoleID AND rp.ModuleID=m.ModuleID AND rp.PermissionID=p.PermissionID
  );

-- =========================================================
-- school_people demo data
-- PersonType must be Student/Faculty/Staff
-- Your schema does not store Doctor/Dentist/Nurse in PersonType;
-- Doctors/Dentists/Nurses are represented via employee_assignments.PositionTitle
-- =========================================================
-- 10 Students
INSERT INTO school_people (SchoolID, FirstName, LastName, MiddleName, Email, PersonType, Sex) VALUES
('SCH-1001','Juan','Santos','A','juan.santos@nucare.edu','Student','Male'),
('SCH-1002','Maria','Reyes','B','maria.reyes@nucare.edu','Student','Female'),
('SCH-1003','Liam','Cruz','C','liam.cruz@nucare.edu','Student','Male'),
('SCH-1004','Sophia','Dela Cruz','D','sophia.delacruz@nucare.edu','Student','Female'),
('SCH-1005','Noah','Garcia','E','noah.garcia@nucare.edu','Student','Male'),
('SCH-1006','Emma','Mendoza','F','emma.mendoza@nucare.edu','Student','Female'),
('SCH-1007','Aiden','Torres','G','aiden.torres@nucare.edu','Student','Male'),
('SCH-1008','Olivia','Ramos','H','olivia.ramos@nucare.edu','Student','Female'),
('SCH-1009','Ethan','Castillo','I','ethan.castillo@nucare.edu','Student','Male'),
('SCH-1010','Isla','Villanueva','J','isla.villanueva@nucare.edu','Student','Female');

-- 5 Faculty
INSERT INTO school_people (SchoolID, FirstName, LastName, MiddleName, Email, PersonType, Sex) VALUES
('SCH-2001','Ana','Fernandez','K','ana.fernandez@nucare.edu','Faculty','Female'),
('SCH-2002','Mark','Santos','L','mark.santos@nucare.edu','Faculty','Male'),
('SCH-2003','Kate','Gomez','M','kate.gomez@nucare.edu','Faculty','Female'),
('SCH-2004','Daniel','Aguilar','N','daniel.aguilar@nucare.edu','Faculty','Male'),
('SCH-2005','Grace','Santos','O','grace.santos@nucare.edu','Faculty','Female');

-- 5 Staff
INSERT INTO school_people (SchoolID, FirstName, LastName, MiddleName, Email, PersonType, Sex) VALUES
('SCH-3001','Paul','Ramos','P','paul.ramos@nucare.edu','Staff','Male'),
('SCH-3002','Jade','Flores','Q','jade.flores@nucare.edu','Staff','Female'),
('SCH-3003','Victor','Navarro','R','victor.navarro@nucare.edu','Staff','Male'),
('SCH-3004','Nina','Ortega','S','nina.ortega@nucare.edu','Staff','Female'),
('SCH-3005','Oscar','Montoya','T','oscar.montoya@nucare.edu','Staff','Male');

-- 2 Doctors
INSERT INTO school_people (SchoolID, FirstName, LastName, MiddleName, Email, PersonType, Sex) VALUES
('SCH-4001','Dr. Miguel','Cordero','U','miguel.cordero@nucare.edu','Staff','Male'),
('SCH-4002','Dr. Carla','Paredes','V','carla.paredes@nucare.edu','Staff','Female');

-- 1 Dentist
INSERT INTO school_people (SchoolID, FirstName, LastName, MiddleName, Email, PersonType, Sex) VALUES
('SCH-5001','Dr. Rafael','Bautista','W','rafael.bautista@nucare.edu','Staff','Male');

-- 2 Nurses
INSERT INTO school_people (SchoolID, FirstName, LastName, MiddleName, Email, PersonType, Sex) VALUES
('SCH-6001','Nurse Helen','Reyes','X','helen.reyes@nucare.edu','Staff','Female'),
('SCH-6002','Nurse Josephine','Cruz','Y','josephine.cruz@nucare.edu','Staff','Female');

-- 1 Admin
INSERT INTO school_people (SchoolID, FirstName, LastName, MiddleName, Email, PersonType, Sex) VALUES
('SCH-7001','Admin','Dela Rosa','Z','admin.delarosa@nucare.edu','Faculty','Male');

-- 1 Super Admin
INSERT INTO school_people (SchoolID, FirstName, LastName, MiddleName, Email, PersonType, Sex) VALUES
('SCH-8001','Super','Admin','AA','superadmin@nucare.edu','Faculty','Female');

-- =========================================================
-- student_enrollments demo data
-- AcademicYear: 2025-2026
-- Semester: 1st Semester, 2nd Semester
-- Include: enrolled, dropped, not enrolled (we model only present students with statuses)
-- =========================================================
-- Enroll 1st semester
INSERT INTO student_enrollments (SchoolPersonID, ProgramID, AcademicYear, Semester, EnrollmentStatus) VALUES
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1001'), NULL,'2025-2026','1st Semester','Enrolled'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1002'), NULL,'2025-2026','1st Semester','Enrolled'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1003'), NULL,'2025-2026','1st Semester','Enrolled'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1004'), NULL,'2025-2026','1st Semester','Dropped'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1005'), NULL,'2025-2026','1st Semester','Enrolled'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1006'), NULL,'2025-2026','1st Semester','Enrolled'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1007'), NULL,'2025-2026','1st Semester','Dropped'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1008'), NULL,'2025-2026','1st Semester','Enrolled');

-- 2nd semester
INSERT INTO student_enrollments (SchoolPersonID, ProgramID, AcademicYear, Semester, EnrollmentStatus) VALUES
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1001'), NULL,'2025-2026','2nd Semester','Enrolled'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1002'), NULL,'2025-2026','2nd Semester','Enrolled'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1003'), NULL,'2025-2026','2nd Semester','Enrolled'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1004'), NULL,'2025-2026','2nd Semester','Not Enrolled'), -- if enum allows; otherwise use Dropped
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1005'), NULL,'2025-2026','2nd Semester','Enrolled'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1006'), NULL,'2025-2026','2nd Semester','Enrolled'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1007'), NULL,'2025-2026','2nd Semester','Not Enrolled'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1008'), NULL,'2025-2026','2nd Semester','Enrolled');

-- =========================================================
-- employee_assignments demo data
-- Guards, janitors, registrar, faculty, clinic staff
-- PositionTitle is used by some legacy logic; for RBAC we rely on roles anyway.
-- =========================================================
INSERT INTO employee_assignments (SchoolPersonID, Department, PositionTitle, EmploymentStatus, StartDate)
VALUES
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-3001'),'Security','Guard','Employed','2025-01-01'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-3002'),'Facilities','Janitor','Employed','2025-01-01'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-3003'),'Registrar','Registrar','Employed','2025-01-01'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-2001'),'College of Nursing','Faculty','Employed','2025-01-01'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-2002'),'College of Medicine','Faculty','Employed','2025-01-01'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-4001'),'Clinic','Physician','Employed','2025-01-01'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-4002'),'Clinic','Physician','Employed','2025-01-01'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-5001'),'Clinic','Dentist','Employed','2025-01-01'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-6001'),'Clinic','Nurse','Employed','2025-01-01'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-6002'),'Clinic','Nurse','Employed','2025-01-01');

-- =========================================================
-- users + user_roles demo
-- NOTE: password hashes must match the plaintext you will use.
-- For demo purposes, seed with a fixed password you will later tell users to use:
-- Password: DemoPass123!
-- =========================================================

-- Precomputed hashes are environment-dependent; easiest is to let users register normally.
-- So we only seed RBAC accounts if you already have users/PasswordHash.
-- If you MUST create accounts here, replace placeholder hashes with real password_hash outputs.

-- Create placeholder users if not exists (requires manual hash values)
-- (We skip inserting users/password hashes to avoid invalid logins.)
-- =========================================================
-- user_roles seeding is also skipped without users.

-- =========================================================
-- IMPORTANT:
-- For testing immediately:
--   - Create accounts via existing signup flow after running this seed
--   - Or manually insert users with real password hashes.
-- =========================================================
