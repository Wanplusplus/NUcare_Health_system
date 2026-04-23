<?php
require_once __DIR__ . '/controllers/patientadd.controller.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handlePatientAddRequest($_POST);
}

header('Location: dashboard.php?panel=patientsPanel');
exit;
