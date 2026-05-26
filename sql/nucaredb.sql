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


CREATE TABLE consultation_attachments (
    AttachmentID        INT NOT NULL AUTO_INCREMENT,
    ClinicTransactionID INT NOT NULL,
    UploadedBy          INT NULL,
    FileName            VARCHAR(255) NOT NULL,
    StoredName          VARCHAR(255) NOT NULL,
    FilePath            VARCHAR(500) NOT NULL,

    FileType            ENUM('image/jpeg','image/png','application/pdf') NOT NULL,
    FileSizeBytes       INT NOT NULL,

    AttachmentCategory  ENUM(
        'Lab Result',
        'Medical Certificate',
        'Dental',
        'X-Ray',
        'Prescription',
        'Other'
    ) NOT NULL DEFAULT 'Other',

    Notes               VARCHAR(500) NULL,
    CreatedAt           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (AttachmentID),

    CONSTRAINT fk_attach_transaction
        FOREIGN KEY (ClinicTransactionID)
        REFERENCES clinic_transactions(ClinicTransactionID)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

ALTER TABLE physical_examinations
    MODIFY COLUMN CardioClearance ENUM('Fit', 'Unfit', 'Pending') NULL DEFAULT NULL;


    -- ══════════════════════════════════════════════════════════════
-- dental_transactions — stripped to pure FK/junction table
-- ══════════════════════════════════════════════════════════════

-- Option A: Drop and recreate (clean slate)
DROP TABLE IF EXISTS dental_transactions;

CREATE TABLE dental_transactions (
    DentalTransactionID   INT  NOT NULL AUTO_INCREMENT,
    ClinicTransactionID   INT  NOT NULL,
    InventoryID           INT  NULL,          -- FK → medicine_inventory (medicine dispensed)
    AttachmentID          INT  NULL,          -- FK → consultation_attachments (scanned form)
    AttachmentCategory    VARCHAR(100) NULL,  -- mirrors consultation_attachments.AttachmentCategory

    PRIMARY KEY (DentalTransactionID),
    UNIQUE KEY uq_dental_ctid (ClinicTransactionID),   -- one dental record per visit

    CONSTRAINT fk_dental_ct
        FOREIGN KEY (ClinicTransactionID) REFERENCES clinic_transactions (ClinicTransactionID)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT fk_dental_inv
        FOREIGN KEY (InventoryID) REFERENCES medicine_inventory (InventoryID)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT fk_dental_att
        FOREIGN KEY (AttachmentID) REFERENCES consultation_attachments (AttachmentID)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- ──────────────────────────────────────────────────────────────
-- Option B: ALTER existing table (keeps data, removes old cols)
-- Run this block instead of the DROP/CREATE above if you want
-- to preserve any existing rows.
-- ──────────────────────────────────────────────────────────────
/*
ALTER TABLE dental_transactions
    -- drop old data columns
    DROP COLUMN IF EXISTS CurrentAge,
    DROP COLUMN IF EXISTS TeethCount,
    DROP COLUMN IF EXISTS OperationLower,
    DROP COLUMN IF EXISTS ConditionLower,
    DROP COLUMN IF EXISTS OperationUpper,
    DROP COLUMN IF EXISTS ConditionUpper,
    DROP COLUMN IF EXISTS PresenceOfCalculus,
    DROP COLUMN IF EXISTS InflammationOfGingiva,
    DROP COLUMN IF EXISTS PeriodontalPocket,
    DROP COLUMN IF EXISTS DentofacialAnomaly,
    DROP COLUMN IF EXISTS Caries,
    DROP COLUMN IF EXISTS ForExtraction,
    DROP COLUMN IF EXISTS RootFragment,
    DROP COLUMN IF EXISTS LostDueToCaries,
    DROP COLUMN IF EXISTS FilledOrRestored,
    DROP COLUMN IF EXISTS FluorideTherapy,
    DROP COLUMN IF EXISTS Diagnosis,

    -- add the three new FK columns
    ADD COLUMN InventoryID         INT         NULL AFTER ClinicTransactionID,
    ADD COLUMN AttachmentID        INT         NULL AFTER InventoryID,
    ADD COLUMN AttachmentCategory  VARCHAR(100) NULL AFTER AttachmentID,

    -- unique constraint: one dental record per clinic visit
    ADD UNIQUE KEY uq_dental_ctid (ClinicTransactionID),

    ADD CONSTRAINT fk_dental_inv
        FOREIGN KEY (InventoryID) REFERENCES medicine_inventory (InventoryID)
        ON DELETE SET NULL ON UPDATE CASCADE,

    ADD CONSTRAINT fk_dental_att
        FOREIGN KEY (AttachmentID) REFERENCES consultation_attachments (AttachmentID)
        ON DELETE SET NULL ON UPDATE CASCADE;
*/

-- ──────────────────────────────────────────────────────────────
-- If consultation_attachments.AttachmentCategory is an ENUM,
-- add 'Dental Form' to it:
-- ──────────────────────────────────────────────────────────────
/*
ALTER TABLE consultation_attachments
    MODIFY COLUMN AttachmentCategory
        ENUM('Lab Result','Medical Certificate','Referral','X-Ray','Prescription','Dental Form','Other')
        NOT NULL DEFAULT 'Other';
*/

ALTER TABLE medical_certificates
DROP COLUMN remarks;


-- ══════════════════════════════════════════════════════════════════════════
-- NUcare — Attachment Category Normalization
-- Replaces ENUM string in consultation_attachments.AttachmentCategory
-- with a proper FK to a lookup table (3NF, scalable)
-- ══════════════════════════════════════════════════════════════════════════

-- ── STEP 1: Create the lookup table ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS attachment_document_types (
    DocumentTypeID   SMALLINT     NOT NULL AUTO_INCREMENT,
    Category         VARCHAR(60)  NOT NULL,   -- broad group (for filtering/reporting)
    DocumentType     VARCHAR(100) NOT NULL,   -- specific label shown in UI
    IsActive         TINYINT(1)   NOT NULL DEFAULT 1,
    SortOrder        SMALLINT     NOT NULL DEFAULT 0,

    PRIMARY KEY (DocumentTypeID),
    UNIQUE KEY uq_doc_type (Category, DocumentType)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;


-- ── STEP 2: Seed with clean, normalized document types ───────────────────
--
-- Design rules applied:
--   • Category   = broad group used for FILTERING and REPORTING (≤ 8 values)
--   • DocumentType = specific label shown to staff in the UI dropdown
--   • No trailing spaces, consistent Title Case
--   • "Medical Certificate" is a Category; specific MC variants are DocumentTypes under it
--   • School-specific forms (Dress Down, Absence, Permit to Leave) live under "School Form"
--   • "Other" always last as the catch-all
-- ─────────────────────────────────────────────────────────────────────────

INSERT INTO attachment_document_types (Category, DocumentType, SortOrder) VALUES

-- Laboratory
('Laboratory',           'Lab Result',                        10),
('Laboratory',           'CBC (Complete Blood Count)',         11),
('Laboratory',           'Urinalysis',                        12),
('Laboratory',           'Blood Chemistry',                   13),
('Laboratory',           'Drug Test Result',                  14),

-- Imaging
('Imaging',              'X-Ray',                             20),
('Imaging',              'Ultrasound',                        21),
('Imaging',              'ECG / EKG',                         22),

-- Medical Certificate
('Medical Certificate',  'Medical Certificate',               30),
('Medical Certificate',  'Medical Certificate – Dress Down',  31),
('Medical Certificate',  'Medical Certificate – Absence',     32),
('Medical Certificate',  'Fit-to-Return Clearance',          33),
('Medical Certificate',  'Medical Clearance',                 34),

-- School Form
('School Form',          'Health Status Declaration Form',    40),
('School Form',          'Permit to Leave Form',              41),
('School Form',          'Physical Examination Form',         42),
('School Form',          'Immunization Record',               43),

-- Dental
('Dental',               'Dental Examination Form',           50),
('Dental',               'Dental Treatment Record',           51),

-- Prescription & Referral
('Prescription',         'Prescription',                      60),
('Referral',             'Referral Letter',                   70),
('Referral',             'Referral – Specialist',             71),

-- Other
('Other',                'Other',                             99);


-- ── STEP 3: Add FK column to consultation_attachments ────────────────────
--
-- We ADD a new nullable FK column alongside the old string column.
-- Once the application is fully migrated, you can drop AttachmentCategory.
-- Running both in parallel avoids breaking the live system during rollout.
-- ─────────────────────────────────────────────────────────────────────────

ALTER TABLE consultation_attachments
    ADD COLUMN DocumentTypeID SMALLINT NULL
        AFTER AttachmentCategory,
    ADD CONSTRAINT fk_ca_doctype
        FOREIGN KEY (DocumentTypeID)
        REFERENCES attachment_document_types (DocumentTypeID)
        ON DELETE SET NULL ON UPDATE CASCADE;


-- ── STEP 4: Backfill DocumentTypeID from old string column ───────────────
--
-- Maps old free-text values → new DocumentTypeID.
-- Safe to run multiple times (WHERE DocumentTypeID IS NULL guard).
-- ─────────────────────────────────────────────────────────────────────────

UPDATE consultation_attachments ca
JOIN attachment_document_types adt
    ON TRIM(ca.AttachmentCategory) = adt.DocumentType
SET ca.DocumentTypeID = adt.DocumentTypeID
WHERE ca.DocumentTypeID IS NULL;

-- Catch old values that don't match exactly → fallback to 'Other'
UPDATE consultation_attachments
SET DocumentTypeID = (
    SELECT DocumentTypeID FROM attachment_document_types
    WHERE DocumentType = 'Other' LIMIT 1
)
WHERE DocumentTypeID IS NULL
  AND AttachmentCategory IS NOT NULL
  AND AttachmentCategory != '';


-- ── STEP 5 (FUTURE — run after app is fully on DocumentTypeID) ───────────
-- Once all code reads DocumentTypeID instead of AttachmentCategory string:
--
--   ALTER TABLE consultation_attachments
--       DROP FOREIGN KEY fk_ca_doctype,   -- drop first so we can re-add after modify
--       MODIFY COLUMN DocumentTypeID SMALLINT NOT NULL,
--       DROP COLUMN AttachmentCategory,
--       ADD CONSTRAINT fk_ca_doctype
--           FOREIGN KEY (DocumentTypeID)
--           REFERENCES attachment_document_types (DocumentTypeID)
--           ON DELETE RESTRICT ON UPDATE CASCADE;
-- ─────────────────────────────────────────────────────────────────────────


-- ── REFERENCE: query the lookup table (use in your PHP dropdowns) ─────────
--
--   SELECT DocumentTypeID, Category, DocumentType
--   FROM attachment_document_types
--   WHERE IsActive = 1
--   ORDER BY SortOrder ASC;
--
-- Returns rows grouped naturally by SortOrder so PHP can build
-- <optgroup label="Category"> sections with a single pass.
-- ─────────────────────────────────────────────────────────────────────────


ALTER TABLE medical_certificates
    ADD COLUMN AttachmentID INT NULL AFTER ClinicTransactionID,
    ADD CONSTRAINT fk_mc_attachment
        FOREIGN KEY (AttachmentID)
        REFERENCES consultation_attachments (AttachmentID)
        ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE medical_certificates
    MODIFY COLUMN IssuedByMedProfID INT NULL;