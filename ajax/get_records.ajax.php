<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = require __DIR__ . '/../../config/db_pdo.php';

try {
    $sql = "
        SELECT
            sp.SchoolPersonID AS userID,
            sp.SchoolID,
            sp.FirstName,
            sp.MiddleName,
            sp.LastName,
            sp.Email,
            sp.Sex,
            sp.PersonType,
            se.AcademicYear,
            se.Semester,
            se.EnrollmentStatus,
            pr.ProgramName AS program,
            pr.Department  AS department,
            COALESCE(v.visitCount, 0) AS visitCount,
            v.lastVisit
        FROM school_people sp
        LEFT JOIN student_enrollments se
            ON se.EnrollmentID = (
                SELECT MAX(se2.EnrollmentID)
                FROM student_enrollments se2
                WHERE se2.SchoolPersonID = sp.SchoolPersonID
            )
        LEFT JOIN programs pr ON pr.ProgramID = se.ProgramID
        LEFT JOIN (
            SELECT
                SchoolPersonID,
                COUNT(*)       AS visitCount,
                MAX(VisitDate) AS lastVisit
            FROM clinic_transactions
            GROUP BY SchoolPersonID
        ) v ON v.SchoolPersonID = sp.SchoolPersonID
        ORDER BY sp.LastName ASC, sp.FirstName ASC
    ";

    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    echo json_encode([
        'ok'      => false,
        'message' => 'Database error.',
        'debug'   => $e->getMessage(),
    ]);
    exit;
}

$records = [];
$stats   = ['total' => 0, 'students' => 0, 'faculty' => 0, 'staff' => 0];

/*
 * Enrollment status mapping:
 *   NULL              → person has no enrollment row (Faculty/Staff) → 'Active'
 *   'Enrolled'        → currently enrolled student                  → 'Active'
 *   'Active'          → explicit active flag                        → 'Active'
 *   anything else     → dropped, leave of absence, etc.            → 'Inactive'
 */
$activeStatuses = ['enrolled', 'active'];

foreach ($rows as $row) {
    $fullName = trim(implode(' ', array_filter([
        trim((string)($row['FirstName']  ?? '')),
        !empty($row['MiddleName'])
            ? mb_substr(trim((string)$row['MiddleName']), 0, 1) . '.'
            : '',
        trim((string)($row['LastName']   ?? '')),
    ])));

    $personType = (string)($row['PersonType'] ?? 'Staff');

    $rawStatus = $row['EnrollmentStatus'] ?? null;
    if ($rawStatus === null) {
        // No enrollment row → treat as active (Faculty/Staff fall here)
        $status = 'Active';
    } elseif (in_array(strtolower($rawStatus), $activeStatuses, true)) {
        $status = 'Active';
    } else {
        $status = 'Inactive';
    }

    $records[] = [
        'userID'      => (int)$row['userID'],
        'schoolID'    => $row['SchoolID']   ?? '',
        'fullName'    => $fullName,
        'email'       => $row['Email']      ?? '',
        'sex'         => $row['Sex']        ?? '',
        'personType'  => $personType,
        'program'     => $row['program']    ?? null,
        'department'  => $row['department'] ?? null,
        'yearSection' => !empty($row['Semester']) ? $row['Semester'] : null,
        'academicYear'=> $row['AcademicYear'] ?? null,
        'status'      => $status,
        'visitCount'  => (int)($row['visitCount'] ?? 0),
        'lastVisit'   => $row['lastVisit']  ?? null,
    ];

    $stats['total']++;
    if ($personType === 'Student')     $stats['students']++;
    elseif ($personType === 'Faculty') $stats['faculty']++;
    else                               $stats['staff']++;
}

echo json_encode([
    'ok'      => true,
    'stats'   => $stats,
    'records' => $records,
], JSON_UNESCAPED_UNICODE);