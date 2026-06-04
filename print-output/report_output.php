<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db_pdo.php';
require_once __DIR__ . '/../includes/audit.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['UserID'])) {
    http_response_code(401);
    exit('Unauthorized.');
}

$pdo = require __DIR__ . '/../config/db_pdo.php';
$userId = (int)$_SESSION['UserID'];

// Parse parameters
$reportType = (string)($_GET['report_type'] ?? '');
$dateRange = (string)($_GET['date_range'] ?? '');
$dateFrom = (string)($_GET['date_from'] ?? '');
$dateTo = (string)($_GET['date_to'] ?? '');
$roleFilter = (string)($_GET['role_filter'] ?? '');

if ($reportType === '') {
    exit('Missing report type.');
}

// ── Helpers ──────────────────────────────────────────────────
function buildDateWhere(string $dateRange, string $dateFrom, string $dateTo, string $column, array &$where, array &$params): void
{
    if ($dateRange === 'today') {
        $where[] = "DATE({$column}) = CURDATE()";
    } elseif ($dateRange === 'this_week') {
        $where[] = "{$column} >= (CURDATE() - INTERVAL WEEKDAY(CURDATE()) DAY)";
        $where[] = "{$column} < (CURDATE() - INTERVAL WEEKDAY(CURDATE()) DAY + INTERVAL 7 DAY)";
    } elseif ($dateRange === 'this_month') {
        $where[] = "{$column} >= (DATE_FORMAT(CURDATE(), '%Y-%m-01'))";
        $where[] = "{$column} < (DATE_FORMAT(CURDATE(), '%Y-%m-01') + INTERVAL 1 MONTH)";
    } elseif ($dateRange === 'custom' && $dateFrom !== '' && $dateTo !== '') {
        $where[] = "{$column} BETWEEN ? AND ?";
        $params[] = $dateFrom . ' 00:00:00';
        $params[] = $dateTo . ' 23:59:59';
    }
}

function dateRangeLabel(string $dateRange, string $dateFrom, string $dateTo): string
{
    return match ($dateRange) {
        'today' => 'Today',
        'this_week' => 'This Week',
        'this_month' => 'This Month',
        'custom' => $dateFrom . ' to ' . $dateTo,
        default => 'All Time',
    };
}

function reportTypeName(string $type): string
{
    return match ($type) {
        'user_report' => 'User Report',
        'audit_log_report' => 'Audit Log Report',
        'role_permission_report' => 'Role & Permission Report',
        'account_status_report' => 'Account Status Report',
        'system_usage_report' => 'System Usage Report',
        'report_history' => 'Report History',
        default => 'System Report',
    };
}

function getActorName(PDO $pdo, int $userId): string
{
    try {
        $stmt = $pdo->prepare("
            SELECT CONCAT(sp.FirstName, ' ', sp.LastName) AS FullName
            FROM users u
            INNER JOIN school_people sp ON sp.SchoolPersonID = u.SchoolPersonID
            WHERE u.UserID = ? LIMIT 1
        ");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ? trim($row['FullName']) : 'Unknown';
    } catch (Throwable $e) {
        return 'Unknown';
    }
}

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// ── Generate report content ──────────────────────────────────
$generatedAt = (new DateTimeImmutable())->format('F d, Y h:i A');
$actorName = getActorName($pdo, $userId);
$reportTitle = reportTypeName($reportType);
$dateLabel = dateRangeLabel($dateRange, $dateFrom, $dateTo);
$filterText = 'Date Range: ' . $dateLabel;
if ($roleFilter !== '') {
    $filterText .= ' | Role: ' . $roleFilter;
}

$tableHtml = '';

try {
    switch ($reportType) {

        case 'user_report':
            $where = [];
            $qParams = [];
            buildDateWhere($dateRange, $dateFrom, $dateTo, 'u.CreatedAt', $where, $qParams);
            if ($roleFilter !== '') {
                $where[] = "EXISTS (SELECT 1 FROM user_roles ur INNER JOIN roles r ON r.RoleID = ur.RoleID WHERE ur.UserID = u.UserID AND r.RoleName = ?)";
                $qParams[] = $roleFilter;
            }
            $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

            $totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $activeUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE IsActive = 1")->fetchColumn();
            $blockedUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE IsActive = 0")->fetchColumn();

            $roleBreakdown = [];
            foreach ($pdo->query("SELECT r.RoleName, COUNT(DISTINCT ur.UserID) AS cnt FROM roles r LEFT JOIN user_roles ur ON ur.RoleID = r.RoleID GROUP BY r.RoleName ORDER BY cnt DESC")->fetchAll() as $row) {
                $roleBreakdown[$row['RoleName']] = (int)$row['cnt'];
            }

            $listSql = "
                SELECT DISTINCT
                    sp.SchoolID,
                    CONCAT(sp.FirstName, ' ', COALESCE(NULLIF(sp.MiddleName, ''), ''), CASE WHEN sp.MiddleName IS NULL OR sp.MiddleName = '' THEN '' ELSE ' ' END, sp.LastName) AS FullName,
                    sp.Email,
                    COALESCE((SELECT GROUP_CONCAT(r.RoleName SEPARATOR ', ') FROM user_roles ur2 INNER JOIN roles r ON r.RoleID = ur2.RoleID WHERE ur2.UserID = u.UserID), '—') AS RoleName,
                    u.IsActive,
                    u.CreatedAt
                FROM users u
                INNER JOIN school_people sp ON sp.SchoolPersonID = u.SchoolPersonID
                {$whereSql}
                ORDER BY u.CreatedAt DESC
            ";
            $listStmt = $pdo->prepare($listSql);
            $listStmt->execute($qParams);
            $userRows = $listStmt->fetchAll(PDO::FETCH_ASSOC);

            // Summary cards
            $tableHtml .= '<table class="summary"><tr>';
            $tableHtml .= '<td><div class="label">Total Users</div><div class="value">' . $totalUsers . '</div></td>';
            $tableHtml .= '<td><div class="label">Active Users</div><div class="value" style="color:#15803d;">' . $activeUsers . '</div></td>';
            $tableHtml .= '<td><div class="label">Blocked Users</div><div class="value" style="color:#b91c1c;">' . $blockedUsers . '</div></td>';
            $tableHtml .= '</tr></table>';

            // Role breakdown
            $tableHtml .= '<div class="section">Users by Role</div>';
            $tableHtml .= '<table class="summary"><tr>';
            foreach ($roleBreakdown as $rn => $cnt) {
                $tableHtml .= '<td><div class="label">' . e($rn) . '</div><div class="value">' . $cnt . '</div></td>';
            }
            $tableHtml .= '</tr></table>';

            // User listing table
            $tableHtml .= '<div class="section">User Listing</div>';
            $tableHtml .= '<table class="data"><thead><tr>';
            $tableHtml .= '<th>School ID</th><th>Full Name</th><th>Email</th><th>Role</th><th>Status</th><th>Registered</th>';
            $tableHtml .= '</tr></thead><tbody>';
            foreach ($userRows as $ur) {
                $status = (int)$ur['IsActive'] === 1 ? 'Active' : 'Blocked';
                $tableHtml .= '<tr>';
                $tableHtml .= '<td>' . e($ur['SchoolID']) . '</td>';
                $tableHtml .= '<td>' . e($ur['FullName']) . '</td>';
                $tableHtml .= '<td>' . e($ur['Email'] ?? '—') . '</td>';
                $tableHtml .= '<td>' . e($ur['RoleName']) . '</td>';
                $tableHtml .= '<td>' . $status . '</td>';
                $tableHtml .= '<td>' . e(date('M d, Y', strtotime($ur['CreatedAt']))) . '</td>';
                $tableHtml .= '</tr>';
            }
            $tableHtml .= '</tbody></table>';
            break;

        case 'audit_log_report':
            $where = [];
            $qParams = [];
            buildDateWhere($dateRange, $dateFrom, $dateTo, 'al.ActionTimestamp', $where, $qParams);
            $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

            // Check for failed logins
            $hasFailedLogins = false;
            try {
                $flStmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE Action LIKE '%failed%' OR Action LIKE '%Failed%'");
                $flStmt->execute();
                $hasFailedLogins = (int)$flStmt->fetchColumn() > 0;
            } catch (Throwable $e) { /* ignore */ }

            $moduleSql = "SELECT ModuleName, COUNT(*) AS cnt FROM audit_logs al {$whereSql} GROUP BY ModuleName ORDER BY cnt DESC";
            $modStmt = $pdo->prepare($moduleSql);
            $modStmt->execute($qParams);
            $moduleStats = $modStmt->fetchAll(PDO::FETCH_ASSOC);
            $totalActions = array_sum(array_column($moduleStats, 'cnt'));

            $listingSql = "
                SELECT CONCAT(sp.FirstName, ' ', sp.LastName) AS FullName, al.Action, al.ModuleName, al.ActionTimestamp
                FROM audit_logs al
                INNER JOIN users u ON al.UserID = u.UserID
                INNER JOIN school_people sp ON u.SchoolPersonID = sp.SchoolPersonID
                {$whereSql}
                ORDER BY al.ActionTimestamp DESC LIMIT 500
            ";
            $listStmt = $pdo->prepare($listingSql);
            $listStmt->execute($qParams);
            $logRows = $listStmt->fetchAll(PDO::FETCH_ASSOC);

            $tableHtml .= '<table class="summary"><tr>';
            $tableHtml .= '<td><div class="label">Total Actions</div><div class="value">' . number_format($totalActions) . '</div></td>';
            $tableHtml .= '<td><div class="label">Modules Tracked</div><div class="value">' . count($moduleStats) . '</div></td>';
            $tableHtml .= '</tr></table>';

            if (!$hasFailedLogins) {
                $tableHtml .= '<div style="padding:10px 14px; background:#fffbeb; border:1px solid #fde68a; border-radius:6px; color:#92400e; font-size:10px; margin-bottom:14px;">⚠ Failed login information is not available in the current audit dataset.</div>';
            }

            $tableHtml .= '<div class="section">Activity by Module</div>';
            $tableHtml .= '<table class="data"><thead><tr><th>Module</th><th>Activity Count</th></tr></thead><tbody>';
            foreach ($moduleStats as $ms) {
                $tableHtml .= '<tr><td>' . e($ms['ModuleName'] ?? 'Unknown') . '</td><td><strong>' . number_format((int)$ms['cnt']) . '</strong></td></tr>';
            }
            $tableHtml .= '</tbody></table>';

            $tableHtml .= '<div class="section">Activity Listing (Latest 500)</div>';
            $tableHtml .= '<table class="data"><thead><tr><th>User</th><th>Action</th><th>Module</th><th>Timestamp</th></tr></thead><tbody>';
            foreach ($logRows as $lr) {
                $tableHtml .= '<tr>';
                $tableHtml .= '<td>' . e($lr['FullName']) . '</td>';
                $tableHtml .= '<td>' . e($lr['Action']) . '</td>';
                $tableHtml .= '<td>' . e($lr['ModuleName'] ?? '—') . '</td>';
                $tableHtml .= '<td>' . e(date('M d, Y h:i A', strtotime($lr['ActionTimestamp']))) . '</td>';
                $tableHtml .= '</tr>';
            }
            $tableHtml .= '</tbody></table>';
            break;

        case 'role_permission_report':
            $rolesData = $pdo->query("SELECT RoleName, Description FROM roles ORDER BY RoleName ASC")->fetchAll(PDO::FETCH_ASSOC);

            $matrixSql = "
                SELECT r.RoleName, m.ModuleName, GROUP_CONCAT(DISTINCT p.PermissionName ORDER BY p.PermissionName SEPARATOR ', ') AS Permissions
                FROM roles r
                LEFT JOIN role_permissions rp ON rp.RoleID = r.RoleID
                LEFT JOIN modules m ON m.ModuleID = rp.ModuleID
                LEFT JOIN permissions p ON p.PermissionID = rp.PermissionID
                GROUP BY r.RoleName, m.ModuleName ORDER BY r.RoleName, m.ModuleName
            ";
            $matrixData = $pdo->query($matrixSql)->fetchAll(PDO::FETCH_ASSOC);
            $roleMatrix = [];
            foreach ($matrixData as $md) {
                $roleMatrix[$md['RoleName']][$md['ModuleName']] = $md['Permissions'];
            }

            $allModules = $pdo->query("SELECT ModuleName FROM modules ORDER BY ModuleName")->fetchAll(PDO::FETCH_COLUMN);

            $roleDist = $pdo->query("SELECT r.RoleName, COUNT(DISTINCT ur.UserID) AS UserCount FROM roles r LEFT JOIN user_roles ur ON ur.RoleID = r.RoleID GROUP BY r.RoleName ORDER BY UserCount DESC")->fetchAll(PDO::FETCH_ASSOC);

            // Role distribution
            $tableHtml .= '<div class="section">Role Distribution</div>';
            $tableHtml .= '<table class="summary"><tr>';
            foreach ($roleDist as $rd) {
                $tableHtml .= '<td><div class="label">' . e($rd['RoleName']) . '</div><div class="value">' . (int)$rd['UserCount'] . '</div></td>';
            }
            $tableHtml .= '</tr></table>';

            // Role listing
            $tableHtml .= '<div class="section">Role Listing</div>';
            $tableHtml .= '<table class="data"><thead><tr><th>Role</th><th>Description</th><th>Modules</th><th>Users</th></tr></thead><tbody>';
            foreach ($rolesData as $rd) {
                $roleName = $rd['RoleName'];
                $moduleCount = isset($roleMatrix[$roleName]) ? count(array_filter($roleMatrix[$roleName])) : 0;
                $userCount = 0;
                foreach ($roleDist as $rdd) {
                    if ($rdd['RoleName'] === $roleName) { $userCount = (int)$rdd['UserCount']; break; }
                }
                $tableHtml .= '<tr><td>' . e($roleName) . '</td><td>' . e($rd['Description'] ?? '—') . '</td><td>' . $moduleCount . '</td><td>' . $userCount . '</td></tr>';
            }
            $tableHtml .= '</tbody></table>';

            // Module-Permission Matrix
            $tableHtml .= '<div class="section">Module Access & Permission Matrix</div>';
            $tableHtml .= '<table class="data"><thead><tr><th>Role</th>';
            foreach ($allModules as $mod) {
                $tableHtml .= '<th>' . e($mod) . '</th>';
            }
            $tableHtml .= '</tr></thead><tbody>';
            foreach ($rolesData as $rd) {
                $roleName = $rd['RoleName'];
                if ($roleFilter !== '' && $roleName !== $roleFilter) continue;
                $tableHtml .= '<tr><td>' . e($roleName) . '</td>';
                foreach ($allModules as $mod) {
                    $perms = $roleMatrix[$roleName][$mod] ?? null;
                    $tableHtml .= '<td>' . ($perms ? '✓ ' . e($perms) : '—') . '</td>';
                }
                $tableHtml .= '</tr>';
            }
            $tableHtml .= '</tbody></table>';
            break;

        case 'account_status_report':
            $where = [];
            $qParams = [];
            buildDateWhere($dateRange, $dateFrom, $dateTo, 'u.CreatedAt', $where, $qParams);
            if ($roleFilter !== '') {
                $where[] = "EXISTS (SELECT 1 FROM user_roles ur INNER JOIN roles r ON r.RoleID = ur.RoleID WHERE ur.UserID = u.UserID AND r.RoleName = ?)";
                $qParams[] = $roleFilter;
            }
            $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

            $totalAccounts = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $activeAccounts = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE IsActive = 1")->fetchColumn();
            $blockedAccounts = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE IsActive = 0")->fetchColumn();

            // FIX: MySQL 8+ with ONLY_FULL_GROUP_BY rejects ORDER BY on columns
            // not in the SELECT list when DISTINCT is used. We wrap in a subquery
            // to apply ORDER BY safely outside the DISTINCT context.
            $acctSql = "
                SELECT *
                FROM (
                    SELECT DISTINCT
                        sp.SchoolID,
                        sp.LastName AS _LastName,
                        CONCAT(sp.FirstName, ' ', COALESCE(NULLIF(sp.MiddleName, ''), ''), CASE WHEN sp.MiddleName IS NULL OR sp.MiddleName = '' THEN '' ELSE ' ' END, sp.LastName) AS FullName,
                        COALESCE((SELECT GROUP_CONCAT(r.RoleName SEPARATOR ', ') FROM user_roles ur2 INNER JOIN roles r ON r.RoleID = ur2.RoleID WHERE ur2.UserID = u.UserID), '—') AS RoleName,
                        u.IsActive,
                        u.LastLogin
                    FROM users u
                    INNER JOIN school_people sp ON sp.SchoolPersonID = u.SchoolPersonID
                    {$whereSql}
                ) AS _acct
                ORDER BY IsActive ASC, _LastName ASC
            ";
            $acctStmt = $pdo->prepare($acctSql);
            $acctStmt->execute($qParams);
            $acctRows = $acctStmt->fetchAll(PDO::FETCH_ASSOC);

            $tableHtml .= '<table class="summary"><tr>';
            $tableHtml .= '<td><div class="label">Total Accounts</div><div class="value">' . $totalAccounts . '</div></td>';
            $tableHtml .= '<td><div class="label">Active</div><div class="value" style="color:#15803d;">' . $activeAccounts . '</div></td>';
            $tableHtml .= '<td><div class="label">Blocked</div><div class="value" style="color:#b91c1c;">' . $blockedAccounts . '</div></td>';
            $tableHtml .= '</tr></table>';

            $tableHtml .= '<div class="section">Account Listing</div>';
            $tableHtml .= '<table class="data"><thead><tr><th>School ID</th><th>Full Name</th><th>Role</th><th>Status</th><th>Last Login</th></tr></thead><tbody>';
            foreach ($acctRows as $ar) {
                $status = (int)$ar['IsActive'] === 1 ? 'Active' : 'Blocked';
                $lastLogin = $ar['LastLogin'] ? date('M d, Y h:i A', strtotime($ar['LastLogin'])) : 'Never';
                $tableHtml .= '<tr>';
                $tableHtml .= '<td>' . e($ar['SchoolID']) . '</td>';
                $tableHtml .= '<td>' . e($ar['FullName']) . '</td>';
                $tableHtml .= '<td>' . e($ar['RoleName']) . '</td>';
                $tableHtml .= '<td>' . $status . '</td>';
                $tableHtml .= '<td>' . e($lastLogin) . '</td>';
                $tableHtml .= '</tr>';
            }
            $tableHtml .= '</tbody></table>';
            break;

        case 'system_usage_report':
            $where = [];
            $qParams = [];
            buildDateWhere($dateRange, $dateFrom, $dateTo, 'al.ActionTimestamp', $where, $qParams);
            $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

            $usageSql = "SELECT COALESCE(al.ModuleName, 'Uncategorized') AS ModuleName, COUNT(*) AS ActivityCount FROM audit_logs al {$whereSql} GROUP BY al.ModuleName ORDER BY ActivityCount DESC";
            $usageStmt = $pdo->prepare($usageSql);
            $usageStmt->execute($qParams);
            $usageRows = $usageStmt->fetchAll(PDO::FETCH_ASSOC);
            $totalActivities = array_sum(array_column($usageRows, 'ActivityCount'));

            $tableHtml .= '<table class="summary"><tr>';
            $tableHtml .= '<td><div class="label">Total Activities</div><div class="value">' . number_format($totalActivities) . '</div></td>';
            $tableHtml .= '<td><div class="label">Modules Tracked</div><div class="value">' . count($usageRows) . '</div></td>';
            if (!empty($usageRows)) {
                $tableHtml .= '<td><div class="label">Most Used</div><div class="value">' . e($usageRows[0]['ModuleName'] ?? '—') . '</div></td>';
            }
            $tableHtml .= '</tr></table>';

            $tableHtml .= '<div class="section">Module Usage Counts</div>';
            $tableHtml .= '<table class="data"><thead><tr><th>Module</th><th>Activity Count</th><th>% of Total</th></tr></thead><tbody>';
            foreach ($usageRows as $ur) {
                $pct = $totalActivities > 0 ? round(((int)$ur['ActivityCount'] / $totalActivities) * 100, 1) : 0;
                $tableHtml .= '<tr><td>' . e($ur['ModuleName']) . '</td><td><strong>' . number_format((int)$ur['ActivityCount']) . '</strong></td><td>' . $pct . '%</td></tr>';
            }
            $tableHtml .= '</tbody></table>';
            break;

        case 'report_history':
            $histSql = "
                SELECT r.ReportType, CONCAT(sp.FirstName, ' ', sp.LastName) AS GeneratedBy, r.GeneratedAt, r.ReportDescription
                FROM reports r
                INNER JOIN users u ON r.GeneratedByUserID = u.UserID
                INNER JOIN school_people sp ON u.SchoolPersonID = sp.SchoolPersonID
                ORDER BY r.GeneratedAt DESC LIMIT 200
            ";
            $histRows = $pdo->query($histSql)->fetchAll(PDO::FETCH_ASSOC);

            $tableHtml .= '<div class="section">Report Generation History</div>';
            $tableHtml .= '<table class="data"><thead><tr><th>Report Type</th><th>Generated By</th><th>Date</th><th>Description</th></tr></thead><tbody>';
            foreach ($histRows as $hr) {
                $tableHtml .= '<tr>';
                $tableHtml .= '<td>' . e($hr['ReportType'] ?? '—') . '</td>';
                $tableHtml .= '<td>' . e($hr['GeneratedBy'] ?? '—') . '</td>';
                $tableHtml .= '<td>' . e(date('M d, Y h:i A', strtotime($hr['GeneratedAt']))) . '</td>';
                $tableHtml .= '<td>' . e($hr['ReportDescription'] ?? '—') . '</td>';
                $tableHtml .= '</tr>';
            }
            $tableHtml .= '</tbody></table>';
            break;

        default:
            exit('Unknown report type.');
    }
} catch (Throwable $e) {
    exit('Report generation failed: ' . $e->getMessage());
}

// ── Build full HTML document ─────────────────────────────────
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 24px 24px 40px 24px;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #1e293b;
            font-size: 11px;
            line-height: 1.45;
        }

        .page {
            width: 100%;
        }

        .header {
            border: 2px solid #d4af37;
            border-radius: 10px;
            padding: 14px 16px;
            background: #f8fbff;
            margin-bottom: 14px;
        }

        .title-row {
            display: table;
            width: 100%;
        }

        .brand {
            display: table-cell;
            vertical-align: top;
            width: 65%;
        }

        .system-title {
            color: #0b3d91;
            font-size: 20px;
            font-weight: 800;
            margin: 0;
        }

        .report-title {
            color: #d4af37;
            font-size: 13px;
            font-weight: 700;
            margin: 2px 0 0 0;
        }

        .meta {
            display: table-cell;
            vertical-align: top;
            text-align: right;
            width: 35%;
            font-size: 10px;
        }

        .meta strong {
            color: #0b3d91;
        }

        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .summary td {
            border: 1px solid #bfd0ea;
            padding: 8px 10px;
            vertical-align: top;
            background: #ffffff;
        }

        .summary .label {
            font-size: 9px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .summary .value {
            font-size: 16px;
            font-weight: 800;
            color: #0b3d91;
            margin-top: 2px;
        }

        .section {
            margin-top: 14px;
            margin-bottom: 8px;
            color: #0b3d91;
            font-size: 12px;
            font-weight: 800;
            border-bottom: 2px solid #d4af37;
            padding-bottom: 4px;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        table.data th,
        table.data td {
            border: 1px solid #c7d2e5;
            padding: 5px 6px;
            vertical-align: top;
        }

        table.data th {
            background: #0b3d91;
            color: #ffffff;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        table.data td {
            background: #ffffff;
            font-size: 9.5px;
        }

        table.data tr:nth-child(even) td {
            background: #f8fafc;
        }

        .footer {
            margin-top: 16px;
            font-size: 9px;
            color: #64748b;
            text-align: right;
            border-top: 1px solid #e1e8f0;
            padding-top: 6px;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <div class="title-row">
                <div class="brand">
                    <div class="system-title">NUcare Health System</div>
                    <div class="report-title"><?php echo e($reportTitle); ?> — System Report</div>
                </div>
                <div class="meta">
                    <div><strong>Generated:</strong> <?php echo e($generatedAt); ?></div>
                    <div><strong>Generated By:</strong> <?php echo e($actorName); ?></div>
                    <div><strong>Date Range:</strong> <?php echo e($dateLabel); ?></div>
                    <?php if ($roleFilter !== ''): ?>
                    <div><strong>Role Filter:</strong> <?php echo e($roleFilter); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php echo $tableHtml; ?>

        <div class="footer">
            Page <span class="pagenum"></span> &mdash; Prepared for printing by NUcare Health System
        </div>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

// ── Render PDF with Dompdf ───────────────────────────────────
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$pdf = new Dompdf($options);
$pdf->loadHtml($html);
$pdf->setPaper('A4', 'portrait');
$pdf->render();

// Add page numbers in footer (wrapped for Dompdf version compatibility)
try {
    $pageCount = $pdf->getPageCount();
    for ($i = 1; $i <= $pageCount; $i++) {
        $pdf->setPage($i);
        $canvas = $pdf->getCanvas();
        $canvas->page_text(520, 810, "Page {PAGE_NUM} of {PAGE_COUNT}", null, 9, array(0, 0, 0));
    }
} catch (Throwable $e) {
    // Fallback: page numbers may not be supported in this Dompdf version
}

$filename = strtolower(str_replace([' ', '&'], ['_', ''], $reportTitle)) . '_report_' . date('Y-m-d') . '.pdf';
$pdf->stream($filename, ['Attachment' => false]);