document.addEventListener('DOMContentLoaded', () => {

    const form            = document.getElementById('registerForm');
    const successMessage  = document.getElementById('successMessage');
    const errorMessage    = document.getElementById('errorMessage');
    const emailInput      = document.getElementById('email'); // ✅ NEW

    // ─── Real-time email validation ──────────────────────────────────────────
    emailInput.addEventListener('input', () => {
        const emailError = document.getElementById('emailError');
        const value = emailInput.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (value === '') {
            emailError.textContent = '';
        } else if (!emailRegex.test(value)) {
            emailError.textContent = 'Please enter a valid email address.';
        } else {
            emailError.textContent = '';
        }
    });

    // ─── Form submit ─────────────────────────────────────────────────────────
    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearErrors();
        successMessage.style.display = 'none';
        errorMessage.style.display   = 'none';

        // Collect values
        const firstName       = document.getElementById('first_name').value.trim();
        const lastName        = document.getElementById('last_name').value.trim();
        const middleName      = document.getElementById('middle_name').value.trim();
        const sex             = document.getElementById('sex').value;
        const schoolId        = document.getElementById('school_id').value.trim();
        const email           = emailInput.value.trim(); // ✅ NEW
        const password        = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        let hasError = false;

        // ─── Client-side validation ──────────────────────────────────────────
        if (firstName === '') {
            document.getElementById('firstNameError').textContent = 'First name is required.';
            hasError = true;
        }

        if (lastName === '') {
            document.getElementById('lastNameError').textContent = 'Last name is required.';
            hasError = true;
        }

        if (sex === '') {
            document.getElementById('sexError').textContent = 'Sex is required.';
            hasError = true;
        }

        if (schoolId === '') {
            document.getElementById('schoolIdError').textContent = 'School ID is required.';
            hasError = true;
        }

        // ✅ NEW: Email validation
        if (email === '') {
            document.getElementById('emailError').textContent = 'Email address is required.';
            hasError = true;
        } else if (!emailRegex.test(email)) {
            document.getElementById('emailError').textContent = 'Please enter a valid email address.';
            hasError = true;
        }

        if (password.length < 6) {
            document.getElementById('passwordError').textContent = 'Password must be at least 6 characters.';
            hasError = true;
        }

        if (password !== confirmPassword) {
            document.getElementById('confirmPasswordError').textContent = 'Passwords do not match.';
            hasError = true;
        }

        if (hasError) return;

        // ─── Build FormData ──────────────────────────────────────────────────
        const formData = new FormData();
        formData.append('first_name',       firstName);
        formData.append('last_name',        lastName);
        formData.append('middle_name',      middleName);
        formData.append('sex',              sex);
        formData.append('school_id',        schoolId);
        formData.append('email',            email); // ✅ NEW
        formData.append('password',         password);
        formData.append('confirm_password', confirmPassword);

        // ─── Send AJAX request ───────────────────────────────────────────────
        try {
            const response = await fetch('register_ajax.php', {
                method: 'POST',
                body: formData
            });

            const rawText = await response.text();
            console.log('RAW RESPONSE:', rawText);

            const result = JSON.parse(rawText);

            if (result.status === 'success') {
                successMessage.textContent    = result.message;
                successMessage.style.display  = 'block';
                form.reset();

                // Redirect to login after 1.5 seconds
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 1500);

            } else {
                errorMessage.textContent   = result.message;
                errorMessage.style.display = 'block';
            }

        } catch (error) {
            console.error('REGISTER ERROR:', error);
            errorMessage.textContent   = 'Something went wrong. Please try again.';
            errorMessage.style.display = 'block';
        }
    });

    // ─── Clear all error messages ────────────────────────────────────────────
    function clearErrors() {
        const ids = [
            'firstNameError', 'lastNameError', 'middleNameError',
            'sexError', 'schoolIdError', 'emailError',
            'passwordError', 'confirmPasswordError'
        ];
        ids.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = '';
        });
    }

});