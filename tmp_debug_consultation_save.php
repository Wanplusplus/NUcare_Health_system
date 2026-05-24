<?php
declare(strict_types=1);

// Simple deterministic debug harness for Consultation module save.
// Run: php tmp_debug_consultation_save.php

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/config/db_pdo.php';
require_once __DIR__ . '/includes/auth_guard.php';

// Ensure session + fake auth user if auth_guard requires it.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fake a logged-in staff user if none exists (you may need to adjust if auth_guard blocks).
if (!isset($_SESSION['UserID'])) {
    $_SESSION['UserID'] = 6; // likely a nurse (see seedme.sql)
}
if (!isset($_SESSION['patient_id'])) {
    // Not required by save_consultation.ajax.php, but keep consistent.
}

$pdo = getPDO();

// 1) pick an existing patient (SchoolPersonID = 1 from seed)
$schoolPersonID = 1;

// 2) create next transaction (mode=auto). Capture consultation_id from JSON.
$_POST = ['school_person_id' => $schoolPersonID, 'mode' => 'auto'];
$_FILES = [];

ob_start();
include __DIR__ . '/ajax/consultation/create_transaction.ajax.php';
$createOut = ob_get_clean();

$createJson = json_decode($createOut, true);
if (!is_array($createJson) || !($createJson['ok'] ?? false)) {
    echo "Create transaction failed:\n";
    echo $createOut . "\n";
    exit(1);
}

$consultationID = (int)($createJson['consultation_id'] ?? 0);

// 3) call save_consultation.ajax.php with minimal valid payload.
$_POST = [
    'consultation_id' => $consultationID,
    'school_person_id' => $schoolPersonID,
    'blood_pressure' => '120/80',
    'temperature' => '36.6',
    'pulse_rate' => '75',
    'weight' => '55.0',
    'complaint' => 'Test complaint',
    'service_type' => 'General Consultation',
    'consultation_status' => 'Waiting',
    'notes' => 'Test notes',
    // medicine rows left empty for first run
    'consultMedName' => [],
    'consultMedQty' => [],
];
$_FILES = [];

ob_start();
include __DIR__ . '/ajax/consultation/save_consultation.ajax.php';
$out = ob_get_clean();

echo "Create transaction output:\n";
echo $createOut . "\n\n";

echo "Save response:\n";
echo $out . "\n";

fwrite(STDERR, "Create JSON: " . json_encode($createJson) . "\n");
fwrite(STDERR, "ConsultationID: {$consultationID}\n");
fwrite(STDERR, "Save POST keys: " . implode(',', array_keys($_POST)) . "\n");

