<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

require_login();
require_role([1, 2, 3, 5, 6, 7]);

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', '0');

function scoped_user_json(array $payload): void {
    echo json_encode($payload);
    exit;
}

function scoped_user_h($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$draw = (int) ($_POST['draw'] ?? 0);
$start = max(0, (int) ($_POST['start'] ?? 0));
$length = (int) ($_POST['length'] ?? 10);
$length = $length > 0 && $length <= 100 ? $length : 10;
$search = trim((string) ($_POST['search']['value'] ?? ''));

$currentStmt = $pdo->prepare("
    SELECT user_id, role_id, school_id, division_unit_id, office_unit_id
    FROM sdopang1_user
    WHERE user_id = ?
    LIMIT 1
");
$currentStmt->execute([$_SESSION['user_id'] ?? 0]);
$currentAccount = $currentStmt->fetch(PDO::FETCH_ASSOC) ?: [];

$roleId = (int) ($currentAccount['role_id'] ?? $_SESSION['role_id'] ?? 0);
$isAdmin = $roleId === 1;
$isSchoolScope = $roleId === 3;
$canManage = $isSchoolScope;

$scopeConditions = [];
$scopeParams = [];
if ($isSchoolScope) {
    $scopeConditions[] = 'u.school_id = :scope_school_id';
    $scopeParams[':scope_school_id'] = (string) ($currentAccount['school_id'] ?? '');
} elseif (!$isAdmin) {
    $officeUnitId = (int) ($currentAccount['office_unit_id'] ?? 0);
    $divisionUnitId = (int) ($currentAccount['division_unit_id'] ?? 0);
    if ($officeUnitId > 0 || $divisionUnitId > 0) {
        $scopeConditions[] = '((:scope_office_unit_id > 0 AND u.office_unit_id = :scope_office_unit_id_match) OR (:scope_division_unit_id > 0 AND u.division_unit_id = :scope_division_unit_id_match))';
        $scopeParams[':scope_office_unit_id'] = $officeUnitId;
        $scopeParams[':scope_office_unit_id_match'] = $officeUnitId;
        $scopeParams[':scope_division_unit_id'] = $divisionUnitId;
        $scopeParams[':scope_division_unit_id_match'] = $divisionUnitId;
    } else {
        $scopeConditions[] = '0 = 1';
    }
}

$searchConditions = [];
$searchParams = [];
if ($search !== '') {
    $searchConditions[] = "(
        u.user_id LIKE :s0 OR
        u.employeeID LIKE :s1 OR
        u.first_name LIKE :s2 OR
        u.middle_name LIKE :s3 OR
        u.last_name LIKE :s4 OR
        CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name) LIKE :s5 OR
        u.email LIKE :s6 OR
        u.school_id LIKE :s7 OR
        s.schoolname LIKE :s8 OR
        r.role_name LIKE :s9 OR
        du.unit_name LIKE :s10 OR
        ou.unit_name LIKE :s11
    )";
    for ($i = 0; $i <= 11; $i++) {
        $searchParams[":s$i"] = "%$search%";
    }
}

$whereParts = array_merge($scopeConditions, $searchConditions);
$whereSql = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';
$scopeWhereSql = $scopeConditions ? 'WHERE ' . implode(' AND ', $scopeConditions) : '';

$baseFrom = "
    FROM sdopang1_user u
    LEFT JOIN sdopang1schoollist s ON s.schoolID = u.school_id
    LEFT JOIN sdopang1_roles r ON r.role_id = u.role_id
    LEFT JOIN division_units du ON du.division_unit_id = u.division_unit_id
    LEFT JOIN office_units ou ON ou.office_unit_id = u.office_unit_id
";

$totalStmt = $pdo->prepare("SELECT COUNT(*) $baseFrom $scopeWhereSql");
$totalStmt->execute($scopeParams);
$recordsTotal = (int) $totalStmt->fetchColumn();

$filteredStmt = $pdo->prepare("SELECT COUNT(*) $baseFrom $whereSql");
$filteredStmt->execute(array_merge($scopeParams, $searchParams));
$recordsFiltered = (int) $filteredStmt->fetchColumn();

$orderColumnIndex = (int) ($_POST['order'][0]['column'] ?? 2);
$orderDirection = strtolower((string) ($_POST['order'][0]['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';
$orderColumns = [
    0 => 'u.user_id',
    1 => 'u.school_id',
    2 => 'u.last_name',
    3 => 's.schoolname',
    4 => 'r.role_name',
    5 => 'du.unit_name',
    6 => 'ou.unit_name',
];
$orderBy = $orderColumns[$orderColumnIndex] ?? 'u.last_name';

$sql = "
    SELECT
        u.user_id,
        u.employeeID,
        u.first_name,
        u.middle_name,
        u.last_name,
        u.email,
        u.status,
        u.school_id,
        COALESCE(s.schoolname, '') AS school_name,
        COALESCE(r.role_name, 'Employee') AS role_name,
        COALESCE(du.unit_name, u.division_unit, 'School') AS division_unit,
        COALESCE(ou.unit_name, '') AS office_unit
    $baseFrom
    $whereSql
    ORDER BY $orderBy $orderDirection, u.first_name ASC
    LIMIT :length OFFSET :start
";

$stmt = $pdo->prepare($sql);
foreach (array_merge($scopeParams, $searchParams) as $key => $value) {
    $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->bindValue(':length', $length, PDO::PARAM_INT);
$stmt->bindValue(':start', $start, PDO::PARAM_INT);
$stmt->execute();

$data = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $fullName = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['middle_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
    $attrs = [
        'data-user-id' => $row['user_id'],
        'data-employee-id' => $row['employeeID'],
        'data-employee-name' => $fullName,
        'data-first-name' => $row['first_name'],
        'data-middle-name' => $row['middle_name'],
        'data-last-name' => $row['last_name'],
        'data-email' => $row['email'],
        'data-status' => $row['status'] ?: 'active',
        'data-school-id' => $row['school_id'],
        'data-school-name' => $row['school_name'],
        'data-role-name' => $row['role_name'],
        'data-division-unit' => $row['division_unit'],
        'data-office-unit' => $row['office_unit'],
    ];
    $attrHtml = '';
    foreach ($attrs as $name => $value) {
        $attrHtml .= ' ' . $name . '="' . scoped_user_h($value) . '"';
    }

    $action = '<div class="btn-group btn-group-sm" role="group">';
    $action .= '<button type="button" class="btn btn-info btnViewUser"' . $attrHtml . '>View</button>';
    if ($canManage) {
        $action .= '<button type="button" class="btn btn-warning btnUpdateUser"' . $attrHtml . '>Update</button>';
        $action .= '<button type="button" class="btn btn-danger btnDeleteUser" data-user-id="' . scoped_user_h($row['user_id']) . '">Delete</button>';
    }
    $action .= '</div>';

    $data[] = [
        'user_id' => scoped_user_h($row['user_id']),
        'school_id' => scoped_user_h($row['school_id']),
        'employee_name' => scoped_user_h($fullName),
        'school_name' => scoped_user_h($row['school_name']),
        'role_name' => scoped_user_h($row['role_name']),
        'division_unit' => scoped_user_h($row['division_unit']),
        'office_unit' => scoped_user_h($row['office_unit']),
        'action' => $action,
    ];
}

scoped_user_json([
    'draw' => $draw,
    'recordsTotal' => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data' => $data,
]);
