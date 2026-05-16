<?php
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$medicine_name = trim($_POST['medicine_name'] ?? '');
$generic_name = trim($_POST['generic_name'] ?? '');
$category = trim($_POST['category'] ?? '');
$quantity = (int)($_POST['quantity'] ?? 0);
$unit = trim($_POST['unit'] ?? '');
$purchase_quantity = (int)($_POST['purchase_quantity'] ?? 0);
$ending_balance = $_POST['ending_balance'] ?? '';
$expiration_date = trim($_POST['expiration_date'] ?? '');
$batch_number = trim($_POST['batch_number'] ?? '');
$supplier = trim($_POST['supplier'] ?? '');
$unit_cost = (float)($_POST['unit_cost'] ?? 0);
$notes = trim($_POST['notes'] ?? '');

if ($medicine_name === '' || $category === '' || $quantity < 0 || $unit === '' || $expiration_date === '') {
    echo json_encode(['success' => false, 'message' => 'Please fill all required fields']);
    exit;
}

if (strtotime($expiration_date) === false) {
    echo json_encode(['success' => false, 'message' => 'Invalid expiration date']);
    exit;
}

if ($ending_balance === '' || !is_numeric($ending_balance)) {
    $ending_balance = $quantity;
} else {
    $ending_balance = (int)$ending_balance;
}

$total_cost = $unit_cost * $purchase_quantity;

$today = new DateTime();
$exp = new DateTime($expiration_date);
$days = (int)$today->diff($exp)->format('%r%a');

if ($quantity <= 0) {
    $status = 'Out of Stock';
} elseif ($days < 0) {
    $status = 'Expired';
} elseif ($days <= 30) {
    $status = 'Near Expiry';
} elseif ($quantity <= 20) {
    $status = 'Low Stock';
} else {
    $status = 'Available';
}

$stmt = $conn->prepare("INSERT INTO medicines (medicine_name, generic_name, category, quantity, unit, purchase_quantity, ending_balance, expiration_date, batch_number, supplier, unit_cost, total_cost, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$stmt->bind_param(
    'sssisisissddss',
    $medicine_name,
    $generic_name,
    $category,
    $quantity,
    $unit,
    $purchase_quantity,
    $ending_balance,
    $expiration_date,
    $batch_number,
    $supplier,
    $unit_cost,
    $total_cost,
    $status,
    $notes
);

$ok = $stmt->execute();

echo json_encode([
    'success' => $ok,
    'message' => $ok ? 'Medicine added successfully' : 'Failed to add medicine'
]);

$stmt->close();
$conn->close();
?>