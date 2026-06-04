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
    <link rel="stylesheet" href="../../assets/css/records.css?v=3">
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
                    <p class="page-desc">Long-term medical records for all registered patients â€” view clinic history, vitals, medicines dispensed, emergencies, and certificates.</p>
                </div>
            </div>

            <div class="records-stats">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fa-solid fa-users"></i></div>
                    <div class="stat-info">
                        <div class="stat-val" id="statTotal">â€”</div>
                        <div class="stat-label">Total Patients</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green"><i class="fa-solid fa-graduation-cap"></i></div>
                    <div class="stat-info">
                        <div class="stat-val" id="statStudents">â€”</div>
                        <div class="stat-label">Students</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon yellow"><i class="fa-solid fa-chalkboard-teacher"></i></div>
                    <div class="stat-info">
                        <div class="stat-val" id="statFaculty">â€”</div>
                        <div class="stat-label">Faculty</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fa-solid fa-id-badge"></i></div>
                    <div class="stat-info">
                        <div class="stat-val" id="statStaff">â€”</div>
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
                        <input type="text" id="recordSearchInput" placeholder="Search by ID, name, or programâ€¦">
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
                        <option value="name_asc">Name Aâ€“Z</option>
                        <option value="name_desc">Name Zâ€“A</option>
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
                    <span class="pagination-info" id="paginationInfo">Loadingâ€¦</span>
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
                            <div class="modal-patient-name" id="modalPatientName">Loading</div>
                            <div class="modal-patient-sub">
                                <span><i class="fa-solid fa-id-card"></i> <span id="modalPatientID">â€”</span></span>
                                <span><i class="fa-solid fa-user-tag"></i> <span id="modalPatientType">â€”</span></span>
                                <span><i class="fa-solid fa-venus-mars"></i> <span id="modalPatientSex">â€”</span></span>
                                <span><i class="fa-solid fa-book-open"></i> <span id="modalPatientProgram">â€”</span></span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-header-actions">
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
                    <button type="button" class="modal-tab" data-tab="tabMedicalProfile" role="tab" aria-selected="false">
                        <i class="fa-solid fa-clipboard-list"></i>
                        Medical Profile
                        <span class="tab-count-badge" id="tabMedicalProfileCount"></span>
                    </button>
                    <button type="button" class="modal-tab" data-tab="tabFamilyHistory" role="tab" aria-selected="false">
                        <i class="fa-solid fa-people-roof"></i>
                        Family History
                        <span class="tab-count-badge" id="tabFamilyHistoryCount"></span>
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
                        <div class="records-edit-toolbar">
                            <button type="button" class="btn-outline" id="togglePatientInfoEdit" style="cursor:pointer;pointer-events:auto;">
                                <i class="fa-solid fa-pen-to-square"></i>
                                Edit Patient Info
                            </button>
                        </div>
                        <div class="patient-info-grid">

                            <div class="info-block">
                                <div class="info-section-title">
                                    <i class="fa-solid fa-user"></i>
                                    Personal Information
                                </div>
                                <div class="info-fields">
                                    <div class="info-field">
                                        <span class="info-label">School ID</span>
                                        <span class="info-val" id="infoSchoolID">â€”</span>
                                    </div>
                                    <div class="info-field">
                                        <span class="info-label">Full Name</span>
                                        <span class="info-val" id="infoFullName">â€”</span>
                                    </div>
                                    <div class="info-field">
                                        <span class="info-label">Sex</span>
                                        <span class="info-val" id="infoSex">â€”</span>
                                    </div>
                                    <div class="info-field">
                                        <span class="info-label">Birthday</span>
                                        <span class="info-val" id="infoBirthday">â€”</span>
                                    </div>
                                    <div class="info-field">
                                        <span class="info-label">Email</span>
                                        <span class="info-val" id="infoEmail">â€”</span>
                                    </div>
                                    <div class="info-field">
                                        <span class="info-label">Contact Number</span>
                                        <span class="info-val" id="infoContact">â€”</span>
                                    </div>
                                    <div class="info-field">
                                        <span class="info-label">Age</span>
                                        <span class="info-val" id="infoAge">-</span>
                                    </div>
                                    <div class="info-field">
                                        <span class="info-label">Nationality</span>
                                        <span class="info-val" id="infoNationality">-</span>
                                    </div>
                                    <div class="info-field">
                                        <span class="info-label">Religion</span>
                                        <span class="info-val" id="infoReligion">-</span>
                                    </div>
                                    <div class="info-field">
                                        <span class="info-label">Profile Status</span>
                                        <span class="info-val" id="infoPatientStatus">-</span>
                                    </div>
                                    <div class="info-field">
                                        <span class="info-label">Address</span>
                                        <span class="info-val" id="infoAddress">-</span>
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
                                        <span class="info-val" id="infoPersonTypes">â€”</span>
                                    </div>
                                    <div class="info-field">
                                        <span class="info-label">Program / Dept.</span>
                                        <span class="info-val" id="infoProgram">â€”</span>
                                    </div>
                                    <div class="info-field">
                                        <span class="info-label">Department</span>
                                        <span class="info-val" id="infoDepartment">â€”</span>
                                    </div>
                                    <div class="info-field">
                                        <span class="info-label">Year &amp; Section</span>
                                        <span class="info-val" id="infoSection">â€”</span>
                                    </div>
                                    <div class="info-field">
                                        <span class="info-label">Enrollment Status</span>
                                        <span class="info-val" id="infoStatus">â€”</span>
                                    </div>
                                    <div class="info-field">
                                        <span class="info-label">Academic Year</span>
                                        <span class="info-val" id="infoAcadYear">â€”</span>
                                    </div>
                                </div>
                            </div>
                            <div class="info-block info-block--full">
                                <div class="info-section-title">
                                    <i class="fa-solid fa-phone-volume"></i>
                                    Emergency Contact
                                </div>
                                <div class="info-fields">
                                    <div class="info-field">
                                        <span class="info-label">Guardian Name</span>
                                        <span class="info-val" id="infoGuardianName">-</span>
                                    </div>
                                    <div class="info-field">
                                        <span class="info-label">Relationship</span>
                                        <span class="info-val" id="infoRelationship">-</span>
                                    </div>
                                    <div class="info-field">
                                        <span class="info-label">Mobile No.</span>
                                        <span class="info-val" id="infoMobileNo">-</span>
                                    </div>
                                    <div class="info-field">
                                        <span class="info-label">Telephone</span>
                                        <span class="info-val" id="infoTelephone">-</span>
                                    </div>
                                    <div class="info-field">
                                        <span class="info-label">Emergency Address</span>
                                        <span class="info-val" id="infoEmergencyAddress">-</span>
                                    </div>
                                </div>
                            </div>


                            <div class="info-block info-block--full">
                                <div class="info-section-title">
                                    <i class="fa-solid fa-virus"></i>
                                    Known Medical Conditions
                                </div>
                                <div class="disease-tags" id="infoDiseases">
                                    <span class="disease-tag empty">Loadingâ€¦</span>
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
                                    <span class="summary-stat-val" id="summaryLastVisit">â€”</span>
                                    <span class="summary-stat-label">Last Visit</span>
                                </div>
                            </div>

                        </div>

                        <form id="recordsPatientInfoForm" class="records-edit-form" style="display:none;">
<input type="hidden" name="school_person_id" id="editSchoolPersonID" value="">
                            <div class="info-block info-block--full">
                                <div class="info-section-title">
                                    <i class="fa-solid fa-user-pen"></i>
                                    Edit Personal Information
                                </div>
                                <div class="records-form-grid">
                                    <label>Contact No. <input name="contact_no" id="edit_contact_no" maxlength="20" required></label>
                                    <label>Gender <select name="gender" id="edit_gender" required><option value="">Select</option><option>Male</option><option>Female</option><option>Other</option></select></label>
                                    <label>Birth Date <input type="date" name="birth_date" id="edit_birth_date" required></label>
                                    <label>Age <input type="number" name="age" id="edit_age" min="1" max="120" required></label>
                                    <label>Nationality <input name="nationality" id="edit_nationality" maxlength="50"></label>
                                    <label>Status <input name="status" id="edit_status" maxlength="20"></label>
                                    <label>Religion <input name="religion" id="edit_religion" maxlength="30"></label>
                                    <label class="full">Address <textarea name="address" id="edit_address"></textarea></label>
                                </div>
                            </div>
                            <div class="info-block info-block--full">
                                <div class="info-section-title">
                                    <i class="fa-solid fa-phone-volume"></i>
                                    Edit Emergency Contact
                                </div>
                                <div class="records-form-grid">
                                    <label>Guardian Name <input name="guardian_name" id="edit_guardian_name" maxlength="100"></label>
                                    <label>Relationship <input name="relationship" id="edit_relationship" maxlength="50"></label>
                                    <label>Mobile No. <input name="mobile_no" id="edit_mobile_no" maxlength="20"></label>
                                    <label>Telephone <input name="telephone" id="edit_telephone" maxlength="20"></label>
                                    <label class="full">Emergency Address <textarea name="emergency_address" id="edit_emergency_address"></textarea></label>
                                </div>
                            </div>
                            <div class="records-edit-actions">
                                <button type="button" class="btn-outline" id="cancelPatientInfoEdit">Cancel</button>
                                <button type="submit" class="btn-primary" id="savePatientInfoEdit">
                                    <i class="fa-solid fa-floppy-disk"></i>
                                    Save Changes
                                </button>
                            </div>
                        </form>
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

                    <div id="tabMedicalProfile" class="tab-panel" role="tabpanel">
                        <div id="medicalProfileList"></div>
                    </div>

                    <div id="tabFamilyHistory" class="tab-panel" role="tabpanel">
                        <form id="recordsFamilyHistoryForm">
                            <div id="recordsFamilyRows"></div>
                            <div class="records-edit-actions">
                                <button type="button" class="btn-outline" id="addRecordsFamilyRow">
                                    <i class="fa-solid fa-plus"></i>
                                    Add Family History
                                </button>
                                <button type="submit" class="btn-primary" id="saveFamilyHistoryBtn">
                                    <i class="fa-solid fa-floppy-disk"></i>
                                    Save Family History
                                </button>
                            </div>
                        </form>
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
<script src="../../assets/js/records.js?v=7"></script>
</body>
</html>
