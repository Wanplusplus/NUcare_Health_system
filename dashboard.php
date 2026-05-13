<?php
require_once __DIR__ . '/controllers/patientadd.controller.php';

$viewData = getPatientAddViewData();
$patientData = $viewData['patientData'];
$programOptions = $viewData['programOptions'];
$successMessage = $viewData['successMessage'];
$errorMessage = $viewData['errorMessage'];
$activePanel = $viewData['activePanel'];
$panelStatusText = $viewData['panelStatusText'];
$panelStatusClass = $viewData['panelStatusClass'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE | Dashboard</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body data-active-panel="<?php echo htmlspecialchars($activePanel); ?>">
<div class="app-shell">
    <button class="hamburger-btn" id="hamburgerBtn" type="button" aria-label="Toggle menu">&#9776;</button>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-mark">NU</div>
            <div>
                <h1>NUCARE</h1>
                <p>Clinic Management</p>
            </div>
        </div>
        <nav class="nav-menu">
            <button class="nav-item <?php echo $activePanel === 'dashboardPanel' ? 'active' : ''; ?>" data-panel="dashboardPanel" type="button"><span class="nav-dot"></span>Dashboard</button>
            <button class="nav-item <?php echo $activePanel === 'consultationPanel' ? 'active' : ''; ?>" data-panel="consultationPanel" type="button"><span class="nav-dot"></span>Consultation</button>
            <button class="nav-item <?php echo $activePanel === 'patientsPanel' ? 'active' : ''; ?>" data-panel="patientsPanel" type="button"><span class="nav-dot"></span>Patients</button>
            <button class="nav-item <?php echo $activePanel === 'recordsPanel' ? 'active' : ''; ?>" data-panel="recordsPanel" type="button"><span class="nav-dot"></span>Records</button>
            <button class="nav-item <?php echo $activePanel === 'reportsPanel' ? 'active' : ''; ?>" data-panel="reportsPanel" type="button"><span class="nav-dot"></span>Reports</button>
            <button class="nav-item <?php echo $activePanel === 'medicinePanel' ? 'active' : ''; ?>" data-panel="medicinePanel" type="button"><span class="nav-dot"></span>Medicine</button>
            <button class="nav-item <?php echo $activePanel === 'schedulePanel' ? 'active' : ''; ?>" data-panel="schedulePanel" type="button"><span class="nav-dot"></span>Schedule</button>
        </nav>
        <div class="sidebar-footer">
            <p class="footer-title">System Status</p>
            <div class="status-pill status-good">Operational</div>
        </div>
    </aside>

    <main class="main-content">
        <header class="page-header">
            <div>
                <p class="breadcrumb">Home / Dashboard</p>
                <h2>NUCARE Clinic Portal</h2>
                <p class="page-description">Manage patients, records, reports, and clinical workflows from one polished interface.</p>
            </div>
            <div class="header-actions">
                <button class="header-button accent" id="newPatientButton" type="button">New Patient</button>
                <button class="header-button outline" type="button">Logout</button>
            </div>
        </header>

        <!-- ═══════════════════════════════════ DASHBOARD ═══════════════════════════════════ -->
        <section id="dashboardPanel" class="panel active">
            <div class="cards-grid">
                <article class="status-card"><h3>Patients</h3><p class="status-value">1,248</p></article>
                <article class="status-card"><h3>Today's Visits</h3><p class="status-value">34</p></article>
                <article class="status-card"><h3>Pending Reports</h3><p class="status-value">12</p></article>
                <article class="status-card"><h3>Appointments</h3><p class="status-value">18</p></article>
            </div>
            <div class="content-grid">
                <div class="panel-card">
                    <div class="panel-card-header"><h3>Quick Actions</h3></div>
                    <div class="panel-card-body">
                        <p>Use the left navigation to explore each module. The new patient form is available in the Patients section.</p>
                        <div class="action-list">
                            <span class="action-pill">Patient intake</span>
                            <span class="action-pill">Record review</span>
                            <span class="action-pill">Report generation</span>
                            <span class="action-pill">Scheduling</span>
                        </div>
                    </div>
                </div>
                <div class="panel-card accent-card">
                    <div class="panel-card-header"><h3>NUCARE Overview</h3></div>
                    <div class="panel-card-body">
                        <p>Designed for health systems, NUCARE brings a clean and professional user experience to clinic management.</p>
                    </div>
                </div>
            </div>
        </section>

<<<<<<< HEAD
            <nav class="nav-menu">
                <button class="nav-item <?php echo $activePanel === 'dashboardPanel' ? 'active' : ''; ?>" data-panel="dashboardPanel" type="button">
                    <span class="nav-dot"></span>
                    Dashboard
                </button>
                <a class="nav-item <?php echo $activePanel === 'consultationPanel' ? 'active' : ''; ?>" href="consultation.php" style="text-decoration:none;">
                    <span class="nav-dot"></span>
                    Consultation
                </a>
                <button class="nav-item <?php echo $activePanel === 'patientsPanel' ? 'active' : ''; ?>" data-panel="patientsPanel" type="button">
                    <span class="nav-dot"></span>
                    Patients
                </button>
                <button class="nav-item <?php echo $activePanel === 'recordsPanel' ? 'active' : ''; ?>" data-panel="recordsPanel" type="button">
                    <span class="nav-dot"></span>
                    Records
                </button>
                <button class="nav-item <?php echo $activePanel === 'reportsPanel' ? 'active' : ''; ?>" data-panel="reportsPanel" type="button">
                    <span class="nav-dot"></span>
                    Reports
                </button>
                <button class="nav-item <?php echo $activePanel === 'medicinePanel' ? 'active' : ''; ?>" data-panel="medicinePanel" type="button">
                    <span class="nav-dot"></span>
                    Medicine
                </button>
                    <button class="nav-item <?php echo $activePanel === 'schedulePanel' ? 'active' : ''; ?>" data-panel="schedulePanel" type="button">
                    <span class="nav-dot"></span>
                    Schedule
                </button>
            </nav>

            <div class="sidebar-footer">
                <p class="footer-title">System Status</p>
                <div class="status-pill status-good">Operational</div>
=======
        <!-- ═══════════════════════════════════ PATIENTS ═══════════════════════════════════ -->
        <section id="patientsPanel" class="panel">
            <div class="panel-overview">
                <div><h3>Add Patient</h3><p>Capture patient details in a form that matches your <code>patients</code> table.</p></div>
                <span class="panel-status <?php echo htmlspecialchars($panelStatusClass); ?>"><?php echo htmlspecialchars($panelStatusText); ?></span>
>>>>>>> f2b5551eed4f7b29e1aaf410975a46ab192ba58a
            </div>
            <?php if ($successMessage !== ''): ?>
                <div class="feedback-message success-message"><?php echo htmlspecialchars($successMessage); ?></div>
            <?php endif; ?>
            <?php if ($errorMessage !== ''): ?>
                <div class="feedback-message error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
            <?php endif; ?>
            <form id="patientForm" class="patient-form" method="post" action="patiente_save_record.php">
                <div class="form-grid">
                    <div class="input-group"><label for="patientFname">First Name</label><input type="text" id="patientFname" name="patientFname" placeholder="Enter first name" value="<?php echo htmlspecialchars($patientData['patientFname']); ?>" required></div>
                    <div class="input-group"><label for="patientLname">Last Name</label><input type="text" id="patientLname" name="patientLname" placeholder="Enter last name" value="<?php echo htmlspecialchars($patientData['patientLname']); ?>" required></div>
                    <div class="input-group"><label for="patientMname">Middle Name</label><input type="text" id="patientMname" name="patientMname" placeholder="Enter middle name" value="<?php echo htmlspecialchars($patientData['patientMname']); ?>"></div>
                    <div class="input-group">
                        <label for="patientProgram">Program</label>
                        <select id="patientProgram" name="patientProgram">
                            <option value=""><?php echo empty($programOptions) ? 'No programs available' : 'Select program'; ?></option>
                            <?php foreach ($programOptions as $program): ?>
                                <option value="<?php echo (int) $program['ProgramID']; ?>" <?php echo $patientData['patientProgram'] === (string) $program['ProgramID'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($program['ProgramName']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-group">
                        <label for="patientSex">Sex</label>
                        <select id="patientSex" name="patientSex" required>
                            <option value="">Select sex</option>
                            <option value="Male" <?php echo $patientData['patientSex'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $patientData['patientSex'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                        </select>
                    </div>
                    <div class="input-group"><label for="patientBirthday">Birthday</label><input type="date" id="patientBirthday" name="patientBirthday" value="<?php echo htmlspecialchars($patientData['patientBirthday']); ?>"></div>
                    <div class="input-group full-width"><label for="patientEmail">Email Address</label><input type="email" id="patientEmail" name="patientEmail" placeholder="Enter email address" value="<?php echo htmlspecialchars($patientData['patientEmail']); ?>"></div>
                    <div class="input-group full-width"><label for="patientPhone">Phone Number</label><input type="tel" id="patientPhone" name="patientPhone" placeholder="Enter phone number" value="<?php echo htmlspecialchars($patientData['patientPhone']); ?>"></div>
                    <div class="input-group full-width"><label for="patientAddress">Address</label><input type="text" id="patientAddress" name="patientAddress" placeholder="Enter patient address" value="<?php echo htmlspecialchars($patientData['patientAddress']); ?>"></div>
                    <div class="input-group full-width"><label for="patientReligion">Religion</label><input type="text" id="patientReligion" name="patientReligion" placeholder="Enter religion" value="<?php echo htmlspecialchars($patientData['patientReligion']); ?>"></div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="primary-button">Save Patient</button>
                    <button type="reset" class="secondary-button" id="clearPatientForm">Clear Form</button>
                </div>
            </form>
            <div class="placeholder-panel">
                <h4>Patients Module</h4>
                <p>This module is a UI placeholder only. The patient intake form fields are laid out to match your <code>patients</code> table structure from <code>nucaredb.sql</code>.</p>
            </div>
        </section>

        <!-- ═══════════════════════════════════ RECORDS ═══════════════════════════════════ -->
        <section id="recordsPanel" class="panel">
            <div class="placeholder-panel large"><h3>Records</h3><p>Placeholder area for patient records, medical transactions, and exam summaries.</p></div>
        </section>

        <!-- ═══════════════════════════════════ REPORTS ═══════════════════════════════════ -->
        <section id="reportsPanel" class="panel">
            <div class="placeholder-panel large"><h3>Reports</h3><p>Placeholder area for reports, analytics, and health summaries.</p></div>
        </section>

        <!-- ═══════════════════════════════════ SETTINGS ═══════════════════════════════════ -->
        <section id="settingsPanel" class="panel">
            <div class="placeholder-panel large"><h3>Settings</h3><p>Placeholder area for profile settings, account preferences, and system configurations.</p></div>
        </section>

        <!-- ═══════════════════════════════════ CONSULTATION ═══════════════════════════════════ -->
        <section id="consultationPanel" class="panel">

            <!-- Toast notification -->
            <div id="consultToast" class="consult-toast" aria-live="polite"></div>

<<<<<<< HEAD
            <section id="recordsPanel" class="panel">
                <div class="placeholder-panel large">
                    <h3>Records</h3>
                    <p>Placeholder area for patient records, medical transactions, and exam summaries. Backend integration will connect this to your database tables later.</p>
                </div>
            </section>

            <section id="reportsPanel" class="panel">
                <div class="placeholder-panel large">
                    <h3>Reports</h3>
                    <p>Placeholder area for reports, analytics, and health summaries. This panel is styled for future data visualizations and export workflows.</p>
                </div>
            </section>

            <section id="settingsPanel" class="panel">
                <div class="placeholder-panel large">
                    <h3>Settings</h3>
                    <p>Placeholder area for profile settings, account preferences, and system configurations.</p>
                </div>
            </section>
            
            
        </main>
    </div>

    <script src="assets/js/app.js"></script>
    <script src="assets/js/patientadd.js"></script>
=======
            <!-- ── Search Patient Card ── -->
            <div class="consult-search-card">
                <div class="consult-search-header">
                    <div class="consult-search-title">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        Find Patient
                    </div>
                    <p class="consult-search-hint">Enter a patient ID number or full name to begin a consultation.</p>
                </div>
                <div class="consult-search-row">
                    <div class="search-input-wrapper">
                        <span class="search-icon-inside">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        </span>
                        <input
                            type="text"
                            id="consultSearchInput"
                            name="consultSearchInput"
                            class="search-input-field"
                            placeholder="Patient ID or full name&hellip;"
                            autocomplete="off"
                            onkeydown="if(event.key==='Enter'){event.preventDefault();window.searchPatient&&window.searchPatient();}"
                        >
                    </div>
                    <button class="search-patient-btn" type="button" onclick="window.searchPatient&&window.searchPatient()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        Search Patient
                    </button>
                </div>
                <div class="search-feedback" id="searchFeedback" aria-live="polite"></div>
            </div>

            <!-- ── Patient Info Card (hidden until found) ── -->
            <div class="consult-patient-card" id="consultPatientCard">
                <div class="cpc-avatar" id="cpcAvatarWrap">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </div>
                <div class="cpc-body">
                    <div class="cpc-name-row">
                        <span class="cpc-fullname" id="cpcName">—</span>
                        <span class="badge-active">Active</span>
                    </div>
                    <div class="cpc-grid">
                        <div class="cpc-field"><span class="cpc-label">Patient ID</span><span class="cpc-value" id="cpcID">—</span></div>
                        <div class="cpc-field"><span class="cpc-label">Sex</span><span class="cpc-value" id="cpcSex">—</span></div>
                        <div class="cpc-field"><span class="cpc-label">Birthday</span><span class="cpc-value" id="cpcBday">—</span></div>
                        <div class="cpc-field"><span class="cpc-label">Program</span><span class="cpc-value" id="cpcProgram">—</span></div>
                        <div class="cpc-field"><span class="cpc-label">Contact No.</span><span class="cpc-value" id="cpcTel">—</span></div>
                        <div class="cpc-field"><span class="cpc-label">Loaded at</span><span class="cpc-value" id="cpcTime">—</span></div>
                    </div>
                </div>
            </div>

            <!-- ── Consultation Form ── -->
            <form id="consultationForm" class="consultation-form" method="post" action="consultation_save_record.php" novalidate>
                <input type="hidden" name="consultPatientID" id="consultPatientID" value="">

                <div class="consult-form-area disabled" id="consultFormArea">

                    <!-- Disabled overlay -->
                    <div class="disabled-overlay show" id="disabledOverlay">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><path d="M11 8v3l2 2"/></svg>
                        <p>Search and select a patient to begin consultation</p>
                    </div>

                    <!-- Vital Signs -->
                    <div class="section-card">
                        <div class="section-label">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                            Vital Signs
                        </div>
                        <div class="form-grid">
                            <div class="input-group"><label for="consultBP">Blood Pressure</label><input type="text" id="consultBP" name="consultBP" placeholder="e.g. 120/80"></div>
                            <div class="input-group"><label for="consultTemp">Temperature (&deg;C)</label><input type="text" id="consultTemp" name="consultTemp" placeholder="e.g. 36.6"></div>
                            <div class="input-group"><label for="consultPulse">Pulse Rate</label><input type="text" id="consultPulse" name="consultPulse" placeholder="e.g. 75 bpm"></div>
                            <div class="input-group"><label for="consultWeight">Weight (kg)</label><input type="text" id="consultWeight" name="consultWeight" placeholder="e.g. 55"></div>
                        </div>
                    </div>

                    <!-- Chief Complaint -->
                    <div class="section-card">
                        <div class="section-label">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            Concern / Chief Complaint
                        </div>
                        <div class="form-grid">
                            <div class="input-group full-width">
                                <label for="consultConcern">Chief Complaint <span class="req-star">*</span></label>
                                <textarea id="consultConcern" name="consultConcern" rows="3" placeholder="Describe the patient's chief complaint or reason for consultation&hellip;"></textarea>
                                <span class="err-msg" id="consultConcernErr" style="display:none;">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                    This field is required
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Medicines Dispensed -->
                    <div class="section-card">
                        <div class="section-label">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                            Medicine(s) Dispensed
                        </div>
                        <div class="meds-list" id="medsList">
                            <div class="med-entry" id="med-0">
                                <div class="input-group">
                                    <label for="consultMedName0">Medicine Name</label>
                                    <input type="text" id="consultMedName0" name="consultMedName[]" placeholder="Medicine name (optional)">
                                    <span class="err-msg" id="medNameErr0" style="display:none;"></span>
                                </div>
                                <div class="input-group med-qty-group">
                                    <label for="consultMedQty0">Qty</label>
                                    <input type="number" id="consultMedQty0" name="consultMedQty[]" placeholder="0" min="1">
                                    <span class="err-msg" id="medQtyErr0" style="display:none;"></span>
                                </div>
                                <div class="med-remove-placeholder"></div>
                            </div>
                        </div>
                        <button class="add-med-btn" type="button" onclick="addConsultMed()">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Add Medicine
                        </button>
                    </div>

                    <!-- Service Rendered -->
                    <div class="section-card">
                        <div class="section-label">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                            Service Rendered
                        </div>
                        <div class="form-grid">
                            <div class="input-group full-width">
                                <label for="consultService">Service <span class="req-star">*</span></label>
                                <select id="consultService" name="consultService" onchange="toggleOtherService(this)">
                                    <option value="">&mdash; Select service &mdash;</option>
                                    <option value="Consultation">Consultation</option>
                                    <option value="First Aid / Wound Care">First Aid / Wound Care</option>
                                    <option value="Medicine Dispensing">Medicine Dispensing</option>
                                    <option value="Blood Pressure Monitoring">Blood Pressure Monitoring</option>
                                    <option value="Medical Certificate">Medical Certificate Issuance</option>
                                    <option value="Referral to Hospital">Referral to Hospital</option>
                                    <option value="Health Counseling">Health Counseling</option>
                                    <option value="Other">Other</option>
                                </select>
                                <span class="err-msg" id="consultServiceErr" style="display:none;">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                    This field is required
                                </span>
                            </div>
                            <div class="input-group full-width" id="otherServiceWrap" style="display:none;">
                                <label for="consultServiceOther">Please specify</label>
                                <input type="text" id="consultServiceOther" name="consultServiceOther" placeholder="Describe the service&hellip;">
                            </div>
                            <div class="input-group full-width">
                                <label for="consultNotes">Additional Notes</label>
                                <textarea id="consultNotes" name="consultNotes" rows="2" placeholder="Additional notes or clinical findings (optional)&hellip;"></textarea>
                            </div>
                        </div>
                    </div>

                </div><!-- /.consult-form-area -->

                <!-- Form Actions -->
                <div class="form-actions-card" id="consultFormActions" style="opacity:0.4;pointer-events:none;">
                    <button type="submit" class="primary-button" id="consultSaveBtn">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        Save Record
                    </button>
                    <button type="reset" class="secondary-button" id="clearConsultForm">Clear Form</button>
                </div>

            </form>
        </section>

    </main>
</div>

<script src="assets/js/app.js"></script>
<script src="assets/js/patientadd.js"></script>
<script src="assets/js/dashboard.js"></script>

<script>
let medCount = 1;
function addConsultMed() {
    const list = document.getElementById('medsList');
    const idx = medCount++;
    const row = document.createElement('div');
    row.className = 'med-entry';
    row.id = 'med-' + idx;
    row.innerHTML =
        '<div class="input-group">' +
            '<label for="consultMedName' + idx + '">Medicine Name</label>' +
            '<input type="text" id="consultMedName' + idx + '" name="consultMedName[]" placeholder="Medicine name (optional)">' +
            '<span class="err-msg" id="medNameErr' + idx + '" style="display:none;"></span>' +
        '</div>' +
        '<div class="input-group med-qty-group">' +
            '<label for="consultMedQty' + idx + '">Qty</label>' +
            '<input type="number" id="consultMedQty' + idx + '" name="consultMedQty[]" placeholder="0" min="1">' +
            '<span class="err-msg" id="medQtyErr' + idx + '" style="display:none;"></span>' +
        '</div>' +
        '<button type="button" class="med-remove-btn" onclick="this.closest(\'.med-entry\').remove()" title="Remove medicine">' +
            '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>' +
        '</button>';
    list.appendChild(row);
}
function toggleOtherService(sel) {
    const w = document.getElementById('otherServiceWrap');
    if (w) w.style.display = sel.value === 'Other' ? '' : 'none';
}
</script>
>>>>>>> f2b5551eed4f7b29e1aaf410975a46ab192ba58a
</body>
</html>