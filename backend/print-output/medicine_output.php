<?php
declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../database/config/db.php';

use Dompdf\Dompdf;
use Dompdf\Options;

function clean_input(mixed $value): string
{
 return trim((string)($value ?? ''));
}

function is_valid_year(?string $value): bool
{
 return $value !== null && preg_match('/^\d{4}$/', $value) === 1;
}

function is_valid_month(?string $value): bool
{
 return $value !== null && preg_match('/^(0?[1-9]|1[0-2])$/', $value) === 1;
}

function stock_badge_class(string $status): string
{
 return match ($status) {
 'Available' => 'badge badge-ok',
 'Low Stock' => 'badge badge-warn',
 'Out Of Stock' => 'badge badge-bad',
 'Expired' => 'badge badge-bad',
 default => 'badge',
 };
}

function safe_date(?string $value): string
{
 if (!$value) {
 return '-';
 }

 try {
 return (new DateTimeImmutable($value))->format('M d, Y');
 } catch (Throwable) {
 return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
 }
}

function currency_num(int|float|string|null $value): string
{
 if ($value === null || $value === '') {
 return '0';
 }

 return number_format((float)$value, 0);
}

$statusFilter = clean_input($_GET['status'] ?? '');
$yearFilter = clean_input($_GET['year'] ?? '');
$monthFilter = clean_input($_GET['month'] ?? '');
$printAllStocks = clean_input($_GET['all'] ?? '') === '1';

$where = [];
$params = [];
$types = '';

if ($statusFilter !== '') {
 $allowedStatuses = ['Available', 'Low Stock', 'Out Of Stock', 'Expired'];
 if (in_array($statusFilter, $allowedStatuses, true)) {
 $where[] = 'i.Status = ?';
 $params[] = $statusFilter;
 $types .= 's';
 }
}

if (!$printAllStocks && is_valid_year($yearFilter)) {
 $where[] = 'YEAR(i.ExpiryDate) = ?';
 $params[] = (int)$yearFilter;
 $types .= 'i';
}

if (!$printAllStocks && is_valid_month($monthFilter)) {
 $where[] = 'MONTH(i.ExpiryDate) = ?';
 $params[] = (int)$monthFilter;
 $types .= 'i';
}

$sql = "
 SELECT
 m.MedicineID,
 m.MedicineName,
 m.GenericName,
 m.MedicineType,
 m.Dosage,
 m.Unit,
 m.Description,
 m.CreatedAt AS MedicineCreatedAt,
 i.InventoryID,
 i.BatchNumber,
 i.Quantity,
 i.ExpiryDate,
 i.DateReceived,
 i.ReorderLevel,
 i.Status AS InventoryStatus,
 i.CreatedAt AS InventoryCreatedAt,
 i.UpdatedAt AS InventoryUpdatedAt
 FROM medicines m
 INNER JOIN medicine_inventory i ON i.MedicineID = m.MedicineID
";

if ($where) {
 $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY m.CreatedAt DESC, i.ExpiryDate ASC, i.InventoryID DESC';

$stmt = $conn->prepare($sql);
if (!$stmt) {
 http_response_code(500);
 exit('Failed to prepare export query.');
}

if ($params) {
 $stmt->bind_param($types, ...$params);
}

if (!$stmt->execute()) {
 http_response_code(500);
 exit('Failed to execute export query.');
}

$result = $stmt->get_result();
$rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

$totalMedicines = count(array_unique(array_column($rows, 'MedicineID')));
$totalInventory = count($rows);
$totalAvailable = 0;
$totalLow = 0;
$totalExpired = 0;
$totalOut = 0;
$totalQuantity = 0;

foreach ($rows as $row) {
 $status = (string)($row['InventoryStatus'] ?? '');
 $qty = (int)($row['Quantity'] ?? 0);
 $totalQuantity += $qty;

 if ($status === 'Available') {
 $totalAvailable++;
 } elseif ($status === 'Low Stock') {
 $totalLow++;
 } elseif ($status === 'Expired') {
 $totalExpired++;
 } elseif ($status === 'Out Of Stock') {
 $totalOut++;
 }
}

$reportMonth = (!$printAllStocks && is_valid_month($monthFilter)) ? (int)$monthFilter : null;
$reportYear = (!$printAllStocks && is_valid_year($yearFilter)) ? (int)$yearFilter : null;
$monthName = $reportMonth ? DateTimeImmutable::createFromFormat('!m', (string)$reportMonth)?->format('F') : null;

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$pdf = new Dompdf($options);

$generatedAt = (new DateTimeImmutable())->format('F d, Y h:i A');

$filterLabels = [];
if ($statusFilter !== '') {
 $filterLabels[] = 'Status: ' . htmlspecialchars($statusFilter, ENT_QUOTES, 'UTF-8');
}
if ($printAllStocks) {
 $filterLabels[] = 'Scope: All medicine stocks';
} elseif ($monthName && $reportYear) {
 $filterLabels[] = 'Period: ' . htmlspecialchars($monthName . ' ' . (string)$reportYear, ENT_QUOTES, 'UTF-8');
} elseif ($reportYear) {
 $filterLabels[] = 'Year: ' . htmlspecialchars((string)$reportYear, ENT_QUOTES, 'UTF-8');
}

$filterText = $filterLabels ? implode(' | ', $filterLabels) : 'All medicine inventory records';

ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <style>
 @page {
 margin: 24px 24px 30px 24px;
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
 width: 70%;
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
 width: 30%;
 font-size: 10.5px;
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
 font-size: 9.5px;
 color: #64748b;
 text-transform: uppercase;
 letter-spacing: 0.4px;
 }

 .summary .value {
 font-size: 15px;
 font-weight: 800;
 color: #0b3d91;
 margin-top: 2px;
 }

 .summary .accent {
 color: #b88900;
 }

 .section {
 margin-top: 10px;
 margin-bottom: 8px;
 color: #0b3d91;
 font-size: 13px;
 font-weight: 800;
 border-bottom: 2px solid #d4af37;
 padding-bottom: 4px;
 }

 .filters {
 font-size: 10.5px;
 color: #475569;
 margin-bottom: 10px;
 }

 table.data {
 width: 100%;
 border-collapse: collapse;
 }

 table.data th,
 table.data td {
 border: 1px solid #c7d2e5;
 padding: 6px 7px;
 vertical-align: top;
 }

 table.data th {
 background: #0b3d91;
 color: #ffffff;
 font-size: 10px;
 text-transform: uppercase;
 letter-spacing: 0.3px;
 }

 table.data td {
 background: #ffffff;
 font-size: 10px;
 }

 .medicine-name {
 font-weight: 700;
 color: #0f172a;
 }

 .muted {
 color: #64748b;
 font-size: 9px;
 }

 .right {
 text-align: right;
 }

 .center {
 text-align: center;
 }

 .nowrap {
 white-space: nowrap;
 }

 .badge {
 display: inline-block;
 padding: 4px 8px;
 border-radius: 999px;
 font-weight: 700;
 font-size: 9px;
 border: 1px solid #94a3b8;
 color: #334155;
 background: #f8fafc;
 }

 .badge-ok {
 background: #e8f8ef;
 border-color: #67c58a;
 color: #0f7a34;
 }

 .badge-warn {
 background: #fff7e3;
 border-color: #e4b63f;
 color: #a16207;
 }

 .badge-bad {
 background: #fdecec;
 border-color: #e48a8a;
 color: #b91c1c;
 }

 .footer {
 margin-top: 12px;
 font-size: 10px;
 color: #64748b;
 text-align: right;
 }
 </style>
</head>
<body>
 <div class="page">
 <div class="header">
 <div class="title-row">
 <div class="brand">
 <div class="system-title">NUcare Health System</div>
 <div class="report-title">Medicine & Inventory PDF Report</div>
 </div>
 <div class="meta">
 <div><strong>Generated:</strong> <?php echo htmlspecialchars($generatedAt, ENT_QUOTES, 'UTF-8'); ?></div>
 <div><strong>Scope:</strong> <?php echo htmlspecialchars($filterText, ENT_QUOTES, 'UTF-8'); ?></div>
 </div>
 </div>
 </div>

 <table class="summary">
 <tr>
 <td>
 <div class="label">Medicines</div>
 <div class="value"><?php echo (int)$totalMedicines; ?></div>
 </td>
 <td>
 <div class="label">Inventory Entries</div>
 <div class="value"><?php echo (int)$totalInventory; ?></div>
 </td>
 <td>
 <div class="label">Total Quantity</div>
 <div class="value accent"><?php echo (int)$totalQuantity; ?></div>
 </td>
 <td>
 <div class="label">Available</div>
 <div class="value" style="font-size: 14px;"><?php echo (int)$totalAvailable; ?></div>
 </td>
 <td>
 <div class="label">Low Stock</div>
 <div class="value" style="font-size: 14px; color: #a16207;"><?php echo (int)$totalLow; ?></div>
 </td>
 <td>
 <div class="label">Expired / OOS</div>
 <div class="value" style="font-size: 14px; color: #b91c1c;"><?php echo (int)($totalExpired + $totalOut); ?></div>
 </td>
 </tr>
 </table>

 <div class="section">Medicine Information Table</div>
 <div class="filters">
 This report includes medicine master data and inventory batch details in one print-ready layout.
 </div>

 <table class="data">
 <thead>
 <tr>
 <th style="width: 17%;">Medicine</th>
 <th style="width: 11%;">Generic Name</th>
 <th style="width: 9%;">Type</th>
 <th style="width: 9%;">Dosage</th>
 <th style="width: 8%;">Unit</th>
 <th style="width: 12%;">Batch</th>
 <th style="width: 7%;" class="right">Qty</th>
 <th style="width: 10%;">Expiry</th>
 <th style="width: 8%;" class="right">Reorder</th>
 <th style="width: 9%;">Status</th>
 </tr>
 </thead>
 <tbody>
 <?php if ($rows): ?>
 <?php foreach ($rows as $row): ?>
 <?php
 $status = (string)($row['InventoryStatus'] ?? '');
 $badgeClass = stock_badge_class($status);
 ?>
 <tr>
 <td>
 <div class="medicine-name"><?php echo htmlspecialchars((string)($row['MedicineName'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
 <div class="muted">ID: <?php echo (int)($row['MedicineID'] ?? 0); ?> | <?php echo htmlspecialchars((string)($row['Description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
 </td>
 <td><?php echo htmlspecialchars((string)($row['GenericName'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
 <td><?php echo htmlspecialchars((string)($row['MedicineType'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
 <td><?php echo htmlspecialchars((string)($row['Dosage'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
 <td><?php echo htmlspecialchars((string)($row['Unit'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
 <td>
 <div class="nowrap"><?php echo htmlspecialchars((string)($row['BatchNumber'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></div>
 <div class="muted">Received: <?php echo safe_date((string)($row['DateReceived'] ?? null)); ?></div>
 </td>
 <td class="right"><?php echo (int)($row['Quantity'] ?? 0); ?></td>
 <td class="nowrap"><?php echo safe_date((string)($row['ExpiryDate'] ?? null)); ?></td>
 <td class="right"><?php echo (int)($row['ReorderLevel'] ?? 0); ?></td>
 <td><span class="<?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></span></td>
 </tr>
 <?php endforeach; ?>
 <?php else: ?>
 <tr>
 <td colspan="10" class="center" style="padding: 16px; color: #64748b;">
 No medicine inventory records found for the selected filters.
 </td>
 </tr>
 <?php endif; ?>
 </tbody>
 </table>

 <div class="footer">
 Prepared for printing and PDF download by NUcare Health System
 </div>
 </div>
</body>
</html>
<?php
$html = ob_get_clean();

$pdf->loadHtml($html);
$pdf->setPaper('A4', 'landscape');
$pdf->render();

$pdf->stream('medicine_inventory_report.pdf', ['Attachment' => false]);


