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
        <div class="login-left">
            <div class="medical-grid" aria-hidden="true"></div>
            <div class="medical-building" aria-hidden="true"></div>
            <div class="logo-section">
                <div class="logo-placeholder">
                    <img src="assets/image/nucarelogo.png" alt="NUCARE Logo" class="logo-icon" style="width: 200px; height: auto;">
                </div>
                <h1 class="brand-name">NUCARE</h1>
                <p class="brand-subtitle">Health Management System</p>
                <div class="brand-divider" aria-hidden="true">
                    <span></span>
                    <i></i>
                    <span></span>
                </div>
                <p class="brand-tagline">Your <strong>Health</strong>, Our Priority</p>
                <p class="brand-description">
                    Empowering better healthcare through innovation, compassion, and excellence.
                </p>
                <div class="feature-list">
                    <div class="feature-item">
                        <span class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M4 12h4l2-4 4 8 2-4h4"></path>
                                <path d="M6 18h12"></path>
                            </svg>
                        </span>
                        <div>
                            <strong>Trusted Care</strong>
                            <p>You can rely on</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M12 3l7 4v5c0 4.5-3 7.8-7 9-4-1.2-7-4.5-7-9V7l7-4z"></path>
                                <path d="M12 8v6"></path>
                                <path d="M9 11h6"></path>
                            </svg>
                        </span>
                        <div>
                            <strong>Secure &amp; Safe</strong>
                            <p>Your data is protected</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <circle cx="9" cy="8" r="4"></circle>
                                <circle cx="17" cy="8" r="3"></circle>
                                <path d="M3 20a6 6 0 0 1 12 0"></path>
                                <path d="M14 20a5 5 0 0 1 7 0"></path>
                            </svg>
                        </span>
                        <div>
                            <strong>Patient First</strong>
                            <p>Always putting you at the center</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M12 21s-6-3.8-8.5-8.1C1.7 9.8 3.3 6 7 6c2 0 3.2 1 4 2 0.8-1 2-2 4-2 3.7 0 5.3 3.8 3.5 6.9C18 17.2 12 21 12 21z"></path>
                                <path d="M12 8v4"></path>
                                <path d="M10 10h4"></path>
                            </svg>
                        </span>
                        <div>
                            <strong>Compassionate</strong>
                            <p>We care with heart</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="login-right">
            <div class="form-container">
                <div class="card-badge" aria-hidden="true">
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
