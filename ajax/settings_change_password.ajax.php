<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['UserID']) || !is_numeric($_SESSION['UserID'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/db.php';

$currentPassword = (string)($_POST['current_password'] ?? '');
$newPassword = (string)($_POST['new_password'] ?? '');
$confirmPassword = (string)($_POST['confirm_password'] ?? '');

if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    exit;
}

if (strlen($newPassword) < 6) {
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters.']);
    exit;
}

if ($newPassword !== $confirmPassword) {
    echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
    exit;
}

$userId = (int)$_SESSION['UserID'];

try {
    $stmt = $conn->prepare('SELECT PasswordHash FROM users WHERE UserID = ? LIMIT 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$user || empty($user['PasswordHash'])) {
        echo json_encode(['status' => 'error', 'message' => 'Account password not found.']);
        exit;
    }

    if (!password_verify($currentPassword, (string)$user['PasswordHash'])) {
        echo json_encode(['status' => 'error', 'message' => 'Current password is incorrect.']);
        exit;
    }

    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

    $upd = $conn->prepare('UPDATE users SET PasswordHash = ? WHERE UserID = ?');
    $upd->bind_param('si', $hashed, $userId);

    if ($upd->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Password updated successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update password.']);
    }
    $upd->close();

} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => 'Server error.', 'error' => $e->getMessage()]);
}

