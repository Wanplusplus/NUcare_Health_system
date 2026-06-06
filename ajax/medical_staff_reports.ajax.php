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
    echo json_encode(['ok' => false, 'message' => 'Clinic reports only.']);
    exit;
}

$pdo = require __DIR__ . '/../config/db_pdo.php';

function json_body(): array
{
    $raw = file_get_contents('php://input') ?: '';
    $body = json_decode($raw, true);
    return is_array($body) ? $body : [];
}

function date_bounds(string $range, string $start, string $end): array
{
    $today = new DateTimeImmutable('today');
    if ($range === 'this_week') {
        $from = $today->modify('monday this week');
        $to = $from->modify('+6 days');
    } elseif ($range === 'this_month') {
        $from = $today->modify('first day of this month');
        $to = $today->modify('last day of this month');
    } elseif ($range === 'custom') {
        $from = DateTimeImmutable::createFromFormat('Y-m-d', $start) ?: $today;
        $to = DateTimeImmutable::createFromFormat('Y-m-d', $end) ?: $from;
        if ($to < $from) {
            throw new InvalidArgumentException('End date cannot be before start date.');
        }
    } else {
        $from = $today;
        $to = $today;
    }

    return [
        'from' => $from->format('Y-m-d') . ' 00:00:00',
        'to' => $to->format('Y-m-d') . ' 23:59:59',
        'start_date' => $from->format('Y-m-d'),
        'end_date' => $to->format('Y-m-d'),
        'label' => $from->format('M d, Y') === $to->format('M d, Y')
            ? $from->format('M d, Y')
            : $from->format('M d, Y') . ' to ' . $to->format('M d, Y'),
    ];
}

function actor_name(PDO $pdo, int $userId): string
{
    $stmt = $pdo->prepare("
        SELECT CONCAT(sp.FirstName, ' ', sp.LastName)
        FROM users u
        INNER JOIN school_people sp ON sp.SchoolPersonID = u.SchoolPersonID
        WHERE u.UserID = ?
    ");
    $stmt->execute([$userId]);
    return trim((string)$stmt->fetchColumn()) ?: 'Medical Staff';
}

function scalar(PDO $pdo, string $sql, array $params): string
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (string)($stmt->fetchColumn() ?: '0');
}

function projected_sections(PDO $pdo, array $bounds, string $reportType): array
{
    $consultParams = [
        ':start_date' => $bounds['start_date'],
        ':end_date' => $bounds['end_date'],
    ];
    $medicineParams = [
        ':from' => $bounds['from'],
        ':to' => $bounds['to'],
    ];

    $consultationRows = [
        'PE' => ['row_label' => 'PE', 'shs' => 0, 'college' => 0, 'faculty' => 0, 'asp' => 0, 'total' => 0],
        'Systemic Viral Illness' => ['row_label' => 'Systemic Viral Illness', 'shs' => 0, 'college' => 0, 'faculty' => 0, 'asp' => 0, 'total' => 0],
        'Cardiovascular Problems' => ['row_label' => 'Cardiovascular Problems', 'shs' => 0, 'college' => 0, 'faculty' => 0, 'asp' => 0, 'total' => 0],
        'Respiratory Problems' => ['row_label' => 'Respiratory Problems', 'shs' => 0, 'college' => 0, 'faculty' => 0, 'asp' => 0, 'total' => 0],
        'Gastrointestinal Problems' => ['row_label' => 'Gastrointestinal Problems', 'shs' => 0, 'college' => 0, 'faculty' => 0, 'asp' => 0, 'total' => 0],
        'Gynecologic Problems' => ['row_label' => 'Gynecologic Problems', 'shs' => 0, 'college' => 0, 'faculty' => 0, 'asp' => 0, 'total' => 0],
        'Allergy/Hypersensitivity Problems' => ['row_label' => 'Allergy/Hypersensitivity Problems', 'shs' => 0, 'college' => 0, 'faculty' => 0, 'asp' => 0, 'total' => 0],
        'Infectious Problems' => ['row_label' => 'Infectious Problems', 'shs' => 0, 'college' => 0, 'faculty' => 0, 'asp' => 0, 'total' => 0],
        'Minor Accidents / Trauma' => ['row_label' => 'Minor Accidents / Trauma', 'shs' => 0, 'college' => 0, 'faculty' => 0, 'asp' => 0, 'total' => 0],
        'Other Consult' => ['row_label' => 'Other Consult', 'shs' => 0, 'college' => 0, 'faculty' => 0, 'asp' => 0, 'total' => 0],
    ];

    $consultStmt = $pdo->prepare("
        SELECT
            CASE
                WHEN COALESCE(ct.ServiceType, ct.Complaint, '') LIKE '%PE%' OR COALESCE(ct.ServiceType, ct.Complaint, '') LIKE '%Physical%' THEN 'PE'
                WHEN COALESCE(ct.Complaint, '') LIKE '%viral%' OR COALESCE(ct.Complaint, '') LIKE '%flu%' OR COALESCE(ct.Complaint, '') LIKE '%fever%' THEN 'Systemic Viral Illness'
                WHEN COALESCE(ct.Complaint, '') LIKE '%cardio%' OR COALESCE(ct.Complaint, '') LIKE '%heart%' OR COALESCE(ct.Complaint, '') LIKE '%blood pressure%' OR COALESCE(ct.Complaint, '') LIKE '%hypertension%' THEN 'Cardiovascular Problems'
                WHEN COALESCE(ct.Complaint, '') LIKE '%cough%' OR COALESCE(ct.Complaint, '') LIKE '%cold%' OR COALESCE(ct.Complaint, '') LIKE '%asthma%' OR COALESCE(ct.Complaint, '') LIKE '%respir%' THEN 'Respiratory Problems'
                WHEN COALESCE(ct.Complaint, '') LIKE '%stomach%' OR COALESCE(ct.Complaint, '') LIKE '%abdom%' OR COALESCE(ct.Complaint, '') LIKE '%diarr%' OR COALESCE(ct.Complaint, '') LIKE '%gastro%' THEN 'Gastrointestinal Problems'
                WHEN COALESCE(ct.Complaint, '') LIKE '%gyne%' OR COALESCE(ct.Complaint, '') LIKE '%menstrual%' OR COALESCE(ct.Complaint, '') LIKE '%pregnan%' THEN 'Gynecologic Problems'
                WHEN COALESCE(ct.Complaint, '') LIKE '%allerg%' OR COALESCE(ct.Complaint, '') LIKE '%hypersens%' THEN 'Allergy/Hypersensitivity Problems'
                WHEN COALESCE(ct.Complaint, '') LIKE '%infect%' THEN 'Infectious Problems'
                WHEN COALESCE(ct.Complaint, '') LIKE '%accident%' OR COALESCE(ct.Complaint, '') LIKE '%trauma%' OR COALESCE(ct.Complaint, '') LIKE '%injur%' OR COALESCE(ct.Complaint, '') LIKE '%wound%' THEN 'Minor Accidents / Trauma'
                ELSE 'Other Consult'
            END AS row_label,
            SUM(CASE WHEN sp.PersonType = 'Student' AND (COALESCE(p.ProgramName, '') LIKE '%SHS%' OR COALESCE(p.Department, '') LIKE '%SHS%') THEN 1 ELSE 0 END) AS shs,
            SUM(CASE WHEN sp.PersonType = 'Student' AND NOT (COALESCE(p.ProgramName, '') LIKE '%SHS%' OR COALESCE(p.Department, '') LIKE '%SHS%') THEN 1 ELSE 0 END) AS college,
            SUM(CASE WHEN sp.PersonType = 'Faculty' THEN 1 ELSE 0 END) AS faculty,
            SUM(CASE WHEN sp.PersonType NOT IN ('Student', 'Faculty') THEN 1 ELSE 0 END) AS asp,
            COUNT(*) AS total
        FROM clinic_transactions ct
        INNER JOIN school_people sp ON sp.SchoolPersonID = ct.SchoolPersonID
        LEFT JOIN student_enrollments se ON se.SchoolPersonID = sp.SchoolPersonID
        LEFT JOIN programs p ON p.ProgramID = se.ProgramID
        WHERE ct.VisitDate BETWEEN :start_date AND :end_date
        GROUP BY row_label
    ");
    $consultStmt->execute($consultParams);
    foreach (($consultStmt->fetchAll(PDO::FETCH_ASSOC) ?: []) as $row) {
        $key = (string)$row['row_label'];
        if (!isset($consultationRows[$key])) {
            $key = 'Other Consult';
        }
        foreach (['shs', 'college', 'faculty', 'asp', 'total'] as $field) {
            $consultationRows[$key][$field] += (int)$row[$field];
        }
    }

    $medicineStmt = $pdo->prepare("
        SELECT m.MedicineName AS medicine_name, COALESCE(SUM(md.QuantityDispensed), 0) AS quantity
        FROM medicines m
        LEFT JOIN medicine_inventory mi ON mi.MedicineID = m.MedicineID
        LEFT JOIN medicine_dispensing md
            ON md.InventoryID = mi.InventoryID
           AND md.DispensedAt BETWEEN :from AND :to
        GROUP BY m.MedicineID, m.MedicineName
        ORDER BY m.MedicineName ASC
    ");
    $medicineStmt->execute($medicineParams);
    $medicineRows = $medicineStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $consultationSection = [
            'title' => 'Medical Consultation',
            'columns' => [
                ['key' => 'row_label', 'label' => ''],
                ['key' => 'shs', 'label' => 'SHS'],
                ['key' => 'college', 'label' => 'College'],
                ['key' => 'faculty', 'label' => 'Faculty'],
                ['key' => 'asp', 'label' => 'ASP'],
                ['key' => 'total', 'label' => 'Total'],
            ],
            'rows' => array_values($consultationRows),
    ];
    $medicineSection = [
            'title' => 'Medicine Dispense / Released',
            'columns' => [
                ['key' => 'medicine_name', 'label' => 'Medicine'],
                ['key' => 'quantity', 'label' => 'Qty'],
            ],
            'rows' => $medicineRows,
    ];

    return $reportType === 'medicine_report' ? [$medicineSection] : [$consultationSection];
}

$body = json_body();
$reportType = (string)($body['report_type'] ?? 'consultation_report');
$dateRange = (string)($body['date_range'] ?? 'today');
$dateFrom = (string)($body['date_from'] ?? '');
$dateTo = (string)($body['date_to'] ?? '');
$page = max(1, (int)($body['page'] ?? 1));
$perPage = min(100, max(10, (int)($body['per_page'] ?? 10)));
$search = trim((string)($body['search'] ?? ''));
$sortKey = (string)($body['sort_key'] ?? '');
$sortDir = strtolower((string)($body['sort_dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';

$configs = [
    'consultation_report' => [
        'title' => 'Consultation Report',
        'date_column' => 'ct.VisitDate',
        'base' => "
            FROM clinic_transactions ct
            INNER JOIN school_people sp ON sp.SchoolPersonID = ct.SchoolPersonID
            LEFT JOIN medical_professionals mp ON mp.MedProfID = ct.MedProfID
            LEFT JOIN users su ON su.UserID = mp.UserID
            LEFT JOIN school_people staff ON staff.SchoolPersonID = su.SchoolPersonID
        ",
        'select' => "
            ct.ClinicTransactionID AS consultation_id,
            COALESCE(NULLIF(TRIM(CONCAT(sp.FirstName, ' ', sp.LastName)), ''), 'Unknown patient') AS patient_name,
            ct.VisitDate AS visit_date,
            TIME(ct.CreatedAt) AS visit_time,
            COALESCE(ct.Complaint, '') AS complaint,
            COALESCE(ct.Notes, '') AS diagnosis,
            CASE WHEN ct.Notes LIKE '%follow%' THEN 'Follow-Up' ELSE ct.ConsultationStatus END AS status,
            COALESCE(NULLIF(TRIM(CONCAT(staff.FirstName, ' ', staff.LastName)), ''), 'Unassigned') AS staff
        ",
        'columns' => [
            ['key' => 'consultation_id', 'label' => 'Consultation ID'],
            ['key' => 'patient_name', 'label' => 'Patient Name'],
            ['key' => 'visit_date', 'label' => 'Date'],
            ['key' => 'visit_time', 'label' => 'Time'],
            ['key' => 'complaint', 'label' => 'Complaint'],
            ['key' => 'diagnosis', 'label' => 'Diagnosis'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'staff', 'label' => 'Staff'],
        ],
        'sorts' => ['consultation_id' => 'ct.ClinicTransactionID', 'patient_name' => 'patient_name', 'visit_date' => 'ct.VisitDate', 'status' => 'status', 'staff' => 'staff'],
        'search' => ['sp.FirstName', 'sp.LastName', 'ct.Complaint', 'ct.Notes', 'ct.ConsultationStatus'],
    ],
    'medicine_report' => [
        'title' => 'Medicine Dispensing Report',
        'date_column' => 'md.DispensedAt',
        'base' => "
            FROM medicine_dispensing md
            INNER JOIN clinic_transactions ct ON ct.ClinicTransactionID = md.ClinicTransactionID
            INNER JOIN school_people sp ON sp.SchoolPersonID = ct.SchoolPersonID
            INNER JOIN medicine_inventory mi ON mi.InventoryID = md.InventoryID
            INNER JOIN medicines m ON m.MedicineID = mi.MedicineID
            LEFT JOIN medical_professionals mp ON mp.MedProfID = ct.MedProfID
            LEFT JOIN users su ON su.UserID = mp.UserID
            LEFT JOIN school_people staff ON staff.SchoolPersonID = su.SchoolPersonID
        ",
        'select' => "
            md.DispensingID AS transaction_id,
            m.MedicineName AS medicine_name,
            md.QuantityDispensed AS quantity,
            COALESCE(NULLIF(TRIM(CONCAT(staff.FirstName, ' ', staff.LastName)), ''), 'Unassigned') AS dispensed_by,
            COALESCE(NULLIF(TRIM(CONCAT(sp.FirstName, ' ', sp.LastName)), ''), 'Unknown patient') AS patient,
            md.DispensedAt AS date
        ",
        'columns' => [
            ['key' => 'transaction_id', 'label' => 'Transaction ID'],
            ['key' => 'medicine_name', 'label' => 'Medicine Name'],
            ['key' => 'quantity', 'label' => 'Quantity'],
            ['key' => 'dispensed_by', 'label' => 'Dispensed By'],
            ['key' => 'patient', 'label' => 'Patient'],
            ['key' => 'date', 'label' => 'Date'],
        ],
        'sorts' => ['transaction_id' => 'md.DispensingID', 'medicine_name' => 'm.MedicineName', 'quantity' => 'md.QuantityDispensed', 'patient' => 'patient', 'date' => 'md.DispensedAt'],
        'search' => ['m.MedicineName', 'sp.FirstName', 'sp.LastName', 'staff.FirstName', 'staff.LastName'],
    ],
];

try {
    if (!isset($configs[$reportType])) {
        throw new InvalidArgumentException('Invalid report type.');
    }
    $bounds = date_bounds($dateRange, $dateFrom, $dateTo);
    $config = $configs[$reportType];
    $params = [':from' => $bounds['from'], ':to' => $bounds['to']];
    $where = ["{$config['date_column']} BETWEEN :from AND :to"];
    if (!empty($config['extra_where'])) {
        $where[] = $config['extra_where'];
    }
    if ($search !== '') {
        $parts = [];
        foreach ($config['search'] as $i => $column) {
            $key = ':search' . $i;
            $parts[] = "{$column} LIKE {$key}";
            $params[$key] = '%' . $search . '%';
        }
        $where[] = '(' . implode(' OR ', $parts) . ')';
    }
    $whereSql = 'WHERE ' . implode(' AND ', $where);
    $sortSql = $config['sorts'][$sortKey] ?? $config['sorts'][array_key_first($config['sorts'])];
    $offset = ($page - 1) * $perPage;

    $countStmt = $pdo->prepare("SELECT COUNT(*) {$config['base']} {$whereSql}");
    $countStmt->execute($params);
    $totalRows = (int)$countStmt->fetchColumn();

    $dataSql = "SELECT {$config['select']} {$config['base']} {$whereSql} ORDER BY {$sortSql} {$sortDir} LIMIT :limit OFFSET :offset";
    $dataStmt = $pdo->prepare($dataSql);
    foreach ($params as $key => $value) {
        $dataStmt->bindValue($key, $value);
    }
    $dataStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $dataStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $dataStmt->execute();
    $resultRows = $dataStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $summary = [];
    if ($reportType === 'consultation_report') {
        $summary = [
            ['label' => 'Total Consultations', 'value' => scalar($pdo, "SELECT COUNT(*) {$config['base']} {$whereSql}", $params)],
            ['label' => 'Completed Consultations', 'value' => scalar($pdo, "SELECT COUNT(*) {$config['base']} {$whereSql} AND ct.ConsultationStatus = 'Completed'", $params)],
            ['label' => 'Pending Consultations', 'value' => scalar($pdo, "SELECT COUNT(*) {$config['base']} {$whereSql} AND ct.ConsultationStatus IN ('Waiting','Consulting')", $params)],
            ['label' => 'Follow-Up Cases', 'value' => scalar($pdo, "SELECT COUNT(*) {$config['base']} {$whereSql} AND ct.Notes LIKE '%follow%'", $params)],
        ];
    } elseif ($reportType === 'medicine_report') {
        $top = scalar($pdo, "SELECT COALESCE(m.MedicineName, 'None') {$config['base']} {$whereSql} GROUP BY m.MedicineID, m.MedicineName ORDER BY COUNT(*) DESC LIMIT 1", $params);
        $summary = [
            ['label' => 'Total Dispensed', 'value' => scalar($pdo, "SELECT COALESCE(SUM(md.QuantityDispensed), 0) {$config['base']} {$whereSql}", $params)],
            ['label' => 'Unique Medicines', 'value' => scalar($pdo, "SELECT COUNT(DISTINCT m.MedicineID) {$config['base']} {$whereSql}", $params)],
            ['label' => 'Most Dispensed Medicine', 'value' => $top === '0' ? 'None' : $top],
        ];
    }

    echo json_encode([
        'ok' => true,
        'title' => $config['title'],
        'dateRangeLabel' => $bounds['label'],
        'generatedBy' => actor_name($pdo, (int)$_SESSION['UserID']),
        'columns' => $config['columns'],
        'rows' => $resultRows,
        'summary' => $summary,
        'reportSections' => projected_sections($pdo, $bounds, $reportType),
        'pagination' => [
            'page' => $page,
            'perPage' => $perPage,
            'totalRows' => $totalRows,
            'totalPages' => max(1, (int)ceil($totalRows / $perPage)),
        ],
    ]);
} catch (Throwable $e) {
    http_response_code($e instanceof InvalidArgumentException ? 422 : 500);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
