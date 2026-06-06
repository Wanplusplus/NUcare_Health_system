function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const passwordToggle = document.getElementById('passwordToggleBtn');
    const eyeIcon = passwordToggle.querySelector('.eye-icon');
    const showPassword = passwordInput.type === 'password';
    const openEyeIcon = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
    const closedEyeIcon = '<path d="M3 3l18 18"></path><path d="M10.58 10.58a2 2 0 0 0 2.83 2.83"></path><path d="M9.88 4.26A10.49 10.49 0 0 1 12 4c7 0 11 8 11 8a17.06 17.06 0 0 1-3.08 4.21"></path><path d="M6.61 6.61A17.32 17.32 0 0 0 1 12s4 8 11 8a10.86 10.86 0 0 0 5.39-1.61"></path>';

    passwordInput.type = showPassword ? 'text' : 'password';
    passwordToggle.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
    passwordToggle.setAttribute('aria-pressed', showPassword ? 'true' : 'false');
    eyeIcon.innerHTML = showPassword ? openEyeIcon : closedEyeIcon;
}

function showError(elementId, message) {
    const errorElement = document.getElementById(elementId);
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.classList.add('show');
    }
}

function clearErrors() {
    document.querySelectorAll('.form-error').forEach(element => {
        element.textContent = '';
        element.classList.remove('show');
    });

    const errorMessage = document.getElementById('errorMessage');
    if (errorMessage) {
        errorMessage.textContent = '';
        errorMessage.classList.remove('show');
        errorMessage.style.display = 'none';
    }
}

function showErrorMessage(message) {
    const errorMessage = document.getElementById('errorMessage');
    if (errorMessage) {
        errorMessage.textContent = message;
        errorMessage.style.display = 'block';
        errorMessage.classList.add('show');
    }
}

function validateLoginForm() {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value.trim();
    let isValid = true;

    clearErrors();

    if (username === '') {
        showError('usernameError', 'School ID is required');
        isValid = false;
    }

    if (password === '') {
        showError('passwordError', 'Password is required');
        isValid = false;
    } else if (password.length < 6) {
        showError('passwordError', 'Password must be at least 6 characters');
        isValid = false;
    }

    return isValid;
}

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const loginButton = document.querySelector('.login-button');

    usernameInput.addEventListener('input', function() {
        document.getElementById('usernameError').textContent = '';
    });

    passwordInput.addEventListener('input', function() {
        document.getElementById('passwordError').textContent = '';
    });

    const forgotPasswordLink = document.querySelector('.forgot-password');
    if (forgotPasswordLink) {
        forgotPasswordLink.addEventListener('click', function(e) {
            e.preventDefault();
            alert('Forgot password flow is not yet available.');
        });
    }

    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        if (!validateLoginForm()) {
            return;
        }

        const originalButtonHTML = loginButton.innerHTML;
        loginButton.disabled = true;
        loginButton.innerHTML = '<span>Signing in...</span>';

        const formData = new FormData(loginForm);

        try {
            const response = await fetch('login_ajax.php', {
                method: 'POST',
                body: formData
            });

            const rawText = await response.text();
            console.log('LOGIN RAW RESPONSE:', rawText);

            const result = JSON.parse(rawText);

            if (result.status === 'success') {
                window.location.href = result.redirect;
            } else {
                showErrorMessage(result.message);
            }
        } catch (error) {
            console.error('LOGIN ERROR:', error);
            showErrorMessage('Something went wrong. Please try again.');
        } finally {
            loginButton.disabled = false;
            loginButton.innerHTML = originalButtonHTML;
        }
    });

    usernameInput.focus();
});
