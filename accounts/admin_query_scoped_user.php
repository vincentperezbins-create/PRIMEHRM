<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/core/audit.php';

require_login();
require_role([1, 3]);

header('Content-Type: application/json');

function scoped_user_response(string $status, string $message, array $extra = []): void {
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    scoped_user_response('error', 'Invalid request method.');
}

if (!isset($_POST['token']) || !verifyToken((string) $_POST['token'])) {
    scoped_user_response('error', 'Invalid security token. Please refresh the page and try again.');
}

$currentStmt = $pdo->prepare("
    SELECT user_id, role_id, school_id
    FROM sdopang1_user
    WHERE user_id = ?
    LIMIT 1
");
$currentStmt->execute([$_SESSION['user_id'] ?? 0]);
$currentAccount = $currentStmt->fetch(PDO::FETCH_ASSOC) ?: [];

$roleId = (int) ($currentAccount['role_id'] ?? $_SESSION['role_id'] ?? 0);
$isAdmin = $roleId === 1;
$isSchoolScope = $roleId === 3;
$schoolId = $isSchoolScope ? trim((string) ($currentAccount['school_id'] ?? '')) : trim((string) ($_POST['school_id'] ?? ''));

if (!$isAdmin && !$isSchoolScope) {
    scoped_user_response('error', 'You are not allowed to manage school employees.');
}

if ($isSchoolScope && ($schoolId === '' || strtoupper($schoolId) === 'NONE')) {
    scoped_user_response('error', 'Your account has no valid school ID. Please contact the administrator.');
}

$action = strtolower(trim((string) ($_POST['action'] ?? '')));
$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT) ?: 0;

function scoped_user_assert_school_access(PDO $pdo, int $userId, string $schoolId, bool $isAdmin): array {
    $stmt = $pdo->prepare("
        SELECT user_id, school_id
        FROM sdopang1_user
        WHERE user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        scoped_user_response('error', 'Employee record was not found.');
    }
    if (!$isAdmin && (string) ($user['school_id'] ?? '') !== $schoolId) {
        scoped_user_response('error', 'This employee does not belong to your assigned school.');
    }
    return $user;
}

if ($action === 'delete') {
    if ($userId <= 0) {
        scoped_user_response('error', 'Invalid employee record.');
    }
    if ($userId === (int) ($_SESSION['user_id'] ?? 0)) {
        scoped_user_response('error', 'You cannot delete your own account.');
    }

    scoped_user_assert_school_access($pdo, $userId, $schoolId, $isAdmin);
    $stmt = $pdo->prepare("DELETE FROM sdopang1_user WHERE user_id = ?");
    $stmt->execute([$userId]);
    audit_log($pdo, $_SESSION['user_id'] ?? null, audit_current_fullname($pdo), 'DELETE', 'School Employees', $userId, 'Deleted a school employee user account.');
    scoped_user_response('success', 'Employee deleted successfully.');
}

if (!in_array($action, ['add', 'update'], true)) {
    scoped_user_response('error', 'Invalid action.');
}

$firstName = trim((string) ($_POST['first_name'] ?? ''));
$middleName = trim((string) ($_POST['middle_name'] ?? ''));
$lastName = trim((string) ($_POST['last_name'] ?? ''));
$employeeId = trim((string) ($_POST['employeeID'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$status = in_array((string) ($_POST['status'] ?? 'active'), ['active', 'inactive'], true) ? (string) $_POST['status'] : 'active';

if ($firstName === '' || $lastName === '') {
    scoped_user_response('error', 'First name and last name are required.');
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    scoped_user_response('error', 'A valid email address is required.');
}

if ($action === 'add' && trim($password) === '') {
    scoped_user_response('error', 'Password is required for new employees.');
}

if ($action === 'update') {
    if ($userId <= 0) {
        scoped_user_response('error', 'Invalid employee record.');
    }
    scoped_user_assert_school_access($pdo, $userId, $schoolId, $isAdmin);
}

$emailStmt = $pdo->prepare("
    SELECT user_id
    FROM sdopang1_user
    WHERE email = ? AND user_id <> ?
    LIMIT 1
");
$emailStmt->execute([$email, $userId]);
if ($emailStmt->fetchColumn()) {
    scoped_user_response('error', 'Email address is already used by another account.');
}

try {
    if ($action === 'add') {
        $stmt = $pdo->prepare("
            INSERT INTO sdopang1_user
                (first_name, middle_name, last_name, employeeID, email, username, password, role_id, status, school_id, division_unit, date_added)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, 4, ?, ?, 'School', NOW())
        ");
        $stmt->execute([
            $firstName,
            $middleName,
            $lastName,
            $employeeId,
            $email,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $status,
            $schoolId,
        ]);
        $newId = (int) $pdo->lastInsertId();
        audit_log($pdo, $_SESSION['user_id'] ?? null, audit_current_fullname($pdo), 'CREATE', 'School Employees', $newId, 'Created a school employee user account.');
        scoped_user_response('success', 'Employee added successfully.', ['user_id' => $newId]);
    }

    $params = [
        $firstName,
        $middleName,
        $lastName,
        $employeeId,
        $email,
        $email,
        $status,
        $schoolId,
    ];
    $passwordSql = '';
    if (trim($password) !== '') {
        $passwordSql = ', password = ?';
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }
    $params[] = $userId;

    $stmt = $pdo->prepare("
        UPDATE sdopang1_user
        SET first_name = ?,
            middle_name = ?,
            last_name = ?,
            employeeID = ?,
            email = ?,
            username = ?,
            status = ?,
            school_id = ?,
            role_id = 4,
            division_unit = 'School'
            $passwordSql
        WHERE user_id = ?
    ");
    $stmt->execute($params);
    audit_log($pdo, $_SESSION['user_id'] ?? null, audit_current_fullname($pdo), 'UPDATE', 'School Employees', $userId, 'Updated a school employee user account.');
    scoped_user_response('success', 'Employee updated successfully.');
} catch (Throwable $e) {
    error_log('School employee save failed: ' . $e->getMessage());
    scoped_user_response('error', 'Unable to save employee. Please check the required fields and try again.');
}
