<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
header('Content-Type: application/json');

require_login();
require_role([1, 2, 3, 5, 6, 7]);

$pdo->exec("
    CREATE TABLE IF NOT EXISTS employee_school_unit_assignments (
        assignment_id INT AUTO_INCREMENT PRIMARY KEY,
        school_id VARCHAR(50) NOT NULL,
        employee_name VARCHAR(255) NOT NULL,
        school_name VARCHAR(255) NOT NULL,
        functional_division_unit_name VARCHAR(255) NOT NULL,
        unit_head_name VARCHAR(255) NOT NULL,
        assigned_role VARCHAR(80) NOT NULL DEFAULT 'Employee',
        scope_type ENUM('admin','school','division_unit','office_unit') NOT NULL DEFAULT 'admin',
        scope_school_id VARCHAR(50) NULL,
        scope_division_unit_id INT NULL,
        scope_office_unit_id INT NULL,
        created_by INT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_school_id (school_id),
        INDEX idx_employee_name (employee_name),
        INDEX idx_unit_head_name (unit_head_name),
        INDEX idx_assigned_role (assigned_role)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$columns = $pdo->query("SHOW COLUMNS FROM employee_school_unit_assignments")->fetchAll(PDO::FETCH_COLUMN);
$columnSql = [
    'scope_type' => "ALTER TABLE employee_school_unit_assignments ADD COLUMN scope_type ENUM('admin','school','division_unit','office_unit') NOT NULL DEFAULT 'admin' AFTER assigned_role",
    'scope_school_id' => "ALTER TABLE employee_school_unit_assignments ADD COLUMN scope_school_id VARCHAR(50) NULL AFTER scope_type",
    'scope_division_unit_id' => "ALTER TABLE employee_school_unit_assignments ADD COLUMN scope_division_unit_id INT NULL AFTER scope_school_id",
    'scope_office_unit_id' => "ALTER TABLE employee_school_unit_assignments ADD COLUMN scope_office_unit_id INT NULL AFTER scope_division_unit_id",
    'created_by' => "ALTER TABLE employee_school_unit_assignments ADD COLUMN created_by INT NULL AFTER scope_office_unit_id",
];
foreach ($columnSql as $column => $sql) {
    if (!in_array($column, $columns, true)) {
        $pdo->exec($sql);
    }
}

$scopeStmt = $pdo->prepare("
    SELECT user_id, role_id, school_id, division_unit_id, office_unit_id
    FROM sdopang1_user
    WHERE user_id = ?
    LIMIT 1
");
$scopeStmt->execute([$_SESSION['user_id']]);
$currentAccount = $scopeStmt->fetch(PDO::FETCH_ASSOC) ?: [];
$roleId = (int) ($currentAccount['role_id'] ?? $_SESSION['role_id'] ?? 0);
$isAdmin = $roleId === 1;
$isSchoolScope = $roleId === 3;
$isUnitScope = !$isAdmin && !$isSchoolScope;

$draw = (int) ($_POST['draw'] ?? 0);
$start = max(0, (int) ($_POST['start'] ?? 0));
$length = (int) ($_POST['length'] ?? 10);
$length = $length > 0 ? min($length, 100) : 10;
$search = trim((string) ($_POST['search']['value'] ?? ''));

$columns = [
    'assignment_id',
    'school_id',
    'employee_name',
    'school_name',
    'functional_division_unit_name',
    'unit_head_name',
    'assigned_role',
];

$orderIndex = (int) ($_POST['order'][0]['column'] ?? 0);
$orderColumn = $columns[$orderIndex] ?? 'assignment_id';
$orderDir = strtolower((string) ($_POST['order'][0]['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';

$where = '';
$params = [];
$conditions = [];

if ($isSchoolScope) {
    $conditions[] = "scope_type = 'school' AND scope_school_id = :scope_school_id";
    $params[':scope_school_id'] = (string) ($currentAccount['school_id'] ?? '');
} elseif ($isUnitScope) {
    $conditions[] = "(
        (scope_type = 'office_unit' AND scope_office_unit_id = :scope_office_unit_id)
        OR (scope_type = 'division_unit' AND scope_division_unit_id = :scope_division_unit_id)
    )";
    $params[':scope_office_unit_id'] = (int) ($currentAccount['office_unit_id'] ?? 0);
    $params[':scope_division_unit_id'] = (int) ($currentAccount['division_unit_id'] ?? 0);
}

if ($search !== '') {
    $conditions[] = "(school_id LIKE :search
        OR employee_name LIKE :search
        OR school_name LIKE :search
        OR functional_division_unit_name LIKE :search
        OR unit_head_name LIKE :search
        OR assigned_role LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$recordsStmt = $pdo->prepare("SELECT COUNT(*) FROM employee_school_unit_assignments $where");
foreach ($params as $key => $value) {
    $recordsStmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$recordsStmt->execute();
$recordsTotal = (int) $recordsStmt->fetchColumn();

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM employee_school_unit_assignments $where");
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$countStmt->execute();
$recordsFiltered = (int) $countStmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT assignment_id, school_id, employee_name, school_name, functional_division_unit_name, unit_head_name, assigned_role
    FROM employee_school_unit_assignments
    $where
    ORDER BY $orderColumn $orderDir
    LIMIT :start, :length
");
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->bindValue(':start', $start, PDO::PARAM_INT);
$stmt->bindValue(':length', $length, PDO::PARAM_INT);
$stmt->execute();

$rows = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $attrs = [
        'id' => $row['assignment_id'],
        'school-id' => $row['school_id'],
        'employee-name' => $row['employee_name'],
        'school-name' => $row['school_name'],
        'division-unit' => $row['functional_division_unit_name'],
        'unit-head' => $row['unit_head_name'],
        'assigned-role' => $row['assigned_role'],
    ];
    $attrText = '';
    foreach ($attrs as $key => $value) {
        $attrText .= ' data-' . $key . '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"';
    }

    $row['assignment_id'] = htmlspecialchars((string) $row['assignment_id'], ENT_QUOTES, 'UTF-8');
    $row['school_id'] = htmlspecialchars((string) $row['school_id'], ENT_QUOTES, 'UTF-8');
    $row['employee_name'] = htmlspecialchars((string) $row['employee_name'], ENT_QUOTES, 'UTF-8');
    $row['school_name'] = htmlspecialchars((string) $row['school_name'], ENT_QUOTES, 'UTF-8');
    $row['functional_division_unit_name'] = htmlspecialchars((string) $row['functional_division_unit_name'], ENT_QUOTES, 'UTF-8');
    $row['unit_head_name'] = htmlspecialchars((string) $row['unit_head_name'], ENT_QUOTES, 'UTF-8');
    $row['assigned_role'] = htmlspecialchars((string) $row['assigned_role'], ENT_QUOTES, 'UTF-8');
    $row['action'] = '<div class="btn-group btn-group-sm" role="group">'
        . '<button type="button" class="btn btn-outline-info btnViewAssignment"' . $attrText . '>View</button>'
        . '<button type="button" class="btn btn-outline-primary btnUpdateAssignment"' . $attrText . '>Update</button>'
        . '<button type="button" class="btn btn-outline-danger btnDeleteAssignment" data-id="' . htmlspecialchars((string) $attrs['id'], ENT_QUOTES, 'UTF-8') . '">Delete</button>'
        . '</div>';
    $rows[] = $row;
}

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data' => $rows,
]);
