<?php
declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../database/config/db.php';

use Dompdf\Dompdf;
use Dompdf\Options;

function clean_int(mixed $value): int
{
 return (int) ($value ?? 0);
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

function safe_str(mixed $value, string $fallback = '-'): string
{
 $s = trim((string)($value ?? ''));
 return $s !== '' ? htmlspecialchars($s, ENT_QUOTES, 'UTF-8') : $fallback;
}

function stock_badge_class(string $status): string
{
 return match ($status) {
 'Available' => 'badge badge-ok',
 'Low Stock' => 'badge badge-warn',
 'Near Expiry' => 'badge badge-warn',
 'Out Of Stock', 'Expired' => 'badge badge-bad',
 default => 'badge',
 };
}

/* -- Validate medicine ID --- */
$medicineId = clean_int($_GET['id'] ?? 0);

if ($medicineId <= 0) {
 http_response_code(400);
 exit('Missing or invalid medicine ID. Usage: individual_medicine_output.php?id=1');
}

/* -- Fetch medicine master record --- */
$stmtMed = $conn->prepare(
 'SELECT MedicineID, MedicineName, GenericName, MedicineType,
 Dosage, Unit, Description, CreatedAt
 FROM medicines
 WHERE MedicineID = ?
 LIMIT 1'
);

if (!$stmtMed) {
 http_response_code(500);
 exit('Query preparation failed (medicines).');
}

$stmtMed->bind_param('i', $medicineId);
$stmtMed->execute();
$medicine = $stmtMed->get_result()?->fetch_assoc();
$stmtMed->close();

if (!$medicine) {
 http_response_code(404);
 exit('Medicine record not found.');
}

/* -- Fetch all inventory batches for this medicine --- */
$stmtInv = $conn->prepare(
 'SELECT InventoryID, BatchNumber, Quantity, ExpiryDate,
 DateReceived, ReorderLevel, Status, CreatedAt, UpdatedAt
 FROM medicine_inventory
 WHERE MedicineID = ?
 ORDER BY ExpiryDate ASC, InventoryID DESC'
);

if (!$stmtInv) {
 http_response_code(500);
 exit('Query preparation failed (inventory).');
}

$stmtInv->bind_param('i', $medicineId);
$stmtInv->execute();
$batches = $stmtInv->get_result()?->fetch_all(MYSQLI_ASSOC) ?? [];
$stmtInv->close();

/* -- Aggregate inventory stats --- */
$totalQty = 0;
$totalAvailable = 0;
$totalLow = 0;
$totalExpired = 0;
$totalOut = 0;

foreach ($batches as $b) {
 $totalQty += (int)($b['Quantity'] ?? 0);
 $s = (string)($b['Status'] ?? '');
 if ($s === 'Available') $totalAvailable++;
 elseif ($s === 'Low Stock' || $s === 'Near Expiry') $totalLow++;
 elseif ($s === 'Expired') $totalExpired++;
 elseif ($s === 'Out Of Stock') $totalOut++;
}

/* -- PDF setup --- */
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$pdf = new Dompdf($options);

$generatedAt = (new DateTimeImmutable())->format('F d, Y h:i A');
$medName = safe_str($medicine['MedicineName']);
$medId = (int)$medicine['MedicineID'];

ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
 @page { margin: 24px 24px 30px 24px; }

 body {
 font-family: DejaVu Sans, Arial, sans-serif;
 color: #1e293b;
 font-size: 11px;
 line-height: 1.45;
 }

 /* -- Header --- */
 .header {
 border: 2px solid #d4af37;
 border-radius: 10px;
 padding: 14px 16px;
 background: #f8fbff;
 margin-bottom: 14px;
 }

 .title-row { display: table; width: 100%; }

 .brand {
 display: table-cell;
 vertical-align: top;
 width: 68%;
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
 width: 32%;
 font-size: 10.5px;
 }

 .meta strong { color: #0b3d91; }

 /* -- Job-order style info block --- */
 .info-block {
 width: 100%;
 border-collapse: collapse;
 margin-bottom: 14px;
 border: 1.5px solid #0b3d91;
 border-radius: 6px;
 }

 .info-block td, .info-block th {
 border: 1px solid #bfd0ea;
 padding: 6px 9px;
 font-size: 10.5px;
 vertical-align: top;
 }

 .info-block .field-label {
 background: #0b3d91;
 color: #ffffff;
 font-weight: 700;
 font-size: 9.5px;
 text-transform: uppercase;
 letter-spacing: 0.4px;
 white-space: nowrap;
 width: 1%;
 }

 .info-block .field-value {
 background: #ffffff;
 color: #0f172a;
 font-weight: 600;
 }

 .info-block .field-value.main {
 font-size: 13px;
 font-weight: 800;
 color: #0b3d91;
 }

 .info-block .field-value.muted {
 color: #64748b;
 font-size: 10px;
 font-weight: 400;
 }

 /* -- Stat summary strip --- */
 .summary {
 width: 100%;
 border-collapse: collapse;
 margin-bottom: 14px;
 }

 .summary td {
 border: 1px solid #bfd0ea;
 padding: 8px 10px;
 background: #ffffff;
 vertical-align: top;
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

 .summary .accent { color: #b88900; }
 .summary .warn { color: #a16207; }
 .summary .danger { color: #b91c1c; }

 /* -- Section heading --- */
 .section {
 margin-top: 10px;
 margin-bottom: 8px;
 color: #0b3d91;
 font-size: 13px;
 font-weight: 800;
 border-bottom: 2px solid #d4af37;
 padding-bottom: 4px;
 }

 /* -- Inventory table --- */
 table.data { width: 100%; border-collapse: collapse; }

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

 .right { text-align: right; }
 .center { text-align: center; }
 .nowrap { white-space: nowrap; }
 .muted { color: #64748b; font-size: 9px; }

 /* -- Status badges --- */
 .badge {
 display: inline-block;
 padding: 3px 8px;
 border-radius: 999px;
 font-weight: 700;
 font-size: 9px;
 border: 1px solid #94a3b8;
 color: #334155;
 background: #f8fafc;
 }

 .badge-ok { background: #e8f8ef; border-color: #67c58a; color: #0f7a34; }
 .badge-warn { background: #fff7e3; border-color: #e4b63f; color: #a16207; }
 .badge-bad { background: #fdecec; border-color: #e48a8a; color: #b91c1c; }

 /* -- Description box --- */
 .desc-box {
 background: #f8fafc;
 border: 1px solid #c7d2e5;
 border-radius: 6px;
 padding: 10px 12px;
 font-size: 10.5px;
 color: #334155;
 margin-bottom: 14px;
 min-height: 38px;
 }

 /* -- Footer --- */
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

 <!-- -- HEADER --- -->
 <div class="header">
 <div class="title-row">
 <div class="brand">
 <div class="system-title">NUcare Health System</div>
 <div class="report-title">Individual Medicine Record</div>
 </div>
 <div class="meta">
 <div><strong>Medicine ID:</strong> MED-<?php echo str_pad((string)$medId, 4, '0', STR_PAD_LEFT); ?></div>
 <div><strong>Generated:</strong> <?php echo htmlspecialchars($generatedAt, ENT_QUOTES, 'UTF-8'); ?></div>
 <div><strong>Batches on record:</strong> <?php echo count($batches); ?></div>
 </div>
 </div>
 </div>

 <!-- -- MEDICINE MASTER INFO (job-order table style) --- -->
 <table class="info-block">
 <!-- Row 1: Name + Generic Name -->
 <tr>
 <td class="field-label">Medicine Name</td>
 <td class="field-value main" colspan="3"><?php echo $medName; ?></td>
 </tr>
 <tr>
 <td class="field-label">Generic Name</td>
 <td class="field-value" colspan="3"><?php echo safe_str($medicine['GenericName']); ?></td>
 </tr>

 <!-- Row 2: Type | Dosage | Unit -->
 <tr>
 <td class="field-label">Medicine Type</td>
 <td class="field-value"><?php echo safe_str($medicine['MedicineType']); ?></td>
 <td class="field-label">Dosage</td>
 <td class="field-value"><?php echo safe_str($medicine['Dosage']); ?></td>
 </tr>
 <tr>
 <td class="field-label">Unit</td>
 <td class="field-value"><?php echo safe_str($medicine['Unit']); ?></td>
 <td class="field-label">Date Added</td>
 <td class="field-value"><?php echo safe_date((string)($medicine['CreatedAt'] ?? '')); ?></td>
 </tr>

 <!-- Row 3: Description (full width) -->
 <tr>
 <td class="field-label">Description</td>
 <td class="field-value muted" colspan="3">
 <?php echo safe_str($medicine['Description'], 'No description provided.'); ?>
 </td>
 </tr>
 </table>

 <!-- -- INVENTORY SUMMARY STRIP --- -->
 <table class="summary">
 <tr>
 <td>
 <div class="label">Total Batches</div>
 <div class="value"><?php echo count($batches); ?></div>
 </td>
 <td>
 <div class="label">Total Quantity</div>
 <div class="value accent"><?php echo $totalQty; ?></div>
 </td>
 <td>
 <div class="label">Available</div>
 <div class="value"><?php echo $totalAvailable; ?></div>
 </td>
 <td>
 <div class="label">Low / Near Expiry</div>
 <div class="value warn"><?php echo $totalLow; ?></div>
 </td>
 <td>
 <div class="label">Expired / OOS</div>
 <div class="value danger"><?php echo $totalExpired + $totalOut; ?></div>
 </td>
 </tr>
 </table>

 <!-- -- INVENTORY BATCHES TABLE --- -->
 <div class="section">Inventory Batch Details</div>

 <table class="data">
 <thead>
 <tr>
 <th style="width:5%;">#</th>
 <th style="width:14%;">Batch No.</th>
 <th style="width:8%;" class="right">Qty</th>
 <th style="width:11%;">Date Received</th>
 <th style="width:11%;">Expiry Date</th>
 <th style="width:8%;" class="right">Reorder Lvl</th>
 <th style="width:10%;">Status</th>
 <th style="width:13%;">Created At</th>
 <th style="width:13%;">Last Updated</th>
 <th style="width:7%;">Inv. ID</th>
 </tr>
 </thead>
 <tbody>
 <?php if ($batches): ?>
 <?php foreach ($batches as $i => $b): ?>
 <?php
 $status = (string)($b['Status'] ?? '');
 $badgeClass = stock_badge_class($status);
 ?>
 <tr>
 <td class="center"><?php echo $i + 1; ?></td>
 <td class="nowrap"><?php echo safe_str($b['BatchNumber']); ?></td>
 <td class="right"><?php echo (int)($b['Quantity'] ?? 0); ?></td>
 <td class="nowrap"><?php echo safe_date((string)($b['DateReceived'] ?? null)); ?></td>
 <td class="nowrap"><?php echo safe_date((string)($b['ExpiryDate'] ?? null)); ?></td>
 <td class="right"><?php echo (int)($b['ReorderLevel'] ?? 0); ?></td>
 <td><span class="<?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></span></td>
 <td class="nowrap muted"><?php echo safe_date((string)($b['CreatedAt'] ?? null)); ?></td>
 <td class="nowrap muted"><?php echo safe_date((string)($b['UpdatedAt'] ?? null)); ?></td>
 <td class="center muted"><?php echo (int)($b['InventoryID'] ?? 0); ?></td>
 </tr>
 <?php endforeach; ?>
 <?php else: ?>
 <tr>
 <td colspan="10" class="center" style="padding: 16px; color: #64748b;">
 No inventory batches recorded for this medicine.
 </td>
 </tr>
 <?php endif; ?>
 </tbody>
 </table>

 <!-- -- FOOTER --- -->
 <div class="footer">
 NUcare Health System &mdash; Individual Medicine Record for MED-<?php echo str_pad((string)$medId, 4, '0', STR_PAD_LEFT); ?>
 &mdash; Printed <?php echo htmlspecialchars($generatedAt, ENT_QUOTES, 'UTF-8'); ?>
 </div>

</div>
</body>
</html>
<?php
$html = ob_get_clean();

$pdf->loadHtml($html);
$pdf->setPaper('A4', 'portrait');
$pdf->render();

$safeName = preg_replace('/[^a-z0-9_\-]/i', '_', $medicine['MedicineName'] ?? 'medicine');
$filename = 'medicine_' . $medId . '_' . $safeName . '.pdf';

$pdf->stream($filename, ['Attachment' => false]);

