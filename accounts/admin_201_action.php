<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/csrf.php';

require_login();
require_validator($pdo, '201');if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
    UPDATE sdopang1_documents
    SET status = ?, remarks = ?
    WHERE document_id = ?
");
$success = $stmt->execute([$status, $remarks, $documentId]);

if (!$success) {
    die("Update failed");
}

$_SESSION['success_message'] = $status === 'Approved'
    ? 'Document approved successfully.'
    : 'Document returned successfully.';

header("Location: admin_201_tables.php");
exit;


