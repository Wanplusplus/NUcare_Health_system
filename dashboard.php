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
                <button class="nav-item active" data-panel="dashboardPanel">
                    <span class="nav-dot"></span>
                    Dashboard
                </button>
                <button class="nav-item" data-panel="patientsPanel">
                    <span class="nav-dot"></span>
                    Patients
                </button>
                <button class="nav-item" data-panel="recordsPanel">
                    <span class="nav-dot"></span>
                    Records
                </button>
                <button class="nav-item" data-panel="reportsPanel">
                    <span class="nav-dot"></span>
                    Reports
                </button>
                <button class="nav-item" data-panel="settingsPanel">
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
                    <p class="breadcrumb">Home / Dashboard</p>
                    <h2>NUCARE Clinic Portal</h2>
                    <p class="page-description">Manage patients, records, reports, and clinical workflows from one polished interface.</p>
                </div>
                <div class="header-actions">
                    <button class="header-button accent">New Patient</button>
                    <button class="header-button outline">Logout</button>
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

                <form class="patient-form">
                    <div class="form-grid">
                        <div class="input-group">
                            <label for="patientFname">First Name</label>
                            <input type="text" id="patientFname" placeholder="Enter first name">
                        </div>
                        <div class="input-group">
                            <label for="patientLname">Last Name</label>
                            <input type="text" id="patientLname" placeholder="Enter last name">
                        </div>
                        <div class="input-group">
                            <label for="patientMname">Middle Name</label>
                            <input type="text" id="patientMname" placeholder="Enter middle name">
                        </div>
                        <div class="input-group">
                            <label for="patientProgram">Program</label>
                            <select id="patientProgram">
                                <option value="">Select program</option>
                                <option>Health Sciences</option>
                                <option>Medical Technology</option>
                                <option>Physical Therapy</option>
                                <option>Nursing</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label for="patientSex">Sex</label>
                            <select id="patientSex">
                                <option value="">Select sex</option>
                                <option>Male</option>
                                <option>Female</option>
                            </select>
                        </div>
                        <div class="input-group">
                            <label for="patientBirthday">Birthday</label>
                            <input type="date" id="patientBirthday">
                        </div>
                        <div class="input-group full-width">
                            <label for="patientEmail">Email Address</label>
                            <input type="email" id="patientEmail" placeholder="Enter email address">
                        </div>
                        <div class="input-group full-width">
                            <label for="patientPhone">Phone Number</label>
                            <input type="tel" id="patientPhone" placeholder="Enter phone number">
                        </div>
                        <div class="input-group full-width">
                            <label for="patientAddress">Address</label>
                            <input type="text" id="patientAddress" placeholder="Enter patient address">
                        </div>
                        <div class="input-group full-width">
                            <label for="patientReligion">Religion</label>
                            <input type="text" id="patientReligion" placeholder="Enter religion">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="primary-button">Save Patient</button>
                        <button type="button" class="secondary-button">Clear Form</button>
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
        </main>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>
