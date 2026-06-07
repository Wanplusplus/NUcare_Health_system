 -- Rebuild emergencies table to the new emergency-medicine schema
 -- Run this on the live database after taking a backup.

 ALTER TABLE emergencies
 DROP FOREIGN KEY fk_emergency_person;

 ALTER TABLE emergencies
 ADD COLUMN InventoryID INT NULL AFTER SchoolPersonID,
 ADD COLUMN MedicineID INT NULL AFTER InventoryID,
 ADD COLUMN DispensingID INT NULL AFTER MedicineID,
 ADD COLUMN ClinicTransactionID INT NOT NULL AFTER DispensingID,
 ADD COLUMN TreatmentGiven VARCHAR(255) NULL AFTER ClinicTransactionID;

 ALTER TABLE emergencies
 DROP COLUMN IncidentDate,
 DROP COLUMN IncidentTime,
 DROP COLUMN IncidentLocation,
 DROP COLUMN BP,
 DROP COLUMN RR,
 DROP COLUMN HR,
 DROP COLUMN Temperature,
 DROP COLUMN AmbulanceNo,
 DROP COLUMN TimeDispatched,
 DROP COLUMN TimeArrived;

ALTER TABLE emergencies
 ADD CONSTRAINT fk_emergency_inventory
 FOREIGN KEY (InventoryID)
 REFERENCES medicine_inventory(InventoryID)
 ON DELETE SET NULL
 ON UPDATE CASCADE,
 ADD CONSTRAINT fk_emergency_medicine
 FOREIGN KEY (MedicineID)
 REFERENCES medicines(MedicineID)
 ON DELETE SET NULL
 ON UPDATE CASCADE;

ALTER TABLE medicine_inventory
 MODIFY Status ENUM(
 'Emergency',
 'Available',
 'Low Stock',
 'Out Of Stock',
 'Expired'
 ) DEFAULT 'Available';

UPDATE medicine_inventory mi
INNER JOIN medicines m ON m.MedicineID = mi.MedicineID
SET mi.Status = 'Emergency'
WHERE LOWER(m.MedicineName) = LOWER('Diphenhydramine');

 -- Seed Diphenhydramine as emergency medicine if it does not exist yet.
 INSERT INTO medicines (MedicineName, GenericName, MedicineType, Dosage, Unit, Description)
 SELECT 'Diphenhydramine', 'Diphenhydramine', 'Emergency Medicine', '50 mg/mL', 'ampule', 'Emergency medicine for allergic reactions'
 WHERE NOT EXISTS (
 SELECT 1
 FROM medicines
 WHERE LOWER(MedicineName) = LOWER('Diphenhydramine')
 );

 INSERT INTO medicine_inventory (MedicineID, BatchNumber, Quantity, ExpiryDate, DateReceived, ReorderLevel, Status)
 SELECT m.MedicineID, 'EMERGENCY-STOCK', 10, NULL, CURDATE(), 10, 'Emergency'
 FROM medicines m
 WHERE LOWER(m.MedicineName) = LOWER('Diphenhydramine')
 AND NOT EXISTS (
 SELECT 1
 FROM medicine_inventory mi
 WHERE mi.MedicineID = m.MedicineID
 );

 ALTER TABLE medicine_inventory
MODIFY Status VARCHAR(20);

INSERT INTO medicine_inventory (MedicineID, BatchNumber, Quantity, ExpiryDate, DateReceived, ReorderLevel, Status)
 SELECT m.MedicineID, 'EMERGENCY-STOCK', 10, NULL, CURDATE(), 10, 'Emergency'
 FROM medicines m
 WHERE LOWER(m.MedicineName) = LOWER('Diphenhydramine')
 AND NOT EXISTS (
 SELECT 1
 FROM medicine_inventory mi
 WHERE mi.MedicineID = m.MedicineID
 );
