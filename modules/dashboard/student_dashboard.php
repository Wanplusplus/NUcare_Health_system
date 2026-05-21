<?php
// Student dashboard entrypoint.
// Your project routes "student_dashboard.php" from index.php, but the dashboard
// content is role-based (medical staff vs admin). This file keeps routing stable.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If user is already an admin, send them to admin dashboard.
if (isset($_SESSION['UserID']) && isset($_SESSION['Roles']) && is_array($_SESSION['Roles']) && array_intersect($_SESSION['Roles'], ['Admin', 'Super Admin']) !== []) {
    header('Location: admin_dashboard.php');
    exit;
}

// If user is an authenticated medical staff user, send to the staff dashboard.
if (isset($_SESSION['UserID']) || isset($_SESSION['patient_id'])) {
    header('Location: medical_staff_dashboard.php');
    exit;
}

// Otherwise, treat as unauthenticated.
header('Location: ../../auth/login.php');
exit;



