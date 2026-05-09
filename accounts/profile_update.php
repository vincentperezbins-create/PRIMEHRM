<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/csrf.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request');
}

if (!isset($_POST['token']) || !verifyToken($_POST['token'])) {
    die('Invalid CSRF token');
}

$enumValue = function (string $key, array $allowed): ?string {
    $value = $_POST[$key] ?? '';
    return in_array($value, $allowed, true) ? $value : null;
};

$data = [
    'first_name' => trim($_POST['first_name'] ?? ''),
    'middle_name' => trim($_POST['middle_name'] ?? ''),
    'last_name' => trim($_POST['last_name'] ?? ''),
    'name_extension' => trim($_POST['name_extension'] ?? ''),
    'age' => filter_input(INPUT_POST, 'age', FILTER_VALIDATE_INT) ?: null,
    'sex' => $enumValue('sex', ['Male', 'Female', 'Other']),
    'email' => trim($_POST['email'] ?? ''),
    'civil_status' => trim($_POST['civil_status'] ?? ''),
    'religion' => trim($_POST['religion'] ?? ''),
    'region' => trim($_POST['region'] ?? ''),
    'person_with_disability' => $enumValue('person_with_disability', ['YES', 'NO']),
    'indigenous_group' => $enumValue('indigenous_group', ['YES', 'NO']),
    'solo_parent' => $enumValue('solo_parent', ['YES', 'NO']),
    'educational_background' => trim($_POST['educational_background'] ?? ''),
    'specify_educational_background' => trim($_POST['specify_educational_background'] ?? ''),
    'grade_level_taught' => trim($_POST['grade_level_taught'] ?? ''),
    'specialization' => trim($_POST['specialization'] ?? ''),
    'actual_subjects_taught' => trim($_POST['actual_subjects_taught'] ?? ''),
    'years_in_current_position' => filter_input(INPUT_POST, 'years_in_current_position', FILTER_VALIDATE_INT) ?: null,
    'employeeID' => trim($_POST['employeeID'] ?? ''),
    'tin' => trim($_POST['tin'] ?? ''),
    'prc_license_number' => trim($_POST['prc_license_number'] ?? ''),
];

if ($data['first_name'] === '' || $data['last_name'] === '') {
    die('First name and last name are required');
}

$sets = [];
$values = [];

foreach ($data as $column => $value) {
    $sets[] = "`$column` = ?";
    $values[] = $value === '' ? null : $value;
}

$values[] = $_SESSION['user_id'];

$stmt = $pdo->prepare('UPDATE sdopang1_user SET ' . implode(', ', $sets) . ' WHERE user_id = ?');
$success = $stmt->execute($values);

$_SESSION['success_message'] = $success ? 'Profile updated successfully.' : 'Profile update failed.';
header('Location: profile.php');
exit;
