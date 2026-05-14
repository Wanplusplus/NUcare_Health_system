<?php
/**
 * login.php — Login form and authentication handler.
 *
 * GET  → show the form.
 * POST → validate credentials, start session, redirect.
 */
session_start();

// Already logged in? Go straight to the dashboard.
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        // TODO: replace with real DB auth once the `users` table exists.
        // For now, any non-empty username/password is accepted.
        session_regenerate_id(true);
        $_SESSION['user_id']  = 1;
        $_SESSION['username'] = $username;
        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NUCARE — Sign In</title>
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
<div class="login-container">

    <!-- ── Left branding panel ── -->
    <div class="login-left">
        <div class="left-overlay"></div>
        <div class="left-particles" aria-hidden="true">
            <span></span><span></span><span></span><span></span><span></span>
        </div>
        <div class="logo-section">
            <div class="logo-placeholder">
                <img src="assets/image/nucarelogo.png" alt="NUCARE Logo" class="logo-icon">
            </div>
            <h1 class="brand-name">NUCARE</h1>
            <p class="brand-subtitle">Health Management System</p>
            <div class="heartbeat-divider" aria-hidden="true">
                <span></span><i></i><span></span>
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

    <!-- ── Right form panel ── -->
    <div class="login-right">
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

            <?php if ($error !== ''): ?>
                <div class="error-banner" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!--
                The form posts to itself (login.php).
                No JS is needed for a standard server-side auth flow.
            -->
            <form class="login-form" method="post" action="login.php">
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-shell">
                        <span class="input-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21a8 8 0 0 0-16 0"></path>
                                <circle cx="12" cy="8" r="4"></circle>
                            </svg>
                        </span>
                        <input type="text" id="username" name="username"
                               class="form-input" placeholder="Enter your username"
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                               required autocomplete="username">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-shell password-wrapper">
                        <span class="input-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="4" y="11" width="16" height="9" rx="2"></rect>
                                <path d="M8 11V8a4 4 0 1 1 8 0v3"></path>
                            </svg>
                        </span>
                        <input type="password" id="password" name="password"
                               class="form-input" placeholder="Enter your password"
                               required autocomplete="current-password">
                        <button type="button" class="password-toggle"
                                onclick="this.previousElementSibling.type =
                                         this.previousElementSibling.type === 'password'
                                         ? 'text' : 'password'"
                                aria-label="Toggle password visibility">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember">
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
            </form>
        </div>
    </div>

</div>
</body>
</html>
