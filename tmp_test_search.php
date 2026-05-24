<?php
require __DIR__ . '/config/db_pdo.php';
$pdo = require __DIR__ . '/config/db_pdo.php';
$q = '2024-116605';
$sql = "SELECT sp.SchoolPersonID, sp.SchoolID, sp.Sex FROM school_people sp
        WHERE sp.SchoolID = :schoolid_exact
        OR sp.SchoolID LIKE :schoolid_like
        OR CAST(sp.SchoolPersonID AS CHAR) = :schoolid_exact
        OR CAST(sp.SchoolPersonID AS CHAR) LIKE :schoolid_like
        ORDER BY (sp.SchoolID = :schoolid_exact) DESC
        LIMIT 1";
$stmt = $pdo->prepare("SELECT sp.SchoolPersonID, sp.SchoolID, sp.Sex FROM school_people sp WHERE sp.SchoolID = :schoolid_exact LIMIT 1");
$stmt->execute([':schoolid_exact'=>$q]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));

