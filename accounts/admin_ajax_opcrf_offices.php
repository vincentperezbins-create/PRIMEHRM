<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

require_login();
require_role([1]);

header('Content-Type: application/json');

$stmt = $pdo->query("
    SELECT o.*, p.office_name AS parent_office_name, s.schoolname,
           TRIM(CONCAT(u.first_name, ' ', u.last_name)) AS office_head_name,
           TRIM(CONCAT(uh.first_name, ' ', uh.last_name)) AS unit_head_name
    FROM sdopang1_offices o
    LEFT JOIN sdopang1_user u ON u.user_id = o.office_head
    LEFT JOIN sdopang1_user uh ON uh.user_id = o.unit_head
    LEFT JOIN sdopang1_offices p ON p.office_id = o.parent_office_id
    LEFT JOIN sdopang1schoollist s ON s.schoolID = o.school_id
    ORDER BY o.office_name
");

$data = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $id = (int) $row['office_id'];
    $data[] = [
        'office_name' => $row['office_name'],
        'office_type' => trim(($row['office_category'] ?: 'Division Office') . ' / ' . ($row['office_type'] ?: '-')),
        'office_head_name' => $row['office_head_name'] ?: '-',
        'unit_head_name' => $row['unit_head_name'] ?: '-',
        'status' => $row['status'],
        'action' => '
            <button class="btn btn-warning btn-sm openModal" data-action="Update" data-id="'.$id.'">Update</button>
            <button class="btn btn-danger btn-sm btnDeleteOffice" data-id="'.$id.'">Delete</button>
        ',
    ];
}

echo json_encode(['data' => $data]);
