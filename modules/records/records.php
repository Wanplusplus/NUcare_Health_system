<?php
session_start();

if (!isset($_SESSION['patient_id']) && !isset($_SESSION['UserID'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$patientName = $_SESSION['patient_name'] ?? 'User';

require_once __DIR__ . '/../../includes/module_guard.php';
requireModule('Records', 'access');

$activeSidebarItem = 'records';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE | Patient Records</title>
    <link rel="icon" href="/NUcare_Health_system/assets/image/nucarelogo.png">
    <link rel="stylesheet" href="../../assets/css/app.css?v=1">
    <link rel="stylesheet" href="../../assets/css/records.css?v=2">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="app-shell">

    <?php
    $sidebarPath = __DIR__ . '/../../includes/sidebar_medical_staff.php';
    if (file_exists($sidebarPath)) require_once $sidebarPath;
    ?>

    <main class="main-content">
        <div class="records-page">

            <div class="records-page-header">
                <div class="page-header-left">
                    <nav class="breadcrumb">
                        <span>Home</span>
                        <i class="fa-solid fa-chevron-right"></i>
                        <span class="bc-active">Records</span>
                    </nav>
                    <h1 class="page-title">Patient Records</h1>
                    <p class="page-desc">Long-term medical records for all registered patients — view clinic history, vitals, medicines dispensed, emergencies, and certificates.</p>
                </div>
                <div class="page-header-right">
                    <a href="../../auth/logout.php" class="btn-outline">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        Logout
                    </a>
                </div>
            </div>

            <div class="records-stats">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fa-solid fa-users"></i></div>
                    <div class="stat-info">
                        <div class="stat-val" id="statTotal">—</div>
                        <div class="stat-label">Total Patients</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fa-solid fa-graduation-cap"></i></div>
                    <div class="stat-info">
                        <div class="stat-val" id="statStudents">—</div>
                        <div class="stat-label">Students</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon yellow"><i class="fa-solid fa-chalkboard-teacher"></i></div>
                    <div class="stat-info">
                        <div class="stat-val" id="statFaculty">—</div>
                        <div class="stat-label">Faculty</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fa-solid fa-id-badge"></i></div>
                    <div class="stat-info">
                        <div class="stat-val" id="statStaff">—</div>
                        <div class="stat-label">Staff</div>
                    </div>
                </div>
            </div>

            <div class="records-card">
                <div class="card-section-label">
                    <i class="fa-solid fa-folder-open"></i>
                    All Patient Records
                </div>
                <p class="card-section-desc">Click any row or the View button to open a full patient record.</p>

                <div class="records-filter-row">
                    <div class="search-input-wrap">
                        <i class="fa-solid fa-magnifying-glass search-icon"></i>
                        <input type="text" id="recordSearchInput" placeholder="Search by ID, name, or program…">
                    </div>

                    <select class="filter-select" id="filterType">
                        <option value="">All Types</option>
                        <option value="Student">Student</option>
                        <option value="Faculty">Faculty</option>
                        <option value="Staff">Staff</option>
                    </select>

                    <select class="filter-select" id="filterStatus">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>

                    <select class="filter-select" id="filterSort">
                        <option value="name_asc">Name A–Z</option>
                        <option value="name_desc">Name Z–A</option>
                        <option value="visits_desc">Most Visits</option>
                        <option value="recent">Recent Visit</option>
                    </select>
                </div>

                <div class="records-table-wrap">
                    <table class="records-table">
                        <thead>
                            <tr>
                                <th>ID Number</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Program / Dept.</th>
                                <th>Status</th>
                                <th>Visits</th>
                                <th>Last Visit</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="recordsTbody">
                        </tbody>
                    </table>
                </div>

                <div class="pagination-row">
                    <span class="pagination-info" id="paginationInfo">Loading…</span>
                    <div class="pagination-btns" id="paginationBtns"></div>
                </div>
            </div>

        </div>

        <div id="recordModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="modalPatientName">
            <div class="modal-box">

                <div class="modal-header">
                    <div class="modal-header-left">
                        <div class="modal-avatar" id="modalAvatarIcon">
                            <i class="fa-solid fa-user-nurse"></i>
                        </div>
                        <div>
                            <div class="modal-patient-name" id="modalPatientName">Loading…</div>
                            <div class="modal-patient-sub">
                                <span><i class="fa-solid fa-id-card"></i> <span id="modalPatientID">—</span></span>
                                <span><i class="fa-solid fa-user-tag"></i> <span id="modalPatientType">—</span></span>
                                <span><i class="fa-solid fa-venus-mars"></i> <span id="modalPatientSex">—</span></span>
                                <span><i class="fa-solid fa-book-open"></i> <span id="modalPatientProgram">—</span></span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-header-actions">
                        <button class="modal-print-btn" id="modalPrintBtn" title="Print record" aria-label="Print patient record">
                            <i class="fa-solid fa-print"></i>
                        </button>
                        <button class="modal-close" id="modalCloseBtn" title="Close" aria-label="Close modal">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>

                <div class="modal-tabs" role="tablist">
                    <button type="button" class="modal-tab active" data-tab="tabInfo" role="tab" aria-selected="true">
                        <i class="fa-solid fa-circle-info"></i>
                        Patient Info
                    </button>
                    <button type="button" class="modal-tab" data-tab="tabHistory" role="tab" aria-selected="false">
                        <i class="fa-solid fa-notes-medical"></i>
                        Clinic History
                        <span class="tab-count-badge" id="tabHistoryCount"></span>
                    </button>
                    <button type="button" class="modal-tab" data-tab="tabEmergency" role="tab" aria-selected="false">
                        <i class="fa-solid fa-kit-medical"></i>
                        Emergencies
                        <span class="tab-count-badge tab-count-red" id="tabEmergencyCount"></span>
                    </button>
                    <button type="button" class="modal-tab" data-tab="tabCerts" role="tab" aria-selected="false">
                        <i class="fa-solid fa-file-shield"></i>
                        Certificates
                        <span class="tab-count-badge tab-count-green" id="tabCertsCount"></span>
                    </button>
                </div>

                <div class="modal-body">

                    <div id="tabInfo" class="tab-panel active" role="tabpanel">
                        <div class="patient-info-grid">

                            <div class="info-block">
                                <div class="info-section-title">
                                    <i class="fa-solid fa-user"></i>
                                    Personal Information
                                </div>
                                <div class="info-fields">
                                    <div class="info-field">
                                        <span class="info-label">School ID</span>
                                        <span class="info-val" id="infoSchoolID">—</span>
                                    </div>
                                    <div class="info-field">
                                        <span class="info-label">Full Name</span>
                                        <span class="info-val" id="infoFullName">—</span>
                                    </div>
                                    <div class="info-field">
                                        <span class="info-label">Sex</span>
                                        <span class="info-val" id="infoSex">—</span>
                                    </div>
                                    <div class="info-field">
                                        <span class="info-label">Birthday</span>
                                        <span class="info-val" id="infoBirthday">—</span>
                                    </div>
                                    <div class="info-field">
                                        <span class="info-label">Email</span>
                                        <span class="info-val" id="infoEmail">—</span>
                                    </div>
                                    <div class="info-field">
                                        <span class="info-label">Contact Number</span>
                                        <span class="info-val" id="infoContact">—</span>
                                    </div>
                                </div>
                            </div>

                            <div class="info-block">
                                <div class="info-section-title">
                                    <i class="fa-solid fa-building-columns"></i>
                                    Academic / Employment
                                </div>
                                <div class="info-fields">
                                    <div class="info-field">
                                        <span class="info-label">Person Type</span>
                                        <span class="info-val" id="infoPersonTypes">—</span>
                                    </div>
                                    <div class="info-field">
                                        <span class="info-label">Program / Dept.</span>
                                        <span class="info-val" id="infoProgram">—</span>
                                    </div>
                                    <div class="info-field">
                                        <span class="info-label">Department</span>
                                        <span class="info-val" id="infoDepartment">—</span>
                                    </div>
                                    <div class="info-field">
                                        <span class="info-label">Year &amp; Section</span>
                                        <span class="info-val" id="infoSection">—</span>
                                    </div>
                                    <div class="info-field">
                                        <span class="info-label">Enrollment Status</span>
                                        <span class="info-val" id="infoStatus">—</span>
                                    </div>
                                    <div class="info-field">
                                        <span class="info-label">Academic Year</span>
                                        <span class="info-val" id="infoAcadYear">—</span>
                                    </div>
                                </div>
                            </div>

                            <div class="info-block info-block--full">
                                <div class="info-section-title">
                                    <i class="fa-solid fa-virus"></i>
                                    Known Medical Conditions
                                </div>
                                <div class="disease-tags" id="infoDiseases">
                                    <span class="disease-tag empty">Loading…</span>
                                </div>
                            </div>

                            <div class="info-block info-block--full info-summary-strip" id="infoSummaryStrip">
                                <div class="summary-stat">
                                    <i class="fa-solid fa-calendar-check"></i>
                                    <span class="summary-stat-val" id="summaryVisits">0</span>
                                    <span class="summary-stat-label">Total Visits</span>
                                </div>
                                <div class="summary-stat-divider"></div>
                                <div class="summary-stat">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                    <span class="summary-stat-val" id="summaryEmergencies">0</span>
                                    <span class="summary-stat-label">Emergencies</span>
                                </div>
                                <div class="summary-stat-divider"></div>
                                <div class="summary-stat">
                                    <i class="fa-solid fa-file-shield"></i>
                                    <span class="summary-stat-val" id="summaryCerts">0</span>
                                    <span class="summary-stat-label">Certificates</span>
                                </div>
                                <div class="summary-stat-divider"></div>
                                <div class="summary-stat">
                                    <i class="fa-solid fa-clock-rotate-left"></i>
                                    <span class="summary-stat-val" id="summaryLastVisit">—</span>
                                    <span class="summary-stat-label">Last Visit</span>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div id="tabHistory" class="tab-panel" role="tabpanel">
                        <div class="history-toolbar">
                            <div class="history-filters">
                                <button class="history-filter-btn active" data-filter="all" onclick="filterHistory('all', this)">
                                    <i class="fa-solid fa-list"></i> All Visits
                                </button>
                                <button class="history-filter-btn" data-filter="general" onclick="filterHistory('general', this)">
                                    <i class="fa-solid fa-stethoscope"></i> General
                                </button>
                                <button class="history-filter-btn" data-filter="dental" onclick="filterHistory('dental', this)">
                                    <i class="fa-solid fa-tooth"></i> Dental
                                </button>
                                <button class="history-filter-btn" data-filter="physical" onclick="filterHistory('physical', this)">
                                    <i class="fa-solid fa-clipboard-list"></i> Physical Exam
                                </button>
                            </div>
                            <div class="history-count-label" id="historyVisibleCount"></div>
                        </div>

                        <div class="timeline" id="clinicTimeline">
                        </div>
                    </div>

                    <div id="tabEmergency" class="tab-panel" role="tabpanel">
                        <div class="emergency-list" id="emergencyList">
                        </div>
                    </div>

                    <div id="tabCerts" class="tab-panel" role="tabpanel">
                        <div class="cert-toolbar">
                            <div class="cert-filter-row">
                                <button class="cert-filter-btn active" data-cat="all" onclick="filterCerts('all', this)">
                                    <i class="fa-solid fa-layer-group"></i> All
                                </button>
                                <button class="cert-filter-btn" data-cat="certificate" onclick="filterCerts('certificate', this)">
                                    <i class="fa-solid fa-file-shield"></i> Certificates
                                </button>
                                <button class="cert-filter-btn" data-cat="clearance" onclick="filterCerts('clearance', this)">
                                    <i class="fa-solid fa-check-circle"></i> Clearances
                                </button>
                                <button class="cert-filter-btn" data-cat="other" onclick="filterCerts('other', this)">
                                    <i class="fa-solid fa-file"></i> Other
                                </button>
                            </div>
                            <div class="cert-count-label" id="certVisibleCount"></div>
                        </div>

                        <div class="cert-list" id="certList">
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div id="transactionDetailModal" class="modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="txDetailModalTitle">
            <div class="modal-box">
                <div class="modal-header">
                    <div class="modal-header-left">
                        <div class="modal-avatar" id="txDetailAvatarIcon">
                            <i class="fa-solid fa-notes-medical"></i>
                        </div>
                        <div>
                            <div class="modal-patient-name" id="txDetailModalTitle">Transaction Details</div>
                            <div class="modal-patient-sub" id="txDetailModalSub">Read-only consultation record</div>
                        </div>
                    </div>
                    <div class="modal-header-actions">
                        <button class="modal-close" id="txDetailCloseBtn" title="Close" aria-label="Close transaction details">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>

                <div class="modal-body">
                    <div id="txDetailModalBody"></div>
                </div>
            </div>
        </div>

        <div id="recordsToast" class="records-toast" role="status" aria-live="polite"></div>

    </main>
</div>

<script src="../../assets/js/app.js"></script>
<script src="../../assets/js/records.js?v=5"></script>
</body>
</html>
