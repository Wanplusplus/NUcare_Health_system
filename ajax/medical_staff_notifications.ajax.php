<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// For medical staff, allow notifications as long as the session exists.
// Some parts of the app use `UserID`, others use `patient_id`, so we just avoid hard-failing.
$userId = null;
if (isset($_SESSION['UserID']) && is_numeric($_SESSION['UserID'])) {
    $userId = (int)$_SESSION['UserID'];
} elseif (isset($_SESSION['patient_id']) && is_numeric($_SESSION['patient_id'])) {
    $userId = (int)$_SESSION['patient_id'];
}

if ($userId === null) {
    // Still return medicine notifications; do not block UI.
    $userId = 0;
}

require_once __DIR__ . '/../config/db.php';


function respond(array $payload, int $httpCode = 200): void {
    http_response_code($httpCode);
    echo json_encode($payload);
    exit;
}

function getUserId(): ?int {
    $keys = ['UserID', 'user_id', 'userId', 'patient_id', 'patientId'];
    foreach ($keys as $k) {
        if (isset($_SESSION[$k]) && is_numeric($_SESSION[$k])) {
            return (int)$_SESSION[$k];
        }
    }
    return null;
}

function getMedProfIdFromSessionOrUser(): ?int {
    // 1) Explicit MedProfID (best)
    if (isset($_SESSION['MedProfID']) && is_numeric($_SESSION['MedProfID'])) {
        return (int)$_SESSION['MedProfID'];
    }

    // 2) Derive MedProfID from UserID (common)
    $userId = getUserId();
    if ($userId !== null && $userId > 0) {
        global $conn;
        try {
            $stmt = $conn->prepare("SELECT MedProfID FROM medical_professionals WHERE UserID = ? LIMIT 1");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            if ($row && isset($row['MedProfID']) && is_numeric($row['MedProfID'])) {
                return (int)$row['MedProfID'];
            }
        } catch (Throwable $e) {
            return null;
        }
    }

    // 3) No mapping found
    return null;
}


function safeStr(mixed $v): string {
    return is_string($v) ? trim($v) : (string)$v;
}

function computePriorityForStatus(string $status): string {
    $s = strtolower($status);
    if ($s === 'low stock' || $s === 'out of stock') return 'Medium';
    if ($s === 'near expiry') return 'Medium';
    if ($s === 'expired') return 'High';
    return 'Low';
}

try {
    $userId = getUserId();

    $notifications = [];

    // 1) Booking-based alerts: New appointment + reschedule events
    // Schema (see sql/nucaredb.sql):
    //   bookings.BookingStatus: Pending, Approved, Completed, Cancelled
    //   bookings.RequestDate: timestamp
    //   bookings.RescheduleStatus: Proposed, Accepted, Rejected (used in schedule.ajax.php)
    //   bookings.MedProfID links to medical_professionals.MedProfID
    //
    // For medical staff, we try to filter by the logged-in user. If session does not map,
    // we still return medicine alerts below.
    $medProfId = null;
    try {
        $medProfId = getMedProfIdFromSessionOrUser();
    } catch (Throwable $e) {
        $medProfId = null;
    }



    if ($medProfId !== null) {
        // New appointment booked (Pending)
        try {
            $sqlBk = "
                SELECT
                    b.BookingID,
                    b.BookingType,
                    b.BookingStatus,
                    b.RequestDate,
                    b.AppointmentDate,
                    b.AppointmentStart,
                    sp.FirstName,
                    sp.LastName,
                    sp.SchoolID,
                    b.ServiceType,
                    b.ReasonForVisit
                FROM bookings b
                INNER JOIN school_people sp ON sp.SchoolPersonID = b.SchoolPersonID
                WHERE b.MedProfID = ?
                  AND b.BookingStatus = 'Pending'
                ORDER BY b.RequestDate DESC
                LIMIT 10
            ";
            $stmt = $conn->prepare($sqlBk);
            $stmt->bind_param('i', $medProfId);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            foreach ($rows as $r) {
                $patientName = trim(safeStr(($r['FirstName'] ?? '') . ' ' . ($r['LastName'] ?? '')));
                if ($patientName === '') $patientName = 'Student';

                $when = '';
                if (!empty($r['AppointmentDate']) && !empty($r['AppointmentStart'])) {
                    $when = ' — ' . safeStr($r['AppointmentDate']) . ' at ' . safeStr(substr((string)$r['AppointmentStart'], 0, 5));
                }

                $notifications[] = [
                    'title' => 'New appointment booked',
                    'message' => $patientName . $when,
                    'timestamp' => $r['RequestDate'] ?: date('c'),
                    'priority' => 'Medium',
                    'status' => 'Pending',
                ];
            }
        } catch (Throwable $e) {
            // non-fatal
        }

        // Reschedule events
        try {
            $sqlRs = "
                SELECT
                    b.BookingID,
                    b.RescheduleStatus,
                    b.RequestDate,
                    b.RescheduleProposedDate,
                    b.RescheduleProposedStart,
                    sp.FirstName,
                    sp.LastName
                FROM bookings b
                INNER JOIN school_people sp ON sp.SchoolPersonID = b.SchoolPersonID
                WHERE b.MedProfID = ?
                  AND b.RescheduleStatus IN ('Proposed','Accepted')
                ORDER BY b.RequestDate DESC
                LIMIT 10
            ";
            $stmt = $conn->prepare($sqlRs);
            $stmt->bind_param('i', $medProfId);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            foreach ($rows as $r) {
                $patientName = trim(safeStr(($r['FirstName'] ?? '') . ' ' . ($r['LastName'] ?? '')));
                if ($patientName === '') $patientName = 'Student';

                $status = safeStr($r['RescheduleStatus'] ?? '');
                $newWhen = '';
                if (!empty($r['RescheduleProposedDate'])) {
                    $t = '';
                    if (!empty($r['RescheduleProposedStart'])) {
                        $t = safeStr(substr((string)$r['RescheduleProposedStart'], 0, 5));
                    }
                    $newWhen = ' — ' . safeStr($r['RescheduleProposedDate']) . ($t ? (' at ' . $t) : '');
                }

                if ($status === 'Proposed') {
                    $notifications[] = [
                        'title' => 'Reschedule requested',
                        'message' => $patientName . $newWhen,
                        'timestamp' => $r['RequestDate'] ?: date('c'),
                        'priority' => 'Medium',
                        'status' => 'Proposed',
                    ];
                } elseif ($status === 'Accepted') {
                    $notifications[] = [
                        'title' => 'Reschedule confirmed',
                        'message' => $patientName . $newWhen,
                        'timestamp' => $r['RequestDate'] ?: date('c'),
                        'priority' => 'Low',
                        'status' => 'Accepted',
                    ];
                }
            }
        } catch (Throwable $e) {
            // non-fatal
        }
    }

    // 2) Medicine-based alerts: Low Stock, Near Expiry, Expired
    // Assumptions based on existing medicine implementation:
    // - medicines table: MedicineName
    // - medicine_inventory table: Quantity, ExpiryDate, ReorderLevel
    // - We can derive inventory status with the same logic used in ajax/medicine_ajax.php
    $sql = "
        SELECT
            m.MedicineID,
            m.MedicineName,
            i.Quantity,
            i.ExpiryDate,
            i.ReorderLevel,
            i.UpdatedAt
        FROM medicines m
        INNER JOIN medicine_inventory i ON i.MedicineID = m.MedicineID
        WHERE 1=1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $today = new DateTimeImmutable('today');
    foreach ($rows as $r) {
        $medicineName = safeStr($r['MedicineName'] ?? 'Medicine');
        $qty = (int)($r['Quantity'] ?? 0);
        $expiry = $r['ExpiryDate'] ?? null;
        $reorder = (int)($r['ReorderLevel'] ?? 10);
        $updatedAt = $r['UpdatedAt'] ?? null;

        $status = null;

        if ($qty <= 0) {
            // Treat out-of-stock as low stock alert
            $status = 'Low Stock';
        } else if ($expiry === null || $expiry === '' || $expiry === '0000-00-00') {
            $status = ($qty <= $reorder) ? 'Low Stock' : 'Available';
        } else {
            $expDate = DateTimeImmutable::createFromFormat('Y-m-d', (string)$expiry);
            if ($expDate instanceof DateTimeImmutable && $expDate < $today) {
                $status = 'Expired';
            } else {
                $days = 9999;
                if ($expDate instanceof DateTimeImmutable) {
                    $days = (int)$today->diff($expDate)->format('%r%a');
                }
                if ($expDate instanceof DateTimeImmutable && $days <= 30) {
                    $status = 'Near Expiry';
                } else if ($qty <= $reorder) {
                    $status = 'Low Stock';
                } else {
                    $status = 'Available';
                }
            }
        }

        if ($status === 'Available') {
            continue;
        }

        $priority = computePriorityForStatus($status);

        if ($status === 'Expired') {
            $title = 'Expired Medicines Alert';
            $message = $medicineName;
        } elseif ($status === 'Near Expiry') {
            $title = 'Near Expiry Medicines Alert';
            $message = $medicineName;
        } else {
            $title = 'Low Stock Alert';
            $message = $medicineName;
        }

        $notifications[] = [
            'title' => $title,
            'message' => $message,
            'timestamp' => $updatedAt ?: date('c'),
            'priority' => $priority,
            'status' => $status,
        ];
    }

    // Sort: Critical(High) first, then medium
    $prioScore = ['High' => 3, 'Medium' => 2, 'Low' => 1];
    usort($notifications, function($a, $b) use ($prioScore) {
        $pa = $prioScore[$a['priority'] ?? 'Low'] ?? 1;
        $pb = $prioScore[$b['priority'] ?? 'Low'] ?? 1;
        if ($pa !== $pb) return $pb - $pa;
        return strcmp((string)($b['timestamp'] ?? ''), (string)($a['timestamp'] ?? ''));
    });

    // If there are still no notifications (e.g., medicine query returned none),
    // return an empty list gracefully so the UI can show "No alerts right now".
    // Limit to a reasonable dropdown size
    $notifications = array_slice($notifications, 0, 15);

    respond([
        'success' => true,
        'last_updated' => date('c'),
        'notifications' => $notifications,
        'user_id' => $userId,
    ]);

} catch (Throwable $e) {
    respond(['success' => false, 'message' => 'Failed to fetch notifications', 'error' => $e->getMessage()], 500);
}

