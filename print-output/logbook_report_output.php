<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db_pdo.php';
require_once __DIR__ . '/../includes/logbook_data.php';
require_once __DIR__ . '/../includes/audit.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['UserID'])) {
    http_response_code(401);
    exit('Unauthorized.');
}

$pdo = require __DIR__ . '/../config/db_pdo.php';

$month = (int)($_GET['month'] ?? date('n'));
$year = (int)($_GET['year'] ?? date('Y'));
$userId = (int)$_SESSION['UserID'];

$data = nucareBuildLogbookData($pdo, $year, $month);

$period = $data['period'];
$summary = $data['summary'];
$serviceRows = $data['serviceRows'];
$medicineRows = $data['medicineRows'];
$supplyRows = $data['supplyRows'];
$serviceMatrix = $data['serviceMatrix'];
$dentalMatrix = $data['dentalMatrix'];
$dailyMatrix = $data['dailyMatrix'];
$medicineTotals = $data['medicineTotals'];
$supplyTotals = $data['supplyTotals'];

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function renderCountCell(int $value): string
{
    return '<td class="num">' . number_format($value) . '</td>';
}

$generatedAt = (new DateTimeImmutable())->format('F d, Y h:i A');
$title = 'Daily Logbook';
$filename = sprintf('NUCARE_Daily_Logbook_%04d-%02d.pdf', (int)$period['year'], (int)$period['month']);

ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 22px 22px 28px 22px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #1e293b;
            font-size: 10.5px;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }

        .page {
            width: 100%;
        }

        .header {
            background: #0b3d91;
            color: #ffffff;
            border-radius: 10px;
            padding: 16px 18px;
            margin-bottom: 12px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-left,
        .header-right {
            vertical-align: top;
        }

        .header-left {
            width: 68%;
        }

        .brand {
            font-size: 20px;
            font-weight: 800;
            margin: 0 0 2px 0;
        }

        .subtitle {
            font-size: 12px;
            font-weight: 700;
            color: #dbeafe;
            margin: 0;
        }

        .meta {
            font-size: 9.5px;
            text-align: right;
        }

        .meta strong {
            color: #ffffff;
        }

        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .summary td {
            border: 1px solid #bfd0ea;
            padding: 8px 10px;
            vertical-align: top;
            background: #ffffff;
        }

        .summary .label {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: .4px;
            color: #64748b;
        }

        .summary .value {
            font-size: 16px;
            font-weight: 800;
            color: #0b3d91;
            margin-top: 2px;
        }

        .section {
            margin-top: 10px;
            margin-bottom: 6px;
            color: #0b3d91;
            font-size: 12px;
            font-weight: 800;
            border-bottom: 2px solid #0ea5e9;
            padding-bottom: 4px;
            page-break-after: avoid;
        }

        .note {
            padding: 8px 10px;
            border: 1px solid #dbeafe;
            background: #f8fbff;
            border-radius: 8px;
            font-size: 9px;
            color: #475569;
            margin-bottom: 10px;
        }

        table.data {
            width: 100%;
            max-width: 100%;
            border-collapse: collapse;
            margin: 0 0 10px 0;
            table-layout: fixed;
            page-break-inside: auto;
        }

        table.data th,
        table.data td {
            border: 1px solid #c7d2e5;
            padding: 5px 6px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        table.data th {
            background: #0b3d91;
            color: #ffffff;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        table.data td {
            font-size: 9px;
        }

        table.data tr:nth-child(even) td {
            background: #f8fafc;
        }

        .name-col {
            text-align: left;
            width: auto;
        }

        .qty-col {
            width: 90px;
            text-align: center;
        }

        .num {
            text-align: center;
            font-weight: 700;
            white-space: nowrap;
        }

        .footer {
            margin-top: 12px;
            font-size: 8.5px;
            color: #64748b;
            text-align: right;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }
    </style>
</head>
<body>
<div class="page">

    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-left">
                    <div class="brand">NUcare Health System</div>
                    <p class="subtitle">Daily Logbook Report</p>
                </td>
                <td class="header-right">
                    <div class="meta">
                        <div><strong>Generated:</strong> <?= e($generatedAt) ?></div>
                        <div><strong>Period:</strong> <?= e($period['label']) ?></div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <table class="summary">
        <tr>
            <td>
                <div class="label">Consultations</div>
                <div class="value"><?= number_format((int)$summary['consultations_total']) ?></div>
            </td>
            <td>
                <div class="label">Medicines Dispensed</div>
                <div class="value"><?= number_format((int)$summary['medicine_units_total']) ?></div>
            </td>
            <td>
                <div class="label">Unclassified Entries</div>
                <div class="value"><?= number_format((int)$summary['unclassified_consultations']) ?></div>
            </td>
            <td>
                <div class="label">Output</div>
                <div class="value" style="font-size:13px;">Viewable PDF</div>
            </td>
        </tr>
    </table>

    <div class="section">Services</div>
    <table class="data">
        <thead>
            <tr>
                <th class="name-col">Service / Condition</th>
                <th class="qty-col">Student</th>
                <th class="qty-col">Faculty</th>
                <th class="qty-col">Staff</th>
                <th class="qty-col">ASP</th>
                <th class="qty-col">Total</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($serviceRows as $label): ?>
            <tr>
                <td><?= e($label) ?></td>
                <?= renderCountCell((int)($serviceMatrix[$label]['Student'] ?? 0)) ?>
                <?= renderCountCell((int)($serviceMatrix[$label]['Faculty'] ?? 0)) ?>
                <?= renderCountCell((int)($serviceMatrix[$label]['Staff'] ?? 0)) ?>
                <?= renderCountCell((int)($serviceMatrix[$label]['ASP'] ?? 0)) ?>
                <td class="num"><?= number_format(array_sum($serviceMatrix[$label] ?? [])) ?></td>
            </tr>
        <?php endforeach; ?>

            <tr>
                <td><strong>Dental Consult</strong></td>
                <?= renderCountCell((int)($dentalMatrix['Student'] ?? 0)) ?>
                <?= renderCountCell((int)($dentalMatrix['Faculty'] ?? 0)) ?>
                <?= renderCountCell((int)($dentalMatrix['Staff'] ?? 0)) ?>
                <?= renderCountCell((int)($dentalMatrix['ASP'] ?? 0)) ?>
                <td class="num"><?= number_format(array_sum($dentalMatrix)) ?></td>
            </tr>

            <tr>
                <td><strong>Daily Consult</strong></td>
                <?= renderCountCell((int)($dailyMatrix['Student'] ?? 0)) ?>
                <?= renderCountCell((int)($dailyMatrix['Faculty'] ?? 0)) ?>
                <?= renderCountCell((int)($dailyMatrix['Staff'] ?? 0)) ?>
                <?= renderCountCell((int)($dailyMatrix['ASP'] ?? 0)) ?>
                <td class="num"><?= number_format(array_sum($dailyMatrix)) ?></td>
            </tr>
        </tbody>
    </table>

    <div class="section">Medicine Dispense / Released</div>
    <table class="data">
        <thead>
            <tr>
                <th class="name-col">Medicine</th>
                <th class="qty-col">Qty</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($medicineRows)): ?>
            <?php foreach ($medicineRows as $label): ?>
                <tr>
                    <td><?= e($label) ?></td>
                    <td class="num"><?= number_format((int)($medicineTotals[$label] ?? 0)) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="2" class="num">No medicine dispensed for this period.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="section">Supplies Used</div>
    <table class="data">
        <thead>
            <tr>
                <th class="name-col">Supply</th>
                <th class="qty-col">Qty</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($supplyRows)): ?>
            <?php foreach ($supplyRows as $label): ?>
                <tr>
                    <td><?= e($label) ?></td>
                    <td class="num"><?= number_format((int)($supplyTotals[$label] ?? 0)) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="2" class="num">No supplies used for this period.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="section">Remarks</div>
    <div class="note">
        This report is generated from live clinic records for the selected period and rendered in browser-viewable PDF format.
    </div>

    <div class="footer">
        Prepared for printing by NUcare Health System
    </div>

</div>
</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$pdf = new Dompdf($options);
$pdf->loadHtml($html);
$pdf->setPaper('A4', 'portrait');
$pdf->render();

try {
    $canvas = $pdf->getCanvas();
    $canvas->page_text(515, 810, 'Page {PAGE_NUM} of {PAGE_COUNT}', null, 8, [0, 0, 0]);
} catch (Throwable $e) {
}

try {
    $outputPath = __DIR__ . DIRECTORY_SEPARATOR . $filename;
    @file_put_contents($outputPath, $pdf->output());
} catch (Throwable $e) {
}

auditLog($userId, null, 'reports_generated', 'Reports', null, $title . ' - ' . $period['label']);

$pdf->stream($filename, ['Attachment' => false]);