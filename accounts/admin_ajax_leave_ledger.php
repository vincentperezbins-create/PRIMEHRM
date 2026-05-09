<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

require_login();
require_role([1]);

header('Content-Type: application/json');

try {

    $year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT) ?: (int) date('Y');
    $user_id = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT) ?: null;

    // base query
    $sql = "
    SELECT 
        t.user_id,
        DATE(t.created_at) AS date,
        t.transaction_type,
        t.days,
        t.balance_after,
        t.remarks,

        lt.leave_code,
        lt.leave_name,

        u.employeeID AS employee_id,
        CONCAT(u.first_name,' ',u.last_name) AS name

    FROM leave_transactions t
    JOIN leave_types lt ON t.leave_type_id = lt.leave_type_id
    JOIN sdopang1_user u ON t.user_id = u.user_id
    WHERE YEAR(t.created_at) = ?
    ";

    if ($user_id) {
        $sql .= " AND t.user_id = ? ";
        $stmt = $pdo->prepare($sql . " ORDER BY t.created_at ASC");
        $stmt->execute([$year, $user_id]);
    } else {
        $stmt = $pdo->prepare($sql . " ORDER BY t.created_at ASC");
        $stmt->execute([$year]);
    }

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];

    foreach ($rows as $r) {

        $earned = 0;
        $used = 0;

        if (in_array($r['transaction_type'], ['earn', 'adjust'], true)) {
            $earned = $r['days'];
        } else {
            $used = $r['days'];
        }

        $data[] = [
            'date' => date('M d, Y', strtotime($r['date'])),
            'employee_id' => $r['employee_id'] ?: '-',
            'employee' => $r['name'],
            'leave_code' => $r['leave_code'],
            'leave_name' => $r['leave_name'],
            'earned' => $earned,
            'used' => $used,
            'balance' => number_format($r['balance_after'], 3),
            'remarks' => $r['remarks']
        ];
    }

    echo json_encode([
        "data" => $data
    ]);

} catch (Throwable $e) {

    echo json_encode([
        "data" => [],
        "error" => $e->getMessage()
    ]);
}
