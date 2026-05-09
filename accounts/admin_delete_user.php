<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(400);
    echo "Invalid ID";
    exit;
}

// OPTIONAL: prevent deleting yourself
if ($id == $_SESSION['user_id']) {
    http_response_code(403);
    echo "You cannot delete your own account";
    exit;
}

// DELETE
$stmt = $pdo->prepare("DELETE FROM sdopang1_user WHERE user_id = ?");
$success = $stmt->execute([$id]);

if ($success) {
    echo "success";
} else {
    http_response_code(500);
    echo "Delete failed";
}