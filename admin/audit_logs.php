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

require_once __DIR__ . '/../includes/audit.php';
$pdo = require __DIR__ . '/../config/db_pdo.php';

$qSearch = trim((string)($_GET['search'] ?? ''));
$qModule = trim((string)($_GET['module'] ?? ''));
$qRange = trim((string)($_GET['range'] ?? ''));
$qCustomFrom = trim((string)($_GET['from'] ?? ''));
$qCustomTo = trim((string)($_GET['to'] ?? ''));
$schoolId = (string)($_GET['school_id'] ?? '');

// Never show technical/debug/internal actions.
$neverShowActions = [
    'login_debug_rbac_loaded',
    'login_debug_school_match',
    'failed_login',
    'failed_signup',
    'login_hash_debug',
];

$where = [];
$params = [];

if ($qSearch !== '') {
    $like = '%' . $qSearch . '%';
    $where[] = '(sp.SchoolID LIKE ? OR sp.FirstName LIKE ? OR sp.LastName LIKE ? OR CONCAT(sp.FirstName, " ", COALESCE(NULLIF(sp.MiddleName, ""), ""), CASE WHEN sp.MiddleName IS NULL OR sp.MiddleName = "" THEN "" ELSE " " END, sp.LastName) LIKE ?)';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($qModule !== '') {
    $where[] = 'al.ModuleName = ?';
    $params[] = $qModule;
}

if ($schoolId !== '') {
    $where[] = 'sp.SchoolID = ?';
    $params[] = $schoolId;
}


if ($qRange === 'today') {
    $where[] = 'DATE(al.ActionTimestamp) = CURDATE()';
} elseif ($qRange === 'this_week') {
    $where[] = 'al.ActionTimestamp >= (CURDATE() - INTERVAL WEEKDAY(CURDATE()) DAY)'
        . ' AND al.ActionTimestamp < (CURDATE() - INTERVAL WEEKDAY(CURDATE()) DAY + INTERVAL 7 DAY)';
} elseif ($qRange === 'this_month') {
    $where[] = 'al.ActionTimestamp >= (DATE_FORMAT(CURDATE(), "%Y-%m-01"))'
        . ' AND al.ActionTimestamp < (DATE_FORMAT(CURDATE(), "%Y-%m-01") + INTERVAL 1 MONTH)';
} elseif ($qRange === 'custom' && $qCustomFrom !== '' && $qCustomTo !== '') {
    $where[] = 'al.ActionTimestamp BETWEEN ? AND ?';
    $params[] = $qCustomFrom . ' 00:00:00';
    $params[] = $qCustomTo . ' 23:59:59';
}

// Exclude debug actions at display time.
if (!empty($neverShowActions)) {
    $placeholders = implode(',', array_fill(0, count($neverShowActions), '?'));
    $where[] = 'al.Action NOT IN (' . $placeholders . ')';
    foreach ($neverShowActions as $a) {
        $params[] = $a;
    }
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Modules dropdown from modules table.
$modules = [];
try {
    $mStmt = $pdo->query("SELECT ModuleName FROM modules ORDER BY ModuleName ASC");
    $modules = $mStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
    $modules = [];
}

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE | Audit Logs</title>
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="../assets/css/admin_dashboard_overrides.css">
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
                <div class="text-muted">Total: <?= (int)count($rows) ?></div>
            </div>

            <div class="panel-card-body">
                    <div class="admin-filterbar audit-filterbar-enterprise" style="margin-bottom: 14px;">

                    <div class="admin-filter" style="min-width: 280px;">
                        <label>Search</label>
                        <input
                            type="text"
                            name="search"
                            class="form-control audit-filter"
                            data-filter-key="search"
                            placeholder="Search School ID or Name"
                            value="<?= htmlspecialchars($qSearch) ?>"
                        >
                    </div>

                    <div class="admin-filter" style="min-width: 260px;">
                        <label>Module</label>
                        <select name="module" class="form-select audit-filter" data-filter-key="module">
                            <option value="">All Modules</option>
                            <?php foreach ($modules as $m): ?>
                                <?php if ($m === 'Audit Logs') continue; ?>
                                <option value="<?= htmlspecialchars((string)$m) ?>" <?= $qModule === $m ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string)$m) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="admin-filter" style="min-width: 220px;">
                        <label>Date Range</label>
                        <select name="range" class="form-select audit-filter" data-filter-key="range">
                            <option value="" <?= $qRange === '' ? 'selected' : '' ?>>All Time</option>
                            <option value="today" <?= $qRange === 'today' ? 'selected' : '' ?>>Today</option>
                            <option value="this_week" <?= $qRange === 'this_week' ? 'selected' : '' ?>>This Week</option>
                            <option value="this_month" <?= $qRange === 'this_month' ? 'selected' : '' ?>>This Month</option>
                            <option value="custom" <?= $qRange === 'custom' ? 'selected' : '' ?>>Custom Range</option>
                        </select>
                    </div>

                    <?php if ($qRange === 'custom'): ?>
                        <div class="admin-filter audit-filter-custom" style="min-width: 180px;">
                            <label>From</label>
                            <input type="date" name="from" class="form-control audit-filter" data-filter-key="from" value="<?= htmlspecialchars($qCustomFrom) ?>">
                        </div>
                        <div class="admin-filter audit-filter-custom" style="min-width: 180px;">
                            <label>To</label>
                            <input type="date" name="to" class="form-control audit-filter" data-filter-key="to" value="<?= htmlspecialchars($qCustomTo) ?>">
                        </div>
                    <?php else: ?>
                        <div class="admin-filter audit-filter-custom" style="min-width: 180px; display:none;">
                            <label>From</label>
                            <input type="date" name="from" class="form-control audit-filter" data-filter-key="from" value="<?= htmlspecialchars($qCustomFrom) ?>">
                        </div>
                        <div class="admin-filter audit-filter-custom" style="min-width: 180px; display:none;">
                            <label>To</label>
                            <input type="date" name="to" class="form-control audit-filter" data-filter-key="to" value="<?= htmlspecialchars($qCustomTo) ?>">
                        </div>
                    <?php endif; ?>

                    <div style="display:flex; gap:12px; align-items:center;">
                        <a href="audit_logs.php" class="btn admin-btn-ghost">Reset</a>
                    </div>
                </div>


                <script>
                    (function(){
                        const form = document.currentScript && document.currentScript.parentElement;
                        const getFilters = () => {
                            const params = new URLSearchParams(window.location.search);
                            // Collect only our known filter keys from inputs/selects.
                            document.querySelectorAll('.audit-filter[data-filter-key]').forEach(el => {
                                const key = el.getAttribute('data-filter-key');
                                const val = (el.value || '').trim();
                                if (val === '') params.delete(key);
                                else params.set(key, val);
                            });
                            return params;
                        };

                        const refresh = () => {
                            const params = getFilters();
                            const qs = params.toString();
                            window.location.href = 'audit_logs.php' + (qs ? ('?' + qs) : '');
                        };

                        const wire = () => {
                            document.querySelectorAll('.audit-filter[data-filter-key]').forEach(el => {
                                const evt = (el.tagName.toLowerCase() === 'input' && el.type === 'text') ? 'input' : 'change';
                                el.addEventListener(evt, () => {
                                    // Toggle custom from/to visibility based on range selection
                                    const range = document.querySelector('select.audit-filter[data-filter-key="range"]');
                                    if (range) {
                                        const isCustom = range.value === 'custom';
                                        document.querySelectorAll('.audit-filter-custom').forEach(box => {
                                            box.style.display = isCustom ? '' : 'none';
                                        });
                                    }
                                    refresh();
                                });
                            });
                        };

                        wire();
                    })();
                </script>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle admin-table">

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
                                $fullName = trim(preg_replace('/\s+/', ' ', (string)$r['FullName']));
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$r['UserID']) ?></td>
                                    <td><?= htmlspecialchars((string)$r['SchoolID']) ?></td>
                            <td><?= htmlspecialchars($fullName) ?></td>



                                    <td><?= htmlspecialchars((string)$r['Action']) ?></td>
                                    <td><?= htmlspecialchars((string)$r['Module']) ?></td>
                                    <td><?= htmlspecialchars(date('Y-m-d H:i:s', strtotime((string)$r['ActionTimestamp']))) ?></td>
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

