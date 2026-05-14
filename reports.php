<?php
/**
 * reports.php — Reports module.
 *
 * Lists generated reports and allows basic filtering by status.
 */
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/db_connect.php';

// ── Status filter ────────────────────────────────────────────────────────────
$allowedStatuses = ['', 'pending', 'complete', 'archived'];
$filterStatus    = in_array($_GET['status'] ?? '', $allowedStatuses, true)
                   ? ($_GET['status'] ?? '')
                   : '';

// ── Fetch reports ────────────────────────────────────────────────────────────
if ($filterStatus !== '') {
    $stmt = $conn->prepare(
        'SELECT ReportID, Title, Status, CreatedAt
         FROM   reports
         WHERE  Status = ?
         ORDER  BY CreatedAt DESC
         LIMIT  100'
    );
    $stmt->bind_param('s', $filterStatus);
    $stmt->execute();
    $reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $result  = $conn->query(
        'SELECT ReportID, Title, Status, CreatedAt
         FROM   reports
         ORDER  BY CreatedAt DESC
         LIMIT  100'
    );
    $reports = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

$activePage = 'reports';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE | Reports</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<div class="app-shell">

    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="main-content">
        <header class="page-header">
            <div>
                <p class="breadcrumb">Home / Reports</p>
                <h2>Reports</h2>
                <p class="page-description">Analytics, health summaries, and exportable clinical reports.</p>
            </div>
            <div class="header-actions">
                <a href="logout.php" class="header-button outline">Logout</a>
            </div>
        </header>

        <!-- ── Status filter ── -->
        <div class="panel-card">
            <div class="panel-card-header">
                <h3>Filter</h3>
            </div>
            <div class="panel-card-body">
                <form method="get" action="reports.php" class="inline-filter-form">
                    <div class="input-group" style="max-width: 220px;">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="">All statuses</option>
                            <?php foreach (['pending', 'complete', 'archived'] as $s): ?>
                                <option value="<?php echo $s; ?>"
                                    <?php echo $filterStatus === $s ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($s); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-actions" style="margin-top:.5rem;">
                        <button type="submit" class="primary-button">Filter</button>
                        <?php if ($filterStatus !== ''): ?>
                            <a href="reports.php" class="secondary-button">Clear</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- ── Reports table ── -->
        <div class="panel-card" style="margin-top: 1.5rem;">
            <div class="panel-card-header">
                <h3>Reports</h3>
            </div>
            <div class="panel-card-body">
                <?php if (empty($reports)): ?>
                    <p class="muted">No reports found.</p>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reports as $rep): ?>
                                    <tr>
                                        <td><?php echo (int) $rep['ReportID']; ?></td>
                                        <td><?php echo htmlspecialchars($rep['Title'] ?? '—'); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo htmlspecialchars($rep['Status'] ?? 'unknown'); ?>">
                                                <?php echo htmlspecialchars(ucfirst($rep['Status'] ?? '—')); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($rep['CreatedAt'] ?? '—'); ?></td>
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
