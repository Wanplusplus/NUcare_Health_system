<?php
declare(strict_types=1);

session_start();
if (!isset($_SESSION['UserID'])) {
    header('Location: ../../auth/login.php');
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
    <link rel="stylesheet" href="../../assets/css/app.css">
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
        #toastContainer { position:fixed; top:20px; right:20px; z-index:1500; }
    </style>
</head>
<body>
<div class="app-shell">
    <?php require_once __DIR__ . '/../../includes/patient_sidebar.php'; ?>

    <main class="main-content">
        <div class="settings-wrap">
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
<script src="../../assets/js/app.js"></script>
<script src="../../assets/js/settings_change_password.js?v=1"></script>
</body>
</html>
