<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo = require __DIR__ . '/../../config/db_pdo.php';

/* ── helpers ─────────────────────────────────────────────────────────── */
function clean(mixed $v): string { return trim((string)($v ?? '')); }
function cleanFloat(mixed $v): ?float {
    $s = trim((string)($v ?? ''));
    return ($s !== '' && is_numeric($s)) ? (float)$s : null;
}
function cleanInt(mixed $v): ?int {
    $s = trim((string)($v ?? ''));
    return ($s !== '' && is_numeric($s)) ? (int)$s : null;
}
function fail(string $msg, array $extra = []): never {
    echo json_encode(array_merge(['ok' => false, 'message' => $msg], $extra));
    exit;
}

/* ── input ───────────────────────────────────────────────────────────── */
$consultationID = cleanInt($_POST['consultation_id'] ?? '');   // PhysicalExamID
if (!$consultationID || $consultationID <= 0) fail('Invalid consultation ID');

// Consultation details — stored on clinic_transactions (3NF)
$complaint   = clean($_POST['complaint']           ?? '');
$serviceType = clean($_POST['service_type']        ?? '');
$notes       = clean($_POST['notes']               ?? '');
$rawStatus   = clean($_POST['consultation_status'] ?? 'Waiting');

$allowedStatuses = ['Waiting', 'Consulting', 'Completed', 'Cancelled'];
$status = in_array($rawStatus, $allowedStatuses, true) ? $rawStatus : 'Waiting';

if ($complaint === '') fail('Chief complaint is required');
if ($serviceType === '') fail('Service type is required');

// Vitals — stored on physical_examinations
$bp          = clean($_POST['blood_pressure'] ?? '');
$temperature = cleanFloat($_POST['temperature'] ?? '');
$pulseRate   = clean($_POST['pulse_rate']      ?? '');
$weight      = cleanFloat($_POST['weight']     ?? '');
$height      = cleanFloat($_POST['height']     ?? '');

// Medicines dispensed
$medInventoryIDs = array_map('intval', $_POST['med_inventory_id']  ?? []);
$medQtys         = array_map('intval', $_POST['med_qty']           ?? []);
$medInstructions = array_map('trim',   (array)($_POST['med_instructions'] ?? []));

/* ── resolve ClinicTransactionID from PhysicalExamID ─────────────────── */
try {
    $lookupStmt = $pdo->prepare("
        SELECT ClinicTransactionID
        FROM physical_examinations
        WHERE PhysicalExamID = :id
        LIMIT 1
    ");
    $lookupStmt->execute([':id' => $consultationID]);
    $ctid = (int)($lookupStmt->fetchColumn() ?: 0);
} catch (Throwable $e) {
    fail('Lookup failed: ' . $e->getMessage());
}

if ($ctid <= 0) fail('No linked transaction found for this consultation');

/* ── transactional save ──────────────────────────────────────────────── */
try {
    $pdo->beginTransaction();

    // 1. Update physical_examinations — VITALS ONLY
    //    Complaint / ServiceType / Notes / ConsultationStatus live on clinic_transactions (3NF)
    $peStmt = $pdo->prepare("
        UPDATE physical_examinations
        SET
            BloodPressure = :bp,
            Temperature   = :temp,
            PulseRate     = :pulse,
            Weight        = :weight,
            Height        = :height
        WHERE PhysicalExamID = :id
    ");
    $peStmt->execute([
        ':bp'    => $bp        ?: null,
        ':temp'  => $temperature,
        ':pulse' => $pulseRate ?: null,
        ':weight'=> $weight,
        ':height'=> $height,
        ':id'    => $consultationID,
    ]);

    // 2. Update clinic_transactions — complaint / service / notes / status
    $ctStmt = $pdo->prepare("
        UPDATE clinic_transactions
        SET
            ServiceType        = :service,
            Complaint          = :complaint,
            Notes              = :notes,
            ConsultationStatus = :status
        WHERE ClinicTransactionID = :ctid
    ");
    $ctStmt->execute([
        ':service'  => $serviceType,
        ':complaint'=> $complaint,
        ':notes'    => $notes ?: null,
        ':status'   => $status,
        ':ctid'     => $ctid,
    ]);

    // 3. Process medicine dispensing
    $dispensed = [];
    foreach ($medInventoryIDs as $idx => $invID) {
        if ($invID <= 0) continue;

        $qty = max(0, (int)($medQtys[$idx] ?? 0));
        if ($qty <= 0) continue;

        $instructions = $medInstructions[$idx] ?? '';

        // Lock inventory row and check stock
        $lockStmt = $pdo->prepare("
            SELECT InventoryID, Quantity, Status
            FROM medicine_inventory
            WHERE InventoryID = :id
            FOR UPDATE
        ");
        $lockStmt->execute([':id' => $invID]);
        $inv = $lockStmt->fetch(PDO::FETCH_ASSOC);

        if (!$inv) throw new RuntimeException("Inventory ID $invID not found");

        $available = (int)$inv['Quantity'];
        if ($available < $qty) {
            throw new RuntimeException(
                "Insufficient stock for Inventory ID $invID. Available: $available, Requested: $qty"
            );
        }

        $newQty    = $available - $qty;
        $newStatus = computeInventoryStatus($newQty, $inv['Status']);

        $pdo->prepare("
            UPDATE medicine_inventory
            SET Quantity = :qty, Status = :status, UpdatedAt = NOW()
            WHERE InventoryID = :id
        ")->execute([':qty' => $newQty, ':status' => $newStatus, ':id' => $invID]);

        $pdo->prepare("
            INSERT INTO medicine_dispensing
                (ClinicTransactionID, InventoryID, QuantityDispensed, Instructions, DispensedAt)
            VALUES (:ctid, :invid, :qty, :instr, NOW())
        ")->execute([
            ':ctid'  => $ctid,
            ':invid' => $invID,
            ':qty'   => $qty,
            ':instr' => $instructions ?: null,
        ]);

        $dispensed[] = ['inventory_id' => $invID, 'qty' => $qty];
    }

    $pdo->commit();

    echo json_encode([
        'ok'              => true,
        'message'         => 'Consultation saved successfully',
        'transaction_id'  => $ctid,
        'consultation_id' => $consultationID,
        'medicines_given' => count($dispensed),
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode([
        'ok'      => false,
        'message' => $e->getMessage(),
    ]);
}

/* ── helper ──────────────────────────────────────────────────────────── */
function computeInventoryStatus(int $qty, string $currentStatus): string
{
    if (str_contains($currentStatus, 'Expired')) return 'Expired';
    if ($qty <= 0)  return 'Out Of Stock';
    if ($qty <= 10) return 'Low Stock';
    return 'Available';
}