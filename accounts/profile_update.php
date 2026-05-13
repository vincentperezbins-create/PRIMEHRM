<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/core/audit.php';

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
    'appointmentdate' => trim($_POST['appointmentdate'] ?? ''),
    'assumptiontoduty' => trim($_POST['assumptiontoduty'] ?? ''),
    'employeeID' => trim($_POST['employeeID'] ?? ''),
    'tin' => trim($_POST['tin'] ?? ''),
    'prc_license_number' => trim($_POST['prc_license_number'] ?? ''),
];

if ($data['first_name'] === '' || $data['last_name'] === '') {
    die('First name and last name are required');
}

$currentPhotoStmt = $pdo->prepare('SELECT user_image FROM sdopang1_user WHERE user_id = ?');
$currentPhotoStmt->execute([$_SESSION['user_id']]);
$currentPhoto = (string) ($currentPhotoStmt->fetchColumn() ?: '');

$deleteProfilePhoto = static function (string $path): void {
    $relativePath = ltrim(str_replace('\\', '/', $path), '/');
    $baseDir = realpath(__DIR__ . '/uploads/profile');
    $fullPath = realpath(__DIR__ . '/' . $relativePath);

    if ($baseDir && $fullPath && str_starts_with($fullPath, $baseDir) && is_file($fullPath)) {
        unlink($fullPath);
    }
};

if (($_POST['remove_profile_photo'] ?? '0') === '1') {
    if ($currentPhoto !== '') {
        $deleteProfilePhoto($currentPhoto);
    }
    $data['user_image'] = null;
}

if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
    $photo = $_FILES['profile_photo'];

    if ($photo['error'] !== UPLOAD_ERR_OK) {
        die('Profile photo upload failed.');
    }

    if (($photo['size'] ?? 0) > 2 * 1024 * 1024) {
        die('Profile photo must be 2MB or smaller.');
    }

    $imageInfo = @getimagesize($photo['tmp_name']);
    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    if (!$imageInfo || !isset($allowedMimeTypes[$imageInfo['mime']])) {
        die('Profile photo must be a JPG, PNG, WebP, or GIF image.');
    }

    $uploadDir = __DIR__ . '/uploads/profile/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
        die('Unable to prepare profile photo folder.');
    }

    $fileName = 'profile_' . (int) $_SESSION['user_id'] . '_' . bin2hex(random_bytes(8)) . '.' . $allowedMimeTypes[$imageInfo['mime']];
    $targetPath = $uploadDir . $fileName;

    if (!move_uploaded_file($photo['tmp_name'], $targetPath)) {
        die('Unable to save profile photo.');
    }

    if ($currentPhoto !== '') {
        $deleteProfilePhoto($currentPhoto);
    }

    $data['user_image'] = 'uploads/profile/' . $fileName;
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

if ($success) {
    audit_log(
        $pdo,
        $_SESSION['user_id'] ?? null,
        trim($data['first_name'] . ' ' . $data['last_name']),
        isset($data['user_image']) && $data['user_image'] ? 'UPLOAD' : 'UPDATE',
        'Profile',
        $_SESSION['user_id'] ?? null,
        isset($data['user_image']) && $data['user_image'] ? 'Updated profile and uploaded a profile photo.' : 'Updated profile details.'
    );
}

$_SESSION['success_message'] = $success ? 'Profile updated successfully.' : 'Profile update failed.';
header('Location: profile.php');
exit;
