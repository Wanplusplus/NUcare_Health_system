<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db_pdo.php';

function auditLog(
    ?int $actorUserId,
    ?int $actorSchoolPersonId,
    string $actionType,
    ?string $entityType = null,
    ?string $entityId = null,
    ?string $details = null,
    ?string $ipAddress = null
): void {
    $traceFile = __DIR__ . '/../_audit_trace.txt';

    $trace = function (string $line) use ($traceFile): void {
        @file_put_contents($traceFile, '[' . date('c') . '] ' . $line . PHP_EOL, FILE_APPEND);
    };

    $pdo = require __DIR__ . '/../config/db_pdo.php';

    try {
        $trace('auditLog CALLED actionType=' . $actionType . ' entityType=' . (string)$entityType . ' entityId=' . (string)$entityId);
        @error_log('[auditLog] CALLED action=' . $actionType . ' entity=' . (string)$entityType);

        // Validate DB connection.
        $pdo->query('SELECT 1')->fetchColumn();

        // Schema sanity: audit_logs.UserID is NOT NULL in nucaredb.sql.
        if ($actorUserId === null) {
            $trace('auditLog SKIP actorUserId is null (would violate audit_logs.UserID NOT NULL)');
            @error_log('[auditLog] SKIP: actorUserId is null');
            return;
        }

        $userId = $actorUserId;
        $action = $actionType;
        $moduleName = $entityType;
        $tableAffected = $details;
        // RecordID column is INT. Some legacy callers pass non-numeric data in $entityId.
        // This caused SQLSTATE 1265 data truncated.
        if ($entityId === null || $entityId === '' || !is_numeric((string)$entityId)) {
            $recordId = null;
            $trace('auditLog RECORDID coerced to NULL (non-numeric entityId)');
        } else {
            $recordId = (int)$entityId;
        }

        $trace('auditLog PREP INSERT UserID=' . (string)$userId . ' Action=' . (string)$action . ' ModuleName=' . (string)$moduleName);


        $sql = "
            INSERT INTO audit_logs (UserID, Action, ModuleName, TableAffected, RecordID)
            VALUES (?, ?, ?, ?, ?)
        ";

        $trace('auditLog EXEC INSERT');

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $action, $moduleName, $tableAffected, $recordId]);

        $trace('auditLog INSERT OK rowCount=' . (string)$stmt->rowCount());
        @error_log('[auditLog] INSERT OK rowCount=' . (string)$stmt->rowCount());

    } catch (Throwable $e) {
        $trace('auditLog EXCEPTION: ' . $e->getMessage());
        @error_log('[auditLog EXCEPTION] ' . $e->getMessage());

        try {
            $trace('auditLog PDO errorInfo=' . json_encode($pdo->errorInfo()));
        } catch (Throwable $e2) {
            // ignore
        }
    }
}


