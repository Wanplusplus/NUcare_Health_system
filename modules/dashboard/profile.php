<?php
session_start();

if (!isset($_SESSION['UserID'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$activeSidebarItem = 'profile';

$pdo = require __DIR__ . '/../../config/db_pdo.php';
$userId = (int)$_SESSION['UserID'];
$schoolPersonId = isset($_SESSION['SchoolPersonID']) ? (int)$_SESSION['SchoolPersonID'] : 0;

// Fetch current person details
$person = null;
if ($schoolPersonId > 0) {
    $stmt = $pdo->prepare(
        "SELECT sp.SchoolID, sp.FirstName, sp.MiddleName, sp.LastName, sp.Email, sp.PersonType, sp.Sex
         FROM school_people sp
         WHERE sp.SchoolPersonID = ? LIMIT 1"
    );
    $stmt->execute([$schoolPersonId]);
    $person = $stmt->fetch(PDO::FETCH_ASSOC);
}

$fullName = $person ? trim(((string)$person['FirstName']) . ' ' . ((string)$person['MiddleName']) . ' ' . ((string)$person['LastName'])) : ($_SESSION['patient_name'] ?? 'User');
$email = $person ? (string)$person['Email'] : '';
$schoolId = $person ? (string)$person['SchoolID'] : ($_SESSION['SchoolID'] ?? '');
$personType = $person ? (string)$person['PersonType'] : '';

$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Optional password change flow (uses existing update_password.php endpoint)
    $newPassword = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if ($newPassword === '' || $confirmPassword === '') {
        $error = 'Password fields are required.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // call update_password.php directly
        $upd = $pdo->prepare("UPDATE users SET PasswordHash = ?, UpdatedAt = NOW() WHERE UserID = ?");
        $hashed = hash('sha256', $newPassword);
        $upd->execute([$hashed, $userId]);
        $success = 'Password updated successfully.';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE | Profile</title>
    <link rel="stylesheet" href="../../assets/css/app.css">
    <style>
        :root{--clinic-yellow:#FACC15;--clinic-bg:#FEF9C3;--clinic-text:#1f2937;}
        body{background:#fff;color:var(--clinic-text);}
        .clinic-wrap{max-width:900px;margin:20px auto;padding:0 16px;}
        .clinic-card{background:#fff;border:1px solid rgba(234,179,8,.25);border-radius:16px;box-shadow:0 10px 25px rgba(250,204,21,.18);padding:16px 16px;margin-bottom:14px;}
        .clinic-title{margin:0 0 10px 0;font-size:1.25rem;}
        .field{margin-bottom:12px;}
        label{display:block;margin-bottom:6px;font-weight:700;}
        input{width:100%;padding:10px 12px;border-radius:12px;border:1px solid rgba(31,41,55,.15);}
        .btn{display:inline-flex;align-items:center;justify-content:center;border-radius:12px;border:1px solid rgba(234,179,8,.35);background:var(--clinic-yellow);padding:10px 14px;font-weight:800;color:#1f2937;cursor:pointer;}
        .muted{color:#6b7280;}
        .alert{padding:10px 12px;border-radius:12px;margin-bottom:10px;}
        .alert-success{background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.25);}
        .alert-error{background:rgba(239,68,68,.10);border:1px solid rgba(239,68,68,.22);}
    </style>
</head>
<body>
<div class="app-shell">
    <?php require_once __DIR__ . '/../../includes/patient_sidebar.php'; ?>

    <main class="main-content">
        <div class="clinic-wrap">
            <div class="clinic-card">
                <h1 class="clinic-title">Profile</h1>
                <div class="muted">Your account details</div>
            </div>

            <div class="clinic-card">
                <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

                <div class="field">
                    <label>Name</label>
                    <div style="padding:10px 12px;border:1px solid rgba(31,41,55,.15);border-radius:12px;background:#fff;"><?php echo htmlspecialchars($fullName); ?></div>
                </div>

                <div class="field">
                    <label>SchoolID</label>
                    <div style="padding:10px 12px;border:1px solid rgba(31,41,55,.15);border-radius:12px;background:#fff;"><?php echo htmlspecialchars($schoolId); ?></div>
                </div>

                <div class="field">
                    <label>Email</label>
                    <div style="padding:10px 12px;border:1px solid rgba(31,41,55,.15);border-radius:12px;background:#fff;"><?php echo htmlspecialchars($email); ?></div>
                </div>

                <div class="field">
                    <label>PersonType</label>
                    <div style="padding:10px 12px;border:1px solid rgba(31,41,55,.15);border-radius:12px;background:#fff;"><?php echo htmlspecialchars($personType); ?></div>
                </div>
            </div>

            <div class="clinic-card">
                <h2 class="clinic-title">Change Password</h2>
                <form method="post" autocomplete="off">
                    <div class="field">
                        <label>New Password</label>
                        <input type="password" name="new_password" required>
                    </div>
                    <div class="field">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                    <button class="btn" type="submit">Update Password</button>
                </form>
                <div class="muted" style="margin-top:10px;">Password is stored using SHA2(password, 256) for compatibility.</div>
            </div>
        </div>
    </main>
</div>
<script src="../../assets/js/app.js"></script>
</body>
</html>

