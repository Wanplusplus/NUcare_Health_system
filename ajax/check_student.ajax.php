<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db_pdo.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$school_id = trim((string)($_POST['school_id'] ?? ''));
$full_name = trim((string)($_POST['full_name'] ?? ''));
$email = strtolower(trim((string)($_POST['email'] ?? '')));
$academic_year = trim((string)($_POST['academic_year'] ?? ''));
$semester = trim((string)($_POST['semester'] ?? ''));

if ($school_id === '') {
    echo json_encode(['status' => 'error', 'message' => 'School ID is required']);
    exit;
}

if ($academic_year === '' || $semester === '') {
    echo json_encode(['status' => 'error', 'message' => 'Academic year and semester are required']);
    exit;
}

$pdo = require __DIR__ . '/../config/db_pdo.php';

try {
    // 1) Map SchoolID -> SchoolPersonID + official identity fields
    $spStmt = $pdo->prepare("
        SELECT SchoolPersonID, SchoolID, Email, PersonFname, PersonLname, FirstName, LastName
        FROM school_people
        WHERE SchoolID = ?
        LIMIT 1
    ");
    $spStmt->execute([$school_id]);
    $sp = $spStmt->fetch();

    if (!$sp) {
        echo json_encode(['status' => 'not_found', 'message' => 'You are not officially enrolled.']);
        exit;
    }

    $schoolPersonId = (int)$sp['SchoolPersonID'];

    // 2) Check enrollment status for the requested term
    $enrollStmt = $pdo->prepare("
        SELECT EnrollmentStatus, AcademicYear, Semester
        FROM student_enrollments
        WHERE SchoolPersonID = ?
          AND AcademicYear = ?
          AND Semester = ?
        LIMIT 1
    ");
    $enrollStmt->execute([$schoolPersonId, $academic_year, $semester]);
    $enroll = $enrollStmt->fetch();

    if (!$enroll || (string)$enroll['EnrollmentStatus'] !== 'Enrolled') {
        echo json_encode(['status' => 'not_enrolled', 'message' => 'You are not officially enrolled.']);
        exit;
    }

    // 3) Validate optional email + name against school_people
    $officialEmail = strtolower((string)($sp['Email'] ?? ''));
    if ($email !== '' && $officialEmail !== '' && strcasecmp($officialEmail, $email) !== 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email does not match official record.']);
        exit;
    }

    $officialFname = (string)($sp['FirstName'] ?? $sp['PersonFname'] ?? '');
    $officialLname = (string)($sp['LastName'] ?? $sp['PersonLname'] ?? '');
    $officialFullName = trim($officialFname . ' ' . $officialLname);

    if ($full_name !== '' && $officialFullName !== '' && strcasecmp($officialFullName, $full_name) !== 0) {
        echo json_encode(['status' => 'error', 'message' => 'Full name does not match official record.']);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Student verified',
        'student' => [
            'SchoolPersonID' => $schoolPersonId,
            'SchoolID' => (string)$sp['SchoolID'],
            'EnrollmentStatus' => (string)$enroll['EnrollmentStatus'],
            'AcademicYear' => (string)$enroll['AcademicYear'],
            'Semester' => (string)$enroll['Semester'],
        ],
    ]);
} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => 'Enrollment verification failed', 'details' => $e->getMessage()]);
}
?>
