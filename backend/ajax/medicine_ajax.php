<?php
declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (session_status() === PHP_SESSION_NONE) {
 session_start();
}

// Auth check - return JSON instead of redirecting (redirect breaks AJAX)
if (!isset($_SESSION['patient_id']) && !isset($_SESSION['UserID'])) {
 header('Content-Type: application/json; charset=utf-8');
 http_response_code(401);
 echo json_encode(['success' => false, 'message' => 'Unauthorized']);
 exit;
}

require_once __DIR__ . '/../../backend/includes/audit.php';
require_once __DIR__ . '/../../database/config/db.php';

$conn = $conn ?? null;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
 http_response_code(405);
 echo json_encode(['success' => false, 'message' => 'Invalid request method']);
 exit;
}

function respond(array $payload, int $httpCode = 200): void
{
 http_response_code($httpCode);
 echo json_encode($payload);
 exit;
}

function clean_text(mixed $value): string
{
 return trim((string)($value ?? ''));
}

function parse_date(?string $value): ?string
{
 $value = clean_text($value);
 if ($value === '') return null;
 $timestamp = strtotime($value);
 if ($timestamp === false) return null;
 return date('Y-m-d', $timestamp);
}

function compute_inventory_status(int $quantity, ?string $expiryDate, int $reorderLevel, string $medicineType = ''): string
{
 if (stripos($medicineType, 'emergency') !== false) {
 return 'Emergency';
 }
 if ($quantity <= 0) return 'Out Of Stock';
 if ($expiryDate === null) return $quantity <= $reorderLevel ? 'Low Stock' : 'Available';

 $today = new DateTimeImmutable('today');
 $expiry = DateTimeImmutable::createFromFormat('Y-m-d', $expiryDate);

 if ($expiry instanceof DateTimeImmutable && $expiry < $today) return 'Expired';
 if ($quantity <= $reorderLevel) return 'Low Stock';
 return 'Available';
}

function compute_display_status(int $quantity, ?string $expiryDate, int $reorderLevel, string $storedStatus = ''): string
{
 if ($storedStatus === 'Emergency') return 'Emergency';
 if ($quantity <= 0) return 'Out Of Stock';
 if ($expiryDate === null) return $quantity <= $reorderLevel ? 'Low Stock' : 'Available';

 $today = new DateTimeImmutable('today');
 $expiry = DateTimeImmutable::createFromFormat('Y-m-d', $expiryDate);

 if ($expiry instanceof DateTimeImmutable) {
 if ($expiry < $today) return 'Expired';
 $days = (int)$today->diff($expiry)->format('%r%a');
 if ($days <= 30) return 'Near Expiry';
 }

 if ($quantity <= $reorderLevel) return 'Low Stock';
 return 'Available';
}

function current_user_id(): ?int
{
 foreach (['UserID', 'user_id', 'userId'] as $key) {
 if (isset($_SESSION[$key]) && is_numeric($_SESSION[$key])) {
 return (int)$_SESSION[$key];
 }
 }
 return null;
}

function build_payload(): array
{
 $medicineId = isset($_POST['medicine_id']) && is_numeric($_POST['medicine_id']) ? (int)$_POST['medicine_id'] : null;
 $inventoryId = isset($_POST['inventory_id']) && is_numeric($_POST['inventory_id']) ? (int)$_POST['inventory_id'] : null;

 $action = clean_text($_POST['action'] ?? 'store');
 if (!in_array($action, ['store', 'update', 'list', 'delete'], true)) {
 $action = 'store';
 }

 return [
 'action' => $action,
 'medicine_id' => $medicineId,
 'inventory_id' => $inventoryId,
 'medicine_name' => clean_text($_POST['medicine_name'] ?? null),
 'generic_name' => clean_text($_POST['generic_name'] ?? null),
 'medicine_type' => clean_text($_POST['category'] ?? null),
 'dosage' => clean_text($_POST['dosage'] ?? null),
 'unit' => clean_text($_POST['unit'] ?? null),
 'description' => clean_text($_POST['description'] ?? null),
 'batch_number' => clean_text($_POST['batch_code'] ?? null),
 'quantity' => isset($_POST['quantity']) && $_POST['quantity'] !== '' ? (int)$_POST['quantity'] : null,
 'expiry_date' => parse_date($_POST['expiration_date'] ?? null),
 'date_received' => parse_date($_POST['date_received'] ?? null) ?? date('Y-m-d'),
 'reorder_level' => isset($_POST['reorder_level']) && $_POST['reorder_level'] !== '' ? max(0, (int)$_POST['reorder_level']) : 10,
 ];
}

function fetch_medicine_rows(mysqli $conn): array
{
 $sql = "
 SELECT
 m.MedicineID AS medicine_id,
 m.MedicineName AS medicine_name,
 m.GenericName AS generic_name,
 m.MedicineType AS medicine_type,
 m.Dosage AS dosage,
 m.Unit AS unit,
 m.Description AS description,
 m.CreatedAt AS medicine_created_at,
 i.InventoryID AS inventory_id,
 i.BatchNumber AS batch_number,
 i.Quantity AS quantity,
 i.ExpiryDate AS expiry_date,
 i.DateReceived AS date_received,
 i.ReorderLevel AS reorder_level,
 i.Status AS status,
 i.CreatedAt AS inventory_created_at,
 i.UpdatedAt AS inventory_updated_at
 FROM medicines m
 INNER JOIN medicine_inventory i ON i.MedicineID = m.MedicineID
 ORDER BY m.CreatedAt DESC, i.ExpiryDate ASC, i.InventoryID DESC
 ";

 $result = $conn->query($sql);
 $rows = $result->fetch_all(MYSQLI_ASSOC);

 foreach ($rows as &$row) {
 $row['status_display'] = compute_display_status(
 (int)($row['quantity'] ?? 0),
 isset($row['expiry_date']) ? (string)$row['expiry_date'] : null,
 (int)($row['reorder_level'] ?? 10),
 (string)($row['status'] ?? '')
 );
 }
 unset($row);

 return $rows;
}

// ---
// MAIN DISPATCH
// ---
$payload = build_payload();
$action = $payload['action'];

try {
 if (!isset($conn) || !($conn instanceof mysqli)) {
 respond(['success' => false, 'message' => 'Database connection unavailable'], 500);
 }
 if ($conn->connect_error) {
 respond(['success' => false, 'message' => 'Database connection failed'], 500);
 }

 $who = current_user_id();

 // -- LIST ---
 if ($action === 'list') {
 $rows = fetch_medicine_rows($conn);
 respond([
 'success' => true,
 'message' => 'Medicine records loaded successfully',
 'data' => $rows,
 ]);
 }

 // -- STORE & UPDATE shared validation ---
 if (in_array($action, ['store', 'update'], true)) {
 foreach (['medicine_name' => $payload['medicine_name'], 'medicine_type' => $payload['medicine_type'], 'unit' => $payload['unit']] as $field => $value) {
 if ($value === '') {
 respond(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'], 422);
 }
 }
 }

 // -- STORE ---
 if ($action === 'store') {
 if ($payload['quantity'] === null || $payload['quantity'] < 0) {
 respond(['success' => false, 'message' => 'Quantity is required and must be zero or greater'], 422);
 }
 if ($payload['expiry_date'] === null) {
 respond(['success' => false, 'message' => 'Expiration date is required and must be valid'], 422);
 }

 $medicineName = $payload['medicine_name'];
 $genericName = $payload['generic_name'];
 $medicineType = $payload['medicine_type'];
 $dosage = $payload['dosage'];
 $unit = $payload['unit'];
 $description = $payload['description'];
 $batchNumber = $payload['batch_number'];
 $quantity = (int)$payload['quantity'];
 $expiryDate = $payload['expiry_date'];
 $dateReceived = $payload['date_received'];
 $reorderLevel = (int)$payload['reorder_level'];

 $conn->begin_transaction();

 $mStmt = $conn->prepare('INSERT INTO medicines (MedicineName, GenericName, MedicineType, Dosage, Unit, Description, CreatedAt) VALUES (?, ?, ?, ?, ?, ?, NOW())');
 $mStmt->bind_param('ssssss', $medicineName, $genericName, $medicineType, $dosage, $unit, $description);
 $mStmt->execute();
 $medicineId = (int)$conn->insert_id;
 $mStmt->close();

 $status = compute_inventory_status($quantity, $expiryDate, $reorderLevel, $medicineType);

 $iStmt = $conn->prepare('INSERT INTO medicine_inventory (MedicineID, BatchNumber, Quantity, ExpiryDate, DateReceived, ReorderLevel, Status, CreatedAt, UpdatedAt) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
 $iStmt->bind_param('isissis', $medicineId, $batchNumber, $quantity, $expiryDate, $dateReceived, $reorderLevel, $status);
 $iStmt->execute();
 $inventoryId = (int)$conn->insert_id;
 $iStmt->close();

 if ($who !== null) {
 auditLog($who, null, 'Added medicine ' . $medicineName, 'Medicine', null, 'Stock in (Qty: ' . $quantity . ', Batch: ' . ($batchNumber ?: 'N/A') . ')', null);
 }

 $conn->commit();

 respond(['success' => true, 'message' => 'Medicine saved successfully', 'medicineId' => $medicineId, 'inventoryId' => $inventoryId, 'status' => $status]);
 }

 // -- UPDATE ---
 if ($action === 'update') {
 if ($payload['medicine_id'] === null || $payload['inventory_id'] === null) {
 respond(['success' => false, 'message' => 'Missing ID for update'], 422);
 }
 if ($payload['quantity'] === null || $payload['quantity'] < 0) {
 respond(['success' => false, 'message' => 'Quantity is required and must be zero or greater'], 422);
 }
 if ($payload['expiry_date'] === null) {
 respond(['success' => false, 'message' => 'Expiration date is required and must be valid'], 422);
 }

 $medicineId = $payload['medicine_id'];
 $inventoryId = $payload['inventory_id'];
 $medicineName = $payload['medicine_name'];
 $genericName = $payload['generic_name'];
 $medicineType = $payload['medicine_type'];
 $dosage = $payload['dosage'];
 $unit = $payload['unit'];
 $description = $payload['description'];
 $batchNumber = $payload['batch_number'];
 $quantity = (int)$payload['quantity'];
 $expiryDate = $payload['expiry_date'];
 $reorderLevel = (int)$payload['reorder_level'];
 $status = compute_inventory_status($quantity, $expiryDate, $reorderLevel, $medicineType);

 $conn->begin_transaction();

 $mStmt = $conn->prepare('UPDATE medicines SET MedicineName = ?, GenericName = ?, MedicineType = ?, Dosage = ?, Unit = ?, Description = ? WHERE MedicineID = ?');
 $mStmt->bind_param('ssssssi', $medicineName, $genericName, $medicineType, $dosage, $unit, $description, $medicineId);
 $mStmt->execute();
 $mStmt->close();

 $iStmt = $conn->prepare('UPDATE medicine_inventory SET BatchNumber = ?, Quantity = ?, ExpiryDate = ?, ReorderLevel = ?, Status = ?, UpdatedAt = NOW() WHERE InventoryID = ? AND MedicineID = ?');
 $iStmt->bind_param('sisssii', $batchNumber, $quantity, $expiryDate, $reorderLevel, $status, $inventoryId, $medicineId);
 $iStmt->execute();
 $iStmt->close();

 if ($who !== null) {
 auditLog($who, null, 'Updated medicine ID ' . $medicineId, 'Medicine', null, 'Updated ' . $medicineName . ' (Qty: ' . $quantity . ')', null);
 }

 $conn->commit();

 respond(['success' => true, 'message' => 'Medicine updated successfully', 'status' => $status]);
 }

 // -- DELETE ---
// -- DELETE ---
if ($action === 'delete') {
 $medicineId = $payload['medicine_id'];
 $inventoryId = $payload['inventory_id'];

 if (!$medicineId || !$inventoryId) {
 respond(['success' => false, 'message' => 'Missing medicine or inventory ID'], 422);
 }

 // Get name for audit before deleting
 $nameStmt = $conn->prepare('SELECT MedicineName FROM medicines WHERE MedicineID = ?');
 $nameStmt->bind_param('i', $medicineId);
 $nameStmt->execute();
 $nameStmt->bind_result($medicineName);
 $nameStmt->fetch();
 $nameStmt->close();
 $medicineName = $medicineName ?? 'Unknown';

 $conn->begin_transaction();

 // Delete dispensing records linked to this inventory batch first (FK constraint)
 $delDisp = $conn->prepare('DELETE FROM medicine_dispensing WHERE InventoryID = ?');
 $delDisp->bind_param('i', $inventoryId);
 $delDisp->execute();
 $delDisp->close();

 // Delete inventory logs linked to this inventory batch if the table exists
 $logCheck = $conn->query("SHOW TABLES LIKE 'medicine_inventory_logs'");
 if ($logCheck->num_rows > 0) {
 $delLog = $conn->prepare('DELETE FROM medicine_inventory_logs WHERE InventoryID = ?');
 $delLog->bind_param('i', $inventoryId);
 $delLog->execute();
 $delLog->close();
 }

 // Now delete the inventory batch row
 $delInv = $conn->prepare('DELETE FROM medicine_inventory WHERE InventoryID = ? AND MedicineID = ?');
 $delInv->bind_param('ii', $inventoryId, $medicineId);
 $delInv->execute();
 $delInv->close();

 // Delete master record only if no other inventory rows remain
 $check = $conn->prepare('SELECT COUNT(*) FROM medicine_inventory WHERE MedicineID = ?');
 $check->bind_param('i', $medicineId);
 $check->execute();
 $check->bind_result($remaining);
 $check->fetch();
 $check->close();

 if ((int)$remaining === 0) {
 $delMed = $conn->prepare('DELETE FROM medicines WHERE MedicineID = ?');
 $delMed->bind_param('i', $medicineId);
 $delMed->execute();
 $delMed->close();
 }

 if ($who !== null) {
 auditLog($who, null, 'Deleted medicine ' . $medicineName, 'Medicine', null,
 'Deleted batch ID ' . $inventoryId . ' for ' . $medicineName, null);
 }

 $conn->commit();

 respond(['success' => true, 'message' => 'Medicine deleted successfully']);
}

 // -- FALLBACK ---
 respond(['success' => false, 'message' => 'Unsupported action'], 400);

} catch (Throwable $e) {
 if (isset($conn) && $conn instanceof mysqli) {
 try { $conn->rollback(); } catch (Throwable $ignored) {}
 }
 respond([
 'success' => false,
 'message' => 'Failed to process medicine request',
 'error' => $e->getMessage(),
 ], 500);
}




