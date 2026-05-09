<?php
if (ob_get_level()) ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
$userModel = new User($pdo);
require_login(); require_role([1]);

$draw   = $_POST['draw'] ?? 1;
$start  = $_POST['start'] ?? 0;
$length = $_POST['length'] ?? 10;
$search = $_POST['search']['value'] ?? '';

$where = ""; $params = [];
if($search){
    $where = " WHERE 
        CONCAT(u.first_name,' ',u.last_name) LIKE :q OR
        t.leave_name LIKE :q OR
        a.status LIKE :q
    ";
    $params[':q'] = "%$search%";
}

$total = $pdo->query("SELECT COUNT(*) FROM leave_applications")->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM leave_applications a
    LEFT JOIN leave_types t ON t.leave_type_id=a.leave_type_id
    LEFT JOIN sdopang1_user u ON u.user_id=a.user_id
    $where
");
$stmt->execute($params);
$filtered = $stmt->fetchColumn();

$sql = "
SELECT 
    a.application_id,
    a.user_id,   -- 🔥 ADD THIS (IMPORTANT)
    a.date_from, 
    a.date_to, 
    a.days, 
    a.status,
    t.leave_name,
    CONCAT(u.first_name,' ',u.last_name) AS employee
FROM leave_applications a
LEFT JOIN leave_types t ON t.leave_type_id=a.leave_type_id
LEFT JOIN sdopang1_user u ON u.user_id=a.user_id
$where
ORDER BY a.created_at DESC
LIMIT :len OFFSET :st
";

$stmt = $pdo->prepare($sql);
foreach($params as $k=>$v) $stmt->bindValue($k,$v);
$stmt->bindValue(':st',(int)$start,PDO::PARAM_INT);
$stmt->bindValue(':len',(int)$length,PDO::PARAM_INT);
$stmt->execute();

$data=[];
while($r=$stmt->fetch(PDO::FETCH_ASSOC)){
    $badge = $r['status']=='approved'
        ? '<span class="badge bg-success">Approved</span>'
        : ($r['status']=='rejected'
            ? '<span class="badge bg-danger">Rejected</span>'
            : '<span class="badge bg-warning text-dark">Pending</span>');

    $data[]=[
        "employee"=>$r['employee'],
        "leave_name"=>$r['leave_name'],
        "date_from"=>$r['date_from'],
        "date_to"=>$r['date_to'],
        "days"=>$r['days'],
        "status_badge"=>$badge,
        "action"=>'
    <button class="btn btn-info btn-sm openModal" 
        data-id="'.$r['application_id'].'" 
        data-action="View">View</button>

    <a href="admin_leave_employee_view.php?user_id='.$r['user_id'].'" 
       class="btn btn-primary btn-sm">
       View Card
    </a>

    '.($r['status']=='pending' ? '

    <button class="btn btn-success btn-sm btnApprove" 
        data-id="'.$r['application_id'].'">Approve</button>

    <button class="btn btn-danger btn-sm btnReject" 
        data-id="'.$r['application_id'].'">Reject</button>

    ' : '').'
'
    ];
}

echo json_encode([
    "draw"=>(int)$draw,
    "recordsTotal"=>(int)$total,
    "recordsFiltered"=>(int)$filtered,
    "data"=>$data
]);