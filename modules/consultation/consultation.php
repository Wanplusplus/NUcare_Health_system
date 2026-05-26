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
    <?php
    // <base href> pins all relative URLs to the real page location,
    // regardless of how the router rewrites the URL.
    // SCRIPT_NAME = /NUcare_Health_system/pages/clinic/consultation.php
    // base = /NUcare_Health_system/pages/clinic/
    $baseHref = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';
    ?>
    <base href="<?= htmlspecialchars('http://' . $_SERVER['HTTP_HOST'] . $baseHref) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE | Consultation</title>
    <link rel="icon" href="/NUcare_Health_system/assets/image/nucarelogo.png">
    <link rel="stylesheet" href="../../assets/css/app.css?v=1">
    <link rel="stylesheet" href="../../assets/css/consultation.css?v=3">
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
                <span class="service-badge" id="serviceTypeBadge" style="display:none;"></span>
            </div>
        </div>

        <!-- ══ FIND PATIENT ══ -->
        <div class="consult-card">
            <div class="card-section-label">
                <i class="fa-solid fa-magnifying-glass"></i>
                Find Patient
            </div>
            <p class="card-section-desc">Enter a School ID or patient name to begin a consultation.</p>

            <div class="search-row">
                <div class="search-input-wrap" style="position:relative;">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input
                        type="text"
                        id="consultSearchInput"
                        placeholder="Search School ID or name…"
                        autocomplete="off"
                        onkeydown="if(event.key==='Enter'){ event.preventDefault(); searchPatient(); }"
                        oninput="onSearchInput(this.value)"
                    >
                    <ul class="search-autocomplete" id="searchAutocomplete"></ul>
                </div>
                <button class="btn-search-patient" type="button" onclick="searchPatient()">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    Search Patient
                </button>
            </div>

            <div id="searchFeedback" class="search-feedback"></div>
        </div>

        <!-- ══ PATIENT BANNER ══ -->
        <div class="patient-banner" id="consultPatientCard">
            <div class="patient-banner-avatar" id="patientAvatarInitials">
                <i class="fa-solid fa-user-nurse"></i>
            </div>
            <div class="patient-banner-body">
                <div class="patient-banner-name-row">
                    <h2 id="cpcName">—</h2>
                    <span class="status-badge active">Active</span>
                </div>
                <div class="patient-banner-meta">
                    <div class="meta-field">
                        <span class="meta-label">School ID</span>
                        <span class="meta-val" id="cpcID">—</span>
                    </div>
                    <div class="meta-field">
                        <span class="meta-label">Sex</span>
                        <span class="meta-val" id="cpcSex">—</span>
                    </div>
                    <div class="meta-field">
                        <span class="meta-label">Age</span>
                        <span class="meta-val" id="cpcAge">—</span>
                    </div>
                    <div class="meta-field">
                        <span class="meta-label">Type</span>
                        <span class="meta-val" id="cpcType">—</span>
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

            <!-- ══ HISTORY-EXISTS MODAL (3-button) ══ -->
            <div id="txConfirmModal" class="modal" style="display:none;">
                <div class="modal-overlay" onclick="closeTxConfirm()"></div>
                <div class="modal-card modal-card--choice">
                    <div class="modal-head">
                        <div class="modal-title">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                            Existing Consultation Records Found
                        </div>
                        <button type="button" class="modal-close" onclick="closeTxConfirm()" aria-label="Close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p id="txConfirmText">This patient already has consultation history. How would you like to proceed?</p>
                        <div class="modal-sub">All previous consultations are preserved and read-only.</div>
                    </div>
                    <div class="modal-actions modal-actions--col">
                        <button type="button" class="btn-modal btn-choice btn-choice--new" onclick="confirmAddAnother(true)">
                            <span class="btn-choice-icon"><i class="fa-solid fa-plus"></i></span>
                            <span class="btn-choice-body">
                                <span class="btn-choice-label">New Transaction</span>
                                <span class="btn-choice-desc">Start a fresh consultation for this visit</span>
                            </span>
                        </button>
                        <button type="button" class="btn-modal btn-choice btn-choice--history" onclick="openPatientHistoryModal()">
                            <span class="btn-choice-icon"><i class="fa-solid fa-folder-open"></i></span>
                            <span class="btn-choice-body">
                                <span class="btn-choice-label">View Previous Transactions</span>
                                <span class="btn-choice-desc">Browse and open past consultation records</span>
                            </span>
                        </button>
                        <button type="button" class="btn-modal btn-choice btn-choice--cancel" onclick="closeTxConfirm()">
                            <span class="btn-choice-icon"><i class="fa-solid fa-xmark"></i></span>
                            <span class="btn-choice-body">
                                <span class="btn-choice-label">Cancel</span>
                                <span class="btn-choice-desc">Go back without starting a transaction</span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ══ PATIENT HISTORY MODAL ══ -->
            <div id="patientHistoryModal" class="modal" style="display:none;">
                <div class="modal-overlay" onclick="closePatientHistoryModal()"></div>
                <div class="modal-card modal-card--history">
                    <div class="modal-head modal-head--history">
                        <div>
                            <div class="modal-title">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                                Consultation History
                            </div>
                            <div class="modal-sub" id="histModalPatientName" style="margin-top:2px;color:var(--gray-400);font-size:.78rem;"></div>
                        </div>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <div class="modal-hist-search">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="text" id="modalHistSearchInput" placeholder="Search complaint, service…" oninput="onModalHistSearch(this.value)">
                            </div>
                            <button type="button" class="modal-close" onclick="closePatientHistoryModal()" aria-label="Close">&times;</button>
                        </div>
                    </div>
                    <div class="modal-hist-body" id="modalHistBody">
                        <div class="modal-hist-loading">
                            <i class="fa-solid fa-spinner fa-spin"></i> Loading records…
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══ TRANSACTION DETAIL MODAL (READ-ONLY) ══ -->
            <div id="txDetailModal" class="modal" style="display:none;">
                <div class="modal-overlay" onclick="closeTxDetailModal()"></div>
                <div class="modal-card modal-card--detail">
                    <div class="modal-head modal-head--detail">
                        <div>
                            <div class="modal-title" id="txDetailModalTitle">Transaction Details</div>
                            <div class="modal-sub" id="txDetailModalSub" style="margin-top:2px;color:var(--gray-400);font-size:.78rem;"></div>
                        </div>
                        <div style="display:flex;gap:8px;align-items:center;flex-shrink:0;">
                            <button type="button" class="btn-modal-action btn-modal-back" onclick="closeTxDetailModal(); openPatientHistoryModal();">
                                <i class="fa-solid fa-arrow-left"></i> Back
                            </button>
                            <button type="button" class="modal-close" onclick="closeTxDetailModal()" aria-label="Close">&times;</button>
                        </div>
                    </div>
                    <div class="modal-detail-body" id="txDetailModalBody">
                        <div class="modal-hist-loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading…</div>
                    </div>
                </div>
            </div>

            <!-- Attachment image preview lightbox -->
            <div id="attachPreviewModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.82);align-items:center;justify-content:center;flex-direction:column;gap:14px;" onclick="if(event.target===this)closeAttachmentPreview()">
                <div style="display:flex;align-items:center;gap:12px;width:min(92vw,820px);">
                    <span id="attachPreviewName" style="color:#fff;font-size:.9rem;font-weight:600;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>
                    <a id="attachPreviewDl" href="#" download style="color:#fff;background:rgba(255,255,255,.15);border-radius:8px;padding:7px 14px;font-size:.8rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:6px;"><i class="fa-solid fa-download"></i> Download</a>
                    <button onclick="closeAttachmentPreview()" style="background:rgba(255,255,255,.15);border:none;border-radius:8px;color:#fff;width:36px;height:36px;cursor:pointer;font-size:1.1rem;display:flex;align-items:center;justify-content:center;"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <img id="attachPreviewImg" src="" alt="Attachment preview" style="max-width:min(92vw,820px);max-height:80vh;border-radius:10px;object-fit:contain;box-shadow:0 8px 40px rgba(0,0,0,.5);">
            </div>

            <!-- Form lock overlay -->
            <div id="disabledOverlay" class="show">
                <i class="fa-solid fa-lock"></i>
                Search for a patient first to activate the form
            </div>

            <form id="consultationForm"
                  method="POST"
                  action="../../ajax/consultation/save_consultation.ajax.php"
                  enctype="multipart/form-data"
                  class="consult-form-area disabled">

                <input type="hidden" id="consultPatientID"  name="school_person_id">
                <input type="hidden" id="consultationID"    name="consultation_id" value="">

                <!-- ════════════════════════════════════════
                     SECTION: VITAL SIGNS
                     Shown for: General Consultation, First Aid, Physical Examination
                     Hidden for: Dental, Medical Certificate
                ════════════════════════════════════════ -->
                <div class="consult-card" id="section-vitals" data-section="vitals">
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
                            <input type="number" id="consultTemp" name="temperature" step="0.1" placeholder="e.g. 36.6">
                        </div>
                        <div class="form-group">
                            <label for="consultPulse">Pulse Rate (bpm)</label>
                            <input type="number" id="consultPulse" name="pulse_rate" step="1" placeholder="e.g. 75">
                        </div>
                        <div class="form-group">
                            <label for="consultWeight">Weight (kg)</label>
                            <input type="number" id="consultWeight" name="weight" step="0.1" placeholder="e.g. 55" oninput="calcBMI()">
                        </div>
                        <div class="form-group">
                            <label for="consultHeight">Height (cm)</label>
                            <input type="number" id="consultHeight" name="height" step="0.1" placeholder="e.g. 160" oninput="calcBMI()">
                        </div>
                        <!-- BMI auto-calculated -->
                        <div class="form-group">
                            <label>BMI</label>
                            <div class="bmi-display" id="bmiDisplay">
                                <span class="bmi-val" id="bmiValue">—</span>
                                <span class="bmi-label" id="bmiCategory"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ════════════════════════════════════════
                     SECTION: CONSULTATION DETAILS
                     Hidden for: Physical Examination, Medical Certificate
                ════════════════════════════════════════ -->
                <div class="consult-card" id="section-consultation-details" data-section="consultation-details">
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
                            <select id="consultService" name="service_type" onchange="onServiceTypeChange(this.value)">
                                <option value="">— Select service —</option>
                                <option>General Consultation</option>
                                <option>Dental</option>
                                <option>First Aid</option>
                                <option>Medical Certificate</option>
                                <option>Physical Examination</option>
                                <option>Other</option>
                            </select>
                            <span class="err-msg" id="consultServiceErr"></span>
                        </div>

                        <div class="form-group" id="otherServiceWrap" style="display:none;">
                            <label for="consultServiceOther">Specify Other Service</label>
                            <input type="text" id="consultServiceOther" name="service_other" placeholder="Specify service…">
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

                <!-- ════════════════════════════════════════
                     SECTION: DENTAL (Service-specific)
                     Shown only when Service Type = Dental
                ════════════════════════════════════════ -->
                <div class="consult-card hidden" id="section-dental" data-section="dental">
                    <div class="card-section-label">
                        <i class="fa-solid fa-tooth"></i>
                        Dental Information
                    </div>
                    <div class="dental-grid">
                        <div class="form-group">
                            <label for="dentalToothConcern">Tooth Concern</label>
                            <input type="text" id="dentalToothConcern" name="tooth_concern" placeholder="e.g. Upper right molar, tooth #16">
                        </div>
                        <div class="form-group">
                            <label for="dentalProcedure">Procedure Done</label>
                            <select id="dentalProcedure" name="dental_procedure">
                                <option value="">— Select procedure —</option>
                                <option>Tooth Extraction</option>
                                <option>Filling / Restoration</option>
                                <option>Cleaning / Prophylaxis</option>
                                <option>Oral Examination</option>
                                <option>X-Ray</option>
                                <option>Fluoride Treatment</option>
                                <option>Root Canal</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="form-group form-group--full">
                            <label for="dentalNotes">Dentist Notes</label>
                            <textarea id="dentalNotes" name="dentist_notes" rows="3"
                                      placeholder="Additional notes, observations, next appointment…"></textarea>
                        </div>
                    </div>
                </div>

                <!-- ════════════════════════════════════════
                     SECTION: FIRST AID (Service-specific)
                     Shown only when Service Type = First Aid
                ════════════════════════════════════════ -->
                <div class="consult-card hidden" id="section-firstaid" data-section="firstaid">
                    <div class="card-section-label">
                        <i class="fa-solid fa-kit-medical"></i>
                        First Aid Details
                    </div>
                    <div class="firstaid-grid">
                        <div class="form-group">
                            <label for="faInjuryType">Injury Type</label>
                            <select id="faInjuryType" name="injury_type">
                                <option value="">— Select type —</option>
                                <option>Laceration / Cut</option>
                                <option>Bruise / Contusion</option>
                                <option>Sprain / Strain</option>
                                <option>Burn</option>
                                <option>Fracture (suspected)</option>
                                <option>Head Injury</option>
                                <option>Eye Injury</option>
                                <option>Allergic Reaction</option>
                                <option>Fainting / Syncope</option>
                                <option>Nosebleed</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="faLocation">Incident Location</label>
                            <input type="text" id="faLocation" name="incident_location" placeholder="e.g. Gymnasium, Cafeteria, Room 301">
                        </div>
                        <div class="form-group form-group--full">
                            <label for="faAction">Immediate Action Taken</label>
                            <textarea id="faAction" name="immediate_action_taken" rows="3"
                                      placeholder="Describe the first aid given, medications applied, referral made…"></textarea>
                        </div>
                    </div>
                </div>

                <!-- ════════════════════════════════════════
                     SECTION: PHYSICAL EXAMINATION
                     Shown only when Service Type = Physical Examination
                     Replaces Consultation Details + Medicine for this service type
                ════════════════════════════════════════ -->
                <div class="physexam-card hidden" id="section-physical-exam" data-section="physical-exam">
                    <div class="physexam-header">
                        <div>
                            <div class="physexam-header-title">
                                <i class="fa-solid fa-stethoscope"></i>
                                Physical Examination Form
                            </div>
                            <div class="physexam-header-desc">Complete the systematic body systems review. Mark each finding as Normal or Abnormal.</div>
                        </div>
                        <span class="physexam-header-badge">
                            <i class="fa-solid fa-shield-halved"></i> Confidential Medical Record
                        </span>
                    </div>

                    <div class="physexam-body">

                        <!-- ── Exam Date ── -->
                        <div class="pe-section">
                            <div class="pe-section-label">
                                <div class="pe-section-label-icon"><i class="fa-solid fa-calendar-days"></i></div>
                                <span class="pe-section-label-text">Examination Date</span>
                            </div>
                            <div class="pe-vitals-grid">
                                <div class="form-group">
                                    <label for="examDate">Date of Examination <span class="req">*</span></label>
                                    <input type="date" id="examDate" name="exam_date" value="<?= date('Y-m-d') ?>">
                                </div>
                            </div>
                        </div>

                        <!-- ── PE Vitals ── -->
                        <div class="pe-section">
                            <div class="pe-section-label">
                                <div class="pe-section-label-icon"><i class="fa-solid fa-heart-pulse"></i></div>
                                <span class="pe-section-label-text">Vital Signs</span>
                            </div>
                            <div class="pe-vitals-grid">
                                <div class="form-group">
                                    <label for="peHeight">Height (cm)</label>
                                    <input type="number" id="peHeight" name="pe_height" step="0.1" placeholder="e.g. 165" oninput="calcBMI()">
                                </div>
                                <div class="form-group">
                                    <label for="peWeight">Weight (kg)</label>
                                    <input type="number" id="peWeight" name="pe_weight" step="0.1" placeholder="e.g. 60" oninput="calcBMI()">
                                </div>
                                <div class="form-group">
                                    <label for="peBP">Blood Pressure</label>
                                    <input type="text" id="peBP" name="pe_blood_pressure" placeholder="e.g. 120/80">
                                </div>
                                <div class="form-group">
                                    <label for="peTemp">Temperature (°C)</label>
                                    <input type="number" id="peTemp" name="pe_temperature" step="0.1" placeholder="e.g. 36.6">
                                </div>
                                <div class="form-group">
                                    <label for="pePulse">Pulse Rate (bpm)</label>
                                    <input type="number" id="pePulse" name="pe_pulse_rate" step="1" placeholder="e.g. 75">
                                </div>
                                <!-- BMI Box -->
                                <div class="bmi-display-box">
                                    <div class="bmi-display-label">Body Mass Index (BMI)</div>
                                    <div class="bmi-display-value" id="bmiValue">—</div>
                                    <div class="bmi-display-cat" id="bmiCategory"></div>
                                </div>
                            </div>
                        </div>

                        <!-- ── Head & Sensory ── -->
                        <div class="pe-section">
                            <div class="pe-section-label">
                                <div class="pe-section-label-icon"><i class="fa-solid fa-brain"></i></div>
                                <span class="pe-section-label-text">Head &amp; Sensory</span>
                            </div>
                            <div class="pe-exam-grid">

                                <!-- Ears -->
                                <div class="exam-field-group">
                                    <div class="exam-field-label">
                                        <span>Ears</span>
                                        <div class="exam-badge-row">
                                            <button type="button" class="exam-badge-btn normal"
                                                    data-field="examEars" data-status="normal"
                                                    onclick="toggleExamBadge('examEars','normal')">
                                                <i class="fa-solid fa-check"></i> Normal
                                            </button>
                                            <button type="button" class="exam-badge-btn abnormal"
                                                    data-field="examEars" data-status="abnormal"
                                                    onclick="toggleExamBadge('examEars','abnormal')">
                                                <i class="fa-solid fa-triangle-exclamation"></i> Abnormal
                                            </button>
                                        </div>
                                    </div>
                                    <select class="exam-field-input exam-select" id="examEars" name="exam_ears">
                                        <option value="">— Select finding —</option>
                                        <option value="Normal">Normal</option>
                                        <option value="Cerumen impaction">Cerumen impaction</option>
                                        <option value="Otitis media">Otitis media</option>
                                        <option value="Tympanic membrane perforation">Tympanic membrane perforation</option>
                                        <option value="Hearing loss">Hearing loss</option>
                                        <option value="Other abnormality">Other abnormality</option>
                                    </select>
                                </div>

                                <!-- Eyes / Pupil -->
                                <div class="exam-field-group">
                                    <div class="exam-field-label">
                                        <span>Eyes / Pupil</span>
                                        <div class="exam-badge-row">
                                            <button type="button" class="exam-badge-btn normal"
                                                    data-field="examEyesPupil" data-status="normal"
                                                    onclick="toggleExamBadge('examEyesPupil','normal')">
                                                <i class="fa-solid fa-check"></i> Normal
                                            </button>
                                            <button type="button" class="exam-badge-btn abnormal"
                                                    data-field="examEyesPupil" data-status="abnormal"
                                                    onclick="toggleExamBadge('examEyesPupil','abnormal')">
                                                <i class="fa-solid fa-triangle-exclamation"></i> Abnormal
                                            </button>
                                        </div>
                                    </div>
                                    <select class="exam-field-input exam-select" id="examEyesPupil" name="exam_eyes_pupil">
                                        <option value="">— Select finding —</option>
                                        <option value="PERRLA">PERRLA (Normal)</option>
                                        <option value="Anisocoria">Anisocoria</option>
                                        <option value="Conjunctivitis">Conjunctivitis</option>
                                        <option value="Papilledema">Papilledema</option>
                                        <option value="Visual field defect">Visual field defect</option>
                                        <option value="Other abnormality">Other abnormality</option>
                                    </select>
                                </div>

                                <!-- Nose -->
                                <div class="exam-field-group">
                                    <div class="exam-field-label">
                                        <span>Nose</span>
                                        <div class="exam-badge-row">
                                            <button type="button" class="exam-badge-btn normal"
                                                    data-field="examNose" data-status="normal"
                                                    onclick="toggleExamBadge('examNose','normal')">
                                                <i class="fa-solid fa-check"></i> Normal
                                            </button>
                                            <button type="button" class="exam-badge-btn abnormal"
                                                    data-field="examNose" data-status="abnormal"
                                                    onclick="toggleExamBadge('examNose','abnormal')">
                                                <i class="fa-solid fa-triangle-exclamation"></i> Abnormal
                                            </button>
                                        </div>
                                    </div>
                                    <select class="exam-field-input exam-select" id="examNose" name="exam_nose">
                                        <option value="">— Select finding —</option>
                                        <option value="Normal">Normal</option>
                                        <option value="Nasal polyps">Nasal polyps</option>
                                        <option value="Deviated septum">Deviated septum</option>
                                        <option value="Rhinitis">Rhinitis</option>
                                        <option value="Sinusitis">Sinusitis</option>
                                        <option value="Other abnormality">Other abnormality</option>
                                    </select>
                                </div>

                            </div>
                        </div>

                        <!-- ── Cardiovascular & Respiratory ── -->
                        <div class="pe-section">
                            <div class="pe-section-label">
                                <div class="pe-section-label-icon"><i class="fa-solid fa-lungs"></i></div>
                                <span class="pe-section-label-text">Cardiovascular &amp; Respiratory</span>
                            </div>
                            <div class="pe-exam-grid">

                                <!-- Heart -->
                                <div class="exam-field-group">
                                    <div class="exam-field-label">
                                        <span>Heart</span>
                                        <div class="exam-badge-row">
                                            <button type="button" class="exam-badge-btn normal"
                                                    data-field="examHeart" data-status="normal"
                                                    onclick="toggleExamBadge('examHeart','normal')">
                                                <i class="fa-solid fa-check"></i> Normal
                                            </button>
                                            <button type="button" class="exam-badge-btn abnormal"
                                                    data-field="examHeart" data-status="abnormal"
                                                    onclick="toggleExamBadge('examHeart','abnormal')">
                                                <i class="fa-solid fa-triangle-exclamation"></i> Abnormal
                                            </button>
                                        </div>
                                    </div>
                                    <select class="exam-field-input exam-select" id="examHeart" name="exam_heart">
                                        <option value="">— Select finding —</option>
                                        <option value="Regular rate and rhythm">Regular rate and rhythm (Normal)</option>
                                        <option value="Murmur">Murmur</option>
                                        <option value="Arrhythmia">Arrhythmia</option>
                                        <option value="Bradycardia">Bradycardia</option>
                                        <option value="Tachycardia">Tachycardia</option>
                                        <option value="Other abnormality">Other abnormality</option>
                                    </select>
                                </div>

                                <!-- Lungs -->
                                <div class="exam-field-group">
                                    <div class="exam-field-label">
                                        <span>Lungs</span>
                                        <div class="exam-badge-row">
                                            <button type="button" class="exam-badge-btn normal"
                                                    data-field="examLungs" data-status="normal"
                                                    onclick="toggleExamBadge('examLungs','normal')">
                                                <i class="fa-solid fa-check"></i> Normal
                                            </button>
                                            <button type="button" class="exam-badge-btn abnormal"
                                                    data-field="examLungs" data-status="abnormal"
                                                    onclick="toggleExamBadge('examLungs','abnormal')">
                                                <i class="fa-solid fa-triangle-exclamation"></i> Abnormal
                                            </button>
                                        </div>
                                    </div>
                                    <select class="exam-field-input exam-select" id="examLungs" name="exam_lungs">
                                        <option value="">— Select finding —</option>
                                        <option value="Clear to auscultation">Clear to auscultation (Normal)</option>
                                        <option value="Wheezing">Wheezing</option>
                                        <option value="Crackles / Rales">Crackles / Rales</option>
                                        <option value="Rhonchi">Rhonchi</option>
                                        <option value="Diminished breath sounds">Diminished breath sounds</option>
                                        <option value="Other abnormality">Other abnormality</option>
                                    </select>
                                </div>

                                <!-- Thorax -->
                                <div class="exam-field-group">
                                    <div class="exam-field-label">
                                        <span>Thorax</span>
                                        <div class="exam-badge-row">
                                            <button type="button" class="exam-badge-btn normal"
                                                    data-field="examThorax" data-status="normal"
                                                    onclick="toggleExamBadge('examThorax','normal')">
                                                <i class="fa-solid fa-check"></i> Normal
                                            </button>
                                            <button type="button" class="exam-badge-btn abnormal"
                                                    data-field="examThorax" data-status="abnormal"
                                                    onclick="toggleExamBadge('examThorax','abnormal')">
                                                <i class="fa-solid fa-triangle-exclamation"></i> Abnormal
                                            </button>
                                        </div>
                                    </div>
                                    <select class="exam-field-input exam-select" id="examThorax" name="exam_thorax">
                                        <option value="">— Select finding —</option>
                                        <option value="Symmetric expansion">Symmetric expansion (Normal)</option>
                                        <option value="Asymmetric expansion">Asymmetric expansion</option>
                                        <option value="Barrel chest">Barrel chest</option>
                                        <option value="Kyphoscoliosis">Kyphoscoliosis</option>
                                        <option value="Other abnormality">Other abnormality</option>
                                    </select>
                                </div>

                            </div>
                        </div>

                        <!-- ── Abdomen & Extremities ── -->
                        <div class="pe-section">
                            <div class="pe-section-label">
                                <div class="pe-section-label-icon"><i class="fa-solid fa-person"></i></div>
                                <span class="pe-section-label-text">Abdomen &amp; Musculoskeletal</span>
                            </div>
                            <div class="pe-exam-grid">

                                <!-- Abdomen -->
                                <div class="exam-field-group">
                                    <div class="exam-field-label">
                                        <span>Abdomen</span>
                                        <div class="exam-badge-row">
                                            <button type="button" class="exam-badge-btn normal"
                                                    data-field="examAbdomen" data-status="normal"
                                                    onclick="toggleExamBadge('examAbdomen','normal')">
                                                <i class="fa-solid fa-check"></i> Normal
                                            </button>
                                            <button type="button" class="exam-badge-btn abnormal"
                                                    data-field="examAbdomen" data-status="abnormal"
                                                    onclick="toggleExamBadge('examAbdomen','abnormal')">
                                                <i class="fa-solid fa-triangle-exclamation"></i> Abnormal
                                            </button>
                                        </div>
                                    </div>
                                    <select class="exam-field-input exam-select" id="examAbdomen" name="exam_abdomen">
                                        <option value="">— Select finding —</option>
                                        <option value="Soft, non-tender">Soft, non-tender (Normal)</option>
                                        <option value="Tenderness on palpation">Tenderness on palpation</option>
                                        <option value="Hepatomegaly">Hepatomegaly</option>
                                        <option value="Splenomegaly">Splenomegaly</option>
                                        <option value="Ascites">Ascites</option>
                                        <option value="Rigidity / guarding">Rigidity / guarding</option>
                                        <option value="Other abnormality">Other abnormality</option>
                                    </select>
                                </div>

                                <!-- Skin -->
                                <div class="exam-field-group">
                                    <div class="exam-field-label">
                                        <span>Skin</span>
                                        <div class="exam-badge-row">
                                            <button type="button" class="exam-badge-btn normal"
                                                    data-field="examSkin" data-status="normal"
                                                    onclick="toggleExamBadge('examSkin','normal')">
                                                <i class="fa-solid fa-check"></i> Normal
                                            </button>
                                            <button type="button" class="exam-badge-btn abnormal"
                                                    data-field="examSkin" data-status="abnormal"
                                                    onclick="toggleExamBadge('examSkin','abnormal')">
                                                <i class="fa-solid fa-triangle-exclamation"></i> Abnormal
                                            </button>
                                        </div>
                                    </div>
                                    <select class="exam-field-input exam-select" id="examSkin" name="exam_skin">
                                        <option value="">— Select finding —</option>
                                        <option value="Normal color and turgor">Normal color and turgor (Normal)</option>
                                        <option value="Pallor">Pallor</option>
                                        <option value="Jaundice">Jaundice</option>
                                        <option value="Cyanosis">Cyanosis</option>
                                        <option value="Rash / Lesions">Rash / Lesions</option>
                                        <option value="Edema">Edema</option>
                                        <option value="Other abnormality">Other abnormality</option>
                                    </select>
                                </div>

                                <!-- Extremities -->
                                <div class="exam-field-group">
                                    <div class="exam-field-label">
                                        <span>Extremities</span>
                                        <div class="exam-badge-row">
                                            <button type="button" class="exam-badge-btn normal"
                                                    data-field="examExtremities" data-status="normal"
                                                    onclick="toggleExamBadge('examExtremities','normal')">
                                                <i class="fa-solid fa-check"></i> Normal
                                            </button>
                                            <button type="button" class="exam-badge-btn abnormal"
                                                    data-field="examExtremities" data-status="abnormal"
                                                    onclick="toggleExamBadge('examExtremities','abnormal')">
                                                <i class="fa-solid fa-triangle-exclamation"></i> Abnormal
                                            </button>
                                        </div>
                                    </div>
                                    <select class="exam-field-input exam-select" id="examExtremities" name="exam_extremities">
                                        <option value="">— Select finding —</option>
                                        <option value="Full range of motion">Full range of motion (Normal)</option>
                                        <option value="Limited ROM">Limited range of motion</option>
                                        <option value="Peripheral edema">Peripheral edema</option>
                                        <option value="Varicosities">Varicosities</option>
                                        <option value="Clubbing">Clubbing</option>
                                        <option value="Other abnormality">Other abnormality</option>
                                    </select>
                                </div>

                                <!-- Deformities -->
                                <div class="exam-field-group">
                                    <div class="exam-field-label">
                                        <span>Deformities</span>
                                        <div class="exam-badge-row">
                                            <button type="button" class="exam-badge-btn normal"
                                                    data-field="examDeformities" data-status="normal"
                                                    onclick="toggleExamBadge('examDeformities','normal')">
                                                <i class="fa-solid fa-check"></i> None
                                            </button>
                                            <button type="button" class="exam-badge-btn abnormal"
                                                    data-field="examDeformities" data-status="abnormal"
                                                    onclick="toggleExamBadge('examDeformities','abnormal')">
                                                <i class="fa-solid fa-triangle-exclamation"></i> Present
                                            </button>
                                        </div>
                                    </div>
                                    <select class="exam-field-input exam-select" id="examDeformities" name="exam_deformities">
                                        <option value="">— Select finding —</option>
                                        <option value="None">None noted (Normal)</option>
                                        <option value="Scoliosis">Scoliosis</option>
                                        <option value="Kyphosis">Kyphosis</option>
                                        <option value="Limb deformity">Limb deformity</option>
                                        <option value="Congenital defect">Congenital defect</option>
                                        <option value="Post-surgical deformity">Post-surgical deformity</option>
                                        <option value="Other">Other — specify in remarks</option>
                                    </select>
                                </div>

                            </div>
                        </div>

                        <!-- ── Medical Remarks ── -->
                        <div class="pe-section">
                            <div class="pe-section-label">
                                <div class="pe-section-label-icon"><i class="fa-solid fa-file-medical"></i></div>
                                <span class="pe-section-label-text">Medical Remarks</span>
                            </div>
                            <textarea id="examRemarks" name="exam_remarks" class="pe-remarks-area"
                                      rows="4" placeholder="Additional findings, observations, or recommendations from the examining physician…"></textarea>
                        </div>

                        <!-- ── Medical Clearance ── -->
                        <div class="pe-section">
                            <div class="pe-section-label">
                                <div class="pe-section-label-icon"><i class="fa-solid fa-shield-halved"></i></div>
                                <span class="pe-section-label-text">Medical Clearance Result</span>
                            </div>
                            <div class="clearance-section">
                                <div class="clearance-section-title">
                                    <i class="fa-solid fa-clipboard-check"></i>
                                    Cardio-Pulmonary Clearance Decision <span class="req">*</span>
                                </div>
                                <div class="clearance-options">
                                    <div class="clearance-option" data-value="Fit" onclick="selectClearance('Fit')">
                                        <i class="fa-solid fa-circle-check" style="color:#16a34a;"></i>
                                        <div>
                                            <div style="font-weight:800;">FIT</div>
                                            <div style="font-size:.72rem;color:var(--gray-500);">Cleared for activity</div>
                                        </div>
                                    </div>
                                    <div class="clearance-option" data-value="Unfit" onclick="selectClearance('Unfit')">
                                        <i class="fa-solid fa-circle-xmark" style="color:#dc2626;"></i>
                                        <div>
                                            <div style="font-weight:800;">UNFIT</div>
                                            <div style="font-size:.72rem;color:var(--gray-500);">Not cleared — needs treatment</div>
                                        </div>
                                    </div>
                                    <div class="clearance-option" data-value="Pending" onclick="selectClearance('Pending')">
                                        <i class="fa-solid fa-clock" style="color:#d97706;"></i>
                                        <div>
                                            <div style="font-weight:800;">PENDING</div>
                                            <div style="font-size:.72rem;color:var(--gray-500);">Awaiting further tests</div>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" id="examCardioClearance" name="exam_cardio_clearance" value="">
                            </div>
                        </div>

                    </div><!-- /.physexam-body -->
                </div><!-- /#section-physical-exam -->

                <div class="consult-card" id="section-medicines" data-section="medicines">
                    <div class="card-section-label">
                        <i class="fa-solid fa-pills"></i>
                        Medicines Dispensed
                        <span class="section-note">Optional — only fill if medicine was given</span>
                    </div>

                    <div id="medsList"></div>

                    <button type="button" class="btn-add-med" onclick="addMedRow()">
                        <i class="fa-solid fa-plus"></i>
                        Add Medicine
                    </button>
                </div>
    
                <div class="consult-card" id="section-attachment" data-section="attachment">
                    <div class="card-section-label">
                        <i class="fa-solid fa-paperclip"></i>
                        Attach Document
                        <span id="attachmentRequiredBadge" class="section-note" style="display:none;">
                            <span class="req">*</span> Required for Medical Certificate
                        </span>
                    </div>
                    <p class="card-section-desc">Upload a supporting document such as a lab result, referral letter, or medical certificate PDF.</p>

                    <div class="pdf-upload-zone" id="pdfUploadZone">
                        <input type="file"
                               id="consultAttachmentFile"
                               name="consultation_attachment"
                               accept="image/png,image/jpeg,application/pdf"
                               onchange="handleAttachmentSelect(this)">
                        <i class="fa-solid fa-file-arrow-up pdf-upload-icon"></i>
                        <span class="pdf-upload-label" id="pdfUploadLabel">Click to upload or drag &amp; drop JPG/PNG/PDF</span>
                        <span class="pdf-upload-hint">Allowed: JPG, PNG, PDF · Maximum 50 MB</span>
                    </div>

                    <div class="pdf-file-preview" id="pdfFilePreview">
                        <div class="pdf-file-icon"><i class="fa-solid fa-file-pdf"></i></div>
                        <div class="pdf-file-info">
                            <div class="pdf-file-name" id="pdfFileName">—</div>
                            <div class="pdf-file-size" id="pdfFileSize">—</div>
                        </div>
                        <button type="button" class="btn-remove-pdf" onclick="removeAttachment()" title="Remove file">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="pdf-err-msg" id="pdfErrMsg">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span id="pdfErrText"></span>
                    </div>

                    <!-- Attachment metadata (stored in consultation_attachments, 3NF) -->
                    <div class="attach-meta-row" id="attachMetaRow" style="display:none;">
                        <div class="form-group">
                            <label for="attachmentCategory">Document Category</label>
                            <select id="attachmentCategory" name="attachment_category" class="attach-category-select">
                                <option value="Other">Other</option>
                                <option value="Lab Result">Lab Result</option>
                                <option value="Medical Certificate">Medical Certificate</option>
                                <option value="Referral">Referral</option>
                                <option value="X-Ray">X-Ray</option>
                                <option value="Prescription">Prescription</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="attachmentNotes">Notes (optional)</label>
                            <input type="text" id="attachmentNotes" name="attachment_notes" placeholder="e.g. CBC result from St. Luke's">
                        </div>
                    </div>
                </div>

                <!-- ════════════════════════════════════════
                     SECTION: CONSULTATION HISTORY (EMR, READ-ONLY)
                     Always visible below the form
                ════════════════════════════════════════ -->
                <div class="consult-card consult-history-card">
                    <div class="card-section-label">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        Consultation History
                        <span class="section-note">Read-only — previous visits</span>
                    </div>

                    <!-- History toolbar: search + date range + print -->
                    <div class="history-toolbar">
                        <div class="history-search-wrap">
                            <i class="fa-solid fa-magnifying-glass" style="color:var(--gray-400);font-size:.8rem;"></i>
                            <input type="text" placeholder="Search complaint, service, or Tx #…" oninput="onHistorySearch(this.value)">
                        </div>
                        <div class="history-date-wrap">
                            <input type="date" id="historyDateFrom" onchange="filterHistoryByDate()" title="From date">
                            <span class="history-date-sep">→</span>
                            <input type="date" id="historyDateTo"   onchange="filterHistoryByDate()" title="To date">
                        </div>
                        <button type="button" class="btn-print-history" onclick="printHistory()" title="Print history">
                            <i class="fa-solid fa-print"></i> Print
                        </button>
                    </div>

                    <!-- Timeline view (primary) -->
                    <div class="history-timeline" id="historyTimeline">
                        <div class="muted" style="padding:20px 0;text-align:center;">
                            Search for a patient to view consultation history.
                        </div>
                    </div>

                    <!-- Read-only detail panel (slides in on click) -->
                    <div class="history-detail-panel" id="historyDetailPanel">
                        <div class="history-detail-header">
                            <h3 class="history-detail-title" id="historyDetailTitle">Transaction Details</h3>
                            <div class="history-detail-actions">
                                <button type="button" class="btn-print-history" onclick="printHistory()">
                                    <i class="fa-solid fa-print"></i> Print
                                </button>
                                <button type="button" class="btn-export-pdf" onclick="exportHistoryPDF()">
                                    <i class="fa-solid fa-file-pdf"></i> Export PDF
                                </button>
                                <button type="button" class="btn-clear-consult" onclick="closeHistoryDetail()" style="padding:7px 14px;font-size:.76rem;">
                                    <i class="fa-solid fa-xmark"></i> Close
                                </button>
                            </div>
                        </div>
                        <div id="historyDetailBody"></div>
                    </div>

                    <!-- Table fallback (legacy compatibility) -->
                    <div class="history-table-wrap" style="display:none;">
                        <table class="history-table" id="consultHistoryTable">
                            <thead>
                                <tr>
                                    <th>Tx #</th>
                                    <th>Date</th>
                                    <th>Service</th>
                                    <th>Complaint</th>
                                    <th>Status</th>
                                    <th>Medicines</th>
                                </tr>
                            </thead>
                            <tbody id="consultHistoryTbody">
                                <tr><td colspan="6" class="muted">Search for a patient to view history.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>


            </form>

            <!-- ── Sticky Save Panel — OUTSIDE the form so the disabled overlay never blocks it ── -->
            <div class="consult-actions-sticky">
                <div class="consult-actions-sticky-inner">
                    <button type="button" class="btn-save-consult" id="btnSaveConsult" onclick="submitConsultForm()">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Save Consultation
                    </button>
                    <button type="button" class="btn-clear-consult" id="clearConsultForm" onclick="clearForm()">
                        <i class="fa-solid fa-rotate-left"></i>
                        Clear
                    </button>
                </div>
            </div>

        </div><!-- /.consult-form-outer -->

    </div><!-- /.consult-page -->

    <!-- Toast -->
    <div id="consultToast" class="consult-toast"></div>

    </main>
</div>

<script src="../../assets/js/app.js"></script>
<script src="../../assets/js/consultation.js?v=3"></script>
</body>
</html>