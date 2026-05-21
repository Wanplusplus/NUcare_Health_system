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

    // ── Toast helper ──────────────────────────────────────────────────────────
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
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
                        showToast(parsedDebug.message, parsedDebug.status === 'success' ? 'success' : 'error');
                        return;
                    }
                } catch (e) {}
            }
            let result;
            try {
                result = JSON.parse(raw);
            } catch (e) {
                console.error('RESET PARSE ERROR. Raw response:', raw);
                showToast('Backend returned non-JSON response. Check console.', 'error');
                return;
            }

            if (result.status === 'success') {
                showToast(result.message, 'success');
                form.reset();
                // Redirect to login after 2 seconds
                setTimeout(() => {
                    window.location.href = result.redirect || 'login.php';
                }, 2000);
            } else {
                showToast(result.message, 'error');
            }

        } catch (err) {
            console.error('RESET ERROR:', err);
            showToast('Something went wrong. Please try again.', 'error');
        } finally {
            setLoading(false);
        }
    });

});