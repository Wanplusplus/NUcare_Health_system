-- =========================================================
-- SAMPLE REGISTERED USERS
-- These users CAN login immediately
-- =========================================================

-- =========================================================
-- SCHOOL PEOPLE (REGISTERED)
-- =========================================================

INSERT INTO school_people
(
 SchoolID,
 FirstName,
 LastName,
 MiddleName,
 Email,
 PersonType,
 Sex
)
VALUES

-- STUDENTS
('2024-116605', 'John', 'Carter', 'Lee', 'john.carter@nucare.edu', 'Student', 'Male'),
('2024-116606', 'Angela', 'Reyes', 'Mae', 'angela.reyes@nucare.edu', 'Student', 'Female'),

-- FACULTY
('2024-116607', 'Maria', 'Santos', 'Lopez', 'maria.santos@nucare.edu', 'Faculty', 'Female'),

-- STAFF
('2024-116608', 'Robert', 'Diaz', 'Tan', 'robert.diaz@nucare.edu', 'Staff', 'Male'),

-- MEDICAL STAFF
('2024-116609', 'Samuel', 'Lim', 'Torres', 'samuel.lim@nucare.edu', 'Staff', 'Male'),
('2024-116610', 'Patricia', 'Cruz', 'Mendoza', 'patricia.cruz@nucare.edu', 'Staff', 'Female'),
('2024-116611', 'Kevin', 'Garcia', 'Flores', 'kevin.garcia@nucare.edu', 'Staff', 'Male'),

-- ADMINS
('2024-116612', 'Alice', 'Fernandez', 'Uy', 'alice.admin@nucare.edu', 'Staff', 'Female'),
('2024-116613', 'Brian', 'Villanueva', 'Go', 'brian.super@nucare.edu', 'Staff', 'Male');

-- =========================================================
-- PROGRAMS
-- =========================================================

INSERT INTO programs
(
 ProgramName,
 Department
)
VALUES
('BS Information Technology', 'CCS'),
('BS Nursing', 'CON'),
('BS Psychology', 'CAS');

-- =========================================================
-- STUDENT ENROLLMENTS
-- =========================================================

INSERT INTO student_enrollments
(
 SchoolPersonID,
 ProgramID,
 AcademicYear,
 Semester,
 EnrollmentStatus
)
VALUES
(1, 1, '2025-2026', '1st Semester', 'Enrolled'),
(2, 3, '2025-2026', '1st Semester', 'Enrolled');

-- =========================================================
-- EMPLOYEE ASSIGNMENTS
-- =========================================================

INSERT INTO employee_assignments
(
 SchoolPersonID,
 Department,
 PositionTitle,
 EmploymentStatus,
 StartDate
)
VALUES
(3, 'CCS', 'Professor', 'Employed', '2024-06-01'),
(4, 'Security Office', 'Security Guard', 'Employed', '2024-06-01'),

-- MEDICAL STAFF
(5, 'Clinic', 'Doctor', 'Employed', '2024-06-01'),
(6, 'Clinic', 'Nurse', 'Employed', '2024-06-01'),
(7, 'Clinic', 'Dentist', 'Employed', '2024-06-01'),

-- ADMINS
(8, 'ICT Office', 'System Administrator', 'Employed', '2024-06-01'),
(9, 'ICT Office', 'Super Administrator', 'Employed', '2024-06-01');

-- =========================================================
-- USERS
-- PASSWORDS BELOW
-- =========================================================

INSERT INTO users
(
 SchoolPersonID,
 PasswordHash,
 IsActive
)
VALUES

-- STUDENTS
(1, SHA2('student123', 256), TRUE),
(2, SHA2('psych123', 256), TRUE),

-- FACULTY
(3, SHA2('faculty123', 256), TRUE),

-- STAFF
(4, SHA2('staff123', 256), TRUE),

-- MEDICAL STAFF
(5, SHA2('doctor123', 256), TRUE),
(6, SHA2('nurse123', 256), TRUE),
(7, SHA2('dentist123', 256), TRUE),

-- ADMINS
(8, SHA2('admin123', 256), TRUE),
(9, SHA2('superadmin123', 256), TRUE);

-- =========================================================
-- USER ROLES
-- =========================================================

INSERT INTO user_roles
(
 UserID,
 RoleID
)
VALUES

-- STUDENTS
(1, 1),
(2, 1),

-- FACULTY
(3, 2),

-- STAFF
(4, 3),

-- DOCTOR
(5, 4),

-- NURSE
(6, 6),

-- DENTIST
(7, 5),

-- ADMIN
(8, 7),

-- SUPER ADMIN
(9, 8);

-- =========================================================
-- MEDICAL PROFESSIONALS
-- =========================================================

INSERT INTO medical_professionals
(
 UserID,
 Profession,
 Unit
)
VALUES
(5, 'Doctor', 'General Medicine'),
(6, 'Nurse', 'Clinic Nurse Station'),
(7, 'Dentist', 'Dental Clinic');

-- =========================================================
-- ROLE PERMISSIONS
-- =========================================================
-- ModuleID reference:
-- 1=Consultation, 2=Records, 3=Reports, 4=Medicine, 5=Schedule,
-- 6=Admin Panel, 7=RBAC Management, 8=User Management, 9=Audit Logs
-- PermissionID reference: 1=access, 7=manage
-- =========================================================

-- =========================================================
-- STUDENT / FACULTY / STAFF
-- Records/access + Schedule/access only
-- =========================================================

INSERT INTO role_permissions
(RoleID, ModuleID, PermissionID)
VALUES
-- STUDENT: Records/access, Schedule/access
(1, 2, 1),
(1, 5, 1),
-- FACULTY: Records/access, Schedule/access
(2, 2, 1),
(2, 5, 1),
-- STAFF: Records/access, Schedule/access
(3, 2, 1),
(3, 5, 1);

-- =========================================================
-- DOCTOR / DENTIST / NURSE
-- Consultation + Records + Reports + Medicine + Schedule
-- Each with BOTH access AND manage
-- =========================================================

INSERT INTO role_permissions
(RoleID, ModuleID, PermissionID)
VALUES
-- DOCTOR: 5 modules x (access + manage) = 10 rows
(4, 1, 1), (4, 1, 7),
(4, 2, 1), (4, 2, 7),
(4, 3, 1), (4, 3, 7),
(4, 4, 1), (4, 4, 7),
(4, 5, 1), (4, 5, 7),
-- DENTIST: 5 modules x (access + manage) = 10 rows
(5, 1, 1), (5, 1, 7),
(5, 2, 1), (5, 2, 7),
(5, 3, 1), (5, 3, 7),
(5, 4, 1), (5, 4, 7),
(5, 5, 1), (5, 5, 7),
-- NURSE: 5 modules x (access + manage) = 10 rows
(6, 1, 1), (6, 1, 7),
(6, 2, 1), (6, 2, 7),
(6, 3, 1), (6, 3, 7),
(6, 4, 1), (6, 4, 7),
(6, 5, 1), (6, 5, 7);

-- =========================================================
-- ADMIN
-- Admin Panel/access + User Management/access + Reports/access + Audit Logs/access
-- Must NOT have Consultation, Records, Medicine, Schedule, RBAC Management
-- =========================================================

INSERT INTO role_permissions
(RoleID, ModuleID, PermissionID)
VALUES
(7, 6, 1), -- Admin Panel -> access
(7, 8, 1), -- User Management -> access
(7, 3, 1), -- Reports -> access
(7, 9, 1); -- Audit Logs -> access

-- =========================================================
-- SUPER ADMIN
-- Admin Panel/access + User Management/access + RBAC Management/access + Reports/access + Audit Logs/access
-- Must NOT have Consultation, Records, Medicine, Schedule
-- =========================================================

INSERT INTO role_permissions
(RoleID, ModuleID, PermissionID)
VALUES
(8, 6, 1), -- Admin Panel -> access
(8, 8, 1), -- User Management -> access
(8, 7, 1), -- RBAC Management -> access
(8, 3, 1), -- Reports -> access
(8, 9, 1); -- Audit Logs -> access

-- =========================================================
-- UNREGISTERED SCHOOL PEOPLE
-- These CANNOT login yet
-- They ONLY exist in school_people
-- Used for signup testing
-- =========================================================

INSERT INTO school_people
(
 SchoolID,
 FirstName,
 LastName,
 MiddleName,
 Email,
 PersonType,
 Sex
)
VALUES

-- UNREGISTERED STUDENTS
('2024-116614', 'Chris', 'Navarro', 'Lim', 'chris.navarro@nucare.edu', 'Student', 'Male'),
('2024-116615', 'Samantha', 'Yu', 'Reyes', 'samantha.yu@nucare.edu', 'Student', 'Female'),

-- UNREGISTERED FACULTY
('2024-116616', 'Daniel', 'Ong', 'Torres', 'daniel.ong@nucare.edu', 'Faculty', 'Male'),

-- UNREGISTERED STAFF
('2024-116617', 'Monica', 'Ramos', 'Sy', 'monica.ramos@nucare.edu', 'Staff', 'Female');

-- =========================================================
-- ENROLLMENTS FOR UNREGISTERED STUDENTS
-- =========================================================

INSERT INTO student_enrollments
(
 SchoolPersonID,
 ProgramID,
 AcademicYear,
 Semester,
 EnrollmentStatus
)
VALUES
(10, 1, '2025-2026', '1st Semester', 'Enrolled'),
(11, 2, '2025-2026', '1st Semester', 'Enrolled');

-- =========================================================
-- EMPLOYEE ASSIGNMENTS FOR UNREGISTERED STAFF/FACULTY
-- =========================================================

INSERT INTO employee_assignments
(
 SchoolPersonID,
 Department,
 PositionTitle,
 EmploymentStatus,
 StartDate
)
VALUES
(12, 'CAS', 'Instructor', 'Employed', '2024-06-01'),
(13, 'Registrar', 'Office Staff', 'Employed', '2024-06-01');