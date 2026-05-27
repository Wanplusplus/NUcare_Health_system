<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['patient_id']) && !isset($_SESSION['UserID'])) {
    header('Location: ../../auth/login.php');
    exit;
}

require_once __DIR__ . '/../includes/module_guard.php';
requireModule('Admin Panel', 'access');

$activeSidebarItem = 'audit_logs';
$active = 'audit_logs';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE | Audit Logs</title>
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body>
<div class="app-shell">
    <?php
    $sidebarPath = __DIR__ . '/../includes/sidebar_admin.php';
    if (file_exists($sidebarPath)) {
        require_once $sidebarPath;
    }
    ?>

    <?php
    $pdo = require __DIR__ . '/../config/db_pdo.php';

    $where = [];
    $params = [];

    // Optional filters
    $qUserId = isset($_GET['user_id']) && is_numeric($_GET['user_id']) ? (int)$_GET['user_id'] : null;
    $qModule = isset($_GET['module']) ? trim((string)$_GET['module']) : '';

    if ($qUserId !== null) {
        $where[] = 'al.UserID = ?';
        $params[] = $qUserId;
    }

    if ($qModule !== '') {
        $where[] = 'al.ModuleName = ?';
        $params[] = $qModule;
    }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $sql = "
        SELECT
            al.UserID AS UserID,
            sp.SchoolID AS SchoolID,
            CONCAT(
                sp.FirstName,
                ' ',
                COALESCE(NULLIF(sp.MiddleName, ''), ''),
                CASE WHEN sp.MiddleName IS NULL OR sp.MiddleName = '' THEN '' ELSE ' ' END,
                sp.LastName
            ) AS FullName,
            al.Action AS Action,
            al.ModuleName AS Module,
            al.ActionTimestamp AS ActionTimestamp
        FROM audit_logs al
        INNER JOIN users u ON al.UserID = u.UserID
        INNER JOIN school_people sp ON u.SchoolPersonID = sp.SchoolPersonID
        {$whereSql}
        ORDER BY al.ActionTimestamp DESC, al.AuditLogID DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Gather modules for dropdown (cheap)
    $modules = [];
    try {
        $mStmt = $pdo->query("SELECT DISTINCT ModuleName FROM audit_logs WHERE ModuleName IS NOT NULL ORDER BY ModuleName ASC");
        $modules = $mStmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $e) {
        $modules = [];
    }

    ?>

    <main class="main-content">

        <header class="page-header">
            <div>
                <p class="breadcrumb">Home / Audit Logs</p>
                <h2>Audit Logs</h2>
                <p class="page-description">Admin activity feed (WHO did WHAT).</p>
            </div>
            <div class="header-actions">
                <a href="../auth/logout.php" class="header-button outline">Logout</a>
            </div>
        </header>

        <section class="panel-card">
            <div class="panel-card-header d-flex align-items-center justify-content-between">

                <h3 class="m-0">Activity Logs</h3>
                <div class="text-muted">Total: <?= isset($rows) ? (int)count($rows) : 0 ?></div>
            </div>

        <div class="panel-card-body">
                <form method="get" class="row g-2 align-items-end" style="margin-bottom: 14px;">

                    <div class="col-md-4">
                        <label class="form-label mb-1">UserID (optional)</label>
                        <input
                            type="text"
                            name="user_id"
                            class="form-control"
                            placeholder="e.g. 5"
                            value="<?= isset($qUserId) ? htmlspecialchars((string)$qUserId) : '' ?>"
                        >
                    </div>
                    <div class="col-md-4">
                        <label class="form-label mb-1">Module (optional)</label>
                        <select name="module" class="form-select">
                            <option value="">All Modules</option>
                            <?php foreach ($modules as $m): ?>
                                <option value="<?= htmlspecialchars((string)$m) ?>" <?= $qModule === $m ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string)$m) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn" style="background:#6f7cff; color:#fff; border-radius:14px; padding:6px 14px;">Apply</button>
                        <a href="audit_logs.php" class="btn" style="border-radius:14px; border:1px solid #d0d5ff; color:#445; padding:6px 14px;">Reset</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead>
                        <tr>
                            <th>UserID</th>
                            <th>SchoolID</th>
                            <th>Full Name</th>
                            <th>Action</th>
                            <th>Module</th>
                            <th>Timestamp</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No audit logs found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $r): ?>
                                <?php
                                $fullName = (string)$r['FullName'];
                                // Trim extra spaces caused by missing MiddleName
                                $fullName = trim(preg_replace('/\s+/', ' ', $fullName));
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$r['UserID']) ?></td>
                                    <td><?= htmlspecialchars((string)$r['SchoolID']) ?></td>
                                    <td><?= htmlspecialchars($fullName) ?></td>
                                    <td><?= htmlspecialchars((string)$r['Action']) ?></td>
                                    <td><?= htmlspecialchars((string)$r['Module']) ?></td>
                                    <td><?= htmlspecialchars((string)$r['ActionTimestamp']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>
</div>

<script src="../assets/js/app.js"></script>
</body>
</html>

