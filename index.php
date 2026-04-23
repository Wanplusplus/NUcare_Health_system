<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE - Health Management System</title>
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <!-- Left Section: Logo -->
        <div class="login-left">
            <div class="logo-section">
                <div class="logo-placeholder">
                        <img src="assets/image/nucarelogo.png" alt="NUCARE Logo" class="logo-icon" style="width: 200px; height: auto;">
                        <!-- Medical cross icon -->
                        <circle cx="50" cy="50" r="45" fill="none" stroke="#ffffff" stroke-width="2"/>
                        <rect x="45" y="25" width="10" height="50" fill="#ffffff"/>
                        <rect x="25" y="45" width="50" height="10" fill="#ffffff"/>
                        
                    </svg>
                </div>
                <h1 class="brand-name">NUCARE</h1>
                <p class="brand-tagline">Health Management System</p>
            </div>
        </div>

        <div class="login-right">
            <div class="form-container">
                <h2 class="form-title">Welcome Back</h2>
                <p class="form-subtitle">Sign in to your account</p>

                <form id="loginForm" class="login-form" onsubmit="handleLogin(event)">
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-shell">
                            <span class="input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21a8 8 0 0 0-16 0"></path>
                                    <circle cx="12" cy="8" r="4"></circle>
                                </svg>
                            </span>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                class="form-input" 
                                placeholder="Enter your username"
                                required
                            >
                        </div>
                        <span class="form-error" id="usernameError"></span>
                    </div>

                    <!-- Password Field -->
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="password-wrapper">
                            <span class="input-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="4" y="11" width="16" height="9" rx="2"></rect>
                                    <path d="M8 11V8a4 4 0 1 1 8 0v3"></path>
                                </svg>
                            </span>
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-input" 
                                placeholder="Enter your password"
                                required
                            >
                            <button 
                                type="button" 
                                class="password-toggle" 
                                onclick="togglePasswordVisibility()"
                                aria-label="Toggle password visibility"
                                id="passwordToggleBtn"
                            >
                                <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                        <span class="form-error" id="passwordError"></span>
                    </div>

                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" name="remember" class="checkbox">
                            <span>Remember me</span>
                        </label>
                        <a href="#" class="forgot-password">Forgot password?</a>
                    </div>

                    <button type="submit" class="login-button">
                        <span>Sign In</span>
                        <svg class="button-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14"></path>
                            <path d="M13 5l7 7-7 7"></path>
                        </svg>
                    </button>

                    <div class="error-message" id="errorMessage"></div>
                </form>

                <div class="form-footer">
                    <p>Don't have an account? <a href="#" class="signup-link">Sign Up</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/login.js"></script>
</body>
</html>
