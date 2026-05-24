-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 24, 2026 at 07:40 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nucaredb`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `AuditLogID` int NOT NULL,
  `UserID` int NOT NULL,
  `Action` varchar(255) NOT NULL,
  `ModuleName` varchar(100) DEFAULT NULL,
  `TableAffected` varchar(100) DEFAULT NULL,
  `RecordID` int DEFAULT NULL,
  `ActionTimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `BookingID` int NOT NULL,
  `SchoolPersonID` int NOT NULL,
  `MedProfID` int DEFAULT NULL,
  `AvailabilityID` int DEFAULT NULL,
  `BookingType` enum('Appointment','Walk-In') NOT NULL DEFAULT 'Appointment',
  `ServiceType` varchar(100) DEFAULT NULL,
  `AppointmentDate` date DEFAULT NULL,
  `AppointmentStart` time DEFAULT NULL,
  `AppointmentEnd` time DEFAULT NULL,
  `ReasonForVisit` varchar(255) DEFAULT NULL,
  `BookingStatus` enum('Pending','Approved','Completed','Cancelled') DEFAULT 'Pending',
  `RequestDate` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinic_transactions`
--

CREATE TABLE `clinic_transactions` (
  `ClinicTransactionID` int NOT NULL,
  `BookingID` int DEFAULT NULL,
  `SchoolPersonID` int NOT NULL,
  `MedProfID` int DEFAULT NULL,
  `VisitDate` date NOT NULL,
  `Complaint` varchar(255) DEFAULT NULL,
  `ServiceType` varchar(150) DEFAULT NULL,
  `ConsultationStatus` enum('Waiting','Consulting','Completed','Cancelled') DEFAULT 'Waiting',
  `Notes` text,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dental_transactions`
--

CREATE TABLE `dental_transactions` (
  `DentalTransactionID` int NOT NULL,
  `ClinicTransactionID` int NOT NULL,
  `CurrentAge` int DEFAULT NULL,
  `TeethCount` int DEFAULT NULL,
  `OperationLower` varchar(255) DEFAULT NULL,
  `ConditionLower` varchar(255) DEFAULT NULL,
  `OperationUpper` varchar(255) DEFAULT NULL,
  `ConditionUpper` varchar(255) DEFAULT NULL,
  `PresenceOfCalculus` tinyint(1) DEFAULT NULL,
  `InflammationOfGingiva` tinyint(1) DEFAULT NULL,
  `PeriodontalPocket` tinyint(1) DEFAULT NULL,
  `DentofacialAnomaly` tinyint(1) DEFAULT NULL,
  `Caries` tinyint(1) DEFAULT NULL,
  `ForExtraction` tinyint(1) DEFAULT NULL,
  `RootFragment` tinyint(1) DEFAULT NULL,
  `LostDueToCaries` int DEFAULT NULL,
  `FilledOrRestored` int DEFAULT NULL,
  `FluorideTherapy` tinyint(1) DEFAULT NULL,
  `Diagnosis` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `diseases`
--

CREATE TABLE `diseases` (
  `DiseaseID` int NOT NULL,
  `DiseaseName` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emergencies`
--

CREATE TABLE `emergencies` (
  `EmergencyID` int NOT NULL,
  `SchoolPersonID` int NOT NULL,
  `IncidentDate` date DEFAULT NULL,
  `IncidentTime` time DEFAULT NULL,
  `IncidentLocation` varchar(255) DEFAULT NULL,
  `BP` varchar(20) DEFAULT NULL,
  `RR` int DEFAULT NULL,
  `HR` int DEFAULT NULL,
  `Temperature` decimal(5,2) DEFAULT NULL,
  `TreatmentGiven` varchar(255) DEFAULT NULL,
  `AmbulanceNo` varchar(20) DEFAULT NULL,
  `TimeDispatched` time DEFAULT NULL,
  `TimeArrived` time DEFAULT NULL,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_assignments`
--

CREATE TABLE `employee_assignments` (
  `AssignmentID` int NOT NULL,
  `SchoolPersonID` int NOT NULL,
  `Department` varchar(150) DEFAULT NULL,
  `PositionTitle` varchar(150) DEFAULT NULL,
  `EmploymentStatus` enum('Employed','Resigned','Inactive') NOT NULL DEFAULT 'Employed',
  `StartDate` date DEFAULT NULL,
  `EndDate` date DEFAULT NULL,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medical_certificates`
--

CREATE TABLE `medical_certificates` (
  `MedicalCertificateID` int NOT NULL,
  `ClinicTransactionID` int NOT NULL,
  `IssuedByMedProfID` int NOT NULL,
  `CertificateType` varchar(100) DEFAULT NULL,
  `Remarks` varchar(255) DEFAULT NULL,
  `ValidUntil` date DEFAULT NULL,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medical_professionals`
--

CREATE TABLE `medical_professionals` (
  `MedProfID` int NOT NULL,
  `UserID` int NOT NULL,
  `Profession` enum('Doctor','Dentist','Nurse') NOT NULL,
  `Unit` varchar(100) DEFAULT NULL,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medical_professional_availability`
--

CREATE TABLE `medical_professional_availability` (
  `AvailabilityID` int NOT NULL,
  `MedProfID` int NOT NULL,
  `AvailableDate` date NOT NULL,
  `StartTime` time NOT NULL,
  `EndTime` time NOT NULL,
  `SlotDurationMinutes` int DEFAULT '30',
  `AvailabilityStatus` enum('Available','Unavailable','Cancelled') DEFAULT 'Available',
  `Notes` varchar(255) DEFAULT NULL,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicines`
--

CREATE TABLE `medicines` (
  `MedicineID` int NOT NULL,
  `MedicineName` varchar(150) NOT NULL,
  `GenericName` varchar(150) DEFAULT NULL,
  `MedicineType` varchar(100) DEFAULT NULL,
  `Dosage` varchar(100) DEFAULT NULL,
  `Unit` varchar(50) DEFAULT NULL,
  `Description` varchar(255) DEFAULT NULL,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicine_dispensing`
--

CREATE TABLE `medicine_dispensing` (
  `DispensingID` int NOT NULL,
  `ClinicTransactionID` int NOT NULL,
  `InventoryID` int NOT NULL,
  `QuantityDispensed` int NOT NULL,
  `Instructions` varchar(255) DEFAULT NULL,
  `DispensedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicine_inventory`
--

CREATE TABLE `medicine_inventory` (
  `InventoryID` int NOT NULL,
  `MedicineID` int NOT NULL,
  `BatchNumber` varchar(100) DEFAULT NULL,
  `Quantity` int NOT NULL DEFAULT '0',
  `ExpiryDate` date DEFAULT NULL,
  `DateReceived` date DEFAULT NULL,
  `ReorderLevel` int DEFAULT '10',
  `Status` enum('Available','Low Stock','Out Of Stock','Expired') DEFAULT 'Available',
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicine_inventory_logs`
--

CREATE TABLE `medicine_inventory_logs` (
  `LogID` int NOT NULL,
  `InventoryID` int NOT NULL,
  `ActionType` enum('Stock In','Dispensed','Adjusted','Expired') NOT NULL,
  `QuantityChanged` int NOT NULL,
  `PerformedByUserID` int DEFAULT NULL,
  `Notes` varchar(255) DEFAULT NULL,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `ModuleID` int NOT NULL,
  `ModuleName` varchar(100) NOT NULL,
  `Description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `modules`
--

INSERT INTO `modules` (`ModuleID`, `ModuleName`, `Description`) VALUES
(1, 'Consultation', 'Consultation management'),
(2, 'Records', 'Clinic records'),
(3, 'Reports', 'Generated reports'),
(4, 'Medicine', 'Medicine inventory'),
(5, 'Schedule', 'Appointment scheduling'),
(6, 'Admin Panel', 'Administrative panel'),
(7, 'RBAC Management', 'Role permissions management'),
(8, 'User Management', 'Manage users'),
(9, 'Audit Logs', 'System audit logs');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `PermissionID` int NOT NULL,
  `PermissionName` varchar(100) NOT NULL,
  `Description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`PermissionID`, `PermissionName`, `Description`) VALUES
(1, 'access', 'Allow module access'),
(2, 'view', 'Can view records'),
(3, 'create', 'Can create records'),
(4, 'edit', 'Can edit records'),
(5, 'delete', 'Can delete records'),
(6, 'approve', 'Can approve requests'),
(7, 'manage', 'Full management permissions');

-- --------------------------------------------------------

--
-- Table structure for table `physical_examinations`
--

CREATE TABLE `physical_examinations` (
  `PhysicalExamID` int NOT NULL,
  `ClinicTransactionID` int NOT NULL,
  `ExamDate` date DEFAULT NULL,
  `Height` decimal(5,2) DEFAULT NULL,
  `Weight` decimal(5,2) DEFAULT NULL,
  `Temperature` decimal(5,2) DEFAULT NULL,
  `BloodPressure` varchar(20) DEFAULT NULL,
  `PulseRate` int DEFAULT NULL,
  `Ears` varchar(100) DEFAULT NULL,
  `EyesPupil` varchar(100) DEFAULT NULL,
  `Heart` varchar(100) DEFAULT NULL,
  `Nose` varchar(100) DEFAULT NULL,
  `Thorax` varchar(100) DEFAULT NULL,
  `Abdomen` varchar(100) DEFAULT NULL,
  `Lungs` varchar(100) DEFAULT NULL,
  `Skin` varchar(100) DEFAULT NULL,
  `Extremities` varchar(100) DEFAULT NULL,
  `Deformities` varchar(100) DEFAULT NULL,
  `CardioClearance` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `ProgramID` int NOT NULL,
  `ProgramName` varchar(150) NOT NULL,
  `Department` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `ReportID` int NOT NULL,
  `GeneratedByUserID` int NOT NULL,
  `ReportType` varchar(100) DEFAULT NULL,
  `ReportDescription` varchar(255) DEFAULT NULL,
  `GeneratedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `RoleID` int NOT NULL,
  `RoleName` varchar(100) NOT NULL,
  `Description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`RoleID`, `RoleName`, `Description`) VALUES
(1, 'Student', 'Default student role'),
(2, 'Faculty', 'Default faculty role'),
(3, 'Staff', 'Default staff role'),
(4, 'Doctor', 'Medical doctor role'),
(5, 'Dentist', 'Medical dentist role'),
(6, 'Nurse', 'Medical nurse role'),
(7, 'Admin', 'Administrative role'),
(8, 'Super Admin', 'Full system access');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `RolePermissionID` int NOT NULL,
  `RoleID` int NOT NULL,
  `ModuleID` int NOT NULL,
  `PermissionID` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `school_people`
--

CREATE TABLE `school_people` (
  `SchoolPersonID` int NOT NULL,
  `SchoolID` varchar(50) NOT NULL,
  `FirstName` varchar(100) NOT NULL,
  `LastName` varchar(100) NOT NULL,
  `MiddleName` varchar(100) DEFAULT NULL,
  `Email` varchar(255) DEFAULT NULL,
  `PersonType` enum('Student','Faculty','Staff') NOT NULL,
  `Sex` enum('Male','Female') NOT NULL,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_enrollments`
--

CREATE TABLE `student_enrollments` (
  `EnrollmentID` int NOT NULL,
  `SchoolPersonID` int NOT NULL,
  `ProgramID` int DEFAULT NULL,
  `AcademicYear` varchar(20) NOT NULL,
  `Semester` varchar(20) NOT NULL,
  `EnrollmentStatus` enum('Enrolled','Not Enrolled','Dropped','Graduated') NOT NULL DEFAULT 'Enrolled',
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` int NOT NULL,
  `SchoolPersonID` int NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `ResetToken` varchar(255) DEFAULT NULL,
  `TokenExpiry` datetime DEFAULT NULL,
  `IsActive` tinyint(1) DEFAULT '1',
  `LastLogin` datetime DEFAULT NULL,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_diseases`
--

CREATE TABLE `user_diseases` (
  `UserDiseaseID` int NOT NULL,
  `SchoolPersonID` int NOT NULL,
  `DiseaseID` int NOT NULL,
  `Notes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `UserRoleID` int NOT NULL,
  `UserID` int NOT NULL,
  `RoleID` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`AuditLogID`),
  ADD KEY `fk_audit_user` (`UserID`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`BookingID`),
  ADD KEY `fk_booking_person` (`SchoolPersonID`),
  ADD KEY `fk_booking_medprof` (`MedProfID`),
  ADD KEY `fk_booking_availability` (`AvailabilityID`);

--
-- Indexes for table `clinic_transactions`
--
ALTER TABLE `clinic_transactions`
  ADD PRIMARY KEY (`ClinicTransactionID`),
  ADD KEY `fk_transaction_booking` (`BookingID`),
  ADD KEY `fk_transaction_person` (`SchoolPersonID`),
  ADD KEY `fk_transaction_medprof` (`MedProfID`);

--
-- Indexes for table `dental_transactions`
--
ALTER TABLE `dental_transactions`
  ADD PRIMARY KEY (`DentalTransactionID`),
  ADD KEY `fk_dental_transaction` (`ClinicTransactionID`);

--
-- Indexes for table `diseases`
--
ALTER TABLE `diseases`
  ADD PRIMARY KEY (`DiseaseID`),
  ADD UNIQUE KEY `DiseaseName` (`DiseaseName`);

--
-- Indexes for table `emergencies`
--
ALTER TABLE `emergencies`
  ADD PRIMARY KEY (`EmergencyID`),
  ADD KEY `fk_emergency_person` (`SchoolPersonID`);

--
-- Indexes for table `employee_assignments`
--
ALTER TABLE `employee_assignments`
  ADD PRIMARY KEY (`AssignmentID`),
  ADD KEY `fk_employee_person` (`SchoolPersonID`);

--
-- Indexes for table `medical_certificates`
--
ALTER TABLE `medical_certificates`
  ADD PRIMARY KEY (`MedicalCertificateID`),
  ADD KEY `fk_medcert_transaction` (`ClinicTransactionID`),
  ADD KEY `fk_medcert_medprof` (`IssuedByMedProfID`);

--
-- Indexes for table `medical_professionals`
--
ALTER TABLE `medical_professionals`
  ADD PRIMARY KEY (`MedProfID`),
  ADD UNIQUE KEY `UserID` (`UserID`);

--
-- Indexes for table `medical_professional_availability`
--
ALTER TABLE `medical_professional_availability`
  ADD PRIMARY KEY (`AvailabilityID`),
  ADD KEY `fk_availability_medprof` (`MedProfID`);

--
-- Indexes for table `medicines`
--
ALTER TABLE `medicines`
  ADD PRIMARY KEY (`MedicineID`);

--
-- Indexes for table `medicine_dispensing`
--
ALTER TABLE `medicine_dispensing`
  ADD PRIMARY KEY (`DispensingID`),
  ADD KEY `fk_dispense_transaction` (`ClinicTransactionID`),
  ADD KEY `fk_dispense_inventory` (`InventoryID`);

--
-- Indexes for table `medicine_inventory`
--
ALTER TABLE `medicine_inventory`
  ADD PRIMARY KEY (`InventoryID`),
  ADD KEY `fk_inventory_medicine` (`MedicineID`);

--
-- Indexes for table `medicine_inventory_logs`
--
ALTER TABLE `medicine_inventory_logs`
  ADD PRIMARY KEY (`LogID`),
  ADD KEY `fk_inventory_log_inventory` (`InventoryID`),
  ADD KEY `fk_inventory_log_user` (`PerformedByUserID`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`ModuleID`),
  ADD UNIQUE KEY `ModuleName` (`ModuleName`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`PermissionID`),
  ADD UNIQUE KEY `PermissionName` (`PermissionName`);

--
-- Indexes for table `physical_examinations`
--
ALTER TABLE `physical_examinations`
  ADD PRIMARY KEY (`PhysicalExamID`),
  ADD KEY `fk_physical_transaction` (`ClinicTransactionID`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`ProgramID`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`ReportID`),
  ADD KEY `fk_report_user` (`GeneratedByUserID`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`RoleID`),
  ADD UNIQUE KEY `RoleName` (`RoleName`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`RolePermissionID`),
  ADD UNIQUE KEY `RoleID` (`RoleID`,`ModuleID`,`PermissionID`),
  ADD KEY `fk_rp_module` (`ModuleID`),
  ADD KEY `fk_rp_permission` (`PermissionID`);

--
-- Indexes for table `school_people`
--
ALTER TABLE `school_people`
  ADD PRIMARY KEY (`SchoolPersonID`),
  ADD UNIQUE KEY `SchoolID` (`SchoolID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Indexes for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  ADD PRIMARY KEY (`EnrollmentID`),
  ADD KEY `fk_enrollment_person` (`SchoolPersonID`),
  ADD KEY `fk_enrollment_program` (`ProgramID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `SchoolPersonID` (`SchoolPersonID`);

--
-- Indexes for table `user_diseases`
--
ALTER TABLE `user_diseases`
  ADD PRIMARY KEY (`UserDiseaseID`),
  ADD KEY `fk_ud_person` (`SchoolPersonID`),
  ADD KEY `fk_ud_disease` (`DiseaseID`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`UserRoleID`),
  ADD UNIQUE KEY `UserID` (`UserID`,`RoleID`),
  ADD KEY `fk_ur_role` (`RoleID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `AuditLogID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `BookingID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clinic_transactions`
--
ALTER TABLE `clinic_transactions`
  MODIFY `ClinicTransactionID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dental_transactions`
--
ALTER TABLE `dental_transactions`
  MODIFY `DentalTransactionID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `diseases`
--
ALTER TABLE `diseases`
  MODIFY `DiseaseID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emergencies`
--
ALTER TABLE `emergencies`
  MODIFY `EmergencyID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_assignments`
--
ALTER TABLE `employee_assignments`
  MODIFY `AssignmentID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medical_certificates`
--
ALTER TABLE `medical_certificates`
  MODIFY `MedicalCertificateID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medical_professionals`
--
ALTER TABLE `medical_professionals`
  MODIFY `MedProfID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medical_professional_availability`
--
ALTER TABLE `medical_professional_availability`
  MODIFY `AvailabilityID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicines`
--
ALTER TABLE `medicines`
  MODIFY `MedicineID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicine_dispensing`
--
ALTER TABLE `medicine_dispensing`
  MODIFY `DispensingID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicine_inventory`
--
ALTER TABLE `medicine_inventory`
  MODIFY `InventoryID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicine_inventory_logs`
--
ALTER TABLE `medicine_inventory_logs`
  MODIFY `LogID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `ModuleID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `PermissionID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `physical_examinations`
--
ALTER TABLE `physical_examinations`
  MODIFY `PhysicalExamID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `ProgramID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `ReportID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `RoleID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `RolePermissionID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `school_people`
--
ALTER TABLE `school_people`
  MODIFY `SchoolPersonID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  MODIFY `EnrollmentID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_diseases`
--
ALTER TABLE `user_diseases`
  MODIFY `UserDiseaseID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `UserRoleID` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_booking_availability` FOREIGN KEY (`AvailabilityID`) REFERENCES `medical_professional_availability` (`AvailabilityID`),
  ADD CONSTRAINT `fk_booking_medprof` FOREIGN KEY (`MedProfID`) REFERENCES `medical_professionals` (`MedProfID`),
  ADD CONSTRAINT `fk_booking_person` FOREIGN KEY (`SchoolPersonID`) REFERENCES `school_people` (`SchoolPersonID`);

--
-- Constraints for table `clinic_transactions`
--
ALTER TABLE `clinic_transactions`
  ADD CONSTRAINT `fk_transaction_booking` FOREIGN KEY (`BookingID`) REFERENCES `bookings` (`BookingID`),
  ADD CONSTRAINT `fk_transaction_medprof` FOREIGN KEY (`MedProfID`) REFERENCES `medical_professionals` (`MedProfID`),
  ADD CONSTRAINT `fk_transaction_person` FOREIGN KEY (`SchoolPersonID`) REFERENCES `school_people` (`SchoolPersonID`);

--
-- Constraints for table `dental_transactions`
--
ALTER TABLE `dental_transactions`
  ADD CONSTRAINT `fk_dental_transaction` FOREIGN KEY (`ClinicTransactionID`) REFERENCES `clinic_transactions` (`ClinicTransactionID`);

--
-- Constraints for table `emergencies`
--
ALTER TABLE `emergencies`
  ADD CONSTRAINT `fk_emergency_person` FOREIGN KEY (`SchoolPersonID`) REFERENCES `school_people` (`SchoolPersonID`);

--
-- Constraints for table `employee_assignments`
--
ALTER TABLE `employee_assignments`
  ADD CONSTRAINT `fk_employee_person` FOREIGN KEY (`SchoolPersonID`) REFERENCES `school_people` (`SchoolPersonID`);

--
-- Constraints for table `medical_certificates`
--
ALTER TABLE `medical_certificates`
  ADD CONSTRAINT `fk_medcert_medprof` FOREIGN KEY (`IssuedByMedProfID`) REFERENCES `medical_professionals` (`MedProfID`),
  ADD CONSTRAINT `fk_medcert_transaction` FOREIGN KEY (`ClinicTransactionID`) REFERENCES `clinic_transactions` (`ClinicTransactionID`);

--
-- Constraints for table `medical_professionals`
--
ALTER TABLE `medical_professionals`
  ADD CONSTRAINT `fk_medprof_user` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `medical_professional_availability`
--
ALTER TABLE `medical_professional_availability`
  ADD CONSTRAINT `fk_availability_medprof` FOREIGN KEY (`MedProfID`) REFERENCES `medical_professionals` (`MedProfID`);

--
-- Constraints for table `medicine_dispensing`
--
ALTER TABLE `medicine_dispensing`
  ADD CONSTRAINT `fk_dispense_inventory` FOREIGN KEY (`InventoryID`) REFERENCES `medicine_inventory` (`InventoryID`),
  ADD CONSTRAINT `fk_dispense_transaction` FOREIGN KEY (`ClinicTransactionID`) REFERENCES `clinic_transactions` (`ClinicTransactionID`);

--
-- Constraints for table `medicine_inventory`
--
ALTER TABLE `medicine_inventory`
  ADD CONSTRAINT `fk_inventory_medicine` FOREIGN KEY (`MedicineID`) REFERENCES `medicines` (`MedicineID`);

--
-- Constraints for table `medicine_inventory_logs`
--
ALTER TABLE `medicine_inventory_logs`
  ADD CONSTRAINT `fk_inventory_log_inventory` FOREIGN KEY (`InventoryID`) REFERENCES `medicine_inventory` (`InventoryID`),
  ADD CONSTRAINT `fk_inventory_log_user` FOREIGN KEY (`PerformedByUserID`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `physical_examinations`
--
ALTER TABLE `physical_examinations`
  ADD CONSTRAINT `fk_physical_transaction` FOREIGN KEY (`ClinicTransactionID`) REFERENCES `clinic_transactions` (`ClinicTransactionID`);

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `fk_report_user` FOREIGN KEY (`GeneratedByUserID`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_rp_module` FOREIGN KEY (`ModuleID`) REFERENCES `modules` (`ModuleID`),
  ADD CONSTRAINT `fk_rp_permission` FOREIGN KEY (`PermissionID`) REFERENCES `permissions` (`PermissionID`),
  ADD CONSTRAINT `fk_rp_role` FOREIGN KEY (`RoleID`) REFERENCES `roles` (`RoleID`);

--
-- Constraints for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  ADD CONSTRAINT `fk_enrollment_person` FOREIGN KEY (`SchoolPersonID`) REFERENCES `school_people` (`SchoolPersonID`),
  ADD CONSTRAINT `fk_enrollment_program` FOREIGN KEY (`ProgramID`) REFERENCES `programs` (`ProgramID`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_user_person` FOREIGN KEY (`SchoolPersonID`) REFERENCES `school_people` (`SchoolPersonID`);

--
-- Constraints for table `user_diseases`
--
ALTER TABLE `user_diseases`
  ADD CONSTRAINT `fk_ud_disease` FOREIGN KEY (`DiseaseID`) REFERENCES `diseases` (`DiseaseID`),
  ADD CONSTRAINT `fk_ud_person` FOREIGN KEY (`SchoolPersonID`) REFERENCES `school_people` (`SchoolPersonID`);

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `fk_ur_role` FOREIGN KEY (`RoleID`) REFERENCES `roles` (`RoleID`),
  ADD CONSTRAINT `fk_ur_user` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
