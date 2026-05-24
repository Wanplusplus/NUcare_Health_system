-- Consultation Module schema (Consultation Transactions)
-- Uses PHP + MySQL (InnoDB) with prepared statements in application code.

-- NOTE (Core Rule): NO separate consultation table.
-- Consultation transactions are stored in `physical_examinations`.

-- This file only contains ALTER TABLE statements to extend `physical_examinations`.

START TRANSACTION;

-- Transaction number per patient (patient linkage is via clinic_transactions.SchoolPersonID)
-- We store the transaction number on physical_examinations for history display.

ALTER TABLE `physical_examinations`
  ADD COLUMN IF NOT EXISTS `transaction_number` INT NULL,
  ADD COLUMN IF NOT EXISTS `service_type` VARCHAR(150) NULL,
  ADD COLUMN IF NOT EXISTS `consultation_status` ENUM('Waiting','Consulting','Completed','Cancelled') NOT NULL DEFAULT 'Waiting',
  ADD COLUMN IF NOT EXISTS `chief_complaint` TEXT NULL,
  ADD COLUMN IF NOT EXISTS `clinical_notes` TEXT NULL,
  ADD COLUMN IF NOT EXISTS `findings` TEXT NULL,
  ADD COLUMN IF NOT EXISTS `assessment` TEXT NULL,
  ADD COLUMN IF NOT EXISTS `treatment_plan` TEXT NULL,
  ADD COLUMN IF NOT EXISTS `attachment_path` VARCHAR(255) NULL;

-- created_at (useful for history display; keep compatibility with existing CreatedAt)
ALTER TABLE `physical_examinations`
  ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;

-- If physical_examinations already has ExamDate, keep it.
-- Ensure a consistent ordering column exists for history UI.

-- Unique traceability constraint: uniqueness is best enforced with patient reference,
-- but physical_examinations doesn't store SchoolPersonID directly.
-- We enforce uniqueness loosely on (ClinicTransactionID, transaction_number) as a fallback,
-- since ClinicTransactionID is per patient transaction.
-- If you later add school_person_id column to physical_examinations, we can tighten this constraint.

ALTER TABLE `physical_examinations`
  ADD UNIQUE KEY IF NOT EXISTS `uniq_clinictrans_txnum` (`ClinicTransactionID`, `transaction_number`);

COMMIT;


