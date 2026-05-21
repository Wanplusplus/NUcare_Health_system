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

    // 1) Medicine-based alerts: Low Stock, Near Expiry, Expired
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

