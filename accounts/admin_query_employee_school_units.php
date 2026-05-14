<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/csrf.php';
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
    SELECT u.user_id, u.role_id, u.school_id, u.division_unit_id, u.office_unit_id,
           CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) AS fullname,
           s.schoolname,
           du.unit_name AS division_unit_name,
           ou.unit_name AS office_unit_name
    FROM sdopang1_user u
    LEFT JOIN sdopang1schoollist s ON s.schoolID = u.school_id
    LEFT JOIN division_units du ON du.division_unit_id = u.division_unit_id
    LEFT JOIN office_units ou ON ou.office_unit_id = u.office_unit_id
    WHERE u.user_id = ?
    LIMIT 1
");
$scopeStmt->execute([$_SESSION['user_id']]);
$currentAccount = $scopeStmt->fetch(PDO::FETCH_ASSOC) ?: [];
$roleId = (int) ($currentAccount['role_id'] ?? $_SESSION['role_id'] ?? 0);
$isAdmin = $roleId === 1;
$isSchoolScope = $roleId === 3;

if (!verifyToken($_POST['token'] ?? '')) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request token. Please reload the page and try again.']);
    exit;
}

$action = $_POST['action'] ?? '';
$allowedRoles = ['Employee', 'Unit Head'];

function assignment_payload(array $source, array $allowedRoles): array
{
    $payload = [
        'school_id' => trim((string) ($source['school_id'] ?? '')),
        'employee_name' => trim((string) ($source['employee_name'] ?? '')),
        'school_name' => trim((string) ($source['school_name'] ?? '')),
        'functional_division_unit_name' => trim((string) ($source['functional_division_unit_name'] ?? '')),
        'unit_head_name' => trim((string) ($source['unit_head_name'] ?? '')),
        'assigned_role' => trim((string) ($source['assigned_role'] ?? 'Employee')),
    ];

    foreach ($payload as $key => $value) {
        if ($value === '') {
            throw new InvalidArgumentException('Please complete all required fields.');
        }
    }

    if (!in_array($payload['assigned_role'], $allowedRoles, true)) {
        throw new InvalidArgumentException('Invalid role selection.');
    }

    return $payload;
}

function assignment_scope(PDO $pdo, array $currentAccount, bool $isAdmin, bool $isSchoolScope): array
{
    if ($isAdmin) {
        $schoolId = trim((string) ($_POST['school_id'] ?? ''));
        return [
            'scope_type' => $schoolId !== '' && strtoupper($schoolId) !== 'DIVISION' ? 'school' : 'admin',
            'scope_school_id' => $schoolId !== '' && strtoupper($schoolId) !== 'DIVISION' ? $schoolId : null,
            'scope_division_unit_id' => null,
            'scope_office_unit_id' => null,
        ];
    }

    if ($isSchoolScope) {
        return [
            'scope_type' => 'school',
            'scope_school_id' => (string) ($currentAccount['school_id'] ?? ''),
            'scope_division_unit_id' => null,
            'scope_office_unit_id' => null,
        ];
    }

    $officeUnitId = (int) ($currentAccount['office_unit_id'] ?? 0);
    $divisionUnitId = (int) ($currentAccount['division_unit_id'] ?? 0);

    return [
        'scope_type' => $officeUnitId > 0 ? 'office_unit' : 'division_unit',
        'scope_school_id' => null,
        'scope_division_unit_id' => $divisionUnitId ?: null,
        'scope_office_unit_id' => $officeUnitId ?: null,
    ];
}

function apply_scope_to_payload(array $payload, array $currentAccount, bool $isAdmin, bool $isSchoolScope): array
{
    if ($isAdmin) {
        return $payload;
    }

    if ($isSchoolScope) {
        $payload['school_id'] = (string) ($currentAccount['school_id'] ?? '');
        $payload['school_name'] = (string) ($currentAccount['schoolname'] ?? '');
        $payload['functional_division_unit_name'] = $payload['functional_division_unit_name'] ?: 'School Personnel';
        return $payload;
    }

    $payload['school_id'] = 'DIVISION';
    $payload['school_name'] = 'Schools Division Office 1 Pangasinan';
    $payload['functional_division_unit_name'] = (string) (($currentAccount['office_unit_name'] ?? '') ?: ($currentAccount['division_unit_name'] ?? $payload['functional_division_unit_name']));
    return $payload;
}

function can_access_assignment(PDO $pdo, int $assignmentId, array $currentAccount, bool $isAdmin, bool $isSchoolScope): bool
{
    if ($isAdmin) {
        return true;
    }

    $stmt = $pdo->prepare("
        SELECT scope_type, scope_school_id, scope_division_unit_id, scope_office_unit_id
        FROM employee_school_unit_assignments
        WHERE assignment_id = ?
        LIMIT 1
    ");
    $stmt->execute([$assignmentId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return false;
    }

    if ($isSchoolScope) {
        return $row['scope_type'] === 'school' && (string) $row['scope_school_id'] === (string) ($currentAccount['school_id'] ?? '');
    }

    $officeUnitId = (int) ($currentAccount['office_unit_id'] ?? 0);
    $divisionUnitId = (int) ($currentAccount['division_unit_id'] ?? 0);
    return (
        $row['scope_type'] === 'office_unit' && (int) $row['scope_office_unit_id'] === $officeUnitId
    ) || (
        $row['scope_type'] === 'division_unit' && (int) $row['scope_division_unit_id'] === $divisionUnitId
    );
}

try {
    if ($action === 'add') {
        $payload = apply_scope_to_payload(assignment_payload($_POST, $allowedRoles), $currentAccount, $isAdmin, $isSchoolScope);
        $scope = assignment_scope($pdo, $currentAccount, $isAdmin, $isSchoolScope);
        $stmt = $pdo->prepare("
            INSERT INTO employee_school_unit_assignments
                (school_id, employee_name, school_name, functional_division_unit_name, unit_head_name, assigned_role, scope_type, scope_school_id, scope_division_unit_id, scope_office_unit_id, created_by)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $payload['school_id'],
            $payload['employee_name'],
            $payload['school_name'],
            $payload['functional_division_unit_name'],
            $payload['unit_head_name'],
            $payload['assigned_role'],
            $scope['scope_type'],
            $scope['scope_school_id'],
            $scope['scope_division_unit_id'],
            $scope['scope_office_unit_id'],
            (int) ($_SESSION['user_id'] ?? 0),
        ]);
        echo json_encode(['status' => 'success']);
        exit;
    }

    if ($action === 'update') {
        $assignmentId = filter_input(INPUT_POST, 'assignment_id', FILTER_VALIDATE_INT);
        if (!$assignmentId) {
            throw new InvalidArgumentException('Assignment record is required.');
        }
        if (!can_access_assignment($pdo, $assignmentId, $currentAccount, $isAdmin, $isSchoolScope)) {
            throw new RuntimeException('You are not allowed to update this assignment.');
        }

        $payload = apply_scope_to_payload(assignment_payload($_POST, $allowedRoles), $currentAccount, $isAdmin, $isSchoolScope);
        $scope = assignment_scope($pdo, $currentAccount, $isAdmin, $isSchoolScope);
        $stmt = $pdo->prepare("
            UPDATE employee_school_unit_assignments
            SET school_id = ?,
                employee_name = ?,
                school_name = ?,
                functional_division_unit_name = ?,
                unit_head_name = ?,
                assigned_role = ?,
                scope_type = ?,
                scope_school_id = ?,
                scope_division_unit_id = ?,
                scope_office_unit_id = ?
            WHERE assignment_id = ?
        ");
        $stmt->execute([
            $payload['school_id'],
            $payload['employee_name'],
            $payload['school_name'],
            $payload['functional_division_unit_name'],
            $payload['unit_head_name'],
            $payload['assigned_role'],
            $scope['scope_type'],
            $scope['scope_school_id'],
            $scope['scope_division_unit_id'],
            $scope['scope_office_unit_id'],
            $assignmentId,
        ]);
        echo json_encode(['status' => 'success']);
        exit;
    }

    if ($action === 'delete') {
        $assignmentId = filter_input(INPUT_POST, 'assignment_id', FILTER_VALIDATE_INT);
        if (!$assignmentId) {
            throw new InvalidArgumentException('Assignment record is required.');
        }
        if (!can_access_assignment($pdo, $assignmentId, $currentAccount, $isAdmin, $isSchoolScope)) {
            throw new RuntimeException('You are not allowed to delete this assignment.');
        }

        $stmt = $pdo->prepare("DELETE FROM employee_school_unit_assignments WHERE assignment_id = ?");
        $stmt->execute([$assignmentId]);
        echo json_encode(['status' => 'success']);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
} catch (Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
