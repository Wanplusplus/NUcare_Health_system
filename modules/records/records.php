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
    <link rel="stylesheet" href="../../assets/css/records.css?v=1">
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

        <!-- ══ PAGE HEADER ══ -->
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

        <!-- ══ STATS ══ -->
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

        <!-- ══ RECORDS TABLE ══ -->
        <div class="records-card">
            <div class="card-section-label">
                <i class="fa-solid fa-folder-open"></i>
                All Patient Records
            </div>
            <p class="card-section-desc">Click any row or the View button to open a full patient record.</p>

            <!-- Filters -->
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

            <!-- Table -->
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
                        <!-- Populated by JS -->
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pagination-row">
                <span class="pagination-info" id="paginationInfo">Loading…</span>
                <div class="pagination-btns" id="paginationBtns"></div>
            </div>
        </div>

    </div><!-- /.records-page -->

    <!-- ══════════════════════════════════════════
         PATIENT RECORD MODAL
    ══════════════════════════════════════════ -->
    <div id="recordModal" class="modal-backdrop">
        <div class="modal-box">

            <!-- Modal Header -->
            <div class="modal-header">
                <div class="modal-header-left">
                    <div class="modal-avatar">
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
                <button class="modal-close" id="modalCloseBtn" title="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Modal Tabs -->
            <div class="modal-tabs">
                <button class="modal-tab active" data-tab="tabInfo">
                    <i class="fa-solid fa-circle-info"></i>
                    Patient Info
                </button>
                <button class="modal-tab" data-tab="tabHistory">
                    <i class="fa-solid fa-notes-medical"></i>
                    Clinic History
                    <span id="tabHistoryCount" style="background:var(--navy);color:#fff;border-radius:999px;padding:1px 7px;font-size:.65rem;"></span>
                </button>
                <button class="modal-tab" data-tab="tabEmergency">
                    <i class="fa-solid fa-kit-medical"></i>
                    Emergencies
                </button>
                <button class="modal-tab" data-tab="tabCerts">
                    <i class="fa-solid fa-file-shield"></i>
                    Certificates
                </button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body">

                <!-- ── Tab: Patient Info ── -->
                <div id="tabInfo" class="tab-panel active">

                    <div class="patient-info-grid">

                        <!-- Personal Info -->
                        <div class="info-block">
                            <div class="info-section-title"><i class="fa-solid fa-user"></i> Personal Information</div>
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
                            </div>
                        </div>

                        <!-- Academic / Employment Info -->
                        <div class="info-block">
                            <div class="info-section-title"><i class="fa-solid fa-building-columns"></i> Academic / Employment</div>
                            <div class="info-fields">
                                <div class="info-field">
                                    <span class="info-label">Type</span>
                                    <span class="info-val" id="infoPersonType">—</span>
                                </div>
                                <div class="info-field">
                                    <span class="info-label">Program / Dept.</span>
                                    <span class="info-val" id="infoProgram">—</span>
                                </div>
                                <div class="info-field">
                                    <span class="info-label">Year &amp; Section / Position</span>
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

                        <!-- Known Conditions / Diseases -->
                        <div class="info-block info-block--full">
                            <div class="info-section-title"><i class="fa-solid fa-virus"></i> Known Medical Conditions</div>
                            <div class="disease-tags" id="infoDiseases">
                                <span class="disease-tag empty">Loading…</span>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ── Tab: Clinic History ── -->
                <div id="tabHistory" class="tab-panel">
                    <div class="history-filters">
                        <button class="history-filter-btn active" onclick="filterHistory('all', this)">
                            <i class="fa-solid fa-list"></i> All Visits
                        </button>
                        <button class="history-filter-btn" onclick="filterHistory('general', this)">
                            <i class="fa-solid fa-stethoscope"></i> General
                        </button>
                        <button class="history-filter-btn" onclick="filterHistory('dental', this)">
                            <i class="fa-solid fa-tooth"></i> Dental
                        </button>
                        <button class="history-filter-btn" onclick="filterHistory('physical', this)">
                            <i class="fa-solid fa-clipboard-list"></i> Physical Exam
                        </button>
                    </div>

                    <div class="timeline" id="clinicTimeline">
                        <!-- Populated by JS -->
                    </div>
                </div>

                <!-- ── Tab: Emergencies ── -->
                <div id="tabEmergency" class="tab-panel">
                    <div class="emergency-list" id="emergencyList">
                        <!-- Populated by JS -->
                    </div>
                </div>

                <!-- ── Tab: Certificates ── -->
                <div id="tabCerts" class="tab-panel">
                    <div class="cert-list" id="certList">
                        <!-- Populated by JS -->
                    </div>
                </div>

            </div><!-- /.modal-body -->
        </div><!-- /.modal-box -->
    </div><!-- /.modal-backdrop -->

    <!-- Toast -->
    <div id="recordsToast" class="records-toast"></div>

    </main>
</div>

<script src="../../assets/js/app.js"></script>
<script src="../../assets/js/records.js"></script>
</body>
</html>