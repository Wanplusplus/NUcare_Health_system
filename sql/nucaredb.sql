CREATE DATABASE IF NOT EXISTS nucaredb;
USE nucaredb;

-- =========================================================
-- SCHOOL PEOPLE
-- Master identity table
-- =========================================================

CREATE TABLE school_people (
    SchoolPersonID INT AUTO_INCREMENT PRIMARY KEY,

    SchoolID VARCHAR(50) NOT NULL UNIQUE,

    FirstName VARCHAR(100) NOT NULL,
    LastName VARCHAR(100) NOT NULL,
    MiddleName VARCHAR(100),

    Email VARCHAR(255) UNIQUE,

    PersonType ENUM(
        'Student',
        'Faculty',
        'Staff'
    ) NOT NULL,

    Sex ENUM('Male', 'Female') NOT NULL,

    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================================================
-- PROGRAMS
-- =========================================================

CREATE TABLE programs (
    ProgramID INT AUTO_INCREMENT PRIMARY KEY,

    ProgramName VARCHAR(150) NOT NULL,
    Department VARCHAR(150) NOT NULL
);

-- =========================================================
-- STUDENT ENROLLMENTS
-- =========================================================

CREATE TABLE student_enrollments (
    EnrollmentID INT AUTO_INCREMENT PRIMARY KEY,

    SchoolPersonID INT NOT NULL,
    ProgramID INT,

    AcademicYear VARCHAR(20) NOT NULL,
    Semester VARCHAR(20) NOT NULL,

    EnrollmentStatus ENUM(
        'Enrolled',
        'Not Enrolled',
        'Dropped',
        'Graduated'
    ) NOT NULL DEFAULT 'Enrolled',

    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_enrollment_person
        FOREIGN KEY (SchoolPersonID)
        REFERENCES school_people(SchoolPersonID),

    CONSTRAINT fk_enrollment_program
        FOREIGN KEY (ProgramID)
        REFERENCES programs(ProgramID)
);

-- =========================================================
-- EMPLOYEE ASSIGNMENTS
-- =========================================================

CREATE TABLE employee_assignments (
    AssignmentID INT AUTO_INCREMENT PRIMARY KEY,

    SchoolPersonID INT NOT NULL,

    Department VARCHAR(150),
    PositionTitle VARCHAR(150),

    EmploymentStatus ENUM(
        'Employed',
        'Resigned',
        'Inactive'
    ) NOT NULL DEFAULT 'Employed',

    StartDate DATE,
    EndDate DATE,

    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_employee_person
        FOREIGN KEY (SchoolPersonID)
        REFERENCES school_people(SchoolPersonID)
);

-- =========================================================
-- USERS
-- =========================================================

CREATE TABLE users (
    UserID INT AUTO_INCREMENT PRIMARY KEY,

    SchoolPersonID INT NOT NULL UNIQUE,

    PasswordHash VARCHAR(255) NOT NULL,

    ResetToken VARCHAR(255),
    TokenExpiry DATETIME,

    IsActive BOOLEAN DEFAULT TRUE,

    LastLogin DATETIME,

    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_user_person
        FOREIGN KEY (SchoolPersonID)
        REFERENCES school_people(SchoolPersonID)
);

-- =========================================================
-- ROLES
-- =========================================================

CREATE TABLE roles (
    RoleID INT AUTO_INCREMENT PRIMARY KEY,

    RoleName VARCHAR(100) NOT NULL UNIQUE,
    Description VARCHAR(255)
);

-- =========================================================
-- MODULES
-- =========================================================

CREATE TABLE modules (
    ModuleID INT AUTO_INCREMENT PRIMARY KEY,

    ModuleName VARCHAR(100) NOT NULL UNIQUE,
    Description VARCHAR(255)
);

-- =========================================================
-- PERMISSIONS
-- =========================================================

CREATE TABLE permissions (
    PermissionID INT AUTO_INCREMENT PRIMARY KEY,

    PermissionName VARCHAR(100) NOT NULL UNIQUE,
    Description VARCHAR(255)
);

-- =========================================================
-- USER ROLES
-- =========================================================

CREATE TABLE user_roles (
    UserRoleID INT AUTO_INCREMENT PRIMARY KEY,

    UserID INT NOT NULL,
    RoleID INT NOT NULL,

    CONSTRAINT fk_ur_user
        FOREIGN KEY (UserID)
        REFERENCES users(UserID),

    CONSTRAINT fk_ur_role
        FOREIGN KEY (RoleID)
        REFERENCES roles(RoleID),

    UNIQUE(UserID, RoleID)
);

-- =========================================================
-- ROLE PERMISSIONS
-- =========================================================

CREATE TABLE role_permissions (
    RolePermissionID INT AUTO_INCREMENT PRIMARY KEY,

    RoleID INT NOT NULL,
    ModuleID INT NOT NULL,
    PermissionID INT NOT NULL,

    CONSTRAINT fk_rp_role
        FOREIGN KEY (RoleID)
        REFERENCES roles(RoleID),

    CONSTRAINT fk_rp_module
        FOREIGN KEY (ModuleID)
        REFERENCES modules(ModuleID),

    CONSTRAINT fk_rp_permission
        FOREIGN KEY (PermissionID)
        REFERENCES permissions(PermissionID),

    UNIQUE(RoleID, ModuleID, PermissionID)
);

-- =========================================================
-- MEDICAL PROFESSIONALS
-- =========================================================

CREATE TABLE medical_professionals (
    MedProfID INT AUTO_INCREMENT PRIMARY KEY,

    UserID INT NOT NULL UNIQUE,

    Profession ENUM(
        'Doctor',
        'Dentist',
        'Nurse'
    ) NOT NULL,

    Unit VARCHAR(100),

    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_medprof_user
        FOREIGN KEY (UserID)
        REFERENCES users(UserID)
);

-- =========================================================
-- MEDICAL PROFESSIONAL AVAILABILITY
-- =========================================================

CREATE TABLE medical_professional_availability (
    AvailabilityID INT AUTO_INCREMENT PRIMARY KEY,

    MedProfID INT NOT NULL,

    AvailableDate DATE NOT NULL,

    StartTime TIME NOT NULL,
    EndTime TIME NOT NULL,

    SlotDurationMinutes INT DEFAULT 30,

    AvailabilityStatus ENUM(
        'Available',
        'Unavailable',
        'Cancelled'
    ) DEFAULT 'Available',

    Notes VARCHAR(255),

    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_availability_medprof
        FOREIGN KEY (MedProfID)
        REFERENCES medical_professionals(MedProfID)
);

-- =========================================================
-- BOOKINGS / SCHEDULES
-- =========================================================

CREATE TABLE bookings (
    BookingID INT AUTO_INCREMENT PRIMARY KEY,

    SchoolPersonID INT NOT NULL,

    MedProfID INT,

    AvailabilityID INT,

    BookingType ENUM(
        'Appointment',
        'Walk-In'
    ) NOT NULL DEFAULT 'Appointment',

    ServiceType VARCHAR(100),

    AppointmentDate DATE,

    AppointmentStart TIME,
    AppointmentEnd TIME,

    ReasonForVisit VARCHAR(255),

    BookingStatus ENUM(
        'Pending',
        'Approved',
        'Completed',
        'Cancelled'
    ) DEFAULT 'Pending',

    RequestDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_booking_person
        FOREIGN KEY (SchoolPersonID)
        REFERENCES school_people(SchoolPersonID),

    CONSTRAINT fk_booking_medprof
        FOREIGN KEY (MedProfID)
        REFERENCES medical_professionals(MedProfID),

    CONSTRAINT fk_booking_availability
        FOREIGN KEY (AvailabilityID)
        REFERENCES medical_professional_availability(AvailabilityID)
);

-- =========================================================
-- CLINIC TRANSACTIONS
-- =========================================================

CREATE TABLE clinic_transactions (
    ClinicTransactionID INT AUTO_INCREMENT PRIMARY KEY,

    BookingID INT,

    SchoolPersonID INT NOT NULL,

    MedProfID INT,

    VisitDate DATE NOT NULL,

    Complaint VARCHAR(255),

    ServiceType VARCHAR(150),

    ConsultationStatus ENUM(
        'Waiting',
        'Consulting',
        'Completed',
        'Cancelled'
    ) DEFAULT 'Waiting',

    Notes TEXT,

    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_transaction_booking
        FOREIGN KEY (BookingID)
        REFERENCES bookings(BookingID),

    CONSTRAINT fk_transaction_person
        FOREIGN KEY (SchoolPersonID)
        REFERENCES school_people(SchoolPersonID),

    CONSTRAINT fk_transaction_medprof
        FOREIGN KEY (MedProfID)
        REFERENCES medical_professionals(MedProfID)
);

-- =========================================================
-- PHYSICAL EXAMINATIONS
-- =========================================================

CREATE TABLE physical_examinations (
    PhysicalExamID INT AUTO_INCREMENT PRIMARY KEY,

    ClinicTransactionID INT NOT NULL,

    ExamDate DATE,

    Height DECIMAL(5,2),
    Weight DECIMAL(5,2),

    BloodPressure VARCHAR(20),
    PulseRate INT,

    Ears VARCHAR(100),
    EyesPupil VARCHAR(100),
    Heart VARCHAR(100),
    Nose VARCHAR(100),
    Thorax VARCHAR(100),
    Abdomen VARCHAR(100),
    Lungs VARCHAR(100),
    Skin VARCHAR(100),
    Extremities VARCHAR(100),
    Deformities VARCHAR(100),

    CardioClearance BOOLEAN,

    CONSTRAINT fk_physical_transaction
        FOREIGN KEY (ClinicTransactionID)
        REFERENCES clinic_transactions(ClinicTransactionID)
);

-- =========================================================
-- DENTAL TRANSACTIONS
-- KEEPING ORIGINAL STRUCTURE
-- =========================================================

CREATE TABLE dental_transactions (
    DentalTransactionID INT AUTO_INCREMENT PRIMARY KEY,

    ClinicTransactionID INT NOT NULL,

    CurrentAge INT,
    TeethCount INT,

    OperationLower VARCHAR(255),
    ConditionLower VARCHAR(255),

    OperationUpper VARCHAR(255),
    ConditionUpper VARCHAR(255),

    PresenceOfCalculus BOOLEAN,
    InflammationOfGingiva BOOLEAN,
    PeriodontalPocket BOOLEAN,
    DentofacialAnomaly BOOLEAN,
    Caries BOOLEAN,
    ForExtraction BOOLEAN,
    RootFragment BOOLEAN,

    LostDueToCaries INT,
    FilledOrRestored INT,

    FluorideTherapy BOOLEAN,

    Diagnosis VARCHAR(255),

    CONSTRAINT fk_dental_transaction
        FOREIGN KEY (ClinicTransactionID)
        REFERENCES clinic_transactions(ClinicTransactionID)
);

-- =========================================================
-- MEDICAL CERTIFICATES
-- =========================================================

CREATE TABLE medical_certificates (
    MedicalCertificateID INT AUTO_INCREMENT PRIMARY KEY,

    ClinicTransactionID INT NOT NULL,

    IssuedByMedProfID INT NOT NULL,

    CertificateType VARCHAR(100),

    Remarks VARCHAR(255),

    ValidUntil DATE,

    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_medcert_transaction
        FOREIGN KEY (ClinicTransactionID)
        REFERENCES clinic_transactions(ClinicTransactionID),

    CONSTRAINT fk_medcert_medprof
        FOREIGN KEY (IssuedByMedProfID)
        REFERENCES medical_professionals(MedProfID)
);

-- =========================================================
-- DISEASES
-- =========================================================

CREATE TABLE diseases (
    DiseaseID INT AUTO_INCREMENT PRIMARY KEY,

    DiseaseName VARCHAR(150) NOT NULL UNIQUE
);

-- =========================================================
-- USER DISEASES
-- =========================================================

CREATE TABLE user_diseases (
    UserDiseaseID INT AUTO_INCREMENT PRIMARY KEY,

    SchoolPersonID INT NOT NULL,

    DiseaseID INT NOT NULL,

    Notes VARCHAR(255),

    CONSTRAINT fk_ud_person
        FOREIGN KEY (SchoolPersonID)
        REFERENCES school_people(SchoolPersonID),

    CONSTRAINT fk_ud_disease
        FOREIGN KEY (DiseaseID)
        REFERENCES diseases(DiseaseID)
);

-- =========================================================
-- EMERGENCIES
-- =========================================================

CREATE TABLE emergencies (
    EmergencyID INT AUTO_INCREMENT PRIMARY KEY,

    SchoolPersonID INT NOT NULL,

    IncidentDate DATE,
    IncidentTime TIME,

    IncidentLocation VARCHAR(255),

    BP VARCHAR(20),
    RR INT,
    HR INT,

    Temperature DECIMAL(5,2),

    TreatmentGiven VARCHAR(255),

    AmbulanceNo VARCHAR(20),

    TimeDispatched TIME,
    TimeArrived TIME,

    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_emergency_person
        FOREIGN KEY (SchoolPersonID)
        REFERENCES school_people(SchoolPersonID)
);

-- =========================================================
-- MEDICINES
-- =========================================================

CREATE TABLE medicines (
    MedicineID INT AUTO_INCREMENT PRIMARY KEY,

    MedicineName VARCHAR(150) NOT NULL,

    GenericName VARCHAR(150),

    MedicineType VARCHAR(100),

    Dosage VARCHAR(100),

    Unit VARCHAR(50),

    Description VARCHAR(255),

    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =========================================================
-- MEDICINE INVENTORY
-- =========================================================

CREATE TABLE medicine_inventory (
    InventoryID INT AUTO_INCREMENT PRIMARY KEY,

    MedicineID INT NOT NULL,

    BatchNumber VARCHAR(100),

    Quantity INT NOT NULL DEFAULT 0,

    ExpiryDate DATE,

    DateReceived DATE,

    ReorderLevel INT DEFAULT 10,

    Status ENUM(
        'Available',
        'Low Stock',
        'Out Of Stock',
        'Expired'
    ) DEFAULT 'Available',

    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_inventory_medicine
        FOREIGN KEY (MedicineID)
        REFERENCES medicines(MedicineID)
);

-- =========================================================
-- MEDICINE DISPENSING
-- =========================================================

CREATE TABLE medicine_dispensing (
    DispensingID INT AUTO_INCREMENT PRIMARY KEY,

    ClinicTransactionID INT NOT NULL,

    InventoryID INT NOT NULL,

    QuantityDispensed INT NOT NULL,

    Instructions VARCHAR(255),

    DispensedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_dispense_transaction
        FOREIGN KEY (ClinicTransactionID)
        REFERENCES clinic_transactions(ClinicTransactionID),

    CONSTRAINT fk_dispense_inventory
        FOREIGN KEY (InventoryID)
        REFERENCES medicine_inventory(InventoryID)
);

-- =========================================================
-- MEDICINE INVENTORY LOGS
-- =========================================================

CREATE TABLE medicine_inventory_logs (
    LogID INT AUTO_INCREMENT PRIMARY KEY,

    InventoryID INT NOT NULL,

    ActionType ENUM(
        'Stock In',
        'Dispensed',
        'Adjusted',
        'Expired'
    ) NOT NULL,

    QuantityChanged INT NOT NULL,

    PerformedByUserID INT,

    Notes VARCHAR(255),

    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_inventory_log_inventory
        FOREIGN KEY (InventoryID)
        REFERENCES medicine_inventory(InventoryID),

    CONSTRAINT fk_inventory_log_user
        FOREIGN KEY (PerformedByUserID)
        REFERENCES users(UserID)
);

-- =========================================================
-- REPORTS
-- =========================================================

CREATE TABLE reports (
    ReportID INT AUTO_INCREMENT PRIMARY KEY,

    GeneratedByUserID INT NOT NULL,

    ReportType VARCHAR(100),

    ReportDescription VARCHAR(255),

    GeneratedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_report_user
        FOREIGN KEY (GeneratedByUserID)
        REFERENCES users(UserID)
);

-- =========================================================
-- AUDIT LOGS
-- =========================================================

CREATE TABLE audit_logs (
    AuditLogID INT AUTO_INCREMENT PRIMARY KEY,

    UserID INT NOT NULL,

    Action VARCHAR(255) NOT NULL,

    ModuleName VARCHAR(100),

    TableAffected VARCHAR(100),

    RecordID INT,

    ActionTimestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_audit_user
        FOREIGN KEY (UserID)
        REFERENCES users(UserID)
);

-- =========================================================
-- ROLES SEED
-- =========================================================

INSERT INTO roles (RoleName, Description) VALUES
('Student', 'Default student role'),
('Faculty', 'Default faculty role'),
('Staff', 'Default staff role'),
('Doctor', 'Medical doctor role'),
('Dentist', 'Medical dentist role'),
('Nurse', 'Medical nurse role'),
('Admin', 'Administrative role'),
('Super Admin', 'Full system access');

-- =========================================================
-- MODULES SEED
-- =========================================================

INSERT INTO modules (ModuleName, Description) VALUES
('Consultation', 'Consultation management'),
('Records', 'Clinic records'),
('Reports', 'Generated reports'),
('Medicine', 'Medicine inventory'),
('Schedule', 'Appointment scheduling'),
('Admin Panel', 'Administrative panel'),
('RBAC Management', 'Role permissions management'),
('User Management', 'Manage users'),
('Audit Logs', 'System audit logs');

-- =========================================================
-- PERMISSIONS SEED
-- =========================================================

INSERT INTO permissions (PermissionName, Description) VALUES
('access', 'Allow module access'),
('view', 'Can view records'),
('create', 'Can create records'),
('edit', 'Can edit records'),
('delete', 'Can delete records'),
('approve', 'Can approve requests'),
('manage', 'Full management permissions');