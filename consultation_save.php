<?php
/**
 * api/search_patient.php - AJAX endpoint to search for patients
 */
require_once __DIR__ . '/../db_connect.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$search = trim($_GET['q'] ?? '');
if (empty($search)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No search term provided']);
    exit;
}

// Search by PatientID (numeric) or by name
if (is_numeric($search)) {
    $stmt = $conn->prepare(
        "SELECT p.PatientID, p.PatientFname, p.PatientLname, p.PatientMname, 
                p.Sex, p.Birthday, p.PhoneNum, p.Address,
                pr.ProgramName
         FROM patients p
         LEFT JOIN programs pr ON p.ProgramID = pr.ProgramID
         WHERE p.PatientID = ?"
    );
    $stmt->bind_param('i', $search);
} else {
    $like = '%' . $search . '%';
    $stmt = $conn->prepare(
        "SELECT p.PatientID, p.PatientFname, p.PatientLname, p.PatientMname, 
                p.Sex, p.Birthday, p.PhoneNum, p.Address,
                pr.ProgramName
         FROM patients p
         LEFT JOIN programs pr ON p.ProgramID = pr.ProgramID
         WHERE p.PatientFname LIKE ? OR p.PatientLname LIKE ? OR CONCAT(p.PatientFname, ' ', p.PatientLname) LIKE ?
         LIMIT 1"
    );
    $stmt->bind_param('sss', $like, $like, $like);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $patient = $result->fetch_assoc();
    $fullName = trim($patient['PatientFname'] . ' ' . ($patient['PatientMname'] ? $patient['PatientMname'] . ' ' : '') . $patient['PatientLname']);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'patient' => [
            'PatientID' => $patient['PatientID'],
            'FullName' => $fullName,
            'Sex' => $patient['Sex'],
            'Birthday' => $patient['Birthday'],
            'PhoneNum' => $patient['PhoneNum'],
            'Address' => $patient['Address'],
            'ProgramName' => $patient['ProgramName'] ?? 'N/A'
        ]
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Patient not found']);
}

$stmt->close();
$conn->close();
?>