-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 21, 2026 at 03:58 AM
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
  `ActionTimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `IPAddress` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `BookingID` int NOT NULL,
  `UserID` int NOT NULL,
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
  `UserID` int NOT NULL,
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
  `UserID` int NOT NULL,
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

--
-- Dumping data for table `employee_assignments`
--

INSERT INTO `employee_assignments` (`AssignmentID`, `SchoolPersonID`, `Department`, `PositionTitle`, `EmploymentStatus`, `StartDate`, `EndDate`, `CreatedAt`) VALUES
(1, 16, 'Security', 'Guard', 'Employed', '2025-01-01', NULL, '2026-05-18 15:38:37'),
(2, 17, 'Facilities', 'Janitor', 'Employed', '2025-01-01', NULL, '2026-05-18 15:38:37'),
(3, 18, 'Registrar', 'Registrar', 'Employed', '2025-01-01', NULL, '2026-05-18 15:38:37'),
(4, 11, 'College of Nursing', 'Faculty', 'Employed', '2025-01-01', NULL, '2026-05-18 15:38:37'),
(5, 12, 'College of Medicine', 'Faculty', 'Employed', '2025-01-01', NULL, '2026-05-18 15:38:37'),
(6, 21, 'Clinic', 'Physician', 'Employed', '2025-01-01', NULL, '2026-05-18 15:38:37'),
(7, 22, 'Clinic', 'Physician', 'Employed', '2025-01-01', NULL, '2026-05-18 15:38:37'),
(8, 23, 'Clinic', 'Dentist', 'Employed', '2025-01-01', NULL, '2026-05-18 15:38:37'),
(9, 24, 'Clinic', 'Nurse', 'Employed', '2025-01-01', NULL, '2026-05-18 15:38:37'),
(10, 25, 'Clinic', 'Nurse', 'Employed', '2025-01-01', NULL, '2026-05-18 15:38:37'),
(11, 27, 'Administration', 'System Admin', 'Employed', '2025-01-01', NULL, '2026-05-18 15:38:37'),
(12, 28, 'Administration', 'System Admin', 'Employed', '2025-01-01', NULL, '2026-05-18 15:38:37'),
(13, 29, 'Administration', 'System Admin', 'Employed', '2025-01-01', NULL, '2026-05-18 15:38:37'),
(14, 16, 'Security', 'Guard', 'Employed', '2025-01-01', NULL, '2026-05-21 02:31:24'),
(15, 17, 'Facilities', 'Janitor', 'Employed', '2025-01-01', NULL, '2026-05-21 02:31:24'),
(16, 18, 'Registrar', 'Registrar', 'Employed', '2025-01-01', NULL, '2026-05-21 02:31:24'),
(17, 11, 'College of Nursing', 'Faculty', 'Employed', '2025-01-01', NULL, '2026-05-21 02:31:24'),
(18, 12, 'College of Medicine', 'Faculty', 'Employed', '2025-01-01', NULL, '2026-05-21 02:31:24'),
(19, 21, 'Clinic', 'Physician', 'Employed', '2025-01-01', NULL, '2026-05-21 02:31:24'),
(20, 22, 'Clinic', 'Physician', 'Employed', '2025-01-01', NULL, '2026-05-21 02:31:24'),
(21, 23, 'Clinic', 'Dentist', 'Employed', '2025-01-01', NULL, '2026-05-21 02:31:24'),
(22, 24, 'Clinic', 'Nurse', 'Employed', '2025-01-01', NULL, '2026-05-21 02:31:24'),
(23, 25, 'Clinic', 'Nurse', 'Employed', '2025-01-01', NULL, '2026-05-21 02:31:24'),
(24, 27, 'Administration', 'System Admin', 'Employed', '2025-01-01', NULL, '2026-05-21 02:31:24'),
(25, 28, 'Administration', 'System Admin', 'Employed', '2025-01-01', NULL, '2026-05-21 02:31:24'),
(26, 29, 'Administration', 'System Admin', 'Employed', '2025-01-01', NULL, '2026-05-21 02:31:24');

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

--
-- Dumping data for table `medical_professionals`
--

INSERT INTO `medical_professionals` (`MedProfID`, `UserID`, `Profession`, `Unit`, `CreatedAt`) VALUES
(1, 23, 'Doctor', NULL, '2026-05-21 03:42:25'),
(2, 24, 'Nurse', NULL, '2026-05-21 03:42:25'),
(3, 25, 'Dentist', NULL, '2026-05-21 03:42:25');

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
(1, 'Consultation', 'Consultation module'),
(2, 'Records', 'Clinic records module'),
(3, 'Reports', 'Reports module'),
(4, 'Medicine', 'Medicine inventory module'),
(5, 'Schedule', 'Scheduling module'),
(6, 'Admin Panel', 'Administrative module'),
(7, 'RBAC Management', 'Role management module');

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
(1, 'View', 'Can view module'),
(2, 'Create', 'Can create records'),
(3, 'Edit', 'Can edit records'),
(4, 'Delete', 'Can delete records'),
(5, 'Approve', 'Can approve requests'),
(6, 'Manage', 'Full management permissions'),
(7, 'access', 'Allow access to module');

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
(1, 'Student', 'Student user'),
(2, 'Faculty', 'Faculty user'),
(3, 'Staff', 'Staff user'),
(4, 'Doctor', 'Doctor access'),
(5, 'Dentist', 'Dentist access'),
(6, 'Nurse', 'Nurse access'),
(7, 'Admin', 'Admin access'),
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

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`RolePermissionID`, `RoleID`, `ModuleID`, `PermissionID`) VALUES
(1, 1, 1, 1),
(2, 1, 1, 7),
(3, 1, 2, 1),
(4, 1, 2, 7),
(5, 1, 5, 1),
(6, 1, 5, 7),
(249, 2, 1, 1),
(272, 2, 1, 2),
(280, 2, 1, 3),
(250, 2, 1, 7),
(251, 2, 2, 1),
(268, 2, 2, 2),
(276, 2, 2, 3),
(252, 2, 2, 7),
(282, 2, 4, 1),
(270, 2, 4, 2),
(278, 2, 4, 3),
(264, 2, 4, 7),
(253, 2, 5, 1),
(266, 2, 5, 2),
(274, 2, 5, 3),
(254, 2, 5, 7),
(256, 3, 1, 1),
(273, 3, 1, 2),
(281, 3, 1, 3),
(257, 3, 1, 7),
(258, 3, 2, 1),
(269, 3, 2, 2),
(277, 3, 2, 3),
(259, 3, 2, 7),
(283, 3, 4, 1),
(271, 3, 4, 2),
(279, 3, 4, 3),
(265, 3, 4, 7),
(260, 3, 5, 1),
(267, 3, 5, 2),
(275, 3, 5, 3),
(261, 3, 5, 7),
(129, 4, 1, 1),
(105, 4, 1, 2),
(117, 4, 1, 3),
(93, 4, 1, 5),
(81, 4, 1, 7),
(123, 4, 2, 1),
(99, 4, 2, 2),
(111, 4, 2, 3),
(87, 4, 2, 5),
(75, 4, 2, 7),
(126, 4, 4, 1),
(102, 4, 4, 2),
(114, 4, 4, 3),
(90, 4, 4, 5),
(78, 4, 4, 7),
(120, 4, 5, 1),
(96, 4, 5, 2),
(108, 4, 5, 3),
(84, 4, 5, 5),
(72, 4, 5, 7),
(128, 5, 1, 1),
(104, 5, 1, 2),
(116, 5, 1, 3),
(92, 5, 1, 5),
(80, 5, 1, 7),
(122, 5, 2, 1),
(98, 5, 2, 2),
(110, 5, 2, 3),
(86, 5, 2, 5),
(74, 5, 2, 7),
(125, 5, 4, 1),
(101, 5, 4, 2),
(113, 5, 4, 3),
(89, 5, 4, 5),
(77, 5, 4, 7),
(119, 5, 5, 1),
(95, 5, 5, 2),
(107, 5, 5, 3),
(83, 5, 5, 5),
(71, 5, 5, 7),
(130, 6, 1, 1),
(106, 6, 1, 2),
(118, 6, 1, 3),
(94, 6, 1, 5),
(82, 6, 1, 7),
(124, 6, 2, 1),
(100, 6, 2, 2),
(112, 6, 2, 3),
(88, 6, 2, 5),
(76, 6, 2, 7),
(127, 6, 4, 1),
(103, 6, 4, 2),
(115, 6, 4, 3),
(91, 6, 4, 5),
(79, 6, 4, 7),
(121, 6, 5, 1),
(97, 6, 5, 2),
(109, 6, 5, 3),
(85, 6, 5, 5),
(73, 6, 5, 7),
(181, 7, 1, 1),
(153, 7, 1, 2),
(167, 7, 1, 3),
(160, 7, 1, 4),
(146, 7, 1, 5),
(174, 7, 1, 6),
(139, 7, 1, 7),
(178, 7, 2, 1),
(150, 7, 2, 2),
(164, 7, 2, 3),
(157, 7, 2, 4),
(143, 7, 2, 5),
(171, 7, 2, 6),
(136, 7, 2, 7),
(177, 7, 3, 1),
(149, 7, 3, 2),
(163, 7, 3, 3),
(156, 7, 3, 4),
(142, 7, 3, 5),
(170, 7, 3, 6),
(135, 7, 3, 7),
(180, 7, 4, 1),
(152, 7, 4, 2),
(166, 7, 4, 3),
(159, 7, 4, 4),
(145, 7, 4, 5),
(173, 7, 4, 6),
(138, 7, 4, 7),
(176, 7, 5, 1),
(148, 7, 5, 2),
(162, 7, 5, 3),
(155, 7, 5, 4),
(141, 7, 5, 5),
(169, 7, 5, 6),
(134, 7, 5, 7),
(182, 7, 6, 1),
(154, 7, 6, 2),
(168, 7, 6, 3),
(161, 7, 6, 4),
(147, 7, 6, 5),
(175, 7, 6, 6),
(140, 7, 6, 7),
(179, 7, 7, 1),
(151, 7, 7, 2),
(165, 7, 7, 3),
(158, 7, 7, 4),
(144, 7, 7, 5),
(172, 7, 7, 6),
(137, 7, 7, 7),
(244, 8, 1, 1),
(216, 8, 1, 2),
(230, 8, 1, 3),
(223, 8, 1, 4),
(209, 8, 1, 5),
(237, 8, 1, 6),
(202, 8, 1, 7),
(241, 8, 2, 1),
(213, 8, 2, 2),
(227, 8, 2, 3),
(220, 8, 2, 4),
(206, 8, 2, 5),
(234, 8, 2, 6),
(199, 8, 2, 7),
(240, 8, 3, 1),
(212, 8, 3, 2),
(226, 8, 3, 3),
(219, 8, 3, 4),
(205, 8, 3, 5),
(233, 8, 3, 6),
(198, 8, 3, 7),
(243, 8, 4, 1),
(215, 8, 4, 2),
(229, 8, 4, 3),
(222, 8, 4, 4),
(208, 8, 4, 5),
(236, 8, 4, 6),
(201, 8, 4, 7),
(239, 8, 5, 1),
(211, 8, 5, 2),
(225, 8, 5, 3),
(218, 8, 5, 4),
(204, 8, 5, 5),
(232, 8, 5, 6),
(197, 8, 5, 7),
(245, 8, 6, 1),
(217, 8, 6, 2),
(231, 8, 6, 3),
(224, 8, 6, 4),
(210, 8, 6, 5),
(238, 8, 6, 6),
(203, 8, 6, 7),
(242, 8, 7, 1),
(214, 8, 7, 2),
(228, 8, 7, 3),
(221, 8, 7, 4),
(207, 8, 7, 5),
(235, 8, 7, 6),
(200, 8, 7, 7);

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

--
-- Dumping data for table `school_people`
--

INSERT INTO `school_people` (`SchoolPersonID`, `SchoolID`, `FirstName`, `LastName`, `MiddleName`, `Email`, `PersonType`, `Sex`, `CreatedAt`) VALUES
(1, 'SCH-1001', 'Juan', 'Santos', 'A', 'juan.santos@nucare.edu', 'Student', 'Male', '2026-05-18 15:38:36'),
(2, 'SCH-1002', 'Maria', 'Reyes', 'B', 'maria.reyes@nucare.edu', 'Student', 'Female', '2026-05-18 15:38:36'),
(3, 'SCH-1003', 'Liam', 'Cruz', 'C', 'liam.cruz@nucare.edu', 'Student', 'Male', '2026-05-18 15:38:36'),
(4, 'SCH-1004', 'Sophia', 'Dela Cruz', 'D', 'sophia.delacruz@nucare.edu', 'Student', 'Female', '2026-05-18 15:38:36'),
(5, 'SCH-1005', 'Noah', 'Garcia', 'E', 'noah.garcia@nucare.edu', 'Student', 'Male', '2026-05-18 15:38:36'),
(6, 'SCH-1006', 'Emma', 'Mendoza', 'F', 'emma.mendoza@nucare.edu', 'Student', 'Female', '2026-05-18 15:38:36'),
(7, 'SCH-1007', 'Aiden', 'Torres', 'G', 'aiden.torres@nucare.edu', 'Student', 'Male', '2026-05-18 15:38:36'),
(8, 'SCH-1008', 'Olivia', 'Ramos', 'H', 'olivia.ramos@nucare.edu', 'Student', 'Female', '2026-05-18 15:38:36'),
(9, 'SCH-1009', 'Ethan', 'Castillo', 'I', 'ethan.castillo@nucare.edu', 'Student', 'Male', '2026-05-18 15:38:36'),
(10, 'SCH-1010', 'Isla', 'Villanueva', 'J', 'isla.villanueva@nucare.edu', 'Student', 'Female', '2026-05-18 15:38:36'),
(11, 'SCH-2001', 'Ana', 'Fernandez', 'K', 'ana.fernandez@nucare.edu', 'Faculty', 'Female', '2026-05-18 15:38:36'),
(12, 'SCH-2002', 'Mark', 'Santos', 'L', 'mark.santos@nucare.edu', 'Faculty', 'Male', '2026-05-18 15:38:36'),
(13, 'SCH-2003', 'Kate', 'Gomez', 'M', 'kate.gomez@nucare.edu', 'Faculty', 'Female', '2026-05-18 15:38:36'),
(14, 'SCH-2004', 'Daniel', 'Aguilar', 'N', 'daniel.aguilar@nucare.edu', 'Faculty', 'Male', '2026-05-18 15:38:36'),
(15, 'SCH-2005', 'Grace', 'Santos', 'O', 'grace.santos@nucare.edu', 'Faculty', 'Female', '2026-05-18 15:38:36'),
(16, 'SCH-3001', 'Paul', 'Ramos', 'P', 'paul.ramos@nucare.edu', 'Staff', 'Male', '2026-05-18 15:38:36'),
(17, 'SCH-3002', 'Jade', 'Flores', 'Q', 'jade.flores@nucare.edu', 'Staff', 'Female', '2026-05-18 15:38:36'),
(18, 'SCH-3003', 'Victor', 'Navarro', 'R', 'victor.navarro@nucare.edu', 'Staff', 'Male', '2026-05-18 15:38:36'),
(19, 'SCH-3004', 'Nina', 'Ortega', 'S', 'nina.ortega@nucare.edu', 'Staff', 'Female', '2026-05-18 15:38:36'),
(20, 'SCH-3005', 'Oscar', 'Montoya', 'T', 'oscar.montoya@nucare.edu', 'Staff', 'Male', '2026-05-18 15:38:36'),
(21, 'SCH-4001', 'Dr. Miguel', 'Cordero', 'U', 'miguel.cordero@nucare.edu', 'Staff', 'Male', '2026-05-18 15:38:36'),
(22, 'SCH-4002', 'Dr. Carla', 'Paredes', 'V', 'carla.paredes@nucare.edu', 'Staff', 'Female', '2026-05-18 15:38:36'),
(23, 'SCH-5001', 'Dr. Rafael', 'Bautista', 'W', 'rafael.bautista@nucare.edu', 'Staff', 'Male', '2026-05-18 15:38:36'),
(24, 'SCH-6001', 'Nurse Helen', 'Reyes', 'X', 'helen.reyes@nucare.edu', 'Staff', 'Female', '2026-05-18 15:38:36'),
(25, 'SCH-6002', 'Nurse Josephine', 'Cruz', 'Y', 'josephine.cruz@nucare.edu', 'Staff', 'Female', '2026-05-18 15:38:36'),
(26, 'SCH-7001', 'Admin', 'Dela Rosa', 'Z', 'admin.delarosa@nucare.edu', 'Faculty', 'Male', '2026-05-18 15:38:36'),
(27, 'SCH-8001', 'Super', 'Admin One', 'AA', 'superadmin1@nucare.edu', 'Staff', 'Female', '2026-05-18 15:38:37'),
(28, 'SCH-8002', 'Super', 'Admin Two', 'AB', 'superadmin2@nucare.edu', 'Staff', 'Male', '2026-05-18 15:38:37'),
(29, 'SCH-8003', 'Super', 'Admin Three', 'AC', 'superadmin3@nucare.edu', 'Staff', 'Female', '2026-05-18 15:38:37'),
(65, 'STF-888', 'Demo', 'Doctor', NULL, 'doctor888@example.com', 'Staff', 'Male', '2026-05-21 03:25:38'),
(66, 'STF-999', 'Demo', 'Nurse', NULL, 'nurse999@example.com', 'Staff', 'Female', '2026-05-21 03:25:38'),
(67, 'STF-000', 'Demo', 'Dentist', NULL, 'dentist000@example.com', 'Staff', 'Male', '2026-05-21 03:25:38');

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

--
-- Dumping data for table `student_enrollments`
--

INSERT INTO `student_enrollments` (`EnrollmentID`, `SchoolPersonID`, `ProgramID`, `AcademicYear`, `Semester`, `EnrollmentStatus`, `CreatedAt`) VALUES
(1, 1, NULL, '2025-2026', '1st Semester', 'Enrolled', '2026-05-18 15:38:37'),
(2, 2, NULL, '2025-2026', '1st Semester', 'Enrolled', '2026-05-18 15:38:37'),
(3, 3, NULL, '2025-2026', '1st Semester', 'Enrolled', '2026-05-18 15:38:37'),
(4, 4, NULL, '2025-2026', '1st Semester', 'Dropped', '2026-05-18 15:38:37'),
(5, 5, NULL, '2025-2026', '1st Semester', 'Enrolled', '2026-05-18 15:38:37'),
(6, 6, NULL, '2025-2026', '1st Semester', 'Enrolled', '2026-05-18 15:38:37'),
(7, 7, NULL, '2025-2026', '1st Semester', 'Dropped', '2026-05-18 15:38:37'),
(8, 8, NULL, '2025-2026', '1st Semester', 'Enrolled', '2026-05-18 15:38:37'),
(9, 1, NULL, '2025-2026', '2nd Semester', 'Enrolled', '2026-05-18 15:38:37'),
(10, 2, NULL, '2025-2026', '2nd Semester', 'Enrolled', '2026-05-18 15:38:37'),
(11, 3, NULL, '2025-2026', '2nd Semester', 'Enrolled', '2026-05-18 15:38:37'),
(12, 4, NULL, '2025-2026', '2nd Semester', 'Not Enrolled', '2026-05-18 15:38:37'),
(13, 5, NULL, '2025-2026', '2nd Semester', 'Enrolled', '2026-05-18 15:38:37'),
(14, 6, NULL, '2025-2026', '2nd Semester', 'Enrolled', '2026-05-18 15:38:37'),
(15, 7, NULL, '2025-2026', '2nd Semester', 'Not Enrolled', '2026-05-18 15:38:37'),
(16, 8, NULL, '2025-2026', '2nd Semester', 'Enrolled', '2026-05-18 15:38:37'),
(17, 1, NULL, '2025-2026', '1st Semester', 'Enrolled', '2026-05-21 02:31:24'),
(18, 2, NULL, '2025-2026', '1st Semester', 'Enrolled', '2026-05-21 02:31:24'),
(19, 3, NULL, '2025-2026', '1st Semester', 'Enrolled', '2026-05-21 02:31:24'),
(20, 4, NULL, '2025-2026', '1st Semester', 'Dropped', '2026-05-21 02:31:24'),
(21, 5, NULL, '2025-2026', '1st Semester', 'Enrolled', '2026-05-21 02:31:24'),
(22, 6, NULL, '2025-2026', '1st Semester', 'Enrolled', '2026-05-21 02:31:24'),
(23, 7, NULL, '2025-2026', '1st Semester', 'Dropped', '2026-05-21 02:31:24'),
(24, 8, NULL, '2025-2026', '1st Semester', 'Enrolled', '2026-05-21 02:31:24'),
(25, 1, NULL, '2025-2026', '2nd Semester', 'Enrolled', '2026-05-21 02:31:24'),
(26, 2, NULL, '2025-2026', '2nd Semester', 'Enrolled', '2026-05-21 02:31:24'),
(27, 3, NULL, '2025-2026', '2nd Semester', 'Enrolled', '2026-05-21 02:31:24'),
(28, 4, NULL, '2025-2026', '2nd Semester', 'Not Enrolled', '2026-05-21 02:31:24'),
(29, 5, NULL, '2025-2026', '2nd Semester', 'Enrolled', '2026-05-21 02:31:24'),
(30, 6, NULL, '2025-2026', '2nd Semester', 'Enrolled', '2026-05-21 02:31:24'),
(31, 7, NULL, '2025-2026', '2nd Semester', 'Not Enrolled', '2026-05-21 02:31:24'),
(32, 8, NULL, '2025-2026', '2nd Semester', 'Enrolled', '2026-05-21 02:31:24');

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

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `SchoolPersonID`, `PasswordHash`, `ResetToken`, `TokenExpiry`, `IsActive`, `LastLogin`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 27, '$2y$10$MbHUvivnEtPN9vR/CIY7DezcYUW80wm8QBkvCqaB1EZXJmXUi6.9C', NULL, NULL, 1, NULL, '2026-05-18 15:38:37', '2026-05-18 15:38:37'),
(2, 28, '$2y$10$MbHUvivnEtPN9vR/CIY7DezcYUW80wm8QBkvCqaB1EZXJmXUi6.9C', NULL, NULL, 1, NULL, '2026-05-18 15:38:37', '2026-05-18 15:38:37'),
(3, 29, '$2y$10$MbHUvivnEtPN9vR/CIY7DezcYUW80wm8QBkvCqaB1EZXJmXUi6.9C', NULL, NULL, 1, NULL, '2026-05-18 15:38:37', '2026-05-18 15:38:37'),
(4, 1, '$2y$10$MbHUvivnEtPN9vR/CIY7DezcYUW80wm8QBkvCqaB1EZXJmXUi6.9C', NULL, NULL, 1, NULL, '2026-05-18 18:11:23', '2026-05-21 02:31:24'),
(5, 24, '$2y$10$.eSSa1GEcybVGZdGby7co.Iw0GmfLk8GqquSf7Jc/lpY3/8T9smeu', NULL, NULL, 1, NULL, '2026-05-21 01:23:59', '2026-05-21 01:23:59'),
(6, 2, '$2y$10$MbHUvivnEtPN9vR/CIY7DezcYUW80wm8QBkvCqaB1EZXJmXUi6.9C', NULL, NULL, 1, NULL, '2026-05-21 01:26:56', '2026-05-21 02:31:24'),
(7, 3, '$2y$10$MbHUvivnEtPN9vR/CIY7DezcYUW80wm8QBkvCqaB1EZXJmXUi6.9C', NULL, NULL, 1, NULL, '2026-05-21 01:32:49', '2026-05-21 02:31:24'),
(9, 4, '$2y$10$MbHUvivnEtPN9vR/CIY7DezcYUW80wm8QBkvCqaB1EZXJmXUi6.9C', NULL, NULL, 1, NULL, '2026-05-21 02:31:24', '2026-05-21 02:31:24'),
(10, 5, '$2y$10$MbHUvivnEtPN9vR/CIY7DezcYUW80wm8QBkvCqaB1EZXJmXUi6.9C', NULL, NULL, 1, NULL, '2026-05-21 02:31:24', '2026-05-21 02:31:24'),
(11, 6, '$2y$10$MbHUvivnEtPN9vR/CIY7DezcYUW80wm8QBkvCqaB1EZXJmXUi6.9C', NULL, NULL, 1, NULL, '2026-05-21 02:31:24', '2026-05-21 02:31:24'),
(12, 7, '$2y$10$MbHUvivnEtPN9vR/CIY7DezcYUW80wm8QBkvCqaB1EZXJmXUi6.9C', NULL, NULL, 1, NULL, '2026-05-21 02:31:24', '2026-05-21 02:31:24'),
(13, 8, '$2y$10$MbHUvivnEtPN9vR/CIY7DezcYUW80wm8QBkvCqaB1EZXJmXUi6.9C', NULL, NULL, 1, NULL, '2026-05-21 02:31:24', '2026-05-21 02:31:24'),
(14, 9, '$2y$10$MbHUvivnEtPN9vR/CIY7DezcYUW80wm8QBkvCqaB1EZXJmXUi6.9C', NULL, NULL, 1, NULL, '2026-05-21 02:31:24', '2026-05-21 02:31:24'),
(15, 10, '$2y$10$S7Sb5GS0xWrRazOzb1iIKeafOMyJzp935U/xILmTxsW9t9BVszdBm', NULL, NULL, 1, NULL, '2026-05-21 02:31:24', '2026-05-21 02:33:14'),
(16, 21, '$2y$10$5LvucdTBkHR1qaMagGplvucS9GhIin6hTZHLZgBAmZTSsSOlK8S7i', NULL, NULL, 1, NULL, '2026-05-21 02:36:14', '2026-05-21 02:36:14'),
(23, 65, '$2y$10$N98IQPwRp5vj8ARnaZcwG.N8bs8sS2iq09eKxGNLEkY2/NlQa14Te', NULL, NULL, 1, NULL, '2026-05-21 03:25:38', '2026-05-21 03:42:25'),
(24, 66, '$2y$10$HkVcGfn3.CLzPNgYCVwxY.IqdcYrq.wixOR.Ap3PVvATJ/xtrwjs.', NULL, NULL, 1, NULL, '2026-05-21 03:25:38', '2026-05-21 03:42:25'),
(25, 67, '$2y$10$OXcWEdjoWxOK0TWX.nyjNeLd7jYJ8UX2sdRs6WnYg8zNxo4k7gTCy', NULL, NULL, 1, NULL, '2026-05-21 03:25:38', '2026-05-21 03:42:25');

-- --------------------------------------------------------

--
-- Table structure for table `user_diseases`
--

CREATE TABLE `user_diseases` (
  `UserDiseaseID` int NOT NULL,
  `UserID` int NOT NULL,
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
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`UserRoleID`, `UserID`, `RoleID`) VALUES
(1, 1, 8),
(2, 2, 8),
(3, 3, 8),
(4, 4, 1),
(5, 5, 3),
(6, 6, 1),
(8, 7, 1),
(10, 9, 1),
(11, 10, 1),
(12, 11, 1),
(13, 12, 1),
(14, 13, 1),
(15, 14, 1),
(16, 15, 1),
(18, 16, 4),
(25, 23, 4),
(26, 24, 6),
(27, 25, 5);

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
  ADD KEY `fk_booking_user` (`UserID`),
  ADD KEY `fk_booking_medprof` (`MedProfID`),
  ADD KEY `fk_booking_availability` (`AvailabilityID`);

--
-- Indexes for table `clinic_transactions`
--
ALTER TABLE `clinic_transactions`
  ADD PRIMARY KEY (`ClinicTransactionID`),
  ADD KEY `fk_transaction_booking` (`BookingID`),
  ADD KEY `fk_transaction_user` (`UserID`),
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
  ADD KEY `fk_emergency_user` (`UserID`);

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
  ADD KEY `fk_ud_user` (`UserID`),
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
  MODIFY `AssignmentID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `medical_certificates`
--
ALTER TABLE `medical_certificates`
  MODIFY `MedicalCertificateID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medical_professionals`
--
ALTER TABLE `medical_professionals`
  MODIFY `MedProfID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  MODIFY `ModuleID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `PermissionID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

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
  MODIFY `RoleID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `RolePermissionID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=298;

--
-- AUTO_INCREMENT for table `school_people`
--
ALTER TABLE `school_people`
  MODIFY `SchoolPersonID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  MODIFY `EnrollmentID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `user_diseases`
--
ALTER TABLE `user_diseases`
  MODIFY `UserDiseaseID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `UserRoleID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

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
  ADD CONSTRAINT `fk_booking_user` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `clinic_transactions`
--
ALTER TABLE `clinic_transactions`
  ADD CONSTRAINT `fk_transaction_booking` FOREIGN KEY (`BookingID`) REFERENCES `bookings` (`BookingID`),
  ADD CONSTRAINT `fk_transaction_medprof` FOREIGN KEY (`MedProfID`) REFERENCES `medical_professionals` (`MedProfID`),
  ADD CONSTRAINT `fk_transaction_user` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`);

--
-- Constraints for table `dental_transactions`
--
ALTER TABLE `dental_transactions`
  ADD CONSTRAINT `fk_dental_transaction` FOREIGN KEY (`ClinicTransactionID`) REFERENCES `clinic_transactions` (`ClinicTransactionID`);

--
-- Constraints for table `emergencies`
--
ALTER TABLE `emergencies`
  ADD CONSTRAINT `fk_emergency_user` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`);

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
  ADD CONSTRAINT `fk_ud_user` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`);

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
