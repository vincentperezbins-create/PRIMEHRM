<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/leave_helpers.php';

require_login();
require_scoped_validator($pdo, 'leave');

header('Content-Type: application/json');

try {
    $where = [];
    $params = [];
    $scope = user_area_validation_scope($pdo, 'leave');

    if ($scope === 'school') {
        $user = current_user_row($pdo);
        $where[] = 'u.school_id = ?';
        $params[] = (string) ($user['school_id'] ?? '');
    }

    $sql = "
        SELECT 
            la.application_id,
            la.user_id,
            la.leave_type_id,
            la.date_from,
            la.date_to,
            la.days,
            la.reason,
            la.status,
            la.created_at,
            " . (leave_has_column($pdo, 'leave_applications', 'cto_schedule') ? "la.cto_schedule" : "NULL") . " AS cto_schedule,
            " . (leave_has_column($pdo, 'leave_applications', 'leave_schedule') ? "la.leave_schedule" : "NULL") . " AS leave_schedule,
            " . (leave_has_column($pdo, 'leave_applications', 'pay_status') ? "COALESCE(la.pay_status, 'with_pay')" : "'with_pay'") . " AS pay_status,

            u.employeeID AS employee_id,
            CONCAT(u.first_name,' ',u.last_name) AS name,
            lt.leave_code,
            lt.leave_name,

            CONCAT(
                DATE_FORMAT(la.date_from, '%b %d, %Y'),
                ' - ',
                DATE_FORMAT(la.date_to, '%b %d, %Y')
            ) AS date_range

        FROM leave_applications la
        JOIN sdopang1_user u ON la.user_id = u.user_id
        JOIN leave_types lt ON la.leave_type_id = lt.leave_type_id
    ";

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY la.created_at DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($data as &$row) {
        $scheduleJson = $row['leave_schedule'] ?? ($row['cto_schedule'] ?? null);
        if (!empty($scheduleJson)) {
            $schedule = json_decode((string) $scheduleJson, true);
            if (is_array($schedule)) {
                $row['date_range'] = leave_cto_schedule_label($schedule);
            }
        }
    }
    unset($row);

    echo json_encode([
        "data" => $data
    ]);

} catch (Exception $e) {

    echo json_encode([
        "data" => [],
        "error" => $e->getMessage()
    ]);
}


