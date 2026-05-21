<?php
session_start();
// Redirect if already logged in
if (isset($_SESSION['UserID'])) {
    $roles = $_SESSION['Roles'] ?? [];
    if (is_array($roles) && array_intersect($roles, ['Admin', 'Super Admin']) !== []) {
        header('Location: ../modules/dashboard/admin_dashboard.php');
    } else {
        header('Location: ../modules/dashboard/student_dashboard.php');
    }
    exit;
}

if (isset($_SESSION['patient_id'])) {
    header('Location: ../modules/dashboard/student_dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | NUcare</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/forgot_password.css">
</head>
<body>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <div class="page-wrapper">
        <div class="card">

            <!-- Logo / Branding -->
            <div class="card-header">
                <div class="brand-badge">NU</div>
                <h1 class="brand-name">NUCARE</h1>
                <p class="brand-sub">Health Management System</p>
            </div>

            <div class="card-body">
<h2 class="form-title">Forgot Password?</h2>
<p class="form-desc">
                    Enter your registered Gmail / Email address and we will send you a reset link.
                </p>

                <form id="forgotPasswordForm">
                    <div class="form-group">
                        <label for="email" class="form-label">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="4" width="20" height="16" rx="2"/>
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                            </svg>
                            Gmail / Email Address
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-input"
                            placeholder="Enter your email address"
                            autocomplete="email"
                            required
                        >
                        <span class="form-error" id="emailError"></span>
                    </div>

                    <button type="submit" class="btn-primary" id="submitBtn">
                        <span id="btnText">Send Reset Link</span>
                        <span class="spinner" id="btnSpinner" style="display:none;"></span>
                    </button>
                </form>

                <div class="form-footer">
                    <a href="login.php" class="back-link">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="m15 18-6-6 6-6"/>
                        </svg>
                        Back to Login
                    </a>
                </div>
            </div>

        </div>
    </div>

    <script src="../assets/js/forgot_password.js"></script>
</body>
</html>
