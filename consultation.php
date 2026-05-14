<?php
/**
 * consultation.php - Patient consultation module
 * 
 * Allows medical staff to record patient consultations, vitals, and dispensed medicines.
 */
require_once __DIR__ . '/includes/auth_guard.php';
require_once __DIR__ . '/db_connect.php';

$activePage = 'consultation';
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

    <!-- ── Sidebar ── -->
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

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
                <a href="patients.php">
                    <button class="header-button accent" type="button">New Patient</button>
                </a>
                <a href="logout.php">
                    <button class="header-button outline" type="button">Logout</button>
                </a>
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
                                   placeholder="e.g. 1 or Maria Santos"
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
                <input type="hidden" id="consultMedProfID" name="consultMedProfID" value="<?php echo $_SESSION['user_id'] ?? 1; ?>">

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
                                    <div class="input-group">
                                        <label for="consultHeight">Height (cm)</label>
                                        <input type="text" id="consultHeight" name="consultHeight" placeholder="e.g. 165">
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
                                        <label for="consultConcern">Chief Concern / Complaint <span style="color:#ef4444">*</span></label>
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
                                        <label for="consultNotes">Diagnosis & Clinical Notes</label>
                                        <textarea id="consultNotes" name="consultNotes" rows="3"
                                                  placeholder="Diagnosis, treatment plan, observations..."></textarea>
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
                                            <select id="consultMedName0" name="consultMedName[]" class="medicine-select"
                                                    onchange="checkMedicineStock(this, 0)">
                                                <option value="">Select medicine</option>
                                                <?php
                                                // Load medicines from medicineandstuffs table
                                                $medResult = $conn->query(
                                                    "SELECT ItemID, ItemName, ItemStockQuantity 
                                                     FROM medicineandstuffs 
                                                     WHERE ItemStockQuantity > 0 
                                                     ORDER BY ItemName"
                                                );
                                                if ($medResult && $medResult->num_rows > 0) {
                                                    while ($med = $medResult->fetch_assoc()) {
                                                        echo '<option value="' . htmlspecialchars($med['ItemName']) . 
                                                             '" data-id="' . $med['ItemID'] . 
                                                             '" data-stock="' . $med['ItemStockQuantity'] . '">' . 
                                                             htmlspecialchars($med['ItemName']) . 
                                                             ' (Stock: ' . $med['ItemStockQuantity'] . ')</option>';
                                                    }
                                                }
                                                ?>
                                            </select>
                                            <span class="err-msg" id="medNameErr0"></span>
                                        </div>
                                        <div class="input-group" style="max-width:120px;">
                                            <label for="consultMedQty0">Qty</label>
                                            <input type="number" id="consultMedQty0" name="consultMedQty[]"
                                                   placeholder="0" min="1" onchange="validateMedQty(this, 0)">
                                            <span class="err-msg" id="medQtyErr0"></span>
                                        </div>
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
<script>
// consultation.js functionality embedded here for completeness

let currentPatientId = null;
let medRowCount = 1;

function searchPatient() {
    const searchTerm = document.getElementById('consultSearchInput').value.trim();
    const feedback = document.getElementById('searchFeedback');
    
    if (!searchTerm) {
        feedback.innerHTML = '<span class="error-text">Please enter a patient ID or name</span>';
        return;
    }
    
    // AJAX call to search for patient
    fetch(`api/search_patient.php?q=${encodeURIComponent(searchTerm)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.patient) {
                // Populate patient card
                document.getElementById('cpcID').textContent = data.patient.PatientID;
                document.getElementById('cpcName').textContent = data.patient.FullName;
                document.getElementById('cpcSex').textContent = data.patient.Sex || '—';
                document.getElementById('cpcBday').textContent = data.patient.Birthday || '—';
                document.getElementById('cpcProgram').textContent = data.patient.ProgramName || '—';
                document.getElementById('cpcTel').textContent = data.patient.PhoneNum || '—';
                document.getElementById('cpcTime').textContent = new Date().toLocaleTimeString();
                
                // Set hidden field
                document.getElementById('consultPatientID').value = data.patient.PatientID;
                currentPatientId = data.patient.PatientID;
                
                // Enable form
                document.getElementById('disabledOverlay').classList.remove('show');
                document.getElementById('consultFormArea').classList.remove('disabled');
                document.getElementById('consultFormActions').style.opacity = '1';
                document.getElementById('consultFormActions').style.pointerEvents = 'auto';
                
                feedback.innerHTML = '<span class="success-text">✓ Patient found: ' + 
                                    data.patient.FullName + '</span>';
            } else {
                feedback.innerHTML = '<span class="error-text">✗ Patient not found</span>';
                resetPatientSelection();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            feedback.innerHTML = '<span class="error-text">Error searching for patient</span>';
        });
}

function resetPatientSelection() {
    document.getElementById('cpcID').textContent = '—';
    document.getElementById('cpcName').textContent = '—';
    document.getElementById('cpcSex').textContent = '—';
    document.getElementById('cpcBday').textContent = '—';
    document.getElementById('cpcProgram').textContent = '—';
    document.getElementById('cpcTel').textContent = '—';
    document.getElementById('consultPatientID').value = '';
    currentPatientId = null;
    
    document.getElementById('disabledOverlay').classList.add('show');
    document.getElementById('consultFormArea').classList.add('disabled');
    document.getElementById('consultFormActions').style.opacity = '.4';
    document.getElementById('consultFormActions').style.pointerEvents = 'none';
}

function addConsultMed() {
    const medsList = document.getElementById('medsList');
    const newRow = document.createElement('div');
    newRow.className = 'med-entry';
    newRow.id = `med-${medRowCount}`;
    
    newRow.innerHTML = `
        <div class="input-group">
            <label for="consultMedName${medRowCount}">Medicine Name</label>
            <select id="consultMedName${medRowCount}" name="consultMedName[]" class="medicine-select"
                    onchange="checkMedicineStock(this, ${medRowCount})">
                <option value="">Select medicine</option>
                ${document.querySelector('#med-0 select').innerHTML}
            </select>
            <span class="err-msg" id="medNameErr${medRowCount}"></span>
        </div>
        <div class="input-group" style="max-width:120px;">
            <label for="consultMedQty${medRowCount}">Qty</label>
            <input type="number" id="consultMedQty${medRowCount}" name="consultMedQty[]"
                   placeholder="0" min="1" onchange="validateMedQty(this, ${medRowCount})">
            <span class="err-msg" id="medQtyErr${medRowCount}"></span>
        </div>
        <button type="button" class="remove-med-btn" onclick="removeConsultMed(${medRowCount})">
            <i class="ti ti-trash"></i>
        </button>
    `;
    
    medsList.appendChild(newRow);
    medRowCount++;
}

function removeConsultMed(rowId) {
    const row = document.getElementById(`med-${rowId}`);
    if (row) row.remove();
}

function checkMedicineStock(select, rowId) {
    const selectedOption = select.options[select.selectedIndex];
    const maxStock = selectedOption.getAttribute('data-stock') || 0;
    const qtyInput = document.getElementById(`consultMedQty${rowId}`);
    
    if (qtyInput) {
        qtyInput.setAttribute('max', maxStock);
        qtyInput.placeholder = `Max: ${maxStock}`;
    }
}

function validateMedQty(input, rowId) {
    const qty = parseInt(input.value);
    const max = parseInt(input.getAttribute('max')) || 0;
    const errorSpan = document.getElementById(`medQtyErr${rowId}`);
    
    if (qty > max) {
        errorSpan.textContent = `Quantity exceeds available stock (${max})`;
        input.value = max;
    } else if (qty < 1 && input.value !== '') {
        errorSpan.textContent = 'Quantity must be at least 1';
    } else {
        errorSpan.textContent = '';
    }
}

// Form validation before submit
document.getElementById('consultationForm').addEventListener('submit', function(e) {
    const concern = document.getElementById('consultConcern').value.trim();
    const service = document.getElementById('consultService').value;
    let hasError = false;
    
    if (!concern) {
        document.getElementById('consultConcernErr').textContent = 'Chief concern is required';
        hasError = true;
    } else {
        document.getElementById('consultConcernErr').textContent = '';
    }
    
    if (!service) {
        document.getElementById('consultServiceErr').textContent = 'Service type is required';
        hasError = true;
    } else {
        document.getElementById('consultServiceErr').textContent = '';
    }
    
    if (hasError) {
        e.preventDefault();
        showToast('Please fill in all required fields', 'error');
    }
});

// Clear form
document.getElementById('clearConsultForm').addEventListener('click', function() {
    if (confirm('Clear all form data? This cannot be undone.')) {
        document.getElementById('consultationForm').reset();
        // Reset medicine rows to just one
        const medsList = document.getElementById('medsList');
        medsList.innerHTML = medsList.children[0].outerHTML;
        medRowCount = 1;
        showToast('Form cleared', 'info');
    }
});

// Show/hide "Other Service" field
document.getElementById('consultService').addEventListener('change', function() {
    const otherWrap = document.getElementById('otherServiceWrap');
    if (this.value === 'Other') {
        otherWrap.style.display = 'block';
    } else {
        otherWrap.style.display = 'none';
    }
});

function showToast(message, type) {
    const toast = document.getElementById('consultToast');
    toast.textContent = message;
    toast.className = `toast toast-${type}`;
    toast.style.display = 'block';
    setTimeout(() => {
        toast.style.display = 'none';
    }, 3000);
}
</script>
</body>
</html>