<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/leave_helpers.php';

require_login();
require_role([1]);

header('Content-Type: application/json');

try {
    $employeeId = trim((string) ($_GET['employee_id'] ?? ''));
    $name = trim((string) ($_GET['name'] ?? ''));
    $schoolId = trim((string) ($_GET['school_id'] ?? ''));
    $officeId = filter_input(INPUT_GET, 'office_id', FILTER_VALIDATE_INT);
    $year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);
    $month = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT);

    $where = ["la.status = 'approved'"];
    $params = [];

    if ($employeeId !== '') {
        $where[] = 'u.employeeID LIKE ?';
        $params[] = '%' . $employeeId . '%';
    }

    if ($name !== '') {
        $where[] = "TRIM(CONCAT(u.first_name, ' ', COALESCE(NULLIF(u.middle_name, ''), ''), IF(u.middle_name IS NULL OR u.middle_name = '', '', ' '), u.last_name)) LIKE ?";
        $params[] = '%' . $name . '%';
    }

    if ($schoolId !== '') {
        $where[] = 'u.school_id = ?';
        $params[] = $schoolId;
    }

    if ($officeId) {
        $where[] = 'u.office_id = ?';
        $params[] = $officeId;
    }

    if ($year) {
        $where[] = 'YEAR(la.date_from) = ?';
        $params[] = $year;
    }

    if ($month && $month >= 1 && $month <= 12) {
        $where[] = 'MONTH(la.date_from) = ?';
        $params[] = $month;
    }

    $stmt = $pdo->prepare("
        SELECT
            la.application_id,
            la.user_id,
            la.date_from,
            la.date_to,
            la.days,
            la.status,
            la.approved_at,
            la.created_at,
            " . (leave_has_column($pdo, 'leave_applications', 'pay_status') ? "COALESCE(la.pay_status, 'with_pay')" : "'with_pay'") . " AS pay_status,
            u.employeeID AS employee_id,
            TRIM(CONCAT(u.first_name, ' ', COALESCE(NULLIF(u.middle_name, ''), ''), IF(u.middle_name IS NULL OR u.middle_name = '', '', ' '), u.last_name)) AS name,
            lt.leave_code,
            lt.leave_name,
            COALESCE(s.schoolname, '') AS schoolname,
            COALESCE(o.office_name, '') AS office_name,
            CONCAT(DATE_FORMAT(la.date_from, '%b %d, %Y'), ' - ', DATE_FORMAT(la.date_to, '%b %d, %Y')) AS date_range,
            DATE_FORMAT(la.approved_at, '%b %d, %Y') AS approved_date
        FROM leave_applications la
        JOIN sdopang1_user u ON u.user_id = la.user_id
        JOIN leave_types lt ON lt.leave_type_id = la.leave_type_id
        LEFT JOIN sdopang1schoollist s ON s.schoolID = u.school_id
        LEFT JOIN sdopang1_offices o ON o.office_id = u.office_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY la.date_from DESC, la.application_id DESC
    ");
    $stmt->execute($params);

    echo json_encode(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
    echo json_encode(['data' => [], 'error' => $e->getMessage()]);
}
