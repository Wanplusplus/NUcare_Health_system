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
<body>
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
                <button class="nav-item <?php echo $activePanel === 'settingsPanel' ? 'active' : ''; ?>" data-panel="settingsPanel" type="button">
                    <span class="nav-dot"></span>
                    Settings
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
                    <p class="breadcrumb"><?php echo $activePanel === 'patientsPanel' ? 'Home / Patients / Add Patient' : 'Home / Dashboard'; ?></p>
                    <h2><?php echo $activePanel === 'patientsPanel' ? 'Patient Intake' : 'NUCARE Clinic Portal'; ?></h2>
                    <p class="page-description">
                        <?php echo $activePanel === 'patientsPanel'
                            ? 'Use the patient form to capture intake information aligned with your database schema.'
                            : 'Manage patients, records, reports, and clinical workflows from one polished interface.'; ?>
                    </p>
                </div>
                <div class="header-actions">
                    <button class="header-button accent" id="newPatientButton" type="button">New Patient</button>
                    <button class="header-button outline" type="button">Logout</button>
                </div>
            </header>

            <section id="dashboardPanel" class="panel <?php echo $activePanel === 'dashboardPanel' ? 'active' : ''; ?>">
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

            <section id="patientsPanel" class="panel <?php echo $activePanel === 'patientsPanel' ? 'active' : ''; ?>">
                <div class="panel-overview">
                    <div>
                        <h3>Add Patient</h3>
                        <p>Capture patient details in a form that matches your `patients` table.</p>
                    </div>
                    <span class="panel-status <?php echo $panelStatusClass; ?>"><?php echo htmlspecialchars($panelStatusText); ?></span>
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
                            <?php if (empty($programOptions)): ?>
                                <small class="input-help">Program is optional right now because the `programs` table has no records yet.</small>
                            <?php endif; ?>
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
                    <p>The patient intake form is now connected to the database and saves new records to the `patients` table in `nucaredb`.</p>
                </div>
            </section>

            <section id="recordsPanel" class="panel <?php echo $activePanel === 'recordsPanel' ? 'active' : ''; ?>">
                <div class="placeholder-panel large">
                    <h3>Records</h3>
                    <p>Placeholder area for patient records, medical transactions, and exam summaries. Backend integration will connect this to your database tables later.</p>
                </div>
            </section>

            <section id="reportsPanel" class="panel <?php echo $activePanel === 'reportsPanel' ? 'active' : ''; ?>">
                <div class="placeholder-panel large">
                    <h3>Reports</h3>
                    <p>Placeholder area for reports, analytics, and health summaries. This panel is styled for future data visualizations and export workflows.</p>
                </div>
            </section>

            <section id="settingsPanel" class="panel <?php echo $activePanel === 'settingsPanel' ? 'active' : ''; ?>">
                <div class="placeholder-panel large">
                    <h3>Settings</h3>
                    <p>Placeholder area for profile settings, account preferences, and system configurations.</p>
                </div>
            </section>
        </main>
    </div>

    <script src="assets/js/app.js"></script>
    <script src="assets/js/patientadd.js"></script>
</body>
</html>
