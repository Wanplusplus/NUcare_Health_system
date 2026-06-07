<?php
session_start();

if (!isset($_SESSION['patient_id']) && !isset($_SESSION['UserID'])) {
 header('Location: /NUcare_Health_system/frontend/auth/login.php');
 exit;
}

$patientName = $_SESSION['patient_name'] ?? $_SESSION['full_name'] ?? 'User';

$activeSidebarItem = 'settings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>Settings | NUcare</title>
 <link rel="icon" href="/NUcare_Health_system/assets/image/nucarelogo.png">
 <link rel="stylesheet" href="/NUcare_Health_system/assets/css/app.css?v=1">
 <link rel="stylesheet" href="/NUcare_Health_system/assets/css/records.css?v=1">
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
 <link rel="preconnect" href="https://fonts.googleapis.com" />
 <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
 <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
 <style>
 *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

 :root {
 --primary: #0f3c76;
 --primary-dark: #112c5e;
 --primary-light: #ccfbf1;
 --card: #ffffff;
 --text: #1e293b;
 --muted: #64748b;
 --border: #e2e8f0;
 --error: #dc2626;
 --radius: 16px;
 }

 /* White background for the whole content area */
 body { background: #f8fafc !important; }

 .main-content {
 background: #ffffff !important;
 display: flex !important;
 align-items: center !important;
 justify-content: center !important;
 min-height: 100vh !important;
 padding: 48px 32px !important;
 }

 .page-wrapper {
 width: 100%;
 max-width: 600px;
 }

 .card {
 background: #fff;
 border-radius: var(--radius);
 overflow: hidden;
 box-shadow: 0 4px 24px rgba(0,0,0,.08);
 border: 1px solid var(--border);
 }

 .card-header {
 background: linear-gradient(135deg, var(--primary), #0d9488);
 padding: 48px 40px 40px;
 text-align: center;
 }

 .brand-badge {
 width: 72px; height: 72px;
 background: rgba(255,255,255,.2);
 border-radius: 18px;
 display: inline-flex;
 align-items: center;
 justify-content: center;
 font-size: 24px; font-weight: 700;
 color: #fff;
 margin-bottom: 14px;
 backdrop-filter: blur(4px);
 }

 .brand-name { color: #fff; font-size: 26px; font-weight: 700; letter-spacing: 2px; }
 .brand-sub { color: var(--primary-light); font-size: 13px; margin-top: 6px; }

 .card-body { padding: 40px; }
 .form-title { font-size: 22px; font-weight: 700; color: var(--text); margin-bottom: 8px; }
 .form-desc { font-size: 15px; color: var(--muted); margin-bottom: 32px; line-height: 1.6; }

 .btn-account {
 width: 100%;
 padding: 14px;
 background: linear-gradient(135deg, var(--primary), #0d9488);
 color: #fff;
 border: none;
 border-radius: 10px;
 font-size: 15px; font-weight: 600;
 font-family: 'Inter', sans-serif;
 cursor: pointer;
 display: flex; align-items: center; justify-content: center; gap: 8px;
 transition: opacity .2s, transform .1s;
 }

 .btn-account:hover { opacity: .92; }
 .btn-account:active { transform: scale(.98); }

 .settings-form {
 display: flex;
 flex-direction: column;
 gap: 14px;
 }

 .field-label {
 display: block;
 margin-bottom: 6px;
 font-weight: 800;
 color: var(--text);
 }

 .field-input {
 width: 100%;
 padding: 11px 12px;
 border-radius: 12px;
 border: 1px solid var(--border);
 font-size: 14px;
 font-family: 'Inter', sans-serif;
 }

 .form-error {
 display: block;
 min-height: 16px;
 color: var(--error);
 font-size: 12px;
 font-weight: 700;
 margin-top: 6px;
 }

 .settings-feedback {
 margin-top: 14px;
 min-height: 20px;
 font-size: 13px;
 font-weight: 700;
 color: var(--muted);
 }

 .settings-feedback.success { color: #0f766e; }
 .settings-feedback.error { color: var(--error); }

 .toast-stack {
 position: fixed;
 top: 20px;
 right: 20px;
 z-index: 1500;
 display: flex;
 flex-direction: column;
 gap: 10px;
 }

 .settings-toast {
 min-width: 240px;
 max-width: 360px;
 padding: 12px 14px;
 border-radius: 12px;
 box-shadow: 0 12px 30px rgba(0,0,0,.18);
 font-size: 13px;
 font-weight: 800;
 opacity: 0;
 transform: translateY(-8px);
 transition: opacity .2s, transform .2s;
 }

 .settings-toast.show { opacity: 1; transform: translateY(0); }
 .settings-toast.success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
 .settings-toast.error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

 @media (max-width: 500px) {
 .card-body, .card-header { padding: 24px 20px; }
 }
 </style>
</head>
<body>

<div class="app-shell">

<?php
$sidebarPath = __DIR__ . '/../../../backend/includes/sidebar_medical_staff.php';
if (file_exists($sidebarPath)) require_once $sidebarPath;
?>

 <main class="main-content">
 <div class="page-wrapper">
 <div class="card">
 <div class="card-header">
 <div class="brand-badge">NU</div>
 <h1 class="brand-name">NUCARE</h1>
 <p class="brand-sub">Health Management System</p>
 </div>
 <div class="card-body">
 <h2 class="form-title">Settings</h2>
 <p class="form-desc">Enter your current password, then choose and confirm a new one.</p>

 <form id="settingsChangePasswordForm" class="settings-form" autocomplete="off">
 <div>
 <label for="current_password" class="field-label">Current Password</label>
 <input type="password" id="current_password" name="current_password" class="field-input" />
 <span class="form-error" id="currentPasswordError"></span>
 </div>

 <div>
 <label for="new_password" class="field-label">New Password</label>
 <input type="password" id="new_password" name="new_password" class="field-input" />
 <span class="form-error" id="newPasswordError"></span>
 </div>

 <div>
 <label for="confirm_password" class="field-label">Confirm Password</label>
 <input type="password" id="confirm_password" name="confirm_password" class="field-input" />
 <span class="form-error" id="confirmPasswordError"></span>
 </div>

 <button type="submit" class="btn-account" id="changePasswordBtn">
 <span id="changePasswordBtnText">Update Password</span>
 <span class="spinner" id="changePasswordSpinner" style="display:none;"></span>
 </button>
 </form>

 <div class="settings-feedback" id="passwordFeedback" aria-live="polite"></div>
 </div>
 </div>
 </div>
 </main>
</div>

<script src="/NUcare_Health_system/assets/js/settings_change_password.js?v=1"></script>

<div class="toast-stack" id="toastContainer" aria-live="polite"></div>

</body>
</html>





