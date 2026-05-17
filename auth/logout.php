<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../includes/audit.php';

$actorUserId = isset($_SESSION['UserID']) ? (int)$_SESSION['UserID'] : null;
$actorSchoolPersonId = isset($_SESSION['SchoolPersonID']) ? (int)$_SESSION['SchoolPersonID'] : null;
$ip = $_SERVER['REMOTE_ADDR'] ?? null;

// Audit logout (best-effort)
try {
    auditLog($actorUserId, $actorSchoolPersonId, 'logout', 'auth', null, null, $ip);
} catch (Throwable $e) {
    // ignore
}

// Proper session teardown
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'] ?? '/', $params['domain'] ?? '', $params['secure'] ?? false, $params['httponly'] ?? true);
}
session_regenerate_id(true);
session_destroy();

header('Location: login.php');
exit;
