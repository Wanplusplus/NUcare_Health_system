<?php
session_start();

if (isset($_SESSION['UserID'])) {
 $roles = $_SESSION['Roles'] ?? [];
 if (is_array($roles) && array_intersect($roles, ['Admin', 'Super Admin']) !== []) {
 header('Location: /NUcare_Health_system/frontend/admin/dashboard/admin_dashboard.php');
 } else {
 header('Location: /NUcare_Health_system/frontend/student/dashboard/student_dashboard.php');
 }
 exit;
}

if (isset($_SESSION['patient_id'])) {
 header('Location: /NUcare_Health_system/frontend/student/dashboard/student_dashboard.php');
 exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>NUcare - Register</title>
 <link rel="icon" type="image/png" href="/NUcare_Health_system/assets/image/nucarelogo.png">
 <link rel="stylesheet" href="/NUcare_Health_system/assets/css/register.css?v=2">
</head>
<body>
 <main class="register-shell">
 <section class="register-hero" aria-label="NUcare introduction">
 <div class="hero-grid" aria-hidden="true"></div>
 <div class="hero-dots" aria-hidden="true">
 <span></span><span></span><span></span><span></span><span></span><span></span>
 </div>
 <div class="hero-cross hero-cross-a" aria-hidden="true"></div>
 <div class="hero-cross hero-cross-b" aria-hidden="true"></div>
 <div class="hero-arc" aria-hidden="true"></div>

 <div class="hero-content">
 <div class="hero-logo">
 <img src="/NUcare_Health_system/assets/image/nucarelogo.png" alt="NUcare logo">
 </div>

 <h1>Welcome to <span>NUcare</span></h1>
 <p class="hero-copy">Create your patient account and access your health records, clinic services, and appointments in one secure place.</p>

 <div class="hero-features">
 <div class="feature-item">
 <span class="feature-icon" aria-hidden="true">
 <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
 <path d="M12 22s7-4 7-10V6l-7-3-7 3v6c0 6 7 10 7 10Z"></path>
 <path d="M9.5 12.2 11.2 14l3.6-4"></path>
 </svg>
 </span>
 <div>
 <strong>Secure Records</strong>
 <p>Your health information stays protected.</p>
 </div>
 </div>
 <div class="feature-item">
 <span class="feature-icon" aria-hidden="true">
 <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
 <path d="M12 6v6l4 2"></path>
 <circle cx="12" cy="12" r="9"></circle>
 </svg>
 </span>
 <div>
 <strong>Faster Access</strong>
 <p>Book and manage clinic services quickly.</p>
 </div>
 </div>
 <div class="feature-item">
 <span class="feature-icon" aria-hidden="true">
 <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
 <path d="M12 21s-7-4.4-7-10.5A4.5 4.5 0 0 1 9.5 6a4.9 4.9 0 0 1 2.5.7A4.9 4.9 0 0 1 14.5 6 4.5 4.5 0 0 1 19 10.5C19 16.6 12 21 12 21Z"></path>
 </svg>
 </span>
 <div>
 <strong>Better Patient Care</strong>
 <p>Stay connected to the clinic with less friction.</p>
 </div>
 </div>
 </div>
 </div>
 </section>

 <section class="register-form-area" aria-label="Patient registration form">
 <div class="register-card">
 <div class="card-icon" aria-hidden="true">
 <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
 <path d="M20 21a8 8 0 0 0-16 0"></path>
 <circle cx="12" cy="8" r="4"></circle>
 </svg>
 </div>

 <h2>Create Account</h2>
 <p class="card-subtitle">Register as a Patient</p>

 <div class="progress-steps" aria-label="Registration progress">
 <span class="step active"></span>
 <span class="step-line"></span>
 <span class="step"></span>
 <span class="step-line"></span>
 <span class="step"></span>
 </div>

 <form id="registerForm" class="register-form" autocomplete="off">
 <input type="hidden" id="confirm_password" name="confirm_password" value="">

 <div class="form-row">
 <div class="field">
 <label for="first_name">First Name</label>
 <div class="input-shell">
 <span class="input-icon" aria-hidden="true">
 <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
 <circle cx="12" cy="8" r="4"></circle>
 <path d="M4 20a8 8 0 0 1 16 0"></path>
 </svg>
 </span>
 <input type="text" id="first_name" name="first_name" placeholder="Enter your first name" required>
 </div>
 <span class="field-error" id="firstNameError"></span>
 </div>

 <div class="field">
 <label for="last_name">Last Name</label>
 <div class="input-shell">
 <span class="input-icon" aria-hidden="true">
 <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
 <circle cx="12" cy="8" r="4"></circle>
 <path d="M4 20a8 8 0 0 1 16 0"></path>
 </svg>
 </span>
 <input type="text" id="last_name" name="last_name" placeholder="Enter your last name" required>
 </div>
 <span class="field-error" id="lastNameError"></span>
 </div>
 </div>

 <div class="field">
 <label for="middle_name">Middle Name <span>(Optional)</span></label>
 <div class="input-shell">
 <span class="input-icon" aria-hidden="true">
 <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
 <circle cx="12" cy="8" r="4"></circle>
 <path d="M4 20a8 8 0 0 1 16 0"></path>
 </svg>
 </span>
 <input type="text" id="middle_name" name="middle_name" placeholder="Enter your middle name">
 </div>
 <span class="field-error" id="middleNameError"></span>
 </div>

 <div class="form-row">
 <div class="field">
 <label for="sex">Sex</label>
 <div class="input-shell select-shell">
 <span class="input-icon" aria-hidden="true">
 <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
 <path d="M8 8h8M8 12h8M8 16h8"></path>
 </svg>
 </span>
 <select id="sex" name="sex" required>
 <option value="">Select sex</option>
 <option value="Male">Male</option>
 <option value="Female">Female</option>
 </select>
 <span class="select-arrow" aria-hidden="true"></span>
 </div>
 <span class="field-error" id="sexError"></span>
 </div>

 <div class="field">
 <label for="school_id">School ID</label>
 <div class="input-shell">
 <span class="input-icon" aria-hidden="true">
 <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
 <rect x="4" y="5" width="16" height="14" rx="2"></rect>
 <path d="M8 9h8M8 13h5"></path>
 </svg>
 </span>
 <input type="text" id="school_id" name="school_id" placeholder="Enter your school ID" required>
 </div>
 <span class="helper-text">Must match your official school record.</span>
 <span class="field-error" id="schoolIdError"></span>
 </div>
 </div>

 <div class="field">
 <label for="email">Email Address</label>
 <div class="input-shell">
 <span class="input-icon" aria-hidden="true">
 <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
 <path d="M4 6h16v12H4z"></path>
 <path d="m4 7 8 6 8-6"></path>
 </svg>
 </span>
 <input type="email" id="email" name="email" placeholder="Enter your email address" required autocomplete="email">
 </div>
 <span class="helper-text">We may use this for account recovery and clinic notices.</span>
 <span class="field-error" id="emailError"></span>
 </div>

 <div class="field">
 <label for="password">Password</label>
 <div class="input-shell password-shell">
 <span class="input-icon" aria-hidden="true">
 <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
 <rect x="4" y="10" width="16" height="10" rx="2"></rect>
 <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
 </svg>
 </span>
 <input type="password" id="password" name="password" placeholder="Enter your password" required>
 <button type="button" class="password-toggle" id="passwordToggle" aria-label="Toggle password visibility">
 <svg class="eye-open" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
 <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7Z"></path>
 <circle cx="12" cy="12" r="3"></circle>
 </svg>
 </button>
 </div>
 <span class="helper-text">Use at least 6 characters. Passwords are stored securely.</span>
 <span class="field-error" id="passwordError"></span>
 </div>

 <div class="privacy-note">
 <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
 <path d="M12 22s7-4 7-10V6l-7-3-7 3v6c0 6 7 10 7 10Z"></path>
 </svg>
 <span>We take your privacy seriously. Your information is secure and will never be shared.</span>
 </div>

 <button type="submit" class="submit-button">
 <span>CREATE ACCOUNT</span>
 <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
 <path d="M5 12h12"></path>
 <path d="m13 6 6 6-6 6"></path>
 </svg>
 </button>

 <div class="success-message" id="successMessage"></div>
 <div class="error-message" id="errorMessage"></div>
 </form>

 <p class="card-footer">Already have an account? <a href="login.php">Sign In</a></p>
 </div>
 </section>
 </main>

 <script src="/NUcare_Health_system/assets/js/register.js?v=4"></script>
</body>
</html>




