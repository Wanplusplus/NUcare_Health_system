<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method.'
    ]);
    exit;
}

$school_id = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($school_id === '' || $password === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please enter your School ID and password.'
    ]);
    exit;
}

$sql = "SELECT PatientID, PatientFname, PatientLname, SchoolID, Password, Role
        FROM patients
        WHERE SchoolID = ?
        LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to prepare login query: ' . mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, 's', $school_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) === 1) {
    $patient = mysqli_fetch_assoc($result);

    if (!empty($patient['Password']) && password_verify($password, $patient['Password'])) {
        $_SESSION['patient_id'] = $patient['PatientID'];
        $_SESSION['patient_name'] = trim($patient['PatientFname'] . ' ' . $patient['PatientLname']);
        $_SESSION['school_id'] = $patient['SchoolID'];
        $_SESSION['role'] = $patient['Role'];

        echo json_encode([
            'status' => 'success',
            'message' => 'Login successful.',
            'redirect' => '../modules/dashboard/dashboard.php'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid School ID or password.'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid School ID or password.'
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>