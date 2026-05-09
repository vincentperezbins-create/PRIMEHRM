<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/notification_helpers.php';

require_login();

$notificationId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$notificationId) {
    header('Location: index.php');
    exit;
}

$link = notification_mark_read($pdo, $notificationId, (int) $_SESSION['user_id'], (int) $_SESSION['role_id']);

if (!$link) {
    $link = 'index.php';
}

header('Location: ' . $link);
exit;
