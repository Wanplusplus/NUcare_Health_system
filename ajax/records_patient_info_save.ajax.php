<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Auth: support all session key variants ────────────────────────────────────
$sessionUserID = $_SESSION['UserID'] ?? $_SESSION['user_id'] ?? $_SESSION['userid'] ?? null;
if (!$sessionUserID) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized. Please log in again.']);
    exit;
}

$pdo = require __DIR__ . '/../config/db_pdo.php';
require_once __DIR__ . '/../includes/patients_info_helpers.php';

// ── Validate incoming school_person_id ────────────────────────────────────────
// Identity rule: SchoolPersonID is the anchor. SchoolID must never be used.
// Accept multiple POST key variants for the same identity anchor.
$schoolPersonIDRaw = $_POST['school_person_id']
    ?? $_POST['schoolPersonID']
    ?? $_POST['SchoolPersonID']
    ?? $_POST['schoolpersonid']
    ?? $_POST['school_personid']
    ?? null;

$schoolPersonID = is_numeric($schoolPersonIDRaw) ? (int)$schoolPersonIDRaw : 0;

// Also support a very common alias some legacy pages may send.
if ($schoolPersonID <= 0 && isset($_POST['id']) && is_numeric($_POST['id'])) {
    $schoolPersonID = (int)$_POST['id'];
}


if ($schoolPersonID <= 0) {
    // Do NOT fail with a misleading message; include what the client actually sent.
    $maybeKeys = ['school_person_id', 'schoolPersonID', 'school_personid', 'id'];
    $debugSent = [];
    foreach ($maybeKeys as $k) {
        if (isset($_POST[$k])) $debugSent[$k] = $_POST[$k];
    }

    // Sometimes FormData may send as string under a different casing.
    foreach ($_POST as $k => $v) {
        $debugSent['POST_' . (string)$k] = $v;
    }


    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'message' => 'Invalid patient (missing school_person_id).',
        'debug' => $debugSent,
    ]);
    exit;
}



try {
    // ── 1. Look up existing UserID linked to this school person ───────────────
    $stmt = $pdo->prepare('
        SELECT UserID FROM users
        WHERE SchoolPersonID = :spid
        LIMIT 1
    ');
    $stmt->execute([':spid' => $schoolPersonID]);
    $targetUserID = (int)($stmt->fetchColumn() ?: 0);

    // ── 2. No linked user? Auto-create one from school_persons data ───────────
    if ($targetUserID <= 0) {
        // Fetch the school person's basic info to seed the users row.
        // Adjust column names below if your table uses different naming.
        $spStmt = $pdo->prepare('
            SELECT
                SchoolID,
                FirstName,
                LastName,
                Email,
                PersonType
            FROM school_persons
            WHERE SchoolPersonID = :spid
            LIMIT 1
        ');
        $spStmt->execute([':spid' => $schoolPersonID]);
        $spRow = $spStmt->fetch(PDO::FETCH_ASSOC);

        if (!$spRow) {
            echo json_encode(['ok' => false, 'message' => 'Patient record not found in school_persons.']);
            exit;
        }

        // Insert a linked users row.
        // Extend or trim columns to match your exact users table schema.
        // IMPORTANT (core identity rule): Never use SchoolID as an identity anchor.
        // SchoolID may be stored as nullable metadata; it must not participate in lookups/foreign-key anchors.
        // Here we only copy it as nullable field if your `users` table supports it.
        $insStmt = $pdo->prepare('
            INSERT INTO users
                (SchoolPersonID, SchoolID, FirstName, LastName, Email, Role, CreatedAt)
            VALUES
                (:spid, NULLIF(:school_id, \"\"), :fname, :lname, :email, :role, NOW())
        ');
        $insStmt->execute([
            ':spid'      => $schoolPersonID,
            ':school_id' => (string)($spRow['SchoolID'] ?? ''),
            ':fname'     => (string)($spRow['FirstName']  ?? ''),
            ':lname'     => (string)($spRow['LastName']   ?? ''),
            ':email'     => (string)($spRow['Email']      ?? ''),
            ':role'      => (string)($spRow['PersonType'] ?? 'Student'),
        ]);

        $targetUserID = (int)$pdo->lastInsertId();

        if ($targetUserID <= 0) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'message' => 'Failed to create linked user account.']);
            exit;
        }
    }

    // ── 3. Decode family history payload ──────────────────────────────────────
    $familyRaw   = (string)($_POST['family_history'] ?? '[]');
    $familyItems = json_decode($familyRaw, true);
    if (!is_array($familyItems)) {
        $familyItems = [];
    }

    // ── 4. Save everything in a transaction ───────────────────────────────────
    $pdo->beginTransaction();

    if (($_POST['mode'] ?? '') !== 'family_only') {
        $payload = patientsInfoPayload($_POST);
        $errors  = patientsInfoValidate($payload);

        if ($errors) {
            $pdo->rollBack();
            echo json_encode([
                'ok'      => false,
                'message' => implode(' ', $errors),
                'errors'  => $errors,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        patientsInfoSave($pdo, $targetUserID, $payload);
    }

    familyHistoryReplace($pdo, $targetUserID, $familyItems);
    $pdo->commit();

    // ── 5. Return fresh data to the frontend (records.js uses these) ──────────
    echo json_encode([
        'ok'            => true,
        'message'       => 'Patient information saved.',
        'patientsInfo'  => patientsInfoLoad($pdo, $targetUserID),
        'familyHistory' => familyHistoryLoad($pdo, $targetUserID),
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}