<?php
session_start();

if (isset($_SESSION['patient_id'])) {
    header('Location: ../modules/dashboard/dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE - Register</title>
    <link rel="stylesheet" href="../assets/css/register.css?v=1">
</head>
<body>
    <div class="register-container">
        <div class="register-left">
            <div class="left-overlay"></div>
            <div class="left-particles" aria-hidden="true">
                <span></span><span></span><span></span><span></span><span></span>
            </div>

            <div class="logo-section">
                <div class="logo-placeholder">
                    <img src="../assets/image/nucarelogo.png" alt="NUCARE Logo" class="logo-icon">
                </div>
                <h1 class="brand-name">NUCARE</h1>
                <p class="brand-subtitle">Health Management System</p>
                <div class="heartbeat-divider" aria-hidden="true">
                    <span></span><i></i><span></span>
                </div>
                <p class="brand-tagline">Create your <strong>Patient</strong> account</p>
                <p class="brand-description">
                    Register to access NUCARE services and manage your health records securely.
                </p>
            </div>
        </div>

        <div class="register-right">
            <div class="form-container">
                <div class="avatar-badge" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M20 21a8 8 0 0 0-16 0"></path>
                        <circle cx="12" cy="8" r="4"></circle>
                    </svg>
                </div>

                <h2 class="form-title">Create Account</h2>
                <p class="form-subtitle">Register as a Patient</p>
                <div class="title-underline" aria-hidden="true"></div>

                <form id="registerForm" class="register-form">

                    <!-- First Name -->
                    <div class="form-group">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" id="first_name" name="first_name" class="form-input"
                               placeholder="Enter your first name" required>
                        <span class="form-error" id="firstNameError"></span>
                    </div>

                    <!-- Last Name -->
                    <div class="form-group">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" id="last_name" name="last_name" class="form-input"
                               placeholder="Enter your last name" required>
                        <span class="form-error" id="lastNameError"></span>
                    </div>

                    <!-- Middle Name -->
                    <div class="form-group">
                        <label for="middle_name" class="form-label">Middle Name (Optional)</label>
                        <input type="text" id="middle_name" name="middle_name" class="form-input"
                               placeholder="Enter your middle name">
                        <span class="form-error" id="middleNameError"></span>
                    </div>

                    <!-- Sex -->
                    <div class="form-group">
                        <label for="sex" class="form-label">Sex</label>
                        <select id="sex" name="sex" class="form-input" required>
                            <option value="">Select sex</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                        <span class="form-error" id="sexError"></span>
                    </div>

                    <!-- School ID -->
                    <div class="form-group">
                        <label for="school_id" class="form-label">School ID</label>
                        <input type="text" id="school_id" name="school_id" class="form-input"
                               placeholder="Enter your school ID" required>
                        <span class="form-error" id="schoolIdError"></span>
                    </div>

                    <!-- ✅ NEW: Email Address -->
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" id="email" name="email" class="form-input"
                               placeholder="Enter your email address" required autocomplete="email">
                        <span class="form-error" id="emailError"></span>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-input"
                               placeholder="Enter your password" required>
                        <span class="form-error" id="passwordError"></span>
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input"
                               placeholder="Confirm your password" required>
                        <span class="form-error" id="confirmPasswordError"></span>
                    </div>

                    <button type="submit" class="register-button">Sign Up</button>

                    <div class="success-message" id="successMessage"></div>
                    <div class="error-message" id="errorMessage"></div>
                </form>

                <div class="form-footer">
                    <p>I already have an account. <a href="login.php" class="login-link">Sign In</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/register.js?v=3"></script>
</body>
</html>