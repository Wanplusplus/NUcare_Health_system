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
                    <div class="form-group">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="form-input" placeholder="Enter your full name" required>
                        <span class="form-error" id="fullNameError"></span>
                    </div>

                    <div class="form-group">
                        <label for="sex" class="form-label">Sex</label>
                        <select id="sex" name="sex" class="form-input" required>
                            <option value="">Select sex</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                        <span class="form-error" id="sexError"></span>
                    </div>

                    <div class="form-group">
                        <label for="school_id" class="form-label">School ID</label>
                        <input type="text" id="school_id" name="school_id" class="form-input" placeholder="Enter your school ID" required>
                        <span class="form-error" id="schoolIdError"></span>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-input" placeholder="Enter your password" required>
                        <span class="form-error" id="passwordError"></span>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="Confirm your password" required>
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

    <script src="../assets/js/register.js?v=1"></script>
</body>
</html>