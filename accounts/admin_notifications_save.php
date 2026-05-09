<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/core/notification_helpers.php';

require_login();
require_role([1]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request');
}

if (!isset($_POST['token']) || !verifyToken($_POST['token'])) {
    die('Invalid CSRF token');
}

$targetType = $_POST['target_type'] ?? 'all';
$title = trim($_POST['title'] ?? '');
$message = trim($_POST['message'] ?? '');
$link = trim($_POST['link'] ?? '') ?: null;
$userId = null;
$roleId = null;

if ($title === '' || $message === '') {
    die('Title and message are required');
}

if ($targetType === 'user') {
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    if (!$userId) {
        die('User is required');
    }
} elseif ($targetType === 'role') {
    $roleId = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);
    if (!$roleId) {
        die('Role is required');
    }
}

notification_create($pdo, $title, $message, $link, $userId, $roleId, 'manual', (int) $_SESSION['user_id']);

$_SESSION['success_message'] = 'Notification sent.';
header('Location: admin_notifications.php');
exit;
