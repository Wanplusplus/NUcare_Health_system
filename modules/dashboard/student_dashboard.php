<?php
declare(strict_types=1);

// Role router: keeps the old student_dashboard.php entrypoint stable while sending users
// to the correct landing page based on actual RBAC roles.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/rbac.php';

if (!isset($_SESSION['UserID'])) {
    header('Location: ../../auth/login.php');
    exit;
}


$roles = isset($_SESSION['Roles']) && is_array($_SESSION['Roles']) ? $_SESSION['Roles'] : [];
$landingKey = rbacGetLandingDashboardKey($roles);

if ($landingKey === 'admin') {
    header('Location: admin_dashboard.php');
    exit;
}

if ($landingKey === 'medical') {
    header('Location: medical_staff_dashboard.php');
    exit;
}

header('Location: patient_dashboard.php');
exit;
<<<<<<< HEAD



=======
>>>>>>> adminside
