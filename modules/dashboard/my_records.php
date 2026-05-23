<?php
session_start();

if (!isset($_SESSION['UserID'])) {
    header('Location: ../../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../includes/module_guard.php';
requireModule('Records', 'access');

$activeSidebarItem = 'records';

$schoolPersonId = isset($_SESSION['SchoolPersonID']) ? (int)$_SESSION['SchoolPersonID'] : 0;
$pdo = require __DIR__ . '/../../config/db_pdo.php';

if ($schoolPersonId <= 0) {
    header('Location: ../../auth/login.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT ct.ClinicTransactionID,
            ct.VisitDate,
            ct.Complaint,
            ct.ConsultationStatus,
            se.PhysicalExamID,
            mec.MedicalCertificateID,
            (SELECT 1) as _dummy
     FROM clinic_transactions ct
     LEFT JOIN physical_examinations se ON se.ClinicTransactionID = ct.ClinicTransactionID
     LEFT JOIN medical_certificates mec ON mec.ClinicTransactionID = ct.ClinicTransactionID
     WHERE ct.SchoolPersonID = ?
     ORDER BY ct.VisitDate DESC, ct.ClinicTransactionID DESC
     LIMIT 50"
);
$stmt->execute([$schoolPersonId]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

$studentName = $_SESSION['patient_name'] ?? 'User';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE | My Records</title>
    <link rel="stylesheet" href="../../assets/css/app.css">
    <style>
        :root{--clinic-yellow:#FACC15;--clinic-bg:#FEF9C3;--clinic-text:#1f2937;}
        body{background:#fff;color:var(--clinic-text);}
        .clinic-wrap{max-width:1050px;margin:20px auto;padding:0 16px;}
        .clinic-card{background:#fff;border:1px solid rgba(234,179,8,.25);border-radius:16px;box-shadow:0 10px 25px rgba(250,204,21,.18);padding:16px 16px;margin-bottom:14px;}
        .clinic-title{margin:0 0 6px 0;font-size:1.25rem;}
        .muted{color:#6b7280;}
        .record-row{display:flex;justify-content:space-between;gap:12px;padding:12px 12px;border-radius:12px;background:rgba(250,204,21,.08);border:1px solid rgba(250,204,21,.18);margin-bottom:10px;}
        .tag{display:inline-block;padding:4px 10px;border-radius:999px;background:var(--clinic-bg);border:1px solid rgba(250,204,21,.5);font-weight:700;font-size:.85rem;}
        .grid{display:grid;grid-template-columns:1fr;gap:14px;}
        @media (min-width:900px){.grid{grid-template-columns:1.1fr .9fr;}}
    </style>
</head>
<body>
<div class="app-shell">
    <?php require_once __DIR__ . '/../../includes/patient_sidebar.php'; ?>

    <main class="main-content">
        <div class="clinic-wrap">
            <div class="clinic-card">
                <h1 class="clinic-title">My Medical Records</h1>
                <div class="muted">Secure view: only your own clinic history is shown.</div>
            </div>

            <div class="grid">
                <div class="clinic-card">
                    <h2 class="clinic-title">Latest Visits</h2>
                    <?php if (!$records): ?>
                        <div class="muted">No records found.</div>
                    <?php else: ?>
                        <?php foreach ($records as $r): ?>
                            <div class="record-row">
                                <div>
                                    <div style="font-weight:900;">Visit: <?php echo htmlspecialchars((string)($r['VisitDate'] ?? '—')); ?></div>
                                    <div class="muted">Complaint: <?php echo htmlspecialchars((string)($r['Complaint'] ?? '—')); ?></div>
                                    <div class="muted">Consultation Status: <?php echo htmlspecialchars((string)($r['ConsultationStatus'] ?? '—')); ?></div>
                                </div>
                                <div class="tag">#<?php echo htmlspecialchars((string)($r['ClinicTransactionID'] ?? '')); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="clinic-card">
                    <h2 class="clinic-title">Quick Info</h2>
                    <div class="muted">Welcome, <?php echo htmlspecialchars($studentName); ?>.</div>
                    <div style="margin-top:10px;" class="muted">Records are filtered by your logged-in SchoolPersonID.</div>
                    <div style="margin-top:10px;" class="muted">Tip: Use the Dashboard for the latest record and appointment status.</div>
                </div>
            </div>
        </div>
    </main>
</div>
<script src="../../assets/js/app.js"></script>
</body>
</html>

