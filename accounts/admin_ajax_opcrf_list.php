<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

require_login();
require_validator($pdo, 'opcrf');header('Content-Type: application/json');

$where = [];
$params = [];
$officeId = filter_input(INPUT_GET, 'office_id', FILTER_VALIDATE_INT);
$status = $_GET['status'] ?? '';

if ($officeId) {
    $where[] = 'o.office_id = ?';
    $params[] = $officeId;
}

if (in_array($status, ['Draft','For Review','Reviewed','Approved','Returned'], true)) {
    $where[] = 'o.status = ?';
    $params[] = $status;
}

$sql = "
    SELECT o.*, f.office_name
    FROM sdopang1_opcrf o
    JOIN sdopang1_offices f ON f.office_id = o.office_id
";

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY o.created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$data = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $id = (int) $row['opcrf_id'];
    $data[] = [
        'title' => $row['title'],
        'office_name' => $row['office_name'],
        'period' => $row['school_year'] . ' / ' . $row['quarter'],
        'status' => $row['status'],
        'overall_rating' => $row['overall_rating'] !== null ? number_format((float) $row['overall_rating'], 2) : '-',
        'action' => '<a class="btn btn-sm btn-primary" href="admin_view_opcrf.php?id='.$id.'">Open</a>',
    ];
}

echo json_encode(['data' => $data]);


