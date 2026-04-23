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
                    <svg class="logo-icon" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                        <!-- Medical cross icon -->
                        <circle cx="50" cy="50" r="45" fill="none" stroke="#FFC72C" stroke-width="2"/>
                        <rect x="45" y="25" width="10" height="50" fill="#FFC72C"/>
                        <rect x="25" y="45" width="50" height="10" fill="#FFC72C"/>
                        <text x="50" y="75" font-size="12" text-anchor="middle" fill="#00205B" font-weight="bold">NUCARE</text>
                    </svg>
                </div>
                <h1 class="brand-name">NUCARE</h1>
                <p class="brand-tagline">Health Management System</p>
            </div>
        </div>

        <!-- Right Section: Login Form -->
        <div class="login-right">
            <div class="form-container">
                <h2 class="form-title">Welcome Back</h2>
                <p class="form-subtitle">Sign in to your account</p>

                <form id="loginForm" class="login-form" onsubmit="handleLogin(event)">
                    <!-- Username Field -->
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            class="form-input" 
                            placeholder="Enter your username"
                            required
                        >
                        <span class="form-error" id="usernameError"></span>
                    </div>

                    <!-- Password Field -->
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="password-wrapper">
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
                            >
                                <span class="eye-icon">👁️</span>
                            </button>
                        </div>
                        <span class="form-error" id="passwordError"></span>
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" name="remember" class="checkbox">
                            <span>Remember me</span>
                        </label>
                        <a href="#" class="forgot-password">Forgot password?</a>
                    </div>

                    <!-- Login Button -->
                    <button type="submit" class="login-button">
                        <span>Sign In</span>
                    </button>

                    <!-- Error Message -->
                    <div class="error-message" id="errorMessage"></div>
                </form>

                <!-- Footer -->
                <div class="form-footer">
                    <p>Don't have an account? <a href="#" class="signup-link">Contact Administrator</a></p>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/login.js"></script>
</body>
</html>
