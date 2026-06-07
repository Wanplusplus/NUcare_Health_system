<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
 session_start();
}

// ---
// ACCESS CONTROL: Only Medical Staff, Admin, or Super Admin
// ---
require_once __DIR__ . '/../../../backend/includes/auth_guard.php';
require_once __DIR__ . '/../../../backend/includes/audit.php';
require_once __DIR__ . '/../../../database/config/db_pdo.php';

$pdo = require __DIR__ . '/../../../database/config/db_pdo.php';

$userId = isset($_SESSION['UserID']) ? (int)$_SESSION['UserID'] : 0;
$schoolPersonId = isset($_SESSION['SchoolPersonID']) ? (int)$_SESSION['SchoolPersonID'] : null;

// Fetch user roles
$stmt = $pdo->prepare("
 SELECT r.RoleName
 FROM user_roles ur
 INNER JOIN roles r ON r.RoleID = ur.RoleID
 WHERE ur.UserID = ?
");
$stmt->execute([$userId]);
$userRoles = array_map(static fn($row) => (string)$row['RoleName'], $stmt->fetchAll());
$roleNames = array_map('strtolower', $userRoles);

$allowed = array_intersect($roleNames, ['admin', 'super admin', 'doctor', 'dentist', 'nurse']);
if (empty($allowed)) {
 http_response_code(403);
 echo '<h2 style="color:#8b0000;text-align:center;margin-top:4rem;">Access Denied. Medical Staff, Admin, or Super Admin role required.</h2>';
 exit;
}

$activeSidebarItem = 'simulate_lifecycle_audit';

// ---
// State
// ---
$simulationResult = null;
$executionResult = null;
$errorMessages = [];

// ---
// Fetch DB snapshot (READ-ONLY helper)
// ---
function fetchDbSnapshot(PDO $pdo): array
{
 $sql = "
 SELECT
 sp.SchoolPersonID,
 sp.SchoolID,
 sp.FirstName,
 sp.LastName,
 sp.Email,
 sp.PersonType,
 u.UserID,
 u.IsActive,
 se.EnrollmentID,
 se.EnrollmentStatus,
 ea.AssignmentID,
 ea.EmploymentStatus,
 GROUP_CONCAT(DISTINCT r.RoleName ORDER BY r.RoleName ASC SEPARATOR ', ') AS RoleNames
 FROM school_people sp
 LEFT JOIN users u ON u.SchoolPersonID = sp.SchoolPersonID
 LEFT JOIN student_enrollments se ON se.SchoolPersonID = sp.SchoolPersonID
 LEFT JOIN employee_assignments ea ON ea.SchoolPersonID = sp.SchoolPersonID
 LEFT JOIN user_roles ur ON ur.UserID = u.UserID
 LEFT JOIN roles r ON r.RoleID = ur.RoleID
 GROUP BY sp.SchoolPersonID, sp.SchoolID, sp.FirstName, sp.LastName, sp.Email, sp.PersonType,
 u.UserID, u.IsActive, se.EnrollmentID, se.EnrollmentStatus, ea.AssignmentID, ea.EmploymentStatus
 ORDER BY sp.SchoolID ASC
 ";
 $stmt = $pdo->query($sql);
 $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
 $indexed = [];
 foreach ($rows as $row) {
 $sid = strtoupper(trim((string)$row['SchoolID'] ?? ''));
 if ($sid === '') continue;
 $indexed[$sid] = $row;
 }
 return $indexed;
}

// ---
// Helper: get IP
// ---
function getIp(): ?string
{
 return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? null);
}

// ---
// Helper: resolve role ID from name
// ---
function getRoleId(PDO $pdo, string $roleName): ?int
{
 $stmt = $pdo->prepare("SELECT RoleID FROM roles WHERE LOWER(TRIM(RoleName)) = LOWER(TRIM(?)) LIMIT 1");
 $stmt->execute([$roleName]);
 $val = $stmt->fetchColumn();
 return $val !== false ? (int)$val : null;
}

// ---
// Parse CSV
// ---
function parseCsv(string $csvContent): array
{
 $lines = explode("\n", str_replace("\r\n", "\n", trim($csvContent)));
 if (count($lines) < 2) {
 return ['error' => 'CSV must contain a header row and at least one data row.', 'records' => []];
 }

 $header = str_getcsv($lines[0]);
 $header = array_map('trim', $header);
 $expectedHeaders = ['SchoolID', 'FirstName', 'LastName', 'Email', 'PersonType', 'Status'];
 $headerMap = [];
 foreach ($expectedHeaders as $idx => $colName) {
 $found = false;
 foreach ($header as $hi => $h) {
 if (strcasecmp($h, $colName) === 0) {
 $headerMap[$colName] = $hi;
 $found = true;
 break;
 }
 }
 if (!$found) {
 $headerMap[$colName] = $idx;
 }
 }

 $records = [];
 for ($i = 1; $i < count($lines); $i++) {
 $line = trim($lines[$i]);
 if ($line === '') continue;
 $fields = str_getcsv($line);
 if (count($fields) < 4) continue;
 $schoolId = strtoupper(trim((string)($fields[$headerMap['SchoolID']] ?? '')));
 if ($schoolId === '') continue;
 $records[$schoolId] = [
 'SchoolID' => $schoolId,
 'FirstName' => trim((string)($fields[$headerMap['FirstName']] ?? '')),
 'LastName' => trim((string)($fields[$headerMap['LastName']] ?? '')),
 'Email' => trim((string)($fields[$headerMap['Email']] ?? '')),
 'PersonType' => trim((string)($fields[$headerMap['PersonType']] ?? '')),
 'Status' => trim((string)($fields[$headerMap['Status']] ?? 'Active')),
 ];
 }
 return ['error' => null, 'records' => $records];
}

// ---
// Process simulation / execution on POST
// ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 $mode = $_POST['mode'] ?? 'simulate'; // 'simulate' or 'execute'

 // Gather CSV
 $csvContent = '';
 if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
 $tmp = $_FILES['csv_file']['tmp_name'];
 $content = @file_get_contents($tmp);
 if ($content === false) {
 $errorMessages[] = 'Failed to read uploaded file.';
 } else {
 $csvContent = $content;
 }
 } else {
 $errorMessages[] = 'No CSV data provided. Upload a file or paste CSV content.';
 }

 if (empty($errorMessages) && $csvContent !== '') {
 $parsed = parseCsv($csvContent);
 if ($parsed['error']) {
 $errorMessages[] = $parsed['error'];
 } elseif (empty($parsed['records'])) {
 $errorMessages[] = 'No valid records found in CSV. Check format.';
 } else {
 $csvRecords = $parsed['records'];
 $dbSnapshot = fetchDbSnapshot($pdo);

 // Compute differences
 $newEntries = [];
 $wouldBeBlocked = [];
 $noChange = [];

 // CSV but NOT in DB -> NEW ENTRY
 foreach ($csvRecords as $sid => $csvRec) {
 if (!isset($dbSnapshot[$sid])) {
 $pt = ucfirst(strtolower($csvRec['PersonType']));
 $newEntries[] = [
 'SchoolID' => $sid,
 'Name' => $csvRec['FirstName'] . ' ' . $csvRec['LastName'],
 'Type' => $pt,
 'Status' => 'NEW ENTRY',
 'IsActive' => '-',
 'DBStatus' => 'Not found in database',
 ];
 }
 }

 // DB but NOT in CSV -> WOULD BE BLOCKED
 foreach ($dbSnapshot as $sid => $dbRec) {
 if (!isset($csvRecords[$sid])) {
 $pt = ucfirst(strtolower((string)$dbRec['PersonType'] ?? ''));
 $name = trim(((string)$dbRec['FirstName'] ?? '') . ' ' . ((string)$dbRec['LastName'] ?? ''));
 $wouldBeBlocked[] = [
 'SchoolID' => $sid,
 'Name' => $name !== '' ? $name : '(No name)',
 'Type' => $pt !== '' ? $pt : 'Unknown',
 'Status' => 'WOULD BE BLOCKED',
 'IsActive' => ((int)($dbRec['IsActive'] ?? 0) === 1) ? 'Active' : 'Inactive',
 'DBStatus' => (string)($dbRec['EnrollmentStatus'] ?? $dbRec['EmploymentStatus'] ?? 'Unknown'),
 'SchoolPersonID' => (int)($dbRec['SchoolPersonID'] ?? 0),
 'UserID' => (int)($dbRec['UserID'] ?? 0),
 'EnrollmentID' => (int)($dbRec['EnrollmentID'] ?? 0),
 'AssignmentID' => (int)($dbRec['AssignmentID'] ?? 0),
 ];
 }
 }

 // BOTH -> NO CHANGE
 foreach ($csvRecords as $sid => $csvRec) {
 if (isset($dbSnapshot[$sid])) {
 $dbRec = $dbSnapshot[$sid];
 $pt = ucfirst(strtolower((string)$dbRec['PersonType'] ?? $csvRec['PersonType']));
 $name = trim(((string)$dbRec['FirstName'] ?? $csvRec['FirstName']) . ' ' . ((string)$dbRec['LastName'] ?? $csvRec['LastName']));
 $noChange[] = [
 'SchoolID' => $sid,
 'Name' => $name !== '' ? $name : $csvRec['FirstName'] . ' ' . $csvRec['LastName'],
 'Type' => $pt,
 'Status' => 'NO CHANGE',
 'IsActive' => ((int)($dbRec['IsActive'] ?? 0) === 1) ? 'Active' : 'Inactive',
 'DBStatus' => (string)($dbRec['EnrollmentStatus'] ?? $dbRec['EmploymentStatus'] ?? 'Active'),
 ];
 }
 }

 usort($newEntries, static fn($a, $b) => strcmp($a['SchoolID'], $b['SchoolID']));
 usort($wouldBeBlocked, static fn($a, $b) => strcmp($a['SchoolID'], $b['SchoolID']));
 usort($noChange, static fn($a, $b) => strcmp($a['SchoolID'], $b['SchoolID']));

 $allRecords = array_merge($newEntries, $wouldBeBlocked, $noChange);

 $simulationResult = [
 'totalCsv' => count($csvRecords),
 'totalDb' => count($dbSnapshot),
 'newEntries' => count($newEntries),
 'wouldBeBlocked' => count($wouldBeBlocked),
 'noChange' => count($noChange),
 'records' => $allRecords,
 ];

 // ---
 // EXECUTION MODE: Actually perform the DB changes
 // ---
 if ($mode === 'execute') {
 $pdo->beginTransaction();
 try {
 $blockedCount = 0;
 $insertedCount = 0;
 $blockedDetails = [];
 $insertedDetails = [];

 // -- BLOCK: users in DB but NOT in CSV --
 foreach ($wouldBeBlocked as $rec) {
 $spId = $rec['SchoolPersonID'];
 $uId = $rec['UserID'];
 $pt = $rec['Type'];
 $sid = $rec['SchoolID'];

 if ($uId <= 0) continue;

 // 1) Block login
 $stmtBlock = $pdo->prepare("UPDATE users SET IsActive = 0 WHERE UserID = ? AND IsActive = 1");
 $stmtBlock->execute([$uId]);
 $didBlock = $stmtBlock->rowCount() > 0;

 // 2) Update enrollment/employment status
 $isStudent = strcasecmp($pt, 'Student') === 0;
 $isEmployee = in_array(strtolower($pt), ['faculty', 'staff'], true);

 $didUpdateEnrollment = false;
 $didUpdateEmployment = false;

 if ($isStudent && $rec['EnrollmentID'] > 0) {
 $stmtEnroll = $pdo->prepare("UPDATE student_enrollments SET EnrollmentStatus = 'Not Enrolled' WHERE EnrollmentID = ? AND EnrollmentStatus = 'Enrolled'");
 $stmtEnroll->execute([$rec['EnrollmentID']]);
 $didUpdateEnrollment = $stmtEnroll->rowCount() > 0;
 } elseif ($isStudent && $spId > 0) {
 // No enrollment record exists, create one as 'Not Enrolled'
 $stmtEnroll = $pdo->prepare("INSERT INTO student_enrollments (SchoolPersonID, EnrollmentStatus, AcademicYear, Semester) VALUES (?, 'Not Enrolled', 'CURRENT', 'Term')");
 $stmtEnroll->execute([$spId]);
 $didUpdateEnrollment = true;
 }

 if ($isEmployee && $rec['AssignmentID'] > 0) {
 $stmtEmp = $pdo->prepare("UPDATE employee_assignments SET EmploymentStatus = 'Inactive' WHERE AssignmentID = ? AND EmploymentStatus = 'Employed'");
 $stmtEmp->execute([$rec['AssignmentID']]);
 $didUpdateEmployment = $stmtEmp->rowCount() > 0;
 } elseif ($isEmployee && $spId > 0) {
 $stmtEmp = $pdo->prepare("INSERT INTO employee_assignments (SchoolPersonID, EmploymentStatus) VALUES (?, 'Inactive')");
 $stmtEmp->execute([$spId]);
 $didUpdateEmployment = true;
 }

 if ($didBlock || $didUpdateEnrollment || $didUpdateEmployment) {
 $blockedCount++;
 $blockedDetails[] = $sid . ' (' . $rec['Name'] . ')';
 }

 // Audit log
 $auditAction = $isStudent ? 'Term unenrollment (lifecycle)' : 'Term unemployment (lifecycle)';
 auditLog(
 $userId,
 $schoolPersonId,
 $auditAction,
 'User Management',
 $uId > 0 ? (string)$uId : null,
 'Lifecycle audit: ' . $sid . ' (' . $rec['Name'] . ') blocked - ' . $pt,
 getIp()
 );
 }

 // -- INSERT: CSV entries NOT in DB --
 foreach ($newEntries as $rec) {
 $sid = $rec['SchoolID'];
 $name = $rec['Name'];
 $pt = $rec['Type'];

 // Find the matching CSV record data
 $csvData = $csvRecords[$sid] ?? null;
 if (!$csvData) continue;

 // 1) Insert into school_people
 $personTypeDb = $pt;
 $email = $csvData['Email'];
 $stmtSp = $pdo->prepare("INSERT INTO school_people (SchoolID, FirstName, LastName, Email, PersonType, Sex) VALUES (?, ?, ?, ?, ?, 'Male')");
 $stmtSp->execute([$sid, $csvData['FirstName'], $csvData['LastName'], $email, $personTypeDb]);
 $newSpId = (int)$pdo->lastInsertId();

 // 2) Insert into users (IsActive = 1)
 $defaultHash = password_hash(bin2hex(random_bytes(4)), PASSWORD_DEFAULT);
 $stmtUser = $pdo->prepare("INSERT INTO users (SchoolPersonID, PasswordHash, IsActive) VALUES (?, ?, 1)");
 $stmtUser->execute([$newSpId, $defaultHash]);
 $newUserId = (int)$pdo->lastInsertId();

 // 3) Insert enrollment or assignment
 $isStudent = strcasecmp($pt, 'Student') === 0;
 if ($isStudent) {
 $stmtEnroll = $pdo->prepare("INSERT INTO student_enrollments (SchoolPersonID, EnrollmentStatus, AcademicYear, Semester) VALUES (?, 'Enrolled', 'CURRENT', 'Term')");
 $stmtEnroll->execute([$newSpId]);
 } else {
 $stmtEmp = $pdo->prepare("INSERT INTO employee_assignments (SchoolPersonID, EmploymentStatus) VALUES (?, 'Employed')");
 $stmtEmp->execute([$newSpId]);
 }

 // 4) Assign default role
 $defaultRole = $isStudent ? 'Student' : ($pt === 'Faculty' ? 'Faculty' : 'Staff');
 $roleId = getRoleId($pdo, $defaultRole);
 if ($roleId !== null) {
 $stmtRole = $pdo->prepare("INSERT IGNORE INTO user_roles (UserID, RoleID) VALUES (?, ?)");
 $stmtRole->execute([$newUserId, $roleId]);
 }

 $insertedCount++;
 $insertedDetails[] = $sid . ' (' . $name . ')';

 // Audit log
 auditLog(
 $userId,
 $schoolPersonId,
 'Term enrollment (lifecycle)',
 'User Management',
 (string)$newUserId,
 'Lifecycle audit: ' . $sid . ' (' . $name . ') created - ' . $pt,
 getIp()
 );
 }

 $pdo->commit();

 $executionResult = [
 'status' => 'success',
 'blockedCount' => $blockedCount,
 'insertedCount' => $insertedCount,
 'blockedDetails' => $blockedDetails,
 'insertedDetails' => $insertedDetails,
 'timestamp' => date('Y-m-d H:i:s'),
 ];

 } catch (Throwable $e) {
 $pdo->rollBack();
 $errorMessages[] = 'Execution failed: ' . $e->getMessage();
 $errorMessages[] = 'All changes were rolled back. No data was modified.';
 }
 }
 }
 }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>NUCARE | Simulate Term Audit</title>
 <link rel="stylesheet" href="/NUcare_Health_system/assets/css/app.css">
 <link rel="stylesheet" href="/NUcare_Health_system/assets/css/admin_dashboard_overrides.css">
 <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
 <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
 <style>
 body { background: #f5f7fa; }
 .sim-wrapper { padding: 24px 32px; max-width: 1400px; margin: 0 auto; }
 .sim-card {
 background: #fff;
 border-radius: 14px;
 box-shadow: 0 2px 12px rgba(0,0,0,0.06);
 padding: 24px 28px;
 margin-bottom: 20px;
 }
 .sim-card h3 {
 font-size: 1.15rem;
 font-weight: 700;
 color: #1a1a2e;
 margin: 0 0 16px 0;
 padding-bottom: 12px;
 border-bottom: 1px solid #e9ecef;
 }
 .warning-banner {
 background: #fff3cd;
 border: 1px solid #ffc107;
 border-left: 5px solid #ffc107;
 border-radius: 10px;
 padding: 14px 20px;
 margin-bottom: 20px;
 color: #856404;
 font-weight: 600;
 display: flex;
 align-items: center;
 gap: 10px;
 }
 .warning-banner i { font-size: 1.4rem; }
 .success-banner {
 background: #d1fae5;
 border: 1px solid #16a34a;
 border-left: 5px solid #16a34a;
 border-radius: 10px;
 padding: 14px 20px;
 margin-bottom: 20px;
 color: #065f46;
 font-weight: 600;
 display: flex;
 align-items: center;
 gap: 10px;
 }
 .success-banner i { font-size: 1.4rem; }
 .summary-grid {
 display: grid;
 grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
 gap: 14px;
 margin-bottom: 0;
 }
 .summary-stat {
 background: #f8f9fa;
 border-radius: 10px;
 padding: 16px 18px;
 text-align: center;
 }
 .summary-stat .stat-value { font-size: 2rem; font-weight: 800; }
 .summary-stat .stat-label { font-size: 0.85rem; color: #6b7280; margin-top: 4px; }
 .stat-green .stat-value { color: #16a34a; }
 .stat-red .stat-value { color: #dc2626; }
 .stat-blue .stat-value { color: #2563eb; }
 .stat-gray .stat-value { color: #6b7280; }

 .badge-new {
 background: #d1fae5;
 color: #065f46;
 font-weight: 600;
 padding: 4px 10px;
 border-radius: 20px;
 font-size: 0.78rem;
 white-space: nowrap;
 }
 .badge-blocked {
 background: #fee2e2;
 color: #991b1b;
 font-weight: 600;
 padding: 4px 10px;
 border-radius: 20px;
 font-size: 0.78rem;
 white-space: nowrap;
 }
 .badge-ok {
 background: #e0e7ff;
 color: #3730a3;
 font-weight: 600;
 padding: 4px 10px;
 border-radius: 20px;
 font-size: 0.78rem;
 white-space: nowrap;
 }
 .csv-sample {
 background: #1e293b;
 color: #e2e8f0;
 border-radius: 8px;
 padding: 12px 16px;
 font-family: 'Cascadia Code', 'Fira Code', 'Consolas', monospace;
 font-size: 0.8rem;
 white-space: pre;
 overflow-x: auto;
 margin-top: 8px;
 }
 .table th {
 background: #f1f5f9;
 font-size: 0.8rem;
 text-transform: uppercase;
 letter-spacing: 0.5px;
 color: #475569;
 }
 .table td { vertical-align: middle; font-size: 0.9rem; }
 .btn-run {
 background: #0b3d91;
 border: none;
 padding: 10px 32px;
 font-weight: 600;
 }
 .btn-run:hover { background: #06285e; }
 .btn-execute {
 background: #dc2626;
 border: none;
 padding: 10px 32px;
 font-weight: 600;
 }
 .btn-execute:hover { background: #991b1b; }
 .btn-execute:disabled, .btn-run:disabled { opacity: 0.6; cursor: not-allowed; }
 textarea { font-family: 'Cascadia Code', 'Fira Code', 'Consolas', monospace; font-size: 0.82rem; }
 .exec-summary-grid {
 display: grid;
 grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
 gap: 14px;
 }
 .exec-stat {
 border-radius: 10px;
 padding: 18px 20px;
 text-align: center;
 color: #fff;
 }
 .exec-stat .exec-value { font-size: 2rem; font-weight: 800; }
 .exec-stat .exec-label { font-size: 0.85rem; opacity: 0.9; margin-top: 4px; }
 .exec-blocked { background: #dc2626; }
 .exec-inserted { background: #16a34a; }
 .detail-list { font-size: 0.85rem; max-height: 200px; overflow-y: auto; }
 .confirm-checkbox { transform: scale(1.2); margin-right: 8px; }
 </style>
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
 <div class="sim-wrapper">
 <!-- Header -->
 <header class="page-header" style="margin-bottom: 0;">
 <div>
 <p class="breadcrumb">Home / Settings / Simulate Term Audit</p>
 <h2><i class="bi bi-flask me-2"></i>Term Lifecycle Audit</h2>
 <p class="page-description">Compare a CSV snapshot against the database and execute lifecycle changes (block unenrolled users, create new entries).</p>
 </div>
 <div class="header-actions">
 <a href="/NUcare_Health_system/frontend/auth/logout.php" class="header-button outline">Logout</a>
 </div>
 </header>

 <!-- Execution Success Banner -->
 <?php if ($executionResult !== null && $executionResult['status'] === 'success'): ?>
 <div class="success-banner">
 <i class="bi bi-check-circle-fill"></i>
 <div>
 <strong>check Lifecycle audit executed successfully.</strong><br>
 <span><?= (int)$executionResult['blockedCount'] ?> account(s) blocked &middot; <?= (int)$executionResult['insertedCount'] ?> new record(s) created &middot; <?= date('Y-m-d H:i:s') ?></span>
 </div>
 </div>
 <?php endif; ?>

 <!-- Warning Banner -->
 <div class="warning-banner">
 <i class="bi bi-exclamation-triangle-fill"></i>
 <span>Warning THIS TOOL MODIFIES THE DATABASE when confirmed. Preview results first, then confirm to execute.</span>
 </div>

 <?php if (!empty($errorMessages)): ?>
 <div class="alert alert-danger" role="alert">
 <ul class="mb-0">
 <?php foreach ($errorMessages as $err): ?>
 <li><?= htmlspecialchars($err) ?></li>
 <?php endforeach; ?>
 </ul>
 </div>
 <?php endif; ?>

 <!-- Input Form -->
 <div class="sim-card">
 <h3><i class="bi bi-upload me-2"></i>1. Upload CSV File</h3>
 <form method="post" enctype="multipart/form-data" id="inputForm">
 <div class="row g-4">
 <div class="col-12">
 <label class="form-label fw-semibold">Upload CSV File</label>
 <input class="form-control" type="file" name="csv_file" id="csvFile" accept=".csv" required>
 <div class="form-text">Upload a .csv file with columns: SchoolID, FirstName, LastName, Email, PersonType, Status</div>
 </div>
 </div>

 <!-- Hidden mode field, set by JS -->
 <input type="hidden" name="mode" id="modeField" value="simulate">

 <div class="mt-4 d-flex align-items-center gap-3 flex-wrap">
 <button type="submit" class="btn btn-primary btn-run" id="simulateBtn" onclick="document.getElementById('modeField').value='simulate'">
 <i class="bi bi-eye me-1"></i>Preview Simulation
 </button>
 <button type="submit" class="btn btn-danger btn-execute" id="executeBtn" onclick="return confirmExecution();">
 <i class="bi bi-lightning-fill me-1"></i>Execute Lifecycle Audit
 </button>
 <span class="text-muted" style="font-size:0.85rem;">
 <i class="bi bi-info-circle me-1"></i>Preview first, then execute when ready
 </span>
 </div>
 </form>
 </div>

 <!-- Execution Results -->
 <?php if ($executionResult !== null && $executionResult['status'] === 'success'): ?>
 <div class="sim-card">
 <h3><i class="bi bi-check2-circle me-2 text-success"></i>Execution Complete</h3>
 <div class="exec-summary-grid">
 <div class="exec-stat exec-blocked">
 <div class="exec-value"><?= (int)$executionResult['blockedCount'] ?></div>
 <div class="exec-label"><i class="bi bi-lock-fill me-1"></i>Accounts Blocked</div>
 </div>
 <div class="exec-stat exec-inserted">
 <div class="exec-value"><?= (int)$executionResult['insertedCount'] ?></div>
 <div class="exec-label"><i class="bi bi-plus-circle me-1"></i>New Records Created</div>
 </div>
 </div>
 <?php if (!empty($executionResult['blockedDetails'])): ?>
 <div class="mt-3">
 <strong>Blocked accounts:</strong>
 <div class="detail-list mt-1">
 <?php foreach ($executionResult['blockedDetails'] as $d): ?>
 <div><span class="badge bg-danger me-1">BLOCKED</span> <?= htmlspecialchars($d) ?></div>
 <?php endforeach; ?>
 </div>
 </div>
 <?php endif; ?>
 <?php if (!empty($executionResult['insertedDetails'])): ?>
 <div class="mt-3">
 <strong>New records created:</strong>
 <div class="detail-list mt-1">
 <?php foreach ($executionResult['insertedDetails'] as $d): ?>
 <div><span class="badge bg-success me-1">CREATED</span> <?= htmlspecialchars($d) ?></div>
 <?php endforeach; ?>
 </div>
 </div>
 <?php endif; ?>
 <div class="mt-3 text-muted" style="font-size:0.85rem;">
 <i class="bi bi-clock me-1"></i>Executed at <?= htmlspecialchars($executionResult['timestamp']) ?>
 </div>
 </div>
 <?php endif; ?>

 <!-- Simulation Results (Preview) -->
 <?php if ($simulationResult !== null): ?>
 <div class="sim-card" id="previewSection">
 <h3>
 <i class="bi bi-bar-chart-fill me-2"></i>2. Preview - What Would Happen
 <span class="badge bg-secondary ms-2" style="font-size:0.7rem;"><?= count($simulationResult['records']) ?> records</span>
 </h3>

 <!-- Summary -->
 <div class="summary-grid mb-4">
 <div class="summary-stat stat-green">
 <div class="stat-value"><?= (int)$simulationResult['newEntries'] ?></div>
 <div class="stat-label"><i class="bi bi-plus-circle me-1"></i>New Entries</div>
 <div style="font-size:0.75rem;color:#16a34a;">Not in DB -> will be created</div>
 </div>
 <div class="summary-stat stat-red">
 <div class="stat-value"><?= (int)$simulationResult['wouldBeBlocked'] ?></div>
 <div class="stat-label"><i class="bi bi-lock-fill me-1"></i>To Be Blocked</div>
 <div style="font-size:0.75rem;color:#dc2626;">In DB but missing from CSV</div>
 </div>
 <div class="summary-stat stat-blue">
 <div class="stat-value"><?= (int)$simulationResult['noChange'] ?></div>
 <div class="stat-label"><i class="bi bi-check-circle me-1"></i>No Change</div>
 <div style="font-size:0.75rem;color:#2563eb;">Present in both CSV and DB</div>
 </div>
 <div class="summary-stat stat-gray">
 <div class="stat-value"><?= (int)$simulationResult['totalCsv'] ?></div>
 <div class="stat-label"><i class="bi bi-file-earmark me-1"></i>CSV Records</div>
 <div style="font-size:0.75rem;color:#6b7280;">vs <?= (int)$simulationResult['totalDb'] ?> in database</div>
 </div>
 </div>

 <!-- Detailed table -->
 <?php if (!empty($simulationResult['records'])): ?>
 <div class="table-responsive">
 <table class="table table-hover align-middle">
 <thead>
 <tr>
 <th>School ID</th>
 <th>Name</th>
 <th>Type</th>
 <th>Current DB Status</th>
 <th>Account</th>
 <th>Result</th>
 </tr>
 </thead>
 <tbody>
 <?php foreach ($simulationResult['records'] as $rec): ?>
 <?php
 $badgeClass = ''; $rowClass = '';
 if ($rec['Status'] === 'NEW ENTRY') {
 $badgeClass = 'badge-new'; $rowClass = 'table-success';
 } elseif ($rec['Status'] === 'WOULD BE BLOCKED') {
 $badgeClass = 'badge-blocked'; $rowClass = 'table-danger';
 } else {
 $badgeClass = 'badge-ok';
 }
 ?>
 <tr class="<?= $rowClass ?>">
 <td><code><?= htmlspecialchars($rec['SchoolID']) ?></code></td>
 <td><strong><?= htmlspecialchars($rec['Name']) ?></strong></td>
 <td><span class="badge bg-secondary-subtle text-secondary-emphasis"><?= htmlspecialchars($rec['Type']) ?></span></td>
 <td><?= htmlspecialchars($rec['DBStatus']) ?></td>
 <td>
 <?php if ($rec['IsActive'] === 'Active'): ?>
 <span class="badge bg-success-subtle text-success-emphasis">Active</span>
 <?php elseif ($rec['IsActive'] === 'Inactive'): ?>
 <span class="badge bg-danger-subtle text-danger-emphasis">Inactive</span>
 <?php else: ?>
 <span class="text-muted">-</span>
 <?php endif; ?>
 </td>
 <td><span class="<?= $badgeClass ?>"><?= htmlspecialchars($rec['Status']) ?></span></td>
 </tr>
 <?php endforeach; ?>
 </tbody>
 </table>
 </div>
 <?php endif; ?>

 <!-- Execute hint -->
 <?php if ($executionResult === null && ($simulationResult['wouldBeBlocked'] > 0 || $simulationResult['newEntries'] > 0)): ?>
 <div class="mt-3 p-3 bg-light rounded border">
 <div class="text-muted" style="font-size:0.82rem;">
 <i class="bi bi-info-circle me-1 text-primary"></i>
 <strong>Ready to apply these changes?</strong> Use the <strong>"Execute Lifecycle Audit"</strong> button at the top of the page to actually block accounts and create new records.
 </div>
 </div>
 <?php endif; ?>
 </div>
 <?php endif; ?>
 </div>
 </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
 function confirmExecution() {
 document.getElementById('modeField').value = 'execute';
 return confirm(
 'Warning WARNING: This will modify the database!\n\n' +
 '- Users missing from CSV will be BLOCKED (IsActive=0)\n' +
 '- New entries will be CREATED in school_people + users\n' +
 '- Enrollment/employment status will be UPDATED\n\n' +
 'This action CANNOT be undone automatically.\n\n' +
 'Are you sure you want to proceed?'
 );
 }
</script>
</body>
</html>

