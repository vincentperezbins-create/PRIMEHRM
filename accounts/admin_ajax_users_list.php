<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

require_login();
require_role([1]);

header('Content-Type: application/json');

// 🔒 prevent warnings breaking JSON
error_reporting(0);
ini_set('display_errors', 0);

// 📥 DataTables params
$draw   = $_POST['draw'] ?? 0;
$start  = $_POST['start'] ?? 0;
$length = $_POST['length'] ?? 10;
$search = $_POST['search']['value'] ?? '';
$personnelType = trim($_POST['personnel_type'] ?? '');
$divisionUnitId = filter_input(INPUT_POST, 'division_unit_id', FILTER_VALIDATE_INT);
$schoolId = trim($_POST['school_id'] ?? '');
$officeUnitId = filter_input(INPUT_POST, 'office_unit_id', FILTER_VALIDATE_INT);
$roleId = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);

// 🔍 SEARCH
$where = "";
$params = [];
$conditions = [];

if (!empty($search)) {

    $conditions[] = "( 
        u.last_name LIKE :s1 OR
        u.middle_name LIKE :s2 OR
        u.first_name LIKE :s3 OR
        CONCAT(u.first_name, ' ', u.last_name) LIKE :s4 OR
        CONCAT(u.first_name, ' ', u.middle_name, ' ', u.last_name) LIKE :s5 OR
        CONCAT(u.last_name, ', ', u.first_name, ' ', u.middle_name) LIKE :s6 OR
        u.tin LIKE :s7 OR
        u.user_id LIKE :s8 OR
        u.prc_license_number LIKE :s9 OR
        u.employeeID LIKE :s10 OR
        u.email LIKE :s11 OR
        u.division_unit LIKE :s12 OR
        du.unit_name LIKE :s13 OR
        ou.unit_name LIKE :s14 OR
        s.schoolname LIKE :s15 OR
        p.position_title LIKE :s16 OR
        p.position_category LIKE :s17 OR
        r.role_name LIKE :s18
    )";

    for ($i = 1; $i <= 18; $i++) {
        $params[":s$i"] = "%$search%";
    }
}

if (in_array($personnelType, ['Teaching', 'Non-Teaching'], true)) {
    $conditions[] = "p.position_category = :personnel_type";
    $params[':personnel_type'] = $personnelType;
}

if ($divisionUnitId) {
    $conditions[] = "u.division_unit_id = :division_unit_id";
    $params[':division_unit_id'] = $divisionUnitId;
}

if ($schoolId !== '') {
    $conditions[] = "u.school_id = :school_id";
    $params[':school_id'] = $schoolId;
}

if ($officeUnitId) {
    $conditions[] = "u.office_unit_id = :office_unit_id";
    $params[':office_unit_id'] = $officeUnitId;
}

if ($roleId) {
    $conditions[] = "u.role_id = :role_id";
    $params[':role_id'] = $roleId;
}

if ($conditions) {
    $where = " WHERE " . implode(" AND ", $conditions);
}

// 🔢 TOTAL RECORDS
$total = $pdo->query("SELECT COUNT(*) FROM sdopang1_user")->fetchColumn();

// 🔢 FILTERED RECORDS
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM sdopang1_user u
    LEFT JOIN sdopang1_position p ON p.position_id = u.position_id
    LEFT JOIN sdopang1schoollist s ON s.schoolID = u.school_id
    LEFT JOIN sdopang1_offices o ON o.office_id = u.office_id
    LEFT JOIN sdopang1_roles r ON r.role_id = u.role_id
    LEFT JOIN division_units du ON du.division_unit_id = u.division_unit_id
    LEFT JOIN office_units ou ON ou.office_unit_id = u.office_unit_id
    $where
");
$stmt->execute($params);
$filtered = $stmt->fetchColumn();

// 📄 MAIN QUERY
$sql = "
SELECT 
    u.user_id,
    u.first_name,
    u.middle_name,
    u.last_name,
    u.school_id,
    u.employeeID,
    u.email,
    u.tin,
    u.prc_license_number,
    COALESCE(du.unit_name, u.division_unit, 'School') AS division_unit,
    COALESCE(r.role_name, 'Employee') AS role_name,

    s.schoolname,
    p.position_title,
    COALESCE(ou.unit_name, o.office_name) AS office_name

FROM sdopang1_user u
LEFT JOIN sdopang1_position p ON p.position_id = u.position_id
LEFT JOIN sdopang1schoollist s ON s.schoolID = u.school_id
LEFT JOIN sdopang1_offices o ON o.office_id = u.office_id
LEFT JOIN sdopang1_roles r ON r.role_id = u.role_id
LEFT JOIN division_units du ON du.division_unit_id = u.division_unit_id
LEFT JOIN office_units ou ON ou.office_unit_id = u.office_unit_id

$where

ORDER BY u.first_name ASC
LIMIT :length OFFSET :start
";

$stmt = $pdo->prepare($sql);

// 🔗 bind search
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v, PDO::PARAM_STR);
}

// 🔗 bind pagination
$stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
$stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);

$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 🎯 FORMAT OUTPUT
$output = [];

foreach ($data as $row) {

    $middle = !empty($row['middle_name']) ? $row['middle_name'] . ' ' : '';
    $name = $row['first_name'] . ' ' . $middle . $row['last_name'];

    $output[] = [
        "name" => $name,
        "school_id" => $row['school_id'],
        "schoolname" => $row['schoolname'],
        "position_title" => $row['position_title'],
        "role_name" => $row['role_name'],
        "division_unit" => $row['division_unit'],
        "office_name" => $row['office_name'],
        "action" => '
            <div class="prime-actions">
                <button class="btn btn-sm btn-view openModal" data-id="'.$row['user_id'].'" data-action="View"><i class="bi bi-eye"></i> View</button>
                <button class="btn btn-sm btn-update openModal" data-id="'.$row['user_id'].'" data-action="Update"><i class="bi bi-pencil-square"></i> Update</button>
                <button class="btn btn-sm btn-delete btnDelete" data-id="'.$row['user_id'].'"><i class="bi bi-trash"></i> Delete</button>
            </div>
        '
    ];
}

// 📦 RESPONSE
echo json_encode([
    "draw" => intval($draw),
    "recordsTotal" => intval($total),
    "recordsFiltered" => intval($filtered),
    "data" => $output
]);
exit;
