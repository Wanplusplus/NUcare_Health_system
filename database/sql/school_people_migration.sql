START TRANSACTION;

ALTER TABLE `school_people`
 MODIFY `SchoolID` VARCHAR(50) NULL DEFAULT NULL;

ALTER TABLE `school_people`
 MODIFY `PersonType`
 ENUM('Student','Faculty','Staff','Guard','Visitor','ROMAC')
 NOT NULL DEFAULT 'Visitor';

SET @idx_exists := (
 SELECT COUNT(1)
 FROM information_schema.statistics
 WHERE table_schema = DATABASE()
 AND table_name = 'school_people'
 AND index_name = 'idx_school_people_name_type'
);

SET @idx_sql := IF(
 @idx_exists = 0,
 'ALTER TABLE `school_people` ADD INDEX `idx_school_people_name_type` (`LastName`, `FirstName`, `PersonType`)',
 'SELECT 1'
);

PREPARE idx_stmt FROM @idx_sql;
EXECUTE idx_stmt;
DEALLOCATE PREPARE idx_stmt;

COMMIT;
