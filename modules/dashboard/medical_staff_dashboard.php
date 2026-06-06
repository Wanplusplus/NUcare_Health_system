<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['UserID'])) {
    header('Location: ../../auth/login.php');
    exit;
}

if (isset($_SESSION['Roles']) && is_array($_SESSION['Roles']) && array_intersect($_SESSION['Roles'], ['Admin', 'Super Admin']) !== []) {
    header('Location: admin_dashboard.php');
    exit;
}

$activeSidebarItem = 'dashboard';
$staffName = $_SESSION['patient_name'] ?? 'Medical Staff';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --clinic-primary: #0b3d91;
            --clinic-primary-2: #1660d7;
            --clinic-accent: #d4af37;
            --clinic-bg: #f5f8fb;
            --clinic-surface: #ffffff;
            --clinic-border: #d8e3ea;
            --clinic-text: #172033;
            --clinic-muted: #627084;
            --clinic-danger: #b91c1c;
            --clinic-warn: #b45309;
            --clinic-good: #0b3d91;
            --clinic-shadow: 0 14px 34px rgba(11, 61, 145, .12);
        }

        .clinic-page { display: flex; flex-direction: column; gap: 16px; padding-bottom: 28px; }
        .clinic-hero {
            border: 1px solid #cfe0ff;
            background: linear-gradient(135deg, #0b3d91 0%, #1660d7 100%);
            color: #fff;
            border-radius: 8px;
            padding: 22px 24px;
            box-shadow: var(--clinic-shadow);
        }
        .clinic-hero-row { display: flex; justify-content: space-between; gap: 18px; flex-wrap: wrap; align-items: flex-start; }
        .clinic-greeting { margin: 0 0 6px; font-size: 26px; font-weight: 800; letter-spacing: 0; }
        .clinic-hero p { margin: 0; color: rgba(255,255,255,.9); }
        .clinic-clock { text-align: right; min-width: 220px; font-weight: 800; }
        .clinic-clock span { display: block; font-size: 12px; font-weight: 700; color: rgba(255,255,255,.84); margin-top: 4px; }

        .kpi-grid { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 12px; }
        .kpi-card, .clinic-card {
            background: var(--clinic-surface);
            border: 1px solid var(--clinic-border);
            border-radius: 8px;
            box-shadow: var(--clinic-shadow);
        }
        .kpi-card { padding: 16px; min-height: 132px; display: flex; flex-direction: column; gap: 12px; }
        .kpi-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .kpi-icon { width: 38px; height: 38px; border-radius: 8px; display: grid; place-items: center; color: var(--clinic-primary); background: #eff6ff; }
        .kpi-title { font-size: 12px; color: var(--clinic-muted); font-weight: 800; text-transform: uppercase; letter-spacing: .04em; }
        .kpi-value { font-size: 30px; font-weight: 850; color: var(--clinic-text); line-height: 1; }
        .kpi-trend { font-size: 12px; color: var(--clinic-muted); line-height: 1.35; }
        .kpi-card.warning { border-color: #f1c274; background: #fffaf0; }
        .kpi-card.warning .kpi-icon { background: #fef3c7; color: var(--clinic-warn); }

        .clinic-grid { display: grid; grid-template-columns: minmax(0, 1.45fr) minmax(320px, .75fr); gap: 16px; align-items: start; }
        .clinic-stack { display: flex; flex-direction: column; gap: 16px; min-width: 0; }
        .clinic-card { padding: 18px; min-width: 0; }
        .card-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; margin-bottom: 14px; }
        .card-title { margin: 0; color: var(--clinic-text); font-size: 16px; font-weight: 850; }
        .card-subtitle { margin: 4px 0 0; color: var(--clinic-muted); font-size: 12px; }
        .segmented { display: inline-flex; border: 1px solid var(--clinic-border); border-radius: 8px; overflow: hidden; background: #fff; }
        .segmented button { border: 0; background: transparent; color: var(--clinic-muted); padding: 8px 11px; font-weight: 800; cursor: pointer; }
        .segmented button.active { background: var(--clinic-primary); color: #fff; }
        .chart-row { display: grid; grid-template-columns: minmax(0, 1.2fr) minmax(260px, .8fr); gap: 16px; align-items: stretch; }
        .chart-box { border: 1px solid var(--clinic-border); border-radius: 8px; padding: 12px; min-height: 260px; background: #fbfdff; overflow: hidden; }
        canvas { width: 100%; height: 220px; display: block; max-width: 100%; flex: 0 0 auto; }
        #complaintChart { height: 260px; }
        #medicineChart, #inventoryChart { height: 250px; }
        .chart-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-top: 12px; }
        .mini-stat { border: 1px solid var(--clinic-border); border-radius: 8px; padding: 10px; background: #fff; }
        .mini-stat .label { color: var(--clinic-muted); font-size: 11px; font-weight: 800; text-transform: uppercase; }
        .mini-stat .value { color: var(--clinic-text); font-size: 18px; font-weight: 850; margin-top: 3px; }
        .split-two { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }

        .insight-list, .activity-list, .alert-list { display: flex; flex-direction: column; gap: 10px; }
        .insight-item, .activity-item, .alert-item {
            border: 1px solid var(--clinic-border);
            border-radius: 8px;
            padding: 12px;
            background: #fbfdff;
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }
        .item-icon { width: 34px; height: 34px; border-radius: 8px; background: #eff6ff; color: var(--clinic-primary); display: grid; place-items: center; flex: 0 0 auto; }
        .item-main { min-width: 0; flex: 1; }
        .item-title { color: var(--clinic-text); font-size: 13px; font-weight: 850; margin-bottom: 3px; }
        .item-text { color: var(--clinic-muted); font-size: 12px; line-height: 1.4; overflow-wrap: anywhere; }
        .activity-time { font-size: 11px; color: var(--clinic-primary); font-weight: 800; margin-top: 5px; }
        .priority { font-size: 10px; font-weight: 850; text-transform: uppercase; border-radius: 999px; padding: 4px 8px; }
        .priority.high { background: #fee2e2; color: var(--clinic-danger); }
        .priority.medium { background: #fef3c7; color: var(--clinic-warn); }
        .priority.low { background: #dbeafe; color: var(--clinic-good); }

        .empty-state, .error-state { border: 1px dashed var(--clinic-border); border-radius: 8px; padding: 18px; color: var(--clinic-muted); text-align: center; background: #fbfdff; }
        .error-state { border-color: #fecaca; color: var(--clinic-danger); background: #fff7f7; }
        .skeleton { position: relative; overflow: hidden; background: #e9eef4; border-radius: 8px; min-height: 18px; }
        .skeleton::after { content: ''; position: absolute; inset: 0; transform: translateX(-100%); background: linear-gradient(90deg, transparent, rgba(255,255,255,.65), transparent); animation: shimmer 1.15s infinite; }
        @keyframes shimmer { 100% { transform: translateX(100%); } }

        @media (max-width: 1220px) { .kpi-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } .clinic-grid { grid-template-columns: 1fr; } }
        @media (max-width: 820px) { .chart-row, .split-two { grid-template-columns: 1fr; } .clinic-clock { text-align: left; } }
        @media (max-width: 640px) { .kpi-grid { grid-template-columns: 1fr; } .chart-stats { grid-template-columns: 1fr; } .clinic-hero { padding: 18px; } .clinic-greeting { font-size: 22px; } }
    </style>
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
                <p class="page-description">Live clinic operations, patient flow, and medicine activity.</p>
            </div>
            <div class="header-actions">
                <div class="notif-bell">
                    <button id="notifBellBtn" type="button" aria-haspopup="true" aria-expanded="false" aria-label="Notifications">
                        <i class="fa-solid fa-bell"></i>
                        <span>Notifications</span>
                        <span class="notif-badge" id="notifBadge" style="display:none;">0</span>
                    </button>
                    <div id="notifDropdown" class="notif-dropdown" role="menu" aria-label="Notification list">
                        <div class="notif-header"><h4>Real-time Alerts</h4><div class="notif-lastup" id="notifLastUpdated"></div></div>
                        <div class="notif-body">
                            <div class="notif-loading" id="notifLoading">Loading...</div>
                            <div class="notif-empty" id="notifEmpty">No alerts right now.</div>
                            <div id="notifList"></div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <section class="clinic-page">
            <div class="clinic-hero">
                <div class="clinic-hero-row">
                    <div>
                        <h1 class="clinic-greeting" id="clinicGreeting">Good day, <?php echo htmlspecialchars($staffName); ?></h1>
                        <p>Monitor consultations, patient activity, medicine dispensing, and clinic performance.</p>
                    </div>
                    <div class="clinic-clock" id="clinicClock">Loading time<span>Current clinic date and time</span></div>
                </div>
            </div>

            <div class="kpi-grid" id="kpiGrid">
                <?php for ($i = 0; $i < 6; $i++): ?>
                    <div class="kpi-card"><div class="skeleton" style="height:38px;width:38px;"></div><div class="skeleton" style="height:14px;"></div><div class="skeleton" style="height:30px;width:72px;"></div><div class="skeleton"></div></div>
                <?php endfor; ?>
            </div>

            <div id="dashboardError" class="error-state" style="display:none;"></div>

            <div class="clinic-grid">
                <div class="clinic-stack">
                    <section class="clinic-card">
                        <div class="card-head">
                            <div>
                                <h3 class="card-title">Consultation Analytics</h3>
                                <p class="card-subtitle">Consultation volume trends and current status distribution.</p>
                            </div>
                            <div class="segmented" aria-label="Consultation trend range">
                                <button type="button" data-trend="daily" class="active">Daily</button>
                                <button type="button" data-trend="weekly">Weekly</button>
                                <button type="button" data-trend="monthly">Monthly</button>
                            </div>
                        </div>
                        <div class="chart-row">
                            <div class="chart-box">
                                <canvas id="trendChart" height="220"></canvas>
                                <div class="chart-stats">
                                    <div class="mini-stat"><div class="label">Total Consultations</div><div class="value" id="trendTotal">0</div></div>
                                    <div class="mini-stat"><div class="label">Average</div><div class="value" id="trendAverage">0</div></div>
                                    <div class="mini-stat"><div class="label">Highest Day</div><div class="value" id="trendHighest">None</div></div>
                                </div>
                            </div>
                            <div class="chart-box">
                                <canvas id="statusChart" height="220"></canvas>
                            </div>
                        </div>
                    </section>

                    <section class="clinic-card">
                        <div class="card-head">
                            <div>
                                <h3 class="card-title">Most Common Health Complaints</h3>
                                <p class="card-subtitle">Top clinic complaints from consultation records.</p>
                            </div>
                        </div>
                        <div class="chart-box"><canvas id="complaintChart" height="260"></canvas></div>
                    </section>

                    <section class="clinic-card">
                        <div class="card-head">
                            <div>
                                <h3 class="card-title">Medicine Dispensing Analytics</h3>
                                <p class="card-subtitle">Frequently released medicines and inventory health.</p>
                            </div>
                        </div>
                        <div class="split-two">
                            <div class="chart-box"><canvas id="medicineChart" height="250"></canvas></div>
                            <div class="chart-box"><canvas id="inventoryChart" height="250"></canvas></div>
                        </div>
                    </section>
                </div>

                <aside class="clinic-stack">
                    <section class="clinic-card">
                        <div class="card-head"><h3 class="card-title">Clinic Insights</h3></div>
                        <div class="insight-list" id="insightList"><div class="skeleton" style="height:70px;"></div><div class="skeleton" style="height:70px;"></div></div>
                    </section>
                    <section class="clinic-card">
                        <div class="card-head"><h3 class="card-title">Recent Activities</h3></div>
                        <div class="activity-list" id="activityList"><div class="skeleton" style="height:70px;"></div><div class="skeleton" style="height:70px;"></div></div>
                    </section>
                    <section class="clinic-card">
                        <div class="card-head"><h3 class="card-title">Alerts & Notifications</h3></div>
                        <div class="alert-list" id="alertList"><div class="skeleton" style="height:70px;"></div><div class="skeleton" style="height:70px;"></div></div>
                    </section>
                </aside>
            </div>
        </section>
    </main>
</div>

<script src="../../assets/js/app.js"></script>
<script src="../../assets/js/medical_staff_notifications.js?v=3"></script>
<script>
(function () {
    'use strict';

    const endpoint = '/NUcare_Health_system/ajax/medical_staff_dashboard.ajax.php';
    let currentTrend = 'daily';
    let lastDashboardData = null;
    let trendRequestId = 0;

    function el(id) { return document.getElementById(id); }
    function num(value) { return Number(value || 0).toLocaleString(); }
    function esc(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (m) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[m];
        });
    }
    function colors() { return ['#0b3d91', '#1660d7', '#d4af37', '#2563eb', '#1d4ed8', '#7c3aed', '#be123c', '#475569', '#0891b2', '#4338ca']; }

    function tickClock() {
        const now = new Date();
        const hour = now.getHours();
        const greeting = hour < 12 ? 'Good Morning' : (hour < 18 ? 'Good Afternoon' : 'Good Evening');
        const name = window.clinicStaffName || '<?php echo addslashes($staffName); ?>';
        el('clinicGreeting').textContent = greeting + ', ' + name;
        el('clinicClock').innerHTML = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) + ' ' +
            now.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' }) + '<span>Current clinic date and time</span>';
    }

    function setupCanvas(canvas) {
        const rect = canvas.getBoundingClientRect();
        const dpr = window.devicePixelRatio || 1;
        const cssHeight = parseInt(window.getComputedStyle(canvas).height, 10) || Number(canvas.getAttribute('height') || 220);
        canvas.width = Math.max(1, Math.floor(rect.width * dpr));
        canvas.height = Math.max(1, Math.floor(cssHeight * dpr));
        const ctx = canvas.getContext('2d');
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        return { ctx, width: rect.width, height: cssHeight };
    }

    function drawEmpty(canvasId, text) {
        const canvas = el(canvasId);
        const setup = setupCanvas(canvas);
        setup.ctx.clearRect(0, 0, setup.width, setup.height);
        setup.ctx.fillStyle = '#627084';
        setup.ctx.font = '13px Arial';
        setup.ctx.textAlign = 'center';
        setup.ctx.fillText(text, setup.width / 2, setup.height / 2);
    }

    function drawLine(canvasId, rows) {
        if (!rows || rows.length === 0) return drawEmpty(canvasId, 'No consultation trend data');
        const {ctx, width, height} = setupCanvas(el(canvasId));
        const pad = 34;
        const max = Math.max(1, ...rows.map(r => Number(r.total || 0)));
        ctx.clearRect(0, 0, width, height);
        ctx.strokeStyle = '#d8e3ea';
        ctx.lineWidth = 1;
        for (let i = 0; i < 4; i++) {
            const y = pad + ((height - pad * 2) / 3) * i;
            ctx.beginPath(); ctx.moveTo(pad, y); ctx.lineTo(width - pad, y); ctx.stroke();
        }
        ctx.strokeStyle = '#0b3d91';
        ctx.lineWidth = 3;
        ctx.beginPath();
        rows.forEach((r, i) => {
            const x = pad + ((width - pad * 2) / Math.max(1, rows.length - 1)) * i;
            const y = height - pad - (Number(r.total || 0) / max) * (height - pad * 2);
            if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
        });
        ctx.stroke();
        ctx.fillStyle = '#1660d7';
        rows.forEach((r, i) => {
            const x = pad + ((width - pad * 2) / Math.max(1, rows.length - 1)) * i;
            const y = height - pad - (Number(r.total || 0) / max) * (height - pad * 2);
            ctx.beginPath(); ctx.arc(x, y, 4, 0, Math.PI * 2); ctx.fill();
        });
        ctx.fillStyle = '#627084';
        ctx.font = '11px Arial';
        ctx.textAlign = 'center';
        rows.filter((_, i) => i === 0 || i === rows.length - 1 || i === Math.floor(rows.length / 2)).forEach((r, idx) => {
            const i = idx === 0 ? 0 : (idx === 1 && rows.length > 2 ? Math.floor(rows.length / 2) : rows.length - 1);
            const x = pad + ((width - pad * 2) / Math.max(1, rows.length - 1)) * i;
            ctx.fillText(String(rows[i].label || ''), x, height - 8);
        });
    }

    function drawDoughnut(canvasId, rows) {
        if (!rows || rows.length === 0) return drawEmpty(canvasId, 'No status data');
        const {ctx, width, height} = setupCanvas(el(canvasId));
        const total = rows.reduce((s, r) => s + Number(r.total || 0), 0);
        if (!total) return drawEmpty(canvasId, 'No status data');
        const cx = width / 2, cy = 88, radius = 64;
        let start = -Math.PI / 2;
        ctx.clearRect(0, 0, width, height);
        rows.forEach((r, i) => {
            const slice = (Number(r.total || 0) / total) * Math.PI * 2;
            ctx.beginPath(); ctx.moveTo(cx, cy); ctx.arc(cx, cy, radius, start, start + slice); ctx.closePath();
            ctx.fillStyle = colors()[i % colors().length]; ctx.fill();
            start += slice;
        });
        ctx.globalCompositeOperation = 'destination-out';
        ctx.beginPath(); ctx.arc(cx, cy, 38, 0, Math.PI * 2); ctx.fill();
        ctx.globalCompositeOperation = 'source-over';
        ctx.fillStyle = '#172033'; ctx.font = '800 18px Arial'; ctx.textAlign = 'center'; ctx.fillText(num(total), cx, cy + 6);
        ctx.font = '12px Arial';
        rows.forEach((r, i) => {
            const y = 170 + i * 18;
            ctx.fillStyle = colors()[i % colors().length]; ctx.fillRect(16, y - 9, 10, 10);
            ctx.fillStyle = '#172033'; ctx.textAlign = 'left';
            ctx.fillText((r.status_label || r.label) + ' - ' + Math.round(Number(r.total || 0) / total * 100) + '%', 32, y);
        });
    }

    function drawBars(canvasId, rows, labelKey) {
        if (!rows || rows.length === 0) return drawEmpty(canvasId, 'No records found');
        const {ctx, width, height} = setupCanvas(el(canvasId));
        const max = Math.max(1, ...rows.map(r => Number(r.total || 0)));
        const left = 118, top = 16, barH = Math.min(18, (height - 28) / rows.length - 5);
        ctx.clearRect(0, 0, width, height);
        ctx.font = '12px Arial';
        rows.forEach((r, i) => {
            const y = top + i * (barH + 7);
            const label = String(r[labelKey] || r.label || '').slice(0, 18);
            const w = (width - left - 40) * (Number(r.total || 0) / max);
            ctx.fillStyle = '#627084'; ctx.textAlign = 'right'; ctx.fillText(label, left - 10, y + barH - 4);
            ctx.fillStyle = colors()[i % colors().length]; ctx.fillRect(left, y, w, barH);
            ctx.fillStyle = '#172033'; ctx.textAlign = 'left'; ctx.fillText(String(r.total || 0), left + w + 6, y + barH - 4);
        });
    }

    function renderKpis(kpis) {
        el('kpiGrid').innerHTML = kpis.map(kpi => '<article class="kpi-card ' + (kpi.warning ? 'warning' : '') + '">' +
            '<div class="kpi-top"><div class="kpi-icon"><i class="fa-solid ' + esc(kpi.icon) + '"></i></div><div class="kpi-title">' + esc(kpi.title) + '</div></div>' +
            '<div class="kpi-value">' + num(kpi.value) + '</div><div class="kpi-trend">' + esc(kpi.trend) + '</div></article>').join('');
    }

    function renderList(id, rows, type) {
        if (!rows || rows.length === 0) {
            el(id).innerHTML = '<div class="empty-state">No records to display.</div>';
            return;
        }
        el(id).innerHTML = rows.map((row, idx) => {
            if (type === 'insight') {
                return '<article class="insight-item"><div class="item-icon"><i class="fa-solid fa-chart-simple"></i></div><div class="item-main"><div class="item-title">' + esc(row.title) + '</div><div class="item-text">' + esc(row.text) + '</div></div></article>';
            }
            const date = new Date(String(row.happened_at || row.alert_date || row.ExpiryDate || '').replace(' ', 'T'));
            const time = isNaN(date.getTime()) ? '' : date.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
            return '<article class="activity-item"><div class="item-icon"><i class="fa-solid ' + (idx % 2 ? 'fa-notes-medical' : 'fa-circle-check') + '"></i></div><div class="item-main"><div class="item-title">' + esc(row.activity_type || row.title || row.MedicineName || 'Activity') + '</div><div class="item-text">' + esc(row.patient_name || row.message || row.Notes || row.MedicineName || '') + '</div><div class="activity-time">' + esc(time) + '</div></div></article>';
        }).join('');
    }

    function renderAlerts(alerts) {
        const rows = [];
        (alerts.lowStock || []).forEach(r => rows.push({priority:'high', title:'Low Stock Alert', text:r.MedicineName + ' (' + r.remaining + ' remaining)'}));
        (alerts.followUps || []).forEach(r => rows.push({priority:'medium', title:'Follow-Up Alert', text:r.patient_name}));
        (alerts.expiring || []).forEach(r => rows.push({priority:'medium', title:'Expiring Medicines Alert', text:r.MedicineName + ' expires on ' + r.ExpiryDate}));
        if (!rows.length) {
            el('alertList').innerHTML = '<div class="empty-state">No clinic alerts right now.</div>';
            return;
        }
        el('alertList').innerHTML = rows.slice(0, 20).map(row => '<article class="alert-item"><div class="item-icon"><i class="fa-solid fa-triangle-exclamation"></i></div><div class="item-main"><div class="item-title">' + esc(row.title) + '</div><div class="item-text">' + esc(row.text) + '</div></div><span class="priority ' + row.priority + '">' + row.priority + '</span></article>').join('');
    }

    async function loadDashboard() {
        try {
            const response = await fetch(endpoint + '?trend=' + encodeURIComponent(currentTrend), { headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (!data.ok) throw new Error(data.message || 'Dashboard failed to load.');
            lastDashboardData = data;
            window.clinicStaffName = data.staffName || window.clinicStaffName;
            el('dashboardError').style.display = 'none';
            renderKpis(data.kpis || []);
            drawLine('trendChart', (data.consultationTrend || {}).rows || []);
            drawDoughnut('statusChart', data.statusDistribution || []);
            drawBars('complaintChart', data.commonComplaints || [], 'label');
            drawBars('medicineChart', data.mostDispensed || [], 'label');
            drawDoughnut('inventoryChart', data.inventoryStatus || []);
            el('trendTotal').textContent = num((data.consultationTrend || {}).total);
            el('trendAverage').textContent = String((data.consultationTrend || {}).average || 0);
            const highest = (data.consultationTrend || {}).highest || {};
            el('trendHighest').textContent = highest.total ? highest.label + ' (' + highest.total + ')' : 'None';
            renderList('insightList', data.insights || [], 'insight');
            renderList('activityList', data.activities || [], 'activity');
            renderAlerts(data.alerts || {});
        } catch (err) {
            el('dashboardError').textContent = err.message || 'Unable to load dashboard data.';
            el('dashboardError').style.display = 'block';
        }
    }

    async function loadTrendOnly(range) {
        const requestId = ++trendRequestId;
        try {
            const response = await fetch(endpoint + '?trend=' + encodeURIComponent(range), { headers: { Accept: 'application/json' } });
            const data = await response.json();
            if (!data.ok || requestId !== trendRequestId) return;
            lastDashboardData = Object.assign({}, lastDashboardData || {}, {
                consultationTrend: data.consultationTrend,
                statusDistribution: data.statusDistribution
            });
            drawLine('trendChart', (data.consultationTrend || {}).rows || []);
            drawDoughnut('statusChart', data.statusDistribution || []);
            el('trendTotal').textContent = num((data.consultationTrend || {}).total);
            el('trendAverage').textContent = String((data.consultationTrend || {}).average || 0);
            const highest = (data.consultationTrend || {}).highest || {};
            el('trendHighest').textContent = highest.total ? highest.label + ' (' + highest.total + ')' : 'None';
        } catch (err) {
            el('dashboardError').textContent = err.message || 'Unable to load consultation analytics.';
            el('dashboardError').style.display = 'block';
        }
    }

    function redrawCachedCharts() {
        if (!lastDashboardData) return;
        drawLine('trendChart', (lastDashboardData.consultationTrend || {}).rows || []);
        drawDoughnut('statusChart', lastDashboardData.statusDistribution || []);
        drawBars('complaintChart', lastDashboardData.commonComplaints || [], 'label');
        drawBars('medicineChart', lastDashboardData.mostDispensed || [], 'label');
        drawDoughnut('inventoryChart', lastDashboardData.inventoryStatus || []);
    }

    document.querySelectorAll('[data-trend]').forEach(button => {
        button.addEventListener('click', function () {
            document.querySelectorAll('[data-trend]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentTrend = this.dataset.trend;
            loadTrendOnly(currentTrend);
        });
    });

    let resizeTimer = null;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(redrawCachedCharts, 120);
    });
    tickClock();
    setInterval(tickClock, 1000);
    loadDashboard();
    setInterval(loadDashboard, 30000);
})();
</script>
</body>
</html>
