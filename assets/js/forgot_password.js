document.addEventListener('DOMContentLoaded', () => {

    const form        = document.getElementById('forgotPasswordForm');
    const emailInput  = document.getElementById('email');
    const emailError  = document.getElementById('emailError');
    const submitBtn   = document.getElementById('submitBtn');
    const btnText     = document.getElementById('btnText');
    const btnSpinner  = document.getElementById('btnSpinner');

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
        submitBtn.disabled       = loading;
        btnText.style.display    = loading ? 'none'         : 'inline';
        btnSpinner.style.display = loading ? 'inline-block' : 'none';
    }

    // ── Real-time email validation ────────────────────────────────────────────
    emailInput.addEventListener('input', () => {
        const val   = emailInput.value.trim();
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        emailError.textContent = val && !regex.test(val) ? 'Please enter a valid email address.' : '';
    });

    // ── Form submit ───────────────────────────────────────────────────────────
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        emailError.textContent = '';

        const email = emailInput.value.trim();
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        // Client-side validation
        if (!email) {
            emailError.textContent = 'Email address is required.';
            return;
        }
        if (!regex.test(email)) {
            emailError.textContent = 'Please enter a valid email address.';
            return;
        }

        setLoading(true);

        try {
            const formData = new FormData();
            formData.append('email', email);

            const response = await fetch('../ajax/forgot_password.ajax.php', {
                method: 'POST',
                body: formData
            });

            const raw = await response.text();
            console.log('FORGOT RESPONSE:', raw);
            const result = JSON.parse(raw);

            if (result.status === 'success') {
                showToast(result.message, 'success');
                form.reset();
            } else {
                // Show "User not found" or other error in the field + toast
                emailError.textContent = result.message;
                showToast(result.message, 'error');
            }

        } catch (err) {
            console.error('FORGOT ERROR:', err);
            showToast('Something went wrong. Please try again.', 'error');
        } finally {
            setLoading(false);
        }
    });

});