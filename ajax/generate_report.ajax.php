<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['UserID'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized.']);
    exit;
}

require_once __DIR__ . '/../config/db_pdo.php';
require_once __DIR__ . '/../includes/audit.php';

$pdo = require __DIR__ . '/../config/db_pdo.php';
$userId = (int)$_SESSION['UserID'];

// Parse JSON body
$raw = file_get_contents('php://input');
$params = json_decode($raw, true);

if (!$params || !isset($params['report_type'])) {
    echo json_encode(['ok' => false, 'message' => 'Invalid request parameters.']);
    exit;
}

$reportType = (string)($params['report_type'] ?? '');
$dateRange = (string)($params['date_range'] ?? '');
$dateFrom = (string)($params['date_from'] ?? '');
$dateTo = (string)($params['date_to'] ?? '');
$roleFilter = (string)($params['role_filter'] ?? '');

// ── Helper: Build date WHERE clause ──────────────────────────
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

// ── Helper: Date range label ─────────────────────────────────
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

// ── Helper: Human-readable report name ───────────────────────
function reportTypeName(string $type): string
{
    return match ($type) {
        'user_report' => 'User Report',
        'audit_log_report' => 'Audit Log Report',
        'role_permission_report' => 'Role & Permission Report',
        'account_status_report' => 'Account Status Report',
        'system_usage_report' => 'System Usage Report',
        'report_history' => 'Report History',
        default => 'Unknown Report',
    };
}

// ── Helper: Get actor name ───────────────────────────────────
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

// ── Helper: Log report generation ────────────────────────────
function logReportGeneration(PDO $pdo, int $userId, string $reportType, string $dateRange, string $dateFrom, string $dateTo, string $roleFilter): void
{
    try {
        $desc = reportTypeName($reportType) . ' | Range: ' . dateRangeLabel($dateRange, $dateFrom, $dateTo);
        if ($roleFilter !== '') {
            $desc .= ' | Role: ' . $roleFilter;
        }
        $stmt = $pdo->prepare("INSERT INTO reports (GeneratedByUserID, ReportType, ReportDescription) VALUES (?, ?, ?)");
        $stmt->execute([$userId, reportTypeName($reportType), $desc]);
    } catch (Throwable $e) {
        // Non-critical; ignore
    }
}

// ══════════════════════════════════════════════════════════════
// REPORT GENERATION
// ══════════════════════════════════════════════════════════════

$html = '';
$title = reportTypeName($reportType);
$meta = '';

try {
    switch ($reportType) {

        // ──────────────────────────────────────────────────────
        // 1. USER REPORT
        // ──────────────────────────────────────────────────────
        case 'user_report':
            $where = [];
            $qParams = [];

            // Date filter on registration
            buildDateWhere($dateRange, $dateFrom, $dateTo, 'u.CreatedAt', $where, $qParams);

            // Role filter
            if ($roleFilter !== '') {
                $where[] = "EXISTS (
                    SELECT 1 FROM user_roles ur
                    INNER JOIN roles r ON r.RoleID = ur.RoleID
                    WHERE ur.UserID = u.UserID AND r.RoleName = ?
                )";
                $qParams[] = $roleFilter;
            }

            $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

            // Summary stats
            $totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $activeUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE IsActive = 1")->fetchColumn();
            $blockedUsers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE IsActive = 0")->fetchColumn();

            // Users by role
            $roleBreakdown = [];
            $roleSql = "
                SELECT r.RoleName, COUNT(DISTINCT ur.UserID) AS cnt
                FROM roles r
                LEFT JOIN user_roles ur ON ur.RoleID = r.RoleID
                GROUP BY r.RoleName
                ORDER BY cnt DESC
            ";
            foreach ($pdo->query($roleSql)->fetchAll() as $row) {
                $roleBreakdown[$row['RoleName']] = (int)$row['cnt'];
            }

            // User listing
            $listSql = "
                SELECT DISTINCT
                    u.UserID,
                    sp.SchoolID,
                    CONCAT(sp.FirstName, ' ', COALESCE(NULLIF(sp.MiddleName, ''), ''), CASE WHEN sp.MiddleName IS NULL OR sp.MiddleName = '' THEN '' ELSE ' ' END, sp.LastName) AS FullName,
                    sp.Email,
                    COALESCE(
                        (SELECT GROUP_CONCAT(r.RoleName SEPARATOR ', ')
                         FROM user_roles ur2
                         INNER JOIN roles r ON r.RoleID = ur2.RoleID
                         WHERE ur2.UserID = u.UserID),
                        '—'
                    ) AS RoleName,
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

            $dateLabel = dateRangeLabel($dateRange, $dateFrom, $dateTo);
            $meta = count($userRows) . ' users found | ' . $dateLabel;

            // Build HTML
            $html .= '<div class="report-section-title">Summary</div>';
            $html .= '<div class="report-summary-cards">';
            $html .= '<div class="report-summary-card"><div class="summary-label">Total Users</div><div class="summary-value">' . $totalUsers . '</div></div>';
            $html .= '<div class="report-summary-card"><div class="summary-label">Active Users</div><div class="summary-value" style="color:#15803d;">' . $activeUsers . '</div></div>';
            $html .= '<div class="report-summary-card"><div class="summary-label">Blocked Users</div><div class="summary-value" style="color:#b91c1c;">' . $blockedUsers . '</div></div>';
            $html .= '</div>';

            $html .= '<div class="report-section-title">Users by Role</div>';
            $html .= '<div class="report-summary-cards">';
            foreach ($roleBreakdown as $roleName => $cnt) {
                $html .= '<div class="report-summary-card"><div class="summary-label">' . htmlspecialchars($roleName) . '</div><div class="summary-value">' . $cnt . '</div></div>';
            }
            $html .= '</div>';

            $html .= '<div class="report-section-title">User Listing</div>';
            $html .= '<div class="table-responsive">';
            $html .= '<table class="table table-striped table-hover align-middle admin-table">';
            $html .= '<thead><tr>';
            $html .= '<th>School ID</th><th>Full Name</th><th>Email</th><th>Role</th><th>Account Status</th><th>Date Registered</th>';
            $html .= '</tr></thead><tbody>';
            if (empty($userRows)) {
                $html .= '<tr><td colspan="6" class="text-center text-muted">No users found for the selected filters.</td></tr>';
            } else {
                foreach ($userRows as $ur) {
                    $isActive = (int)$ur['IsActive'] === 1;
                    $html .= '<tr>';
                    $html .= '<td>' . htmlspecialchars($ur['SchoolID']) . '</td>';
                    $html .= '<td>' . htmlspecialchars($ur['FullName']) . '</td>';
                    $html .= '<td>' . htmlspecialchars($ur['Email'] ?? '—') . '</td>';
                    $html .= '<td><span class="admin-role-pill">' . htmlspecialchars($ur['RoleName']) . '</span></td>';
                    $html .= '<td>' . ($isActive ? '<span class="admin-badge admin-badge-success">Active</span>' : '<span class="admin-badge admin-badge-muted">Blocked</span>') . '</td>';
                    $html .= '<td>' . htmlspecialchars(date('M d, Y', strtotime($ur['CreatedAt']))) . '</td>';
                    $html .= '</tr>';
                }
            }
            $html .= '</tbody></table></div>';
            break;

        // ──────────────────────────────────────────────────────
        // 2. AUDIT LOG REPORT
        // ──────────────────────────────────────────────────────
        case 'audit_log_report':
            $where = [];
            $qParams = [];

            buildDateWhere($dateRange, $dateFrom, $dateTo, 'al.ActionTimestamp', $where, $qParams);

            $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

            // Check for failed login data
            $hasFailedLogins = false;
            try {
                $flStmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs WHERE Action LIKE '%failed%' OR Action LIKE '%Failed%'");
                $flStmt->execute();
                $hasFailedLogins = (int)$flStmt->fetchColumn() > 0;
            } catch (Throwable $e) {
                // ignore
            }

            // User activity summary
            $activitySummarySql = "
                SELECT
                    CONCAT(sp.FirstName, ' ', sp.LastName) AS FullName,
                    sp.SchoolID,
                    COUNT(*) AS ActionCount
                FROM audit_logs al
                INNER JOIN users u ON al.UserID = u.UserID
                INNER JOIN school_people sp ON u.SchoolPersonID = sp.SchoolPersonID
                {$whereSql}
                GROUP BY al.UserID, sp.FirstName, sp.LastName, sp.SchoolID
                ORDER BY ActionCount DESC
            ";
            $actStmt = $pdo->prepare($activitySummarySql);
            $actStmt->execute($qParams);
            $activitySummary = $actStmt->fetchAll(PDO::FETCH_ASSOC);

            // Activity by module
            $moduleSql = "
                SELECT ModuleName, COUNT(*) AS cnt
                FROM audit_logs al
                {$whereSql}
                GROUP BY ModuleName
                ORDER BY cnt DESC
           ";
            $modStmt = $pdo->prepare($moduleSql);
            $modStmt->execute($qParams);
            $moduleStats = $modStmt->fetchAll(PDO::FETCH_ASSOC);

            // Activity listing
            $listingSql = "
                SELECT
                    CONCAT(sp.FirstName, ' ', sp.LastName) AS FullName,
                    sp.SchoolID,
                    al.Action,
                    al.ModuleName,
                    al.ActionTimestamp
                FROM audit_logs al
                INNER JOIN users u ON al.UserID = u.UserID
                INNER JOIN school_people sp ON u.SchoolPersonID = sp.SchoolPersonID
                {$whereSql}
                ORDER BY al.ActionTimestamp DESC
                LIMIT 500
            ";
            $listStmt2 = $pdo->prepare($listingSql);
            $listStmt2->execute($qParams);
            $logRows = $listStmt2->fetchAll(PDO::FETCH_ASSOC);

            $dateLabel = dateRangeLabel($dateRange, $dateFrom, $dateTo);
            $totalActions = array_sum(array_column($moduleStats, 'cnt'));
            $meta = number_format($totalActions) . ' actions | ' . $dateLabel;

            $html .= '<div class="report-section-title">User Activity Summary</div>';
            if (!$hasFailedLogins) {
                $html .= '<div class="report-note"><i class="bi bi-info-circle"></i>Failed login information is not available in the current audit dataset.</div>';
            }
            $html .= '<div class="table-responsive">';
            $html .= '<table class="table table-striped table-hover align-middle admin-table">';
            $html .= '<thead><tr><th>User</th><th>School ID</th><th>Total Actions</th></tr></thead><tbody>';
            if (empty($activitySummary)) {
                $html .= '<tr><td colspan="3" class="text-center text-muted">No activity found for the selected date range.</td></tr>';
            } else {
                foreach ($activitySummary as $as) {
                    $html .= '<tr>';
                    $html .= '<td>' . htmlspecialchars($as['FullName']) . '</td>';
                    $html .= '<td>' . htmlspecialchars($as['SchoolID']) . '</td>';
                    $html .= '<td><strong>' . (int)$as['ActionCount'] . '</strong></td>';
                    $html .= '</tr>';
                }
            }
            $html .= '</tbody></table></div>';

            $html .= '<div class="report-section-title">Activity by Module</div>';
            $html .= '<div class="report-summary-cards">';
            foreach ($moduleStats as $ms) {
                $html .= '<div class="report-summary-card"><div class="summary-label">' . htmlspecialchars($ms['ModuleName'] ?? 'Unknown') . '</div><div class="summary-value">' . (int)$ms['cnt'] . '</div></div>';
            }
            $html .= '</div>';

            $html .= '<div class="report-section-title">Activity Listing (Latest 500)</div>';
            $html .= '<div class="table-responsive">';
            $html .= '<table class="table table-striped table-hover align-middle admin-table">';
            $html .= '<thead><tr><th>User</th><th>Action</th><th>Module</th><th>Timestamp</th></tr></thead><tbody>';
            if (empty($logRows)) {
                $html .= '<tr><td colspan="4" class="text-center text-muted">No audit logs found.</td></tr>';
            } else {
                foreach ($logRows as $lr) {
                    $html .= '<tr>';
                    $html .= '<td>' . htmlspecialchars($lr['FullName']) . '</td>';
                    $html .= '<td>' . htmlspecialchars($lr['Action']) . '</td>';
                    $html .= '<td><span class="admin-module-chip">' . htmlspecialchars($lr['ModuleName'] ?? '—') . '</span></td>';
                    $html .= '<td>' . htmlspecialchars(date('M d, Y h:i A', strtotime($lr['ActionTimestamp']))) . '</td>';
                    $html .= '</tr>';
                }
            }
            $html .= '</tbody></table></div>';
            break;

        // ──────────────────────────────────────────────────────
        // 3. ROLE & PERMISSION REPORT
        // ──────────────────────────────────────────────────────
        case 'role_permission_report':
            // Role listing
            $rolesSql = "SELECT RoleName, Description FROM roles ORDER BY RoleName ASC";
            $rolesData = $pdo->query($rolesSql)->fetchAll(PDO::FETCH_ASSOC);

            // Module access matrix
            $matrixSql = "
                SELECT
                    r.RoleName,
                    m.ModuleName,
                    GROUP_CONCAT(DISTINCT p.PermissionName ORDER BY p.PermissionName SEPARATOR ', ') AS Permissions
                FROM roles r
                LEFT JOIN role_permissions rp ON rp.RoleID = r.RoleID
                LEFT JOIN modules m ON m.ModuleID = rp.ModuleID
                LEFT JOIN permissions p ON p.PermissionID = rp.PermissionID
                GROUP BY r.RoleName, m.ModuleName
                ORDER BY r.RoleName, m.ModuleName
            ";
            $matrixData = $pdo->query($matrixSql)->fetchAll(PDO::FETCH_ASSOC);

            // Group by role
            $roleMatrix = [];
            foreach ($matrixData as $md) {
                $roleMatrix[$md['RoleName']][$md['ModuleName']] = $md['Permissions'];
            }

            // Get all modules and permissions
            $allModules = $pdo->query("SELECT ModuleName FROM modules ORDER BY ModuleName")->fetchAll(PDO::FETCH_COLUMN);
            $allPermissions = $pdo->query("SELECT PermissionName FROM permissions ORDER BY PermissionName")->fetchAll(PDO::FETCH_COLUMN);

            // Role distribution
            $roleDistSql = "
                SELECT r.RoleName, COUNT(DISTINCT ur.UserID) AS UserCount
                FROM roles r
                LEFT JOIN user_roles ur ON ur.RoleID = r.RoleID
                GROUP BY r.RoleName
                ORDER BY UserCount DESC
            ";
            $roleDist = $pdo->query($roleDistSql)->fetchAll(PDO::FETCH_ASSOC);

            $meta = count($rolesData) . ' roles | ' . count($allModules) . ' modules | ' . count($allPermissions) . ' permissions';

            $html .= '<div class="report-section-title">Role Distribution</div>';
            $html .= '<div class="report-summary-cards">';
            foreach ($roleDist as $rd) {
                $html .= '<div class="report-summary-card"><div class="summary-label">' . htmlspecialchars($rd['RoleName']) . '</div><div class="summary-value">' . (int)$rd['UserCount'] . ' <span style="font-size:14px; font-weight:600;">users</span></div></div>';
            }
            $html .= '</div>';

            // Role listing
            $html .= '<div class="report-section-title">Role Listing</div>';
            $html .= '<div class="table-responsive">';
            $html .= '<table class="table table-striped table-hover align-middle admin-table">';
            $html .= '<thead><tr><th>Role</th><th>Description</th><th>Modules Assigned</th><th>Users</th></tr></thead><tbody>';
            foreach ($rolesData as $rd) {
                $moduleName = $rd['RoleName'];
                $moduleCount = isset($roleMatrix[$moduleName]) ? count(array_filter($roleMatrix[$moduleName])) : 0;
                $userCount = 0;
                foreach ($roleDist as $rdd) {
                    if ($rdd['RoleName'] === $moduleName) {
                        $userCount = (int)$rdd['UserCount'];
                        break;
                    }
                }
                $html .= '<tr>';
                $html .= '<td><span class="admin-role-pill">' . htmlspecialchars($rd['RoleName']) . '</span></td>';
                $html .= '<td>' . htmlspecialchars($rd['Description'] ?? '—') . '</td>';
                $html .= '<td>' . $moduleCount . '</td>';
                $html .= '<td>' . $userCount . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table></div>';

            // Module-Permission Matrix
            $html .= '<div class="report-section-title">Module Access & Permission Matrix</div>';
            $html .= '<div class="table-responsive">';
            $html .= '<table class="table table-bordered align-middle matrix-table" style="border-collapse: collapse;">';
            $html .= '<thead><tr style="background: rgba(26,26,110,0.04);">';
            $html .= '<th style="text-align:left;">Role</th>';
            foreach ($allModules as $mod) {
                $html .= '<th>' . htmlspecialchars($mod) . '</th>';
            }
            $html .= '</tr></thead><tbody>';
            foreach ($rolesData as $rd) {
                $roleName = $rd['RoleName'];
                // Apply role filter if set
                if ($roleFilter !== '' && $roleName !== $roleFilter) {
                    continue;
                }
                $html .= '<tr>';
                $html .= '<td><span class="admin-role-pill">' . htmlspecialchars($roleName) . '</span></td>';
                foreach ($allModules as $mod) {
                    $perms = $roleMatrix[$roleName][$mod] ?? null;
                    if ($perms) {
                        $html .= '<td><i class="bi bi-check-circle-fill check-icon"></i><br><span style="font-size:10px; color:#64748b;">' . htmlspecialchars($perms) . '</span></td>';
                    } else {
                        $html .= '<td><i class="bi bi-dash-circle cross-icon"></i></td>';
                    }
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table></div>';
            break;

        // ──────────────────────────────────────────────────────
        // 4. ACCOUNT STATUS REPORT
        // ──────────────────────────────────────────────────────
        case 'account_status_report':
            $where = [];
            $qParams = [];

            buildDateWhere($dateRange, $dateFrom, $dateTo, 'u.CreatedAt', $where, $qParams);

            if ($roleFilter !== '') {
                $where[] = "EXISTS (
                    SELECT 1 FROM user_roles ur
                    INNER JOIN roles r ON r.RoleID = ur.RoleID
                    WHERE ur.UserID = u.UserID AND r.RoleName = ?
                )";
                $qParams[] = $roleFilter;
            }

            $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

            // Summary stats
            $totalAccounts = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $activeAccounts = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE IsActive = 1")->fetchColumn();
            $blockedAccounts = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE IsActive = 0")->fetchColumn();

            // Account listing
            // FIX: MySQL 8+ with ONLY_FULL_GROUP_BY rejects ORDER BY on columns
            // not in the SELECT list when DISTINCT is used. We wrap in a subquery
            // to apply ORDER BY safely outside the DISTINCT context.
            $acctSql = "
                SELECT *
                FROM (
                    SELECT DISTINCT
                        u.UserID,
                        sp.SchoolID,
                        sp.LastName AS _LastName,
                        CONCAT(sp.FirstName, ' ', COALESCE(NULLIF(sp.MiddleName, ''), ''), CASE WHEN sp.MiddleName IS NULL OR sp.MiddleName = '' THEN '' ELSE ' ' END, sp.LastName) AS FullName,
                        COALESCE(
                            (SELECT GROUP_CONCAT(r.RoleName SEPARATOR ', ')
                             FROM user_roles ur2
                             INNER JOIN roles r ON r.RoleID = ur2.RoleID
                             WHERE ur2.UserID = u.UserID),
                            '—'
                        ) AS RoleName,
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

            $dateLabel = dateRangeLabel($dateRange, $dateFrom, $dateTo);
            $meta = $activeAccounts . ' active / ' . $blockedAccounts . ' blocked | ' . $dateLabel;

            $html .= '<div class="report-section-title">Status Distribution</div>';
            $html .= '<div class="report-summary-cards">';
            $html .= '<div class="report-summary-card"><div class="summary-label">Total Accounts</div><div class="summary-value">' . $totalAccounts . '</div></div>';
            $html .= '<div class="report-summary-card"><div class="summary-label">Active Accounts</div><div class="summary-value" style="color:#15803d;">' . $activeAccounts . '</div></div>';
            $html .= '<div class="report-summary-card"><div class="summary-label">Blocked Accounts</div><div class="summary-value" style="color:#b91c1c;">' . $blockedAccounts . '</div></div>';
            $html .= '</div>';

            $html .= '<div class="report-section-title">Account Listing</div>';
            $html .= '<div class="table-responsive">';
            $html .= '<table class="table table-striped table-hover align-middle admin-table">';
            $html .= '<thead><tr><th>School ID</th><th>Full Name</th><th>Role</th><th>Status</th><th>Last Login</th></tr></thead><tbody>';
            if (empty($acctRows)) {
                $html .= '<tr><td colspan="5" class="text-center text-muted">No accounts found for the selected filters.</td></tr>';
            } else {
                foreach ($acctRows as $ar) {
                    $isActive = (int)$ar['IsActive'] === 1;
                    $lastLogin = $ar['LastLogin'] ? date('M d, Y h:i A', strtotime($ar['LastLogin'])) : 'Never';
                    $html .= '<tr>';
                    $html .= '<td>' . htmlspecialchars($ar['SchoolID']) . '</td>';
                    $html .= '<td>' . htmlspecialchars($ar['FullName']) . '</td>';
                    $html .= '<td><span class="admin-role-pill">' . htmlspecialchars($ar['RoleName']) . '</span></td>';
                    $html .= '<td>' . ($isActive ? '<span class="admin-badge admin-badge-success">Active</span>' : '<span class="admin-badge admin-badge-muted">Blocked</span>') . '</td>';
                    $html .= '<td>' . htmlspecialchars($lastLogin) . '</td>';
                    $html .= '</tr>';
                }
            }
            $html .= '</tbody></table></div>';
            break;

        // ──────────────────────────────────────────────────────
        // 5. SYSTEM USAGE REPORT
        // ──────────────────────────────────────────────────────
        case 'system_usage_report':
            $where = [];
            $qParams = [];

            buildDateWhere($dateRange, $dateFrom, $dateTo, 'al.ActionTimestamp', $where, $qParams);

            $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

            $usageSql = "
                SELECT
                    COALESCE(al.ModuleName, 'Uncategorized') AS ModuleName,
                    COUNT(*) AS ActivityCount
                FROM audit_logs al
                {$whereSql}
                GROUP BY al.ModuleName
                ORDER BY ActivityCount DESC
            ";
            $usageStmt = $pdo->prepare($usageSql);
            $usageStmt->execute($qParams);
            $usageRows = $usageStmt->fetchAll(PDO::FETCH_ASSOC);

            $totalActivities = array_sum(array_column($usageRows, 'ActivityCount'));
            $dateLabel = dateRangeLabel($dateRange, $dateFrom, $dateTo);
            $meta = number_format($totalActivities) . ' total activities | ' . $dateLabel;

            $html .= '<div class="report-section-title">Usage Summary</div>';
            $html .= '<div class="report-summary-cards">';
            $html .= '<div class="report-summary-card"><div class="summary-label">Total Activities</div><div class="summary-value">' . number_format($totalActivities) . '</div></div>';
            $html .= '<div class="report-summary-card"><div class="summary-label">Modules Tracked</div><div class="summary-value">' . count($usageRows) . '</div></div>';
            if (!empty($usageRows)) {
                $html .= '<div class="report-summary-card"><div class="summary-label">Most Used</div><div class="summary-value" style="font-size:16px;">' . htmlspecialchars($usageRows[0]['ModuleName'] ?? '—') . '</div></div>';
                $leastUsed = end($usageRows);
                $html .= '<div class="report-summary-card"><div class="summary-label">Least Used</div><div class="summary-value" style="font-size:16px;">' . htmlspecialchars($leastUsed['ModuleName'] ?? '—') . '</div></div>';
            }
            $html .= '</div>';

            $html .= '<div class="report-section-title">Module Usage Counts</div>';
            $html .= '<div class="table-responsive">';
            $html .= '<table class="table table-striped table-hover align-middle admin-table">';
            $html .= '<thead><tr><th>Module</th><th>Activity Count</th><th>% of Total</th></tr></thead><tbody>';
            if (empty($usageRows)) {
                $html .= '<tr><td colspan="3" class="text-center text-muted">No usage data found for the selected date range.</td></tr>';
            } else {
                foreach ($usageRows as $ur) {
                    $pct = $totalActivities > 0 ? round(((int)$ur['ActivityCount'] / $totalActivities) * 100, 1) : 0;
                    $html .= '<tr>';
                    $html .= '<td><span class="admin-module-chip">' . htmlspecialchars($ur['ModuleName']) . '</span></td>';
                    $html .= '<td><strong>' . number_format((int)$ur['ActivityCount']) . '</strong></td>';
                    $html .= '<td>' . $pct . '%</td>';
                    $html .= '</tr>';
                }
            }
            $html .= '</tbody></table></div>';
            break;

        // ──────────────────────────────────────────────────────
        // 6. REPORT HISTORY
        // ──────────────────────────────────────────────────────
        case 'report_history':
            $histSql = "
                SELECT
                    r.ReportType,
                    CONCAT(sp.FirstName, ' ', sp.LastName) AS GeneratedBy,
                    r.GeneratedAt,
                    r.ReportDescription
                FROM reports r
                INNER JOIN users u ON r.GeneratedByUserID = u.UserID
                INNER JOIN school_people sp ON u.SchoolPersonID = sp.SchoolPersonID
                ORDER BY r.GeneratedAt DESC
                LIMIT 200
            ";
            $histRows = $pdo->query($histSql)->fetchAll(PDO::FETCH_ASSOC);

            $meta = count($histRows) . ' reports generated';

            $html .= '<div class="report-section-title">Report Generation History</div>';
            $html .= '<div class="table-responsive">';
            $html .= '<table class="table table-striped table-hover align-middle admin-table">';
            $html .= '<thead><tr><th>Report Type</th><th>Generated By</th><th>Generated Date</th><th>Description</th></tr></thead><tbody>';
            if (empty($histRows)) {
                $html .= '<tr><td colspan="4" class="text-center text-muted">No reports have been generated yet.</td></tr>';
            } else {
                foreach ($histRows as $hr) {
                    $html .= '<tr>';
                    $html .= '<td><span class="admin-role-pill">' . htmlspecialchars($hr['ReportType'] ?? '—') . '</span></td>';
                    $html .= '<td>' . htmlspecialchars($hr['GeneratedBy'] ?? '—') . '</td>';
                    $html .= '<td>' . htmlspecialchars(date('M d, Y h:i A', strtotime($hr['GeneratedAt']))) . '</td>';
                    $html .= '<td>' . htmlspecialchars($hr['ReportDescription'] ?? '—') . '</td>';
                    $html .= '</tr>';
                }
            }
            $html .= '</tbody></table></div>';
            break;

        default:
            echo json_encode(['ok' => false, 'message' => 'Unknown report type.']);
            exit;
    }

    // Log report generation
    logReportGeneration($pdo, $userId, $reportType, $dateRange, $dateFrom, $dateTo, $roleFilter);

    // Audit log for report generation
    auditLog($userId, null, 'reports_generated', 'Reports', null, $title);

    echo json_encode([
        'ok' => true,
        'title' => $title,
        'meta' => $meta,
        'html' => $html,
    ]);

} catch (Throwable $e) {
    echo json_encode([
        'ok' => false,
        'message' => 'Report generation failed: ' . $e->getMessage(),
    ]);
}