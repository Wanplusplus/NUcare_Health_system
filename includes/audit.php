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
    try {
        $pdo = require __DIR__ . '/../config/db_pdo.php';

        $sql = "
            INSERT INTO audit_logs
              (ActorUserID, ActorSchoolPersonID, ActionType, EntityType, EntityID, Details, IpAddress)
            VALUES
              (?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $actorUserId,
            $actorSchoolPersonId,
            $actionType,
            $entityType,
            $entityId,
            $details,
            $ipAddress ?? ($_SERVER['REMOTE_ADDR'] ?? null),
        ]);
    } catch (Throwable $e) {
        // Never block auth flows due to audit failure
    }
}
