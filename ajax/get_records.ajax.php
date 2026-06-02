<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = require __DIR__ . '/../config/db_pdo.php';

function personFullName(array $row): string
{
    $parts = array_filter([
        trim((string)($row['FirstName'] ?? '')),
        !empty($row['MiddleName']) ? substr(trim((string)$row['MiddleName']), 0, 1) . '.' : '',
        trim((string)($row['LastName'] ?? '')),
    ]);

    return trim(implode(' ', $parts));
}

function latestColumnName(PDO $pdo, string $table, array $candidates): ?string
{
    try {
        $stmt = $pdo->query('SHOW COLUMNS FROM ' . $table);
        $columns = array_map(static fn(array $row): string => (string)$row['Field'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Throwable $e) {
        return null;
    }

    foreach ($candidates as $candidate) {
        if (in_array($candidate, $columns, true)) {
            return $candidate;
        }
    }

    return null;
}

function normalizeStudentStatus(?string $status): ?string
{
    $value = strtolower(trim((string)$status));
    if ($value === '') {
        return null;
    }

    return $value === 'enrolled' ? 'Active' : 'Inactive';
}

function normalizeEmploymentStatus(?string $status): ?string
{
    $value = strtolower(trim((string)$status));
    if ($value === '') {
        return null;
    }

    return $value === 'employed' ? 'Active' : 'Inactive';
}

function formatYearSection(?string $academicYear, ?string $semester): string
{
    $academicYear = trim((string)$academicYear);
    $semester = trim((string)$semester);

    if ($academicYear === '' && $semester === '') {
        return '';
    }

    if ($academicYear !== '' && $semester !== '') {
        return $academicYear . ' • ' . $semester;
    }

    return $academicYear !== '' ? $academicYear : $semester;
}

try {
    $birthdayColumn = latestColumnName($pdo, 'school_people', ['Birthday', 'BirthDate', 'BirthDay']);
    $contactColumn = latestColumnName($pdo, 'school_people', ['ContactNumber', 'ContactNo', 'Phone', 'Mobile']);

    $birthdaySelect = $birthdayColumn ? 'sp.`' . $birthdayColumn . '` AS Birthday' : 'NULL AS Birthday';
    $contactSelect = $contactColumn ? 'sp.`' . $contactColumn . '` AS ContactNumber' : 'NULL AS ContactNumber';

    $sql = "
        SELECT
            sp.SchoolPersonID,
            sp.SchoolID,
            sp.FirstName,
            sp.MiddleName,
            sp.LastName,
            sp.Email,
            sp.Sex,
            sp.PersonType,
            {$birthdaySelect},
            {$contactSelect},

            se.ProgramID,
            se.AcademicYear,
            se.Semester,
            se.EnrollmentStatus,
            pr.ProgramName,
            pr.Department AS StudentDepartment,

            COALESCE(v.visitCount, 0) AS visitCount,
            v.lastVisit
        FROM school_people sp
        LEFT JOIN (
            SELECT se1.*
            FROM student_enrollments se1
            INNER JOIN (
                SELECT SchoolPersonID, MAX(EnrollmentID) AS EnrollmentID
                FROM student_enrollments
                GROUP BY SchoolPersonID
            ) latest_enrollment
                ON latest_enrollment.EnrollmentID = se1.EnrollmentID
        ) se
            ON se.SchoolPersonID = sp.SchoolPersonID
        LEFT JOIN programs pr
            ON pr.ProgramID = se.ProgramID
        LEFT JOIN (
            SELECT
                SchoolPersonID,
                COUNT(*) AS visitCount,
                MAX(VisitDate) AS lastVisit
            FROM clinic_transactions
            GROUP BY SchoolPersonID
        ) v
            ON v.SchoolPersonID = sp.SchoolPersonID
        ORDER BY sp.LastName ASC, sp.FirstName ASC, sp.SchoolPersonID ASC
    ";


    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
        'message' => 'Database error.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$records = [];
$stats = [
    'total' => 0,
    'students' => 0,
    'faculty' => 0,
    'staff' => 0,
];

foreach ($rows as $row) {

        $personType = trim((string)($row['PersonType'] ?? ''));
        $fullName = personFullName($row);

        $program = $row['ProgramName'] ?? null;
        $department = $row['StudentDepartment'] ?? null;
        $yearSection = formatYearSection($row['AcademicYear'] ?? null, $row['Semester'] ?? null);
        $academicYear = $row['AcademicYear'] ?? null;
        $semester = $row['Semester'] ?? null;
        $enrollmentStatus = $row['EnrollmentStatus'] ?? null;

        $status = $personType === 'Student'
            ? normalizeStudentStatus($enrollmentStatus)
            : null;

        $records[] = [
            'schoolPersonID' => (int)($row['SchoolPersonID'] ?? 0),
            'schoolID' => $row['SchoolID'] ?? null,
            'fullName' => $fullName,
            'firstName' => $row['FirstName'] ?? null,
            'middleName' => $row['MiddleName'] ?? null,
            'lastName' => $row['LastName'] ?? null,
            'email' => $row['Email'] ?? null,
            'sex' => $row['Sex'] ?? null,
            'personType' => $personType !== '' ? $personType : '—',
            'birthday' => $row['Birthday'] ?? null,
            'contactNumber' => $row['ContactNumber'] ?? null,
            'program' => $program,
            'department' => $department,
            'positionTitle' => null,
            'yearSection' => $yearSection,
            'academicYear' => $academicYear,
            'semester' => $semester,
            'enrollmentStatus' => $enrollmentStatus,
            'employmentStatus' => null,
            'status' => $status,
            'visitCount' => (int)($row['visitCount'] ?? 0),
            'lastVisit' => $row['lastVisit'] ?? null,
        ];

        $stats['total']++;
        if ($personType === 'Student') {
            $stats['students']++;
        } elseif ($personType === 'Faculty') {
            $stats['faculty']++;
        } elseif ($personType === 'Staff') {
            $stats['staff']++;
        }
    }


echo json_encode([
    'ok' => true,
    'stats' => $stats,
    'records' => $records,
], JSON_UNESCAPED_UNICODE);
