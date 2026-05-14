<?php
// consultation_search_patient_ajax.php
// Returns JSON: { found: true, patients: [...] } or { found: false }

header('Content-Type: application/json');
session_start();

// ── DB credentials ──────────────────────────────────────────────
$host    = 'localhost';
$db      = 'nucaredb';
$user    = 'root';
$pass    = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    echo json_encode(['found' => false, 'error' => 'Database connection failed.']);
    exit;
}

// ── Sanitise input ──────────────────────────────────────────────
$q = trim($_GET['q'] ?? '');

if ($q === '') {
    echo json_encode(['found' => false]);
    exit;
}

// ── Query: exact PatientID OR partial name match, up to 10 results ──
$stmt = $pdo->prepare("
    SELECT
        p.PatientID,
        p.PatientFname,
        p.PatientMname,
        p.PatientLname,
        p.PatientSex,
        p.PatientBirthday,
        p.PatientPhone,
        pr.ProgramName
    FROM patients p
    LEFT JOIN programs pr ON pr.ProgramID = p.ProgramID
    WHERE
        p.PatientID = :exact
        OR CONCAT_WS(' ', p.PatientFname, p.PatientMname, p.PatientLname) LIKE :name
        OR CONCAT_WS(' ', p.PatientFname, p.PatientLname) LIKE :name
    ORDER BY p.PatientLname, p.PatientFname
    LIMIT 10
");

$stmt->execute([
    ':exact' => $q,
    ':name'  => '%' . $q . '%',
]);

$rows = $stmt->fetchAll();

if (!$rows) {
    echo json_encode(['found' => false]);
    exit;
}

// ── Format each row ─────────────────────────────────────────────
$patients = [];
foreach ($rows as $row) {
    $birthday = '';
    if (!empty($row['PatientBirthday'])) {
        $dt = DateTime::createFromFormat('Y-m-d', $row['PatientBirthday']);
        $birthday = $dt ? $dt->format('F j, Y') : $row['PatientBirthday'];
    }
    $patients[] = [
        'patientID'      => $row['PatientID'],
        'patientFname'   => $row['PatientFname'],
        'patientMname'   => $row['PatientMname'] ?? '',
        'patientLname'   => $row['PatientLname'],
        'patientSex'     => $row['PatientSex'],
        'patientBirthday'=> $birthday,
        'patientProgram' => $row['ProgramName'] ?? '—',
        'patientPhone'   => $row['PatientPhone'] ?? '—',
    ];
}

// If exact PatientID match and only one result, auto-select it
echo json_encode([
    'found'    => true,
    'patients' => $patients,
    'single'   => count($patients) === 1,  // JS auto-loads when true
]);