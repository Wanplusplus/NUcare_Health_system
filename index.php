<?php
session_start();

if (isset($_SESSION['UserID'])) {
    $roles = $_SESSION['Roles'] ?? [];
    if (is_array($roles) && array_intersect($roles, ['Admin', 'Super Admin']) !== []) {
        header('Location: modules/dashboard/admin_dashboard.php');
    } else {
        header('Location: modules/dashboard/student_dashboard.php');
    }
    exit;
}

if (isset($_SESSION['patient_id'])) {
    header('Location: modules/dashboard/student_dashboard.php');
    exit;
}

header('Location: auth/login.php');
exit;
