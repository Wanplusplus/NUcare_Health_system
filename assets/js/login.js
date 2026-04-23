/* ============================================
   NUCARE Login Page JavaScript
   ============================================ */

/**
 * Toggle password visibility
 */
function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const passwordToggle = document.querySelector('.password-toggle');
    const eyeIcon = passwordToggle.querySelector('.eye-icon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        passwordToggle.setAttribute('aria-label', 'Hide password');
        eyeIcon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
    } else {
        passwordInput.type = 'password';
        passwordToggle.setAttribute('aria-label', 'Show password');
        eyeIcon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
    }
}

/**
 * Validate login form
 */
function validateLoginForm() {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value.trim();
    let isValid = true;

    // Clear previous errors
    clearErrors();

    // Username validation
    if (username === '') {
        showError('usernameError', 'Username is required');
        isValid = false;
    } else if (username.length < 3) {
        showError('usernameError', 'Username must be at least 3 characters');
        isValid = false;
    }

    // Password validation
    if (password === '') {
        showError('passwordError', 'Password is required');
        isValid = false;
    } else if (password.length < 6) {
        showError('passwordError', 'Password must be at least 6 characters');
        isValid = false;
    }

    return isValid;
}

/**
 * Show field-specific error
 */
function showError(elementId, message) {
    const errorElement = document.getElementById(elementId);
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.classList.add('show');
    }
}

/**
 * Clear all error messages
 */
function clearErrors() {
    const errorElements = document.querySelectorAll('.form-error');
    errorElements.forEach(element => {
        element.textContent = '';
        element.classList.remove('show');
    });

    const errorMessage = document.getElementById('errorMessage');
    if (errorMessage) {
        errorMessage.textContent = '';
        errorMessage.classList.remove('show');
    }
}

/**
 * Show general error message
 */
function showErrorMessage(message) {
    const errorMessage = document.getElementById('errorMessage');
    if (errorMessage) {
        errorMessage.textContent = message;
        errorMessage.classList.add('show');
    }
}

/**
 * Handle login form submission
 */
function handleLogin(event) {
    event.preventDefault();

    // Validate form
    if (!validateLoginForm()) {
        return;
    }

    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value.trim();
    const rememberMe = document.querySelector('input[name="remember"]').checked;
    const loginButton = document.querySelector('.login-button');

    // Disable button and show loading state
    loginButton.disabled = true;
    const originalText = loginButton.innerHTML;
    loginButton.innerHTML = '<span>Signing in...</span>';

    // Prepare login data
    const loginData = {
        username: username,
        password: password,
        remember_me: rememberMe
    };

    // For demo purposes, redirect to dashboard directly
    // In production, replace with actual authentication
    setTimeout(() => {
        window.location.href = 'dashboard.php';
    }, 1000);
}

/**
 * Initialize form with event listeners
 */
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');

    // Clear error messages on input
    usernameInput.addEventListener('input', function() {
        if (this.value.length > 0) {
            const errorElement = document.getElementById('usernameError');
            if (errorElement) {
                errorElement.textContent = '';
                errorElement.classList.remove('show');
            }
        }
    });

    passwordInput.addEventListener('input', function() {
        if (this.value.length > 0) {
            const errorElement = document.getElementById('passwordError');
            if (errorElement) {
                errorElement.textContent = '';
                errorElement.classList.remove('show');
            }
        }
    });

    // Forgot password link handler
    const forgotPasswordLink = document.querySelector('.forgot-password');
    if (forgotPasswordLink) {
        forgotPasswordLink.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Forgot password clicked - implement password reset flow');
            // TODO: Implement forgot password flow
        });
    }

    // Signup/Contact Admin link handler
    const signupLink = document.querySelector('.signup-link');
    if (signupLink) {
        signupLink.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Contact administrator clicked');
            // TODO: Implement contact form or admin notification
        });
    }

    // Allow Enter key to submit form
    loginForm.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            loginForm.dispatchEvent(new Event('submit'));
        }
    });

    // Set focus on username input on page load
    usernameInput.focus();
});
