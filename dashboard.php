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
        <aside class="sidebar">
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

                <style>
                    /* ── Consultation Panel ── */
                    #consultationPanel {
                        display: flex;
                        flex-direction: column;
                        gap: 18px;
                        padding: 24px;
                        font-family: 'Segoe UI', sans-serif;
                    }

                    /* Search bar */
                    .consult-search-card {
                        background: #fff;
                        border: 0.5px solid #dce4f0;
                        border-radius: 12px;
                        padding: 16px 18px;
                    }
                    .consult-search-title {
                        font-size: 11px;
                        font-weight: 700;
                        color: #1a2c5b;
                        letter-spacing: 0.07em;
                        text-transform: uppercase;
                        margin-bottom: 10px;
                        display: flex;
                        align-items: center;
                        gap: 7px;
                    }
                    .consult-search-title i { font-size: 15px; color: #d4a700; }
                    .consult-search-row {
                        display: flex;
                        gap: 10px;
                        align-items: flex-end;
                    }
                    .consult-search-row .input-group { flex: 1; margin: 0; }
                    .consult-search-row .input-group label {
                        font-size: 11px;
                        font-weight: 600;
                        color: #6b7a99;
                        letter-spacing: 0.05em;
                        text-transform: uppercase;
                        display: block;
                        margin-bottom: 4px;
                    }
                    .consult-search-row .input-group input {
                        width: 100%;
                        padding: 8px 11px;
                        border: 1px solid #dce4f0;
                        border-radius: 8px;
                        font-size: 13px;
                        color: #1a2c5b;
                        background: #fafbfd;
                        outline: none;
                        font-family: inherit;
                        transition: border 0.15s, box-shadow 0.15s;
                    }
                    .consult-search-row .input-group input:focus {
                        border-color: #7a9edc;
                        box-shadow: 0 0 0 3px rgba(122,158,220,0.18);
                    }
                    .search-patient-btn {
                        padding: 9px 20px;
                        border-radius: 8px;
                        border: none;
                        background: #1a2c5b;
                        color: #fff;
                        font-size: 13px;
                        font-weight: 600;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        gap: 7px;
                        font-family: inherit;
                        white-space: nowrap;
                        transition: background 0.15s;
                        flex-shrink: 0;
                    }
                    .search-patient-btn:hover { background: #243570; }
                    .search-patient-btn i { font-size: 15px; }

                    /* Search feedback */
                    .search-feedback {
                        margin-top: 10px;
                        font-size: 12px;
                        display: none;
                        align-items: center;
                        gap: 6px;
                        padding: 8px 12px;
                        border-radius: 8px;
                    }
                    .search-feedback.not-found {
                        display: flex;
                        background: #fdf0ef;
                        color: #c0392b;
                        border: 1px solid #f3c0bc;
                    }
                    .search-feedback.found {
                        display: flex;
                        background: #eaf5ef;
                        color: #1a7a4a;
                        border: 1px solid #b2dcc4;
                    }

                    /* Patient card */
                    .consult-patient-card {
                        background: #fff;
                        border: 0.5px solid #dce4f0;
                        border-radius: 12px;
                        padding: 14px 18px;
                        display: none;
                        gap: 16px;
                        align-items: flex-start;
                    }
                    .consult-patient-card.visible { display: flex; }
                    .cpc-avatar {
                        width: 62px;
                        height: 62px;
                        border-radius: 50%;
                        background: #dce4f0;
                        border: 2px solid #dce4f0;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        flex-shrink: 0;
                        color: #6b7a99;
                        font-size: 26px;
                    }
                    .cpc-grid {
                        flex: 1;
                        display: grid;
                        grid-template-columns: 1fr 1fr;
                        gap: 4px 20px;
                    }
                    .cpc-field { display: flex; flex-direction: column; gap: 1px; }
                    .cpc-label {
                        font-size: 10px;
                        font-weight: 600;
                        color: #6b7a99;
                        letter-spacing: 0.06em;
                        text-transform: uppercase;
                    }
                    .cpc-value {
                        font-size: 13px;
                        color: #1a2c5b;
                        font-weight: 500;
                        border-bottom: 1px solid #dce4f0;
                        padding-bottom: 2px;
                        min-height: 18px;
                    }
                    .cpc-status {
                        flex-shrink: 0;
                        display: flex;
                        flex-direction: column;
                        align-items: flex-end;
                        gap: 6px;
                    }
                    .badge-active {
                        padding: 3px 10px;
                        border-radius: 20px;
                        font-size: 11px;
                        font-weight: 600;
                        background: #eaf5ef;
                        color: #1a7a4a;
                    }
                    .cpc-time { font-size: 11px; color: #6b7a99; }

                    /* Disabled overlay state */
                    .consult-form-area {
                        display: flex;
                        flex-direction: column;
                        gap: 14px;
                        position: relative;
                    }
                    .consult-form-area.disabled {
                        pointer-events: none;
                        user-select: none;
                    }
                    .consult-form-area.disabled .section-card,
                    .consult-form-area.disabled .form-actions-card {
                        opacity: 0.4;
                        filter: grayscale(0.3);
                    }
                    .disabled-overlay {
                        display: none;
                        position: absolute;
                        inset: 0;
                        z-index: 10;
                        background: rgba(240,244,251,0.55);
                        border-radius: 12px;
                        align-items: center;
                        justify-content: center;
                        flex-direction: column;
                        gap: 10px;
                    }
                    .disabled-overlay.show { display: flex; }
                    .disabled-overlay i { font-size: 32px; color: #6b7a99; }
                    .disabled-overlay p { font-size: 13px; color: #6b7a99; font-weight: 500; }

                    /* Section cards */
                    .section-card {
                        background: #fff;
                        border: 0.5px solid #dce4f0;
                        border-radius: 12px;
                        padding: 14px 18px;
                    }
                    .section-label {
                        font-size: 11px;
                        font-weight: 700;
                        color: #1a2c5b;
                        letter-spacing: 0.07em;
                        text-transform: uppercase;
                        margin-bottom: 10px;
                        display: flex;
                        align-items: center;
                        gap: 7px;
                    }
                    .section-label i { font-size: 15px; color: #d4a700; }

                    /* Form grid */
                    .form-grid {
                        display: grid;
                        grid-template-columns: 1fr 1fr;
                        gap: 10px;
                    }
                    .input-group {
                        display: flex;
                        flex-direction: column;
                        gap: 4px;
                    }
                    .input-group.full-width { grid-column: span 2; }
                    .input-group label {
                        font-size: 11px;
                        font-weight: 600;
                        color: #6b7a99;
                        letter-spacing: 0.05em;
                        text-transform: uppercase;
                    }
                    .input-group input,
                    .input-group select,
                    .input-group textarea {
                        width: 100%;
                        padding: 8px 11px;
                        border: 1px solid #dce4f0;
                        border-radius: 8px;
                        font-size: 13px;
                        color: #1a2c5b;
                        background: #fafbfd;
                        outline: none;
                        font-family: inherit;
                        transition: border 0.15s, box-shadow 0.15s;
                    }
                    .input-group input:focus,
                    .input-group select:focus,
                    .input-group textarea:focus {
                        border-color: #7a9edc;
                        box-shadow: 0 0 0 3px rgba(122,158,220,0.18);
                    }
                    .input-group input.error,
                    .input-group select.error,
                    .input-group textarea.error {
                        border-color: #c0392b;
                        background: #fdf0ef;
                    }
                    .input-group textarea { resize: none; min-height: 72px; }
                    .input-group select { cursor: pointer; }

                    /* Error messages */
                    .err-msg {
                        font-size: 11px;
                        color: #c0392b;
                        font-weight: 500;
                        display: none;
                        align-items: center;
                        gap: 4px;
                        margin-top: 2px;
                    }
                    .err-msg.show { display: flex; }

                    /* Medicine rows */
                    .meds-list { display: flex; flex-direction: column; gap: 8px; }
                    .med-entry {
                        display: grid;
                        grid-template-columns: 1fr 100px 30px;
                        gap: 8px;
                        align-items: end;
                    }
                    .remove-med {
                        width: 30px;
                        height: 36px;
                        border: 1px solid #dce4f0;
                        border-radius: 8px;
                        background: none;
                        cursor: pointer;
                        color: #6b7a99;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 15px;
                        transition: background 0.12s, color 0.12s;
                    }
                    .remove-med:hover { background: #fdf0ef; color: #c0392b; border-color: #c0392b; }
                    .add-med-btn {
                        background: none;
                        border: 1px dashed #dce4f0;
                        border-radius: 8px;
                        padding: 6px 12px;
                        font-size: 12px;
                        color: #6b7a99;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        gap: 5px;
                        margin-top: 8px;
                        font-family: inherit;
                        transition: background 0.15s, border-color 0.15s;
                    }
                    .add-med-btn:hover { background: #f0f4fb; border-color: #b0bed4; }

                    /* Form actions */
                    .form-actions-card {
                        background: #fff;
                        border: 0.5px solid #dce4f0;
                        border-radius: 12px;
                        padding: 12px 18px;
                        display: flex;
                        justify-content: flex-end;
                        gap: 10px;
                    }
                    .primary-button {
                        padding: 9px 28px;
                        border-radius: 8px;
                        border: none;
                        background: #f5c842;
                        color: #111e3f;
                        font-size: 13px;
                        font-weight: 700;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        gap: 8px;
                        font-family: inherit;
                        transition: background 0.15s, transform 0.1s;
                    }
                    .primary-button:hover { background: #d4a700; color: #fff; }
                    .primary-button:active { transform: scale(0.97); }
                    .secondary-button {
                        padding: 9px 20px;
                        border-radius: 8px;
                        border: 1px solid #dce4f0;
                        background: none;
                        font-size: 13px;
                        color: #6b7a99;
                        cursor: pointer;
                        font-family: inherit;
                        transition: background 0.15s;
                    }
                    .secondary-button:hover { background: #f0f4fb; }

                    /* Toast */
                    #consultToast {
                        position: fixed;
                        top: 20px;
                        right: 24px;
                        padding: 10px 16px;
                        border-radius: 10px;
                        font-size: 13px;
                        font-weight: 600;
                        display: none;
                        align-items: center;
                        gap: 8px;
                        z-index: 9999;
                        font-family: 'Segoe UI', sans-serif;
                    }
                    #consultToast.success { background: #eaf5ef; color: #1a7a4a; border: 1px solid #b2dcc4; display: flex; }
                    #consultToast.error-toast { background: #fdf0ef; color: #c0392b; border: 1px solid #f3c0bc; display: flex; }
                </style>

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
                                onkeydown="if(event.key==='Enter'){searchPatient();}">
                        </div>
                        <button class="search-patient-btn" type="button" onclick="searchPatient()">
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

            <script src="assets/js/consultation.js"></script>
                        
        </main>
    </div>

    <script src="assets/js/app.js"></script>
    <script src="assets/js/patientadd.js"></script>
</body>
</html>