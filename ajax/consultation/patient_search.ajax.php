<?php
declare(strict_types=1);

/* ════════════════════════════════════════════════════════════════════
   patient_search.ajax.php   →  PLACE IN:  ajax/consultation/
   ────────────────────────────────────────────────────────────────────
   FIXED: the exact-match check used trim($row['SchoolID']) which raises a
   deprecation/TypeError on PHP 8.1+ when SchoolID is NULL (walk-ins). All
   SchoolID reads are now cast with (string) so NULL never breaks the query
   — aligning with the spec rule "NULL SchoolID must not break queries".
══════════════════════════════════════════════════════════════════════ */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();

$pdo = require __DIR__ . '/../../config/db_pdo.php';

$q = trim((string)($_GET['q'] ?? ''));

if ($q === '') {
    echo json_encode(['ok' => false, 'message' => 'Missing query']);
    exit;
}

// Strip "SCH-" / "SCH " prefixes only — don't touch IDs like "2024-116605"
$normalized = trim((string)preg_replace('/^\s*SCH[-\s]+/i', '', $q));

$like     = '%' . $q . '%';
$likeNorm = '%' . $normalized . '%';

$sql = "
SELECT
    sp.SchoolPersonID,
    sp.SchoolID,
    sp.FirstName,
    sp.MiddleName,
    sp.LastName,
    sp.Sex,
    sp.PersonType
FROM school_people sp
WHERE
    (
        sp.SchoolID IS NOT NULL
        AND sp.PersonType IN ('Student', 'Faculty', 'Staff')
        AND (
            sp.SchoolID = :exact1
            OR sp.SchoolID = :exact_norm1
            OR sp.SchoolID LIKE :like1
            OR sp.SchoolID LIKE :like_norm1
            OR CAST(sp.SchoolPersonID AS CHAR) = :pid
            OR CONCAT_WS(' ', sp.FirstName, sp.MiddleName, sp.LastName) LIKE :like2
            OR CONCAT_WS(' ', sp.FirstName, sp.LastName) LIKE :like3
        )
    )
    OR
    (
        sp.SchoolID IS NULL
        AND sp.PersonType IN ('Guard', 'Visitor', 'ROMAC')
        AND (
            sp.PersonType LIKE :person_type
            OR CONCAT_WS(' ', sp.FirstName, sp.MiddleName, sp.LastName) LIKE :exception_like1
            OR CONCAT_WS(' ', sp.FirstName, sp.LastName) LIKE :exception_like2
        )
    )
ORDER BY
    CASE
        WHEN sp.SchoolID = :exact2      THEN 0
        WHEN sp.SchoolID = :exact_norm2 THEN 1
        WHEN sp.SchoolID LIKE :like4    THEN 2
        WHEN sp.PersonType IN ('Guard', 'Visitor', 'ROMAC') THEN 3
        ELSE 3
    END ASC,
    sp.LastName ASC
LIMIT 10
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':exact1'      => $q,
    ':exact_norm1' => $normalized,
    ':like1'       => $like,
    ':like_norm1'  => $likeNorm,
    ':pid'         => $q,
    ':like2'       => $like,
    ':like3'       => $like,
    ':person_type' => $like,
    ':exception_like1' => $like,
    ':exception_like2' => $like,
    ':exact2'      => $q,
    ':exact_norm2' => $normalized,
    ':like4'       => $like,
]);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) {
    echo json_encode(['ok' => true, 'found' => false, 'results' => []]);
    exit;
}

function buildPatient(array $p): array {
    $parts = array_filter([
        $p['FirstName'] ?? '',
        !empty($p['MiddleName']) ? mb_substr((string)$p['MiddleName'], 0, 1) . '.' : '',
        $p['LastName']  ?? '',
    ]);
    $fullName = trim(implode(' ', $parts));

    return [
        'SchoolPersonID' => (int)$p['SchoolPersonID'],
        'SchoolID'       => $p['SchoolID'],            // may be NULL (walk-in)
        'FirstName'      => $p['FirstName']  ?? '',
        'MiddleName'     => $p['MiddleName'] ?? '',
        'LastName'       => $p['LastName']   ?? '',
        'FullName'       => $fullName,
        'Sex'            => $p['Sex']        ?? '',
        'PersonType'     => $p['PersonType'] ?? '',
        'Age'            => null,
        'LoadedAt'       => date('h:i A'),
    ];
}

// Check for exact SchoolID match first — (string) cast guards against NULL.
$exactMatch = null;
foreach ($rows as $row) {
    $sid = strtolower(trim((string)($row['SchoolID'] ?? '')));
    if ($sid !== '' && ($sid === strtolower($q) || $sid === strtolower($normalized))) {
        $exactMatch = $row;
        break;
    }
}

// Exact match — return as single patient
if ($exactMatch !== null) {
    $p = buildPatient($exactMatch);
    echo json_encode([
        'ok'             => true,
        'found'          => true,
        'patient'        => $p,
        'SchoolPersonID' => $p['SchoolPersonID'],
        'SchoolID'       => $p['SchoolID'],
        'FullName'       => $p['FullName'],
        'Sex'            => $p['Sex'],
        'Age'            => $p['Age'],
        'PersonType'     => $p['PersonType'],
        'LoadedAt'       => $p['LoadedAt'],
    ]);
    exit;
}

// Only one result, no exact match — still treat as found
if (count($rows) === 1) {
    $p = buildPatient($rows[0]);
    echo json_encode([
        'ok'             => true,
        'found'          => true,
        'patient'        => $p,
        'SchoolPersonID' => $p['SchoolPersonID'],
        'SchoolID'       => $p['SchoolID'],
        'FullName'       => $p['FullName'],
        'Sex'            => $p['Sex'],
        'Age'            => $p['Age'],
        'PersonType'     => $p['PersonType'],
        'LoadedAt'       => $p['LoadedAt'],
    ]);
    exit;
}

// Multiple results — return suggestions for autocomplete
echo json_encode([
    'ok'       => true,
    'found'    => false,
    'multiple' => true,
    'results'  => array_map('buildPatient', $rows),
]);
