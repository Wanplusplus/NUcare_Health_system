<?php
/* ── Show PHP errors as JSON so the browser can read them ── */
ini_set('display_errors', 0);
error_reporting(E_ALL);
set_exception_handler(function($e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'PHP Exception: ' . $e->getMessage()]);
    exit;
});
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => "PHP Error [$errno]: $errstr in $errfile on line $errline"]);
    exit;
});

header('Content-Type: application/json');

/* ── DB connection — adjust path if needed ── */
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$action = trim($_POST['action'] ?? 'add');

/* ════════════════════════════════════════
   FETCH — return all medicines + inventory
   ════════════════════════════════════════ */
if ($action === 'fetch') {
    $sql = "
        SELECT
            m.MedicineID        AS id,
            m.MedicineName      AS medicine_name,
            m.GenericName       AS generic_name,
            m.MedicineType      AS category,
            m.Dosage            AS dosage,
            m.Unit              AS unit,
            m.Description       AS notes,
            COALESCE(i.InventoryID,    0)    AS inventory_id,
            COALESCE(i.BatchNumber,    '')   AS batch_number,
            COALESCE(i.Quantity,       0)    AS quantity,
            COALESCE(i.Quantity,       0)    AS purchase_quantity,
            COALESCE(i.Quantity,       0)    AS ending_balance,
            COALESCE(CAST(i.ExpiryDate AS CHAR), '') AS expiration_date,
            COALESCE(i.Status, 'Available')  AS status
        FROM medicines m
        LEFT JOIN medicine_inventory i ON i.MedicineID = m.MedicineID
        ORDER BY m.CreatedAt DESC, i.ExpiryDate ASC
    ";

    $result = $conn->query($sql);
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Query failed: ' . $conn->error]);
        exit;
    }

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $rows]);
    $conn->close();
    exit;
}

/* ════════════════════════════════════════
   ADD — two-step insert (medicines + inventory)
   ════════════════════════════════════════ */

$medicine_name   = trim($_POST['medicine_name']    ?? '');
$generic_name    = trim($_POST['generic_name']     ?? '');
$category        = trim($_POST['category']         ?? '');
$dosage          = trim($_POST['dosage']            ?? '');
$unit            = trim($_POST['unit']              ?? '');
$description     = trim($_POST['description']       ?? '');

$batch_code      = trim($_POST['batch_code']        ?? '');
$quantity        = (int)($_POST['quantity']          ?? 0);
$purchase_qty    = (int)($_POST['purchase_quantity'] ?? 0);
$unit_cost       = (float)($_POST['unit_cost']       ?? 0);
$expiration_date = trim($_POST['expiration_date']   ?? '');
$supplier        = trim($_POST['supplier']           ?? '');

/* Validation */
if ($medicine_name === '') { echo json_encode(['success'=>false,'message'=>'Medicine name is required']); exit; }
if ($category      === '') { echo json_encode(['success'=>false,'message'=>'Category is required']);       exit; }
if ($unit          === '') { echo json_encode(['success'=>false,'message'=>'Unit is required']);           exit; }
if ($expiration_date === '' || strtotime($expiration_date) === false) {
    echo json_encode(['success'=>false,'message'=>'A valid expiration date is required']); exit;
}
if ($quantity < 0) { echo json_encode(['success'=>false,'message'=>'Quantity cannot be negative']); exit; }

/* Compute status — values MUST match your enum exactly */
$today = new DateTime();
$exp   = new DateTime($expiration_date);
$days  = (int)$today->diff($exp)->format('%r%a');

if ($quantity <= 0) {
    $status = 'Out Of Stock';
} elseif ($days < 0) {
    $status = 'Expired';
} elseif ($days <= 30) {
    $status = 'Near Expiry';
} elseif ($quantity <= 10) {
    $status = 'Low Stock';
} else {
    $status = 'Available';
}

/* Transaction */
$conn->begin_transaction();

try {
    /* STEP 1 — medicines master */
    $stmtM = $conn->prepare(
        "INSERT INTO medicines (MedicineName, GenericName, MedicineType, Dosage, Unit, Description)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    if (!$stmtM) throw new Exception('Prepare medicines failed: ' . $conn->error);

    $stmtM->bind_param('ssssss',
        $medicine_name, $generic_name, $category, $dosage, $unit, $description
    );
    if (!$stmtM->execute()) throw new Exception('Insert medicines failed: ' . $stmtM->error);
    $medicine_id = $conn->insert_id;
    $stmtM->close();

    /* STEP 2 — medicine_inventory batch */
    $stmtI = $conn->prepare(
        "INSERT INTO medicine_inventory (MedicineID, BatchNumber, Quantity, ExpiryDate, Status)
         VALUES (?, ?, ?, ?, ?)"
    );
    if (!$stmtI) throw new Exception('Prepare inventory failed: ' . $conn->error);

    $stmtI->bind_param('isiss',
        $medicine_id, $batch_code, $quantity, $expiration_date, $status
    );
    if (!$stmtI->execute()) throw new Exception('Insert inventory failed: ' . $stmtI->error);
    $inventory_id = $conn->insert_id;
    $stmtI->close();

    $conn->commit();

    echo json_encode([
        'success'      => true,
        'message'      => 'Medicine added successfully',
        'medicine_id'  => $medicine_id,
        'inventory_id' => $inventory_id,
        'status'       => $status,
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();