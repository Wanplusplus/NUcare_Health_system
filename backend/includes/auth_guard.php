<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
 session_start();
}

// RBAC-only: authorization depends only on session UserID.
if (!isset($_SESSION['UserID'])) {
 header('Location: /NUcare_Health_system/frontend/auth/login.php');
 exit;
}
?>




