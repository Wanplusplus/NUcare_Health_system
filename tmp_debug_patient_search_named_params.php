<?php
test();

function test(): void {
    $sql = "SELECT sp.SchoolPersonID, sp.SchoolID FROM school_people sp WHERE sp.SchoolID = :exact OR sp.SchoolID = :normalized_exact OR sp.SchoolID LIKE :like OR sp.SchoolID LIKE :normalized_like OR CAST(sp.SchoolPersonID AS CHAR) = :person_exact";
    preg_match_all('/:[A-Za-z0-9_]+/', $sql, $m);
    $all = $m[0];
    $uniq = array_values(array_unique($all));
    echo 'Found named tokens: ' . count($all) . PHP_EOL;
    echo 'Unique named tokens: ' . implode(', ', $uniq) . PHP_EOL;
}

