document.addEventListener('DOMContentLoaded', () => {

    const form            = document.getElementById('resetPasswordForm');
    const newPassInput    = document.getElementById('new_password');
    const confirmInput    = document.getElementById('confirm_password');
    const tokenInput      = document.getElementById('reset_token');
    const newPassError    = document.getElementById('newPasswordError');
    const confirmError    = document.getElementById('confirmPasswordError');
    const resetBtn        = document.getElementById('resetBtn');
    const resetBtnText    = document.getElementById('resetBtnText');
    const resetSpinner    = document.getElementById('resetSpinner');
    const strengthBar     = document.getElementById('strengthBar');
    const strengthText    = document.getElementById('strengthText');

    // ── Reset result modal (show ONLY after backend success/error) ─────────
    function showResetModal(message, type = 'success', onOk = null) {
        // Remove any existing modal
        const old = document.getElementById('resetPasswordResultModal');
        if (old) old.remove();

        const overlay = document.createElement('div');
        overlay.id = 'resetPasswordResultModal';
        overlay.style.cssText = `position:fixed;inset:0;background:rgba(15,23,42,.6);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;z-index:3000;`;

        const accent = type === 'success'
            ? 'linear-gradient(135deg, #0f3c76, #0d9488)'
            : 'linear-gradient(135deg, #b91c1c, #ef4444)';
        const btnBg  = type === 'success' ? '#0d9488' : '#dc2626';
        const title  = type === 'success' ? 'Success' : 'Error';

        overlay.innerHTML = `
            <div style="background:#fff;border-radius:16px;width:100%;max-width:440px;box-shadow:0 32px 80px rgba(0,0,0,.35);overflow:hidden;">
                <div style="padding:16px 18px;background:${accent};color:#fff;display:flex;align-items:center;justify-content:space-between;">
                    <div style="font-weight:800;">${title}</div>
                    <button type="button" id="resetPasswordModalClose" aria-label="Close" style="background:rgba(255,255,255,.15);border:none;border-radius:10px;width:34px;height:34px;color:#fff;cursor:pointer;">✕</button>
                </div>
                <div style="padding:18px 18px 20px;">
                    <div style="font-size:14px;font-weight:600;color:#1e293b;line-height:1.5;">${message}</div>
                    <div style="margin-top:16px;display:flex;justify-content:flex-end;gap:12px;">
                        <button type="button" id="resetPasswordModalOk" style="padding:10px 14px;border-radius:12px;border:1px solid rgba(0,0,0,.08);background:${btnBg};color:#fff;font-weight:700;cursor:pointer;">OK</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';

        const cleanup = () => {
            overlay.remove();
            document.body.style.overflow = '';
        };

        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) cleanup();
        });

        overlay.querySelector('#resetPasswordModalClose')?.addEventListener('click', cleanup, { once: true });

        overlay.querySelector('#resetPasswordModalOk')?.addEventListener('click', () => {
            cleanup();
            if (typeof onOk === 'function') onOk();
        }, { once: true });
    }

    // ── Loading state ─────────────────────────────────────────────────────────

    function setLoading(loading) {
        resetBtn.disabled           = loading;
        resetBtnText.style.display  = loading ? 'none'         : 'inline';
        resetSpinner.style.display  = loading ? 'inline-block' : 'none';
    }

    // ── Show / Hide password toggle ───────────────────────────────────────────
    document.querySelectorAll('.toggle-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = document.getElementById(btn.dataset.target);
            const isPassword = target.type === 'password';
            target.type = isPassword ? 'text' : 'password';
            btn.querySelector('.eye-icon').style.opacity = isPassword ? '.4' : '1';
        });
    });

    // ── Password strength checker ─────────────────────────────────────────────
    newPassInput.addEventListener('input', () => {
        const val = newPassInput.value;
        let score = 0;

        if (val.length >= 8)              score++;
        if (/[A-Z]/.test(val))            score++;
        if (/[a-z]/.test(val))            score++;
        if (/\d/.test(val))               score++;
        if (/[^A-Za-z0-9]/.test(val))     score++;

        if (val.length === 0) {
            strengthBar.style.width      = '0%';
            strengthBar.style.background = '';
            strengthText.textContent     = '';
            strengthText.style.color     = '';
        } else if (score <= 2) {
            strengthBar.style.width      = '33%';
            strengthBar.style.background = '#dc2626';
            strengthText.textContent     = 'Weak password';
            strengthText.style.color     = '#dc2626';
        } else if (score <= 3) {
            strengthBar.style.width      = '66%';
            strengthBar.style.background = '#f59e0b';
            strengthText.textContent     = 'Medium password';
            strengthText.style.color     = '#f59e0b';
        } else {
            strengthBar.style.width      = '100%';
            strengthBar.style.background = '#16a34a';
            strengthText.textContent     = 'Strong password ✓';
            strengthText.style.color     = '#16a34a';
        }
    });

    // ── Form submit ───────────────────────────────────────────────────────────
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        newPassError.textContent   = '';
        confirmError.textContent   = '';

        const token           = tokenInput.value.trim();
        const newPassword     = newPassInput.value;
        const confirmPassword = confirmInput.value;

        // Client-side validation
        if (newPassword.length < 6) {
            newPassError.textContent = 'Password must be at least 6 characters.';
            return;
        }

        if (newPassword !== confirmPassword) {
            confirmError.textContent = 'Passwords do not match.';
            return;
        }

        setLoading(true);

        try {
            const formData = new FormData();
            formData.append('token',            token);
            formData.append('new_password',     newPassword);
            formData.append('confirm_password', confirmPassword);

            const response = await fetch('../ajax/reset_password.ajax.php', {
                method: 'POST',
                body: formData
            });

            const raw = await response.text();
            console.log('RESET RESPONSE:', raw);


            // If backend returns JSON error, show it instead of generic message
            if (raw && raw.trim().startsWith('{')) {
                try {
                    const parsedDebug = JSON.parse(raw);
                    if (parsedDebug && parsedDebug.message) {
                        showResetModal(parsedDebug.message, parsedDebug.status === 'success' ? 'success' : 'error');
                        return;

                    }
                } catch (e) {}
            }
            let result;
            try {
                result = JSON.parse(raw);
            } catch (e) {
                console.error('RESET PARSE ERROR. Raw response:', raw);
                showResetModal('Backend returned non-JSON response. Check console.', 'error');
                return;

            }

            if (result.status === 'success') {
                form.reset();
                const redirectUrl = result.redirect || 'login.php';
                showResetModal(result.message || 'Password updated successfully!', 'success', () => {
                    window.location.href = redirectUrl;
                });
            } else {
                showResetModal(result.message || 'Unable to update password.', 'error');
            }

            // prevent any other UI feedback from running
            return;



        } catch (err) {
            console.error('RESET ERROR:', err);
            showResetModal('Something went wrong. Please try again.', 'error');
        } finally {

            setLoading(false);
        }
    });

});