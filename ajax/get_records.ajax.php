<?php
declare(strict_types=1);
/**
 * get_records.ajax.php
 * ─────────────────────────────────────────────────────────────
 * Returns all school_people with enrollment/program info,
 * visit counts, and last-visit date for the Records List page.
 */

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo = require __DIR__ . '/../../config/db_pdo.php';

/* ══════════════════════════════════════════════════════════════
   STEP 1 — Fetch all school_people (no join risk of duplication)
══════════════════════════════════════════════════════════════ */
$peopleSql = "
    SELECT
        SchoolPersonID,
        SchoolID,
        FirstName,
        MiddleName,
        LastName,
        Email,
        Sex,
        PersonType
    FROM school_people
    ORDER BY LastName ASC, FirstName ASC
";

try {
    $peopleRows = $pdo->query($peopleSql)->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'message' => 'Database error.', 'debug' => $e->getMessage()]);
    exit;
}

if (empty($peopleRows)) {
    echo json_encode(['ok' => true, 'stats' => ['total'=>0,'students'=>0,'faculty'=>0,'staff'=>0], 'records' => []]);
    exit;
}

/* ══════════════════════════════════════════════════════════════
   STEP 2 — Fetch latest enrollment per person (separate query,
   avoids GROUP BY explosion and correlated subquery issues)
══════════════════════════════════════════════════════════════ */
$enrollSql = "
    SELECT
        se.SchoolPersonID,
        se.YearLevel,
        se.Section,
        se.AcademicYear,
        se.EnrollmentStatus,
        pr.ProgramName  AS program,
        pr.Department   AS department
    FROM student_enrollments se
    LEFT JOIN programs pr ON pr.ProgramID = se.ProgramID
    WHERE se.EnrollmentID IN (
        SELECT MAX(se2.EnrollmentID)
        FROM student_enrollments se2
        GROUP BY se2.SchoolPersonID
    )
";

$enrollMap = [];
try {
    $enrollRows = $pdo->query($enrollSql)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($enrollRows as $e) {
        $enrollMap[(int)$e['SchoolPersonID']] = $e;
    }
} catch (Throwable $e) {
    // enrollments table might be empty — non-fatal
}

/* ══════════════════════════════════════════════════════════════
   STEP 3 — Aggregate clinic visit stats per person
══════════════════════════════════════════════════════════════ */
$visitMap = [];
try {
    $visitRows = $pdo->query("
        SELECT SchoolPersonID, COUNT(*) AS visitCount, MAX(VisitDate) AS lastVisit
        FROM clinic_transactions
        GROUP BY SchoolPersonID
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($visitRows as $v) {
        $visitMap[(int)$v['SchoolPersonID']] = $v;
    }
} catch (Throwable $e) {
    // no clinic_transactions yet — non-fatal
}

/* ══════════════════════════════════════════════════════════════
   STEP 4 — Assemble records + stats
══════════════════════════════════════════════════════════════ */
$records = [];
$stats   = ['total' => 0, 'students' => 0, 'faculty' => 0, 'staff' => 0];

foreach ($peopleRows as $row) {
    $pid     = (int)$row['SchoolPersonID'];
    $enroll  = $enrollMap[$pid]  ?? [];
    $visits  = $visitMap[$pid]   ?? [];

    // Build display name
    $nameParts = array_filter([
        $row['FirstName']  ?? '',
        !empty($row['MiddleName']) ? mb_substr($row['MiddleName'], 0, 1) . '.' : '',
        $row['LastName']   ?? '',
    ]);
    $fullName = trim(implode(' ', $nameParts));

    // Year + Section string e.g. "3-A"
    $yearSection = null;
    if (!empty($enroll['YearLevel']) || !empty($enroll['Section'])) {
        $yearSection = trim(
            ($enroll['YearLevel'] ?? '') .
            (!empty($enroll['Section']) ? '-' . $enroll['Section'] : '')
        );
    }

    // Status: enrolled students use EnrollmentStatus; faculty/staff default to Active
    $status     = !empty($enroll['EnrollmentStatus']) ? $enroll['EnrollmentStatus'] : 'Active';
    $personType = $row['PersonType'] ?? 'Staff';

    $records[] = [
        'userID'      => $pid,
        'schoolID'    => $row['SchoolID']    ?? '',
        'fullName'    => $fullName,
        'email'       => $row['Email']       ?? '',
        'sex'         => $row['Sex']         ?? '',
        'personType'  => $personType,
        'program'     => $enroll['program']     ?? null,
        'department'  => $enroll['department']  ?? null,
        'yearSection' => $yearSection,
        'academicYear'=> $enroll['AcademicYear'] ?? null,
        'status'      => $status,
        'visitCount'  => (int)($visits['visitCount'] ?? 0),
        'lastVisit'   => $visits['lastVisit'] ?? null,
    ];

    $stats['total']++;
    $typeKey = strtolower($personType);
    if ($typeKey === 'student')     $stats['students']++;
    elseif ($typeKey === 'faculty') $stats['faculty']++;
    else                            $stats['staff']++;
}

echo json_encode([
    'ok'      => true,
    'stats'   => $stats,
    'records' => $records,
], JSON_UNESCAPED_UNICODE);