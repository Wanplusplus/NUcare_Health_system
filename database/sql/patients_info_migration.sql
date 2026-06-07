CREATE TABLE IF NOT EXISTS patients_info (
 id INT AUTO_INCREMENT PRIMARY KEY,
 UserID INT NOT NULL,
 contact_no VARCHAR(20) NOT NULL,
 gender VARCHAR(10) NOT NULL,
 birth_date DATE NOT NULL,
 age INT NOT NULL,
 nationality VARCHAR(50),
 status VARCHAR(20),
 religion VARCHAR(30),
 address TEXT,

 guardian_name VARCHAR(100),
 relationship VARCHAR(50),
 mobile_no VARCHAR(20),
 telephone VARCHAR(20),
 emergency_address TEXT,

 UNIQUE KEY uq_patients_info_user (UserID),
 CONSTRAINT fk_user
 FOREIGN KEY (UserID) REFERENCES users(UserID)
 ON DELETE CASCADE
 ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @patients_info_user_idx_exists := (
 SELECT COUNT(1)
 FROM information_schema.statistics
 WHERE table_schema = DATABASE()
 AND table_name = 'patients_info'
 AND index_name = 'uq_patients_info_user'
);
SET @patients_info_user_idx_sql := IF(
 @patients_info_user_idx_exists = 0,
 'ALTER TABLE patients_info ADD UNIQUE KEY uq_patients_info_user (UserID)',
 'SELECT 1'
);
PREPARE patients_info_user_idx_stmt FROM @patients_info_user_idx_sql;
EXECUTE patients_info_user_idx_stmt;
DEALLOCATE PREPARE patients_info_user_idx_stmt;

CREATE TABLE IF NOT EXISTS patient_family_history (
 FamilyHistoryID INT AUTO_INCREMENT PRIMARY KEY,
 UserID INT NOT NULL,
 condition_name VARCHAR(120) NOT NULL,
 relationship VARCHAR(80) NOT NULL,
 notes TEXT,
 CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 KEY idx_family_history_user (UserID),
 CONSTRAINT fk_family_history_user
 FOREIGN KEY (UserID) REFERENCES users(UserID)
 ON DELETE CASCADE
 ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
