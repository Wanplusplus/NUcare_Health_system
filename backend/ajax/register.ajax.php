<?php
require_once __DIR__ . '/../../backend/includes/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
 echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
 exit;
}

$school_id = trim($_POST['school_id'] ?? '');
$full_name = trim($_POST['full_name'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$academic_year = trim($_POST['academic_year'] ?? '');
$semester = trim($_POST['semester'] ?? '');

if ($school_id === '' || $full_name === '' || $email === '' || $password === '' || $confirm_password === '') {
 echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
 exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
 echo json_encode(['status' => 'error', 'message' => 'Invalid email']);
 exit;
}

if ($password !== $confirm_password) {
 echo json_encode(['status' => 'error', 'message' => 'Passwords do not match']);
 exit;
}

if (strlen($password) < 8) {
 echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters']);
 exit;
}

$chk = $conn->prepare(
 "SELECT StudentID, Email, SchoolID
 FROM enrolled_students
 WHERE SchoolID = ? AND AcademicYear = ? AND Semester = ?
 LIMIT 1"
);
$chk->bind_param('sss', $school_id, $academic_year, $semester);
$chk->execute();
$res = $chk->get_result();

if ($res->num_rows === 0) {
 echo json_encode(['status' => 'error', 'message' => 'You are not officially enrolled.']);
 exit;
}

$student = $res->fetch_assoc();

if (strtolower($student['Email']) !== $email || strtolower($student['SchoolID']) !== strtolower($school_id)) {
 echo json_encode(['status' => 'error', 'message' => 'Student details do not match official record.']);
 exit;
}

$dup = $conn->prepare("SELECT PatientID FROM patients WHERE SchoolID = ? OR Email = ? LIMIT 1");
$dup->bind_param('ss', $school_id, $email);
$dup->execute();
$dupRes = $dup->get_result();

if ($dupRes->num_rows > 0) {
 echo json_encode(['status' => 'error', 'message' => 'Account already exists.']);
 exit;
}

$hash = hash('sha256', $password);
$role = 'Patient';
$sex = 'Male';
$verified = 1;
$verifiedAt = date('Y-m-d H:i:s');

$parts = explode(' ', $full_name, 2);
$fname = $parts[0];
$lname = $parts[1] ?? $parts[0];
$mname = null;
$programId = null;

$stmt = $conn->prepare(
 "INSERT INTO patients
 (PatientFname, PatientLname, PatientMname, ProgramID, SchoolID, Email, Password, Role, Sex, enrollment_verified, enrollment_student_id, enrollment_checked_at)
 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

$stmt->bind_param(
 'sssiissssiss',
 $fname,
 $lname,
 $mname,
 $programId,
 $school_id,
 $email,
 $hash,
 $role,
 $sex,
 $verified,
 $student['StudentID'],
 $verifiedAt
);

if ($stmt->execute()) {
 echo json_encode(['status' => 'success', 'message' => 'Registration successful. Redirecting to login...']);
} else {
 echo json_encode(['status' => 'error', 'message' => 'Registration failed']);
}
?>



