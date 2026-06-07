<?php
declare(strict_types=1);

require_once __DIR__ . '/../../database/config/db_pdo.php';

function auditLog(
 ?int $actorUserId,
 ?int $actorSchoolPersonId,
 string $actionType,
 ?string $entityType = null,
 ?string $entityId = null,
 ?string $details = null,
 ?string $ipAddress = null
): void {
 $pdo = require __DIR__ . '/../../database/config/db_pdo.php';
 $trace = static function (string $message): void {
 @error_log('[auditLog] ' . $message);
 };

 try {
 @error_log('[auditLog] CALLED action=' . $actionType . ' entity=' . (string)$entityType);

 // Validate DB connection.
 $pdo->query('SELECT 1')->fetchColumn();

 // Schema sanity: audit_logs.UserID is NOT NULL in nucaredb.sql.
 if ($actorUserId === null) {
 @error_log('[auditLog] SKIP: actorUserId is null');
 return;
 }

 $userId = $actorUserId;

 // Normalize legacy/vague action codes into human-readable audit messages.
 $rawAction = strtolower(trim($actionType));

 // If we can reliably convert a stored "raw action" code into a readable phrase, do it here.
 // If the action is purely technical/debug, we suppress it.
 // NOTE: 'failed_login' was previously suppressed here to keep the admin feed
 // clean. It has now been intentionally allowed through so failed authentication
 // attempts appear in the Reports module. Mapped to a human-readable
 // "Failed login attempt" string below in $actionMap.
 $suppressed = [
 // authentication debug/noise (do not log in admin feed)
 'login_debug_rbac_loaded',
 'login_debug_school_match',
 'login_hash_debug',
 // signup failures (still noise; only failed_logins reach the feed now)
 'failed_signup',
 // schedule technical/noise
 'save_slot',
 'respond_booking',
 // user/rbac technical codes (we will map the specific ones below; suppress generic ones)
 'create_user',
 'update_user',
 ];

 if (in_array($rawAction, $suppressed, true)) {
 return;
 }

 // Human readable action mapping.
 // NOTE: actionType passed by callers is the *raw code*. ModuleName is derived from $entityType.
 $actionMap = [
 // auth/login/logout (no dedicated Authentication module in UI)
 'login' => 'Logged into the system',
 'logout' => 'Logged out of the system',
 // Failed login is intentionally recorded so the Reports module can
 // surface failed authentication attempts. The $details parameter from
 // callers carries the attempted School ID, e.g. "Failed login attempt for School ID: 2024-12345".
 'failed_login' => 'Failed login attempt',
 'password_reset' => 'Requested password reset',

 // admin/user management
 'signup' => 'Created user account',
 'account_activation' => 'Created/activated user account',
 'account_deactivation' => 'Deactivated user account',

 'role_assignment' => 'Assigned role',
 'role_removal' => 'Removed role',

 // RBAC management
 'rbac_role_assignment' => 'Updated RBAC permissions',
 'role_assignment_permissions' => 'Updated RBAC permissions',

 // enrollment (not in required list; keep readable but generic)
 'enrollment_change' => 'Updated enrollment status',

 // schedule (callers may pass these raw codes in Action)
 'booking_created' => 'Booked appointment',
 'booking_approved' => 'Approved appointment',
 'booking_cancelled' => 'Cancelled appointment',
 'booking_completed' => 'Completed appointment',

 'availability_set' => 'Set availability',
 'availability_updated' => 'Updated availability',
 'availability_deleted' => 'Deleted availability',

 // consultation/records/medicine generic (fallback)
 'consultation_started' => 'Started consultation',
 'consultation_updated' => 'Updated consultation',
 'consultation_completed' => 'Completed consultation',

 'records_opened' => 'Opened records',
 'records_viewed' => 'Viewed patient record',
 'records_updated' => 'Updated patient record',

 'medicine_inventory_added' => 'Added medicine inventory',
 'medicine_inventory_updated' => 'Updated medicine inventory',
 'medicine_dispensed' => 'Dispensed medicine',
 'medicine_received' => 'Received medicine',
 ];

 $action = $actionMap[$rawAction] ?? $actionType;

 // Only allow module names from the allowed audit module list.
 // $entityType is used as ModuleName by existing callers.
 $allowedModules = [
 'Consultation',
 'Records',
 'Reports',
 'Medicine',
 'Schedule',
 'Admin Panel',
 'RBAC Management',
 'User Management',
 'Audit Logs',
 // Authentication module is required for failed-login reporting.
 'Authentication',
 ];

 // Map raw module names to the canonical allowed list.
 if (is_string($entityType)) {
 $etLower = strtolower(trim($entityType));
 if ($etLower === 'auth') {
 // Successful logins / logouts stay grouped with user management.
 // Failed logins (called with actionType 'failed_login') are routed
 // to the dedicated "Authentication" module for cleaner reporting.
 $moduleName = ($rawAction === 'failed_login') ? 'Authentication' : 'User Management';
 } elseif ($etLower === 'authentication') {
 $moduleName = 'Authentication';
 } else {
 $moduleName = (string)$entityType;
 }
 } else {
 $moduleName = (string)$entityType;
 }

 if (!in_array($moduleName, $allowedModules, true) || $moduleName === '') {
 // Keep it safe for the admin dropdown/filter: use a generic module.
 $moduleName = 'Admin Panel';
 }

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






