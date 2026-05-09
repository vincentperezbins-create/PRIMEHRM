<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

require_login();
require_validator($pdo, 'ipcrf');header('Content-Type: application/json');

$where = [];
$params = [];
$status = $_GET['status'] ?? '';

if (in_array($status, ['Draft','For Review','Reviewed','Approved','Returned'], true)) {
    $where[] = 'i.status = ?';
    $params[] = $status;
}

$sql = "
    SELECT
        i.*,
        u.employeeID,
        CONCAT(u.first_name, ' ', u.last_name) AS employee_name,
        COALESCE(s.schoolname, 'No school') AS schoolname
    FROM sdopang1_ipcrf i
    JOIN sdopang1_user u ON u.user_id = i.user_id
    LEFT JOIN sdopang1schoollist s ON s.schoolID = u.school_id
";

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY i.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$data = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $id = (int) $row['ipcrf_id'];
    $data[] = [
        'employee' => trim(($row['employeeID'] ?: 'No Employee ID') . ' - ' . $row['employee_name']),
        'school' => $row['schoolname'],
        'title' => $row['title'],
        'period' => $row['school_year'] . ' / ' . $row['rating_period'],
        'status' => $row['status'],
        'overall_rating' => $row['overall_rating'] !== null ? number_format((float) $row['overall_rating'], 2) : '-',
        'action' => '<a class="btn btn-sm btn-primary" href="admin_view_ipcrf.php?id='.$id.'">Open</a>',
    ];
}

echo json_encode(['data' => $data]);


