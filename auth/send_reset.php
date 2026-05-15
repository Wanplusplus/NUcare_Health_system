<?php
// Handles email lookup and sending the reset link
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

// ── Check if email exists in database ────────────────────────────────────────
$stmt = $conn->prepare("SELECT PatientID, PatientFname, Email FROM patients WHERE Email = ? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // ← This is the "User not found" response
    echo json_encode(['status' => 'error', 'message' => 'No account found with that email address.']);
    $stmt->close();
    exit;
}

$patient = $result->fetch_assoc();
$stmt->close();

// ── Generate secure reset token ───────────────────────────────────────────────
$token   = bin2hex(random_bytes(32));                           // 64-char hex token
$expiry  = date('Y-m-d H:i:s', strtotime('+20 minutes'));      // Expires in 20 min

// ── Save token and expiry to database ────────────────────────────────────────
$update = $conn->prepare("UPDATE patients SET reset_token = ?, token_expiry = ? WHERE PatientID = ?");
$update->bind_param('ssi', $token, $expiry, $patient['PatientID']);
$update->execute();
$update->close();

// ── Build reset link ──────────────────────────────────────────────────────────
$resetLink = 'http://localhost/NUcare_Health_system/auth/reset_password.php?token=' . urlencode($token);

// ── Send email via PHPMailer ──────────────────────────────────────────────────
$sent = sendResetEmail($email, $resetLink);

if ($sent) {
    echo json_encode(['status' => 'success', 'message' => 'Reset link sent! Please check your email.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to send email. Please try again.']);
}

$conn->close();
?>