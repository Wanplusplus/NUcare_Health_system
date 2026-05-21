<?php
// Handles email lookup and sending the reset link (users + school_people schema)
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

// ── Sanitize and validate email ───────────────────────────────────────────────
$email = strtolower(trim($_POST['email'] ?? ''));

if ($email === '') {
    echo json_encode(['status' => 'error', 'message' => 'Email address is required.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address.']);
    exit;
}

// ── Find user by email (school_people.Email -> users.UserID) ────────────────
$stmt = $conn->prepare(
    "SELECT u.UserID, u.IsActive\n"
    ."FROM users u\n"
    ."JOIN school_people sp ON sp.SchoolPersonID = u.SchoolPersonID\n"
    ."WHERE sp.Email = ?\n"
    ."LIMIT 1"
);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    echo json_encode(['status' => 'error', 'message' => 'No account found with that email address.']);
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

if (isset($user['IsActive']) && (int)$user['IsActive'] !== 1) {
    echo json_encode(['status' => 'error', 'message' => 'Account is inactive.']);
    exit;
}

// ── Generate secure reset token ───────────────────────────────────────────────
$token  = bin2hex(random_bytes(32));
$expiry = date('Y-m-d H:i:s', strtotime('+20 minutes'));

// ── Save token and expiry to users table ─────────────────────────────────────
$update = $conn->prepare(
    "UPDATE users\n"
    ."SET ResetToken = ?, TokenExpiry = ?\n"
    ."WHERE UserID = ?"
);
$update->bind_param('ssi', $token, $expiry, $user['UserID']);
$update->execute();
$update->close();

// ── Build reset link ──────────────────────────────────────────────────────────
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
$resetLink = $scheme . '://' . $host . dirname($basePath, 1) . '/auth/reset_password.php?token=' . urlencode($token);

// ── Send email via PHPMailer ─────────────────────────────────────────────────
$sent = sendResetEmail($email, $resetLink);

if ($sent) {
    echo json_encode(['status' => 'success', 'message' => 'Reset link sent! Please check your email.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to send email. Please try again.']);
}

$conn->close();
?>
