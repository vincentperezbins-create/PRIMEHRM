<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/csrf.php';

$userModel = new User($pdo);
require_login();
require_role([3]);
$currentUser = $userModel->getUserById($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request");
}

if (!isset($_POST['token']) || !verifyToken($_POST['token'])) {
    die("Invalid CSRF token");
}

$documentId = filter_input(INPUT_POST, 'document_id', FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? '';
$remarks = trim($_POST['remarks'] ?? '');

if (!$documentId || !in_array($action, ['approve', 'return'], true)) {
    die("Invalid request");
}

if ($action === 'return' && $remarks === '') {
    die("Remarks are required when returning a document");
}

$status = $action === 'approve' ? 'Approved' : 'Returned';
$remarks = $action === 'approve' ? null : $remarks;

$stmt = $pdo->prepare("
    UPDATE sdopang1_documents d
    JOIN sdopang1_user u ON d.user_id = u.user_id
    SET d.status = ?, d.remarks = ?, d.approved_by = ?, d.approved_at = NOW()
    WHERE d.document_id = ?
      AND u.school_id = ?
      AND u.role_id = 4
      AND u.user_id <> ?
");
$success = $stmt->execute([
    $status,
    $remarks,
    $_SESSION['user_id'],
    $documentId,
    $currentUser['school_id'],
    $_SESSION['user_id'],
]);

if (!$success || $stmt->rowCount() === 0) {
    die("Update failed or access denied");
}

$_SESSION['success_message'] = $status === 'Approved'
    ? 'Document approved successfully.'
    : 'Document returned successfully.';

header("Location: school_201_tables.php");
exit;
