<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['UserID'])) {
    header('Location: ../../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../includes/module_guard.php';
requireModule('Admin Panel', 'access');

require_once __DIR__ . '/../../config/db.php';

// Collect session data for initial PHP render (profile will be loaded via AJAX too)
$patientName = $_SESSION['patient_name'] ?? 'User';
$roles       = $_SESSION['Roles'] ?? [];
$isSuperAdmin = in_array('Super Admin', $roles, true);

$activePage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE | Admin Dashboard</title>
    <link rel="icon" href="/NUcare_Health_system/assets/image/nucarelogo.png">
    <link rel="stylesheet" href="../../assets/css/app.css">
    <link rel="stylesheet" href="../../assets/css/admin_dashboard_overrides.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* Bootstrap's CSS reset may override some app.css sidebar defaults.
           Ensure the sidebar font and spacing always match the other admin
           modules by explicitly resetting the few affected properties. */
        .sidebar .sidebar-brand h1 {
            font-size: 20px !important;
            margin-bottom: 4px !important;
            font-weight: 700 !important;
        }
        .sidebar .sidebar-brand p {
            font-size: 13px !important;
            margin-bottom: 0 !important;
        }
        .sidebar .sidebar-footer .footer-title {
            font-size: 13px !important;
            margin-bottom: 10px !important;
        }
        .sidebar .sidebar-footer .status-pill {
            padding: 10px 14px !important;
            font-size: 13px !important;
        }

        /* ================================================================
           ADMIN DASHBOARD — Enterprise Design System
           ================================================================ */

        /* --- Colour tokens scoped to admin dashboard --- */
        :root {
            --adm-red: #8b0000;
            --adm-red-light: #b22222;
            --adm-bg: #f6f8fb;
            --adm-card: #ffffff;
            --adm-border: #e4e9f2;
            --adm-text: #1a1a3e;
            --adm-text-secondary: #5a6282;
            --adm-shadow: 0 2px 12px rgba(26,26,62,.07);
            --adm-shadow-hover: 0 6px 24px rgba(26,26,62,.12);
            --adm-radius: 16px;
            --adm-radius-sm: 10px;
            --adm-radius-pill: 999px;
        }

        /* --- Admin Dashboard Layout --- */
        .adm-wrap { padding-bottom: 48px; }

        .adm-section-title {
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--adm-text-secondary);
            margin-bottom: 14px;
            margin-top: 32px;
        }
        .adm-section-title:first-child { margin-top: 0; }

        /* ================================================================
           SECTION 1 — PROFILE CARD
           ================================================================ */
        .adm-profile-card {
            background: var(--adm-card);
            border: 1px solid var(--adm-border);
            border-radius: var(--adm-radius);
            box-shadow: var(--adm-shadow);
            display: flex;
            align-items: center;
            gap: 28px;
            padding: 28px 32px;
            transition: transform .18s, box-shadow .18s;
        }
        .adm-profile-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--adm-shadow-hover);
        }

        .adm-avatar {
            width: 72px; height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--adm-red) 0%, #5c0000 100%);
            color: #fff;
            display: grid; place-items: center;
            font-size: 28px; font-weight: 800;
            flex-shrink: 0;
            box-shadow: 0 4px 14px rgba(139,0,0,.22);
        }

        .adm-profile-info { flex: 1; min-width: 0; }

        .adm-profile-name-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 4px;
        }

        .adm-profile-name {
            font-family: 'Poppins', sans-serif;
            font-size: 20px;
            font-weight: 700;
            color: var(--adm-text);
        }

        .adm-badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: var(--adm-radius-pill);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.04em;
            white-space: nowrap;
        }
        .adm-badge.active {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .adm-badge.role {
            background: #ede9fe;
            color: #5b21b6;
            border: 1px solid #ddd6fe;
        }
        .adm-badge.superadmin {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .adm-profile-meta {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
            font-size: 13px;
            color: var(--adm-text-secondary);
            margin-top: 6px;
        }
        .adm-profile-meta span { display: inline-flex; align-items: center; gap: 5px; }
        .adm-profile-meta i { font-size: 14px; opacity: .7; }

        .adm-profile-footnote {
            font-size: 11px;
            color: #94a3c0;
            margin-top: 8px;
        }

        /* ================================================================
           SECTION 2 — ACTIVE USERS & STATUS CARD
           ================================================================ */
        .adm-users-card {
            background: var(--adm-card);
            border: 1px solid var(--adm-border);
            border-radius: var(--adm-radius);
            box-shadow: var(--adm-shadow);
            padding: 28px 32px;
            transition: transform .18s, box-shadow .18s;
        }
        .adm-users-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--adm-shadow-hover);
        }

        .adm-users-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 22px;
        }
        .adm-users-header h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            font-weight: 700;
            color: var(--adm-text);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Pulsing green dot */
        .adm-pulse-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            background: #16a34a;
            display: inline-block;
            position: relative;
        }
        .adm-pulse-dot::after {
            content: '';
            position: absolute;
            inset: -3px;
            border-radius: 50%;
            background: rgba(22,163,74,.35);
            animation: admPulse 1.8s ease-in-out infinite;
        }
        @keyframes admPulse {
            0%, 100% { transform: scale(1); opacity: .7; }
            50% { transform: scale(1.6); opacity: 0; }
        }

        .adm-status-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 22px;
        }

        .adm-status-item {
            text-align: center;
            padding: 16px 8px;
            border-radius: var(--adm-radius-sm);
            background: #f8fafd;
            border: 1px solid var(--adm-border);
        }
        .adm-status-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-bottom: 6px;
        }
        .adm-status-dot.green  { background: #16a34a; }
        .adm-status-dot.yellow { background: #d97706; }
        .adm-status-dot.red    { background: #dc2626; }

        .adm-status-count {
            font-family: 'Poppins', sans-serif;
            font-size: 26px;
            font-weight: 800;
            color: var(--adm-text);
            line-height: 1.1;
        }
        .adm-status-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--adm-text-secondary);
            margin-top: 2px;
        }

        /* Role breakdown */
        .adm-role-breakdown-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--adm-text-secondary);
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--adm-border);
        }
        .adm-role-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 8px;
        }
        .adm-role-chip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
            border-radius: var(--adm-radius-sm);
            background: #f8fafd;
            border: 1px solid var(--adm-border);
            font-size: 13px;
            color: var(--adm-text);
            white-space: nowrap;
            min-width: 0;
        }
        .adm-role-chip > span:first-child {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            min-width: 0;
        }
        .adm-role-chip > span:last-child {
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
            margin-left: 8px;
            flex-shrink: 0;
        }

        /* ================================================================
           SECTION 3 — METRICS GRID + HEALTH CARD
           ================================================================ */
        .adm-metrics-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .adm-metric-card {
            background: var(--adm-card);
            border: 1px solid var(--adm-border);
            border-radius: var(--adm-radius);
            box-shadow: var(--adm-shadow);
            padding: 24px 20px;
            position: relative;
            overflow: hidden;
            transition: transform .18s, box-shadow .18s;
        }
        .adm-metric-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--adm-shadow-hover);
        }
        .adm-metric-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 4px;
            border-radius: var(--adm-radius) var(--adm-radius) 0 0;
        }
        .adm-metric-card:nth-child(1)::before { background: var(--adm-red); }
        .adm-metric-card:nth-child(2)::before { background: var(--nu-gold, #ffc72c); }
        .adm-metric-card:nth-child(3)::before { background: #8b5cf6; }
        .adm-metric-card:nth-child(4)::before { background: #0ea5e9; }

        .adm-metric-icon {
            width: 40px; height: 40px;
            border-radius: 10px;
            display: grid; place-items: center;
            font-size: 18px;
            margin-bottom: 14px;
        }
        .adm-metric-card:nth-child(1) .adm-metric-icon { background: #fef2f2; color: var(--adm-red); }
        .adm-metric-card:nth-child(2) .adm-metric-icon { background: #fefce8; color: #a16207; }
        .adm-metric-card:nth-child(3) .adm-metric-icon { background: #f5f3ff; color: #7c3aed; }
        .adm-metric-card:nth-child(4) .adm-metric-icon { background: #f0f9ff; color: #0284c7; }

        .adm-metric-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--adm-text-secondary);
            margin-bottom: 6px;
        }
        .adm-metric-value {
            font-family: 'Poppins', sans-serif;
            font-size: 28px;
            font-weight: 800;
            color: var(--adm-text);
        }

        /* Health Card */
        .adm-health-card {
            background: var(--adm-card);
            border: 1px solid var(--adm-border);
            border-radius: var(--adm-radius);
            box-shadow: var(--adm-shadow);
            padding: 24px 28px;
            transition: transform .18s, box-shadow .18s;
        }
        .adm-health-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--adm-shadow-hover);
        }

        .adm-health-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }
        .adm-health-header h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            font-weight: 700;
            color: var(--adm-text);
        }

        .adm-health-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 14px;
            border-radius: var(--adm-radius-pill);
            font-size: 12px;
            font-weight: 700;
        }
        .adm-health-badge.healthy { background: #d1fae5; color: #065f46; }
        .adm-health-badge.warning { background: #fef3c7; color: #92400e; }
        .adm-health-badge.degraded { background: #fee2e2; color: #991b1b; }

        .adm-health-badge i { font-size: 13px; }

        .adm-health-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--adm-border);
        }
        .adm-health-row:last-child { border-bottom: none; }
        .adm-health-row-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--adm-text-secondary);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .adm-health-row-label i { font-size: 15px; opacity: .6; }
        .adm-health-row-value {
            font-size: 13px;
            font-weight: 700;
            color: var(--adm-text);
        }
        .adm-db-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            display: inline-block;
        }
        .adm-db-dot.connected { background: #16a34a; }
        .adm-db-dot.error { background: #dc2626; }

        /* ================================================================
           SECTION 4 — QUICK ACTIONS PANEL
           ================================================================ */
        .adm-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 14px;
        }

        .adm-action-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            padding: 24px 16px;
            background: var(--adm-card);
            border: 1px solid var(--adm-border);
            border-radius: var(--adm-radius);
            box-shadow: var(--adm-shadow);
            text-decoration: none;
            color: var(--adm-text);
            transition: transform .18s, box-shadow .18s, border-color .18s;
        }
        .adm-action-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--adm-shadow-hover);
            border-color: var(--adm-red);
        }

        .adm-action-icon {
            width: 48px; height: 48px;
            border-radius: 14px;
            display: grid; place-items: center;
            font-size: 22px;
            background: #fef2f2;
            color: var(--adm-red);
            transition: background .2s, color .2s;
        }
        .adm-action-card:hover .adm-action-icon {
            background: var(--adm-red);
            color: #fff;
        }

        .adm-action-label {
            font-size: 13px;
            font-weight: 700;
            text-align: center;
        }
        .adm-action-sub {
            font-size: 11px;
            color: var(--adm-text-secondary);
            text-align: center;
            margin-top: -4px;
        }

        /* ================================================================
           RESPONSIVE
           ================================================================ */
        @media (max-width: 1200px) {
            .adm-metrics-grid { grid-template-columns: repeat(2, 1fr); }
            .adm-actions-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 960px) {
            .adm-profile-card { flex-direction: column; text-align: center; gap: 16px; }
            .adm-profile-name-row { justify-content: center; }
            .adm-profile-meta { justify-content: center; }
            .adm-profile-footnote { text-align: center; }
        }
        @media (max-width: 680px) {
            .adm-metrics-grid { grid-template-columns: 1fr; }
            .adm-actions-grid { grid-template-columns: 1fr 1fr; }
            .adm-status-row { grid-template-columns: 1fr; }
            .adm-role-grid { grid-template-columns: 1fr 1fr; }
            .adm-users-card, .adm-health-card, .adm-profile-card { padding: 20px; }
        }
        @media (max-width: 480px) {
            .adm-actions-grid { grid-template-columns: 1fr; }
        }

        /* Loading skeleton */
        .adm-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: admShimmer 1.5s infinite;
            border-radius: 8px;
        }
        @keyframes admShimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
    </style>
</head>
<body>
<div class="app-shell">

    <?php
    $sidebarPath = __DIR__ . '/../../includes/sidebar_admin.php';
    if (file_exists($sidebarPath)) {
        require_once $sidebarPath;
    }
    ?>

    <main class="main-content">
        <header class="page-header">
            <div>
                <p class="breadcrumb">Home / Dashboard</p>
                <h2>Dashboard</h2>
                <p class="page-description">
                    Welcome back, <?php echo htmlspecialchars($patientName); ?>.
                    Here's your system overview.
                </p>
            </div>
            <div class="header-actions">
                <a href="../../auth/logout.php" class="header-button outline">Logout</a>
            </div>
        </header>

        <div class="adm-wrap">

            <!-- ============================================================
                 SECTION 1 — ADMIN PROFILE CARD
                 ============================================================ -->
            <p class="adm-section-title"><i class="bi bi-person-circle"></i> &nbsp;Admin Profile</p>
            <div class="adm-profile-card" id="admProfileCard">
                <div class="adm-avatar" id="admAvatar">—</div>
                <div class="adm-profile-info">
                    <div class="adm-profile-name-row">
                        <span class="adm-profile-name" id="admProfileName">Loading…</span>
                        <span class="adm-badge active" id="admBadgeActive"><i class="bi bi-check-circle-fill" style="font-size:11px;margin-right:3px"></i>Active</span>
                    </div>
                    <div class="adm-profile-meta">
                        <span><i class="bi bi-shield-lock"></i> <span id="admProfileRole">—</span></span>
                        <span><i class="bi bi-hash"></i> <span id="admProfileSchoolID">—</span></span>
                        <span><i class="bi bi-envelope"></i> <span id="admProfileEmail">—</span></span>
                    </div>
                    <p class="adm-profile-footnote" id="admProfileFootnote">Last login: —</p>
                </div>
            </div>

            <!-- ============================================================
                 SECTION 2 — ACTIVE USERS & STATUS
                 ============================================================ -->
            <p class="adm-section-title"><i class="bi bi-people-fill"></i> &nbsp;User Activity Monitor</p>
            <div class="adm-users-card">
                <div class="adm-users-header">
                    <h3><span class="adm-pulse-dot"></span> &nbsp;Active Users & Status</h3>
                    <span style="font-size:12px;color:var(--adm-text-secondary)">Auto-refreshes every 30s</span>
                </div>

                <div class="adm-status-row">
                    <div class="adm-status-item">
                        <span class="adm-status-dot green"></span>
                        <div class="adm-status-count" id="admOnlineNow">0</div>
                        <div class="adm-status-label">Online Now</div>
                    </div>
                    <div class="adm-status-item">
                        <span class="adm-status-dot yellow"></span>
                        <div class="adm-status-count" id="admIdle">0</div>
                        <div class="adm-status-label">Idle</div>
                    </div>
                    <div class="adm-status-item">
                        <span class="adm-status-dot red"></span>
                        <div class="adm-status-count" id="admOfflineToday">0</div>
                        <div class="adm-status-label">Offline Today</div>
                    </div>
                </div>

                <div class="adm-role-breakdown-title">Role Distribution</div>
                <div class="adm-role-grid" id="admRoleGrid">
                    <!-- Populated by JS -->
                </div>
            </div>

            <!-- ============================================================
                 SECTION 3 — SYSTEM OVERVIEW & HEALTH CARDS
                 ============================================================ -->
            <p class="adm-section-title"><i class="bi bi-grid-1x2-fill"></i> &nbsp;System Overview</p>
            <div class="adm-metrics-grid">
                <article class="adm-metric-card">
                    <div class="adm-metric-icon"><i class="bi bi-people"></i></div>
                    <div class="adm-metric-label">Total Users</div>
                    <div class="adm-metric-value" id="admTotalUsers">—</div>
                </article>
                <article class="adm-metric-card">
                    <div class="adm-metric-icon"><i class="bi bi-calendar-check"></i></div>
                    <div class="adm-metric-label">Appointments Today</div>
                    <div class="adm-metric-value" id="admAppointments">—</div>
                </article>
                <article class="adm-metric-card">
                    <div class="adm-metric-icon"><i class="bi bi-chat-dots"></i></div>
                    <div class="adm-metric-label">Pending Consultations</div>
                    <div class="adm-metric-value" id="admConsultations">—</div>
                </article>
            </div>

            <!-- Health Card -->
            <p class="adm-section-title"><i class="bi bi-heart-pulse"></i> &nbsp;System Health</p>
            <div class="adm-health-card">
                <div class="adm-health-header">
                    <h3>Infrastructure Status</h3>
                    <span class="adm-health-badge healthy" id="admHealthBadge">
                        <i class="bi bi-check-circle-fill"></i>
                        <span id="admHealthBadgeText">Healthy</span>
                    </span>
                </div>
                <div class="adm-health-row">
                    <span class="adm-health-row-label"><i class="bi bi-database"></i> Database Status</span>
                    <span class="adm-health-row-value" id="admDbStatus">
                        <span class="adm-db-dot connected"></span>&nbsp; Connected
                    </span>
                </div>
                <div class="adm-health-row">
                    <span class="adm-health-row-label"><i class="bi bi-exclamation-triangle"></i> Errors Today</span>
                    <span class="adm-health-row-value" id="admErrorCount">0</span>
                </div>
                <div class="adm-health-row">
                    <span class="adm-health-row-label"><i class="bi bi-activity"></i> Today's Visits</span>
                    <span class="adm-health-row-value" id="admTodayVisits">0</span>
                </div>
            </div>

            <!-- ============================================================
                 SECTION 4 — QUICK ACTIONS PANEL
                 ============================================================ -->
            <p class="adm-section-title"><i class="bi bi-lightning-charge-fill"></i> &nbsp;Quick Actions</p>
            <div class="adm-actions-grid">
                <a href="/NUcare_Health_system/admin/user_management.php" class="adm-action-card">
                    <div class="adm-action-icon"><i class="bi bi-people-fill"></i></div>
                    <div class="adm-action-label">View Users</div>
                    <div class="adm-action-sub">Manage accounts</div>
                </a>

                <?php if ($isSuperAdmin): ?>
                <a href="/NUcare_Health_system/admin/rbac_management.php" class="adm-action-card">
                    <div class="adm-action-icon"><i class="bi bi-shield-lock-fill"></i></div>
                    <div class="adm-action-label">RBAC Settings</div>
                    <div class="adm-action-sub">Roles & permissions</div>
                </a>
                <?php endif; ?>

                <a href="/NUcare_Health_system/admin/audit_logs.php" class="adm-action-card">
                    <div class="adm-action-icon"><i class="bi bi-journal-text"></i></div>
                    <div class="adm-action-label">Audit Logs</div>
                    <div class="adm-action-sub">Activity history</div>
                </a>

                <a href="/NUcare_Health_system/admin/reports.php" class="adm-action-card">
                    <div class="adm-action-icon"><i class="bi bi-bar-chart-line"></i></div>
                    <div class="adm-action-label">Reports</div>
                    <div class="adm-action-sub">View analytics</div>
                </a>
            </div>

        </div><!-- /.adm-wrap -->
    </main>

</div><!-- /.app-shell -->

<script src="../../assets/js/app.js"></script>
<script>
(function () {
    'use strict';

    const AJAX_URL = '/NUcare_Health_system/ajax/dashboard_stats.ajax.php';
    const POLL_INTERVAL = 30000; // 30 seconds

    /* ---- Helpers ---- */
    function $(id) { return document.getElementById(id); }
    function fmt(n) { return Number(n).toLocaleString(); }

    function timeAgo(dateStr) {
        if (!dateStr) return 'Never';
        var d = new Date(dateStr.replace(' ', 'T') + (dateStr.includes('Z') || dateStr.includes('+') ? '' : 'Z'));
        var now = new Date();
        var diff = Math.floor((now - d) / 1000);
        if (diff < 60)   return 'Just now';
        if (diff < 3600) return Math.floor(diff / 60) + ' min ago';
        if (diff < 86400) return Math.floor(diff / 3600) + ' hr ago';
        return Math.floor(diff / 86400) + ' days ago';
    }

    function formatDateTime(dateStr) {
        if (!dateStr) return 'Never';
        var d = new Date(dateStr.replace(' ', 'T') + (dateStr.includes('Z') || dateStr.includes('+') ? '' : 'Z'));
        var opts = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        return d.toLocaleDateString('en-US', opts);
    }

    /* ---- Role icon map ---- */
    var roleIcons = {
        'Student':       '<i class="bi bi-mortarboard"></i>',
        'Faculty':       '<i class="bi bi-easel"></i>',
        'Staff':         '<i class="bi bi-person-gear"></i>',
        'Doctor':        '<i class="bi bi-heart-pulse"></i>',
        'Dentist':       '<i class="bi bi-emoji-smile"></i>',
        'Nurse':         '<i class="bi bi-bandaid"></i>',
        'Admin':         '<i class="bi bi-shield-fill"></i>',
        'Super Admin':   '<i class="bi bi-shield-shaded"></i>'
    };

    /* ---- Fetch & render ---- */
    function loadDashboard() {
        fetch(AJAX_URL)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok) return;
                renderProfile(data);
                renderActivity(data);
                renderMetrics(data);
                renderHealth(data);
            })
            .catch(function () {
                // Silently retry on next poll
            });
    }

    function renderProfile(data) {
        var p = data.profile;
        if (!p) return;

        var fullName = p.FirstName + (p.MiddleName ? ' ' + p.MiddleName.charAt(0) + '.' : '') + ' ' + p.LastName;
        var initials = p.FirstName.charAt(0) + p.LastName.charAt(0);

        $('admAvatar').textContent = initials;
        $('admProfileName').textContent = fullName;
        $('admProfileSchoolID').textContent = p.SchoolID || '—';
        $('admProfileEmail').textContent = p.Email || '—';

        // Roles display
        var roles = data.roles || [];
        var primaryRole = roles[0] || 'Admin';
        var roleBadgeClass = data.isSuperAdmin ? 'superadmin' : 'role';
        $('admProfileRole').innerHTML = '<span class="adm-badge ' + roleBadgeClass + '">' + primaryRole + '</span>';
        if (roles.length > 1) {
            $('admProfileRole').innerHTML += ' <span style="font-size:11px;color:var(--adm-text-secondary)">+' + (roles.length - 1) + ' more</span>';
        }

        // Last login
        $('admProfileFootnote').textContent = 'Last login: ' + formatDateTime(p.LastLogin);
    }

    function renderActivity(data) {
        var s = data.stats;
        $('admOnlineNow').textContent  = fmt(s.onlineNow);
        $('admIdle').textContent       = fmt(s.idle);
        $('admOfflineToday').textContent = fmt(s.offlineToday);

        // Role breakdown
        var rb = data.roleBreakdown || {};
        var grid = $('admRoleGrid');
        var html = '';
        var roleOrder = ['Student', 'Faculty', 'Staff', 'Doctor', 'Dentist', 'Nurse', 'Admin', 'Super Admin'];
        roleOrder.forEach(function (role) {
            if (rb[role] !== undefined) {
                var icon = roleIcons[role] || '<i class="bi bi-person"></i>';
                html += '<div class="adm-role-chip"><span>' + icon + '&nbsp; ' + role + '</span><span>' + fmt(rb[role]) + '</span></div>';
            }
        });
        grid.innerHTML = html;
    }

    function renderMetrics(data) {
        var s = data.stats;
        $('admTotalUsers').textContent      = fmt(s.totalUsers);
        $('admAppointments').textContent    = fmt(s.todayAppointments);
        $('admConsultations').textContent   = fmt(s.pendingConsultations);
        $('admTodayVisits').textContent     = fmt(s.todayVisits);
    }

    function renderHealth(data) {
        var h = data.health;

        // DB Status
        var dotClass = h.dbStatus === 'Connected' ? 'connected' : 'error';
        $('admDbStatus').innerHTML = '<span class="adm-db-dot ' + dotClass + '"></span>&nbsp; ' + h.dbStatus;

        // Error count
        $('admErrorCount').textContent = fmt(h.errorCountToday);

        // Overall badge
        var badge = $('admHealthBadge');
        var badgeText = $('admHealthBadgeText');
        badge.className = 'adm-health-badge';
        if (h.overallStatus === 'Healthy') {
            badge.classList.add('healthy');
            badge.innerHTML = '<i class="bi bi-check-circle-fill"></i> <span id="admHealthBadgeText">Healthy</span>';
        } else if (h.overallStatus === 'Warning') {
            badge.classList.add('warning');
            badge.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i> <span id="admHealthBadgeText">Warning</span>';
        } else {
            badge.classList.add('degraded');
            badge.innerHTML = '<i class="bi bi-x-circle-fill"></i> <span id="admHealthBadgeText">Degraded</span>';
        }
    }

    /* ---- Initial load + polling ---- */
    loadDashboard();
    setInterval(loadDashboard, POLL_INTERVAL);
})();
</script>
</body>
</html>