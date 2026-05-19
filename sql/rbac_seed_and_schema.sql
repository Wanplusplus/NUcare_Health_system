-- Demo / seed data for nucaredb
-- Run AFTER sql/nucaredb.sql

USE nucaredb;

-- =========================================================
-- BASIC RBAC PERMISSIONS
-- =========================================================
INSERT INTO permissions (PermissionName, Description) VALUES
('access', 'Allow access to module'),
('View', 'Can view module'),
('Create', 'Can create records'),
('Edit', 'Can edit records'),
('Delete', 'Can delete records'),
('Manage', 'Full management permissions'),
('Approve', 'Can approve requests')
ON DUPLICATE KEY UPDATE Description = VALUES(Description);

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
ON DUPLICATE KEY UPDATE Description = VALUES(Description);

-- =========================================================
-- ROLES
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
ON DUPLICATE KEY UPDATE Description = VALUES(Description);

-- =========================================================
-- ROLE PERMISSIONS
-- =========================================================
-- Student: access/view records, consultation, schedule
INSERT INTO role_permissions (RoleID, ModuleID, PermissionID)
SELECT r.RoleID, m.ModuleID, p.PermissionID
FROM roles r
JOIN modules m ON m.ModuleName IN ('Records', 'Consultation', 'Schedule')
JOIN permissions p ON p.PermissionName IN ('access', 'View')
WHERE r.RoleName = 'Student'
ON DUPLICATE KEY UPDATE RoleID = VALUES(RoleID);

-- Faculty/Staff: access/view/create/edit for consultation, records, schedule, medicine
INSERT INTO role_permissions (RoleID, ModuleID, PermissionID)
SELECT r.RoleID, m.ModuleID, p.PermissionID
FROM roles r
JOIN modules m ON m.ModuleName IN ('Consultation', 'Records', 'Schedule', 'Medicine')
JOIN permissions p ON p.PermissionName IN ('access', 'View', 'Create', 'Edit')
WHERE r.RoleName IN ('Faculty', 'Staff')
ON DUPLICATE KEY UPDATE RoleID = VALUES(RoleID);

-- Doctor/Dentist/Nurse: access/view/create/edit/approve for consultation, records, schedule, medicine
INSERT INTO role_permissions (RoleID, ModuleID, PermissionID)
SELECT r.RoleID, m.ModuleID, p.PermissionID
FROM roles r
JOIN modules m ON m.ModuleName IN ('Consultation', 'Records', 'Schedule', 'Medicine')
JOIN permissions p ON p.PermissionName IN ('access', 'View', 'Create', 'Edit', 'Approve')
WHERE r.RoleName IN ('Doctor', 'Dentist', 'Nurse')
ON DUPLICATE KEY UPDATE RoleID = VALUES(RoleID);

-- Admin: full on reports + admin modules, plus general clinic modules
INSERT INTO role_permissions (RoleID, ModuleID, PermissionID)
SELECT r.RoleID, m.ModuleID, p.PermissionID
FROM roles r
JOIN modules m ON m.ModuleName IN ('Reports', 'Admin Panel', 'RBAC Management', 'Consultation', 'Records', 'Medicine', 'Schedule')
JOIN permissions p ON p.PermissionName IN ('access', 'View', 'Create', 'Edit', 'Manage', 'Approve', 'Delete')
WHERE r.RoleName = 'Admin'
ON DUPLICATE KEY UPDATE RoleID = VALUES(RoleID);

-- Super Admin: full management everywhere
INSERT INTO role_permissions (RoleID, ModuleID, PermissionID)
SELECT r.RoleID, m.ModuleID, p.PermissionID
FROM roles r
JOIN modules m ON 1 = 1
JOIN permissions p ON p.PermissionName IN ('access', 'View', 'Create', 'Edit', 'Manage', 'Approve', 'Delete')
WHERE r.RoleName = 'Super Admin'
ON DUPLICATE KEY UPDATE RoleID = VALUES(RoleID);

-- =========================================================
-- SCHOOL PEOPLE
-- PersonType must be Student/Faculty/Staff
-- Super Admin accounts are Staff identities with full RBAC access
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
('SCH-1010','Isla','Villanueva','J','isla.villanueva@nucare.edu','Student','Female')
ON DUPLICATE KEY UPDATE
FirstName = VALUES(FirstName),
LastName = VALUES(LastName),
MiddleName = VALUES(MiddleName),
Email = VALUES(Email),
PersonType = VALUES(PersonType),
Sex = VALUES(Sex);

-- 5 Faculty
INSERT INTO school_people (SchoolID, FirstName, LastName, MiddleName, Email, PersonType, Sex) VALUES
('SCH-2001','Ana','Fernandez','K','ana.fernandez@nucare.edu','Faculty','Female'),
('SCH-2002','Mark','Santos','L','mark.santos@nucare.edu','Faculty','Male'),
('SCH-2003','Kate','Gomez','M','kate.gomez@nucare.edu','Faculty','Female'),
('SCH-2004','Daniel','Aguilar','N','daniel.aguilar@nucare.edu','Faculty','Male'),
('SCH-2005','Grace','Santos','O','grace.santos@nucare.edu','Faculty','Female')
ON DUPLICATE KEY UPDATE
FirstName = VALUES(FirstName),
LastName = VALUES(LastName),
MiddleName = VALUES(MiddleName),
Email = VALUES(Email),
PersonType = VALUES(PersonType),
Sex = VALUES(Sex);

-- 5 Staff
INSERT INTO school_people (SchoolID, FirstName, LastName, MiddleName, Email, PersonType, Sex) VALUES
('SCH-3001','Paul','Ramos','P','paul.ramos@nucare.edu','Staff','Male'),
('SCH-3002','Jade','Flores','Q','jade.flores@nucare.edu','Staff','Female'),
('SCH-3003','Victor','Navarro','R','victor.navarro@nucare.edu','Staff','Male'),
('SCH-3004','Nina','Ortega','S','nina.ortega@nucare.edu','Staff','Female'),
('SCH-3005','Oscar','Montoya','T','oscar.montoya@nucare.edu','Staff','Male')
ON DUPLICATE KEY UPDATE
FirstName = VALUES(FirstName),
LastName = VALUES(LastName),
MiddleName = VALUES(MiddleName),
Email = VALUES(Email),
PersonType = VALUES(PersonType),
Sex = VALUES(Sex);

-- 2 Doctors (stored as Staff identities)
INSERT INTO school_people (SchoolID, FirstName, LastName, MiddleName, Email, PersonType, Sex) VALUES
('SCH-4001','Dr. Miguel','Cordero','U','miguel.cordero@nucare.edu','Staff','Male'),
('SCH-4002','Dr. Carla','Paredes','V','carla.paredes@nucare.edu','Staff','Female')
ON DUPLICATE KEY UPDATE
FirstName = VALUES(FirstName),
LastName = VALUES(LastName),
MiddleName = VALUES(MiddleName),
Email = VALUES(Email),
PersonType = VALUES(PersonType),
Sex = VALUES(Sex);

-- 1 Dentist
INSERT INTO school_people (SchoolID, FirstName, LastName, MiddleName, Email, PersonType, Sex) VALUES
('SCH-5001','Dr. Rafael','Bautista','W','rafael.bautista@nucare.edu','Staff','Male')
ON DUPLICATE KEY UPDATE
FirstName = VALUES(FirstName),
LastName = VALUES(LastName),
MiddleName = VALUES(MiddleName),
Email = VALUES(Email),
PersonType = VALUES(PersonType),
Sex = VALUES(Sex);

-- 2 Nurses
INSERT INTO school_people (SchoolID, FirstName, LastName, MiddleName, Email, PersonType, Sex) VALUES
('SCH-6001','Nurse Helen','Reyes','X','helen.reyes@nucare.edu','Staff','Female'),
('SCH-6002','Nurse Josephine','Cruz','Y','josephine.cruz@nucare.edu','Staff','Female')
ON DUPLICATE KEY UPDATE
FirstName = VALUES(FirstName),
LastName = VALUES(LastName),
MiddleName = VALUES(MiddleName),
Email = VALUES(Email),
PersonType = VALUES(PersonType),
Sex = VALUES(Sex);

-- 1 Admin
INSERT INTO school_people (SchoolID, FirstName, LastName, MiddleName, Email, PersonType, Sex) VALUES
('SCH-7001','Admin','Dela Rosa','Z','admin.delarosa@nucare.edu','Faculty','Male')
ON DUPLICATE KEY UPDATE
FirstName = VALUES(FirstName),
LastName = VALUES(LastName),
MiddleName = VALUES(MiddleName),
Email = VALUES(Email),
PersonType = VALUES(PersonType),
Sex = VALUES(Sex);

-- 3 Super Admin staff accounts
INSERT INTO school_people (SchoolID, FirstName, LastName, MiddleName, Email, PersonType, Sex) VALUES
('SCH-8001','Super','Admin One','AA','superadmin1@nucare.edu','Staff','Female'),
('SCH-8002','Super','Admin Two','AB','superadmin2@nucare.edu','Staff','Male'),
('SCH-8003','Super','Admin Three','AC','superadmin3@nucare.edu','Staff','Female')
ON DUPLICATE KEY UPDATE
FirstName = VALUES(FirstName),
LastName = VALUES(LastName),
MiddleName = VALUES(MiddleName),
Email = VALUES(Email),
PersonType = VALUES(PersonType),
Sex = VALUES(Sex);

-- =========================================================
-- USERS + USER ROLES
-- Password for all three Super Admin accounts: DemoPass123!
-- =========================================================
INSERT INTO users (SchoolPersonID, PasswordHash, IsActive)
SELECT sp.SchoolPersonID, '$2y$10$MbHUvivnEtPN9vR/CIY7DezcYUW80wm8QBkvCqaB1EZXJmXUi6.9C', 1
FROM school_people sp
WHERE sp.SchoolID IN ('SCH-8001', 'SCH-8002', 'SCH-8003')
ON DUPLICATE KEY UPDATE
PasswordHash = VALUES(PasswordHash),
IsActive = VALUES(IsActive);

INSERT INTO user_roles (UserID, RoleID)
SELECT u.UserID, r.RoleID
FROM users u
INNER JOIN school_people sp ON sp.SchoolPersonID = u.SchoolPersonID
INNER JOIN roles r ON r.RoleName = 'Super Admin'
WHERE sp.SchoolID IN ('SCH-8001', 'SCH-8002', 'SCH-8003')
ON DUPLICATE KEY UPDATE
RoleID = VALUES(RoleID);

-- =========================================================
-- DEMO STUDENT USERS
-- Password for demo student accounts: DemoPass123!
-- =========================================================
INSERT INTO users (SchoolPersonID, PasswordHash, IsActive)
SELECT sp.SchoolPersonID, '$2y$10$MbHUvivnEtPN9vR/CIY7DezcYUW80wm8QBkvCqaB1EZXJmXUi6.9C', 1
FROM school_people sp
WHERE sp.SchoolID IN ('SCH-1001', 'SCH-1002', 'SCH-1003', 'SCH-1004', 'SCH-1005', 'SCH-1006', 'SCH-1007', 'SCH-1008', 'SCH-1009', 'SCH-1010')
ON DUPLICATE KEY UPDATE
PasswordHash = VALUES(PasswordHash),
IsActive = VALUES(IsActive);

INSERT INTO user_roles (UserID, RoleID)
SELECT u.UserID, r.RoleID
FROM users u
INNER JOIN school_people sp ON sp.SchoolPersonID = u.SchoolPersonID
INNER JOIN roles r ON r.RoleName = 'Student'
WHERE sp.SchoolID IN ('SCH-1001', 'SCH-1002', 'SCH-1003', 'SCH-1004', 'SCH-1005', 'SCH-1006', 'SCH-1007', 'SCH-1008', 'SCH-1009', 'SCH-1010')
ON DUPLICATE KEY UPDATE
RoleID = VALUES(RoleID);

-- =========================================================
-- EMPLOYEE ASSIGNMENTS
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
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-6002'),'Clinic','Nurse','Employed','2025-01-01'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-8001'),'Administration','System Admin','Employed','2025-01-01'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-8002'),'Administration','System Admin','Employed','2025-01-01'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-8003'),'Administration','System Admin','Employed','2025-01-01')
ON DUPLICATE KEY UPDATE
Department = VALUES(Department),
PositionTitle = VALUES(PositionTitle),
EmploymentStatus = VALUES(EmploymentStatus),
StartDate = VALUES(StartDate);

-- =========================================================
-- STUDENT ENROLLMENTS
-- =========================================================
INSERT INTO student_enrollments (SchoolPersonID, ProgramID, AcademicYear, Semester, EnrollmentStatus) VALUES
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1001'), NULL,'2025-2026','1st Semester','Enrolled'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1002'), NULL,'2025-2026','1st Semester','Enrolled'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1003'), NULL,'2025-2026','1st Semester','Enrolled'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1004'), NULL,'2025-2026','1st Semester','Dropped'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1005'), NULL,'2025-2026','1st Semester','Enrolled'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1006'), NULL,'2025-2026','1st Semester','Enrolled'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1007'), NULL,'2025-2026','1st Semester','Dropped'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1008'), NULL,'2025-2026','1st Semester','Enrolled')
ON DUPLICATE KEY UPDATE
ProgramID = VALUES(ProgramID),
AcademicYear = VALUES(AcademicYear),
Semester = VALUES(Semester),
EnrollmentStatus = VALUES(EnrollmentStatus);

INSERT INTO student_enrollments (SchoolPersonID, ProgramID, AcademicYear, Semester, EnrollmentStatus) VALUES
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1001'), NULL,'2025-2026','2nd Semester','Enrolled'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1002'), NULL,'2025-2026','2nd Semester','Enrolled'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1003'), NULL,'2025-2026','2nd Semester','Enrolled'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1004'), NULL,'2025-2026','2nd Semester','Not Enrolled'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1005'), NULL,'2025-2026','2nd Semester','Enrolled'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1006'), NULL,'2025-2026','2nd Semester','Enrolled'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1007'), NULL,'2025-2026','2nd Semester','Not Enrolled'),
((SELECT SchoolPersonID FROM school_people WHERE SchoolID='SCH-1008'), NULL,'2025-2026','2nd Semester','Enrolled')
ON DUPLICATE KEY UPDATE
ProgramID = VALUES(ProgramID),
AcademicYear = VALUES(AcademicYear),
Semester = VALUES(Semester),
EnrollmentStatus = VALUES(EnrollmentStatus);
