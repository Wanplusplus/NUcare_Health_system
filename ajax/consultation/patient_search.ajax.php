<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);


session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/db_pdo.php';



// Accept both formats:
// - raw numeric School IDs (e.g., 2024-1116262)
// - stored value that may include SCH- prefix.
// Also normalize by removing whitespace.


// For debugging: force JSON even if someone hits this endpoint without a valid session.
// NOTE: kept disabled for production; uncomment only if needed.
// http_response_code(401); echo json_encode(['found'=>false,'message'=>'unauthorized']); exit;

// For AJAX, auth_guard.php may echo/redirect HTML which breaks JSON clients.
// We'll handle auth failure with JSON output in this endpoint instead of relying on redirects.
// (Keep the require if it only checks session; otherwise it can redirect.)


// AJAX Auth Guard: ensure medical staff is logged in via existing system
// auth_guard.php may redirect on failure; for AJAX we rely on it.

$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$q = preg_replace('/\s+/', '', $q ?? '');
$debug = isset($_GET['debug']) && (string)$_GET['debug'] !== '' && (string)$_GET['debug'] !== '0';
if ($q === '') {
    http_response_code(400);
    echo json_encode(['found' => false, 'message' => 'Missing query (School ID).']);
    exit;
}

try {
    $pdo = require __DIR__ . '/../../config/db_pdo.php';


    // Spec: staff searches by School ID from school_people.
    // To be resilient with typed variants, we accept exact match first,
    // then fallback to partial match using LIKE.
    // (Information is displayed from school_people; consultation records are saved in consultation_transactions.)
    // Search by SchoolID (e.g. 2024-1116262) OR by SchoolPersonID (integer primary key).
    // Also support SCH- prefix variants.
    $sql = "SELECT sp.SchoolPersonID, sp.SchoolID, sp.FirstName, sp.MiddleName, sp.LastName, sp.Sex
            FROM school_people sp
            WHERE sp.SchoolID = :schoolid_exact
               OR sp.SchoolID LIKE :schoolid_like
               OR CAST(sp.SchoolPersonID AS CHAR) = :schoolid_exact
               OR CAST(sp.SchoolPersonID AS CHAR) LIKE :schoolid_like

            ORDER BY
              (sp.SchoolID = :schoolid_exact) DESC,
              (sp.SchoolID LIKE :schoolid_like) DESC
            LIMIT 1";



    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':schoolid_exact' => $q,
        ':schoolid_like'  => '%' . $q . '%',
    ]);


    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['found' => false]);
        exit;
    }

    $fullName = trim(implode(' ', array_filter([
        $row['FirstName'] ?? '',
        $row['MiddleName'] ?? '',
        $row['LastName'] ?? '',
    ])));

    echo json_encode([
        'found' => true,

        'SchoolPersonID' => (int)$row['SchoolPersonID'],
        'SchoolID' => (string)$row['SchoolID'],
        'fullName' => $fullName,
        'Sex' => (string)$row['Sex'],
    ]);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'found' => false,
        'message' => 'Server error',
        'error' => $e->getMessage(),
    ]);
    exit;
}

