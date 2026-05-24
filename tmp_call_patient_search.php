<?php
// Local CLI test harness for ajax/consultation/patient_search.ajax.php
$_GET['q'] = '2024-116605';

ob_start();
include __DIR__ . '/ajax/consultation/patient_search.ajax.php';
$out = ob_get_clean();
echo $out;




