<?php
session_start();

if (!isset($_SESSION['patient_id']) && !isset($_SESSION['UserID'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$patientName = $_SESSION['patient_name'] ?? 'User';

require_once __DIR__ . '/../../includes/module_guard.php';
requireModule('Consultation', 'access');

$activeSidebarItem = 'consultation';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE | Consultation</title>
    <link rel="icon" href="/NUcare_Health_system/assets/image/nucarelogo.png">
    <link rel="stylesheet" href="../../assets/css/app.css?v=1">
    <link rel="stylesheet" href="../../assets/css/consultation.css?v=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="app-shell">

    <?php
    $sidebarPath = __DIR__ . '/../../includes/sidebar_medical_staff.php';
    if (file_exists($sidebarPath)) require_once $sidebarPath;
    ?>

    <main class="main-content">
    <div class="consult-page">

        <!-- ══ PAGE HEADER ══ -->
        <div class="consult-page-header">
            <div class="page-header-left">
                <nav class="breadcrumb">
                    <span>Home</span>
                    <i class="fa-solid fa-chevron-right"></i>
                    <span class="bc-active">Consultation</span>
                </nav>
                <h1 class="page-title">Patient Consultation</h1>
                <p class="page-desc">Search for a patient, review details, and record consultation data in one clean workspace.</p>
            </div>
            <div class="page-header-right">
                
                <a href="../../auth/logout.php" class="btn-logout">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    Logout
                </a>
            </div>
        </div>

        <!-- ══ FIND PATIENT ══ -->
        <div class="consult-card">
            <div class="card-section-label">
                <i class="fa-solid fa-magnifying-glass"></i>
                Find Patient
            </div>
            <p class="card-section-desc">Enter a patient ID number or full name to begin a consultation.</p>

            <div class="search-row">
                <div class="search-input-wrap">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input
                        type="text"
                        id="consultSearchInput"
                        placeholder="Search patient ID or name…"
                        onkeydown="if(event.key==='Enter') searchPatient()"
                    >
                </div>
                <button class="btn-search-patient" type="button" onclick="searchPatient()">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    Search Patient
                </button>
            </div>

            <div id="searchFeedback" class="search-feedback"></div>
        </div>

        <!-- ══ PATIENT CARD ══ -->
        <div class="patient-banner" id="consultPatientCard">
            <div class="patient-banner-avatar">
                <i class="fa-solid fa-user-nurse"></i>
            </div>
            <div class="patient-banner-body">
                <div class="patient-banner-name-row">
                    <h2 id="cpcName">—</h2>
                    <span class="status-badge active">Active</span>
                </div>
                <div class="patient-banner-meta">
                    <div class="meta-field">
                        <span class="meta-label">Patient ID</span>
                        <span class="meta-val" id="cpcID">—</span>
                    </div>
                    <div class="meta-field">
                        <span class="meta-label">Sex</span>
                        <span class="meta-val" id="cpcSex">—</span>
                    </div>
                    <div class="meta-field">
                        <span class="meta-label">Birthday</span>
                        <span class="meta-val" id="cpcBday">—</span>
                    </div>
                    <div class="meta-field">
                        <span class="meta-label">Program</span>
                        <span class="meta-val" id="cpcProgram">—</span>
                    </div>
                    <div class="meta-field">
                        <span class="meta-label">Contact No.</span>
                        <span class="meta-val" id="cpcTel">—</span>
                    </div>
                    <div class="meta-field">
                        <span class="meta-label">Loaded At</span>
                        <span class="meta-val" id="cpcTime">—</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ CONSULTATION FORM ══ -->
        <div class="consult-form-outer">
            <div id="disabledOverlay" class="show">
                <i class="fa-solid fa-lock"></i>
                Search for a patient first to activate the form
            </div>

            <form id="consultationForm"
                  method="POST"
                  action="../../ajax/consultation/save_consultation.ajax.php"
                  enctype="multipart/form-data"
                  class="consult-form-area disabled"
                  id="consultFormArea">

                <input type="hidden" id="consultPatientID" name="patient_id">

                <!-- Vitals -->
                <div class="consult-card">
                    <div class="card-section-label">
                        <i class="fa-solid fa-heart-pulse"></i>
                        Vital Signs
                    </div>
                    <div class="vitals-grid">
                        <div class="form-group">
                            <label for="consultBP">Blood Pressure</label>
                            <input type="text" id="consultBP" name="blood_pressure" placeholder="e.g. 120/80">
                        </div>
                        <div class="form-group">
                            <label for="consultTemp">Temperature (°C)</label>
                            <input type="text" id="consultTemp" name="temperature" placeholder="e.g. 36.6">
                        </div>
                        <div class="form-group">
                            <label for="consultPulse">Pulse Rate</label>
                            <input type="text" id="consultPulse" name="pulse_rate" placeholder="e.g. 75 bpm">
                        </div>
                        <div class="form-group">
                            <label for="consultWeight">Weight (kg)</label>
                            <input type="number" id="consultWeight" name="weight" step="0.1" placeholder="e.g. 55">
                        </div>
                    </div>
                </div>

                <!-- Complaint & Service -->
                <div class="consult-card">
                    <div class="card-section-label">
                        <i class="fa-solid fa-clipboard-list"></i>
                        Consultation Details
                    </div>
                    <div class="consult-details-grid">

                        <div class="form-group">
                            <label for="consultConcern">Chief Complaint / Concern <span class="req">*</span></label>
                            <textarea id="consultConcern" name="complaint" rows="3"
                                      placeholder="Describe the patient's main concern…"></textarea>
                            <span class="err-msg" id="consultConcernErr"></span>
                        </div>

                        <div class="form-group">
                            <label for="consultService">Service Type <span class="req">*</span></label>
                            <select id="consultService" name="service_type">
                                <option value="">— Select service —</option>
                                <option>General Consultation</option>
                                <option>Dental</option>
                                <option>First Aid</option>
                                <option>Medical Certificate</option>
                                <option>Immunization</option>
                                <option>Laboratory</option>
                                <option>Physical Examination</option>
                                <option>Other</option>
                            </select>
                            <span class="err-msg" id="consultServiceErr"></span>
                        </div>

                        <div class="form-group" id="otherServiceWrap" style="display:none;">
                            <label for="consultServiceOther">Specify Other Service</label>
                            <input type="text" id="consultServiceOther" name="service_other"
                                   placeholder="Specify service…">
                        </div>

                        <div class="form-group">
                            <label for="consultStatus">Consultation Status</label>
                            <select id="consultStatus" name="consultation_status">
                                <option value="Waiting">Waiting</option>
                                <option value="Consulting">Consulting</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>

                        <div class="form-group form-group--full">
                            <label for="consultNotes">Clinical Notes / Assessment</label>
                            <textarea id="consultNotes" name="notes" rows="3"
                                      placeholder="Findings, assessment, treatment plan…"></textarea>
                        </div>

                    </div>
                </div>

                <!-- Medicines Dispensed -->
                <div class="consult-card">
                    <div class="card-section-label">
                        <i class="fa-solid fa-pills"></i>
                        Medicines Dispensed
                        <span class="section-note">Optional — only fill if medicine was given</span>
                    </div>

                    <div id="medsList">
                        <div class="med-entry" id="med-0">
                            <div class="form-group med-name-group">
                                <label for="consultMedName0">Medicine Name</label>
                                <input type="text" id="consultMedName0" name="consultMedName[]"
                                       placeholder="Medicine name">
                                <span class="err-msg" id="medNameErr0"></span>
                            </div>
                            <div class="form-group med-qty-group">
                                <label for="consultMedQty0">Qty</label>
                                <input type="number" id="consultMedQty0" name="consultMedQty[]"
                                       placeholder="0" min="1">
                                <span class="err-msg" id="medQtyErr0"></span>
                            </div>
                            <div class="med-spacer"></div>
                        </div>
                    </div>

                    <button type="button" class="btn-add-med" onclick="addConsultMed()">
                        <i class="fa-solid fa-plus"></i>
                        Add Another Medicine
                    </button>
                </div>

                <!-- PDF Upload -->
                <div class="consult-card">
                    <div class="card-section-label">
                        <i class="fa-solid fa-file-pdf"></i>
                        Attach Document
                        <span class="section-note">Optional — PDF only, max 10 MB</span>
                    </div>
                    <p class="card-section-desc">Upload a supporting document such as a lab result, referral, or medical certificate.</p>

                    <div class="pdf-upload-zone" id="pdfUploadZone">
                        <input type="file"
                               id="consultPdfFile"
                               name="consultation_pdf"
                               accept="application/pdf"
                               onchange="handlePdfSelect(this)">
                        <i class="fa-solid fa-file-arrow-up pdf-upload-icon"></i>
                        <span class="pdf-upload-label" id="pdfUploadLabel">Click to upload or drag &amp; drop a PDF</span>
                        <span class="pdf-upload-hint">PDF format · Maximum 10 MB</span>
                    </div>

                    <div class="pdf-file-preview" id="pdfFilePreview">
                        <div class="pdf-file-icon"><i class="fa-solid fa-file-pdf"></i></div>
                        <div class="pdf-file-info">
                            <div class="pdf-file-name" id="pdfFileName">—</div>
                            <div class="pdf-file-size" id="pdfFileSize">—</div>
                        </div>
                        <button type="button" class="btn-remove-pdf" onclick="removePdf()" title="Remove file">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="pdf-err-msg" id="pdfErrMsg">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span id="pdfErrText"></span>
                    </div>
                </div>

                <!-- Actions -->
                <div id="consultFormActions" class="consult-actions">
                    <button type="submit" class="btn-save-consult">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Save Consultation
                    </button>
                    <button type="button" class="btn-clear-consult" id="clearConsultForm">
                        <i class="fa-solid fa-rotate-left"></i>
                        Clear Form
                    </button>
                </div>

            </form>
        </div><!-- /.consult-form-outer -->

    </div><!-- /.consult-page -->

    <!-- Toast -->
    <div id="consultToast" class="consult-toast"></div>

    </main>
</div>

<script src="../../assets/js/app.js"></script>
<script src="../../assets/js/consultation.js"></script>
</body>
</html>