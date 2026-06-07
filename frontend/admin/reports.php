<?php
if (session_status() === PHP_SESSION_NONE) {
 session_start();
}

if (!isset($_SESSION['patient_id']) && !isset($_SESSION['UserID'])) {
 header('Location: /NUcare_Health_system/frontend/auth/login.php');
 exit;
}

require_once __DIR__ . '/../../backend/includes/module_guard.php';
require_once __DIR__ . '/../../backend/includes/audit.php';
requireModule('Reports', 'access');

$activeSidebarItem = 'reports';
$active = 'reports';

$pdo = require __DIR__ . '/../../database/config/db_pdo.php';

$actorUserId = isset($_SESSION['UserID']) ? (int)$_SESSION['UserID'] : null;

// Fetch roles for filter dropdown
$allRoles = [];
try {
 $roleStmt = $pdo->query("SELECT RoleName FROM roles ORDER BY RoleName ASC");
 $allRoles = $roleStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {
 $allRoles = [];
}

// Determine if current user is Super Admin
$isSuperAdmin = false;
if (isset($_SESSION['Roles']) && is_array($_SESSION['Roles'])) {
 $isSuperAdmin = in_array('Super Admin', $_SESSION['Roles'], true);
}

// Actor name for report logging
$actorName = 'Unknown';
try {
 if ($actorUserId !== null) {
 $actorStmt = $pdo->prepare("
 SELECT CONCAT(sp.FirstName, ' ', sp.LastName) AS FullName
 FROM users u
 INNER JOIN school_people sp ON sp.SchoolPersonID = u.SchoolPersonID
 WHERE u.UserID = ? LIMIT 1
 ");
 $actorStmt->execute([$actorUserId]);
 $actorRow = $actorStmt->fetch();
 if ($actorRow) {
 $actorName = trim($actorRow['FullName']);
 }
 }
} catch (Throwable $e) {
 // ignore
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>NUCARE | Reports</title>
 <link rel="stylesheet" href="/NUcare_Health_system/assets/css/app.css">
 <link rel="stylesheet" href="/NUcare_Health_system/assets/css/admin_dashboard_overrides.css">
 <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
 <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
 <style>
 .report-type-indicator {
 display: inline-flex;
 align-items: center;
 gap: 6px;
 padding: 6px 14px;
 border-radius: 10px;
 background: var(--admin-red-light);
 color: var(--admin-red);
 font-size: 13px;
 font-weight: 600;
 margin-bottom: 16px;
 }
 .report-type-indicator i {
 font-size: 14px;
 }
 .report-summary-cards {
 display: grid;
 grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
 gap: 16px;
 margin-bottom: 20px;
 }
 .report-summary-card {
 background: var(--admin-surface);
 border: 1px solid var(--admin-border);
 border-radius: var(--radius);
 padding: 18px;
 text-align: center;
 box-shadow: var(--admin-shadow);
 transition: box-shadow 0.2s ease;
 }
 .report-summary-card:hover {
 box-shadow: var(--admin-shadow-md);
 }
 .report-summary-card .summary-label {
 font-size: 11px;
 font-weight: 700;
 letter-spacing: 0.05em;
 text-transform: uppercase;
 color: var(--admin-muted);
 margin-bottom: 6px;
 }
 .report-summary-card .summary-value {
 font-size: 28px;
 font-weight: 800;
 color: var(--admin-red);
 line-height: 1.2;
 }
 .report-section-title {
 font-size: 14px;
 font-weight: 700;
 color: var(--admin-text);
 border-bottom: 2px solid var(--admin-red);
 padding-bottom: 6px;
 margin-bottom: 14px;
 margin-top: 24px;
 }
 .report-section-title:first-of-type {
 margin-top: 0;
 }
 .report-note {
 padding: 12px 16px;
 border-radius: 10px;
 background: #fffbeb;
 border: 1px solid #fde68a;
 color: #92400e;
 font-size: 13px;
 margin-bottom: 16px;
 }
 .report-note i {
 margin-right: 6px;
 }
 #reportResults {
 min-height: 200px;
 }
 .report-loading {
 display: flex;
 align-items: center;
 justify-content: center;
 padding: 60px 20px;
 color: var(--admin-muted);
 font-size: 15px;
 }
 .report-loading .spinner-border {
 margin-right: 12px;
 color: var(--admin-red);
 }
 .report-empty {
 text-align: center;
 padding: 60px 20px;
 color: var(--admin-muted);
 }
 .report-empty i {
 font-size: 48px;
 margin-bottom: 12px;
 display: block;
 opacity: 0.4;
 }
 .report-actions-bar {
 display: flex;
 gap: 12px;
 align-items: center;
 margin-top: 16px;
 padding-top: 16px;
 border-top: 1px solid var(--admin-border);
 }
 .custom-range-fields {
 display: none;
 }
 .custom-range-fields.visible {
 display: contents;
 }
 .matrix-table th {
 font-size: 11px;
 text-transform: uppercase;
 letter-spacing: 0.05em;
 }
 .matrix-table td {
 text-align: center;
 font-size: 13px;
 }
 .matrix-table td:first-child {
 text-align: left;
 font-weight: 600;
 }
 .check-icon {
 color: #15803d;
 font-size: 16px;
 }
 .cross-icon {
 color: #d1d5db;
 font-size: 16px;
 }
 </style>
</head>
<body>
<div class="app-shell">
 <?php
 $sidebarPath = __DIR__ . '/../../backend/includes/sidebar_admin.php';
 if (file_exists($sidebarPath)) {
 require_once $sidebarPath;
 }
 ?>

 <main class="main-content">
 <header class="page-header">
 <div>
 <p class="breadcrumb">Home / Reports</p>
 <h2>Reports</h2>
 <p class="page-description">Generate and export system reports.</p>
 </div>
 <div class="header-actions">
 <a href="/NUcare_Health_system/frontend/auth/logout.php" class="header-button outline">Logout</a>
 </div>
 </header>

 <!-- Filter Bar -->
 <div class="admin-filterbar" style="margin-top: 12px;">
 <div class="admin-filter" style="min-width: 240px;">
 <label>Report Type</label>
 <select id="reportType" name="report_type">
 <option value="">Select Report</option>
 <option value="user_report">User Report</option>
 <option value="audit_log_report">Audit Log Report</option>
 <option value="role_permission_report">Role & Permission Report</option>
 <option value="account_status_report">Account Status Report</option>
 <option value="system_usage_report">System Usage Report</option>
 <option value="report_history">Report History</option>
 </select>
 </div>

 <div class="admin-filter" style="min-width: 180px;">
 <label>Date Range</label>
 <select id="dateRange" name="date_range">
 <option value="">All Time</option>
 <option value="today">Today</option>
 <option value="this_week">This Week</option>
 <option value="this_month">This Month</option>
 <option value="custom">Custom Range</option>
 </select>
 </div>

 <div class="custom-range-fields" id="customRangeFields">
 <div class="admin-filter" style="min-width: 160px;">
 <label>From</label>
 <input type="date" id="dateFrom" name="date_from">
 </div>
 <div class="admin-filter" style="min-width: 160px;">
 <label>To</label>
 <input type="date" id="dateTo" name="date_to">
 </div>
 </div>

 <div class="admin-filter" id="roleFilterWrap" style="min-width: 200px; display: none;">
 <label>Role Filter</label>
 <select id="roleFilter" name="role_filter">
 <option value="">All Roles</option>
 <?php foreach ($allRoles as $r): ?>
 <option value="<?= htmlspecialchars($r) ?>"><?= htmlspecialchars($r) ?></option>
 <?php endforeach; ?>
 </select>
 </div>

 <div style="display: flex; gap: 10px; align-items: center; flex: 0 0 auto; align-self: flex-end; white-space: nowrap;">
 <button type="button" id="btnGenerate" class="btn admin-btn-primary" onclick="generateReport()">
 <i class="bi bi-file-earmark-bar-graph" style="margin-right: 6px;"></i> Generate Report
 </button>
 <button type="button" id="btnPrintPdf" class="btn admin-btn-ghost" style="display: none;" onclick="printPdf()">
 <i class="bi bi-printer" style="margin-right: 6px;"></i> Print PDF
 </button>
 <a href="reports.php" class="btn admin-btn-ghost">Reset</a>
 </div>
 </div>

 <!-- Report Results -->
 <section class="panel-card" style="margin-top: 16px;">
 <div class="panel-card-header d-flex align-items-center justify-content-between">
 <h3 id="reportTitle">Report Results</h3>
 <div class="text-muted" id="reportMeta"></div>
 </div>
 <div class="panel-card-body" id="reportResults">
 <div class="report-empty">
 <i class="bi bi-file-earmark-bar-graph"></i>
 <div style="font-weight: 600; color: var(--admin-text); margin-bottom: 4px;">No Report Generated</div>
 <div>Select a report type and click <strong>Generate Report</strong> to begin.</div>
 </div>
 </div>
 </section>
 </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Role-filter-relevant reports
const roleFilterReports = ['user_report', 'role_permission_report', 'account_status_report'];

// Date-range-relevant reports (reports that use date filtering)
const dateRangeReports = ['user_report', 'audit_log_report', 'account_status_report', 'system_usage_report'];

// Store last generated params for PDF
let lastParams = null;

// Show/hide custom date range
document.getElementById('dateRange').addEventListener('change', function() {
 const customFields = document.getElementById('customRangeFields');
 if (this.value === 'custom') {
 customFields.classList.add('visible');
 } else {
 customFields.classList.remove('visible');
 }
});

// Show/hide role filter based on report type
document.getElementById('reportType').addEventListener('change', function() {
 const roleWrap = document.getElementById('roleFilterWrap');
 if (roleFilterReports.includes(this.value)) {
 roleWrap.style.display = '';
 } else {
 roleWrap.style.display = 'none';
 document.getElementById('roleFilter').value = '';
 }

 // Hide PDF button when report type changes
 document.getElementById('btnPrintPdf').style.display = 'none';
});

function getParams() {
 const reportType = document.getElementById('reportType').value;
 const dateRange = document.getElementById('dateRange').value;
 const dateFrom = document.getElementById('dateFrom').value;
 const dateTo = document.getElementById('dateTo').value;
 const roleFilter = document.getElementById('roleFilter').value;

 if (!reportType) {
 alert('Please select a report type.');
 return null;
 }

 if (dateRange === 'custom') {
 if (!dateFrom || !dateTo) {
 alert('Please select both From and To dates for custom range.');
 return null;
 }
 if (dateFrom > dateTo) {
 alert('The From date cannot be later than the To date.');
 return null;
 }
 }

 return {
 report_type: reportType,
 date_range: dateRange || '',
 date_from: dateFrom || '',
 date_to: dateTo || '',
 role_filter: roleFilter || ''
 };
}

function generateReport() {
 const params = getParams();
 if (!params) return;

 const resultsDiv = document.getElementById('reportResults');
 const titleEl = document.getElementById('reportTitle');
 const metaEl = document.getElementById('reportMeta');

 // Show loading
 resultsDiv.innerHTML = `
 <div class="report-loading">
 <div class="spinner-border spinner-border-sm" role="status"></div>
 Generating report...
 </div>
 `;
 titleEl.textContent = 'Report Results';
 metaEl.textContent = '';
 document.getElementById('btnPrintPdf').style.display = 'none';

 fetch('/NUcare_Health_system/backend/ajax/generate_report.ajax.php', {
 method: 'POST',
 headers: { 'Content-Type': 'application/json' },
 body: JSON.stringify(params)
 })
 .then(res => res.json())
 .then(data => {
 if (data.ok) {
 resultsDiv.innerHTML = data.html;
 titleEl.textContent = data.title || 'Report Results';
 metaEl.textContent = data.meta || '';
 lastParams = params;
 document.getElementById('btnPrintPdf').style.display = '';
 } else {
 resultsDiv.innerHTML = `
 <div class="report-empty">
 <i class="bi bi-exclamation-triangle" style="color: #b91c1c;"></i>
 <div style="font-weight: 600; color: #b91c1c; margin-bottom: 4px;">Error</div>
 <div>${data.message || 'Failed to generate report.'}</div>
 </div>
 `;
 }
 })
 .catch(err => {
 resultsDiv.innerHTML = `
 <div class="report-empty">
 <i class="bi bi-exclamation-triangle" style="color: #b91c1c;"></i>
 <div style="font-weight: 600; color: #b91c1c; margin-bottom: 4px;">Network Error</div>
 <div>${err.message || 'Request failed.'}</div>
 </div>
 `;
 });
}

function printPdf() {
 if (!lastParams) {
 alert('Please generate a report first.');
 return;
 }
 const qs = new URLSearchParams(lastParams).toString();
 window.open('/NUcare_Health_system/backend/print-output/report_output.php?' + qs, '_blank');
}
</script>
</body>
</html>



