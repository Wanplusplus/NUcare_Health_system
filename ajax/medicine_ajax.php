<?php
declare(strict_types=1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

/**
 * Send a JSON response and terminate execution.
 */
function respond(array $payload, int $httpCode = 200): void
{
    http_response_code($httpCode);
    echo json_encode($payload);
    exit;
}

/**
 * Normalize incoming text inputs.
 */
function clean_text(mixed $value): string
{
    return trim((string)($value ?? ''));
}

/**
 * Parse a user-provided date into MySQL format.
 */
function parse_date(?string $value): ?string
{
    $value = clean_text($value);
    if ($value === '') {
        return null;
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return null;
    }

    return date('Y-m-d', $timestamp);
}

/**
 * Compute inventory status server-side.
 */
function compute_inventory_status(int $quantity, ?string $expiryDate, int $reorderLevel): string
{
    if ($quantity <= 0) {
        return 'Out Of Stock';
    }

    if ($expiryDate === null) {
        return $quantity <= $reorderLevel ? 'Low Stock' : 'Available';
    }

    $today = new DateTimeImmutable('today');
    $expiry = DateTimeImmutable::createFromFormat('Y-m-d', $expiryDate);

    if ($expiry instanceof DateTimeImmutable && $expiry < $today) {
        return 'Expired';
    }

    if ($quantity <= $reorderLevel) {
        return 'Low Stock';
    }

    return 'Available';
}

function compute_display_status(int $quantity, ?string $expiryDate, int $reorderLevel): string
{
    if ($quantity <= 0) {
        return 'Out Of Stock';
    }

    if ($expiryDate === null) {
        return $quantity <= $reorderLevel ? 'Low Stock' : 'Available';
    }

    $today = new DateTimeImmutable('today');
    $expiry = DateTimeImmutable::createFromFormat('Y-m-d', $expiryDate);

    if ($expiry instanceof DateTimeImmutable) {
        if ($expiry < $today) {
            return 'Expired';
        }

        $days = (int)$today->diff($expiry)->format('%r%a');
        if ($days <= 30) {
            return 'Near Expiry';
        }
    }

    if ($quantity <= $reorderLevel) {
        return 'Low Stock';
    }

    return 'Available';
}

/**
 * Map the current session user to the inventory log table.
 */
function current_user_id(): ?int
{
    $sessionKeys = ['UserID', 'user_id', 'userId'];
    foreach ($sessionKeys as $key) {
        if (isset($_SESSION[$key]) && is_numeric($_SESSION[$key])) {
            return (int)$_SESSION[$key];
        }
    }

    return null;
}

/**
 * Validate and normalize the medicine/inventory payload.
 */
function build_payload(): array
{
    $medicineId = isset($_POST['medicine_id']) && is_numeric($_POST['medicine_id']) ? (int)$_POST['medicine_id'] : null;
    $inventoryId = isset($_POST['inventory_id']) && is_numeric($_POST['inventory_id']) ? (int)$_POST['inventory_id'] : null;

    $medicineName = clean_text($_POST['medicine_name'] ?? null);
    $genericName = clean_text($_POST['generic_name'] ?? null);
    $medicineType = clean_text($_POST['category'] ?? null);
    $dosage = clean_text($_POST['dosage'] ?? null);
    $unit = clean_text($_POST['unit'] ?? null);
    $description = clean_text($_POST['description'] ?? null);

    $batchNumber = clean_text($_POST['batch_code'] ?? null);
    $quantity = isset($_POST['quantity']) && $_POST['quantity'] !== '' ? (int)$_POST['quantity'] : null;
    $expiryDate = parse_date($_POST['expiration_date'] ?? null);
    $dateReceived = parse_date($_POST['date_received'] ?? null) ?? date('Y-m-d');
    $reorderLevel = isset($_POST['reorder_level']) && $_POST['reorder_level'] !== '' ? max(0, (int)$_POST['reorder_level']) : 10;

    $action = clean_text($_POST['action'] ?? 'store');
    if (!in_array($action, ['store', 'update', 'list', 'delete'], true)) {
        $action = 'store';
    }

    return [
        'action' => $action,
        'medicine_id' => $medicineId,
        'inventory_id' => $inventoryId,
        'medicine_name' => $medicineName,
        'generic_name' => $genericName,
        'medicine_type' => $medicineType,
        'dosage' => $dosage,
        'unit' => $unit,
        'description' => $description,
        'batch_number' => $batchNumber,
        'quantity' => $quantity,
        'expiry_date' => $expiryDate,
        'date_received' => $dateReceived,
        'reorder_level' => $reorderLevel,
    ];
}

/**
 * Return all medicine rows with inventory details.
 */
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
        $quantity = (int)($row['quantity'] ?? 0);
        $expiryDate = isset($row['expiry_date']) ? (string)$row['expiry_date'] : null;
        $reorderLevel = (int)($row['reorder_level'] ?? 10);
        $row['status_display'] = compute_display_status($quantity, $expiryDate, $reorderLevel);
    }
    unset($row);

    return $rows;
}

$payload = build_payload();
$action = $payload['action'];

try {
    if ($action === 'list') {
        $rows = fetch_medicine_rows($conn);
        respond([
            'success' => true,
            'message' => 'Medicine records loaded successfully',
            'data' => $rows,
        ]);
    }

    if (in_array($action, ['store', 'update'], true)) {
        $requiredFields = [
            'medicine_name' => $payload['medicine_name'],
            'medicine_type' => $payload['medicine_type'],
            'unit' => $payload['unit'],
        ];

        foreach ($requiredFields as $field => $value) {
            if ($value === '') {
                respond(['success' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'], 422);
            }
        }
    }

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

        $medicineStmt = $conn->prepare(
            'INSERT INTO medicines (
                MedicineName,
                GenericName,
                MedicineType,
                Dosage,
                Unit,
                Description,
                CreatedAt
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())'
        );

        $medicineStmt->bind_param(
            'ssssss',
            $medicineName,
            $genericName,
            $medicineType,
            $dosage,
            $unit,
            $description
        );
        $medicineStmt->execute();
        $medicineId = (int)$conn->insert_id;
        $medicineStmt->close();

        if ($medicineId <= 0) {
            throw new RuntimeException('Unable to retrieve new MedicineID');
        }

        $status = compute_inventory_status($quantity, $expiryDate, $reorderLevel);

        $inventoryStmt = $conn->prepare(
            'INSERT INTO medicine_inventory (
                MedicineID,
                BatchNumber,
                Quantity,
                ExpiryDate,
                DateReceived,
                ReorderLevel,
                Status,
                CreatedAt,
                UpdatedAt
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );

        $inventoryStmt->bind_param(
            'isissis',
            $medicineId,
            $batchNumber,
            $quantity,
            $expiryDate,
            $dateReceived,
            $reorderLevel,
            $status
        );
        $inventoryStmt->execute();
        $inventoryId = (int)$conn->insert_id;
        $inventoryStmt->close();

        $performedByUserId = current_user_id();
        if ($performedByUserId !== null) {
            $logStmt = $conn->prepare(
                'INSERT INTO medicine_inventory_logs (
                    InventoryID,
                    ActionType,
                    QuantityChanged,
                    PerformedByUserID,
                    Notes,
                    CreatedAt
                ) VALUES (?, ?, ?, ?, ?, NOW())'
            );

            $actionType = 'Stock In';
            $notes = $batchNumber !== '' ? 'Batch: ' . $batchNumber : 'Initial stock entry';

            $logStmt->bind_param(
                'isiis',
                $inventoryId,
                $actionType,
                $quantity,
                $performedByUserId,
                $notes
            );
            $logStmt->execute();
            $logStmt->close();
        }

        $conn->commit();

        respond([
            'success' => true,
            'message' => 'Medicine saved successfully',
            'medicineId' => $medicineId,
            'inventoryId' => $inventoryId,
            'status' => $status,
        ]);
    }

    if ($action === 'update') {
        if ($payload['medicine_id'] === null) {
            respond(['success' => false, 'message' => 'Medicine ID is required for updates'], 422);
        }

        if ($payload['quantity'] === null || $payload['quantity'] < 0) {
            respond(['success' => false, 'message' => 'Quantity is required and must be zero or greater'], 422);
        }

        if ($payload['expiry_date'] === null) {
            respond(['success' => false, 'message' => 'Expiration date is required and must be valid'], 422);
        }

        $medicineId = (int)$payload['medicine_id'];
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

        $medicineStmt = $conn->prepare(
            'UPDATE medicines
             SET MedicineName = ?, GenericName = ?, MedicineType = ?, Dosage = ?, Unit = ?, Description = ?
             WHERE MedicineID = ?'
        );

        $medicineStmt->bind_param(
            'ssssssi',
            $medicineName,
            $genericName,
            $medicineType,
            $dosage,
            $unit,
            $description,
            $medicineId
        );
        $medicineStmt->execute();
        $medicineStmt->close();

        $inventoryId = $payload['inventory_id'];
        if ($inventoryId === null) {
            $inventoryLookup = $conn->prepare(
                'SELECT InventoryID FROM medicine_inventory WHERE MedicineID = ? ORDER BY CreatedAt DESC, InventoryID DESC LIMIT 1'
            );

            $inventoryLookup->bind_param('i', $medicineId);
            $inventoryLookup->execute();
            $inventoryResult = $inventoryLookup->get_result();
            $inventoryRow = $inventoryResult ? $inventoryResult->fetch_assoc() : null;
            $inventoryLookup->close();

            if (!$inventoryRow || !isset($inventoryRow['InventoryID'])) {
                throw new RuntimeException('No inventory row found for this medicine');
            }

            $inventoryId = (int)$inventoryRow['InventoryID'];
        }

        $status = compute_inventory_status($quantity, $expiryDate, $reorderLevel);

        $inventoryStmt = $conn->prepare(
            'UPDATE medicine_inventory
             SET BatchNumber = ?, Quantity = ?, ExpiryDate = ?, DateReceived = ?, ReorderLevel = ?, Status = ?, UpdatedAt = NOW()
             WHERE InventoryID = ? AND MedicineID = ?'
        );

        $inventoryStmt->bind_param(
            'sissisii',
            $batchNumber,
            $quantity,
            $expiryDate,
            $dateReceived,
            $reorderLevel,
            $status,
            $inventoryId,
            $medicineId
        );
        $inventoryStmt->execute();
        $inventoryStmt->close();

        $conn->commit();

        respond([
            'success' => true,
            'message' => 'Medicine updated successfully',
            'medicineId' => $medicineId,
            'inventoryId' => $inventoryId,
            'status' => $status,
        ]);
    }

    if ($action === 'delete') {
        if ($payload['medicine_id'] === null || $payload['inventory_id'] === null) {
            respond(['success' => false, 'message' => 'Medicine ID and Inventory ID are required for deletion'], 422);
        }

        $medicineId = (int)$payload['medicine_id'];
        $inventoryId = (int)$payload['inventory_id'];

        $conn->begin_transaction();

        $logDeleteStmt = $conn->prepare('DELETE FROM medicine_inventory_logs WHERE InventoryID = ?');
        $logDeleteStmt->bind_param('i', $inventoryId);
        $logDeleteStmt->execute();
        $logDeleteStmt->close();

        $deleteInventoryStmt = $conn->prepare('DELETE FROM medicine_inventory WHERE InventoryID = ? AND MedicineID = ?');
        $deleteInventoryStmt->bind_param('ii', $inventoryId, $medicineId);
        $deleteInventoryStmt->execute();
        $affectedInventory = $deleteInventoryStmt->affected_rows;
        $deleteInventoryStmt->close();

        if ($affectedInventory <= 0) {
            throw new RuntimeException('Medicine inventory record not found');
        }

        $remainingStmt = $conn->prepare('SELECT COUNT(*) AS total FROM medicine_inventory WHERE MedicineID = ?');
        $remainingStmt->bind_param('i', $medicineId);
        $remainingStmt->execute();
        $remainingResult = $remainingStmt->get_result();
        $remainingRow = $remainingResult ? $remainingResult->fetch_assoc() : null;
        $remainingStmt->close();

        $remainingCount = (int)($remainingRow['total'] ?? 0);
        if ($remainingCount === 0) {
            $deleteMedicineStmt = $conn->prepare('DELETE FROM medicines WHERE MedicineID = ?');
            $deleteMedicineStmt->bind_param('i', $medicineId);
            $deleteMedicineStmt->execute();
            $deleteMedicineStmt->close();
        }

        $conn->commit();

        respond([
            'success' => true,
            'message' => 'Medicine deleted successfully',
            'medicineId' => $medicineId,
            'inventoryId' => $inventoryId,
        ]);
    }

    respond(['success' => false, 'message' => 'Unsupported action'], 400);
} catch (Throwable $e) {
    try {
        $conn->rollback();
    } catch (Throwable) {
        // ignore rollback errors and return the original failure
    }

    respond([
        'success' => false,
        'message' => 'Failed to process medicine request',
        'error' => $e->getMessage(),
    ], 500);
}
