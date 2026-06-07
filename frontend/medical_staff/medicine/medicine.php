<?php
session_start();

if (!isset($_SESSION['patient_id']) && !isset($_SESSION['UserID'])) {
 header('Location: /NUcare_Health_system/frontend/auth/login.php');
 exit;
}

$patientName = $_SESSION['patient_name'] ?? 'User';

require_once __DIR__ . '/../../../backend/includes/module_guard.php';
requireModule('Medicine', 'access');
$activeSidebarItem = 'medicine';

?>
<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>NUCARE | Medicine</title>
 <link rel="icon" href="/NUcare_Health_system/assets/image/nucarelogo.png">
 <link rel="stylesheet" href="/NUcare_Health_system/assets/css/app.css?v=1">

 <!-- Medicine Styles -->
 <link rel="stylesheet" href="/NUcare_Health_system/assets/css/medicine.css?v=2">

 <!-- Font Awesome -->
 <link rel="stylesheet"
 href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<div class="app-shell">

 <!-- Sidebar -->
 <?php
 $sidebarPath = __DIR__ . '/../../../backend/includes/sidebar_medical_staff.php';



 if (file_exists($sidebarPath)) {
 require_once $sidebarPath;
 }
 ?>

 <!-- Main Content -->
 <main class="main-content">

 <div class="med-page">

 <!-- ---
 TOP BAR - Year selector + Month pills
 --- -->
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

 <button class="all-stock-btn" id="btnAllStocks" type="button">
 <i class="fa-solid fa-boxes-stacked"></i>
 All Stocks
 </button>

 </div>

 <!-- ---
 TITLE ROW + ACTIONS
 --- -->
 <div class="med-title-row">

 <h2>
 <i class="fa-solid fa-pills"></i>
 Medicine

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

 <!-- ---
 ALERTS
 --- -->
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

 <!-- ---
 MAIN BODY
 --- -->
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
 placeholder="Search particulars / items / supplies..."
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
 <span class="s-val" id="sumTotal">-</span>
 </div>

 <div class="s-divider"></div>

 <div class="s-stat">
 <span class="s-label">Available</span>
 <span class="s-val ok" id="sumAvailable">-</span>
 </div>

 <div class="s-stat">
 <span class="s-label">Low Stock</span>
 <span class="s-val warn" id="sumLow">-</span>
 </div>

 <div class="s-stat">
 <span class="s-label">Expired</span>
 <span class="s-val bad" id="sumExpired">-</span>
 </div>

 <div class="s-stat">
 <span class="s-label">Near Expiry</span>
 <span class="s-val warn" id="sumNear">-</span>
 </div>

 <div class="s-divider"></div>

 <div class="s-stat">
 <span class="s-label">Total Qty Purchased</span>
 <span class="s-val" id="sumPurchased">-</span>
 </div>

 <div class="s-stat">
 <span class="s-label">Total Ending Balance</span>
 <span class="s-val" id="sumEndingBalance">-</span>
 </div>

 </div>

 <div class="summary-note" id="summaryPeriodLabel">
 - -
 </div>

 </div>

 </div>

 </div>

 </main>

</div>

<!-- ---
 ADD MEDICINE MODAL
 --- -->

<!-- ADD MEDICINE MODAL -->
<div class="modal-overlay" id="addMedicineModal">
 <div class="modal-box">

 <!-- Modal Header -->
 <div class="modal-header">
 <div class="modal-header-left">
 <i class="fa-solid fa-pills"></i>
 <div>
 <h3 class="modal-title">Add Medicine</h3>
 <p class="modal-subtitle">Step through each section to complete the entry</p>
 </div>
 </div>
 <button class="modal-close" data-close-modal type="button">
 <i class="fa-solid fa-xmark"></i>
 </button>
 </div>

 <!-- Modal Tabs -->
 <div class="modal-tabs">
 <button class="modal-tab active" data-tab="master" type="button">
 <i class="fa-solid fa-book-medical"></i>
 <span>1. Medicine Details</span>
 </button>
 <button class="modal-tab" data-tab="inventory" type="button">
 <i class="fa-solid fa-boxes-stacking"></i>
 <span>2. Inventory / Batch</span>
 </button>
 </div>

 <!-- Form -->
 <form id="addMedicineForm" novalidate>
 <input type="hidden" id="medicine_id" name="medicine_id" value="">
 <input type="hidden" id="inventory_id" name="inventory_id" value="">
 <input type="hidden" id="form_action" name="action" value="store">

 <div class="modal-body">

 <!-- -- TAB 1: Medicine Master -- -->
 <div class="tab-panel active" id="tab-master">

 <div class="form-notice info">
 <i class="fa-solid fa-circle-info"></i>
 <span>This registers the medicine in the <strong>master list</strong>. It does <em>not</em> add stock - proceed to the next tab for inventory.</span>
 </div>

 <div class="form-grid">

 <div class="form-group">
 <label for="medicine_name">Medicine / Brand Name <span class="req">*</span></label>
 <input type="text" id="medicine_name" name="medicine_name" placeholder="e.g. Biogesic, Amoxicillin">
 <span class="form-err" id="err_medicine_name"></span>
 </div>

 <div class="form-group">
 <label for="generic_name">Generic Name</label>
 <input type="text" id="generic_name" name="generic_name" placeholder="e.g. Paracetamol">
 </div>

 <div class="form-group">
 <label for="category">Category <span class="req">*</span></label>
 <select id="category" name="category">
 <option value="">Select category...</option>
 <option>Analgesic / Pain Reliever</option>
 <option>Antibiotic</option>
 <option>Antihistamine</option>
 <option>Antiviral</option>
 <option>Vitamin / Supplement</option>
 <option>Antiseptic / Disinfectant</option>
 <option>First Aid Supply</option>
 <option>Dental Supply</option>
 <option>Other</option>
 </select>
 <span class="form-err" id="err_category"></span>
 </div>

 <div class="form-group">
 <label for="dosage">Dosage / Strength</label>
 <input type="text" id="dosage" name="dosage" placeholder="e.g. 500 mg, 250 mg/5 mL">
 </div>

 <div class="form-group">
 <label for="unit">Unit Type <span class="req">*</span></label>
 <select id="unit" name="unit">
 <option value="">Select unit...</option>
 <option>Tablet</option>
 <option>Capsule</option>
 <option>Bottle</option>
 <option>Sachet</option>
 <option>Ampule</option>
 <option>Vial</option>
 <option>Piece</option>
 <option>Pack</option>
 <option>Roll</option>
 <option>Box</option>
 </select>
 <span class="form-err" id="err_unit"></span>
 </div>

 <div class="form-group form-group--full">
 <label for="med_description">Description / Notes</label>
 <textarea id="med_description" name="description" rows="2" placeholder="Optional notes about this medicine..."></textarea>
 </div>

 </div>

 <div class="form-access-note">
 <i class="fa-solid fa-shield-halved"></i>
 Can manage: <strong>Admin * Doctor * Dentist * Nurse</strong>
 </div>

 </div>

 <!-- -- TAB 2: Inventory / Batch -- -->
 <div class="tab-panel" id="tab-inventory">

 <div class="form-notice info">
 <i class="fa-solid fa-circle-info"></i>
 <span>Record the <strong>stock-in batch</strong>. One medicine can have multiple batches with different expiry dates.</span>
 </div>

 <div class="form-grid">

 <div class="form-group">
 <label for="batch_code">Batch / Lot Code</label>
 <input type="text" id="batch_code" name="batch_code" placeholder="e.g. A1, LOT-2025-08">
 </div>

 <div class="form-group">
 <label for="quantity">Quantity Received <span class="req">*</span></label>
 <input type="number" id="quantity" name="quantity" min="0" placeholder="0">
 <span class="form-err" id="err_quantity"></span>
 </div>

 <div class="form-group">
 <label for="purchase_quantity">Qty of Purchase / Delivery</label>
 <input type="number" id="purchase_quantity" name="purchase_quantity" min="0" placeholder="0">
 </div>

 <div class="form-group">
 <label for="unit_cost">Unit Cost ()</label>
 <input type="number" id="unit_cost" name="unit_cost" min="0" step="0.01" placeholder="0.00">
 </div>

 <div class="form-group">
 <label for="total_cost">Total Cost ()</label>
 <input type="text" id="total_cost" name="total_cost" readonly tabindex="-1" placeholder="Auto-calculated">
 </div>

 <div class="form-group">
 <label for="ending_balance">Ending Balance</label>
 <input type="text" id="ending_balance" name="ending_balance" readonly tabindex="-1" placeholder="Mirrors quantity">
 </div>

 <div class="form-group">
 <label for="expiration_date">Expiration Date <span class="req">*</span></label>
 <input type="date" id="expiration_date" name="expiration_date">
 <span class="form-err" id="err_expiration_date"></span>
 </div>

 <div class="form-group">
 <label for="supplier">Supplier / Source</label>
 <input type="text" id="supplier" name="supplier" placeholder="e.g. PhilHealth Supply, DOH">
 </div>

 <div class="form-group form-group--full">
 <label>Stock Status Preview</label>
 <div class="status-preview" id="statusPreview">
 <span class="s-pill ok"><i class="fa-solid fa-circle-check"></i> Fill in quantity &amp; expiry to preview</span>
 </div>
 </div>

 </div>

 <div class="form-audit-note">
 <i class="fa-solid fa-clock-rotate-left"></i>
 <span>Saving will automatically log a <strong>Stock In (+)</strong> entry in the inventory audit trail.</span>
 </div>

 <div class="form-access-note">
 <i class="fa-solid fa-shield-halved"></i>
 Primarily managed by: <strong>Nurse * Doctor * Dentist * Admin</strong>
 </div>

 </div>

 </div><!-- /.modal-body -->

 <!-- Modal Footer -->
 <div class="modal-footer">

 <button type="button" class="btn btn-outline" data-close-modal>
 Cancel
 </button>

 <div class="modal-footer-right">

 <button type="button" class="btn btn-outline" id="btnTabPrev" style="display:none;">
 <i class="fa-solid fa-arrow-left"></i> Back
 </button>

 <button type="button" class="btn btn-accent" id="btnTabNext">
 Next <i class="fa-solid fa-arrow-right"></i>
 </button>

 <button type="submit" class="btn btn-navy" id="saveMedicineBtn" style="display:none;">
 <i class="fa-solid fa-floppy-disk"></i>
 Save Medicine
 </button>

 </div>

 </div>

 </form>

 </div>
</div>

<!-- Toast Container -->
<div id="toastWrap" class="toast-wrap"></div>

<!-- Main App JS -->
<script src="/NUcare_Health_system/assets/js/app.js"></script>
<script src="/NUcare_Health_system/assets/js/medicine.js?v=4"></script>

</body>
</html>




