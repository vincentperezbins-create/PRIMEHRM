<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/audit.php';

require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$allowedActions = ['CREATE', 'UPDATE', 'DELETE', 'APPROVE', 'REJECT', 'UPLOAD', 'DOWNLOAD', 'PRINT', 'EXPORT'];
$action = strtoupper(trim((string) ($_POST['action_type'] ?? '')));
$module = trim((string) ($_POST['module_name'] ?? ''));
$recordId = trim((string) ($_POST['record_id'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));

if (!in_array($action, $allowedActions, true) || $module === '') {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Invalid audit details']);
    exit;
}

audit_log(
    $pdo,
    $_SESSION['user_id'] ?? null,
    audit_current_fullname($pdo),
    $action,
    $module,
    $recordId !== '' ? $recordId : null,
    $description !== '' ? $description : null
);

echo json_encode(['status' => 'success']);
