<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Legacy UI used patient_id. RBAC login sets UserID/SchoolPersonID.
// Allow either so we don't block RBAC-authenticated users.
if (!isset($_SESSION['patient_id']) && !isset($_SESSION['UserID'])) {
    header('Location: ../../auth/login.php');
    exit;
}
?>
