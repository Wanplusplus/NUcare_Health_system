<?php
/**
 * records.php — Patient records module.
 *
 * Lists medical records / consultation history.
 * Supports an optional ?patient_id= filter in the query string.
 */
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/db_connect.php';

// ── Optional patient filter ──────────────────────────────────────────────────
$filterPatientId = isset($_GET['patient_id']) ? (int) $_GET['patient_id'] : 0;

// ── Fetch records ────────────────────────────────────────────────────────────
if ($filterPatientId > 0) {
    $stmt = $conn->prepare(
        'SELECT r.RecordID, r.PatientID,
                CONCAT(p.FirstName, " ", p.LastName) AS PatientName,
                r.RecordDate, r.Diagnosis, r.Notes
         FROM   records r
         JOIN   patients p ON p.PatientID = r.PatientID
         WHERE  r.PatientID = ?
         ORDER  BY r.RecordDate DESC
         LIMIT  100'
    );
    $stmt->bind_param('i', $filterPatientId);
    $stmt->execute();
    $records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $result  = $conn->query(
        'SELECT r.RecordID, r.PatientID,
                CONCAT(p.FirstName, " ", p.LastName) AS PatientName,
                r.RecordDate, r.Diagnosis, r.Notes
         FROM   records r
         JOIN   patients p ON p.PatientID = r.PatientID
         ORDER  BY r.RecordDate DESC
         LIMIT  100'
    );
    $records = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

$activePage = 'records';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE | Records</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<div class="app-shell">

    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header">
            <div>
                <p class="breadcrumb">Home / Records</p>
                <h2>Records</h2>
                <p class="page-description">Patient medical records, exam summaries, and transaction history.</p>
            </div>
            <div class="header-actions">
                <a href="logout.php" class="header-button outline">Logout</a>
            </div>
        </header>

        <!-- ── Patient filter ── -->
        <div class="panel-card">
            <div class="panel-card-header">
                <h3>Filter by Patient</h3>
            </div>
            <div class="panel-card-body">
                <form method="get" action="records.php" class="inline-filter-form">
                    <div class="input-group" style="max-width: 280px;">
                        <label for="patient_id">Patient ID</label>
                        <input type="number" id="patient_id" name="patient_id"
                               placeholder="e.g. 42"
                               value="<?php echo $filterPatientId ?: ''; ?>">
                    </div>
                    <div class="form-actions" style="margin-top: .5rem;">
                        <button type="submit" class="primary-button">Filter</button>
                        <?php if ($filterPatientId > 0): ?>
                            <a href="records.php" class="secondary-button">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- ── Records table ── -->
        <div class="panel-card" style="margin-top: 1.5rem;">
            <div class="panel-card-header">
                <h3>
                    <?php echo $filterPatientId > 0
                        ? 'Records for Patient #' . $filterPatientId
                        : 'All Records (latest 100)'; ?>
                </h3>
            </div>
            <div class="panel-card-body">
                <?php if (empty($records)): ?>
                    <p class="muted">No records found<?php echo $filterPatientId > 0 ? ' for this patient' : ''; ?>.</p>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Record ID</th>
                                    <th>Patient</th>
                                    <th>Date</th>
                                    <th>Diagnosis</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($records as $rec): ?>
                                    <tr>
                                        <td><?php echo (int) $rec['RecordID']; ?></td>
                                        <td>
                                            <a href="records.php?patient_id=<?php echo (int) $rec['PatientID']; ?>">
                                                <?php echo htmlspecialchars($rec['PatientName']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($rec['RecordDate'] ?? '—'); ?></td>
                                        <td><?php echo htmlspecialchars($rec['Diagnosis'] ?? '—'); ?></td>
                                        <td><?php echo htmlspecialchars($rec['Notes'] ?? '—'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

</div>
<script src="assets/js/app.js"></script>
</body>
</html>
