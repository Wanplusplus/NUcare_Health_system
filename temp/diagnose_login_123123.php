<?php
declare(strict_types=1);

$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_POST['username'] = 'SCH-1002';
$_POST['password'] = '123123';

require __DIR__ . '/../auth/login_ajax.php';
