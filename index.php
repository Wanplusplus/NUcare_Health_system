<?php
session_start();

if (isset($_SESSION['patient_id'])) {
    header('Location: modules/dashboard/dashboard.php');
    exit;
}

header('Location: auth/login.php');
exit;