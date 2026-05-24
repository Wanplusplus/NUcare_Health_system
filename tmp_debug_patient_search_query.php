<?php
require_once __DIR__ . '/config/db_pdo.php';
$pdo = require __DIR__ . '/config/db_pdo.php';

$q = '2024-116605';
$normalizedQ = preg_replace('/^\s*SCH[-\s]*/i', '', $q);
$normalizedQ = trim((string)$normalizedQ);

$sql = "SELECT
                sp.SchoolPersonID,
                sp.SchoolID,
                sp.FirstName,
                sp.MiddleName,
                sp.LastName,
                sp.Sex
            FROM school_people sp
            WHERE
                sp.SchoolID = :exact
                OR sp.SchoolID = :normalized_exact
                OR sp.SchoolID LIKE :like
                OR sp.SchoolID LIKE :normalized_like
                OR CAST(sp.SchoolPersonID AS CHAR) = :person_exact
            ORDER BY
                (sp.SchoolID = :exact) DESC,
                (sp.SchoolID = :normalized_exact) DESC,
                (sp.SchoolID LIKE :like) DESC,
                (sp.SchoolID LIKE :normalized_like) DESC
            LIMIT 1";

$params = [
    ':exact' => $q,
    ':normalized_exact' => $normalizedQ,
    ':like' => '%' . $q . '%',
    ':normalized_like' => '%' . $normalizedQ . '%',
    ':person_exact' => $q,
];

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
var_dump($stmt->fetch(PDO::FETCH_ASSOC));

