<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo = require __DIR__ . '/../../config/db_pdo.php';

/* ══════════════════════════════════════════════════════════════
   HELPERS
══════════════════════════════════════════════════════════════ */
function clean(mixed $v): string         { return trim((string)($v ?? '')); }
function cleanFloat(mixed $v): ?float    { $s = trim((string)($v ?? '')); return ($s !== '' && is_numeric($s)) ? (float)$s : null; }
function nullIfEmpty(string $s): ?string { return $s !== '' ? $s : null; }
function fail(string $msg, string $debug = ''): never {
    echo json_encode(['ok' => false, 'message' => $msg, 'debug' => $debug]);
    exit;
}

/* ══════════════════════════════════════════════════════════════
   INPUT
══════════════════════════════════════════════════════════════ */
$consultationID = (int)trim((string)($_POST['consultation_id'] ?? ''));
if ($consultationID <= 0) fail('No active transaction found. Please search a patient first.');

$serviceType  = clean($_POST['service_type']        ?? '');
$complaint    = clean($_POST['complaint']            ?? '');
$notes        = clean($_POST['notes']               ?? '');
$serviceOther = clean($_POST['service_other']        ?? '');
$rawStatus    = clean($_POST['consultation_status']  ?? 'Waiting');

$allowedStatuses = ['Waiting', 'Consulting', 'Completed', 'Cancelled'];
$status = in_array($rawStatus, $allowedStatuses, true) ? $rawStatus : 'Waiting';

$resolvedService = ($serviceType === 'Other' && $serviceOther !== '') ? $serviceOther : $serviceType;

// Validation
if ($serviceType === '') fail('Please select a Service Type before saving.');
if (!in_array($serviceType, ['Medical Certificate', 'Physical Examination'], true) && $complaint === '') {
    fail('Chief Complaint is required.');
}

/* ── Vitals
   Primary field names: blood_pressure, temperature, etc. (standard + PE sections via JS remap)
   Fallback field names: pe_blood_pressure, pe_temperature, etc. (defensive double-read)
── */
$bp          = nullIfEmpty(clean($_POST['blood_pressure']    ?? $_POST['pe_blood_pressure'] ?? ''));
$temperature = cleanFloat($_POST['temperature']              ?? $_POST['pe_temperature']    ?? '');
$pulseRate   = nullIfEmpty(clean($_POST['pulse_rate']        ?? $_POST['pe_pulse_rate']     ?? ''));
$weight      = cleanFloat($_POST['weight']                   ?? $_POST['pe_weight']         ?? '');
$height      = cleanFloat($_POST['height']                   ?? $_POST['pe_height']         ?? '');

/* ── Physical Exam body-system fields ── */
$isPhysExam      = ($serviceType === 'Physical Examination');
$examDate        = nullIfEmpty(clean($_POST['exam_date']              ?? ''));
$examEars        = nullIfEmpty(clean($_POST['examEars']               ?? ''));
$examEyesPupil   = nullIfEmpty(clean($_POST['examEyesPupil']          ?? ''));
$examHeart       = nullIfEmpty(clean($_POST['examHeart']              ?? ''));
$examNose        = nullIfEmpty(clean($_POST['examNose']               ?? ''));
$examThorax      = nullIfEmpty(clean($_POST['examThorax']             ?? ''));
$examAbdomen     = nullIfEmpty(clean($_POST['examAbdomen']            ?? ''));
$examLungs       = nullIfEmpty(clean($_POST['examLungs']              ?? ''));
$examSkin        = nullIfEmpty(clean($_POST['examSkin']               ?? ''));
$examExtremities = nullIfEmpty(clean($_POST['examExtremities']        ?? ''));
$examDeformities = nullIfEmpty(clean($_POST['examDeformities']        ?? ''));
$examRemarks     = nullIfEmpty(clean($_POST['exam_remarks']           ?? ''));

// CardioClearance — must be one of the three allowed string values.
// The physical_examinations.CardioClearance column must be ENUM('Fit','Unfit','Pending')
// or VARCHAR. If your column is still INT/TINYINT, run the migration SQL below.
$rawClearance    = clean($_POST['exam_cardio_clearance'] ?? '');
$allowedClearance = ['Fit', 'Unfit', 'Pending'];
$cardioClearance = in_array($rawClearance, $allowedClearance, true) ? $rawClearance : null;

if ($isPhysExam && !$examDate)        fail('Examination date is required.');
if ($isPhysExam && !$cardioClearance) fail('Please select a Medical Clearance result (Fit / Unfit / Pending).');

/* ── Attachment category (optional POST field) ── */
$attachCategory = clean($_POST['attachment_category'] ?? 'Other');
$allowedCategories = ['Lab Result', 'Medical Certificate', 'Referral', 'X-Ray', 'Prescription', 'Other'];
if (!in_array($attachCategory, $allowedCategories, true)) $attachCategory = 'Other';
$attachNotes = nullIfEmpty(clean($_POST['attachment_notes'] ?? ''));

/* ── Medicines ── */
$medInventoryIDs = array_map('intval', (array)($_POST['med_inventory_id']  ?? []));
$medQtys         = array_map('intval', (array)($_POST['med_qty']           ?? []));
$medInstructions = array_map('trim',   (array)($_POST['med_instructions']  ?? []));

/* ══════════════════════════════════════════════════════════════
   RESOLVE ClinicTransactionID from PhysicalExamID
══════════════════════════════════════════════════════════════ */
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

/* ══════════════════════════════════════════════════════════════
   DETECT which columns actually exist in physical_examinations
══════════════════════════════════════════════════════════════ */
try {
    $colStmt = $pdo->query("SHOW COLUMNS FROM physical_examinations");
    $existingCols = array_column($colStmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
} catch (Throwable $e) {
    $existingCols = ['PhysicalExamID','ClinicTransactionID','ExamDate',
                     'BloodPressure','Temperature','PulseRate','Weight','Height'];
}
$hasCol = fn(string $c) => in_array($c, $existingCols, true);

/* ══════════════════════════════════════════════════════════════
   TRANSACTIONAL SAVE
══════════════════════════════════════════════════════════════ */

// Pre-process file BEFORE opening transaction so we don't lock the DB while doing I/O
$uploadedFileMeta = null;
$movedFilePath    = null;

if (!empty($_FILES['consultation_attachment']['name'])) {
    $file = $_FILES['consultation_attachment'];

    $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    $maxSize      = 50 * 1024 * 1024; // 50 MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        fail('File upload failed with error code: ' . $file['error']);
    }
    if (!in_array($file['type'], $allowedTypes, true)) {
        fail('Invalid file type. Only JPG, PNG, and PDF files are allowed.');
    }
    if ($file['size'] > $maxSize) {
        fail('File too large. Maximum size allowed is 50 MB.');
    }

    $extMap  = ['image/jpeg' => '.jpg', 'image/png' => '.png', 'application/pdf' => '.pdf'];
    $fileExt = $extMap[$file['type']];

    $storedName = uniqid('consult_', true) . $fileExt;
    $uploadDir  = __DIR__ . '/../../uploads/consultations/';

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        fail('Server error: cannot create upload directory.');
    }

    $fullPath = $uploadDir . $storedName;

    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        fail('Failed to move uploaded file. Check server write permissions.');
    }

    $movedFilePath    = $fullPath;
    $uploadedFileMeta = [
        'original_name' => $file['name'],
        'stored_name'   => $storedName,
        'file_path'     => 'uploads/consultations/' . $storedName,
        'file_type'     => $file['type'],
        'file_size'     => $file['size'],
    ];
}

try {
    $pdo->beginTransaction();

    /* ── 1. UPDATE clinic_transactions ── */
    $pdo->prepare("
        UPDATE clinic_transactions
        SET ServiceType        = :service,
            Complaint          = :complaint,
            Notes              = :notes,
            ConsultationStatus = :status,
            UpdatedAt          = NOW()
        WHERE ClinicTransactionID = :ctid
    ")->execute([
        ':service'   => $resolvedService,
        ':complaint' => nullIfEmpty($complaint),
        ':notes'     => nullIfEmpty($notes),
        ':status'    => $status,
        ':ctid'      => $ctid,
    ]);

    /* ── 2. UPDATE physical_examinations — vitals ── */
    $vitalsSet    = [];
    $vitalsParams = [':id' => $consultationID];

    if ($hasCol('BloodPressure')) { $vitalsSet[] = 'BloodPressure = :bp';     $vitalsParams[':bp']     = $bp; }
    if ($hasCol('Temperature'))   { $vitalsSet[] = 'Temperature   = :temp';   $vitalsParams[':temp']   = $temperature; }
    if ($hasCol('PulseRate'))     { $vitalsSet[] = 'PulseRate     = :pulse';  $vitalsParams[':pulse']  = $pulseRate; }
    if ($hasCol('Weight'))        { $vitalsSet[] = 'Weight        = :weight'; $vitalsParams[':weight'] = $weight; }
    if ($hasCol('Height'))        { $vitalsSet[] = 'Height        = :height'; $vitalsParams[':height'] = $height; }

    if (!empty($vitalsSet)) {
        $pdo->prepare("UPDATE physical_examinations SET " . implode(', ', $vitalsSet) . " WHERE PhysicalExamID = :id")
            ->execute($vitalsParams);
    }

    /* ── 3. UPDATE physical_examinations — PE body systems ── */
    if ($isPhysExam) {
        $peSet    = [];
        $peParams = [':id' => $consultationID];

        $peFields = [
            'ExamDate'        => [':examDate',    $examDate],
            'Ears'            => [':ears',         $examEars],
            'EyesPupil'       => [':eyes',         $examEyesPupil],
            'Heart'           => [':heart',        $examHeart],
            'Nose'            => [':nose',         $examNose],
            'Thorax'          => [':thorax',       $examThorax],
            'Abdomen'         => [':abdomen',      $examAbdomen],
            'Lungs'           => [':lungs',        $examLungs],
            'Skin'            => [':skin',         $examSkin],
            'Extremities'     => [':extremities',  $examExtremities],
            'Deformities'     => [':deformities',  $examDeformities],
            'Remarks'         => [':remarks',      $examRemarks],
            'CardioClearance' => [':clearance',    $cardioClearance],
        ];

        foreach ($peFields as $col => [$param, $value]) {
            if ($hasCol($col)) {
                $peSet[]          = "$col = $param";
                $peParams[$param] = $value;
            }
        }

        if (!empty($peSet)) {
            $pdo->prepare("UPDATE physical_examinations SET " . implode(', ', $peSet) . " WHERE PhysicalExamID = :id")
                ->execute($peParams);
        }
    }

    /* ── 4. Medicines — stock check → deduct → insert dispensing ── */
    $dispensed = [];
    foreach ($medInventoryIDs as $idx => $invID) {
        if ($invID <= 0) {
            // Hidden input was never set — user typed a name but never clicked a dropdown result
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

        $newQty    = $available - $qty;
        $newStatus = computeStatus($newQty, $inv['Status']);

        $pdo->prepare("UPDATE medicine_inventory SET Quantity=:q, Status=:s, UpdatedAt=NOW() WHERE InventoryID=:id")
            ->execute([':q' => $newQty, ':s' => $newStatus, ':id' => $invID]);

        $pdo->prepare("
            INSERT INTO medicine_dispensing (ClinicTransactionID, InventoryID, QuantityDispensed, Instructions, DispensedAt)
            VALUES (:ctid, :invid, :qty, :instr, NOW())
        ")->execute([
            ':ctid'  => $ctid,
            ':invid' => $invID,
            ':qty'   => $qty,
            ':instr' => nullIfEmpty($medInstructions[$idx] ?? ''),
        ]);

        $dispensed[] = $invID;
    }

    /* ── 5. Insert into consultation_attachments (3NF — replaces attachment_path column) ── */
    $attachmentID = null;
    if ($uploadedFileMeta !== null) {
        $uploadedBy = (int)($_SESSION['UserID'] ?? 0) ?: null;

        $pdo->prepare("
            INSERT INTO consultation_attachments
                (ClinicTransactionID, UploadedBy, FileName, StoredName, FilePath, FileType, FileSizeBytes, AttachmentCategory, Notes, CreatedAt)
            VALUES
                (:ctid, :upby, :fname, :sname, :fpath, :ftype, :fsize, :fcat, :fnotes, NOW())
        ")->execute([
            ':ctid'   => $ctid,
            ':upby'   => $uploadedBy,
            ':fname'  => $uploadedFileMeta['original_name'],
            ':sname'  => $uploadedFileMeta['stored_name'],
            ':fpath'  => $uploadedFileMeta['file_path'],
            ':ftype'  => $uploadedFileMeta['file_type'],
            ':fsize'  => $uploadedFileMeta['file_size'],
            ':fcat'   => $attachCategory,
            ':fnotes' => $attachNotes,
        ]);
        $attachmentID = (int)$pdo->lastInsertId();
    }

    $pdo->commit();

    echo json_encode([
        'ok'              => true,
        'message'         => 'Consultation saved successfully.',
        'transaction_id'  => $ctid,
        'consultation_id' => $consultationID,
        'service_type'    => $resolvedService,
        'medicines_given' => count($dispensed),
        'attachment_id'   => $attachmentID,
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();

    // Orphan cleanup — delete the file we moved before the transaction failed
    if ($movedFilePath && file_exists($movedFilePath)) {
        @unlink($movedFilePath);
    }

    fail($e->getMessage());
}

function computeStatus(int $qty, string $current): string {
    if (str_contains($current, 'Expired')) return 'Expired';
    if ($qty <= 0)  return 'Out Of Stock';
    if ($qty <= 10) return 'Low Stock';
    return 'Available';
}