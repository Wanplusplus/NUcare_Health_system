<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE - Health Management System</title>
    <link rel="stylesheet" href="assets/css/login.css?v=2">
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="left-overlay"></div>
            <div class="left-particles" aria-hidden="true">
                <span></span>
                <span></span>
                <span></span>
                <span></span>
                <span></span>
            </div>
            <div class="hospital-visual" aria-hidden="true"></div>
            <div class="curve-accent" aria-hidden="true"></div>
            <div class="curve-shadow" aria-hidden="true"></div>
            <div class="logo-section">
                <div class="logo-placeholder">
                    <img src="assets/image/nucarelogo.png" alt="NUCARE Logo" class="logo-icon">
                </div>
                <h1 class="brand-name">NUCARE</h1>
                <p class="brand-subtitle">Health Management System</p>
                <div class="heartbeat-divider" aria-hidden="true">
                    <span></span>
                    <i></i>
                    <span></span>
                </div>
                <p class="brand-tagline">Your <strong>Health</strong>, Our Priority</p>
                <p class="brand-description">Empowering better healthcare through innovation, compassion, and excellence.</p>
                <div class="feature-list">
                    <div class="feature-item">
                        <span class="feature-icon">♡</span>
                        <strong>Trusted Care</strong>
                        <p>You can rely on</p>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">+</span>
                        <strong>Secure &amp; Safe</strong>
                        <p>Your data is protected</p>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">◌</span>
                        <strong>Patient First</strong>
                        <p>Always putting you at the center</p>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">♥</span>
                        <strong>Compassionate</strong>
                        <p>We care with heart</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="login-right">
            <div class="medical-cross" aria-hidden="true"></div>
            <div class="right-light" aria-hidden="true"></div>
            <div class="form-container">
                <div class="avatar-badge" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path d="M20 21a8 8 0 0 0-16 0"></path>
                        <circle cx="12" cy="8" r="4"></circle>
                    </svg>
                </div>
                <h2 class="form-title">Welcome Back</h2>
                <p class="form-subtitle">Sign in to your account</p>
                <div class="title-underline" aria-hidden="true"></div>

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
                            <path d="M13 5l6 7-6 7"></path>
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

    <script src="assets/js/login.js?v=2"></script>
</body>
</html>
