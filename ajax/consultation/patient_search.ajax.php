<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$pdo = require __DIR__ . '/../../config/db_pdo.php';

$q = trim((string)($_GET['q'] ?? ''));

if ($q === '') {
    echo json_encode(['ok' => false, 'message' => 'Missing query']);
    exit;
}

$normalizedQ = preg_replace('/^\s*SCH[-\s]*/i', '', $q);
$normalizedQ = trim((string)$normalizedQ);

$likeQ = "%$q%";
$likeNormalizedQ = "%$normalizedQ%";

$sql = "
SELECT
    sp.SchoolPersonID,
    sp.SchoolID,
    sp.FirstName,
    sp.MiddleName,
    sp.LastName,
    sp.Sex
FROM school_people sp
WHERE
    sp.SchoolID = :q1
    OR sp.SchoolID = :q2
    OR sp.SchoolID LIKE :q3
    OR sp.SchoolID LIKE :q4
    OR CAST(sp.SchoolPersonID AS CHAR) = :q5
LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':q1' => $q,
    ':q2' => $normalizedQ,
    ':q3' => $likeQ,
    ':q4' => $likeNormalizedQ,
    ':q5' => $q,
]);

$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    echo json_encode(['ok' => true, 'found' => false]);
    exit;
}

$fullName = trim(implode(' ', array_filter([
    $patient['FirstName'],
    $patient['MiddleName'],
    $patient['LastName']
])));

echo json_encode([
    'ok' => true,
    'found' => true,
    'SchoolPersonID' => (int)$patient['SchoolPersonID'],
    'SchoolID' => $patient['SchoolID'],
    'FullName' => $fullName,
    'Sex' => $patient['Sex']
]);