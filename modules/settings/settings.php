<?php
session_start();

if (!isset($_SESSION['patient_id']) && !isset($_SESSION['UserID'])) {
    header('Location: ../../auth/login.php');
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
    <link rel="stylesheet" href="../../assets/css/app.css?v=1">
    <link rel="stylesheet" href="../../assets/css/records.css?v=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary:       #0f3c76;
            --primary-dark:  #112c5e;
            --primary-light: #ccfbf1;
            --card:          #ffffff;
            --text:          #1e293b;
            --muted:         #64748b;
            --border:        #e2e8f0;
            --error:         #dc2626;
            --radius:        16px;
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
        .brand-sub  { color: var(--primary-light); font-size: 13px; margin-top: 6px; }

        .card-body { padding: 40px; }
        .form-title { font-size: 22px; font-weight: 700; color: var(--text); margin-bottom: 8px; }
        .form-desc  { font-size: 15px; color: var(--muted); margin-bottom: 32px; line-height: 1.6; }

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

        .btn-account:hover  { opacity: .92; }
        .btn-account:active { transform: scale(.98); }

        .modal-overlay {
            position: fixed; inset: 0;
            background: rgba(15, 23, 42, .6);
            backdrop-filter: blur(4px);
            display: flex; align-items: center; justify-content: center;
            padding: 24px;
            z-index: 1000;
            opacity: 0; pointer-events: none;
            transition: opacity .25s ease;
        }

        .modal-overlay.open { opacity: 1; pointer-events: all; }

        .modal {
            background: #fff;
            border-radius: var(--radius);
            width: 100%; max-width: 380px;
            box-shadow: 0 32px 80px rgba(0,0,0,.35);
            transform: translateY(18px) scale(.97);
            transition: transform .3s cubic-bezier(.34,1.56,.64,1), opacity .25s ease;
            opacity: 0;
            overflow: hidden;
        }

        .modal-overlay.open .modal { transform: translateY(0) scale(1); opacity: 1; }

        .modal-header {
            background: linear-gradient(135deg, var(--primary), #0d9488);
            padding: 24px 24px 20px;
            display: flex; align-items: center; justify-content: space-between;
        }

        .modal-title-group { display: flex; align-items: center; gap: 10px; }

        .modal-icon {
            width: 38px; height: 38px;
            background: rgba(255,255,255,.2);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
        }

        .modal-icon svg { width: 18px; height: 18px; stroke: #fff; }

        .modal-title { font-size: 16px; font-weight: 700; color: #fff; }
        .modal-sub   { font-size: 12px; color: var(--primary-light); margin-top: 2px; }

        .modal-close {
            background: rgba(255,255,255,.15);
            border: none; border-radius: 8px;
            width: 32px; height: 32px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; color: #fff;
            transition: background .2s;
            flex-shrink: 0;
        }

        .modal-close:hover { background: rgba(255,255,255,.3); }
        .modal-close svg   { width: 16px; height: 16px; stroke: currentColor; }

        .modal-body { padding: 24px; display: flex; flex-direction: column; gap: 12px; }

        .modal-btn {
            width: 100%;
            padding: 14px 16px;
            border-radius: 10px;
            font-size: 14px; font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            display: flex; align-items: center; gap: 12px;
            text-decoration: none;
            border: 1.5px solid transparent;
            transition: all .2s;
        }

        .modal-btn .btn-icon {
            width: 36px; height: 36px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        .modal-btn .btn-icon svg { width: 16px; height: 16px; stroke: currentColor; }
        .modal-btn .btn-label    { flex: 1; text-align: left; }
        .modal-btn .btn-sublabel { font-size: 11px; font-weight: 400; opacity: .7; margin-top: 1px; }

        .modal-btn.reset {
            background: var(--primary-light);
            border-color: rgba(15,118,110,.25);
            color: var(--primary-dark);
        }
        .modal-btn.reset .btn-icon { background: rgba(15,118,110,.15); color: var(--primary); }
        .modal-btn.reset:hover     { background: #b2f0e8; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(15,118,110,.12); }

        .modal-divider { height: 1px; background: var(--border); margin: 4px 0; }

        @media (max-width: 500px) {
            .card-body, .card-header { padding: 24px 20px; }
        }
    </style>
</head>
<body>

<div class="app-shell">

<?php
$sidebarPath = __DIR__ . '/../../includes/sidebar_medical_staff.php';
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
                <p class="form-desc">Manage your account actions below.</p>

                <button class="btn-account" id="openAccountModal">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                    </svg>
                    Account Actions
                </button>
            </div>
        </div>
        </div>
    </main>
</div>

<!-- Modal -->
<div class="modal-overlay" id="accountModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal">

        <div class="modal-header">
            <div class="modal-title-group">
                <div class="modal-icon">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                    </svg>
                </div>
                <div>
                    <div class="modal-title" id="modalTitle">Account Actions</div>
                    <div class="modal-sub">Choose an action below</div>
                </div>
            </div>
            <button class="modal-close" id="closeAccountModal" aria-label="Close modal">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="modal-body">

            <!-- Change Password (Current + New + Confirm) -->
            <button type="button" class="modal-btn reset" id="openChangePassword">
                <div class="btn-icon">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                    </svg>
                </div>
                <div class="btn-label">
                    <div>Change Password</div>
                    <div class="btn-sublabel">Update password using your current password</div>
                </div>
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                </svg>
            </button>

            <div class="modal-divider"></div>

            <!-- Inline Change Password Form (hidden until opened) -->
            <div id="changePasswordPanel" style="display:none; padding: 0 24px 24px;">
                <h3 class="form-title" style="margin-top: 10px;">Update Password</h3>
                <p class="form-desc" style="margin-bottom: 18px;">Enter your current password and choose a new one.</p>

                <form id="settingsChangePasswordForm" autocomplete="off">
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label for="current_password" class="form-label" style="display:block; font-weight:700; margin-bottom:8px;">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="form-input" style="width:100%; padding: 12px 14px; border-radius: 10px; border: 1px solid var(--border);" />
                        <span class="form-error" id="currentPasswordError" style="display:block; margin-top:8px; color: var(--error); font-weight:600; font-size:12px;"></span>
                    </div>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label for="new_password" class="form-label" style="display:block; font-weight:700; margin-bottom:8px;">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-input" style="width:100%; padding: 12px 14px; border-radius: 10px; border: 1px solid var(--border);" />
                        <span class="form-error" id="newPasswordError" style="display:block; margin-top:8px; color: var(--error); font-weight:600; font-size:12px;"></span>
                    </div>

                    <div class="form-group" style="margin-bottom: 18px;">
                        <label for="confirm_password" class="form-label" style="display:block; font-weight:700; margin-bottom:8px;">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" style="width:100%; padding: 12px 14px; border-radius: 10px; border: 1px solid var(--border);" />
                        <span class="form-error" id="confirmPasswordError" style="display:block; margin-top:8px; color: var(--error); font-weight:600; font-size:12px;"></span>
                    </div>

                    <button type="submit" class="btn-account" id="changePasswordBtn">
                        <span id="changePasswordBtnText">Update Password</span>
                        <span class="spinner" id="changePasswordSpinner" style="display:none;"></span>
                    </button>
                </form>
            </div>


        </div>
    </div>
</div>

<script>
    const overlay  = document.getElementById('accountModal');
    const openBtn  = document.getElementById('openAccountModal');
    const closeBtn = document.getElementById('closeAccountModal');

    function openModal()  { overlay.classList.add('open');    document.body.style.overflow = 'hidden'; }
    function closeModal() { overlay.classList.remove('open'); document.body.style.overflow = '';       }

    openBtn.addEventListener('click', openModal);
    closeBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', (e) => { if (e.target === overlay) closeModal(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

    // Change password panel
    const openChangePassword = document.getElementById('openChangePassword');
    const changePasswordPanel = document.getElementById('changePasswordPanel');

    if (openChangePassword && changePasswordPanel) {
        openChangePassword.addEventListener('click', (e) => {
            e.preventDefault();
            changePasswordPanel.style.display = 'block';
        });
    }
</script>

<script src="../../assets/js/settings_change_password.js?v=1"></script>

<!-- toast container for fallback/modal (modal is injected by JS) -->
<div id="toastContainer" style="position:fixed;top:20px;right:20px;z-index:1500;"></div>

</body>
</html>

