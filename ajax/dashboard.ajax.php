<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/db.php';

function json_response(array $payload, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$action = is_string($action) ? trim($action) : '';

try {
    if ($action === 'searchPatients') {
        $q = $_GET['q'] ?? $_POST['q'] ?? '';
        if (!is_string($q)) {
            json_response(['success' => false, 'error' => 'Invalid query'], 400);
        }
        $q = trim($q);

        if ($q === '') {
            json_response(['success' => false, 'error' => 'Query is required'], 400);
        }

        // Limit input length for safety/perf
        if (mb_strlen($q) > 100) {
            $q = mb_substr($q, 0, 100);
        }

        // NOTE: based on your patient model/SQL: patients table fields
        // PatientID, PatientFname, PatientMname, PatientLname, ProgramID, Sex, Birthday, PhoneNum
        // programs table: ProgramID, ProgramName

        $like = '%' . $q . '%';

        // Prefer matching PatientID exact; otherwise match name fields.
        // We'll return up to 10 results.
        $sql = "
            SELECT 
                p.PatientID,
                p.PatientFname,
                p.PatientMname,
                p.PatientLname,
                p.Sex,
                p.Birthday,
                pr.ProgramName,
                p.PhoneNum
            FROM patients p
            LEFT JOIN programs pr ON pr.ProgramID = p.ProgramID
            WHERE 
                p.PatientID = ?
                OR p.PatientFname LIKE ?
                OR p.PatientLname LIKE ?
                OR p.PatientMname LIKE ?
                OR CONCAT(p.PatientFname,' ',p.PatientMname,' ',p.PatientLname) LIKE ?
            ORDER BY 
                (p.PatientID = ?) DESC,
                p.PatientLname ASC,
                p.PatientFname ASC
            LIMIT 10
        ";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            json_response(['success' => false, 'error' => 'Failed to prepare query', 'details' => $conn->error], 500);
        }

        // Bind params: PatientID exact match + multiple LIKEs + order by exact match
        $stmt->bind_param(
            'ssssss',
            $q,     // p.PatientID = ?
            $like,  // p.PatientFname LIKE ?
            $like,  // p.PatientLname LIKE ?
            $like,  // p.PatientMname LIKE ?
            $like,  // full name like
            $q      // ORDER BY (p.PatientID = ?) 
        );

        if (!$stmt->execute()) {
            json_response(['success' => false, 'error' => 'Query failed', 'details' => $stmt->error], 500);
        }

        $result = $stmt->get_result();
        $patients = [];

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $patients[] = [
                    'found' => true,
                    'patientID' => (string)($row['PatientID'] ?? ''),
                    'patientFname' => (string)($row['PatientFname'] ?? ''),
                    'patientMname' => (string)($row['PatientMname'] ?? ''),
                    'patientLname' => (string)($row['PatientLname'] ?? ''),
                    'patientSex' => (string)($row['Sex'] ?? ''),
                    'patientBirthday' => (string)($row['Birthday'] ?? ''),
                    'patientProgram' => (string)($row['ProgramName'] ?? ''),
                    'patientPhone' => (string)($row['PhoneNum'] ?? ''),
                ];
            }
        }

        if (count($patients) === 0) {
            json_response(['success' => true, 'found' => false, 'patients' => []]);
        }

        // For current consultation UI it expects a single patient.
        // Return first match as "patient" + list in case you upgrade UI.
        json_response([
            'success' => true,
            'found' => true,
            'patients' => $patients,
            'patient' => $patients[0],
        ]);
    }

    json_response(['success' => false, 'error' => 'Unknown action'], 400);
} catch (Throwable $e) {
    json_response(['success' => false, 'error' => 'Server error', 'details' => $e->getMessage()], 500);
}

