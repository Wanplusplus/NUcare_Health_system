<?php
declare(strict_types=1);

session_start();
if (!isset($_SESSION['UserID'])) {
    header('Location: ../../auth/login.php');
    exit;
}

$activeSidebarItem = 'my_profile';
$patientName = $_SESSION['patient_name'] ?? $_SESSION['full_name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | NUcare</title>
    <link rel="icon" href="/NUcare_Health_system/assets/image/nucarelogo.png">
    <link rel="stylesheet" href="../../assets/css/app.css?v=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --profile-navy: #0f3c76;
            --profile-border: #e2e8f0;
            --profile-muted: #64748b;
            --profile-bg: #f8fafc;
            --profile-danger: #dc2626;
            --profile-success: #16a34a;
        }

        body { background: var(--profile-bg); }
        .profile-page { width: 100%; padding: 28px 32px; font-family: Inter, Arial, sans-serif; }
        .profile-head { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; margin-bottom:20px; }
        .profile-title { margin:0; color:var(--profile-navy); font-size:1.7rem; font-weight:800; }
        .profile-desc { margin:6px 0 0; color:var(--profile-muted); font-size:.92rem; line-height:1.5; }
        .profile-card { background:#fff; border:1px solid var(--profile-border); border-radius:14px; box-shadow:0 3px 14px rgba(15,23,42,.06); padding:22px; margin-bottom:18px; }
        .profile-section-title { display:flex; align-items:center; gap:8px; color:var(--profile-navy); font-weight:800; margin-bottom:16px; }
        .profile-grid { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:14px; }
        .profile-field { display:flex; flex-direction:column; gap:7px; }
        .profile-field.full { grid-column:1 / -1; }
        .profile-field label { font-size:.76rem; font-weight:800; color:#334155; text-transform:uppercase; letter-spacing:.04em; }
        .profile-field input, .profile-field select, .profile-field textarea {
            width:100%; border:1.5px solid var(--profile-border); border-radius:9px; padding:11px 12px;
            font:600 .9rem Inter, Arial, sans-serif; color:#0f172a; background:#fff;
        }
        .profile-field textarea { min-height:86px; resize:vertical; }
        .profile-field input:focus, .profile-field select:focus, .profile-field textarea:focus {
            outline:none; border-color:var(--profile-navy); box-shadow:0 0 0 3px rgba(15,60,118,.1);
        }
        .required { color:var(--profile-danger); }
        .profile-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:18px; }
        .profile-btn { border:0; border-radius:999px; padding:11px 18px; font-weight:800; cursor:pointer; display:inline-flex; align-items:center; gap:8px; }
        .profile-btn.primary { background:var(--profile-navy); color:#fff; }
        .profile-btn.secondary { background:#f1f5f9; color:#0f172a; }
        .family-row { display:grid; grid-template-columns:1fr 1fr 1.4fr auto; gap:10px; align-items:start; margin-bottom:10px; }
        .family-row input { border:1.5px solid var(--profile-border); border-radius:9px; padding:10px 11px; font-weight:600; }
        .family-remove { border:0; background:#fee2e2; color:#b91c1c; border-radius:9px; width:40px; height:40px; cursor:pointer; }
        .profile-toast { position:fixed; right:24px; bottom:24px; padding:12px 18px; border-radius:999px; font-weight:800; opacity:0; transform:translateY(12px); transition:.2s; z-index:20; }
        .profile-toast.show { opacity:1; transform:translateY(0); }
        .profile-toast.success { background:#f0fdf4; color:var(--profile-success); border:1px solid #bbf7d0; }
        .profile-toast.error { background:#fef2f2; color:var(--profile-danger); border:1px solid #fecaca; }

        @media (max-width: 920px) { .profile-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); } .family-row { grid-template-columns:1fr; } .family-remove { width:100%; } }
        @media (max-width: 620px) { .profile-page { padding:18px; } .profile-grid { grid-template-columns:1fr; } .profile-head { flex-direction:column; } }
    </style>
</head>
<body>
<div class="app-shell">
    <?php require_once __DIR__ . '/../../includes/sidebar_medical_staff.php'; ?>

    <main class="main-content">
        <div class="profile-page">
            <div class="profile-head">
                <div>
                    <h1 class="profile-title">My Profile</h1>
                    <p class="profile-desc">Update your personal information, emergency contact details, and family medical history.</p>
                </div>
            </div>

            <form id="profileInfoForm">
                <div class="profile-card">
                    <div class="profile-section-title"><i class="fa-solid fa-user"></i> Personal Information</div>
                    <div class="profile-grid">
                        <div class="profile-field">
                            <label for="contact_no">Contact No. <span class="required">*</span></label>
                            <input id="contact_no" name="contact_no" maxlength="20" required>
                        </div>
                        <div class="profile-field">
                            <label for="gender">Gender <span class="required">*</span></label>
                            <select id="gender" name="gender" required>
                                <option value="">Select gender</option>
                                <option>Male</option>
                                <option>Female</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="profile-field">
                            <label for="birth_date">Birth Date <span class="required">*</span></label>
                            <input type="date" id="birth_date" name="birth_date" required>
                        </div>
                        <div class="profile-field">
                            <label for="age">Age <span class="required">*</span></label>
                            <input type="number" id="age" name="age" min="1" max="120" required>
                        </div>
                        <div class="profile-field">
                            <label for="nationality">Nationality</label>
                            <input id="nationality" name="nationality" maxlength="50">
                        </div>
                        <div class="profile-field">
                            <label for="status">Status</label>
                            <input id="status" name="status" maxlength="20" placeholder="Single, Married, Active">
                        </div>
                        <div class="profile-field">
                            <label for="religion">Religion</label>
                            <input id="religion" name="religion" maxlength="30">
                        </div>
                        <div class="profile-field full">
                            <label for="address">Address</label>
                            <textarea id="address" name="address"></textarea>
                        </div>
                    </div>
                </div>

                <div class="profile-card">
                    <div class="profile-section-title"><i class="fa-solid fa-phone-volume"></i> Emergency Contact</div>
                    <div class="profile-grid">
                        <div class="profile-field">
                            <label for="guardian_name">Guardian Name</label>
                            <input id="guardian_name" name="guardian_name" maxlength="100">
                        </div>
                        <div class="profile-field">
                            <label for="relationship">Relationship</label>
                            <input id="relationship" name="relationship" maxlength="50">
                        </div>
                        <div class="profile-field">
                            <label for="mobile_no">Mobile No.</label>
                            <input id="mobile_no" name="mobile_no" maxlength="20">
                        </div>
                        <div class="profile-field">
                            <label for="telephone">Telephone</label>
                            <input id="telephone" name="telephone" maxlength="20">
                        </div>
                        <div class="profile-field full">
                            <label for="emergency_address">Emergency Address</label>
                            <textarea id="emergency_address" name="emergency_address"></textarea>
                        </div>
                    </div>
                </div>

                <div class="profile-card">
                    <div class="profile-section-title"><i class="fa-solid fa-people-roof"></i> Family History</div>
                    <div id="familyRows"></div>
                    <button class="profile-btn secondary" type="button" id="addFamilyRow">
                        <i class="fa-solid fa-plus"></i> Add Family History
                    </button>
                </div>

                <div class="profile-actions">
                    <button class="profile-btn primary" type="submit" id="saveProfileBtn">
                        <i class="fa-solid fa-floppy-disk"></i> Save Profile
                    </button>
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
    const form = document.getElementById('profileInfoForm');
    const familyRows = document.getElementById('familyRows');

    function addFamilyRow(item = {}) {
        const row = document.createElement('div');
        row.className = 'family-row';
        row.innerHTML = `
            <input name="condition_name" placeholder="Condition, e.g. Hypertension" value="${esc(item.condition_name || '')}">
            <input name="family_relationship" placeholder="Relationship, e.g. Mother" value="${esc(item.relationship || '')}">
            <input name="family_notes" placeholder="Notes" value="${esc(item.notes || '')}">
            <button type="button" class="family-remove" aria-label="Remove family history"><i class="fa-solid fa-xmark"></i></button>
        `;
        row.querySelector('.family-remove').addEventListener('click', () => row.remove());
        familyRows.appendChild(row);
    }

    function collectFamily() {
        return Array.from(familyRows.querySelectorAll('.family-row')).map(row => ({
            condition_name: row.querySelector('[name="condition_name"]').value.trim(),
            relationship: row.querySelector('[name="family_relationship"]').value.trim(),
            notes: row.querySelector('[name="family_notes"]').value.trim()
        })).filter(item => item.condition_name || item.relationship || item.notes);
    }

    function fill(data) {
        const info = data.patientsInfo || {};
        fields.forEach(name => {
            const el = document.getElementById(name);
            if (el) el.value = info[name] ?? '';
        });
        familyRows.innerHTML = '';
        (data.familyHistory || []).forEach(addFamilyRow);
        if (!familyRows.children.length) addFamilyRow();
    }

    function toast(message, type) {
        const el = document.getElementById('profileToast');
        el.textContent = message;
        el.className = 'profile-toast show ' + (type || 'success');
        setTimeout(() => el.classList.remove('show'), 2600);
    }

    function esc(value) {
        return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    document.getElementById('addFamilyRow').addEventListener('click', () => addFamilyRow());

    fetch('../../ajax/profile_info.ajax.php')
        .then(r => r.json())
        .then(data => { if (!data.ok) throw new Error(data.message || 'Load failed.'); fill(data); })
        .catch(err => toast(err.message, 'error'));

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        const body = new FormData(form);
        body.set('family_history', JSON.stringify(collectFamily()));
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
