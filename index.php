<?php
/**
 * index.php — Entry point.
 * Redirects authenticated users to the dashboard,
 * everyone else to the login page.
 */
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit;
