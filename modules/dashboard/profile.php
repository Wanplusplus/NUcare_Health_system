<?php
declare(strict_types=1);

session_start();
if (!isset($_SESSION['UserID'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$activeSidebarItem = 'profile';

$pdo = require __DIR__ . '/../../config/db_pdo.php';
$schoolPersonId = isset($_SESSION['SchoolPersonID']) ? (int)$_SESSION['SchoolPersonID'] : 0;

$person = null;
if ($schoolPersonId > 0) {
    $stmt = $pdo->prepare(
        "SELECT sp.SchoolID, sp.FirstName, sp.MiddleName, sp.LastName, sp.Email, sp.PersonType
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE | My Profile</title>
    <link rel="stylesheet" href="../../assets/css/app.css">
    <style>
        :root {
            --student-yellow:#FACC15;
            --student-bg:#FEFCE8;
            --student-text:#1f2937;
            --student-muted:#6b7280;
            --student-border:rgba(31,41,55,.14);
            --student-danger:#dc2626;
            --student-success:#16a34a;
        }
        body { background:#fff; color:var(--student-text); }
        .profile-wrap { width:100%; max-width:1040px; margin:20px auto; padding:0 18px 32px; }
        .profile-card { background:#fff; border:1px solid rgba(234,179,8,.28); border-radius:16px; box-shadow:0 10px 25px rgba(250,204,21,.16); padding:18px; margin-bottom:16px; }
        .profile-head { display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; align-items:flex-start; }
        .profile-title { margin:0 0 6px; font-size:1.45rem; font-weight:900; }
        .profile-desc { margin:0; color:var(--student-muted); line-height:1.5; }
        .account-summary { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:10px; margin-top:14px; }
        .summary-item { border:1px solid var(--student-border); border-radius:12px; padding:10px 12px; background:var(--student-bg); }
        .summary-label { display:block; font-size:.72rem; font-weight:900; color:#854d0e; text-transform:uppercase; letter-spacing:.04em; }
        .summary-value { display:block; margin-top:4px; font-weight:800; overflow-wrap:anywhere; }
        .section-title { display:flex; align-items:center; gap:8px; margin:0 0 16px; font-size:1rem; font-weight:900; }
        .form-grid { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:14px; }
        .form-field { display:flex; flex-direction:column; gap:7px; }
        .form-field.full { grid-column:1 / -1; }
        .form-field label { font-size:.76rem; font-weight:900; text-transform:uppercase; letter-spacing:.04em; }
        .required { color:var(--student-danger); }
        input, select, textarea {
            width:100%; padding:11px 12px; border-radius:12px; border:1.5px solid var(--student-border);
            font:700 .92rem Arial, sans-serif; color:var(--student-text); background:#fff;
        }
        textarea { min-height:86px; resize:vertical; }
        input:focus, select:focus, textarea:focus { outline:none; border-color:#ca8a04; box-shadow:0 0 0 3px rgba(250,204,21,.22); }
        .actions { display:flex; justify-content:flex-end; margin-top:18px; }
        .btn-save { border:1px solid rgba(234,179,8,.35); background:var(--student-yellow); color:#1f2937; border-radius:999px; padding:11px 18px; font-weight:900; cursor:pointer; }
        .profile-toast { position:fixed; right:24px; bottom:24px; padding:12px 18px; border-radius:999px; font-weight:900; opacity:0; transform:translateY(12px); transition:.2s; z-index:20; }
        .profile-toast.show { opacity:1; transform:translateY(0); }
        .profile-toast.success { background:#f0fdf4; color:var(--student-success); border:1px solid #bbf7d0; }
        .profile-toast.error { background:#fef2f2; color:var(--student-danger); border:1px solid #fecaca; }
        @media (max-width:900px) { .form-grid, .account-summary { grid-template-columns:repeat(2, minmax(0, 1fr)); } }
        @media (max-width:620px) { .form-grid, .account-summary { grid-template-columns:1fr; } .profile-wrap { padding:0 12px 24px; } }
    </style>
</head>
<body>
<div class="app-shell">
    <?php require_once __DIR__ . '/../../includes/patient_sidebar.php'; ?>

    <main class="main-content">
        <div class="profile-wrap">
            <div class="profile-card">
                <div class="profile-head">
                    <div>
                        <h1 class="profile-title">My Profile</h1>
                        <p class="profile-desc">Complete your patient information and emergency contact details.</p>
                    </div>
                </div>
                <div class="account-summary">
                    <div class="summary-item"><span class="summary-label">Name</span><span class="summary-value"><?php echo htmlspecialchars($fullName); ?></span></div>
                    <div class="summary-item"><span class="summary-label">School ID</span><span class="summary-value"><?php echo htmlspecialchars($schoolId); ?></span></div>
                    <div class="summary-item"><span class="summary-label">Email</span><span class="summary-value"><?php echo htmlspecialchars($email); ?></span></div>
                    <div class="summary-item"><span class="summary-label">Type</span><span class="summary-value"><?php echo htmlspecialchars($personType); ?></span></div>
                </div>
            </div>

            <form id="studentProfileForm">
                <div class="profile-card">
                    <h2 class="section-title">Personal Information</h2>
                    <div class="form-grid">
                        <div class="form-field">
                            <label for="contact_no">Contact No. <span class="required">*</span></label>
                            <input id="contact_no" name="contact_no" maxlength="20" required>
                        </div>
                        <div class="form-field">
                            <label for="gender">Gender <span class="required">*</span></label>
                            <select id="gender" name="gender" required>
                                <option value="">Select gender</option>
                                <option>Male</option>
                                <option>Female</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="birth_date">Birth Date <span class="required">*</span></label>
                            <input type="date" id="birth_date" name="birth_date" required>
                        </div>
                        <div class="form-field">
                            <label for="age">Age <span class="required">*</span></label>
                            <input type="number" id="age" name="age" min="1" max="120" required>
                        </div>
                        <div class="form-field">
                            <label for="nationality">Nationality</label>
                            <input id="nationality" name="nationality" maxlength="50">
                        </div>
                        <div class="form-field">
                            <label for="status">Status</label>
                            <input id="status" name="status" maxlength="20" placeholder="Single, Married, Active">
                        </div>
                        <div class="form-field">
                            <label for="religion">Religion</label>
                            <input id="religion" name="religion" maxlength="30">
                        </div>
                        <div class="form-field full">
                            <label for="address">Address</label>
                            <textarea id="address" name="address"></textarea>
                        </div>
                    </div>
                </div>

                <div class="profile-card">
                    <h2 class="section-title">Emergency Contact Info</h2>
                    <div class="form-grid">
                        <div class="form-field">
                            <label for="guardian_name">Guardian Name</label>
                            <input id="guardian_name" name="guardian_name" maxlength="100">
                        </div>
                        <div class="form-field">
                            <label for="relationship">Relationship</label>
                            <input id="relationship" name="relationship" maxlength="50">
                        </div>
                        <div class="form-field">
                            <label for="mobile_no">Mobile No.</label>
                            <input id="mobile_no" name="mobile_no" maxlength="20">
                        </div>
                        <div class="form-field">
                            <label for="telephone">Telephone</label>
                            <input id="telephone" name="telephone" maxlength="20">
                        </div>
                        <div class="form-field full">
                            <label for="emergency_address">Emergency Address</label>
                            <textarea id="emergency_address" name="emergency_address"></textarea>
                        </div>
                    </div>
                    <div class="actions">
                        <button class="btn-save" type="submit" id="saveProfileBtn">Save Profile</button>
                    </div>
                </div>
            </form>
        </div>
    </main>
</div>

<div class="profile-toast" id="profileToast"></div>
<script src="../../assets/js/app.js"></script>
<script>
(function () {
    const fields = ['contact_no','gender','birth_date','age','nationality','status','religion','address','guardian_name','relationship','mobile_no','telephone','emergency_address'];
    const form = document.getElementById('studentProfileForm');

    function fill(data) {
        const info = data.patientsInfo || {};
        fields.forEach(name => {
            const el = document.getElementById(name);
            if (el) el.value = info[name] ?? '';
        });
    }

    function toast(message, type) {
        const el = document.getElementById('profileToast');
        el.textContent = message;
        el.className = 'profile-toast show ' + (type || 'success');
        setTimeout(() => el.classList.remove('show'), 2600);
    }

    fetch('../../ajax/profile_info.ajax.php')
        .then(r => r.json())
        .then(data => { if (!data.ok) throw new Error(data.message || 'Load failed.'); fill(data); })
        .catch(err => toast(err.message, 'error'));

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        const body = new FormData(form);
        body.set('family_history', JSON.stringify([]));
        const btn = document.getElementById('saveProfileBtn');
        btn.disabled = true;
        fetch('../../ajax/profile_info.ajax.php', { method: 'POST', body })
            .then(r => r.json())
            .then(data => {
                if (!data.ok) throw new Error(data.message || 'Save failed.');
                fill(data);
                toast(data.message || 'Profile saved.', 'success');
            })
            .catch(err => toast(err.message, 'error'))
            .finally(() => { btn.disabled = false; });
    });
})();
</script>
</body>
</html>
