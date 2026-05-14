<?php
/**
 * includes/auth_guard.php
 *
 * Require this at the TOP of every authenticated page (before any output):
 *   require_once __DIR__ . '/includes/auth_guard.php';
 *
 * If the user is not logged in they get bounced to login.php immediately.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
