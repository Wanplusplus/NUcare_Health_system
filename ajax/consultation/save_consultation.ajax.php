<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/db_pdo.php';
require_once __DIR__ . '/../../includes/auth_guard.php';

$pdo = getPDO();

function respond(int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

function sanitizeText(?string $s): ?string {
    $s = $s ?? null;
    if ($s === null) return null;
    $s = trim($s);
    return $s === '' ? null : $s;
}

function validateDocUpload(array $file): array {
    // Returns ['ok'=>bool,'error'=>string|null,'savedName'=>string|null]

    if (!isset($file['error']) || (int)$file['error'] !== 0) {
        // No file selected often returns error UPLOAD_ERR_NO_FILE
        if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['ok' => true, 'savedName' => null];
        }
        return ['ok' => false, 'error' => 'File upload error'];
    }

    $maxBytes = 50 * 1024 * 1024; // 50MB
    $size = (int)($file['size'] ?? 0);
    if ($size <= 0) return ['ok' => true, 'savedName' => null];
    if ($size > $maxBytes) return ['ok' => false, 'error' => 'Attachment exceeds 50MB limit'];

    $allowedExt = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'pdf' => 'application/pdf'];

    $origName = (string)($file['name'] ?? '');
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if (!isset($allowedExt[$ext])) {
        return ['ok' => false, 'error' => 'Invalid file type. Allowed: JPG, PNG, PDF'];
    }

    // Security: validate actual mime using finfo
    $tmpPath = (string)($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_file($tmpPath)) {
        return ['ok' => false, 'error' => 'Invalid temporary upload'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmpPath);
    if ($mime === false || $mime !== $allowedExt[$ext]) {
        return ['ok' => false, 'error' => 'File content type does not match allowed types'];
    }

    // Store with a generated name
    $dir = __DIR__ . '/../../uploads/consultations';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $savedName = uniqid('consult_', true) . '.' . $ext;
    $destPath = $dir . DIRECTORY_SEPARATOR . $savedName;

    if (!move_uploaded_file($tmpPath, $destPath)) {
        return ['ok' => false, 'error' => 'Failed to store attachment'];
    }

    return ['ok' => true, 'savedName' => $savedName];
}

// Inputs
$consultationID = isset($_POST['consultation_id']) ? (int)$_POST['consultation_id'] : 0;
$schoolPersonID = isset($_POST['school_person_id']) ? (int)$_POST['school_person_id'] : 0;

if ($consultationID <= 0 || $schoolPersonID <= 0) {
    respond(400, ['ok' => false, 'message' => 'Missing consultation context']);
}

$bloodPressure = sanitizeText($_POST['blood_pressure'] ?? null);
$temperature = isset($_POST['temperature']) && $_POST['temperature'] !== '' ? (float)$_POST['temperature'] : null;
$pulseRate = isset($_POST['pulse_rate']) && $_POST['pulse_rate'] !== '' ? (int)$_POST['pulse_rate'] : null;
$weight = isset($_POST['weight']) && $_POST['weight'] !== '' ? (float)$_POST['weight'] : null;

$chiefComplaint = sanitizeText($_POST['complaint'] ?? null);
$serviceType = sanitizeText($_POST['service_type'] ?? null);
if ($serviceType === null) {
    respond(400, ['ok' => false, 'message' => 'Service type is required']);
}

$consultationStatus = sanitizeText($_POST['consultation_status'] ?? 'Waiting');
$allowedStatus = ['Waiting','Consulting','Completed','Cancelled'];
if (!in_array($consultationStatus, $allowedStatus, true)) {
    $consultationStatus = 'Waiting';
}

$clinicalNotes = sanitizeText($_POST['notes'] ?? null);

// Medicines arrays (optional)
$medNames = $_POST['consultMedName'] ?? $_POST['consultMedName[]'] ?? null;
$medQtys = $_POST['consultMedQty'] ?? $_POST['consultMedQty[]'] ?? null;

// File upload
$upload = $_FILES['consultation_attachment'] ?? null;
$attachmentResult = null;
if (is_array($upload)) {
    $attachmentResult = validateDocUpload($upload);
    if (!$attachmentResult['ok']) {
        respond(400, ['ok' => false, 'message' => $attachmentResult['error']]);
    }
}

try {
    $pdo->beginTransaction();

    // Ensure consultation row belongs to patient
    $chk = $pdo->prepare("SELECT ConsultationID, SchoolPersonID FROM consultation_transactions WHERE ConsultationID = :cid AND SchoolPersonID = :spid FOR UPDATE");
    $chk->execute([':cid' => $consultationID, ':spid' => $schoolPersonID]);
    $exists = $chk->fetch(PDO::FETCH_ASSOC);
    if (!$exists) {
        $pdo->rollBack();
        respond(404, ['ok' => false, 'message' => 'Consultation context not found']);
    }

    // Validate required fields
    if ($chiefComplaint === null) {
        $pdo->rollBack();
        respond(400, ['ok' => false, 'message' => 'Chief complaint is required']);
    }

    // Update consultation row
    $upd = $pdo->prepare("UPDATE consultation_transactions
        SET BloodPressure = :bp,
            Temperature = :temp,
            PulseRate = :pr,
            Weight = :wt,
            ChiefComplaint = :cc,
            ServiceType = :st,
            ConsultationStatus = :cs,
            ClinicalNotes = :cn,
            AttachedDocument = COALESCE(:ad, AttachedDocument)
        WHERE ConsultationID = :cid");

    $upd->execute([
        ':bp' => $bloodPressure,
        ':temp' => $temperature,
        ':pr' => $pulseRate,
        ':wt' => $weight,
        ':cc' => $chiefComplaint,
        ':st' => $serviceType,
        ':cs' => $consultationStatus,
        ':cn' => $clinicalNotes,
        ':ad' => ($attachmentResult && $attachmentResult['savedName'] ? $attachmentResult['savedName'] : null),
        ':cid' => $consultationID,
    ]);

    // Medicines processing (optional)
    // Expect form fields: consultMedName[] and consultMedQty[] as arrays.
    $medNamesArr = $_POST['consultMedName'] ?? null;
    $medQtysArr = $_POST['consultMedQty'] ?? null;
    if (!is_array($medNamesArr) && isset($_POST['consultMedName'])) {
        $medNamesArr = is_array($_POST['consultMedName[]'] ?? null) ? $_POST['consultMedName[]'] : null;
    }
    $medNamesArr = $_POST['consultMedName[]'] ?? $medNamesArr;
    $medQtysArr = $_POST['consultMedQty[]'] ?? $medQtysArr;

    $medNamesArr = is_array($medNamesArr) ? $medNamesArr : [];
    $medQtysArr = is_array($medQtysArr) ? $medQtysArr : [];

    $count = min(count($medNamesArr), count($medQtysArr));
    $dispensedAny = false;

    for ($i = 0; $i < $count; $i++) {
        $name = trim((string)$medNamesArr[$i]);
        $qtyRaw = $medQtysArr[$i];
        $qty = is_numeric($qtyRaw) ? (int)$qtyRaw : 0;

        if ($name === '' || $qty <= 0) continue;

        // Find medicines inventory rows by medicine name
        // NOTE: connects directly with medicine inventory system, but we only have medicines table keyed by MedicineName.
        $invSql = "SELECT mi.InventoryID, mi.Quantity, mi.ExpiryDate, mi.Status, m.MedicineID
                   FROM medicine_inventory mi
                   INNER JOIN medicines m ON m.MedicineID = mi.MedicineID
                   WHERE m.MedicineName = :mname
                   AND mi.Quantity > 0
                   ORDER BY mi.ExpiryDate ASC, mi.InventoryID ASC
                   LIMIT 1";

        $invStmt = $pdo->prepare($invSql);
        $invStmt->execute([':mname' => $name]);
        $inv = $invStmt->fetch(PDO::FETCH_ASSOC);

        if (!$inv) {
            $pdo->rollBack();
            respond(400, ['ok' => false, 'message' => "Insufficient stock or medicine not found: {$name}" ]);
        }

        $inventoryID = (int)$inv['InventoryID'];
        $available = (int)$inv['Quantity'];
        if ($qty > $available) {
            $pdo->rollBack();
            respond(400, ['ok' => false, 'message' => "Requested qty exceeds available stock for {$name}. Available: {$available}" ]);
        }

        $newQty = $available - $qty;
        $expiryDate = $inv['ExpiryDate'];

        // Update inventory qty + status
        $newStatus = 'Available';
        if ($expiryDate !== null && $expiryDate !== '' && strtotime((string)$expiryDate) < time()) {
            $newStatus = 'Expired';
        } elseif ($newQty <= 0) {
            $newStatus = 'Out Of Stock';
        } elseif ($newQty <= 10) {
            $newStatus = 'Low Stock';
        }

        $updInv = $pdo->prepare("UPDATE medicine_inventory SET Quantity = :q, Status = :st WHERE InventoryID = :iid");
        $updInv->execute([':q' => $newQty, ':st' => $newStatus, ':iid' => $inventoryID]);

        // Insert medicine_dispensing row
        // Existing schema uses ClinicTransactionID in medicine_dispensing.
        // For Consultation module, we will store dispensing linked to consultation transaction by mapping to ClinicTransactionID is not possible.
        // To preserve inventory consistency as required, we'll record dispensing with the closest available FK:
        // - We'll also create a record in clinic_transactions? Not requested.
        // Instead: store in medicine_dispensing using ClinicTransactionID=ConsultationID is wrong.
        // Therefore, we will SKIP medicine_dispensing insertion and only update inventory + logs.
        // But your spec says connect directly with medicine inventory system; stock deduction is the core requirement.
        // We'll still insert logs in medicine_inventory_logs.

        $log = $pdo->prepare("INSERT INTO medicine_inventory_logs
            (InventoryID, ActionType, QuantityChanged, PerformedByUserID, Notes)
            VALUES (:iid, 'Dispensed', :qc, :uid, :notes)");

        $performedByUserID = (int)($_SESSION['UserID'] ?? 0);
        $log->execute([
            ':iid' => $inventoryID,
            ':qc' => -$qty,
            ':uid' => $performedByUserID,
            ':notes' => 'Dispensed via Consultation transaction',
        ]);

        $dispensedAny = true;
    }

    $pdo->commit();

    respond(200, ['ok' => true, 'message' => 'Consultation saved successfully']);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    respond(500, ['ok' => false, 'message' => 'Server error']);
}

