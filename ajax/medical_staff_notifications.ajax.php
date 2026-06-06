<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

$userId = null;
if (isset($_SESSION['UserID']) && is_numeric($_SESSION['UserID'])) {
    $userId = (int)$_SESSION['UserID'];
} elseif (isset($_SESSION['patient_id']) && is_numeric($_SESSION['patient_id'])) {
    $userId = (int)$_SESSION['patient_id'];
}

if ($userId === null) {
    $userId = 0;
}

require_once __DIR__ . '/../config/db.php';

function respond(array $payload, int $httpCode = 200): void {
    http_response_code($httpCode);
    echo json_encode($payload);
    exit;
}

function getUserId(): ?int {
    foreach (['UserID', 'user_id', 'userId', 'patient_id', 'patientId'] as $k) {
        if (isset($_SESSION[$k]) && is_numeric($_SESSION[$k])) {
            return (int)$_SESSION[$k];
        }
    }
    return null;
}

function getMedProfIdFromSessionOrUser(): ?int {
    if (isset($_SESSION['MedProfID']) && is_numeric($_SESSION['MedProfID'])) {
        return (int)$_SESSION['MedProfID'];
    }

    $userId = getUserId();
    if ($userId !== null && $userId > 0) {
        global $conn;
        try {
            $stmt = $conn->prepare("SELECT MedProfID FROM medical_professionals WHERE UserID = ? LIMIT 1");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if ($row && isset($row['MedProfID']) && is_numeric($row['MedProfID'])) {
                return (int)$row['MedProfID'];
            }
        } catch (Throwable $e) {
            return null;
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

function scheduleLink(int $bookingId, string $focus = 'booking', int $professionalId = 0, string $bookingDate = '', string $bookingStart = ''): string {
    $params = [
        'booking_id' => $bookingId,
        'focus' => $focus,
    ];
    if ($professionalId > 0) {
        $params['professional_id'] = $professionalId;
    }
    if ($bookingDate !== '') {
        $params['booking_date'] = $bookingDate;
    }
    if ($bookingStart !== '') {
        $params['booking_start'] = $bookingStart;
    }
    return '../../modules/schedule/schedule.php?' . http_build_query($params);
}

function medicineLink(int $medicineId = 0, int $inventoryId = 0, string $status = ''): string {
    $params = [];
    if ($medicineId > 0) $params['medicine_id'] = $medicineId;
    if ($inventoryId > 0) $params['inventory_id'] = $inventoryId;
    if ($status !== '') $params['alert'] = $status;
    return '../../modules/medicine/medicine.php' . ($params ? ('?' . http_build_query($params)) : '');
}

try {
    $notifications = [];
    // Booking notifications
    try {
        $sqlBk = "
            SELECT
                b.BookingID,
                b.BookingStatus,
                b.MedProfID,
                b.RequestDate,
                b.AppointmentDate,
                b.AppointmentStart,
                sp.FirstName,
                sp.LastName,
                b.ServiceType
            FROM bookings b
            INNER JOIN school_people sp ON sp.SchoolPersonID = b.SchoolPersonID
            WHERE b.BookingStatus = 'Pending'
            ORDER BY b.RequestDate DESC
            LIMIT 10
        ";

        $stmt = $conn->prepare($sqlBk);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($rows as $r) {
            $patientName = trim(safeStr(($r['FirstName'] ?? '') . ' ' . ($r['LastName'] ?? '')));
            if ($patientName === '') $patientName = 'Student';

            $when = '';
            if (!empty($r['AppointmentDate']) && !empty($r['AppointmentStart'])) {
                $when = ' - ' . safeStr($r['AppointmentDate']) . ' at ' . safeStr(substr((string)$r['AppointmentStart'], 0, 5));
            }

            $notifications[] = [
                'title' => 'New appointment booked',
                'message' => $patientName . $when,
                'timestamp' => $r['RequestDate'] ?: date('c'),
                'priority' => 'Medium',
                'status' => 'Pending',
                'type' => 'booking_pending',
                'booking_id' => (int)$r['BookingID'],
                'target_url' => scheduleLink(
                    (int)$r['BookingID'],
                    'pending',
                    (int)($r['MedProfID'] ?? 0),
                    (string)($r['AppointmentDate'] ?? ''),
                    (string)($r['AppointmentStart'] ?? '')
                ),
                'target_label' => 'Open schedule',
            ];
        }
    } catch (Throwable $e) {
        // non-fatal
    }

    // Reschedule notifications
    try {
        $sqlRs = "
            SELECT
                b.BookingID,
                b.MedProfID,
                b.RescheduleStatus,
                b.RequestDate,
                b.RescheduleProposedDate,
                b.RescheduleProposedStart,
                sp.FirstName,
                sp.LastName
            FROM bookings b
            INNER JOIN school_people sp ON sp.SchoolPersonID = b.SchoolPersonID
            WHERE b.RescheduleStatus IN ('Proposed','Accepted','Rejected')
            ORDER BY b.RequestDate DESC
            LIMIT 10
        ";

        $stmt = $conn->prepare($sqlRs);
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
                $newWhen = ' - ' . safeStr($r['RescheduleProposedDate']) . ($t ? (' at ' . $t) : '');
            }

            if ($status === 'Proposed') {
                $notifications[] = [
                    'title' => 'Reschedule requested',
                    'message' => $patientName . $newWhen,
                    'timestamp' => $r['RequestDate'] ?: date('c'),
                    'priority' => 'Medium',
                    'status' => 'Proposed',
                    'type' => 'reschedule_proposed',
                    'booking_id' => (int)$r['BookingID'],
                    'target_url' => scheduleLink(
                        (int)$r['BookingID'],
                        'pending',
                        (int)($r['MedProfID'] ?? 0),
                        (string)($r['AppointmentDate'] ?? ''),
                        (string)($r['AppointmentStart'] ?? '')
                    ),
                    'target_label' => 'Open schedule',
                ];
            } elseif ($status === 'Accepted') {
                $notifications[] = [
                    'title' => 'Student accepted reschedule',
                    'message' => $patientName . ' accepted the booking' . $newWhen,
                    'timestamp' => $r['RequestDate'] ?: date('c'),
                    'priority' => 'Low',
                    'status' => 'Accepted',
                    'type' => 'reschedule_accepted',
                    'booking_id' => (int)$r['BookingID'],
                    'target_url' => scheduleLink(
                        (int)$r['BookingID'],
                        'booking',
                        (int)($r['MedProfID'] ?? 0),
                        (string)($r['RescheduleProposedDate'] ?? ''),
                        (string)($r['RescheduleProposedStart'] ?? '')
                    ),
                    'target_label' => 'Open schedule',
                ];
            } elseif ($status === 'Rejected') {
                $notifications[] = [
                    'title' => 'Student declined reschedule',
                    'message' => $patientName . ' declined the proposed reschedule' . $newWhen,
                    'timestamp' => $r['RequestDate'] ?: date('c'),
                    'priority' => 'Medium',
                    'status' => 'Rejected',
                    'type' => 'reschedule_rejected',
                    'booking_id' => (int)$r['BookingID'],
                    'target_url' => scheduleLink(
                        (int)$r['BookingID'],
                        'pending',
                        (int)($r['MedProfID'] ?? 0),
                        (string)($r['AppointmentDate'] ?? ''),
                        (string)($r['AppointmentStart'] ?? '')
                    ),
                    'target_label' => 'Open schedule',
                ];
            }
        }
    } catch (Throwable $e) {
        // non-fatal
    }

    // Medicine alerts including zero-stock / no inventory medicines
    $sql = "
        SELECT
            m.MedicineID,
            m.MedicineName,
            COALESCE(SUM(i.Quantity), 0) AS TotalQuantity,
            COALESCE(MIN(i.Quantity), 0) AS LowestBatchQuantity,
            MIN(i.ExpiryDate) AS EarliestExpiry,
            GREATEST(COALESCE(MAX(i.ReorderLevel), 10), 50) AS ReorderLevel,
            MAX(i.UpdatedAt) AS UpdatedAt,
            COUNT(i.InventoryID) AS InventoryCount,
            SUM(CASE WHEN i.Quantity <= GREATEST(COALESCE(i.ReorderLevel, 10), 50) THEN 1 ELSE 0 END) AS LowBatchCount,
            SUM(CASE WHEN LOWER(COALESCE(i.Status, '')) IN ('low stock', 'out of stock') THEN 1 ELSE 0 END) AS StatusAlertCount,
            SUM(CASE WHEN LOWER(COALESCE(i.Status, '')) = 'out of stock' THEN 1 ELSE 0 END) AS OutOfStockStatusCount
        FROM medicines m
        LEFT JOIN medicine_inventory i ON i.MedicineID = m.MedicineID
        GROUP BY m.MedicineID, m.MedicineName
        ORDER BY m.MedicineName ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $today = new DateTimeImmutable('today');
    foreach ($rows as $r) {
        $medicineName = safeStr($r['MedicineName'] ?? 'Medicine');
        $qty = (int)($r['TotalQuantity'] ?? 0);
        $lowestBatchQty = (int)($r['LowestBatchQuantity'] ?? 0);
        $expiry = $r['EarliestExpiry'] ?? null;
        $reorder = (int)($r['ReorderLevel'] ?? 10);
        $updatedAt = $r['UpdatedAt'] ?? null;
        $inventoryCount = (int)($r['InventoryCount'] ?? 0);
        $lowBatchCount = (int)($r['LowBatchCount'] ?? 0);
        $statusAlertCount = (int)($r['StatusAlertCount'] ?? 0);
        $outOfStockStatusCount = (int)($r['OutOfStockStatusCount'] ?? 0);

        $status = null;
        if ($qty <= 0 || $inventoryCount === 0 || $outOfStockStatusCount > 0) {
            $status = 'Out of Stock';
        } elseif ($qty <= $reorder || $lowBatchCount > 0 || $statusAlertCount > 0) {
            $status = 'Low Stock';
        } elseif ($expiry === null || $expiry === '' || $expiry === '0000-00-00') {
            $status = 'Available';
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
                } else {
                    $status = 'Available';
                }
            }
        }

        if ($status === 'Available') {
            continue;
        }

        if ($status === 'Expired') {
            $title = 'Expired Medicines Alert';
        } elseif ($status === 'Near Expiry') {
            $title = 'Near Expiry Medicines Alert';
        } else {
            $title = 'Low Stock Alert';
        }

        $notifications[] = [
            'title' => $title,
            'message' => $medicineName . ' (' . $qty . ' total remaining' . ($lowBatchCount > 0 ? ', lowest batch ' . $lowestBatchQty : '') . ')',
            'timestamp' => $updatedAt ?: date('c'),
            'priority' => computePriorityForStatus($status),
            'status' => $status,
            'type' => 'medicine_alert',
            'medicine_id' => (int)($r['MedicineID'] ?? 0),
            'inventory_id' => 0,
            'target_url' => medicineLink((int)($r['MedicineID'] ?? 0), 0, $status),
            'target_label' => 'Open medicine',
        ];
    }

    $prioScore = ['High' => 3, 'Medium' => 2, 'Low' => 1];
    usort($notifications, function($a, $b) use ($prioScore) {
        $pa = $prioScore[$a['priority'] ?? 'Low'] ?? 1;
        $pb = $prioScore[$b['priority'] ?? 'Low'] ?? 1;
        if ($pa !== $pb) return $pb - $pa;
        return strcmp((string)($b['timestamp'] ?? ''), (string)($a['timestamp'] ?? ''));
    });

    respond([
        'success' => true,
        'last_updated' => date('c'),
        'notifications' => array_slice($notifications, 0, 15),
        'user_id' => $userId,
    ]);
} catch (Throwable $e) {
    respond(['success' => false, 'message' => 'Failed to fetch notifications', 'error' => $e->getMessage()], 500);
}
