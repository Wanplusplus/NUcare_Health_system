<?php
/**
 * logout.php — Destroys the session and sends the user back to login.
 *
 * Linked to from every page's "Logout" button:
 *   <a href="logout.php">Logout</a>
 */
session_start();
session_unset();
session_destroy();

// Clear the session cookie so the browser doesn't re-send a stale ID.
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

header('Location: login.php');
exit;
