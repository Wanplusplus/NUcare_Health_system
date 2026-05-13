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
        <button class="hamburger-btn" id="hamburgerBtn" type="button" aria-label="Toggle menu">
        </button>
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
                <button class="nav-item <?php echo $activePanel === 'dashboardPanel' ? 'active' : ''; ?>" data-panel="dashboardPanel" type="button">
                    <span class="nav-dot"></span>
                    Dashboard
                </button>
                <button class="nav-item <?php echo $activePanel === 'consultationPanel' ? 'active' : ''; ?>" data-panel="consultationPanel" type="button">
                    <span class="nav-dot"></span>
                    Consultation 
                </button>
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

            <section id="dashboardPanel" class="panel active">
                <div class="cards-grid">
                    <article class="status-card">
                        <h3>Patients</h3>
                        <p class="status-value">1,248</p>
                    </article>
                    <article class="status-card">
                        <h3>Today's Visits</h3>
                        <p class="status-value">34</p>
                    </article>
                    <article class="status-card">
                        <h3>Pending Reports</h3>
                        <p class="status-value">12</p>
                    </article>
                    <article class="status-card">
                        <h3>Appointments</h3>
                        <p class="status-value">18</p>
                    </article>
                </div>

                <div class="content-grid">
                    <div class="panel-card">
                        <div class="panel-card-header">
                            <h3>Quick Actions</h3>
                        </div>
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
                        <div class="panel-card-header">
                            <h3>NUCARE Overview</h3>
                        </div>
                        <div class="panel-card-body">
                            <p>Designed for health systems, NUCARE brings a clean and professional user experience to clinic management. The interface is responsive and ready for future backend integration.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section id="patientsPanel" class="panel">
                <div class="panel-overview">
                    <div>
                        <h3>Add Patient</h3>
                        <p>Capture patient details in a form that matches your `patients` table.</p>
                    </div>
                    <span class="panel-status">UI only</span>
                </div>

                <?php if ($successMessage !== ''): ?>
                    <div class="feedback-message success-message"><?php echo htmlspecialchars($successMessage); ?></div>
                <?php endif; ?>

                <?php if ($errorMessage !== ''): ?>
                    <div class="feedback-message error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
                <?php endif; ?>

                <form id="patientForm" class="patient-form" method="post" action="patiente_save_record.php">
                    <div class="form-grid">
                        <div class="input-group">
                            <label for="patientFname">First Name</label>
                            <input type="text" id="patientFname" name="patientFname" placeholder="Enter first name" value="<?php echo htmlspecialchars($patientData['patientFname']); ?>" required>
                        </div>
                        <div class="input-group">
                            <label for="patientLname">Last Name</label>
                            <input type="text" id="patientLname" name="patientLname" placeholder="Enter last name" value="<?php echo htmlspecialchars($patientData['patientLname']); ?>" required>
                        </div>
                        <div class="input-group">
                            <label for="patientMname">Middle Name</label>
                            <input type="text" id="patientMname" name="patientMname" placeholder="Enter middle name" value="<?php echo htmlspecialchars($patientData['patientMname']); ?>">
                        </div>
                        <div class="input-group">
                            <label for="patientProgram">Program</label>
                            <select id="patientProgram" name="patientProgram">
                                <option value=""><?php echo empty($programOptions) ? 'No programs available' : 'Select program'; ?></option>
                                <?php foreach ($programOptions as $program): ?>
                                    <option value="<?php echo (int) $program['ProgramID']; ?>" <?php echo $patientData['patientProgram'] === (string) $program['ProgramID'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($program['ProgramName']); ?>
                                    </option>
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
                        <div class="input-group">
                            <label for="patientBirthday">Birthday</label>
                            <input type="date" id="patientBirthday" name="patientBirthday" value="<?php echo htmlspecialchars($patientData['patientBirthday']); ?>">
                        </div>
                        <div class="input-group full-width">
                            <label for="patientEmail">Email Address</label>
                            <input type="email" id="patientEmail" name="patientEmail" placeholder="Enter email address" value="<?php echo htmlspecialchars($patientData['patientEmail']); ?>">
                        </div>
                        <div class="input-group full-width">
                            <label for="patientPhone">Phone Number</label>
                            <input type="tel" id="patientPhone" name="patientPhone" placeholder="Enter phone number" value="<?php echo htmlspecialchars($patientData['patientPhone']); ?>">
                        </div>
                        <div class="input-group full-width">
                            <label for="patientAddress">Address</label>
                            <input type="text" id="patientAddress" name="patientAddress" placeholder="Enter patient address" value="<?php echo htmlspecialchars($patientData['patientAddress']); ?>">
                        </div>
                        <div class="input-group full-width">
                            <label for="patientReligion">Religion</label>
                            <input type="text" id="patientReligion" name="patientReligion" placeholder="Enter religion" value="<?php echo htmlspecialchars($patientData['patientReligion']); ?>">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="primary-button">Save Patient</button>
                        <button type="reset" class="secondary-button" id="clearPatientForm">Clear Form</button>
                    </div>
                </form>

                <div class="placeholder-panel">
                    <h4>Patients Module</h4>
                    <p>This module is a UI placeholder only. The patient intake form fields are laid out to match your `patients` table structure from `nucaredb.sql`.</p>
                </div>
            </section>

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
            
            <section id="consultationPanel" class="panel">

                <div id="consultToast"></div>

                <!-- ── Search Patient ── -->
                <div class="consult-search-card">

                    <div class="consult-search-title">
                        <i class="ti ti-search" aria-hidden="true"></i>Find Patient
                    </div>
                    <div class="consult-search-row">
                        <div class="input-group">
                            <label for="consultSearchInput">Patient ID or Name</label>
                            <input type="text" id="consultSearchInput" name="consultSearchInput"
                                placeholder="Enter patient ID or full name&hellip;"
                                onkeydown="if(event.key==='Enter'){window.searchPatient && window.searchPatient();}">
                        </div>
                        <button class="search-patient-btn" type="button" onclick="window.searchPatient && window.searchPatient()">

                            <i class="ti ti-search" aria-hidden="true"></i>Search
                        </button>
                    </div>
                    <div class="search-feedback" id="searchFeedback"></div>
                </div>

                <!-- ── Patient Info Card (hidden until found) ── -->
                <div class="consult-patient-card" id="consultPatientCard">
                    <div class="cpc-avatar">
                        <i class="ti ti-user" aria-hidden="true"></i>
                    </div>
                    <div class="cpc-grid">
                        <div class="cpc-field">
                            <span class="cpc-label">ID #</span>
                            <span class="cpc-value" id="cpcID"></span>
                        </div>
                        <div class="cpc-field">
                            <span class="cpc-label">Sex</span>
                            <span class="cpc-value" id="cpcSex"></span>
                        </div>
                        <div class="cpc-field">
                            <span class="cpc-label">Name</span>
                            <span class="cpc-value" id="cpcName"></span>
                        </div>
                        <div class="cpc-field">
                            <span class="cpc-label">Birthday</span>
                            <span class="cpc-value" id="cpcBday"></span>
                        </div>
                        <div class="cpc-field">
                            <span class="cpc-label">Program</span>
                            <span class="cpc-value" id="cpcProgram"></span>
                        </div>
                        <div class="cpc-field">
                            <span class="cpc-label">Tel No.</span>
                            <span class="cpc-value" id="cpcTel"></span>
                        </div>
                    </div>
                    <div class="cpc-status">
                        <span class="badge-active">Active</span>
                        <span class="cpc-time" id="cpcTime"></span>
                    </div>
                </div>

                <!-- ── Consultation Form (disabled until patient found) ── -->
                <form id="consultationForm" class="consultation-form" method="post" action="consultation_save_record.php">

                    <input type="hidden" name="consultPatientID" id="consultPatientID" value="">

                    <div class="consult-form-area disabled" id="consultFormArea">

                        <!-- Disabled overlay -->
                        <div class="disabled-overlay show" id="disabledOverlay">
                            <i class="ti ti-user-search" aria-hidden="true"></i>
                            <p>Search and select a patient to begin consultation</p>
                        </div>

                        <!-- Vital Signs -->
                        <div class="section-card">
                            <div class="section-label">
                                <i class="ti ti-heartbeat" aria-hidden="true"></i>Vital Signs
                            </div>
                            <div class="form-grid">
                                <div class="input-group">
                                    <label for="consultBP">Blood Pressure</label>
                                    <input type="text" id="consultBP" name="consultBP" placeholder="e.g. 120/80">
                                </div>
                                <div class="input-group">
                                    <label for="consultTemp">Temperature (&deg;C)</label>
                                    <input type="text" id="consultTemp" name="consultTemp" placeholder="e.g. 36.6">
                                </div>
                                <div class="input-group">
                                    <label for="consultPulse">Pulse Rate</label>
                                    <input type="text" id="consultPulse" name="consultPulse" placeholder="e.g. 75 bpm">
                                </div>
                                <div class="input-group">
                                    <label for="consultWeight">Weight (kg)</label>
                                    <input type="text" id="consultWeight" name="consultWeight" placeholder="e.g. 55">
                                </div>
                            </div>
                        </div>

                        <!-- Chief Complaint -->
                        <div class="section-card">
                            <div class="section-label">
                                <i class="ti ti-message-dots" aria-hidden="true"></i>Concern / Chief Complaint
                            </div>
                            <div class="form-grid">
                                <div class="input-group full-width">
                                    <label for="consultConcern">Chief Complaint</label>
                                    <textarea id="consultConcern" name="consultConcern"
                                        placeholder="Describe the patient's chief complaint or reason for consultation&hellip;"></textarea>
                                    <span class="err-msg" id="consultConcernErr">
                                        <i class="ti ti-alert-circle" aria-hidden="true"></i>This field is required
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Medicine(s) Dispensed -->
                        <div class="section-card">
                            <div class="section-label">
                                <i class="ti ti-pill" aria-hidden="true"></i>Medicine(s) Dispensed
                            </div>
                            <div class="meds-list" id="medsList">
                                <div class="med-entry" id="med-0">
                                    <div class="input-group">
                                        <label for="consultMedName0">Medicine Name</label>
                                        <input type="text" id="consultMedName0" name="consultMedName[]"
                                            placeholder="Medicine name (optional)">
                                        <span class="err-msg" id="medNameErr0"></span>
                                    </div>
                                    <div class="input-group">
                                        <label for="consultMedQty0">Qty</label>
                                        <input type="number" id="consultMedQty0" name="consultMedQty[]"
                                            placeholder="0" min="1">
                                        <span class="err-msg" id="medQtyErr0"></span>
                                    </div>
                                    <div></div><!-- spacer -->
                                </div>
                            </div>
                            <button class="add-med-btn" type="button" onclick="addConsultMed()">
                                <i class="ti ti-plus" aria-hidden="true"></i>Add another medicine
                            </button>
                        </div>

                        <!-- Service Rendered -->
                        <div class="section-card">
                            <div class="section-label">
                                <i class="ti ti-clipboard-list" aria-hidden="true"></i>Service Rendered
                            </div>
                            <div class="form-grid">
                                <div class="input-group full-width">
                                    <label for="consultService">Service</label>
                                    <select id="consultService" name="consultService">
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
                                    <span class="err-msg" id="consultServiceErr">
                                        <i class="ti ti-alert-circle" aria-hidden="true"></i>This field is required
                                    </span>
                                </div>
                                <div class="input-group full-width" id="otherServiceWrap" style="display:none;">
                                    <label for="consultServiceOther">Please specify</label>
                                    <input type="text" id="consultServiceOther" name="consultServiceOther"
                                        placeholder="Describe the service&hellip;">
                                </div>
                                <div class="input-group full-width">
                                    <label for="consultNotes">Additional Notes</label>
                                    <textarea id="consultNotes" name="consultNotes"
                                        placeholder="Additional notes or clinical findings (optional)&hellip;"
                                        style="min-height:52px;"></textarea>
                                </div>
                            </div>
                        </div>

                    </div><!-- /.consult-form-area -->

                    <!-- Form Actions -->
                    <div class="form-actions-card" id="consultFormActions" style="opacity:0.4;pointer-events:none;">
                        <button type="submit" class="primary-button" id="consultSaveBtn">
                            <i class="ti ti-device-floppy" aria-hidden="true"></i>Save Record
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
</body>
</html>



 