<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db_pdo.php';
require_once __DIR__ . '/includes/audit.php';

$pdo = require __DIR__ . '/config/db_pdo.php';

// MOCK INPUTS (replace with real school feed later)
$nextAcademicYear = '2026-2027';
$nextSemester = '2';

// Previously known enrolled set (for simulation)
$previousSchoolPersonIds = [1001, 1002, 1003];

// New list received from the school (hypothetical)
$newEnrolledSchoolPersonIds = [1001, 1002]; // 1003 dropped this term

// Helper: detect presence of student_enrollments table
function tableExists(PDO $pdo, string $tableName): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1");
    $stmt->execute([$tableName]);
    return $stmt->fetchColumn() !== false;
}

$hasNewTable = tableExists($pdo, 'student_enrollments');

$affected = [];

// Enrollment updates
if ($hasNewTable) {
    // Mark dropped/not-enrolled: previously in set but not in new list
    $toDeactivate = array_values(array_diff($previousSchoolPersonIds, $newEnrolledSchoolPersonIds));
    foreach ($toDeactivate as $spId) {
            $stmt = $pdo->prepare("
            UPDATE student_enrollments
            SET EnrollmentStatus = 'Not Enrolled'
            WHERE SchoolPersonID = ?
        ");
        $stmt->execute([(int)$spId]);
        $affected[] = $spId;
        auditLog(null, (int)$spId, 'enrollment_change', 'student_enrollment', (string)$spId, 'Student deactivated: Not Enrolled', null);
    }

    // Mark (or keep) enrolled
    foreach ($newEnrolledSchoolPersonIds as $spId) {
        $existsStmt = $pdo->prepare("SELECT EnrollmentID FROM student_enrollments WHERE SchoolPersonID = ? LIMIT 1");
        $existsStmt->execute([(int)$spId]);
        $exists = $existsStmt->fetchColumn() !== false;

        if ($exists) {
            $upd = $pdo->prepare("
                UPDATE student_enrollments
                SET EnrollmentStatus = 'Enrolled',
                    AcademicYear = ?,
                    Semester = ?
                WHERE SchoolPersonID = ?
            ");
            $upd->execute([$nextAcademicYear, $nextSemester, (int)$spId]);
        } else {
            $ins = $pdo->prepare("
                INSERT INTO student_enrollments (SchoolPersonID, EnrollmentStatus, AcademicYear, Semester)
                VALUES (?, 'Enrolled', ?, ?)
            ");
            $ins->execute([(int)$spId, $nextAcademicYear, $nextSemester]);
        }

        auditLog(null, (int)$spId, 'enrollment_change', 'student_enrollment', (string)$spId, 'Student activated: Enrolled', null);
        $affected[] = $spId;
    }
} else {
    // Fallback to legacy enrolled_students table
    // Legacy table schema in your dump: EnrollmentStatus is enum('Enrolled','Not Enrolled')
    // PK: StudentID. We treat SchoolPersonID in simulation as StudentID.
    $toDeactivate = array_values(array_diff($previousSchoolPersonIds, $newEnrolledSchoolPersonIds));
    foreach ($toDeactivate as $studentId) {
        $stmt = $pdo->prepare("
            UPDATE enrolled_students
            SET EnrollmentStatus = 'Not Enrolled'
            WHERE StudentID = ?
        ");
        $stmt->execute([(int)$studentId]);
        $affected[] = (int)$studentId;
        auditLog(null, (int)$studentId, 'enrollment_change', 'legacy_enrolled_students', (string)$studentId, 'Student deactivated: Not Enrolled', null);
    }

    foreach ($newEnrolledSchoolPersonIds as $studentId) {
        $upd = $pdo->prepare("
            UPDATE enrolled_students
            SET EnrollmentStatus = 'Enrolled',
                AcademicYear = ?,
                Semester = ?
            WHERE StudentID = ?
        ");
        $upd->execute([$nextAcademicYear, $nextSemester, (int)$studentId]);
        auditLog(null, (int)$studentId, 'enrollment_change', 'legacy_enrolled_students', (string)$studentId, 'Student activated: Enrolled', null);
        $affected[] = (int)$studentId;
    }
}

// Output summary
$affected = array_values(array_unique(array_filter($affected)));
echo json_encode([
    'status' => 'success',
    'nextAcademicYear' => $nextAcademicYear,
    'nextSemester' => $nextSemester,
    'affectedSchoolPersonIds' => $affected,
    'usedStudentEnrollmentsTable' => $hasNewTable,
], JSON_PRETTY_PRINT);
