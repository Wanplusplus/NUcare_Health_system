<?php
declare(strict_types=1);

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_POST['first_name'] = 'Maria';
$_POST['last_name'] = 'Reyes';
$_POST['middle_name'] = 'B';
$_POST['sex'] = 'Female';
$_POST['school_id'] = 'SCH-1002';
$_POST['email'] = 'maria.reyes@nucare.edu';
$_POST['password'] = '123123';
$_POST['confirm_password'] = '123123';

require __DIR__ . '/../auth/register_ajax.php';
