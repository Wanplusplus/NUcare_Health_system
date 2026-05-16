<?php
session_start();

if (!isset($_SESSION['patient_id'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$patientName = $_SESSION['patient_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE | Medicine</title>
    <link rel="icon" href="/NUcare_Health_system/assets/image/nucarelogo.png">
    <link rel="stylesheet" href="../../assets/css/app.css?v=1">

    <!-- Medicine Styles -->
    <link rel="stylesheet" href="../../assets/css/medicine.css?v=1">

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<div class="app-shell">

    <!-- Sidebar -->
    <?php
    $sidebarPath = __DIR__ . '/../../includes/sidebar.php';

    if (file_exists($sidebarPath)) {
        require_once $sidebarPath;
    }
    ?>

    <!-- Main Content -->
    <main class="main-content">

        <div class="med-page">

            <!-- ══════════════════════════════════════
                 TOP BAR — Year selector + Month pills
                 ══════════════════════════════════════ -->
            <div class="med-topbar">

                <div class="year-selector" id="yearSelector">
                    <span class="year-label" id="yearLabel">YEAR</span>

                    <button class="year-arrow" id="btnYearPrev" title="Previous year">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>

                    <button class="year-arrow" id="btnYearNext" title="Next year">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>

                <!-- Month Tabs -->
                <div class="month-tabs" id="monthTabs">
                    <button class="month-tab" data-month="1">JAN</button>
                    <button class="month-tab" data-month="2">FEB</button>
                    <button class="month-tab" data-month="3">MAR</button>
                    <button class="month-tab" data-month="4">APR</button>
                    <button class="month-tab" data-month="5">MAY</button>
                    <button class="month-tab" data-month="6">JUN</button>
                    <button class="month-tab" data-month="7">JUL</button>
                    <button class="month-tab" data-month="8">AUG</button>
                    <button class="month-tab" data-month="9">SEPT</button>
                    <button class="month-tab" data-month="10">OCT</button>
                    <button class="month-tab" data-month="11">NOV</button>
                    <button class="month-tab" data-month="12">DEC</button>
                </div>

            </div>

            <!-- ══════════════════════════════════════
                 TITLE ROW + ACTIONS
                 ══════════════════════════════════════ -->
            <div class="med-title-row">

                <h2>
                    <i class="fa-solid fa-pills"></i>
                    Medicine Inventory
                </h2>

                <div class="med-title-actions">

                    <button class="btn btn-accent" id="btnAddMedicine">
                        <i class="fa-solid fa-plus"></i>
                        Add Medicine
                    </button>

                    <button class="btn btn-navy" id="btnExport">
                        <i class="fa-solid fa-file-export"></i>
                        Export
                    </button>

                </div>

            </div>

            <!-- ══════════════════════════════════════
                 ALERTS
                 ══════════════════════════════════════ -->
            <div class="med-alerts" id="medAlerts" style="display: none;">

                <span class="alert-chip low"
                      id="alertLowStock"
                      style="display: none;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span id="alertLowStockText"></span>
                </span>

                <span class="alert-chip exp"
                      id="alertExpired"
                      style="display: none;">
                    <i class="fa-solid fa-ban"></i>
                    <span id="alertExpiredText"></span>
                </span>

                <span class="alert-chip near"
                      id="alertNearExpiry"
                      style="display: none;">
                    <i class="fa-solid fa-clock"></i>
                    <span id="alertNearExpiryText"></span>
                </span>

            </div>

            <!-- ══════════════════════════════════════
                 MAIN BODY
                 ══════════════════════════════════════ -->
            <div class="med-body">

                <!-- TABLE SECTION -->
                <div class="med-table-section">

                    <!-- Toolbar -->
                    <div class="table-toolbar">

                        <div class="search-wrap">
                            <i class="fa-solid fa-magnifying-glass"></i>

                            <input
                                type="text"
                                id="searchInput"
                                placeholder="Search particulars / items / supplies…"
                            >
                        </div>

                        <select class="toolbar-select" id="statusFilter">
                            <option value="">All Status</option>
                            <option value="available">Available</option>
                            <option value="low">Low Stock</option>
                            <option value="expired">Expired</option>
                            <option value="near">Near Expiry</option>
                        </select>

                    </div>

                    <!-- Table -->
                    <table class="med-table" id="medTable">

                        <thead>
                            <tr>
                                <th>Particulars / Items / Supplies</th>
                                <th>Quantity</th>
                                <th>Unit</th>
                                <th>Total</th>
                                <th>Qty of Purchase / Delivery</th>
                                <th>Qty of Ending Balance</th>
                                <th>Expiration Date</th>
                                <th>Availability</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody id="medTableBody">
                            <!-- JS populates -->
                        </tbody>

                    </table>

                    <!-- Empty State -->
                    <div class="med-empty"
                         id="medEmpty"
                         style="display: none;">

                        <div class="empty-icon">
                            <i class="fa-solid fa-box-open"></i>
                        </div>

                        <p>No medicines found for the selected period.</p>

                    </div>

                </div>

                <!-- SUMMARY -->
                <div class="med-summary">

                    <div class="summary-head">
                        Summary Report
                    </div>

                    <div class="summary-body">

                        <div class="s-stat">
                            <span class="s-label">Total Items</span>
                            <span class="s-val" id="sumTotal">—</span>
                        </div>

                        <div class="s-divider"></div>

                        <div class="s-stat">
                            <span class="s-label">Available</span>
                            <span class="s-val ok" id="sumAvailable">—</span>
                        </div>

                        <div class="s-stat">
                            <span class="s-label">Low Stock</span>
                            <span class="s-val warn" id="sumLow">—</span>
                        </div>

                        <div class="s-stat">
                            <span class="s-label">Expired</span>
                            <span class="s-val bad" id="sumExpired">—</span>
                        </div>

                        <div class="s-stat">
                            <span class="s-label">Near Expiry</span>
                            <span class="s-val warn" id="sumNear">—</span>
                        </div>

                        <div class="s-divider"></div>

                        <div class="s-stat">
                            <span class="s-label">Total Qty Purchased</span>
                            <span class="s-val" id="sumPurchased">—</span>
                        </div>

                        <div class="s-stat">
                            <span class="s-label">Total Ending Balance</span>
                            <span class="s-val" id="sumEndingBalance">—</span>
                        </div>

                    </div>

                    <div class="summary-note" id="summaryPeriodLabel">
                        — —
                    </div>

                </div>

            </div>

        </div>

    </main>

</div>

<!-- Main App JS -->
<script src="../../assets/js/app.js"></script>
<script src="../../assets/js/medicine.js"></script>

</body>
</html>