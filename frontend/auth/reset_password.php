<?php
// Grab token from URL
$token = trim($_GET['token'] ?? '');

// Redirect if no token provided
if ($token === '') {
 header('Location: forgot_password.php');
 exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>Reset Password | NUcare</title>
 <link rel="preconnect" href="https://fonts.googleapis.com">
 <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
 <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
 <link rel="stylesheet" href="/NUcare_Health_system/assets/css/reset_password.css">
</head>
<body>

 <!-- Toast Container -->
 <div class="toast-container" id="toastContainer"></div>

 <div class="page-wrapper">
 <div class="card">

 <!-- Header -->
 <div class="card-header">
 <div class="brand-badge">NU</div>
 <h1 class="brand-name">NUCARE</h1>
 <p class="brand-sub">Health Management System</p>
 </div>

 <div class="card-body">
 <h2 class="form-title">Create New Password</h2>
 <p class="form-desc">
 Enter your new password below. Make sure it is strong and memorable.
 </p>

 <form id="resetPasswordForm">
 <!-- Hidden token field -->
 <input type="hidden" id="reset_token" name="token" value="<?php echo htmlspecialchars($token); ?>">

 <!-- New Password -->
 <div class="form-group">
 <label for="new_password" class="form-label">New Password</label>
 <div class="input-wrapper">
 <input
 type="password"
 id="new_password"
 name="new_password"
 class="form-input"
 placeholder="Enter new password"
 required
 >
 <button type="button" class="toggle-btn" data-target="new_password" title="Show/Hide password">
 <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
 <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/>
 <circle cx="12" cy="12" r="3"/>
 </svg>
 </button>
 </div>
 <!-- Password Strength Bar -->
 <div class="strength-bar-wrapper">
 <div class="strength-bar" id="strengthBar"></div>
 </div>
 <span class="strength-text" id="strengthText"></span>
 <span class="form-error" id="newPasswordError"></span>
 </div>

 <!-- Confirm Password -->
 <div class="form-group">
 <label for="confirm_password" class="form-label">Confirm Password</label>
 <div class="input-wrapper">
 <input
 type="password"
 id="confirm_password"
 name="confirm_password"
 class="form-input"
 placeholder="Re-enter new password"
 required
 >
 <button type="button" class="toggle-btn" data-target="confirm_password" title="Show/Hide password">
 <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
 <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/>
 <circle cx="12" cy="12" r="3"/>
 </svg>
 </button>
 </div>
 <span class="form-error" id="confirmPasswordError"></span>
 </div>

 <button type="submit" class="btn-primary" id="resetBtn">
 <span id="resetBtnText">Confirm New Password</span>
 <span class="spinner" id="resetSpinner" style="display:none;"></span>
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

 <script src="/NUcare_Health_system/assets/js/reset_password.js"></script>
</body>
</html>



