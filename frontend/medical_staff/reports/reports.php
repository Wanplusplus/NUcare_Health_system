<?php
if (session_status() === PHP_SESSION_NONE) {
 session_start();
}

if (!isset($_SESSION['UserID'])) {
 header('Location: /NUcare_Health_system/frontend/auth/login.php');
 exit;
}

require_once __DIR__ . '/../../../backend/includes/module_guard.php';
requireModule('Reports', 'access');

$roles = $_SESSION['Roles'] ?? [];
if (is_array($roles) && array_intersect($roles, ['Admin', 'Super Admin']) !== []) {
 header('Location: ../../admin/reports.php');
 exit;
}

$activeSidebarItem = 'reports';
?>
<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>NUCARE | Clinic Reports</title>
 <link rel="icon" href="/NUcare_Health_system/assets/image/nucarelogo.png">
 <link rel="stylesheet" href="/NUcare_Health_system/assets/css/app.css">
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
 <style>
 :root {
 --report-primary: #0b3d91;
 --report-primary-2: #1660d7;
 --report-accent: #d4af37;
 --report-surface: #ffffff;
 --report-border: #d8e3ea;
 --report-text: #172033;
 --report-muted: #627084;
 --report-danger: #b91c1c;
 --report-shadow: 0 14px 34px rgba(11, 61, 145, .12);
 }
 .clinic-reports { display: flex; flex-direction: column; gap: 10px; padding-bottom: 28px; }
 .report-hero { background: linear-gradient(135deg, var(--report-primary), var(--report-primary-2)); color: #fff; border-radius: 8px; padding: 18px 20px; box-shadow: var(--report-shadow); }
 .report-hero h2 { margin: 0 0 6px; font-size: 25px; font-weight: 850; }
 .report-hero p { margin: 0; color: rgba(255,255,255,.9); }
 .report-card { background: var(--report-surface); border: 1px solid var(--report-border); border-radius: 8px; box-shadow: var(--report-shadow); padding: 12px; }
 .filter-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; align-items: end; }
 .field { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
 .field label { color: var(--report-muted); font-size: 12px; font-weight: 850; text-transform: uppercase; letter-spacing: .04em; }
 .field select, .field input { border: 1px solid var(--report-border); border-radius: 8px; padding: 10px 12px; font: inherit; color: var(--report-text); background: #fff; min-height: 42px; }
 .custom-range { display: none; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
 .actions { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
 .btn { border: 0; border-radius: 8px; padding: 11px 14px; font: inherit; font-weight: 850; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
 .btn.primary { background: var(--report-primary); color: #fff; }
 .btn.secondary { background: #eff6ff; color: var(--report-primary); }
 .btn:disabled { opacity: .55; cursor: not-allowed; }
 .summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
 .summary-card { border: 1px solid var(--report-border); border-radius: 8px; padding: 14px; background: #fbfdff; min-height: 84px; }
 .summary-card .label { color: var(--report-muted); font-size: 11px; font-weight: 850; text-transform: uppercase; letter-spacing: .04em; }
 .summary-card .value { color: var(--report-primary); font-size: 23px; font-weight: 850; margin-top: 5px; overflow-wrap: anywhere; }
 .table-toolbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 8px; }
 .table-toolbar h3 { margin: 0; color: var(--report-text); font-size: 16px; font-weight: 850; }
 .search-box { display: flex; gap: 8px; align-items: center; }
 .search-box input { border: 1px solid var(--report-border); border-radius: 8px; padding: 10px 12px; min-width: 240px; }
 .table-wrap { overflow-x: auto; border: 1px solid var(--report-border); border-radius: 8px; }
 table.report-table { width: 100%; min-width: 860px; border-collapse: collapse; background: #fff; }
 .report-table th { background: var(--report-primary); color: #fff; text-align: left; padding: 11px 12px; font-size: 12px; cursor: pointer; white-space: nowrap; }
 .report-table td { border-top: 1px solid var(--report-border); padding: 10px 12px; font-size: 13px; color: var(--report-text); vertical-align: top; }
 .report-table tbody tr:nth-child(even) td { background: #fbfdff; }
 .project-section { margin-top: 10px; }
 .project-section:first-child { margin-top: 0; }
 .project-title { color: var(--report-text); font-size: 14px; font-weight: 850; margin: 0 0 6px; }
 .project-table-wrap { overflow-x: auto; border: 1px solid var(--report-border); border-radius: 6px; }
 .project-table { width: 100%; min-width: 640px; border-collapse: collapse; background: #fff; }
 .project-table th { background: linear-gradient(90deg, #2563eb 0%, #1660d7 72%, #7c3aed 100%); color: #fff; padding: 8px 10px; font-size: 12px; text-align: center; white-space: nowrap; }
 .project-table th:first-child, .project-table td:first-child { text-align: left; }
 .project-table td { border-top: 1px solid var(--report-border); padding: 7px 10px; color: var(--report-text); text-align: center; font-size: 13px; }
 .project-table tbody tr:nth-child(even) td { background: #fbfdff; }
 .project-table td:last-child { background: #f3f0ff; color: #6d28d9; font-weight: 850; }
 .project-total { font-weight: 850; color: var(--report-primary); }
 .pagination { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-top: 12px; color: var(--report-muted); font-size: 13px; }
 .page-buttons { display: flex; gap: 8px; }
 .empty-state, .error-state { border: 1px dashed var(--report-border); border-radius: 8px; padding: 22px; text-align: center; color: var(--report-muted); background: #fbfdff; }
 .error-state { border-color: #fecaca; color: var(--report-danger); background: #fff7f7; display: none; }
 .skeleton { position: relative; overflow: hidden; background: #e9eef4; border-radius: 8px; min-height: 18px; }
 .skeleton::after { content: ''; position: absolute; inset: 0; transform: translateX(-100%); background: linear-gradient(90deg, transparent, rgba(255,255,255,.68), transparent); animation: shimmer 1.15s infinite; }
 @keyframes shimmer { 100% { transform: translateX(100%); } }
 @media (max-width: 1060px) { .filter-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
 @media (max-width: 640px) { .filter-grid, .custom-range, .summary-grid { grid-template-columns: 1fr; } .search-box { width: 100%; } .search-box input { min-width: 0; width: 100%; } }
 </style>
 <link rel="stylesheet" href="/NUcare_Health_system/assets/css/medical_staff_premium.css?v=5">
</head>
<body>
<div class="app-shell">
 <?php
 $sidebarPath = __DIR__ . '/../../../backend/includes/sidebar_medical_staff.php';
 if (file_exists($sidebarPath)) {
 require_once $sidebarPath;
 }
 ?>

 <main class="main-content">
 <header class="page-header">
 <div>
 <p class="breadcrumb">Home / Reports</p>
 <h2>Clinic Reports</h2>
 <p class="page-description">Generate clinic operation reports from consultation and medicine dispensing records.</p>
 </div>
 </header>

 <section class="clinic-reports">
 <div class="report-hero">
 <h2>Medical Staff Reports</h2>
 <p>Filter live clinic records, review summaries, search and sort results, then print only the rows currently displayed.</p>
 </div>

 <section class="report-card">
 <div class="filter-grid">
 <div class="field">
 <label for="reportType">Report Type</label>
 <select id="reportType" required>
 <option value="consultation_report">Consultation Report</option>
 <option value="medicine_report">Medicine Dispensing Report</option>
 </select>
 </div>
 <div class="field">
 <label for="dateRange">Date Range</label>
 <select id="dateRange" required>
 <option value="today" selected>Today</option>
 <option value="this_week">This Week</option>
 <option value="this_month">This Month</option>
 <option value="custom">Custom Range</option>
 </select>
 </div>
 <div class="custom-range" id="customRange">
 <div class="field">
 <label for="dateFrom">Start Date</label>
 <input type="date" id="dateFrom">
 </div>
 <div class="field">
 <label for="dateTo">End Date</label>
 <input type="date" id="dateTo">
 </div>
 </div>
 <div class="actions">
 <button type="button" class="btn primary" id="generateBtn"><i class="fa-solid fa-filter"></i>Generate Report</button>
 <button type="button" class="btn secondary" id="printBtn" disabled><i class="fa-solid fa-file-pdf"></i>Generate PDF</button>
 </div>
 </div>
 <div id="reportError" class="error-state" style="margin-top:14px;"></div>
 </section>

 <section class="report-card" id="summaryCard" style="display:none;">
 <div class="summary-grid" id="summaryGrid">
 <div class="summary-card"><div class="skeleton" style="height:14px;width:70%;"></div><div class="skeleton" style="height:28px;width:45%;margin-top:10px;"></div></div>
 <div class="summary-card"><div class="skeleton" style="height:14px;width:70%;"></div><div class="skeleton" style="height:28px;width:45%;margin-top:10px;"></div></div>
 <div class="summary-card"><div class="skeleton" style="height:14px;width:70%;"></div><div class="skeleton" style="height:28px;width:45%;margin-top:10px;"></div></div>
 </div>
 </section>

 <section class="report-card" id="projectedCard" style="display:none;">
 <div class="table-toolbar">
 <div>
 <h3 id="projectedTitle">Medical Consultation</h3>
 <div style="color:var(--report-muted);font-size:12px;" id="projectedSubtitle">Totals for the selected period.</div>
 </div>
 </div>
 <div id="projectedReport">
 <div class="empty-state">Generate a report to project the consultation and medicine tables.</div>
 </div>
 </section>

 <section class="report-card" id="detailCard" style="display:none;">
 <div class="table-toolbar">
 <div>
 <h3 id="tableTitle">Report Results</h3>
 <div style="color:var(--report-muted);font-size:12px;" id="rangeLabel">Generate a report to load records.</div>
 </div>
 <div class="search-box">
 <input type="search" id="searchInput" placeholder="Search displayed report data">
 <button type="button" class="btn secondary" id="searchBtn"><i class="fa-solid fa-magnifying-glass"></i>Search</button>
 </div>
 </div>
 <div class="table-wrap">
 <table class="report-table">
 <thead id="reportHead"></thead>
 <tbody id="reportBody">
 <tr><td><div class="empty-state">No report generated yet.</div></td></tr>
 </tbody>
 </table>
 </div>
 <div class="pagination">
 <div id="pageInfo">0 records</div>
 <div class="page-buttons">
 <button type="button" class="btn secondary" id="prevPage">Previous</button>
 <button type="button" class="btn secondary" id="nextPage">Next</button>
 </div>
 </div>
 </section>
 </section>
 </main>
</div>

<form id="pdfForm" method="post" action="/NUcare_Health_system/backend/print-output/medical_staff_report_output.php" target="_blank" style="display:none;">
 <input type="hidden" name="payload" id="pdfPayload">
</form>

<script src="/NUcare_Health_system/assets/js/app.js?v=3"></script>
<script>
(function () {
 'use strict';

 const endpoint = '/NUcare_Health_system/backend/ajax/medical_staff_reports.ajax.php';
 const state = {
 reportType: 'consultation_report',
 dateRange: 'today',
 dateFrom: '',
 dateTo: '',
 page: 1,
 perPage: 10,
 sortKey: '',
 sortDir: 'desc',
 search: '',
 payload: null
 };

 function el(id) { return document.getElementById(id); }
 function esc(value) {
 return String(value ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[m]);
 }

 function syncFilters(resetPage) {
 state.reportType = el('reportType').value;
 state.dateRange = el('dateRange').value;
 state.dateFrom = el('dateFrom').value;
 state.dateTo = el('dateTo').value;
 state.search = el('searchInput').value.trim();
 if (resetPage) state.page = 1;
 }

 function setError(message) {
 const box = el('reportError');
 box.textContent = message || '';
 box.style.display = message ? 'block' : 'none';
 }

 function validate() {
 if (state.dateRange === 'custom') {
 if (!state.dateFrom || !state.dateTo) return 'Start date and end date are required.';
 if (state.dateTo < state.dateFrom) return 'End date cannot be before start date.';
 }
 return '';
 }

 async function loadReport(resetPage) {
 syncFilters(resetPage);
 const error = validate();
 if (error) {
 setError(error);
 return;
 }
 setError('');
 el('generateBtn').disabled = true;
 el('printBtn').disabled = true;
 el('reportBody').innerHTML = '<tr><td><div class="skeleton" style="height:46px;"></div></td></tr>';

 try {
 const response = await fetch(endpoint, {
 method: 'POST',
 headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
 body: JSON.stringify({
 report_type: state.reportType,
 date_range: state.dateRange,
 date_from: state.dateFrom,
 date_to: state.dateTo,
 page: state.page,
 per_page: state.perPage,
 sort_key: state.sortKey,
 sort_dir: state.sortDir,
 search: state.search
 })
 });
 const payload = await response.json();
 if (!payload.ok) throw new Error(payload.message || 'Report failed to load.');
 state.payload = payload;
 el('summaryCard').style.display = 'none';
 el('projectedCard').style.display = 'block';
 renderReport(payload);
 renderProjected(payload.reportSections || []);
 el('printBtn').disabled = false;
 } catch (err) {
 setError(err.message || 'Unable to load report.');
 } finally {
 el('generateBtn').disabled = false;
 }
 }

 function renderSummary(summary) {
 if (!summary || summary.length === 0) {
 el('summaryGrid').innerHTML = '<div class="empty-state">No summary available.</div>';
 return;
 }
 el('summaryGrid').innerHTML = summary.map(card => '<article class="summary-card"><div class="label">' + esc(card.label) + '</div><div class="value">' + esc(card.value) + '</div></article>').join('');
 }

 function renderReport(payload) {
 el('tableTitle').textContent = payload.title || 'Report Results';
 el('rangeLabel').textContent = 'Date Range: ' + (payload.dateRangeLabel || 'Selected Range');
 renderSummary(payload.summary || []);

 const columns = payload.columns || [];
 el('reportHead').innerHTML = '<tr>' + columns.map(col => '<th data-sort="' + esc(col.key) + '">' + esc(col.label) + (state.sortKey === col.key ? (state.sortDir === 'asc' ? ' ^' : ' v') : '') + '</th>').join('') + '</tr>';

 const rows = payload.rows || [];
 if (!rows.length) {
 el('reportBody').innerHTML = '<tr><td colspan="' + Math.max(1, columns.length) + '"><div class="empty-state">No records found for the selected filters.</div></td></tr>';
 } else {
 el('reportBody').innerHTML = rows.map(row => '<tr>' + columns.map(col => '<td>' + esc(row[col.key]) + '</td>').join('') + '</tr>').join('');
 }

 document.querySelectorAll('[data-sort]').forEach(th => {
 th.addEventListener('click', function () {
 const key = this.dataset.sort;
 if (state.sortKey === key) {
 state.sortDir = state.sortDir === 'asc' ? 'desc' : 'asc';
 } else {
 state.sortKey = key;
 state.sortDir = 'asc';
 }
 loadReport(true);
 });
 });

 const p = payload.pagination || { page: 1, totalPages: 1, totalRows: 0 };
 el('pageInfo').textContent = 'Page ' + p.page + ' of ' + p.totalPages + ' - ' + p.totalRows + ' records';
 el('prevPage').disabled = p.page <= 1;
 el('nextPage').disabled = p.page >= p.totalPages;
 }

 function renderProjected(sections) {
 if (!sections || sections.length === 0) {
 el('projectedReport').innerHTML = '<div class="empty-state">No projected report data for the selected filters.</div>';
 return;
 }

 el('projectedReport').innerHTML = sections.map(section => {
 el('projectedTitle').textContent = section.title || 'Report';
 el('projectedSubtitle').textContent = 'Date Range: ' + ((state.payload && state.payload.dateRangeLabel) || 'Selected Range');
 const columns = section.columns || [];
 const rows = section.rows || [];
 const head = '<tr>' + columns.map(col => '<th>' + esc(col.label) + '</th>').join('') + '</tr>';
 const body = rows.length
 ? rows.map(row => '<tr>' + columns.map(col => '<td class="' + (col.key === 'total' || col.key === 'quantity' ? 'project-total' : '') + '">' + esc(row[col.key]) + '</td>').join('') + '</tr>').join('')
 : '<tr><td colspan="' + Math.max(1, columns.length) + '"><div class="empty-state">No records found.</div></td></tr>';
 return '<div class="project-section"><h4 class="project-title">' + esc(section.title) + '</h4><div class="project-table-wrap"><table class="project-table"><thead>' + head + '</thead><tbody>' + body + '</tbody></table></div></div>';
 }).join('');
 }

 function updateCustomRange() {
 const show = el('dateRange').value === 'custom';
 el('customRange').style.display = show ? 'grid' : 'none';
 }

 function printPdf() {
 if (!state.payload) return;
 const payload = {
 reportType: state.reportType,
 title: state.payload.title,
 dateRangeLabel: state.payload.dateRangeLabel,
 generatedBy: state.payload.generatedBy,
 columns: state.payload.columns,
 rows: state.payload.rows,
 summary: state.payload.summary,
 reportSections: state.payload.reportSections || []
 };
 el('pdfPayload').value = JSON.stringify(payload);
 el('pdfForm').submit();
 }

 el('dateRange').addEventListener('change', updateCustomRange);
 el('generateBtn').addEventListener('click', () => loadReport(true));
 el('searchBtn').addEventListener('click', () => loadReport(true));
 el('searchInput').addEventListener('keydown', event => { if (event.key === 'Enter') loadReport(true); });
 el('prevPage').addEventListener('click', () => { if (state.page > 1) { state.page--; loadReport(false); } });
 el('nextPage').addEventListener('click', () => { state.page++; loadReport(false); });
 el('printBtn').addEventListener('click', printPdf);

 updateCustomRange();
})();
</script>
</body>
</html>




