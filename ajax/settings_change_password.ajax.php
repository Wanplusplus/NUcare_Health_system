<?php
declare(strict_types=1);

/* ════════════════════════════════════════════════════════════════════
   settings_change_password.ajax.php   →  PLACE IN:  ajax/
   ────────────────────────────────────────────────────────────────────
   Backend for your EXISTING assets/js/settings_change_password.js, which
   POSTs to ../../ajax/settings_change_password.ajax.php and expects:
       { "status": "success" | "error", "message": "..." }

   Implements spec MODULE 7 (Settings → password update, modal only):
     1. Validate session (must have UserID).
     2. Verify CURRENT password (password_verify against stored hash).
     3. New must match Confirm and be >= 6 chars (matches the JS check).
     4. Store new password as a bcrypt hash (password_hash).

   Schema is auto-detected so it adapts to your real `users` table:
     • Table:           'users'  (override $USER_TABLE if different)
     • Primary key:     UserID | id | user_id | ID
     • Password column: Password | PasswordHash | password | password_hash
══════════════════════════════════════════════════════════════════════ */

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();

$USER_TABLE = 'users';   // ← change if your account table has another name
$MIN_LENGTH = 6;         // matches the client-side rule in settings_change_password.js

function respond(string $status, string $message, array $extra = []): never {
    echo json_encode(['status' => $status, 'message' => $message] + $extra);
    exit;
}

if (!isset($_SESSION['UserID'])) {
    respond('error', 'Your session has expired. Please sign in again.');
}

$pdo = require __DIR__ . '/../config/db_pdo.php';

$current = (string)($_POST['current_password'] ?? '');
$new     = (string)($_POST['new_password']     ?? '');
$confirm = (string)($_POST['confirm_password'] ?? '');

if ($current === '')               respond('error', 'Current password is required.');
if ($new === '')                   respond('error', 'Please enter a new password.');
if (mb_strlen($new) < $MIN_LENGTH) respond('error', 'New password must be at least ' . $MIN_LENGTH . ' characters.');
if ($new !== $confirm)             respond('error', 'Passwords do not match.');
if ($new === $current)             respond('error', 'New password must be different from your current one.');

try {
    $cols  = $pdo->query("SHOW COLUMNS FROM `{$USER_TABLE}`")->fetchAll(PDO::FETCH_COLUMN, 0);
    $pkCol = firstMatch($cols, ['UserID', 'id', 'user_id', 'ID']);
    $pwCol = firstMatch($cols, ['Password', 'PasswordHash', 'password', 'password_hash', 'Passwd']);

    if (!$pkCol || !$pwCol) {
        respond('error', 'Account table is not configured for password changes.');
    }

    $userId = (int)$_SESSION['UserID'];

    $stmt = $pdo->prepare("SELECT `{$pwCol}` AS pw FROM `{$USER_TABLE}` WHERE `{$pkCol}` = :id LIMIT 1");
    $stmt->execute([':id' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) respond('error', 'Account not found.');

    if (!verifyCurrent($current, (string)$row['pw'])) {
        respond('error', 'Your current password is incorrect.');
    }

    $newHash = password_hash($new, PASSWORD_BCRYPT);
    $pdo->prepare("UPDATE `{$USER_TABLE}` SET `{$pwCol}` = :pw WHERE `{$pkCol}` = :id")
        ->execute([':pw' => $newHash, ':id' => $userId]);

    // optional audit (non-fatal if helper not present)
    $auditPath = __DIR__ . '/../includes/audit.php';
    if (file_exists($auditPath)) {
        require_once $auditPath;
        if (function_exists('auditLog')) {
            auditLog(
                $userId,
                isset($_SESSION['SchoolPersonID']) ? (int)$_SESSION['SchoolPersonID'] : null,
                'Changed account password',
                'Account',
                null,
                'User changed their own password from Settings',
                null
            );
        }
    }

    respond('success', 'Password updated successfully!');

} catch (Throwable $e) {
    respond('error', 'Could not update password. Please try again.', ['debug' => $e->getMessage()]);
}

/* ── helpers ──────────────────────────────────────────────────────── */
function firstMatch(array $haystack, array $candidates): ?string {
    foreach ($candidates as $c) {
        foreach ($haystack as $h) {
            if (strcasecmp($h, $c) === 0) return $h;
        }
    }
    return null;
}

/**
 * bcrypt/argon via password_verify(); legacy md5/sha1/plaintext accepted once
 * so the bcrypt hash written on success transparently upgrades the row.
 * Delete the legacy branch if your users have no legacy hashes.
 */
function verifyCurrent(string $input, string $stored): bool {
    if ($stored === '') return false;

    $info = password_get_info($stored);
    if (($info['algo'] ?? 0) !== 0 || str_starts_with($stored, '$2y$') || str_starts_with($stored, '$argon')) {
        return password_verify($input, $stored);
    }
    if (strlen($stored) === 32 && ctype_xdigit($stored)) return hash_equals(strtolower($stored), md5($input));
    if (strlen($stored) === 40 && ctype_xdigit($stored)) return hash_equals(strtolower($stored), sha1($input));
    return hash_equals($stored, $input);
}