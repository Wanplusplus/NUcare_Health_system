<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

// ─── Only accept POST ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// ─── Collect and sanitize inputs ────────────────────────────────────────────
$first_name      = trim($_POST['first_name']      ?? '');
$last_name       = trim($_POST['last_name']       ?? '');
$middle_name     = trim($_POST['middle_name']     ?? '');
$sex             = trim($_POST['sex']             ?? '');
$school_id       = trim($_POST['school_id']       ?? '');
$email           = strtolower(trim($_POST['email'] ?? '')); // ✅ NEW
$password        = $_POST['password']        ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$role            = 'Patient';

// ─── Required field check ───────────────────────────────────────────────────
if ($first_name === '' || $last_name === '' || $sex === '' ||
    $school_id === '' || $email === '' || $password === '' || $confirm_password === '') {
    echo json_encode(['status' => 'error', 'message' => 'All required fields must be filled.']);
    exit;
}

// ─── Email format validation ─────────────────────────────────────────────────
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email address format.']);
    exit;
}

// ─── Password length check ───────────────────────────────────────────────────
if (strlen($password) < 6) {
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters.']);
    exit;
}

// ─── Password match check ────────────────────────────────────────────────────
if ($password !== $confirm_password) {
    echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
    exit;
}

// ─── Sex value whitelist ─────────────────────────────────────────────────────
if ($sex !== 'Male' && $sex !== 'Female') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid sex selected.']);
    exit;
}

// ─── Check duplicate School ID ───────────────────────────────────────────────
$checkSchool = mysqli_prepare($conn, "SELECT PatientID FROM patients WHERE SchoolID = ? LIMIT 1");
if (!$checkSchool) {
    echo json_encode(['status' => 'error', 'message' => 'Query error: ' . mysqli_error($conn)]);
    exit;
}
mysqli_stmt_bind_param($checkSchool, 's', $school_id);
mysqli_stmt_execute($checkSchool);
mysqli_stmt_store_result($checkSchool);
if (mysqli_stmt_num_rows($checkSchool) > 0) {
    mysqli_stmt_close($checkSchool);
    echo json_encode(['status' => 'error', 'message' => 'School ID is already registered.']);
    exit;
}
mysqli_stmt_close($checkSchool);

// ─── ✅ NEW: Check duplicate Email ──────────────────────────────────────────
$checkEmail = mysqli_prepare($conn, "SELECT PatientID FROM patients WHERE Email = ? LIMIT 1");
if (!$checkEmail) {
    echo json_encode(['status' => 'error', 'message' => 'Query error: ' . mysqli_error($conn)]);
    exit;
}
mysqli_stmt_bind_param($checkEmail, 's', $email);
mysqli_stmt_execute($checkEmail);
mysqli_stmt_store_result($checkEmail);
if (mysqli_stmt_num_rows($checkEmail) > 0) {
    mysqli_stmt_close($checkEmail);
    echo json_encode(['status' => 'error', 'message' => 'Email address is already registered.']);
    exit;
}
mysqli_stmt_close($checkEmail);

// ─── Hash password ───────────────────────────────────────────────────────────
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$middle_name = $middle_name !== '' ? $middle_name : null;

// ─── ✅ INSERT including Email ───────────────────────────────────────────────
$insertSql = "INSERT INTO patients
                (PatientFname, PatientLname, PatientMname, SchoolID, Email, Password, Role, Sex)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$insertStmt = mysqli_prepare($conn, $insertSql);
if (!$insertStmt) {
    echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . mysqli_error($conn)]);
    exit;
}

// 8 string params: f, l, m, school, email, password, role, sex
mysqli_stmt_bind_param($insertStmt, 'ssssssss',
    $first_name,
    $last_name,
    $middle_name,
    $school_id,
    $email,
    $hashedPassword,
    $role,
    $sex
);

if (mysqli_stmt_execute($insertStmt)) {
    echo json_encode([
        'status'  => 'success',
        'message' => 'Registration successful. Redirecting to login...'
    ]);
} else {
    echo json_encode([
        'status'  => 'error',
        'message' => 'Registration failed: ' . mysqli_stmt_error($insertStmt)
    ]);
}

mysqli_stmt_close($insertStmt);
mysqli_close($conn);
?>