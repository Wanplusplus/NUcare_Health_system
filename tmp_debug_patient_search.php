<?php
require __DIR__ . '/config/db_pdo.php';
$pdo = require __DIR__ . '/config/db_pdo.php';

$q = '2024-116605';
$normalizedQ = preg_replace('/^\s*SCH[-\s]*/i', '', $q);
$likeQ = '%' . $q . '%';
$likeNormalizedQ = '%' . $normalizedQ . '%';

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
            LIMIT 1";



$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':exact' => $q,
    ':normalized_exact' => $normalizedQ,
    ':like' => $likeQ,
    ':normalized_like' => $likeNormalizedQ,
    ':person_exact' => $q,
]);

var_dump($stmt->fetch(PDO::FETCH_ASSOC));

