<?php
require_once __DIR__ . '/../../backend/includes/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
 echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
 exit;
}

$year = trim($_POST['academic_year'] ?? '');
$sem = trim($_POST['semester'] ?? '');

if (!isset($_FILES['students_file']) || $_FILES['students_file']['error'] !== UPLOAD_ERR_OK) {
 echo json_encode(['status' => 'error', 'message' => 'File upload failed']);
 exit;
}

$tmp = $_FILES['students_file']['tmp_name'];
$name = $_FILES['students_file']['name'];
$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

if ($ext !== 'csv') {
 echo json_encode(['status' => 'error', 'message' => 'Only CSV files are allowed']);
 exit;
}

$fh = fopen($tmp, 'r');
if (!$fh) {
 echo json_encode(['status' => 'error', 'message' => 'Unable to read file']);
 exit;
}

$header = fgetcsv($fh);
$inserted = 0;
$updated = 0;
$skipped = 0;

$conn->begin_transaction();

try {
 $stmt = $conn->prepare(
 "INSERT INTO enrolled_students
 (SchoolID, FullName, Program, Email, EnrollmentStatus, AcademicYear, Semester)
 VALUES (?, ?, ?, ?, ?, ?, ?)
 ON DUPLICATE KEY UPDATE
 FullName = VALUES(FullName),
 Program = VALUES(Program),
 Email = VALUES(Email),
 EnrollmentStatus = VALUES(EnrollmentStatus),
 AcademicYear = VALUES(AcademicYear),
 Semester = VALUES(Semester)"
 );

 while (($row = fgetcsv($fh)) !== false) {
 if (count($row) < 5) {
 $skipped++;
 continue;
 }

 $schoolid = trim($row[0]);
 $fullname = trim($row[1]);
 $program = trim($row[2]);
 $email = trim($row[3]);
 $status = trim($row[4]) ?: 'Enrolled';

 if ($schoolid === '' || $fullname === '' || $program === '' || $email === '') {
 $skipped++;
 continue;
 }

 $stmt->bind_param('sssssss', $schoolid, $fullname, $program, $email, $status, $year, $sem);

 if ($stmt->execute()) {
 if ($stmt->affected_rows === 1) {
 $inserted++;
 } else {
 $updated++;
 }
 } else {
 $skipped++;
 }
 }

 $stmt->close();
 $conn->commit();
 fclose($fh);

 echo json_encode([
 'status' => 'success',
 'message' => 'Import completed',
 'inserted' => $inserted,
 'updated' => $updated,
 'skipped' => $skipped
 ]);
} catch (Exception $e) {
 $conn->rollback();
 echo json_encode(['status' => 'error', 'message' => 'Import failed']);
}
?>



