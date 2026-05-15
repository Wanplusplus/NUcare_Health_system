<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method.'
    ]);
    exit;
}

$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$middle_name = trim($_POST['middle_name'] ?? '');
$sex = trim($_POST['sex'] ?? '');
$school_id = trim($_POST['school_id'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$role = 'Patient';

if ($first_name === '' || $last_name === '' || $sex === '' || $school_id === '' || $password === '' || $confirm_password === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'All fields are required.'
    ]);
    exit;
}

if ($password !== $confirm_password) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Passwords do not match.'
    ]);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Password must be at least 6 characters.'
    ]);
    exit;
}

if ($sex !== 'Male' && $sex !== 'Female') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid sex selected.'
    ]);
    exit;
}

$checkSql = "SELECT PatientID FROM patients WHERE SchoolID = ? LIMIT 1";
$checkStmt = mysqli_prepare($conn, $checkSql);

if (!$checkStmt) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Check query failed: ' . mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_bind_param($checkStmt, 's', $school_id);
mysqli_stmt_execute($checkStmt);
$checkResult = mysqli_stmt_get_result($checkStmt);

if ($checkResult && mysqli_num_rows($checkResult) > 0) {
    mysqli_stmt_close($checkStmt);
    echo json_encode([
        'status' => 'error',
        'message' => 'School ID already exists.'
    ]);
    exit;
}

mysqli_stmt_close($checkStmt);

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$middle_name = $middle_name !== '' ? $middle_name : null;

$insertSql = "INSERT INTO patients (PatientFname, PatientLname, PatientMname, SchoolID, Password, Role, Sex)
              VALUES (?, ?, ?, ?, ?, ?, ?)";

$insertStmt = mysqli_prepare($conn, $insertSql);

if (!$insertStmt) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Insert query prepare failed: ' . mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_bind_param($insertStmt, 'sssssss', $first_name, $last_name, $middle_name, $school_id, $hashedPassword, $role, $sex);

if (mysqli_stmt_execute($insertStmt)) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Registration successful. Redirecting to login...'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Insert failed: ' . mysqli_stmt_error($insertStmt)
    ]);
}

mysqli_stmt_close($insertStmt);
mysqli_close($conn);
?>