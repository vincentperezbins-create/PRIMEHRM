<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/csrf.php';


require_login();
require_role([1]);



// METHOD CHECK
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request");
}

// CSRF CHECK
if (!isset($_POST['token']) || !verifyToken($_POST['token'])) {
    die("Invalid CSRF token");
}

// COMMON INPUTS
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

$first_name = trim($_POST['first_name'] ?? '');
$middle_name = trim($_POST['middle_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$age = trim($_POST['age'] ?? '');
$sex = trim($_POST['sex'] ?? '');
$email = trim($_POST['email'] ?? '');
$username = trim($_POST['username'] ?? '');
$password = (string) ($_POST['password'] ?? '');
$role_id = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT) ?: 4;
$status = in_array($_POST['status'] ?? 'active', ['active', 'inactive'], true) ? $_POST['status'] : 'active';
$civil_status = trim($_POST['civil_status'] ?? '');
$religion = trim($_POST['religion'] ?? '');
$region = trim($_POST['region'] ?? '');
$position_id = $_POST['position_id'] ?? null;
$school_id = $_POST['school_id'] ?? '';
$office_id = filter_input(INPUT_POST, 'office_id', FILTER_VALIDATE_INT) ?: null;
$office_role = in_array($_POST['office_role'] ?? 'Staff', ['Head', 'Assistant Head', 'Staff'], true) ? $_POST['office_role'] : 'Staff';
$can_validate_201 = isset($_POST['can_validate_201']) ? 1 : 0;
$can_validate_opcrf = isset($_POST['can_validate_opcrf']) ? 1 : 0;
$can_validate_ipcrf = isset($_POST['can_validate_ipcrf']) ? 1 : 0;
$can_validate_leave = isset($_POST['can_validate_leave']) ? 1 : 0;
$division_unit_id = filter_input(INPUT_POST, 'division_unit_id', FILTER_VALIDATE_INT) ?: null;
$office_unit_id = filter_input(INPUT_POST, 'office_unit_id', FILTER_VALIDATE_INT) ?: null;
$is_office_head = $office_role === 'Head' ? 1 : 0;
$educational_background = trim($_POST['educational_background'] ?? '');
$grade_level_taught = trim($_POST['grade_level_taught'] ?? '');
$specialization = trim($_POST['specialization'] ?? '');
$actual_subjects_taught = trim($_POST['actual_subjects_taught'] ?? '');
$years_in_current_position = trim($_POST['years_in_current_position'] ?? '');
$employeeID = trim($_POST['employeeID'] ?? '');
$tin = trim($_POST['tin'] ?? '');
$prc_license_number = trim($_POST['prc_license_number'] ?? '');

$roleStmt = $pdo->prepare("SELECT role_id FROM sdopang1_roles WHERE role_id = ? LIMIT 1");
$roleStmt->execute([$role_id]);
if (!$roleStmt->fetchColumn()) {
    $role_id = 4;
}

$divisionStmt = $pdo->prepare("
    SELECT unit_code
    FROM division_units
    WHERE division_unit_id = ? AND is_active = 1
    LIMIT 1
");
$divisionStmt->execute([$division_unit_id ?: 0]);
$division_unit = $divisionStmt->fetchColumn() ?: 'School';

if (!$division_unit_id) {
    $defaultDivision = $pdo->prepare("SELECT division_unit_id FROM division_units WHERE unit_code = ? LIMIT 1");
    $defaultDivision->execute([$division_unit]);
    $division_unit_id = (int) $defaultDivision->fetchColumn() ?: null;
}

if ($office_unit_id) {
    $officeUnitStmt = $pdo->prepare("
        SELECT ou.office_unit_id
        FROM office_units ou
        WHERE ou.office_unit_id = ? AND ou.division_unit_id = ? AND ou.is_active = 1
        LIMIT 1
    ");
    $officeUnitStmt->execute([$office_unit_id, $division_unit_id]);
    if (!$officeUnitStmt->fetchColumn()) {
        $office_unit_id = null;
    }
}

if (!$office_id && $id) {
    $currentOfficeStmt = $pdo->prepare("SELECT office_id FROM sdopang1_user WHERE user_id = ? LIMIT 1");
    $currentOfficeStmt->execute([$id]);
    $office_id = $currentOfficeStmt->fetchColumn() ?: null;
}

// =====================================================
// ➕ ADD USER
// =====================================================
if (isset($_POST['btnadduser'])) {
    if ($email === '' || $password === '') {
        die("Email and password are required");
    }

    $stmt = $pdo->prepare("
        INSERT INTO sdopang1_user (
            first_name, middle_name, last_name, age, sex, email, username, password, role_id, status,
            civil_status, religion, region,
            position_id, school_id, office_id, office_role, can_validate_201, can_validate_opcrf, can_validate_ipcrf, can_validate_leave, division_unit, division_unit_id, office_unit_id, is_office_head,
            educational_background, grade_level_taught,
            specialization, actual_subjects_taught,
            years_in_current_position,
            employeeID, tin, prc_license_number
        ) VALUES (
            :first_name, :middle_name, :last_name, :age, :sex, :email, :username, :password, :role_id, :status,
            :civil_status, :religion, :region,
            :position_id, :school_id, :office_id, :office_role, :can_validate_201, :can_validate_opcrf, :can_validate_ipcrf, :can_validate_leave, :division_unit, :division_unit_id, :office_unit_id, :is_office_head,
            :educational_background, :grade_level_taught,
            :specialization, :actual_subjects_taught,
            :years_in_current_position,
            :employeeID, :tin, :prc_license_number
        )
    ");

    $success = $stmt->execute([
        ':first_name' => $first_name,
        ':middle_name' => $middle_name,
        ':last_name' => $last_name,
        ':age' => $age,
        ':sex' => $sex,
        ':email' => $email,
        ':username' => $username,
        ':password' => password_hash($password, PASSWORD_DEFAULT),
        ':role_id' => $role_id,
        ':status' => $status,
        ':civil_status' => $civil_status,
        ':religion' => $religion,
        ':region' => $region,
        ':position_id' => $position_id,
        ':school_id' => $school_id,
        ':office_id' => $office_id,
        ':office_role' => $office_role,
        ':can_validate_201' => $can_validate_201,
        ':can_validate_opcrf' => $can_validate_opcrf,
        ':can_validate_ipcrf' => $can_validate_ipcrf,
        ':can_validate_leave' => $can_validate_leave,
        ':division_unit' => $division_unit,
        ':division_unit_id' => $division_unit_id,
        ':office_unit_id' => $office_unit_id,
        ':is_office_head' => $is_office_head,
        ':educational_background' => $educational_background,
        ':grade_level_taught' => $grade_level_taught,
        ':specialization' => $specialization,
        ':actual_subjects_taught' => $actual_subjects_taught,
        ':years_in_current_position' => $years_in_current_position,
        ':employeeID' => $employeeID,
        ':tin' => $tin,
        ':prc_license_number' => $prc_license_number
    ]);

    $_SESSION['success_message'] = $success ? "User added successfully" : "Add failed";
}


// =====================================================
// 🔄 UPDATE USER
// =====================================================
if (isset($_POST['btnupdateuser'])) {

    if (!$id) {
        die("Invalid user ID");
    }

    $stmt = $pdo->prepare("
        UPDATE sdopang1_user SET
            first_name = :first_name,
            middle_name = :middle_name,
            last_name = :last_name,
            age = :age,
            sex = :sex,
            email = :email,
            username = :username,
            role_id = :role_id,
            status = :status,
            civil_status = :civil_status,
            religion = :religion,
            region = :region,
            position_id = :position_id,
            school_id = :school_id,
            office_id = :office_id,
            office_role = :office_role,
            can_validate_201 = :can_validate_201,
            can_validate_opcrf = :can_validate_opcrf,
            can_validate_ipcrf = :can_validate_ipcrf,
            can_validate_leave = :can_validate_leave,
            division_unit = :division_unit,
            division_unit_id = :division_unit_id,
            office_unit_id = :office_unit_id,
            is_office_head = :is_office_head,
            educational_background = :educational_background,
            grade_level_taught = :grade_level_taught,
            specialization = :specialization,
            actual_subjects_taught = :actual_subjects_taught,
            years_in_current_position = :years_in_current_position,
            employeeID = :employeeID,
            tin = :tin,
            prc_license_number = :prc_license_number
            " . ($password !== '' ? ", password = :password" : "") . "
        WHERE user_id = :id
    ");

    $success = $stmt->execute([
        ':first_name' => $first_name,
        ':middle_name' => $middle_name,
        ':last_name' => $last_name,
        ':age' => $age,
        ':sex' => $sex,
        ':email' => $email,
        ':username' => $username,
        ':role_id' => $role_id,
        ':status' => $status,
        ':civil_status' => $civil_status,
        ':religion' => $religion,
        ':region' => $region,
        ':position_id' => $position_id,
        ':school_id' => $school_id,
        ':office_id' => $office_id,
        ':office_role' => $office_role,
        ':can_validate_201' => $can_validate_201,
        ':can_validate_opcrf' => $can_validate_opcrf,
        ':can_validate_ipcrf' => $can_validate_ipcrf,
        ':can_validate_leave' => $can_validate_leave,
        ':division_unit' => $division_unit,
        ':division_unit_id' => $division_unit_id,
        ':office_unit_id' => $office_unit_id,
        ':is_office_head' => $is_office_head,
        ':educational_background' => $educational_background,
        ':grade_level_taught' => $grade_level_taught,
        ':specialization' => $specialization,
        ':actual_subjects_taught' => $actual_subjects_taught,
        ':years_in_current_position' => $years_in_current_position,
        ':employeeID' => $employeeID,
        ':tin' => $tin,
        ':prc_license_number' => $prc_license_number,
        ':id' => $id
    ] + ($password !== '' ? [':password' => password_hash($password, PASSWORD_DEFAULT)] : []));

    $_SESSION['success_message'] = $success ? "User updated successfully" : "Update failed";
}


// =====================================================
// ❌ DELETE USER
// =====================================================
if (isset($_POST['btndeleteuser'])) {

    if (!$id) {
        die("Invalid user ID");
    }

    $stmt = $pdo->prepare("DELETE FROM sdopang1_user WHERE user_id = ?");
    $success = $stmt->execute([$id]);

    $_SESSION['success_message'] = $success ? "User deleted successfully" : "Delete failed";
}


// =====================================================
// 🔁 REDIRECT
// =====================================================
$redirectTo = $_POST['redirect_to'] ?? 'admin_users_list.php';

header("Location: " . $redirectTo);
exit;
