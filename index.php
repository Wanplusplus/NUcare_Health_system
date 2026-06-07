<?php
session_start();

if (isset($_SESSION['UserID'])) {
 $roles = $_SESSION['Roles'] ?? [];
 if (is_array($roles) && array_intersect($roles, ['Admin', 'Super Admin']) !== []) {
 header('Location: /NUcare_Health_system/frontend/admin/dashboard/admin_dashboard.php');
 } else {
 header('Location: /NUcare_Health_system/frontend/student/dashboard/student_dashboard.php');
 }
 exit;
}

if (isset($_SESSION['patient_id'])) {
 header('Location: /NUcare_Health_system/frontend/student/dashboard/student_dashboard.php');
 exit;
}

header('Location: /NUcare_Health_system/frontend/auth/login.php');
exit;
