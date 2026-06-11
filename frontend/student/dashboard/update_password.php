<?php
declare(strict_types=1);

session_start();
if (!isset($_SESSION['UserID'])) {
 header('Location: /NUcare_Health_system/frontend/auth/login.php');
 exit;
}

$activeSidebarItem = 'settings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>NUCARE | Update Password</title>
 <link rel="stylesheet" href="/NUcare_Health_system/assets/css/app.css">
 <style>
 :root { --yellow:#FACC15; --text:#1f2937; --border:rgba(31,41,55,.15); }
 body { background:#fff; color:var(--text); }
 .settings-wrap { max-width:620px; margin:20px auto; padding:0 16px; }
 .settings-card { background:#fff; border:1px solid rgba(234,179,8,.25); border-radius:16px; box-shadow:0 10px 25px rgba(250,204,21,.18); padding:18px; }
 .settings-title { margin:0 0 8px; font-size:1.35rem; }
 .settings-desc { margin:0 0 18px; color:#6b7280; }
 .field { margin-bottom:14px; }
 label { display:block; margin-bottom:6px; font-weight:800; }
 input { width:100%; padding:11px 12px; border-radius:12px; border:1px solid var(--border); }
 .btn { border:1px solid rgba(234,179,8,.35); background:var(--yellow); padding:11px 16px; border-radius:12px; font-weight:900; cursor:pointer; }
 .form-error { display:block; color:#dc2626; font-size:.8rem; font-weight:700; margin-top:6px; }
 .settings-feedback { margin-top:14px; min-height:20px; font-size:13px; font-weight:700; color:#6b7280; }
 .settings-feedback.success { color:#0f766e; }
 .settings-feedback.error { color:#dc2626; }
 #toastContainer.toast-stack { position:fixed; top:20px; right:20px; z-index:1500; display:flex; flex-direction:column; gap:10px; }
 .settings-toast { min-width:240px; max-width:360px; padding:12px 14px; border-radius:12px; box-shadow:0 12px 30px rgba(0,0,0,.18); font-size:13px; font-weight:800; opacity:0; transform:translateY(-8px); transition:opacity .2s, transform .2s; }
 .settings-toast.show { opacity:1; transform:translateY(0); }
 .settings-toast.success { background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0; }
 .settings-toast.error { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
 </style>
 <link rel="stylesheet" href="/NUcare_Health_system/assets/css/student_cyber.css?v=2">
</head>
<body>
<div class="app-shell">
 <?php require_once __DIR__ . '/../../../backend/includes/patient_sidebar.php'; ?>

 <main class="main-content">
 <div class="settings-wrap">
 <div class="student-cyber-stage" aria-hidden="true">
 <span class="cyber-grid-plane"></span>
 <span class="student-orbit orbit-alpha"></span>
 <span class="student-orbit orbit-beta"></span>
 <span class="student-beam beam-alpha"></span>
 <span class="student-beam beam-beta"></span>
 <span class="student-glyph glyph-plus">+</span>
 <span class="student-glyph glyph-id"></span>
 <span class="student-glyph glyph-care"></span>
 <span class="student-particle p1"></span>
 <span class="student-particle p2"></span>
 <span class="student-particle p3"></span>
 <span class="student-particle p4"></span>
 </div>
 <div class="settings-card">
 <h1 class="settings-title">Update Password</h1>
 <p class="settings-desc">Enter your current password and choose a new one.</p>

 <form id="settingsChangePasswordForm" autocomplete="off">
 <div class="field">
 <label for="current_password">Current Password</label>
 <input type="password" id="current_password" name="current_password">
 <span class="form-error" id="currentPasswordError"></span>
 </div>
 <div class="field">
 <label for="new_password">New Password</label>
 <input type="password" id="new_password" name="new_password">
 <span class="form-error" id="newPasswordError"></span>
 </div>
 <div class="field">
 <label for="confirm_password">Confirm Password</label>
 <input type="password" id="confirm_password" name="confirm_password">
 <span class="form-error" id="confirmPasswordError"></span>
 </div>
 <button class="btn" type="submit" id="changePasswordBtn">
 <span id="changePasswordBtnText">Update Password</span>
 <span class="spinner" id="changePasswordSpinner" style="display:none;"></span>
 </button>
 </form>
 </div>
 </div>
 </main>
</div>

<div id="toastContainer"></div>
<script src="/NUcare_Health_system/assets/js/app.js"></script>
<script src="/NUcare_Health_system/assets/js/settings_change_password.js?v=1"></script>
</body>
</html>




