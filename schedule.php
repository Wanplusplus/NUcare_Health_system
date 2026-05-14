<?php
/**
 * schedule.php — Appointments and scheduling module.
 *
 * Shows the schedule list filtered by date range.
 * Defaults to the current week.
 */
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/db_connect.php';

// ── Date range filter ────────────────────────────────────────────────────────
$dateFrom = $_GET['from'] ?? date('Y-m-d', strtotime('monday this week'));
$dateTo   = $_GET['to']   ?? date('Y-m-d', strtotime('sunday this week'));

// Sanitise — must be valid dates, else fall back to current week.
if (!strtotime($dateFrom)) $dateFrom = date('Y-m-d', strtotime('monday this week'));
if (!strtotime($dateTo))   $dateTo   = date('Y-m-d', strtotime('sunday this week'));

// ── Fetch schedule ────────────────────────────────────────────────────────────
$stmt = $conn->prepare(
    'SELECT s.ScheduleID,
            CONCAT(p.FirstName, " ", p.LastName) AS PatientName,
            s.ScheduleDate, s.TimeSlot, s.Purpose, s.Status
     FROM   schedule s
     JOIN   patients p ON p.PatientID = s.PatientID
     WHERE  s.ScheduleDate BETWEEN ? AND ?
     ORDER  BY s.ScheduleDate ASC, s.TimeSlot ASC
     LIMIT  200'
);
$stmt->bind_param('ss', $dateFrom, $dateTo);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$activePage = 'schedule';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE | Schedule</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<div class="app-shell">

    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header">
            <div>
                <p class="breadcrumb">Home / Schedule</p>
                <h2>Schedule</h2>
                <p class="page-description">Appointments and scheduled consultations.</p>
            </div>
            <div class="header-actions">
                <a href="logout.php" class="header-button outline">Logout</a>
            </div>
        </header>

        <!-- ── Date range filter ── -->
        <div class="panel-card">
            <div class="panel-card-header">
                <h3>Date Range</h3>
            </div>
            <div class="panel-card-body">
                <form method="get" action="schedule.php" class="inline-filter-form">
                    <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap:.75rem;">
                        <div class="input-group">
                            <label for="from">From</label>
                            <input type="date" id="from" name="from"
                                   value="<?php echo htmlspecialchars($dateFrom); ?>">
                        </div>
                        <div class="input-group">
                            <label for="to">To</label>
                            <input type="date" id="to" name="to"
                                   value="<?php echo htmlspecialchars($dateTo); ?>">
                        </div>
                    </div>
                    <div class="form-actions" style="margin-top:.5rem;">
                        <button type="submit" class="primary-button">Apply</button>
                        <a href="schedule.php" class="secondary-button">This Week</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- ── Appointment list ── -->
        <div class="panel-card" style="margin-top: 1.5rem;">
            <div class="panel-card-header">
                <h3>Appointments
                    <span class="count-badge"><?php echo count($appointments); ?></span>
                </h3>
            </div>
            <div class="panel-card-body">
                <?php if (empty($appointments)): ?>
                    <p class="muted">No appointments in this date range.</p>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Patient</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $appt): ?>
                                    <tr>
                                        <td><?php echo (int) $appt['ScheduleID']; ?></td>
                                        <td><?php echo htmlspecialchars($appt['PatientName']); ?></td>
                                        <td><?php echo htmlspecialchars($appt['ScheduleDate']); ?></td>
                                        <td><?php echo htmlspecialchars($appt['TimeSlot'] ?? '—'); ?></td>
                                        <td><?php echo htmlspecialchars($appt['Purpose'] ?? '—'); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo htmlspecialchars(strtolower($appt['Status'] ?? 'unknown')); ?>">
                                                <?php echo htmlspecialchars(ucfirst($appt['Status'] ?? '—')); ?>
                                            </span>
                                        </td>
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
