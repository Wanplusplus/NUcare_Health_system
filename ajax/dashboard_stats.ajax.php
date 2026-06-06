<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['UserID'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized.']);
    exit;
}

$pdo   = require __DIR__ . '/../config/db_pdo.php';
$userId = (int) $_SESSION['UserID'];

try {
    /* ------------------------------------------------------------------ */
    /*  1. Admin Profile                                                   */
    /* ------------------------------------------------------------------ */
    $stmt = $pdo->prepare("
        SELECT
            sp.FirstName,
            sp.LastName,
            sp.MiddleName,
            sp.SchoolID,
            sp.Email,
            u.LastLogin,
            u.CreatedAt AS AccountCreated
        FROM users u
        INNER JOIN school_people sp ON sp.SchoolPersonID = u.SchoolPersonID
        WHERE u.UserID = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $profile = $stmt->fetch() ?: null;

    // Roles from session (already populated by RBAC)
    $roles = $_SESSION['Roles'] ?? [];
    $isSuperAdmin = in_array('Super Admin', $roles, true);

    /* ------------------------------------------------------------------ */
    /*  2. User Counts (Total registered users)                            */
    /* ------------------------------------------------------------------ */
    $totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

    /* ------------------------------------------------------------------ */
    /*  3. Session / Activity Tracking                                     */
    /*     Table: user_sessions (if exists) or fallback to users.LastLogin  */
    /* ------------------------------------------------------------------ */
    $hasSessions = false;
    try {
        $check = $pdo->query("SHOW TABLES LIKE 'user_sessions'");
        $hasSessions = ($check && $check->rowCount() > 0);
    } catch (Throwable $e) {
        $hasSessions = false;
    }

    $onlineNow   = 0;
    $idle        = 0;
    $offlineToday = 0;
    $roleBreakdown = [];

    if ($hasSessions) {
        // Online now — active session in last 5 minutes
        $onlineNow = (int) $pdo->query(
            "SELECT COUNT(DISTINCT UserID) FROM user_sessions WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)"
        )->fetchColumn();

        // Idle — session active in last 30 minutes but not in last 5
        $idle = (int) $pdo->query(
            "SELECT COUNT(DISTINCT UserID) FROM user_sessions WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 30 MINUTE) AND last_activity < DATE_SUB(NOW(), INTERVAL 5 MINUTE)"
        )->fetchColumn();

        // Offline today — users who logged in today but have no active session
        $offlineToday = (int) $pdo->query(
            "SELECT COUNT(*) FROM users WHERE DATE(LastLogin) = CURDATE() AND UserID NOT IN (SELECT DISTINCT UserID FROM user_sessions WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 30 MINUTE))"
        )->fetchColumn();
    } else {
        // Fallback: use LastLogin timestamp from users table
        $onlineNow = (int) $pdo->query(
            "SELECT COUNT(*) FROM users WHERE LastLogin >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)"
        )->fetchColumn();

        $idle = (int) $pdo->query(
            "SELECT COUNT(*) FROM users WHERE LastLogin >= DATE_SUB(NOW(), INTERVAL 30 MINUTE) AND LastLogin < DATE_SUB(NOW(), INTERVAL 5 MINUTE)"
        )->fetchColumn();

        $offlineToday = (int) $pdo->query(
            "SELECT COUNT(*) FROM users WHERE DATE(LastLogin) = CURDATE() AND LastLogin < DATE_SUB(NOW(), INTERVAL 30 MINUTE)"
        )->fetchColumn();
    }

    /* ------------------------------------------------------------------ */
    /*  4. Role Breakdown                                                  */
    /* ------------------------------------------------------------------ */
    $roleSql = "
        SELECT r.RoleName, COUNT(DISTINCT ur.UserID) AS cnt
        FROM roles r
        LEFT JOIN user_roles ur ON ur.RoleID = r.RoleID
        GROUP BY r.RoleName
        ORDER BY cnt DESC
    ";
    foreach ($pdo->query($roleSql)->fetchAll() as $row) {
        $roleBreakdown[$row['RoleName']] = (int) $row['cnt'];
    }

    /* ------------------------------------------------------------------ */
    /*  5. Today's Metrics                                                 */
    /* ------------------------------------------------------------------ */
    $todayAppointments = (int) $pdo->query(
        "SELECT COUNT(*) FROM bookings WHERE AppointmentDate = CURDATE() AND BookingStatus = 'Approved'"
    )->fetchColumn();

    $pendingConsultations = 0;
    try {
        $pendingConsultations = (int) $pdo->query(
            "SELECT COUNT(*) FROM clinic_transactions WHERE ConsultationStatus IN ('Waiting','Consulting')"
        )->fetchColumn();
    } catch (Throwable $e) { /* table may not exist */ }

    $pendingRecords = 0;
    try {
        $pendingRecords = (int) $pdo->query(
            "SELECT COUNT(*) FROM bookings WHERE BookingStatus = 'Pending'"
        )->fetchColumn();
    } catch (Throwable $e) { /* graceful */ }

    $todayVisits = (int) $pdo->query(
        "SELECT COUNT(*) FROM clinic_transactions WHERE DATE(VisitDate) = CURDATE()"
    )->fetchColumn();

    $todayConsultationsTotal = 0;
    $todayConsultationsCompleted = 0;
    $consultationCompletionRate = 0.0;
    try {
        $todayConsultationsTotal = (int) $pdo->query(
            "SELECT COUNT(*) FROM clinic_transactions WHERE DATE(VisitDate) = CURDATE() AND COALESCE(ConsultationStatus, '') IN ('Waiting','Consulting','Completed')"
        )->fetchColumn();
        $todayConsultationsCompleted = (int) $pdo->query(
            "SELECT COUNT(*) FROM clinic_transactions WHERE DATE(VisitDate) = CURDATE() AND ConsultationStatus = 'Completed'"
        )->fetchColumn();
        if ($todayConsultationsTotal > 0) {
            $consultationCompletionRate = round(($todayConsultationsCompleted / $todayConsultationsTotal) * 100, 1);
        }
    } catch (Throwable $e) { /* graceful */ }

    $upcomingEvents = [];
    try {
        $eventSql = "
            SELECT
                event_type,
                event_date,
                event_time,
                event_status,
                title,
                subtitle
            FROM (
                SELECT
                    'appointment' AS event_type,
                    b.AppointmentDate AS event_date,
                    COALESCE(b.AppointmentStart, '09:00:00') AS event_time,
                    COALESCE(b.BookingStatus, 'Pending') AS event_status,
                    COALESCE(
                        NULLIF(TRIM(CONCAT(sp.FirstName, ' ', COALESCE(sp.LastName, ''))), ''),
                        CONCAT('Appointment #', b.BookingID)
                    ) AS title,
                    COALESCE(NULLIF(TRIM(CONCAT(COALESCE(b.ServiceType, ''), ' ', COALESCE(b.ReasonForVisit, ''))), ''), 'Clinic appointment') AS subtitle
                FROM bookings b
                LEFT JOIN school_people sp ON sp.SchoolPersonID = b.SchoolPersonID
                WHERE b.AppointmentDate BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 14 DAY)
                  AND COALESCE(b.BookingStatus, '') NOT IN ('Cancelled')
                UNION ALL
                SELECT
                    'consultation' AS event_type,
                    ct.VisitDate AS event_date,
                    COALESCE(TIME(ct.CreatedAt), '10:00:00') AS event_time,
                    COALESCE(ct.ConsultationStatus, 'Waiting') AS event_status,
                    COALESCE(
                        NULLIF(TRIM(CONCAT(sp.FirstName, ' ', COALESCE(sp.LastName, ''))), ''),
                        CONCAT('Consultation #', ct.ClinicTransactionID)
                    ) AS title,
                    COALESCE(NULLIF(TRIM(CONCAT(COALESCE(ct.ServiceType, ''), ' ', COALESCE(ct.Complaint, ''))), ''), 'Consultation record') AS subtitle
                FROM clinic_transactions ct
                LEFT JOIN school_people sp ON sp.SchoolPersonID = ct.SchoolPersonID
                WHERE ct.VisitDate BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 14 DAY)
                  AND COALESCE(ct.ConsultationStatus, '') NOT IN ('Cancelled')
            ) events
            ORDER BY event_date ASC, event_time ASC, event_type ASC
            LIMIT 12
        ";
        $stmt = $pdo->query($eventSql);
        $upcomingEvents = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        $upcomingEvents = [];
    }

    /* ------------------------------------------------------------------ */
    /*  6. System Health                                                   */
    /* ------------------------------------------------------------------ */
    $dbStatus = 'Connected';
    try {
        $pdo->query("SELECT 1");
    } catch (Throwable $e) {
        $dbStatus = 'Error';
    }

    $errorCountToday = 0;
    try {
        $errorCountToday = (int) $pdo->query(
            "SELECT COUNT(*) FROM audit_logs WHERE DATE(ActionTimestamp) = CURDATE() AND Action LIKE '%error%'"
        )->fetchColumn();
    } catch (Throwable $e) { /* graceful */ }

    // Overall status
    $overallStatus = 'Healthy';
    if ($dbStatus !== 'Connected') {
        $overallStatus = 'Degraded';
    } elseif ($errorCountToday > 50) {
        $overallStatus = 'Warning';
    }

    /* ------------------------------------------------------------------ */
    /*  Build Response                                                     */
    /* ------------------------------------------------------------------ */
    echo json_encode([
        'ok' => true,
        'profile' => $profile,
        'roles'   => $roles,
        'isSuperAdmin' => $isSuperAdmin,
        'stats' => [
            'totalUsers'         => $totalUsers,
            'onlineNow'          => $onlineNow,
            'idle'               => $idle,
            'offlineToday'       => $offlineToday,
            'todayAppointments'  => $todayAppointments,
            'pendingConsultations' => $pendingConsultations,
            'pendingRecords'     => $pendingRecords,
            'todayVisits'        => $todayVisits,
            'todayConsultationsTotal' => $todayConsultationsTotal,
            'todayConsultationsCompleted' => $todayConsultationsCompleted,
            'consultationCompletionRate' => $consultationCompletionRate,
        ],
        'roleBreakdown' => $roleBreakdown,
        'upcomingEvents' => $upcomingEvents,
        'health' => [
            'dbStatus'        => $dbStatus,
            'errorCountToday' => $errorCountToday,
            'overallStatus'   => $overallStatus,
        ],
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
