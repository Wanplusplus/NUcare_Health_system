document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('registerForm');
    const successMessage = document.getElementById('successMessage');
    const errorMessage = document.getElementById('errorMessage');
    const emailInput = document.getElementById('email');

    const emailError = document.getElementById('emailError');
    const firstNameError = document.getElementById('firstNameError');
    const lastNameError = document.getElementById('lastNameError');
    const middleNameError = document.getElementById('middleNameError');
    const sexError = document.getElementById('sexError');
    const schoolIdError = document.getElementById('schoolIdError');
    const passwordError = document.getElementById('passwordError');
    const confirmPasswordError = document.getElementById('confirmPasswordError');

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    function clearErrors() {
        firstNameError.textContent = '';
        lastNameError.textContent = '';
        middleNameError.textContent = '';
        sexError.textContent = '';
        schoolIdError.textContent = '';
        emailError.textContent = '';
        passwordError.textContent = '';
        confirmPasswordError.textContent = '';
    }

    function setMessage(el, text, show = true) {
        el.textContent = text;
        el.style.display = show ? 'block' : 'none';
    }

    // Real-time email validation
    emailInput.addEventListener('input', () => {
        const value = emailInput.value.trim();

        if (value === '') {
            emailError.textContent = '';
        } else if (!emailRegex.test(value)) {
            emailError.textContent = 'Please enter a valid email address.';
        } else {
            emailError.textContent = '';
        }
    });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        clearErrors();
        setMessage(successMessage, '', false);
        setMessage(errorMessage, '', false);

        const firstName = document.getElementById('first_name').value.trim();
        const lastName = document.getElementById('last_name').value.trim();
        const middleName = document.getElementById('middle_name').value.trim();
        const sex = document.getElementById('sex').value;
        const schoolId = document.getElementById('school_id').value.trim();
        const email = emailInput.value.trim();
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        let hasError = false;

        if (firstName === '') {
            firstNameError.textContent = 'First name is required.';
            hasError = true;
        }

        if (lastName === '') {
            lastNameError.textContent = 'Last name is required.';
            hasError = true;
        }

        if (sex === '') {
            sexError.textContent = 'Sex is required.';
            hasError = true;
        }

        if (schoolId === '') {
            schoolIdError.textContent = 'School ID is required.';
            hasError = true;
        }

        if (email === '') {
            emailError.textContent = 'Email address is required.';
            hasError = true;
        } else if (!emailRegex.test(email)) {
            emailError.textContent = 'Please enter a valid email address.';
            hasError = true;
        }

        if (password.length < 6) {
            passwordError.textContent = 'Password must be at least 6 characters.';
            hasError = true;
        }

        if (password !== confirmPassword) {
            confirmPasswordError.textContent = 'Passwords do not match.';
            hasError = true;
        }

        if (hasError) return;

        const formData = new FormData();
        formData.append('first_name', firstName);
        formData.append('last_name', lastName);
        formData.append('middle_name', middleName);
        formData.append('sex', sex);
        formData.append('school_id', schoolId);
        formData.append('email', email);
        formData.append('password', password);
        formData.append('confirm_password', confirmPassword);

        try {
            const response = await fetch('register_ajax.php', {
                method: 'POST',
                body: formData
            });

            const rawText = await response.text();
            console.log('RAW RESPONSE:', rawText);

            let result;
            try {
                result = JSON.parse(rawText);
            } catch (jsonError) {
                console.error('Invalid JSON response:', rawText);
                throw new Error('Server returned invalid JSON. Check PHP errors.');
            }

            if (result.status === 'success') {
                setMessage(successMessage, result.message, true);
                form.reset();

                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 1500);
            } else {
                setMessage(errorMessage, result.message || 'Registration failed.', true);
            }
        } catch (error) {
            console.error('REGISTER ERROR:', error);
            setMessage(errorMessage, error.message || 'Something went wrong. Please try again.', true);
        }
    });
});