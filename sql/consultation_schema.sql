-- Consultation Module schema (Consultation Transactions)
-- Uses PHP + MySQL (InnoDB) with prepared statements in application code.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS `consultation_transactions` (
  `ConsultationID` INT NOT NULL AUTO_INCREMENT,
  `SchoolPersonID` INT NOT NULL,
  `TransactionNumber` INT NOT NULL,

  `BloodPressure` VARCHAR(20) DEFAULT NULL,
  `Temperature` DECIMAL(5,2) DEFAULT NULL,
  `PulseRate` INT DEFAULT NULL,
  `Weight` DECIMAL(6,2) DEFAULT NULL,

  `ChiefComplaint` TEXT DEFAULT NULL,
  `ServiceType` VARCHAR(150) NOT NULL,
  `ConsultationStatus` ENUM('Waiting','Consulting','Completed','Cancelled') NOT NULL DEFAULT 'Waiting',
  `ClinicalNotes` TEXT DEFAULT NULL,

  `AttachedDocument` VARCHAR(255) DEFAULT NULL,

  `CreatedAt` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`ConsultationID`),
  UNIQUE KEY `uniq_schoolperson_transaction` (`SchoolPersonID`, `TransactionNumber`),
  KEY `idx_consult_schoolperson` (`SchoolPersonID`),
  KEY `idx_consult_createdat` (`CreatedAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

ALTER TABLE `consultation_transactions`
  ADD CONSTRAINT `fk_consultation_schoolperson`
  FOREIGN KEY (`SchoolPersonID`) REFERENCES `school_people` (`SchoolPersonID`)
  ON UPDATE CASCADE
  ON DELETE RESTRICT;

COMMIT;

