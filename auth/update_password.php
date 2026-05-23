<?php
// Handles the actual password update after token validation
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

// Collect inputs
$token = trim($_POST['token'] ?? '');
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validate inputs
if ($token === '' || $new_password === '' || $confirm_password === '') {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    exit;
}

if (strlen($new_password) < 6) {
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters.']);
    exit;
}

if ($new_password !== $confirm_password) {
    echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
    exit;
}

// Fetch token record first (users table)
$stmt = $conn->prepare(
    "SELECT UserID, TokenExpiry
     FROM users
     WHERE ResetToken = ?
     LIMIT 1"
);

if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Database prepare failed.']);
    exit;
}

$stmt->bind_param('s', $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or expired reset link.']);
    $stmt->close();
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

// Check expiry in PHP
if (strtotime($user['TokenExpiry']) < time()) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or expired reset link.']);
    exit;
}

// Hash and update password (MySQL SHA2(password, 256))
$hashedPassword = hash('sha256', $new_password);


$update = $conn->prepare(
    "UPDATE users
     SET PasswordHash = ?, ResetToken = NULL, TokenExpiry = NULL
     WHERE UserID = ?"
);

if (!$update) {
    echo json_encode(['status' => 'error', 'message' => 'Database update prepare failed.']);
    exit;
}

$update->bind_param('si', $hashedPassword, $user['UserID']);


if ($update->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Password updated! Redirecting to login...',
        'redirect' => '../auth/login.php'
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update password. Please try again.']);
}

$update->close();
$conn->close();
?>