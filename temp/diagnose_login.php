<?php
declare(strict_types=1);

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_POST['username'] = 'SCH-8001';
$_POST['password'] = 'DemoPass123!';

require __DIR__ . '/../auth/login_ajax.php';
