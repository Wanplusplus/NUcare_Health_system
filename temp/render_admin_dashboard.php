<?php
declare(strict_types=1);

session_start();
$_SESSION['UserID'] = 1;
$_SESSION['patient_id'] = 1;
$_SESSION['patient_name'] = 'Admin User';
$_SESSION['Roles'] = ['Super Admin'];

require __DIR__ . '/../modules/dashboard/admin_dashboard.php';
