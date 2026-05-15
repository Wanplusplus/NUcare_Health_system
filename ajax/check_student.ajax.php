<?php
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$school_id = trim($_POST['school_id'] ?? '');
$full_name = trim($_POST['full_name'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$academic_year = trim($_POST['academic_year'] ?? '');
$semester = trim($_POST['semester'] ?? '');

if ($school_id === '') {
    echo json_encode(['status' => 'error', 'message' => 'School ID is required']);
    exit;
}

$stmt = $conn->prepare(
    "SELECT StudentID, SchoolID, FullName, Program, Email, EnrollmentStatus
     FROM enrolled_students
     WHERE SchoolID = ? AND AcademicYear = ? AND Semester = ?
     LIMIT 1"
);
$stmt->bind_param('sss', $school_id, $academic_year, $semester);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['status' => 'not_found', 'message' => 'You are not officially enrolled.']);
    exit;
}

$row = $res->fetch_assoc();

if (strtolower($row['EnrollmentStatus']) !== 'enrolled') {
    echo json_encode(['status' => 'not_enrolled', 'message' => 'You are not officially enrolled.']);
    exit;
}

if ($email !== '' && strcasecmp($row['Email'], $email) !== 0) {
    echo json_encode(['status' => 'error', 'message' => 'Email does not match official record.']);
    exit;
}

if ($full_name !== '' && strcasecmp($row['FullName'], $full_name) !== 0) {
    echo json_encode(['status' => 'error', 'message' => 'Full name does not match official record.']);
    exit;
}

echo json_encode(['status' => 'success', 'message' => 'Student verified', 'student' => $row]);
?>