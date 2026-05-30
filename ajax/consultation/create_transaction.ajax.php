<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// Suppress PHP warnings from appearing in JSON output
ini_set('display_errors', '0');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../../includes/audit.php';

$pdo = require __DIR__ . '/../../config/db_pdo.php';

$spid = (int)($_POST['school_person_id'] ?? 0);
$mode = $_POST['mode'] ?? 'auto'; // 'auto' | 'next'

if ($spid <= 0) {
    echo json_encode(['ok' => false, 'message' => 'Invalid school_person_id']);
    exit;
}

$actorUserId = isset($_SESSION['UserID']) ? (int)$_SESSION['UserID'] : null;
$actorSchoolPersonId = isset($_SESSION['SchoolPersonID']) ? (int)$_SESSION['SchoolPersonID'] : null;
$patientName = (string)($_SESSION['patient_name'] ?? 'Patient');

try {
    $pdo->beginTransaction();

    // Count existing transactions for this patient
    $check = $pdo->prepare("SELECT COUNT(*) FROM clinic_transactions WHERE SchoolPersonID = :id");
    $check->execute([':id' => $spid]);
    $historyCount = (int)$check->fetchColumn();

    // In auto mode, block if history exists (frontend shows modal to confirm)
    if ($mode === 'auto' && $historyCount > 0) {
        $pdo->rollBack();
        echo json_encode([
            'ok'           => false,
            'message'      => 'History exists',
            'historyCount' => $historyCount,
        ]);
        exit;
    }

    // ── 1. Insert clinic_transactions header ──────────────────────────────
    $ctStmt = $pdo->prepare(
        "INSERT INTO clinic_transactions (
            SchoolPersonID,
            VisitDate,
            ConsultationStatus,
            CreatedAt
        )
        VALUES (
            :spid,
            CURDATE(),
            'Waiting',
            NOW()
        )"
    );
    $ctStmt->execute([':spid' => $spid]);
    $ctid = (int)$pdo->lastInsertId();

    if ($ctid <= 0) {
        throw new RuntimeException('Failed to insert clinic_transactions row');
    }

    // ── 2. Insert blank physical_examinations row ─────────────────────────
    // Only insert the guaranteed columns (ClinicTransactionID + ExamDate).
    // All vitals are nullable so we omit them — no column mismatch possible.
    $peStmt = $pdo->prepare(
        "INSERT INTO physical_examinations (
            ClinicTransactionID,
            ExamDate
        )
        VALUES (
            :ctid,
            CURDATE()
        )"
    );
    $peStmt->execute([':ctid' => $ctid]);
    $peid = (int)$pdo->lastInsertId();

    if ($peid <= 0) {
        throw new RuntimeException('Failed to insert physical_examinations row');
    }

    $pdo->commit();

    // Audit: started consultation
    auditLog(
        $actorUserId,
        $actorSchoolPersonId,
        'Started consultation for ' . $patientName,
        'Consultation',
        null,
        'Started a new consultation for ' . $patientName . ' (ClinicTransactionID ' . (string)$ctid . ')',
        null
    );

    echo json_encode([
        'ok'                 => true,
        'consultation_id'    => $peid,   // PhysicalExamID — used by save_consultation
        'transaction_number' => $ctid,   // ClinicTransactionID
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode([
        'ok'      => false,
        'message' => 'Transaction failed',
        'debug'   => $e->getMessage(),
    ]);
}

