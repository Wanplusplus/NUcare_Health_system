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

$roles = $_SESSION['Roles'] ?? [];
if (array_intersect($roles, ['Admin', 'Super Admin']) !== []) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Medical staff dashboard only.']);
    exit;
}

$pdo = require __DIR__ . '/../config/db_pdo.php';
$userId = (int)$_SESSION['UserID'];

function one(PDO $pdo, string $sql, array $params = []): int
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

function rows(PDO $pdo, string $sql, array $params = []): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function full_name(?array $row): string
{
    if (!$row) {
        return 'Medical Staff';
    }
    return trim((string)($row['FirstName'] ?? '') . ' ' . (string)($row['LastName'] ?? '')) ?: 'Medical Staff';
}

try {
    $profileStmt = $pdo->prepare("
        SELECT sp.FirstName, sp.LastName, sp.MiddleName, sp.SchoolID, sp.Email
        FROM users u
        INNER JOIN school_people sp ON sp.SchoolPersonID = u.SchoolPersonID
        WHERE u.UserID = ?
        LIMIT 1
    ");
    $profileStmt->execute([$userId]);
    $profile = $profileStmt->fetch(PDO::FETCH_ASSOC) ?: null;

    $todayParams = [':today' => date('Y-m-d')];
    $weekParams = [
        ':week_start' => date('Y-m-d', strtotime('monday this week')),
        ':week_end' => date('Y-m-d', strtotime('monday next week')),
    ];
    $monthParams = [
        ':month_start' => date('Y-m-01'),
        ':month_end' => date('Y-m-d', strtotime('first day of next month')),
    ];

    $patientsToday = one($pdo, "SELECT COUNT(DISTINCT SchoolPersonID) FROM clinic_transactions WHERE VisitDate = :today", $todayParams);
    $consultationsToday = one($pdo, "SELECT COUNT(*) FROM clinic_transactions WHERE VisitDate = :today AND ConsultationStatus = 'Completed'", $todayParams);
    $medicinesDispensed = one($pdo, "SELECT COUNT(*) FROM medicine_dispensing WHERE DATE(DispensedAt) = :today", $todayParams);
    $pendingConsultations = one($pdo, "SELECT COUNT(*) FROM clinic_transactions WHERE ConsultationStatus IN ('Waiting','Consulting')");
    $followUpCases = one($pdo, "SELECT COUNT(*) FROM clinic_transactions WHERE Notes LIKE '%follow%'");
    $lowStockMedicines = one($pdo, "
        SELECT COUNT(*) FROM (
            SELECT m.MedicineID, COALESCE(SUM(mi.Quantity), 0) AS CurrentStock, COALESCE(MAX(mi.ReorderLevel), 10) AS MinimumStock
            FROM medicines m
            LEFT JOIN medicine_inventory mi ON mi.MedicineID = m.MedicineID
            GROUP BY m.MedicineID
            HAVING CurrentStock <= MinimumStock
        ) x
    ");

    $trendRange = (string)($_GET['trend'] ?? 'daily');
    if (!in_array($trendRange, ['daily', 'weekly', 'monthly'], true)) {
        $trendRange = 'daily';
    }

    if ($trendRange === 'weekly') {
        $trendSql = "
            SELECT YEARWEEK(VisitDate, 1) AS sort_key,
                   CONCAT(DATE_FORMAT(MIN(VisitDate), '%b %d'), ' - ', DATE_FORMAT(MAX(VisitDate), '%b %d')) AS label,
                   COUNT(*) AS total
            FROM clinic_transactions
            WHERE VisitDate >= DATE_SUB(CURDATE(), INTERVAL 12 WEEK)
            GROUP BY YEARWEEK(VisitDate, 1)
            ORDER BY sort_key ASC
        ";
    } elseif ($trendRange === 'monthly') {
        $trendSql = "
            SELECT DATE_FORMAT(VisitDate, '%Y-%m') AS sort_key,
                   DATE_FORMAT(VisitDate, '%b %Y') AS label,
                   COUNT(*) AS total
            FROM clinic_transactions
            WHERE VisitDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(VisitDate, '%Y-%m'), DATE_FORMAT(VisitDate, '%b %Y')
            ORDER BY sort_key ASC
        ";
    } else {
        $trendSql = "
            SELECT VisitDate AS sort_key, DATE_FORMAT(VisitDate, '%b %d') AS label, COUNT(*) AS total
            FROM clinic_transactions
            WHERE VisitDate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY VisitDate
            ORDER BY VisitDate ASC
        ";
    }
    $trendRows = rows($pdo, $trendSql);
    $trendTotal = array_sum(array_map(static fn($r) => (int)$r['total'], $trendRows));
    $trendAvg = count($trendRows) > 0 ? round($trendTotal / count($trendRows), 1) : 0;
    $highest = ['label' => 'None', 'total' => 0];
    foreach ($trendRows as $r) {
        if ((int)$r['total'] > (int)$highest['total']) {
            $highest = ['label' => (string)$r['label'], 'total' => (int)$r['total']];
        }
    }

    $statusRows = rows($pdo, "
        SELECT
            CASE
                WHEN Notes LIKE '%follow%' THEN 'Follow-Up'
                WHEN ConsultationStatus = 'Completed' THEN 'Completed'
                WHEN ConsultationStatus = 'Cancelled' THEN 'Cancelled'
                ELSE 'Pending'
            END AS status_label,
            COUNT(*) AS total
        FROM clinic_transactions
        WHERE VisitDate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY status_label
    ");

    $complaints = rows($pdo, "
        SELECT COALESCE(NULLIF(TRIM(Complaint), ''), 'Unspecified') AS label, COUNT(*) AS total
        FROM clinic_transactions
        WHERE VisitDate >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
        GROUP BY COALESCE(NULLIF(TRIM(Complaint), ''), 'Unspecified')
        ORDER BY total DESC, label ASC
        LIMIT 10
    ");

    $dispensed = rows($pdo, "
        SELECT m.MedicineName AS label, COUNT(*) AS total
        FROM medicine_dispensing md
        INNER JOIN medicine_inventory mi ON mi.InventoryID = md.InventoryID
        INNER JOIN medicines m ON m.MedicineID = mi.MedicineID
        WHERE md.DispensedAt >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
        GROUP BY m.MedicineID, m.MedicineName
        ORDER BY total DESC, m.MedicineName ASC
        LIMIT 10
    ");

    $inventoryStatus = rows($pdo, "
        SELECT status_label AS label, COUNT(*) AS total
        FROM (
            SELECT
                m.MedicineID,
                CASE
                    WHEN COALESCE(SUM(mi.Quantity), 0) <= 0 THEN 'Out of Stock'
                    WHEN COALESCE(SUM(mi.Quantity), 0) <= COALESCE(MAX(mi.ReorderLevel), 10) THEN 'Low Stock'
                    ELSE 'In Stock'
                END AS status_label
            FROM medicines m
            LEFT JOIN medicine_inventory mi ON mi.MedicineID = m.MedicineID
            GROUP BY m.MedicineID
        ) x
        GROUP BY status_label
    ");

    $peak = rows($pdo, "
        SELECT HOUR(CreatedAt) AS hour_num, COUNT(*) AS total
        FROM clinic_transactions
        WHERE CreatedAt >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY HOUR(CreatedAt)
        ORDER BY total DESC
        LIMIT 1
    ");
    $peakText = 'No consultation hour pattern is available yet.';
    if ($peak) {
        $h = (int)$peak[0]['hour_num'];
        $start = date('g:00 A', strtotime(sprintf('%02d:00:00', $h)));
        $end = date('g:00 A', strtotime(sprintf('%02d:00:00', ($h + 1) % 24)));
        $peakText = "Most consultations occurred between {$start} and {$end}.";
    }

    $weekTotal = one($pdo, "SELECT COUNT(*) FROM clinic_transactions WHERE VisitDate >= :week_start AND VisitDate < :week_end", $weekParams);
    $commonWeek = rows($pdo, "
        SELECT COALESCE(NULLIF(TRIM(Complaint), ''), 'Unspecified') AS label, COUNT(*) AS total
        FROM clinic_transactions
        WHERE VisitDate >= :week_start AND VisitDate < :week_end
        GROUP BY COALESCE(NULLIF(TRIM(Complaint), ''), 'Unspecified')
        ORDER BY total DESC
        LIMIT 1
    ", $weekParams);
    $commonText = 'No complaint trend is available for this week.';
    if ($commonWeek && $weekTotal > 0) {
        $pct = round(((int)$commonWeek[0]['total'] / $weekTotal) * 100);
        $commonText = "{$commonWeek[0]['label']} accounts for {$pct}% of all consultations this week.";
    }

    $topMedMonth = rows($pdo, "
        SELECT m.MedicineName AS label, COUNT(*) AS total
        FROM medicine_dispensing md
        INNER JOIN medicine_inventory mi ON mi.InventoryID = md.InventoryID
        INNER JOIN medicines m ON m.MedicineID = mi.MedicineID
        WHERE md.DispensedAt >= :month_start AND md.DispensedAt < :month_end
        GROUP BY m.MedicineID, m.MedicineName
        ORDER BY total DESC
        LIMIT 1
    ", $monthParams);
    $medicineText = $topMedMonth
        ? "{$topMedMonth[0]['label']} was dispensed {$topMedMonth[0]['total']} times this month."
        : 'No medicine dispensing has been recorded this month.';

    $newPatients = one($pdo, "
        SELECT COUNT(*)
        FROM school_people sp
        WHERE DATE(sp.CreatedAt) = CURDATE()
          AND EXISTS (SELECT 1 FROM clinic_transactions ct WHERE ct.SchoolPersonID = sp.SchoolPersonID)
    ");

    $avgMinutes = one($pdo, "
        SELECT COALESCE(ROUND(AVG(TIMESTAMPDIFF(MINUTE, CreatedAt, COALESCE(UpdatedAt, CreatedAt)))), 0)
        FROM clinic_transactions
        WHERE ConsultationStatus = 'Completed'
          AND UpdatedAt IS NOT NULL
          AND UpdatedAt >= CreatedAt
    ");

    $activities = rows($pdo, "
        SELECT *
        FROM (
            SELECT 'Consultation completed' AS activity_type,
                   COALESCE(NULLIF(TRIM(CONCAT(sp.FirstName, ' ', sp.LastName)), ''), 'Unknown patient') AS patient_name,
                   COALESCE(ct.UpdatedAt, ct.CreatedAt) AS happened_at
            FROM clinic_transactions ct
            INNER JOIN school_people sp ON sp.SchoolPersonID = ct.SchoolPersonID
            WHERE ct.ConsultationStatus = 'Completed'
            UNION ALL
            SELECT 'Medicine dispensed', COALESCE(NULLIF(TRIM(CONCAT(sp.FirstName, ' ', sp.LastName)), ''), 'Unknown patient'), md.DispensedAt
            FROM medicine_dispensing md
            INNER JOIN clinic_transactions ct ON ct.ClinicTransactionID = md.ClinicTransactionID
            INNER JOIN school_people sp ON sp.SchoolPersonID = ct.SchoolPersonID
            UNION ALL
            SELECT 'Follow-up scheduled', COALESCE(NULLIF(TRIM(CONCAT(sp.FirstName, ' ', sp.LastName)), ''), 'Unknown patient'), COALESCE(ct.UpdatedAt, ct.CreatedAt)
            FROM clinic_transactions ct
            INNER JOIN school_people sp ON sp.SchoolPersonID = ct.SchoolPersonID
            WHERE ct.Notes LIKE '%follow%'
            UNION ALL
            SELECT 'New patient registered', COALESCE(NULLIF(TRIM(CONCAT(sp.FirstName, ' ', sp.LastName)), ''), 'Unknown patient'), sp.CreatedAt
            FROM school_people sp
            WHERE EXISTS (SELECT 1 FROM clinic_transactions ct WHERE ct.SchoolPersonID = sp.SchoolPersonID)
        ) activity
        ORDER BY happened_at DESC
        LIMIT 20
    ");

    $lowStock = rows($pdo, "
        SELECT m.MedicineName, COALESCE(SUM(mi.Quantity), 0) AS remaining, COALESCE(MAX(mi.ReorderLevel), 10) AS threshold
        FROM medicines m
        LEFT JOIN medicine_inventory mi ON mi.MedicineID = m.MedicineID
        GROUP BY m.MedicineID, m.MedicineName
        HAVING remaining <= threshold
        ORDER BY remaining ASC, m.MedicineName ASC
        LIMIT 10
    ");
    $followAlerts = rows($pdo, "
        SELECT COALESCE(NULLIF(TRIM(CONCAT(sp.FirstName, ' ', sp.LastName)), ''), 'Unknown patient') AS patient_name,
               COALESCE(ct.UpdatedAt, ct.CreatedAt) AS alert_date,
               ct.Notes
        FROM clinic_transactions ct
        INNER JOIN school_people sp ON sp.SchoolPersonID = ct.SchoolPersonID
        WHERE ct.Notes LIKE '%follow%'
        ORDER BY COALESCE(ct.UpdatedAt, ct.CreatedAt) DESC
        LIMIT 10
    ");
    $expiring = rows($pdo, "
        SELECT m.MedicineName, mi.Quantity, mi.ExpiryDate
        FROM medicine_inventory mi
        INNER JOIN medicines m ON m.MedicineID = mi.MedicineID
        WHERE mi.ExpiryDate IS NOT NULL
          AND mi.ExpiryDate >= CURDATE()
          AND mi.ExpiryDate <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ORDER BY mi.ExpiryDate ASC
        LIMIT 10
    ");

    echo json_encode([
        'ok' => true,
        'profile' => $profile,
        'staffName' => full_name($profile),
        'kpis' => [
            ['key' => 'patientsToday', 'title' => 'Patients Today', 'value' => $patientsToday, 'trend' => 'Unique patients served today', 'icon' => 'fa-user-injured'],
            ['key' => 'consultationsToday', 'title' => 'Consultations Today', 'value' => $consultationsToday, 'trend' => 'Completed records today', 'icon' => 'fa-stethoscope'],
            ['key' => 'medicinesDispensed', 'title' => 'Medicines Dispensed', 'value' => $medicinesDispensed, 'trend' => 'Release transactions today', 'icon' => 'fa-pills'],
            ['key' => 'pendingConsultations', 'title' => 'Pending Consultations', 'value' => $pendingConsultations, 'trend' => 'Waiting or consulting', 'icon' => 'fa-clock'],
            ['key' => 'followUpCases', 'title' => 'Follow-Up Cases', 'value' => $followUpCases, 'trend' => 'Records tagged in notes', 'icon' => 'fa-calendar-check'],
            ['key' => 'lowStockMedicines', 'title' => 'Low Stock Medicines', 'value' => $lowStockMedicines, 'trend' => 'At or below reorder level', 'icon' => 'fa-triangle-exclamation', 'warning' => $lowStockMedicines > 0],
        ],
        'consultationTrend' => [
            'range' => $trendRange,
            'rows' => $trendRows,
            'total' => $trendTotal,
            'average' => $trendAvg,
            'highest' => $highest,
        ],
        'statusDistribution' => $statusRows,
        'commonComplaints' => $complaints,
        'mostDispensed' => $dispensed,
        'inventoryStatus' => $inventoryStatus,
        'insights' => [
            ['title' => 'Peak Consultation Hour', 'text' => $peakText],
            ['title' => 'Most Common Illness', 'text' => $commonText],
            ['title' => 'Most Dispensed Medicine', 'text' => $medicineText],
            ['title' => 'New Patients', 'text' => $newPatients . ' new patients were registered today.'],
            ['title' => 'Average Consultation Time', 'text' => 'Average consultation duration is ' . $avgMinutes . ' minutes.'],
        ],
        'activities' => $activities,
        'alerts' => [
            'lowStock' => $lowStock,
            'followUps' => $followAlerts,
            'expiring' => $expiring,
        ],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Dashboard data failed to load.']);
}
