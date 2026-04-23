-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 23, 2026 at 02:04 AM
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
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `BookingID` int NOT NULL,
  `PatientID` int DEFAULT NULL,
  `MedProfID` int DEFAULT NULL,
  `ServiceType` varchar(100) DEFAULT NULL,
  `PreferredDate` date DEFAULT NULL,
  `ReasonForVisit` varchar(255) DEFAULT NULL,
  `BookingStatus` enum('Pending','Approved','Completed','Cancelled') DEFAULT 'Pending',
  `RequestDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinictransaction`
--

CREATE TABLE `clinictransaction` (
  `ClinicTransactionID` int NOT NULL,
  `PatientID` int DEFAULT NULL,
  `MedProfID` int DEFAULT NULL,
  `VisitDate` date DEFAULT NULL,
  `Complaint` varchar(255) DEFAULT NULL,
  `ServiceType` varchar(150) DEFAULT NULL,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dentaltransaction`
--

CREATE TABLE `dentaltransaction` (
  `DentalTransactionID` int NOT NULL,
  `ClinicTransactionID` int DEFAULT NULL,
  `CurrentAge` int DEFAULT NULL,
  `TeethCount` int DEFAULT NULL,
  `Operation_lower` varchar(255) DEFAULT NULL,
  `Condition_lower` varchar(255) DEFAULT NULL,
  `Operation_upper` varchar(255) DEFAULT NULL,
  `Condition_upper` varchar(255) DEFAULT NULL,
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
-- Table structure for table `emergency`
--

CREATE TABLE `emergency` (
  `EmergencyID` int NOT NULL,
  `PatientID` int DEFAULT NULL,
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
  `TimeArrived` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicalcertificates`
--

CREATE TABLE `medicalcertificates` (
  `CertID` int NOT NULL,
  `ClinicTransactionID` int DEFAULT NULL,
  `MedProfID` int DEFAULT NULL,
  `CertType` varchar(150) DEFAULT NULL,
  `Recommendation` varchar(255) DEFAULT NULL,
  `ValidUntil` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicalprofessionals`
--

CREATE TABLE `medicalprofessionals` (
  `MedProfID` int NOT NULL,
  `MedProfFname` varchar(150) NOT NULL,
  `MedProfLname` varchar(150) NOT NULL,
  `MedProfMname` varchar(150) DEFAULT NULL,
  `Unit` varchar(100) NOT NULL,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicineandstuffs`
--

CREATE TABLE `medicineandstuffs` (
  `ItemID` int NOT NULL,
  `ItemCategory` varchar(100) DEFAULT NULL,
  `ItemName` varchar(150) NOT NULL,
  `ItemStockQuantity` int DEFAULT '0',
  `Unit` varchar(50) DEFAULT NULL,
  `ExpiryDate` date DEFAULT NULL,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patientdiseases`
--

CREATE TABLE `patientdiseases` (
  `PatientDiseaseID` int NOT NULL,
  `PatientID` int DEFAULT NULL,
  `DiseaseID` int DEFAULT NULL,
  `Notes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `PatientID` int NOT NULL,
  `PatientFname` varchar(150) NOT NULL,
  `PatientLname` varchar(150) NOT NULL,
  `PatientMname` varchar(150) DEFAULT NULL,
  `ProgramID` int DEFAULT NULL,
  `Sex` enum('Male','Female') NOT NULL,
  `Birthday` date DEFAULT NULL,
  `EmailAdd` varchar(150) DEFAULT NULL,
  `PhoneNum` varchar(11) DEFAULT NULL,
  `Address` varchar(255) DEFAULT NULL,
  `Religion` varchar(100) DEFAULT NULL,
  `CreatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Table structure for table `physicalexamination`
--

CREATE TABLE `physicalexamination` (
  `PhysicalExamID` int NOT NULL,
  `ClinicTransactionID` int DEFAULT NULL,
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
  `Cardio_Clearance` tinyint(1) DEFAULT NULL
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
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `ScheduleID` int NOT NULL,
  `MedProfID` int DEFAULT NULL,
  `AvailableDate` date DEFAULT NULL,
  `TimeIN` time DEFAULT NULL,
  `TimeOUT` time DEFAULT NULL,
  `Is_Available` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactionitems`
--

CREATE TABLE `transactionitems` (
  `TransactionItemID` int NOT NULL,
  `ClinicTransactionID` int DEFAULT NULL,
  `ItemID` int DEFAULT NULL,
  `Quantity` int DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`BookingID`),
  ADD KEY `PatientID` (`PatientID`),
  ADD KEY `MedProfID` (`MedProfID`);

--
-- Indexes for table `clinictransaction`
--
ALTER TABLE `clinictransaction`
  ADD PRIMARY KEY (`ClinicTransactionID`),
  ADD KEY `PatientID` (`PatientID`),
  ADD KEY `MedProfID` (`MedProfID`);

--
-- Indexes for table `dentaltransaction`
--
ALTER TABLE `dentaltransaction`
  ADD PRIMARY KEY (`DentalTransactionID`),
  ADD KEY `ClinicTransactionID` (`ClinicTransactionID`);

--
-- Indexes for table `diseases`
--
ALTER TABLE `diseases`
  ADD PRIMARY KEY (`DiseaseID`);

--
-- Indexes for table `emergency`
--
ALTER TABLE `emergency`
  ADD PRIMARY KEY (`EmergencyID`),
  ADD KEY `PatientID` (`PatientID`);

--
-- Indexes for table `medicalcertificates`
--
ALTER TABLE `medicalcertificates`
  ADD PRIMARY KEY (`CertID`),
  ADD KEY `ClinicTransactionID` (`ClinicTransactionID`),
  ADD KEY `MedProfID` (`MedProfID`);

--
-- Indexes for table `medicalprofessionals`
--
ALTER TABLE `medicalprofessionals`
  ADD PRIMARY KEY (`MedProfID`);

--
-- Indexes for table `medicineandstuffs`
--
ALTER TABLE `medicineandstuffs`
  ADD PRIMARY KEY (`ItemID`);

--
-- Indexes for table `patientdiseases`
--
ALTER TABLE `patientdiseases`
  ADD PRIMARY KEY (`PatientDiseaseID`),
  ADD KEY `PatientID` (`PatientID`),
  ADD KEY `DiseaseID` (`DiseaseID`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`PatientID`),
  ADD KEY `fk_patient_program` (`ProgramID`);

--
-- Indexes for table `physicalexamination`
--
ALTER TABLE `physicalexamination`
  ADD PRIMARY KEY (`PhysicalExamID`),
  ADD KEY `ClinicTransactionID` (`ClinicTransactionID`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`ProgramID`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`ScheduleID`),
  ADD KEY `MedProfID` (`MedProfID`);

--
-- Indexes for table `transactionitems`
--
ALTER TABLE `transactionitems`
  ADD PRIMARY KEY (`TransactionItemID`),
  ADD KEY `ClinicTransactionID` (`ClinicTransactionID`),
  ADD KEY `ItemID` (`ItemID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `BookingID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clinictransaction`
--
ALTER TABLE `clinictransaction`
  MODIFY `ClinicTransactionID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dentaltransaction`
--
ALTER TABLE `dentaltransaction`
  MODIFY `DentalTransactionID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `diseases`
--
ALTER TABLE `diseases`
  MODIFY `DiseaseID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emergency`
--
ALTER TABLE `emergency`
  MODIFY `EmergencyID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicalcertificates`
--
ALTER TABLE `medicalcertificates`
  MODIFY `CertID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicalprofessionals`
--
ALTER TABLE `medicalprofessionals`
  MODIFY `MedProfID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicineandstuffs`
--
ALTER TABLE `medicineandstuffs`
  MODIFY `ItemID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patientdiseases`
--
ALTER TABLE `patientdiseases`
  MODIFY `PatientDiseaseID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `PatientID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `physicalexamination`
--
ALTER TABLE `physicalexamination`
  MODIFY `PhysicalExamID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `ProgramID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `ScheduleID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactionitems`
--
ALTER TABLE `transactionitems`
  MODIFY `TransactionItemID` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`PatientID`) REFERENCES `patients` (`PatientID`),
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`MedProfID`) REFERENCES `medicalprofessionals` (`MedProfID`);

--
-- Constraints for table `clinictransaction`
--
ALTER TABLE `clinictransaction`
  ADD CONSTRAINT `clinictransaction_ibfk_1` FOREIGN KEY (`PatientID`) REFERENCES `patients` (`PatientID`),
  ADD CONSTRAINT `clinictransaction_ibfk_2` FOREIGN KEY (`MedProfID`) REFERENCES `medicalprofessionals` (`MedProfID`);

--
-- Constraints for table `dentaltransaction`
--
ALTER TABLE `dentaltransaction`
  ADD CONSTRAINT `dentaltransaction_ibfk_1` FOREIGN KEY (`ClinicTransactionID`) REFERENCES `clinictransaction` (`ClinicTransactionID`);

--
-- Constraints for table `emergency`
--
ALTER TABLE `emergency`
  ADD CONSTRAINT `emergency_ibfk_1` FOREIGN KEY (`PatientID`) REFERENCES `patients` (`PatientID`);

--
-- Constraints for table `medicalcertificates`
--
ALTER TABLE `medicalcertificates`
  ADD CONSTRAINT `medicalcertificates_ibfk_1` FOREIGN KEY (`ClinicTransactionID`) REFERENCES `clinictransaction` (`ClinicTransactionID`),
  ADD CONSTRAINT `medicalcertificates_ibfk_2` FOREIGN KEY (`MedProfID`) REFERENCES `medicalprofessionals` (`MedProfID`);

--
-- Constraints for table `patientdiseases`
--
ALTER TABLE `patientdiseases`
  ADD CONSTRAINT `patientdiseases_ibfk_1` FOREIGN KEY (`PatientID`) REFERENCES `patients` (`PatientID`),
  ADD CONSTRAINT `patientdiseases_ibfk_2` FOREIGN KEY (`DiseaseID`) REFERENCES `diseases` (`DiseaseID`);

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `fk_patient_program` FOREIGN KEY (`ProgramID`) REFERENCES `programs` (`ProgramID`);

--
-- Constraints for table `physicalexamination`
--
ALTER TABLE `physicalexamination`
  ADD CONSTRAINT `physicalexamination_ibfk_1` FOREIGN KEY (`ClinicTransactionID`) REFERENCES `clinictransaction` (`ClinicTransactionID`);

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `schedules_ibfk_1` FOREIGN KEY (`MedProfID`) REFERENCES `medicalprofessionals` (`MedProfID`);

--
-- Constraints for table `transactionitems`
--
ALTER TABLE `transactionitems`
  ADD CONSTRAINT `transactionitems_ibfk_1` FOREIGN KEY (`ClinicTransactionID`) REFERENCES `clinictransaction` (`ClinicTransactionID`),
  ADD CONSTRAINT `transactionitems_ibfk_2` FOREIGN KEY (`ItemID`) REFERENCES `medicineandstuffs` (`ItemID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
