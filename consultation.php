<?php
session_start();
// Optional: guard page
// if (!isset($_SESSION['user'])) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE | Consultation</title>
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="assets/css/consultation.css">
    <!-- Tabler Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
</head>
<body>
<div class="app-shell">

    <!-- ── Sidebar (mirrors dashboard) ── -->
    <button class="hamburger-btn" id="hamburgerBtn" type="button" aria-label="Toggle menu"></button>
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
            <a class="nav-item" href="dashboard.php?panel=dashboardPanel">
                <span class="nav-dot"></span>Dashboard
            </a>
            <a class="nav-item active" href="consultation.php">
                <span class="nav-dot"></span>Consultation
            </a>
            <a class="nav-item" href="dashboard.php?panel=patientsPanel">
                <span class="nav-dot"></span>Patients
            </a>
            <a class="nav-item" href="dashboard.php?panel=recordsPanel">
                <span class="nav-dot"></span>Records
            </a>
            <a class="nav-item" href="dashboard.php?panel=reportsPanel">
                <span class="nav-dot"></span>Reports
            </a>
            <a class="nav-item" href="dashboard.php?panel=medicinePanel">
                <span class="nav-dot"></span>Medicine
            </a>
            <a class="nav-item" href="dashboard.php?panel=schedulePanel">
                <span class="nav-dot"></span>Schedule
            </a>
        </nav>

        <div class="sidebar-footer">
            <p class="footer-title">System Status</p>
            <div class="status-pill status-good">Operational</div>
        </div>
    </aside>

    <!-- ── Main content ── -->
    <main class="main-content">
        <header class="page-header">
            <div>
                <a class="back-link" href="dashboard.php">
                    <i class="ti ti-arrow-left"></i> Back to Dashboard
                </a>
                <p class="breadcrumb">Home / Consultation</p>
                <h2>Consultation</h2>
                <p class="page-description">Search for a patient, record vitals, log concerns, and dispense medicines.</p>
            </div>
            <div class="header-actions">
                <a href="dashboard.php?panel=patientsPanel">
                    <button class="header-button accent" type="button">New Patient</button>
                </a>
                <button class="header-button outline" type="button">Logout</button>
            </div>
        </header>

        <section class="panel active" id="consultationPanel">

            <!-- Search -->
            <div class="panel-card" style="margin-bottom:1.25rem;">
                <div class="panel-card-header">
                    <h3>Find Patient</h3>
                </div>
                <div class="panel-card-body">
                    <div class="consult-search-wrap">
                        <div class="input-group">
                            <label for="consultSearchInput">Patient ID or Name</label>
                            <input type="text" id="consultSearchInput"
                                   placeholder="e.g. 2024-00142 or Maria Santos"
                                   onkeydown="if(event.key==='Enter') searchPatient()">
                        </div>
                        <button class="primary-button" type="button" onclick="searchPatient()">
                            <i class="ti ti-search"></i> Search
                        </button>
                    </div>
                    <div class="search-feedback" id="searchFeedback"></div>

                    <!-- Patient summary card -->
                    <div class="consult-patient-card" id="consultPatientCard">
                        <div class="cpc-field">
                            <label>Patient ID</label>
                            <span id="cpcID">—</span>
                        </div>
                        <div class="cpc-field">
                            <label>Full Name</label>
                            <span id="cpcName">—</span>
                        </div>
                        <div class="cpc-field">
                            <label>Sex</label>
                            <span id="cpcSex">—</span>
                        </div>
                        <div class="cpc-field">
                            <label>Birthday</label>
                            <span id="cpcBday">—</span>
                        </div>
                        <div class="cpc-field">
                            <label>Program</label>
                            <span id="cpcProgram">—</span>
                        </div>
                        <div class="cpc-field">
                            <label>Phone</label>
                            <span id="cpcTel">—</span>
                        </div>
                        <div class="cpc-field">
                            <label>Visit Time</label>
                            <span id="cpcTime">—</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Consultation form -->
            <form id="consultationForm" method="post" action="consultation_save.php">
                <input type="hidden" id="consultPatientID" name="consultPatientID" value="">

                <div class="consult-form-outer">
                    <div id="disabledOverlay" class="show">
                        <i class="ti ti-lock"></i> Search and select a patient first
                    </div>

                    <div class="consult-form-area disabled" id="consultFormArea">

                        <!-- Vitals -->
                        <div class="panel-card" style="margin-bottom:1.25rem;">
                            <div class="panel-card-header"><h3>Vitals</h3></div>
                            <div class="panel-card-body">
                                <div class="vitals-grid">
                                    <div class="input-group">
                                        <label for="consultBP">Blood Pressure (mmHg)</label>
                                        <input type="text" id="consultBP" name="consultBP" placeholder="e.g. 120/80">
                                    </div>
                                    <div class="input-group">
                                        <label for="consultTemp">Temperature (°C)</label>
                                        <input type="text" id="consultTemp" name="consultTemp" placeholder="e.g. 36.6">
                                    </div>
                                    <div class="input-group">
                                        <label for="consultPulse">Pulse Rate (bpm)</label>
                                        <input type="text" id="consultPulse" name="consultPulse" placeholder="e.g. 72">
                                    </div>
                                    <div class="input-group">
                                        <label for="consultWeight">Weight (kg)</label>
                                        <input type="text" id="consultWeight" name="consultWeight" placeholder="e.g. 58">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Concern & Service -->
                        <div class="panel-card" style="margin-bottom:1.25rem;">
                            <div class="panel-card-header"><h3>Concern & Service</h3></div>
                            <div class="panel-card-body">
                                <div class="form-grid">
                                    <div class="input-group full-width">
                                        <label for="consultConcern">Chief Concern <span style="color:#ef4444">*</span></label>
                                        <textarea id="consultConcern" name="consultConcern" rows="3"
                                                  placeholder="Describe the patient's complaint or reason for visit..."></textarea>
                                        <span class="err-msg" id="consultConcernErr"></span>
                                    </div>
                                    <div class="input-group">
                                        <label for="consultService">Service Rendered <span style="color:#ef4444">*</span></label>
                                        <select id="consultService" name="consultService">
                                            <option value="">Select service</option>
                                            <option value="First Aid">First Aid</option>
                                            <option value="Medical Certificate">Medical Certificate</option>
                                            <option value="Medicine Dispensing">Medicine Dispensing</option>
                                            <option value="Referral">Referral</option>
                                            <option value="Consultation">Consultation</option>
                                            <option value="Other">Other</option>
                                        </select>
                                        <span class="err-msg" id="consultServiceErr"></span>
                                    </div>
                                    <div class="input-group" id="otherServiceWrap" style="display:none;">
                                        <label for="consultServiceOther">Specify Other Service</label>
                                        <input type="text" id="consultServiceOther" name="consultServiceOther"
                                               placeholder="Describe the service">
                                    </div>
                                    <div class="input-group full-width">
                                        <label for="consultNotes">Clinical Notes</label>
                                        <textarea id="consultNotes" name="consultNotes" rows="3"
                                                  placeholder="Optional: diagnosis, treatment plan, observations..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Medicines -->
                        <div class="panel-card" style="margin-bottom:1.25rem;">
                            <div class="panel-card-header">
                                <h3>Medicines Dispensed</h3>
                                <button class="header-button outline" type="button" onclick="addConsultMed()"
                                        style="font-size:.8rem;padding:.3rem .75rem;">
                                    <i class="ti ti-plus"></i> Add Row
                                </button>
                            </div>
                            <div class="panel-card-body">
                                <div id="medsList">
                                    <!-- Initial row -->
                                    <div class="med-entry" id="med-0">
                                        <div class="input-group">
                                            <label for="consultMedName0">Medicine Name</label>
                                            <input type="text" id="consultMedName0" name="consultMedName[]"
                                                   placeholder="Medicine name (optional)">
                                            <span class="err-msg" id="medNameErr0"></span>
                                        </div>
                                        <div class="input-group" style="max-width:120px;">
                                            <label for="consultMedQty0">Qty</label>
                                            <input type="number" id="consultMedQty0" name="consultMedQty[]"
                                                   placeholder="0" min="1">
                                            <span class="err-msg" id="medQtyErr0"></span>
                                        </div>
                                        <!-- no remove on first row -->
                                        <div style="width:34px;flex-shrink:0;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div><!-- /.consult-form-area -->
                </div><!-- /.consult-form-outer -->

                <div id="consultFormActions" style="opacity:.4;pointer-events:none;">
                    <button type="submit" class="primary-button">
                        <i class="ti ti-device-floppy"></i> Save Consultation
                    </button>
                    <button type="button" class="secondary-button" id="clearConsultForm">
                        <i class="ti ti-eraser"></i> Clear Form
                    </button>
                </div>

            </form>

        </section><!-- /#consultationPanel -->
    </main>
</div>

<div class="toast" id="consultToast"></div>

<script src="assets/js/app.js"></script>
<script src="assets/js/consultation.js"></script>
</body>
</html>