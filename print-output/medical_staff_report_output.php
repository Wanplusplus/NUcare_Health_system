<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['UserID'])) {
    http_response_code(401);
    exit('Unauthorized.');
}

$roles = $_SESSION['Roles'] ?? [];
if (array_intersect($roles, ['Admin', 'Super Admin']) !== []) {
    http_response_code(403);
    exit('Clinic reports only.');
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$payload = json_decode((string)($_POST['payload'] ?? ''), true);
if (!is_array($payload)) {
    exit('Missing report payload.');
}

$allowedTypes = [
    'consultation_report',
    'medicine_report',
];

$reportType = (string)($payload['reportType'] ?? '');
if (!in_array($reportType, $allowedTypes, true)) {
    exit('Invalid report type.');
}

$title = (string)($payload['title'] ?? 'Clinic Report');
$dateRange = (string)($payload['dateRangeLabel'] ?? 'Selected Range');
$generatedBy = (string)($payload['generatedBy'] ?? 'Medical Staff');
$columns = is_array($payload['columns'] ?? null) ? $payload['columns'] : [];
$rows = is_array($payload['rows'] ?? null) ? $payload['rows'] : [];
$summary = is_array($payload['summary'] ?? null) ? $payload['summary'] : [];
$reportSections = is_array($payload['reportSections'] ?? null) ? $payload['reportSections'] : [];
$generatedDate = date('F d, Y');
$generatedTime = date('h:i A');

ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 18px 20px 28px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #1f2937; font-size: 8.6px; line-height: 1.18; }
        .report-shell { border: 1px solid #cbd5e1; }
        .header { background: #0b3d91; color: #fff; padding: 10px 12px 8px; display: table; width: 100%; box-sizing: border-box; }
        .logo { display: table-cell; width: 44px; vertical-align: top; }
        .logo-mark { width: 34px; height: 34px; border: 1.5px solid #d4af37; background: #ffffff; color: #0b3d91; text-align: center; line-height: 34px; font-size: 13px; font-weight: 800; }
        .brand { display: table-cell; vertical-align: top; }
        .school { font-size: 12px; font-weight: 800; margin: 0; }
        .clinic { font-size: 9px; font-weight: 700; color: #dbeafe; margin-top: 1px; }
        .title { font-size: 13px; font-weight: 800; margin-top: 4px; color: #ffffff; }
        .meta { display: table-cell; width: 190px; text-align: right; vertical-align: top; line-height: 1.25; font-size: 7.5px; color: #eff6ff; }
        .meta strong { color: #ffffff; }
        .content { padding: 10px 12px 12px; }
        .meta-strip { border: 1px solid #d8e3ea; background: #f8fbff; margin-bottom: 8px; padding: 6px 8px; color: #475569; font-size: 8px; }
        .meta-strip strong { color: #0b3d91; }
        .summary { width: 100%; border-collapse: collapse; margin: 4px 0 5px; }
        .summary td { border: 1px solid #cbd5e1; background: #f8fafc; padding: 5px 7px; }
        .summary .label { color: #64748b; font-size: 7.5px; text-transform: uppercase; letter-spacing: .2px; }
        .summary .value { font-size: 12px; font-weight: 800; color: #0b3d91; margin-top: 1px; }
        .section-title { font-size: 10px; font-weight: 800; color: #0f172a; margin: 0 0 5px; border-left: 4px solid #d4af37; padding-left: 6px; }
        table.data { width: 100%; border-collapse: collapse; table-layout: fixed; page-break-inside: avoid; }
        table.data th { background: linear-gradient(90deg, #2563eb 0%, #0b3d91 78%, #7c3aed 100%); color: #fff; text-align: center; padding: 5px 6px; font-size: 7.5px; text-transform: uppercase; line-height: 1.05; }
        table.data th:first-child { text-align: left; }
        table.data td { border: .8px solid #d7dee8; padding: 4px 6px; vertical-align: middle; word-break: break-word; text-align: center; line-height: 1.12; }
        table.data td:first-child { text-align: left; }
        table.data td:last-child { background: #f3f0ff; color: #6d28d9; font-weight: 800; }
        table.data tr:nth-child(even) td { background: #f8fafc; }
        table.data tr:nth-child(even) td:last-child { background: #f3f0ff; }
        .empty { border: 1px solid #d7dee8; padding: 8px; text-align: center; color: #64748b; }
        .footer { color: #64748b; font-size: 7px; border-top: 1px solid #d7dee8; padding: 5px 12px 0; margin-top: 8px; }
        .footer .left { float: left; }
        .footer .right { float: right; }
    </style>
</head>
<body>
    <div class="report-shell">
        <div class="header">
            <div class="logo">
                <div class="logo-mark">NU</div>
            </div>
            <div class="brand">
                <div class="school">National University</div>
                <div class="clinic">NUcare Clinic</div>
                <div class="title"><?php echo e($title); ?></div>
            </div>
            <div class="meta">
                <div><strong>Date Range:</strong> <?php echo e($dateRange); ?></div>
                <div><strong>Generated By:</strong> <?php echo e($generatedBy); ?></div>
                <div><strong>Date:</strong> <?php echo e($generatedDate); ?></div>
                <div><strong>Time:</strong> <?php echo e($generatedTime); ?></div>
            </div>
        </div>

        <div class="content">
            <div class="meta-strip">
                <strong>Report period:</strong> <?php echo e($dateRange); ?> &nbsp; | &nbsp;
                <strong>Prepared by:</strong> <?php echo e($generatedBy); ?> &nbsp; | &nbsp;
                <strong>Generated:</strong> <?php echo e($generatedDate); ?> <?php echo e($generatedTime); ?>
            </div>

            <?php foreach ($reportSections as $section): ?>
                <?php
                $sectionColumns = is_array($section['columns'] ?? null) ? $section['columns'] : [];
                $sectionRows = is_array($section['rows'] ?? null) ? $section['rows'] : [];
                ?>
                <div class="section-title"><?php echo e((string)($section['title'] ?? 'Report Section')); ?></div>
                <?php if (!$sectionRows): ?>
                    <div class="empty">No records found.</div>
                <?php else: ?>
                    <table class="data">
                        <thead>
                            <tr>
                                <?php foreach ($sectionColumns as $column): ?>
                                    <th><?php echo e((string)($column['label'] ?? 'Column')); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sectionRows as $row): ?>
                                <tr>
                                    <?php foreach ($sectionColumns as $column): ?>
                                        <?php $key = (string)($column['key'] ?? ''); ?>
                                        <td><?php echo e((string)($row[$key] ?? '')); ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div class="footer">
            <span class="left">NUcare Health System - Generated Automatically</span>
            <span class="right">Page {PAGE_NUM} of {PAGE_COUNT}</span>
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
$options->set('chroot', realpath(__DIR__ . '/..'));

$pdf = new Dompdf($options);
$pdf->loadHtml($html);
$pdf->setPaper('A4', 'portrait');
$pdf->render();

$canvas = $pdf->getCanvas();
$canvas->page_text(500, 812, 'Page {PAGE_NUM} of {PAGE_COUNT}', null, 7, [100, 116, 139]);

$filename = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $title)) . '_' . date('Y-m-d') . '.pdf';
$pdf->stream($filename, ['Attachment' => false]);
