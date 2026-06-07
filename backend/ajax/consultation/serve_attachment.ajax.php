<?php
declare(strict_types=1);
/**
 * serve_attachment.ajax.php
 * ---
 * Streams a consultation attachment file to the browser.
 * Prevents path traversal and only serves files from the
 * consultations upload folder.
 *
 * GET ?id=ATTACHMENT_ID -> streams the file inline (view in browser)
 * GET ?id=ATTACHMENT_ID&dl=1 -> forces download
 */

ini_set('display_errors', '0');

if (session_status() === PHP_SESSION_NONE) session_start();

// -- Auth guard - adjust to match your session variable ---
// Uncomment and adapt if you have session-based auth:
// if (empty($_SESSION['UserID'])) { http_response_code(403); exit('Forbidden'); }

$pdo = require __DIR__ . '/../../../database/config/db_pdo.php';

$attachmentID = (int)($_GET['id'] ?? 0);
if ($attachmentID <= 0) {
 http_response_code(400);
 exit('Invalid attachment ID.');
}

// -- Fetch record from DB ---
try {
 $stmt = $pdo->prepare("
 SELECT AttachmentID, FileName, StoredName, FilePath, FileType, FileSizeBytes
 FROM consultation_attachments
 WHERE AttachmentID = :id
 LIMIT 1
 ");
 $stmt->execute([':id' => $attachmentID]);
 $row = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
 http_response_code(500);
 exit('Database error.');
}

if (!$row) {
 http_response_code(404);
 exit('Attachment not found.');
}

// -- Resolve file path safely ---
// FilePath stored as relative-from-webroot: "uploads/consultations/consult_xxx.jpg"
$webRoot = rtrim(__DIR__ . '/../../', '/');
$filePath = $webRoot . '/' . ltrim($row['FilePath'], '/');
$realPath = realpath($filePath);

// Security: make sure the resolved path is still inside the uploads/consultations dir
$allowedDir = realpath($webRoot . '/uploads/consultations');
if (!$realPath || !$allowedDir || !str_starts_with($realPath, $allowedDir . DIRECTORY_SEPARATOR)) {
 http_response_code(403);
 exit('Access denied.');
}

if (!is_file($realPath)) {
 http_response_code(404);
 exit('File not found on disk.');
}

// -- Stream the file ---
$mimeType = $row['FileType'] ?: mime_content_type($realPath) ?: 'application/octet-stream';
$fileName = $row['FileName'] ?: basename($realPath);
$forceDownload = !empty($_GET['dl']);

// Allow only safe inline MIME types; everything else forces download
$inlineSafe = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
$disposition = ($forceDownload || !in_array($mimeType, $inlineSafe, true))
 ? 'attachment'
 : 'inline';

header('Content-Type: ' . $mimeType);
header('Content-Disposition: ' . $disposition . '; filename="' . addslashes($fileName) . '"');
header('Content-Length: ' . filesize($realPath));
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');

// Flush any output buffers
while (ob_get_level()) ob_end_clean();

readfile($realPath);
exit;



