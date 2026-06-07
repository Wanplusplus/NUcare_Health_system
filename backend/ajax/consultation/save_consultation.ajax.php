<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../../backend/includes/audit.php';

$pdo = require __DIR__ . '/../../../database/config/db_pdo.php';

/* ---
 HELPERS
--- */
function clean(mixed $v): string { return trim((string)($v ?? '')); }
function cleanFloat(mixed $v): ?float { $s = trim((string)($v ?? '')); return ($s !== '' && is_numeric($s)) ? (float)$s : null; }
function nullIfEmpty(string $s): ?string { return $s !== '' ? $s : null; }
function fail(string $msg, string $debug = ''): never {
 echo json_encode(['ok' => false, 'message' => $msg, 'debug' => $debug]);
 exit;
}

/* ---
 INPUT
--- */
$consultationID = (int)trim((string)($_POST['consultation_id'] ?? ''));
if ($consultationID <= 0) fail('No active transaction found. Please search a patient first.');

$serviceType = clean($_POST['service_type'] ?? '');
$complaint = clean($_POST['complaint'] ?? '');
$notes = clean($_POST['notes'] ?? '');
$serviceOther = clean($_POST['service_other'] ?? '');
$rawStatus = clean($_POST['consultation_status'] ?? 'Waiting');

$allowedStatuses = ['Waiting', 'Consulting', 'Completed', 'Cancelled'];
$status = in_array($rawStatus, $allowedStatuses, true) ? $rawStatus : 'Waiting';

$resolvedService = ($serviceType === 'Other' && $serviceOther !== '') ? $serviceOther : $serviceType;

// Validation
if ($serviceType === '') fail('Please select a Service Type before saving.');
if (!in_array($serviceType, ['Medical Certificate', 'Physical Examination', 'Dental'], true) && $complaint === '') {
 fail('Chief Complaint is required.');
}

/* -- Vitals
 Primary field names: blood_pressure, temperature, etc. (standard + PE sections via JS remap)
 Fallback field names: pe_blood_pressure, pe_temperature, etc. (defensive double-read)
-- */
$bp = nullIfEmpty(clean($_POST['blood_pressure'] ?? $_POST['pe_blood_pressure'] ?? ''));
$temperature = cleanFloat($_POST['temperature'] ?? $_POST['pe_temperature'] ?? '');
$pulseRate = nullIfEmpty(clean($_POST['pulse_rate'] ?? $_POST['pe_pulse_rate'] ?? ''));
$weight = cleanFloat($_POST['weight'] ?? $_POST['pe_weight'] ?? '');
$height = cleanFloat($_POST['height'] ?? $_POST['pe_height'] ?? '');

/* -- Physical Exam body-system fields -- */
$isPhysExam = ($serviceType === 'Physical Examination');
$examDate = nullIfEmpty(clean($_POST['exam_date'] ?? ''));
$examEars = nullIfEmpty(clean($_POST['examEars'] ?? ''));
$examEyesPupil = nullIfEmpty(clean($_POST['examEyesPupil'] ?? ''));
$examHeart = nullIfEmpty(clean($_POST['examHeart'] ?? ''));
$examNose = nullIfEmpty(clean($_POST['examNose'] ?? ''));
$examThorax = nullIfEmpty(clean($_POST['examThorax'] ?? ''));
$examAbdomen = nullIfEmpty(clean($_POST['examAbdomen'] ?? ''));
$examLungs = nullIfEmpty(clean($_POST['examLungs'] ?? ''));
$examSkin = nullIfEmpty(clean($_POST['examSkin'] ?? ''));
$examExtremities = nullIfEmpty(clean($_POST['examExtremities'] ?? ''));
$examDeformities = nullIfEmpty(clean($_POST['examDeformities'] ?? ''));
$examRemarks = nullIfEmpty(clean($_POST['exam_remarks'] ?? ''));

// CardioClearance ? must be one of the three allowed string values.
// The physical_examinations.CardioClearance column must be ENUM('Fit','Unfit','Pending')
// or VARCHAR. If your column is still INT/TINYINT, run the migration SQL below.
$rawClearance = clean($_POST['exam_cardio_clearance'] ?? '');
$allowedClearance = ['Fit', 'Unfit', 'Pending'];
$cardioClearance = in_array($rawClearance, $allowedClearance, true) ? $rawClearance : null;

if ($isPhysExam && !$examDate) fail('Examination date is required.');
if ($isPhysExam && !$cardioClearance) fail('Please select a Medical Clearance result (Fit / Unfit / Pending).');

/* -- Attachment category (optional POST field) -- */
$attachCategory = clean($_POST['attachment_category'] ?? 'Other');
$allowedCategories = ['Lab Result', 'Medical Certificate', 'X-Ray / Imaging', 'Prescription', 'Referral', 'Dental Form', 'School Form', 'Other'];
if (!in_array($attachCategory, $allowedCategories, true)) $attachCategory = 'Other';
$attachNotes = nullIfEmpty(clean($_POST['attachment_notes'] ?? ''));

/* -- Medical Certificate extra fields (only used when category = Medical Certificate) -- */
$mcCertificateType = nullIfEmpty(clean($_POST['mc_certificate_type'] ?? ''));
$mcValidUntil = nullIfEmpty(clean($_POST['mc_valid_until'] ?? ''));
$issuedByMedProfID = null; // TODO: resolve MedProfID from session UserID once medical_professionals.UserID FK is set up

/* -- Medicines -- */
$medInventoryIDs = array_map('intval', (array)($_POST['med_inventory_id'] ?? []));
$medQtys = array_map('intval', (array)($_POST['med_qty'] ?? []));
$medInstructions = array_map('trim', (array)($_POST['med_instructions'] ?? []));

/* ---
 RESOLVE ClinicTransactionID from PhysicalExamID
--- */
try {
 $row = $pdo->prepare("
 SELECT ClinicTransactionID FROM physical_examinations
 WHERE PhysicalExamID = :id LIMIT 1
 ");
 $row->execute([':id' => $consultationID]);
 $ctid = (int)($row->fetchColumn() ?: 0);
} catch (Throwable $e) {
 fail('Database lookup failed.', $e->getMessage());
}

if ($ctid <= 0) fail(
 'Transaction not initialised. Please clear the form, search the patient again, and retry.',
 "PhysicalExamID $consultationID has no linked ClinicTransactionID"
);

/* ---
 DETECT which columns actually exist in physical_examinations
--- */
try {
 $colStmt = $pdo->query("SHOW COLUMNS FROM physical_examinations");
 $existingCols = array_column($colStmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
} catch (Throwable $e) {
 $existingCols = ['PhysicalExamID','ClinicTransactionID','ExamDate',
 'BloodPressure','Temperature','PulseRate','Weight','Height'];
}
$hasCol = fn(string $c) => in_array($c, $existingCols, true);

/* ---
 TRANSACTIONAL SAVE
--- */

// Pre-process file BEFORE opening transaction so we don't lock the DB while doing I/O
$uploadedFileMeta = null;
$movedFilePath = null;

if (!empty($_FILES['consultation_attachment']['name'])) {
 $file = $_FILES['consultation_attachment'];

 $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
 $maxSize = 50 * 1024 * 1024; // 50 MB

 if ($file['error'] !== UPLOAD_ERR_OK) {
 fail('File upload failed with error code: ' . $file['error']);
 }
 if (!in_array($file['type'], $allowedTypes, true)) {
 fail('Invalid file type. Only JPG, PNG, and PDF files are allowed.');
 }
 if ($file['size'] > $maxSize) {
 fail('File too large. Maximum size allowed is 50 MB.');
 }

 $extMap = ['image/jpeg' => '.jpg', 'image/png' => '.png', 'application/pdf' => '.pdf'];
 $fileExt = $extMap[$file['type']];

 $storedName = uniqid('consult_', true) . $fileExt;
 $uploadDir = __DIR__ . '/../../uploads/consultations/';

 if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
 fail('Server error: cannot create upload directory.');
 }

 $fullPath = $uploadDir . $storedName;

 if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
 fail('Failed to move uploaded file. Check server write permissions.');
 }

 $movedFilePath = $fullPath;
 $uploadedFileMeta = [
 'original_name' => $file['name'],
 'stored_name' => $storedName,
 'file_path' => 'uploads/consultations/' . $storedName,
 'file_type' => $file['type'],
 'file_size' => $file['size'],
 ];
}

// Prepare actor for audits
$actorUserId = isset($_SESSION['UserID']) ? (int)$_SESSION['UserID'] : null;
$actorSchoolPersonId = isset($_SESSION['SchoolPersonID']) ? (int)$_SESSION['SchoolPersonID'] : null;
$patientName = (string)($_SESSION['patient_name'] ?? 'Patient');

try {
 $pdo->beginTransaction();

 /* -- 1. UPDATE clinic_transactions -- */
 $pdo->prepare("
 UPDATE clinic_transactions
 SET ServiceType = :service,
 Complaint = :complaint,
 Notes = :notes,
 ConsultationStatus = :status,
 UpdatedAt = NOW()
 WHERE ClinicTransactionID = :ctid
 ")->execute([
 ':service' => $resolvedService,
 ':complaint' => nullIfEmpty($complaint),
 ':notes' => nullIfEmpty($notes),
 ':status' => $status,
 ':ctid' => $ctid,
 ]);

 /* -- 2. UPDATE physical_examinations ? vitals -- */
 $vitalsSet = [];
 $vitalsParams = [':id' => $consultationID];

 if ($hasCol('BloodPressure')) { $vitalsSet[] = 'BloodPressure = :bp'; $vitalsParams[':bp'] = $bp; }
 if ($hasCol('Temperature')) { $vitalsSet[] = 'Temperature = :temp'; $vitalsParams[':temp'] = $temperature; }
 if ($hasCol('PulseRate')) { $vitalsSet[] = 'PulseRate = :pulse'; $vitalsParams[':pulse'] = $pulseRate; }
 if ($hasCol('Weight')) { $vitalsSet[] = 'Weight = :weight'; $vitalsParams[':weight'] = $weight; }
 if ($hasCol('Height')) { $vitalsSet[] = 'Height = :height'; $vitalsParams[':height'] = $height; }

 if (!empty($vitalsSet)) {
 $pdo->prepare("UPDATE physical_examinations SET " . implode(', ', $vitalsSet) . " WHERE PhysicalExamID = :id")
 ->execute($vitalsParams);
 }

 /* -- 3. UPDATE physical_examinations ? PE body systems -- */
 if ($isPhysExam) {
 $peSet = [];
 $peParams = [':id' => $consultationID];

 $peFields = [
 'ExamDate' => [':examDate', $examDate],
 'Ears' => [':ears', $examEars],
 'EyesPupil' => [':eyes', $examEyesPupil],
 'Heart' => [':heart', $examHeart],
 'Nose' => [':nose', $examNose],
 'Thorax' => [':thorax', $examThorax],
 'Abdomen' => [':abdomen', $examAbdomen],
 'Lungs' => [':lungs', $examLungs],
 'Skin' => [':skin', $examSkin],
 'Extremities' => [':extremities', $examExtremities],
 'Deformities' => [':deformities', $examDeformities],
 'Remarks' => [':remarks', $examRemarks],
 'CardioClearance' => [':clearance', $cardioClearance],
 ];

 foreach ($peFields as $col => [$param, $value]) {
 if ($hasCol($col)) {
 $peSet[] = "$col = $param";
 $peParams[$param] = $value;
 }
 }

 if (!empty($peSet)) {
 $pdo->prepare("UPDATE physical_examinations SET " . implode(', ', $peSet) . " WHERE PhysicalExamID = :id")
 ->execute($peParams);
 }
 }

 /* -- 4. Medicines ? stock check ? deduct ? insert dispensing -- */
 $dispensed = [];
 foreach ($medInventoryIDs as $idx => $invID) {
 if ($invID <= 0) {
 throw new RuntimeException('Medicine row ' . ($idx + 1) . ': please select a medicine from the search dropdown (do not type manually).');
 }
 $qty = max(0, (int)($medQtys[$idx] ?? 0));
 if ($qty <= 0) continue;

 $lockStmt = $pdo->prepare("
 SELECT InventoryID, Quantity, Status FROM medicine_inventory
 WHERE InventoryID = :id FOR UPDATE
 ");
 $lockStmt->execute([':id' => $invID]);
 $inv = $lockStmt->fetch(PDO::FETCH_ASSOC);

 if (!$inv) throw new RuntimeException("Medicine inventory ID $invID not found.");

 $available = (int)$inv['Quantity'];
 if ($available < $qty) {
 throw new RuntimeException("Not enough stock for inventory #$invID. Available: $available, requested: $qty.");
 }

 $newQty = $available - $qty;
 $newStatus = computeStatus($newQty, $inv['Status']);

 $pdo->prepare("UPDATE medicine_inventory SET Quantity=:q, Status=:s, UpdatedAt=NOW() WHERE InventoryID=:id")
 ->execute([':q' => $newQty, ':s' => $newStatus, ':id' => $invID]);

 $pdo->prepare("
 INSERT INTO medicine_dispensing (ClinicTransactionID, InventoryID, QuantityDispensed, Instructions, DispensedAt)
 VALUES (:ctid, :invid, :qty, :instr, NOW())
 ")->execute([
 ':ctid' => $ctid,
 ':invid' => $invID,
 ':qty' => $qty,
 ':instr' => nullIfEmpty($medInstructions[$idx] ?? ''),
 ]);

 $dispensed[] = $invID;
 }

 /* -- 5. Insert into consultation_attachments -- */
 $attachmentID = null;
 if ($uploadedFileMeta !== null) {
 $uploadedBy = (int)($_SESSION['UserID'] ?? 0) ?: null;

 $pdo->prepare("
 INSERT INTO consultation_attachments
 (ClinicTransactionID, UploadedBy, FileName, StoredName, FilePath, FileType, FileSizeBytes, AttachmentCategory, Notes, CreatedAt)
 VALUES
 (:ctid, :upby, :fname, :sname, :fpath, :ftype, :fsize, :fcat, :fnotes, NOW())
 ")->execute([
 ':ctid' => $ctid,
 ':upby' => $uploadedBy,
 ':fname' => $uploadedFileMeta['original_name'],
 ':sname' => $uploadedFileMeta['stored_name'],
 ':fpath' => $uploadedFileMeta['file_path'],
 ':ftype' => $uploadedFileMeta['file_type'],
 ':fsize' => $uploadedFileMeta['file_size'],
 ':fcat' => $attachCategory,
 ':fnotes' => $attachNotes,
 ]);
 $attachmentID = (int)$pdo->lastInsertId();
 }

 /* -- 6. Medical Certificate ? insert into medical_certificates -- */
 if ($attachCategory === 'Medical Certificate' && $attachmentID !== null) {
 $pdo->prepare("
 INSERT INTO medical_certificates
 (ClinicTransactionID, AttachmentID, IssuedByMedProfID, CertificateType, ValidUntil, CreatedAt)
 VALUES
 (:ctid, :attid, :medprof, :certtype, :validuntil, NOW())
 ON DUPLICATE KEY UPDATE
 AttachmentID = VALUES(AttachmentID),
 IssuedByMedProfID = VALUES(IssuedByMedProfID),
 CertificateType = VALUES(CertificateType),
 ValidUntil = VALUES(ValidUntil)
 ")->execute([
 ':ctid' => $ctid,
 ':attid' => $attachmentID,
 ':medprof' => $issuedByMedProfID,
 ':certtype' => $mcCertificateType ?? 'Medical Certificate',
 ':validuntil'=> $mcValidUntil,
 ]);
 }

 /* -- 7. Dental ? insert/update dental_transactions -- */
 if ($serviceType === 'Dental') {
 $dentalInventoryID = !empty($dispensed) ? $dispensed[0] : null;
 $dentalAttachmentID = $attachmentID ?: null;
 $dentalAttachCat = $dentalAttachmentID ? $attachCategory : null;

 $pdo->prepare("
 INSERT INTO dental_transactions
 (ClinicTransactionID, InventoryID, AttachmentID, AttachmentCategory)
 VALUES
 (:ctid, :invid, :attid, :attcat)
 ON DUPLICATE KEY UPDATE
 InventoryID = VALUES(InventoryID),
 AttachmentID = VALUES(AttachmentID),
 AttachmentCategory = VALUES(AttachmentCategory)
 ")->execute([
 ':ctid' => $ctid,
 ':invid' => $dentalInventoryID,
 ':attid' => $dentalAttachmentID,
 ':attcat' => $dentalAttachCat,
 ]);
 }

 /* -- 8. Emergency record - only for emergency / first aid services -- */
 if (preg_match('/emergency|first aid/i', $serviceType)) {
 $emergencyInventoryID = null;
 $emergencyMedicineID = null;
 $emergencyDispensingID = null;

 foreach ($medInventoryIDs as $invID) {
 if ($invID <= 0) continue;

 $matchStmt = $pdo->prepare("
 SELECT
 md.DispensingID,
 mi.InventoryID,
 mi.MedicineID,
 m.MedicineName,
 m.GenericName
 FROM medicine_dispensing md
 INNER JOIN medicine_inventory mi
 ON mi.InventoryID = md.InventoryID
 INNER JOIN medicines m
 ON m.MedicineID = mi.MedicineID
 WHERE md.ClinicTransactionID = :ctid
 AND md.InventoryID = :inv
 ORDER BY md.DispensedAt DESC, md.DispensingID DESC
 LIMIT 1
 ");
 $matchStmt->execute([':ctid' => $ctid, ':inv' => $invID]);
 $match = $matchStmt->fetch(PDO::FETCH_ASSOC);

 if (!$match) {
 continue;
 }

 $name = strtolower(trim((string)($match['MedicineName'] ?? '')));
 $generic = strtolower(trim((string)($match['GenericName'] ?? '')));
 $isDiphenhydramine = str_contains($name, 'diphenhydramine') || str_contains($generic, 'diphenhydramine');

 if ($isDiphenhydramine || $emergencyInventoryID === null) {
 $emergencyInventoryID = (int)($match['InventoryID'] ?? 0);
 $emergencyMedicineID = (int)($match['MedicineID'] ?? 0);
 $emergencyDispensingID = (int)($match['DispensingID'] ?? 0);

 if ($isDiphenhydramine) {
 break;
 }
 }
 }

 if ($emergencyInventoryID !== null && $emergencyMedicineID !== null && $emergencyDispensingID !== null) {
 $spRow = $pdo->prepare("
 SELECT SchoolPersonID
 FROM clinic_transactions
 WHERE ClinicTransactionID = :ctid
 LIMIT 1
 ");
 $spRow->execute([':ctid' => $ctid]);
 $clinicSchoolPersonID = (int)($spRow->fetchColumn() ?: 0);

 if ($clinicSchoolPersonID > 0) {
 $treatmentGiven = $notes !== '' ? $notes : ($complaint !== '' ? $complaint : $resolvedService);

 $pdo->prepare("
 INSERT INTO emergencies
 (SchoolPersonID, InventoryID, MedicineID, DispensingID, ClinicTransactionID, TreatmentGiven)
 VALUES
 (:spid, :inv, :med, :disp, :ctid, :treat)
 ")->execute([
 ':spid' => $clinicSchoolPersonID,
 ':inv' => $emergencyInventoryID,
 ':med' => $emergencyMedicineID,
 ':disp' => $emergencyDispensingID,
 ':ctid' => $ctid,
 ':treat' => $treatmentGiven,
 ]);
 }
 }
 }

 $pdo->commit();

 // Audit: saved/updated + completed (based on final ConsultationStatus)
 auditLog(
 $actorUserId,
 $actorSchoolPersonId,
 'Saved consultation for ' . $patientName,
 'Consultation',
 null,
 'Saved consultation for ' . $patientName . ' (TransactionID ' . (string)$ctid . ', Service: ' . (string)$resolvedService . ')',
 null
 );

 // ?Updated consultation ?? (when not first save, status changes, etc.)
 auditLog(
 $actorUserId,
 $actorSchoolPersonId,
 'Updated consultation for ' . $patientName,
 'Consultation',
 null,
 'Updated consultation for ' . $patientName . ' (TransactionID ' . (string)$ctid . ', Status: ' . (string)$status . ')',
 null
 );

 if ($status === 'Completed') {
 auditLog(
 $actorUserId,
 $actorSchoolPersonId,
 'Completed consultation for ' . $patientName,
 'Consultation',
 null,
 'Marked consultation as Completed for ' . $patientName . ' (TransactionID ' . (string)$ctid . ')',
 null
 );
 }

 echo json_encode([
 'ok' => true,
 'message' => 'Consultation saved successfully.',
 'transaction_id' => $ctid,
 'consultation_id' => $consultationID,
 'service_type' => $resolvedService,
 'medicines_given' => count($dispensed),
 'attachment_id' => $attachmentID,
 ]);

} catch (Throwable $e) {
 if ($pdo->inTransaction()) $pdo->rollBack();

 if ($movedFilePath && file_exists($movedFilePath)) {
 @unlink($movedFilePath);
 }

 fail($e->getMessage());
}

function computeStatus(int $qty, string $current): string {
 if (str_contains($current, 'Emergency')) return 'Emergency';
 if (str_contains($current, 'Expired')) return 'Expired';
 if ($qty <= 0) return 'Out Of Stock';
 if ($qty <= 10) return 'Low Stock';
 return 'Available';
}





