<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/audit.php';

require_login();
require_role([1]);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(400);
    echo "Invalid ID";
    exit;
}

$stmt = $pdo->prepare("DELETE FROM sdopang1schoollist WHERE schoolID = ?");
if ($stmt->execute([$id])) {
    audit_log($pdo, $_SESSION['user_id'] ?? null, audit_current_fullname($pdo), 'DELETE', 'Schools', $id, 'Deleted a school record.');
}

echo "success";
