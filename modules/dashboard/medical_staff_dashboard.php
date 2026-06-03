<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['UserID'])) {
    header('Location: ../../auth/login.php');
    exit;
}


// Redirect admins away from medical staff dashboard
if (isset($_SESSION['Roles']) && is_array($_SESSION['Roles']) && array_intersect($_SESSION['Roles'], ['Admin', 'Super Admin']) !== []) {
    header('Location: admin_dashboard.php');
    exit;
}

$activeSidebarItem = 'dashboard';
$patientName = $_SESSION['patient_name'] ?? 'Medical Staff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE | Medical Staff Dashboard</title>
    <link rel="icon" href="/NUcare_Health_system/assets/image/nucarelogo.png">
    <link rel="stylesheet" href="../../assets/css/app.css">
    <link rel="stylesheet" href="../../assets/css/medical_staff_notifications.css?v=1">
    <link rel="stylesheet" href="../../assets/css/logbook_print.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ═══════════════════════════════════════
           DAILY LOGBOOK — cute & editable ✨
        ═══════════════════════════════════════ */
        @import url('https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap');

        /* ── Decorative dots banner above logbook ── */
        .logbook-deco-banner {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 24px 0;
        }
        .logbook-deco-banner span {
            display: inline-block;
            width: 10px; height: 10px;
            border-radius: 50%;
            animation: bounceDot 1.4s ease-in-out infinite;
        }
        .logbook-deco-banner span:nth-child(1){ background:#60a5fa; animation-delay:0s; }
        .logbook-deco-banner span:nth-child(2){ background:#f472b6; animation-delay:.15s; }
        .logbook-deco-banner span:nth-child(3){ background:#34d399; animation-delay:.3s; }
        .logbook-deco-banner span:nth-child(4){ background:#fbbf24; animation-delay:.45s; }
        .logbook-deco-banner span:nth-child(5){ background:#a78bfa; animation-delay:.6s; }
        @keyframes bounceDot {
            0%,100%{ transform: translateY(0); }
            40%{ transform: translateY(-6px); }
        }

        .logbook-wrapper {
            padding: 6px 24px 32px;
        }

        .logbook-card {
            background: #fff;
            border: 2px solid #dbeafe;
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 4px 28px rgba(37,99,235,.10), 0 1px 4px rgba(0,0,0,.04);
            position: relative;
        }

        /* cute pastel corner doodles */
        .logbook-card::before {
            content: '🌸';
            position: absolute;
            top: 14px; right: 16px;
            font-size: 1.4rem;
            opacity: .45;
            pointer-events: none;
        }

        /* ── Header ── */
        .logbook-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            padding: 18px 24px;
            background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        /* shimmer stripe */
        .logbook-header::after {
            content: '';
            position: absolute;
            inset: 0;
            background: repeating-linear-gradient(
                45deg,
                rgba(255,255,255,.04) 0px,
                rgba(255,255,255,.04) 12px,
                transparent 12px,
                transparent 24px
            );
            pointer-events: none;
        }

        .logbook-header-left {
            display: flex;
            align-items: center;
            gap: 14px;
            position: relative; z-index:1;
        }

        .logbook-icon {
            width: 46px; height: 46px;
            background: rgba(255,255,255,.22);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
            border: 1.5px solid rgba(255,255,255,.3);
            box-shadow: 0 2px 8px rgba(0,0,0,.12);
        }

        .logbook-title {
            font-size: 1.08rem;
            font-weight: 900;
            letter-spacing: .01em;
            margin: 0 0 4px;
            font-family: 'Nunito', sans-serif;
        }

        .logbook-meta {
            font-size: .74rem;
            opacity: .9;
            margin: 0;
            font-family: 'Nunito', sans-serif;
        }

        .logbook-header-right { position: relative; z-index:1; }

        .logbook-date-badge {
            display: flex;
            align-items: center;
            gap: 7px;
            background: rgba(255,255,255,.18);
            border: 1px solid rgba(255,255,255,.28);
            border-radius: 10px;
            padding: 8px 14px;
            font-size: .73rem;
            white-space: nowrap;
            font-family: 'Nunito', sans-serif;
        }

        /* ── Save bar ── */
        .logbook-savebar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 24px;
            background: linear-gradient(90deg, #eff6ff 0%, #fdf4ff 100%);
            border-bottom: 1.5px dashed #c7d2fe;
            flex-wrap: wrap;
        }

        .logbook-savebar-hint {
            font-size: .75rem;
            color: #7c3aed;
            font-weight: 700;
            font-family: 'Nunito', sans-serif;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .logbook-savebar-hint .sparkle { animation: spin 3s linear infinite; display:inline-block; }
        @keyframes spin { to { transform: rotate(360deg); } }

        .lb-save-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 18px;
            border-radius: 999px;
            border: none;
            background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);
            color: #fff;
            font-size: .78rem;
            font-weight: 800;
            font-family: 'Nunito', sans-serif;
            cursor: pointer;
            transition: transform .15s, box-shadow .15s, opacity .15s;
            box-shadow: 0 4px 14px rgba(124,58,237,.3);
        }

        .lb-save-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(124,58,237,.4); }
        .lb-save-btn:active { transform: scale(.97); }
        .lb-save-btn.saved { background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 4px 14px rgba(16,185,129,.3); }

        /* ── Export PDF button ── */
        .lb-export-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 20px;
            border-radius: 999px;
            border: none;
            background: linear-gradient(135deg, #0ea5e9 0%, #2563eb 100%);
            color: #fff;
            font-size: .78rem;
            font-weight: 800;
            font-family: 'Nunito', sans-serif;
            cursor: pointer;
            transition: transform .15s, box-shadow .15s;
            box-shadow: 0 4px 14px rgba(14,165,233,.35);
            white-space: nowrap;
        }
        .lb-export-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(14,165,233,.45); }
        .lb-export-btn:active { transform: scale(.97); }
        .lb-export-btn.exporting {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            pointer-events: none;
        }
        .lb-export-btn .lb-export-spinner {
            display: none;
            width: 13px; height: 13px;
            border: 2px solid rgba(255,255,255,.4);
            border-top-color: #fff;
            border-radius: 50%;
            animation: lbSpin .6s linear infinite;
        }
        .lb-export-btn.exporting .lb-export-spinner { display: inline-block; }
        .lb-export-btn.exporting .lb-export-icon { display: none; }
        @keyframes lbSpin { to { transform: rotate(360deg); } }

        /* ── Body ── */
        .logbook-body {
            padding: 20px 24px 28px;
            font-family: 'Nunito', sans-serif;
        }

        .logbook-section-label {
            font-size: .77rem;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #7c3aed;
            padding: 12px 0 6px;
            border-bottom: 2.5px solid;
            border-image: linear-gradient(90deg, #2563eb, #7c3aed) 1;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logbook-section-label .sec-emoji { font-size: .95rem; }

        .logbook-row-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 10px;
            font-size: .83rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 4px;
            background: #f8faff;
            border: 1.5px solid #e0e7ff;
            transition: border-color .2s, background .2s;
        }

        .logbook-row-item:hover { border-color: #a5b4fc; background: #f0f4ff; }

        .logbook-row-item--heading {
            background: linear-gradient(90deg, #eff6ff 0%, #fdf4ff 100%);
            border-color: #c7d2fe;
            color: #3730a3;
            font-weight: 900;
        }

        .logbook-row-num {
            font-weight: 900;
            color: #7c3aed;
            min-width: 22px;
            font-size: .78rem;
        }

        .logbook-row-name { flex: 1; }

        /* ── Editable cells ── */
        .lb-edit {
            background: transparent;
            border: none;
            outline: none;
            width: 100%;
            color: inherit;
            font: inherit;
            text-align: center;
            padding: 2px 4px;
            border-radius: 6px;
            transition: background .15s, box-shadow .15s;
            -moz-appearance: textfield;
        }
        .lb-edit::-webkit-outer-spin-button,
        .lb-edit::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

        .lb-edit:focus {
            background: #fffbeb;
            box-shadow: 0 0 0 2px #fbbf24;
            color: #92400e;
        }

        .lb-edit-text {
            background: transparent;
            border: none;
            outline: none;
            width: 100%;
            color: inherit;
            font: inherit;
            padding: 2px 4px;
            border-radius: 6px;
            transition: background .15s, box-shadow .15s;
        }
        .lb-edit-text:focus {
            background: #fdf4ff;
            box-shadow: 0 0 0 2px #a78bfa;
        }

        /* ── Tables ── */
        .logbook-table-wrap {
            overflow-x: auto;
            margin: 6px 0 14px 24px;
            border-radius: 12px;
            border: 1.5px solid #e0e7ff;
            box-shadow: 0 2px 8px rgba(99,102,241,.06);
        }

        .logbook-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .78rem;
            font-family: 'Nunito', sans-serif;
        }

        .logbook-table thead tr {
            background: linear-gradient(90deg, #2563eb 0%, #7c3aed 100%);
        }

        .logbook-table thead th {
            color: #fff;
            font-weight: 800;
            padding: 9px 12px;
            text-align: center;
            font-size: .72rem;
            letter-spacing: .04em;
            white-space: nowrap;
        }

        .logbook-table thead th.lb-col-name {
            text-align: left;
            min-width: 180px;
        }

        .logbook-table thead th.lb-col-total {
            background: rgba(255,255,255,.18);
        }

        .logbook-table tbody tr {
            border-bottom: 1px solid #e0e7ff;
            transition: background .15s;
        }

        .logbook-table tbody tr:last-child { border-bottom: none; }

        .logbook-table tbody tr:hover { background: #f5f3ff; }

        .logbook-table tbody td {
            padding: 7px 10px;
            color: #111827;
            text-align: center;
            font-size: .78rem;
        }

        .logbook-table tbody td:first-child {
            text-align: left;
            font-weight: 600;
        }

        .logbook-table tbody tr.lb-subrow td {
            color: #6b7280;
            font-style: italic;
            font-size: .74rem;
        }

        .lb-total {
            font-weight: 900 !important;
            color: #7c3aed !important;
            background: #f5f3ff;
            font-size: .8rem !important;
        }

        /* ── Two-column ── */
        .logbook-two-col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin: 6px 0 0 24px;
        }

        .logbook-two-col > div {
            border-radius: 12px;
            overflow: hidden;
            border: 1.5px solid #e0e7ff;
        }

        .logbook-table--compact thead th { font-size: .7rem; padding: 7px 10px; }
        .logbook-table--compact tbody td { font-size: .75rem; padding: 6px 10px; }

        /* ── Toast notification ── */
        .lb-toast {
            position: fixed;
            bottom: 28px;
            left: 50%;
            transform: translateX(-50%) translateY(80px);
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            padding: 12px 24px;
            border-radius: 999px;
            font-size: .82rem;
            font-weight: 800;
            font-family: 'Nunito', sans-serif;
            box-shadow: 0 8px 24px rgba(16,185,129,.35);
            z-index: 9999;
            transition: transform .35s cubic-bezier(.34,1.56,.64,1), opacity .35s;
            opacity: 0;
            pointer-events: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .lb-toast.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }

        @media (max-width: 640px) {
            .logbook-two-col { grid-template-columns: 1fr; }
            .logbook-header { flex-direction: column; align-items: flex-start; }
            .logbook-wrapper { padding: 0 12px 24px; }
            .logbook-table-wrap { margin-left: 8px; }
        }

        /* ── Month / Year period pickers ── */
        .lb-period-label {
            font-size: .72rem;
            font-weight: 800;
            font-family: 'Nunito', sans-serif;
            color: rgba(255,255,255,.85);
            display: flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }

        .lb-period-select {
            font-family: 'Nunito', sans-serif;
            font-size: .73rem;
            font-weight: 800;
            color: #3730a3;
            background: rgba(255,255,255,.92);
            border: 1.5px solid rgba(255,255,255,.5);
            border-radius: 8px;
            padding: 4px 10px 4px 8px;
            cursor: pointer;
            outline: none;
            transition: background .15s, box-shadow .15s;
            -webkit-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%237c3aed'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 7px center;
            padding-right: 22px;
        }
        .lb-period-select:hover { background-color: #fff; box-shadow: 0 0 0 2px rgba(255,255,255,.4); }
        .lb-period-select:focus { box-shadow: 0 0 0 2.5px #fbbf24; }

        .lb-period-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(255,255,255,.18);
            border: 1px solid rgba(255,255,255,.3);
            border-radius: 999px;
            padding: 3px 10px;
            font-size: .68rem;
            font-weight: 800;
            font-family: 'Nunito', sans-serif;
            color: #fff;
            white-space: nowrap;
            transition: background .2s;
        }
        .lb-period-badge.loaded { background: rgba(16,185,129,.35); border-color: rgba(16,185,129,.5); }
        .lb-period-badge.new    { background: rgba(251,191,36,.25);  border-color: rgba(251,191,36,.5); }
    </style>

    <!-- jsPDF + html2canvas for PDF export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>
<body>
<div class="app-shell">

    <?php
    $sidebarPath = __DIR__ . '/../../includes/sidebar_medical_staff.php';
    if (file_exists($sidebarPath)) {
        require_once $sidebarPath;
    }
    ?>

    <main class="main-content">
        <header class="page-header">
            <div>
                <p class="breadcrumb">Home / Dashboard</p>
                <h2>Medical Staff Dashboard</h2>
                <p class="page-description">
                    Welcome back, <?php echo htmlspecialchars($patientName); ?>.
                    Access your clinical modules from the sidebar.
                </p>
            </div>
            <div class="header-actions">
                <div class="notif-bell">
                    <button id="notifBellBtn" type="button" aria-haspopup="true" aria-expanded="false" aria-label="Notifications">
                        <i class="fa-solid fa-bell"></i>
                        <span>Notifications</span>
                        <span class="notif-badge" id="notifBadge" style="display:none;">0</span>
                        <span class="sr-only">Notifications</span>

                    </button>


                    <div id="notifDropdown" class="notif-dropdown" role="menu" aria-label="Notification list">


                        <div class="notif-header">
                            <h4>Real-time Alerts</h4>
                            <div class="notif-lastup" id="notifLastUpdated"></div>
                        </div>

                        <div class="notif-body">
                            <div class="notif-loading" id="notifLoading">Loading…</div>
                            <div class="notif-empty" id="notifEmpty">No alerts right now.</div>
                            <div id="notifList"></div>
                        </div>
                    </div>
                </div>
            </div>

        </header>

        <div class="cards-grid">
            <article class="status-card">
                <h3>Staff Role</h3>
                <p class="status-value" style="font-size: 26px;">Medical</p>
            </article>

            <article class="status-card">
                <h3>Consultation</h3>
                <p class="status-value" style="font-size: 26px;">Available</p>
            </article>

            <article class="status-card">
                <h3>Records</h3>
                <p class="status-value" style="font-size: 26px;">Available</p>
            </article>

            <article class="status-card">
                <h3>Schedule</h3>
                <p class="status-value" style="font-size: 26px;">Open</p>
            </article>
        </div>

        <!-- ══ DAILY LOGBOOK ══ -->
        <!-- decorative bouncing dots -->
        <div class="logbook-deco-banner" aria-hidden="true">
            <span></span><span></span><span></span><span></span><span></span>
        </div>

        <div class="logbook-wrapper">
            <div class="logbook-card" id="logbookCard">

                <!-- Header -->
                <div class="logbook-header">
                    <div class="logbook-header-left">
                        <div class="logbook-icon"><i class="fa-solid fa-book-medical"></i></div>
                        <div>
                            <h3 class="logbook-title">✨ Daily Logbook</h3>
                            <p class="logbook-meta">
                                Month: <strong id="lbMonth"><?php echo date('F'); ?></strong>
                                &nbsp;·&nbsp; Term: <strong>2nd</strong>
                                &nbsp;·&nbsp; Dept: <strong>NU Bacolod </strong>
                            </p>
                            <!-- Month / Year pickers -->
                            <div class="lb-period-pickers" style="display:flex;align-items:center;gap:8px;margin-top:8px;flex-wrap:wrap;">
                                <label class="lb-period-label" for="lbPickerMonth">
                                    <i class="fa-regular fa-calendar" style="font-size:.75rem;opacity:.8;"></i>
                                    Record:
                                </label>
                                <select id="lbPickerMonth" class="lb-period-select">
                                    <?php
                                    $months = ['January','February','March','April','May','June',
                                               'July','August','September','October','November','December'];
                                    $curMonth = (int)date('n');
                                    foreach ($months as $i => $m) {
                                        $sel = ($i + 1 === $curMonth) ? ' selected' : '';
                                        echo "<option value=\"".($i+1)."\"$sel>$m</option>\n";
                                    }
                                    ?>
                                </select>
                                <select id="lbPickerYear" class="lb-period-select">
                                    <?php
                                    $curYear = (int)date('Y');
                                    for ($y = $curYear - 2; $y <= $curYear + 1; $y++) {
                                        $sel = ($y === $curYear) ? ' selected' : '';
                                        echo "<option value=\"$y\"$sel>$y</option>\n";
                                    }
                                    ?>
                                </select>
                                <span id="lbPeriodBadge" class="lb-period-badge"></span>
                            </div>
                        </div>
                    </div>
                    <div class="logbook-header-right">
                        <div class="logbook-date-badge">
                            <i class="fa-regular fa-calendar-check"></i>
                            <span>Date Accomplished: <strong id="lbDateStamp"><?php echo date('n/j/Y g:i:s A'); ?></strong></span>
                        </div>
                    </div>
                </div>

                <!-- Save bar -->
                <div class="logbook-savebar">
                    <div class="logbook-savebar-hint">
                        <span class="sparkle">✦</span>
                        Click any number to edit · Changes are saved locally until submitted
                    </div>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <button class="lb-export-btn" id="lbExportBtn" onclick="exportLogbookPDF()" type="button">
                                <span class="lb-export-spinner"></span>
                                <i class="fa-solid fa-file-pdf lb-export-icon"></i>
                                <span class="lb-export-label">Export PDF</span>
                            </button>
                            <button class="lb-save-btn" id="lbSaveBtn" onclick="saveLogbook()">
                                <i class="fa-solid fa-floppy-disk"></i> Save Logbook
                            </button>
                        </div>
                </div>
                    
                <!-- Body -->
                <div class="logbook-body">

                    <!-- ── A. SERVICES ── -->
                    <div class="logbook-section-label">
                        <span class="sec-emoji">🩺</span> A. SERVICES
                    </div>

                    <!-- 1. Student Orientation -->
                    <div class="logbook-row-item">
                        <span class="logbook-row-num">1.</span>
                        <span class="logbook-row-name">Student Orientation</span>
                    </div>

                    <!-- 2. Seminars/Trainings -->
                    <div class="logbook-row-item">
                        <span class="logbook-row-num">2.</span>
                        <span class="logbook-row-name">Seminars / Trainings</span>
                    </div>

                    <!-- 3. Medical Consultation table -->
                    <div class="logbook-row-item logbook-row-item--heading">
                        <span class="logbook-row-num">3.</span>
                        <span class="logbook-row-name">Medical Consultation</span>
                    </div>
                    <div class="logbook-table-wrap">
                        <table class="logbook-table" data-autototal="true">
                            <thead>
                                <tr>
                                    <th class="lb-col-name"></th>
                                    <th>SHS</th>
                                    <th>College</th>
                                    <th>Faculty</th>
                                    <th>ASP</th>
                                    <th class="lb-col-total">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="lb-subrow">
                                    <td>PE</td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td class="lb-total">0</td>
                                </tr>
                                <tr>
                                    <td>Systemic Viral Illness</td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="1"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td class="lb-total">1</td>
                                </tr>
                                <tr>
                                    <td>Cardiovascular Problems</td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td class="lb-total">0</td>
                                </tr>
                                <tr>
                                    <td>Respiratory Problems</td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td class="lb-total">0</td>
                                </tr>
                                <tr>
                                    <td>GastroIntestinal Problems</td>
                                    <td><input class="lb-edit" type="number" min="0" value="3"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="6"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td class="lb-total">9</td>
                                </tr>
                                <tr>
                                    <td>Gynecologic Problems</td>
                                    <td><input class="lb-edit" type="number" min="0" value="5"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="2"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td class="lb-total">7</td>
                                </tr>
                                <tr>
                                    <td>Allergy/Hypersensitivity Problems</td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td class="lb-total">0</td>
                                </tr>
                                <tr>
                                    <td>Infectious Problems</td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td class="lb-total">0</td>
                                </tr>
                                <tr>
                                    <td>Minor Accidents / Trauma</td>
                                    <td><input class="lb-edit" type="number" min="0" value="1"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="1"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td class="lb-total">2</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- 4. Dental Consult -->
                    <div class="logbook-row-item logbook-row-item--heading">
                        <span class="logbook-row-num">4.</span>
                        <span class="logbook-row-name">🦷 Dental Consult</span>
                    </div>
                    <div class="logbook-table-wrap">
                        <table class="logbook-table" data-autototal="true">
                            <thead>
                                <tr>
                                    <th class="lb-col-name"></th>
                                    <th>SHS</th><th>College</th><th>Faculty</th><th>ASP</th>
                                    <th class="lb-col-total">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Oral Prophylaxis</td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td class="lb-total">0</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- 5. Online Consult -->
                    <div class="logbook-row-item logbook-row-item--heading">
                        <span class="logbook-row-num">5.</span>
                        <span class="logbook-row-name">💻 Online Consult</span>
                    </div>
                    <div class="logbook-table-wrap">
                        <table class="logbook-table" data-autototal="true">
                            <thead>
                                <tr>
                                    <th class="lb-col-name"></th>
                                    <th>SHS</th><th>College</th><th>Faculty</th><th>ASP</th>
                                    <th class="lb-col-total">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>—</td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td><input class="lb-edit" type="number" min="0" value="0"></td>
                                    <td class="lb-total">0</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- 6. Medicine Dispense + Supplies -->
                    <div class="logbook-row-item logbook-row-item--heading">
                        <span class="logbook-row-num">6.</span>
                        <span class="logbook-row-name">💊 Medicine Dispense / Released</span>
                    </div>

                    <div class="logbook-two-col">
                        <!-- Medicines -->
                        <div>
                            <table class="logbook-table logbook-table--compact">
                                <thead><tr><th>Medicine</th><th>Qty</th></tr></thead>
                                <tbody>
                                    <tr><td>Ambroxol</td><td><input class="lb-edit" type="number" min="0" value="0"></td></tr>
                                    <tr><td>Biogesic</td><td><input class="lb-edit" type="number" min="0" value="22"></td></tr>
                                    <tr><td>Buscopan</td><td><input class="lb-edit" type="number" min="0" value="2"></td></tr>
                                    <tr><td>Cetirizine</td><td><input class="lb-edit" type="number" min="0" value="14"></td></tr>
                                    <tr><td>Clonidine</td><td><input class="lb-edit" type="number" min="0" value="0"></td></tr>
                                    <tr><td>Diatabs</td><td><input class="lb-edit" type="number" min="0" value="0"></td></tr>
                                    <tr><td>Domperidone</td><td><input class="lb-edit" type="number" min="0" value="0"></td></tr>
                                    <tr><td>Gaviscon</td><td><input class="lb-edit" type="number" min="0" value="0"></td></tr>
                                    <tr><td>Ibuprofen</td><td><input class="lb-edit" type="number" min="0" value="0"></td></tr>
                                    <tr><td>Kremil-S</td><td><input class="lb-edit" type="number" min="0" value="0"></td></tr>
                                    <tr><td>Lozenges</td><td><input class="lb-edit" type="number" min="0" value="0"></td></tr>
                                    <tr><td>Mefenamic Acid</td><td><input class="lb-edit" type="number" min="0" value="0"></td></tr>
                                    <tr><td>Neozep</td><td><input class="lb-edit" type="number" min="0" value="0"></td></tr>
                                    <tr><td>ORS</td><td><input class="lb-edit" type="number" min="0" value="0"></td></tr>
                                    <tr><td>Sinecod</td><td><input class="lb-edit" type="number" min="0" value="0"></td></tr>
                                    <tr><td>Serc</td><td><input class="lb-edit" type="number" min="0" value="0"></td></tr>
                                    <tr><td>Ventolin Nebules</td><td><input class="lb-edit" type="number" min="0" value="0"></td></tr>
                                    <tr><td>Prednisone</td><td><input class="lb-edit" type="number" min="0" value="0"></td></tr>
                                    <tr><td>Benadryl</td><td><input class="lb-edit" type="number" min="0" value="0"></td></tr>
                                    <tr><td>Diphenhydramine Amp</td><td><input class="lb-edit" type="number" min="0" value="0"></td></tr>
                                    <tr><td>Norgesic Forte</td><td><input class="lb-edit" type="number" min="0" value="0"></td></tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Supplies Used -->
                        <div>
                            <table class="logbook-table logbook-table--compact">
                                <thead><tr><th>🩹 Supplies Used</th><th>Qty</th></tr></thead>
                                <tbody>
                                    <tr><td>Betadine</td><td><input class="lb-edit" type="number" min="0" value="1"></td></tr>
                                    <tr><td>Cotton Balls</td><td><input class="lb-edit" type="number" min="0" value="0"></td></tr>
                                    <tr><td>Cotton Buds</td><td><input class="lb-edit" type="number" min="0" value="1"></td></tr>
                                    <tr><td>Elastic Bandage</td><td><input class="lb-edit" type="number" min="0" value="0"></td></tr>
                                    <tr><td>Gauze</td><td><input class="lb-edit" type="number" min="0" value="1"></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="logbook-section-label" style="margin-top:20px;">
                        <span class="sec-emoji">📝</span> B. NOTES / REMARKS
                    </div>
                    <textarea class="lb-edit-text" id="lbNotes" rows="4"
                        style="width:100%;border:1.5px solid #e0e7ff;border-radius:12px;padding:12px 14px;font-size:.82rem;font-family:'Nunito',sans-serif;resize:vertical;color:#374151;background:#f8faff;"
                        placeholder="Add any notes, remarks, or observations for today's logbook…"></textarea>
                    <div class="logbook-pdf-footer">
                    <span>NUCARE Clinic Portal · NU Bacolod</span>
                    <span id="lbPrintDate"></span>
                    <span>Medical Staff Daily Logbook</span>
                </div>
                </div><!-- /.logbook-body -->
            </div><!-- /.logbook-card -->
        </div><!-- /.logbook-wrapper -->

        <!-- Toast -->
        <div class="lb-toast" id="lbToast">
            <i class="fa-solid fa-circle-check"></i> Logbook saved successfully! ✨
        </div>

        <script>
        /* ── Period helpers ── */
        function getLbKey() {
            const m = document.getElementById('lbPickerMonth');
            const y = document.getElementById('lbPickerYear');
            if (!m || !y) return 'nucare_logbook';
            const pad = String(m.value).padStart(2,'0');
            return 'nucare_logbook_' + y.value + '_' + pad;
        }

        function getPeriodLabel() {
            const m = document.getElementById('lbPickerMonth');
            const y = document.getElementById('lbPickerYear');
            if (!m || !y) return '';
            return m.options[m.selectedIndex].text + ' ' + y.value;
        }

        function updateMonthDisplay() {
            const m = document.getElementById('lbPickerMonth');
            const lbMonth = document.getElementById('lbMonth');
            if (m && lbMonth) lbMonth.textContent = m.options[m.selectedIndex].text;
        }

        function updatePeriodBadge(state) {
            const badge = document.getElementById('lbPeriodBadge');
            if (!badge) return;
            if (state === 'loaded') {
                badge.textContent = '✔ Record loaded';
                badge.className = 'lb-period-badge loaded';
            } else {
                badge.textContent = '✦ New record';
                badge.className = 'lb-period-badge new';
            }
        }

        /* ── Load logbook for the selected period ── */
        function loadLogbookForPeriod() {
            updateMonthDisplay();
            const key = getLbKey();
            try {
                const raw = localStorage.getItem(key);
                const allInputs = document.querySelectorAll('.lb-edit');
                const notes = document.getElementById('lbNotes');

                if (raw) {
                    const data = JSON.parse(raw);
                    allInputs.forEach((inp, i) => {
                        inp.value = (data['lb_input_' + i] !== undefined) ? data['lb_input_' + i] : 0;
                        inp.dispatchEvent(new Event('input'));
                    });
                    if (notes && data['lb_notes'] !== undefined) notes.value = data['lb_notes'];
                    updatePeriodBadge('loaded');
                } else {
                    // blank slate for a new period
                    allInputs.forEach(inp => { inp.value = 0; inp.dispatchEvent(new Event('input')); });
                    if (notes) notes.value = '';
                    updatePeriodBadge('new');
                }
            } catch(e) { updatePeriodBadge('new'); }
        }

        /* ── Wire up picker changes ── */
        (function wirePickers() {
            const mPicker = document.getElementById('lbPickerMonth');
            const yPicker = document.getElementById('lbPickerYear');
            if (mPicker) mPicker.addEventListener('change', loadLogbookForPeriod);
            if (yPicker) yPicker.addEventListener('change', loadLogbookForPeriod);
        })();

        /* ── Auto-total for tables with data-autototal ── */
        document.querySelectorAll('table[data-autototal="true"]').forEach(table => {
            table.querySelectorAll('tbody tr').forEach(row => {
                const inputs = row.querySelectorAll('.lb-edit');
                const totalCell = row.querySelector('.lb-total');
                if (!totalCell || inputs.length === 0) return;

                function recalc() {
                    let sum = 0;
                    inputs.forEach(inp => { sum += parseInt(inp.value) || 0; });
                    totalCell.textContent = sum;
                }

                inputs.forEach(inp => {
                    inp.addEventListener('input', recalc);
                });
            });
        });

        /* ── Save / toast ── */
        function saveLogbook() {
            const btn = document.getElementById('lbSaveBtn');
            const toast = document.getElementById('lbToast');

            // Update timestamp
            const now = new Date();
            const stamp = document.getElementById('lbDateStamp');
            if (stamp) {
                stamp.textContent = now.toLocaleDateString('en-PH', { month: 'numeric', day: 'numeric', year: 'numeric' })
                    + ' ' + now.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            }

            // Save all inputs to localStorage under the period key
            const key = getLbKey();
            const data = {};
            document.querySelectorAll('.lb-edit').forEach((inp, i) => {
                data['lb_input_' + i] = inp.value;
            });
            const notes = document.getElementById('lbNotes');
            if (notes) data['lb_notes'] = notes.value;
            try { localStorage.setItem(key, JSON.stringify(data)); } catch(e){}

            updatePeriodBadge('loaded');

            // Button feedback
            btn.classList.add('saved');
            btn.innerHTML = '<i class="fa-solid fa-circle-check"></i> Saved!';
            setTimeout(() => {
                btn.classList.remove('saved');
                btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Save Logbook';
            }, 2500);

            // Toast
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }
        /* ── Export Logbook as PDF ── */
    async function exportLogbookPDF() {
        const btn   = document.getElementById('lbExportBtn');
        const label = btn.querySelector('.lb-export-label');

        // 1. Freeze all auto-totals
        document.querySelectorAll('table[data-autototal="true"]').forEach(table => {
            table.querySelectorAll('tbody tr').forEach(row => {
                const inputs    = row.querySelectorAll('.lb-edit');
                const totalCell = row.querySelector('.lb-total');
                if (!totalCell || inputs.length === 0) return;
                let sum = 0;
                inputs.forEach(inp => { sum += parseInt(inp.value) || 0; });
                totalCell.textContent = sum;
            });
        });

        // 2. Stamp export date/time in footer
        const footerDate = document.getElementById('lbPrintDate');
        const now = new Date();
        const dateStr = now.toLocaleDateString('en-PH', { month: 'long', day: 'numeric', year: 'numeric' })
            + ' · ' + now.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' });
        if (footerDate) footerDate.textContent = 'Exported: ' + dateStr;

        // 3. Show loading state
        btn.classList.add('exporting');
        label.textContent = 'Generating…';

        // 4. Show the PDF footer temporarily
        const pdfFooter = document.querySelector('.logbook-pdf-footer');
        if (pdfFooter) pdfFooter.style.display = 'flex';

        // 5. Hide UI-only elements from the capture
        const uiOnly = document.querySelectorAll('.logbook-savebar, .logbook-deco-banner, .lb-toast');
        uiOnly.forEach(el => el.style.visibility = 'hidden');

        try {
            const card = document.getElementById('logbookCard');

            // 6. Capture with html2canvas
            const canvas = await html2canvas(card, {
                scale: 2,
                useCORS: true,
                backgroundColor: '#ffffff',
                logging: false,
                windowWidth: card.scrollWidth,
                windowHeight: card.scrollHeight
            });

            // 7. Build a styled PDF with jsPDF
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });

            const pageW  = 210;   // A4 width mm
            const pageH  = 297;   // A4 height mm
            const margin = 12;
            const usableW = pageW - margin * 2;

            /* ── PDF Cover Header ── */
            // Blue-to-purple gradient band
            pdf.setFillColor(37, 99, 235);
            pdf.rect(0, 0, pageW, 32, 'F');
            pdf.setFillColor(124, 58, 237);
            pdf.rect(pageW * 0.5, 0, pageW * 0.5, 32, 'F');

            // Diagonal shimmer strip
            pdf.setFillColor(255, 255, 255);
            pdf.setGState(new pdf.GState({ opacity: 0.06 }));
            for (let x = -10; x < pageW + 30; x += 18) {
                pdf.triangle(x, 0, x + 12, 0, x - 12, 32, 'F');
            }
            pdf.setGState(new pdf.GState({ opacity: 1 }));

            // NUCARE wordmark
            pdf.setTextColor(255, 255, 255);
            pdf.setFont('helvetica', 'bold');
            pdf.setFontSize(15);
            pdf.text('✦ NUCARE CLINIC PORTAL', margin, 12);

            // Subtitle
            pdf.setFont('helvetica', 'normal');
            pdf.setFontSize(8);
            pdf.setTextColor(199, 210, 254);
            pdf.text('NU Bacolod · Medical Staff Daily Logbook', margin, 19);

            // Date badge (right side)
            const dateLabel = 'Date: ' + dateStr;
            pdf.setFont('helvetica', 'bold');
            pdf.setFontSize(7.5);
            pdf.setTextColor(255, 255, 255);
            const dtW = pdf.getTextWidth(dateLabel) + 10;
            pdf.setFillColor(255, 255, 255);
            pdf.setGState(new pdf.GState({ opacity: 0.18 }));
            pdf.roundedRect(pageW - margin - dtW, 9, dtW, 10, 3, 3, 'F');
            pdf.setGState(new pdf.GState({ opacity: 1 }));
            pdf.text(dateLabel, pageW - margin - dtW + 5, 15.5);

            /* ── Thin accent rule ── */
            pdf.setDrawColor(165, 180, 252);
            pdf.setLineWidth(0.4);
            pdf.line(margin, 34, pageW - margin, 34);

            /* ── Paste the canvas ── */
            const imgData = canvas.toDataURL('image/jpeg', 0.97);
            const imgW    = usableW;
            const imgH    = (canvas.height * imgW) / canvas.width;
            const startY  = 37;
            const maxH    = pageH - startY - 20; // leave room for footer

            // If image fits on one page
            if (imgH <= maxH) {
                pdf.addImage(imgData, 'JPEG', margin, startY, imgW, imgH);
            } else {
                // Multi-page: slice the canvas
                const totalPages = Math.ceil(imgH / maxH);
                for (let p = 0; p < totalPages; p++) {
                    if (p > 0) pdf.addPage();
                    const srcY   = (p * maxH * canvas.width) / imgW;
                    const srcH   = Math.min((maxH * canvas.width) / imgW, canvas.height - srcY);
                    const sliceH = (srcH * imgW) / canvas.width;

                    // Slice canvas for this page
                    const slice = document.createElement('canvas');
                    slice.width  = canvas.width;
                    slice.height = srcH;
                    const ctx = slice.getContext('2d');
                    ctx.drawImage(canvas, 0, srcY, canvas.width, srcH, 0, 0, canvas.width, srcH);

                    // Add header on continuation pages
                    if (p > 0) {
                        pdf.setFillColor(37, 99, 235);
                        pdf.rect(0, 0, pageW, 10, 'F');
                        pdf.setFillColor(124, 58, 237);
                        pdf.rect(pageW * 0.5, 0, pageW * 0.5, 10, 'F');
                        pdf.setFont('helvetica', 'bold');
                        pdf.setFontSize(7);
                        pdf.setTextColor(255, 255, 255);
                        pdf.text('NUCARE · Daily Logbook (continued)', margin, 7);
                        pdf.addImage(slice.toDataURL('image/jpeg', 0.97), 'JPEG', margin, 12, imgW, sliceH);
                    } else {
                        pdf.addImage(slice.toDataURL('image/jpeg', 0.97), 'JPEG', margin, startY, imgW, sliceH);
                    }
                }
            }

            /* ── PDF Footer on each page ── */
            const totalPgs = pdf.internal.getNumberOfPages();
            for (let p = 1; p <= totalPgs; p++) {
                pdf.setPage(p);
                pdf.setDrawColor(199, 210, 254);
                pdf.setLineWidth(0.3);
                pdf.line(margin, pageH - 14, pageW - margin, pageH - 14);
                pdf.setFont('helvetica', 'normal');
                pdf.setFontSize(6.5);
                pdf.setTextColor(107, 114, 128);
                pdf.text('NUCARE Clinic Portal · NU Bacolod · Medical Staff Daily Logbook', margin, pageH - 9);
                pdf.text('Page ' + p + ' of ' + totalPgs, pageW - margin - 16, pageH - 9);
                // Tiny purple dot accent
                pdf.setFillColor(124, 58, 237);
                pdf.circle(margin - 4, pageH - 9.5, 1.2, 'F');
            }

            // 8. Save the PDF
            const fileName = 'NUCARE_Logbook_' + now.toISOString().slice(0,10) + '.pdf';
            pdf.save(fileName);

        } catch (err) {
            console.error('PDF export error:', err);
            alert('PDF export failed. Please try again.');
        } finally {
            // 9. Restore UI
            uiOnly.forEach(el => el.style.visibility = '');
            if (pdfFooter) pdfFooter.style.display = '';
            btn.classList.remove('exporting');
            label.textContent = 'Export PDF';
        }
    }

        /* ── Restore from localStorage on load (period-aware) ── */
        loadLogbookForPeriod();
        </script>
    </main>

</div>
<script src="../../assets/js/app.js"></script>
<script src="../../assets/js/medical_staff_notifications.js?v=1"></script>
</body>
</html>