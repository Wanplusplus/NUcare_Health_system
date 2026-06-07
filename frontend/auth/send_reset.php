<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$pdo = require __DIR__ . '/../../database/config/db_pdo.php';
require_once __DIR__ . '/../../database/config/mailer.php';

function resetResponse(string $status, string $message): void
{
 echo json_encode(['status' => $status, 'message' => $message]);
 exit;
}

try {
 if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
 resetResponse('error', 'Invalid request.');
 }

 $email = strtolower(trim((string)($_POST['email'] ?? '')));

 if ($email === '') {
 resetResponse('error', 'Email address is required.');
 }

 if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
 resetResponse('error', 'Please enter a valid email address.');
 }

 $stmt = $pdo->prepare("
 SELECT u.UserID, u.IsActive
 FROM users u
 INNER JOIN school_people sp ON sp.SchoolPersonID = u.SchoolPersonID
 WHERE LOWER(sp.Email) = ?
 LIMIT 1
 ");
 $stmt->execute([$email]);
 $user = $stmt->fetch(PDO::FETCH_ASSOC);

 if (!$user) {
 resetResponse('error', 'No account found with that email address.');
 }

 if (isset($user['IsActive']) && (int)$user['IsActive'] !== 1) {
 resetResponse('error', 'Account is inactive.');
 }

 $token = bin2hex(random_bytes(32));
 $expiry = date('Y-m-d H:i:s', strtotime('+20 minutes'));

 $update = $pdo->prepare("
 UPDATE users
 SET ResetToken = ?, TokenExpiry = ?
 WHERE UserID = ?
 ");
 $update->execute([$token, $expiry, (int)$user['UserID']]);

 $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
 $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
 $resetLink = $scheme . '://' . $host . '/NUcare_Health_system/frontend/auth/reset_password.php?token=' . urlencode($token);

 if (!sendResetEmail($email, $resetLink)) {
 resetResponse('error', 'Failed to send email. Check the Gmail app password or SMTP connection.');
 }

 resetResponse('success', 'Reset link sent! Please check your email.');
} catch (Throwable $e) {
 error_log('Forgot password error: ' . $e->getMessage());
 resetResponse('error', 'Password reset failed. Please try again.');
}




