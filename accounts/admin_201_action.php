<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/core/audit.php';

require_login();
require_scoped_validator($pdo, '201');
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

if (!user_can_validate_201_document($pdo, $documentId)) {
    die("You can only validate 201 files within your assigned scope.");
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

audit_log(
    $pdo,
    $_SESSION['user_id'] ?? null,
    audit_current_fullname($pdo),
    $status === 'Approved' ? 'APPROVE' : 'REJECT',
    '201 Files',
    $documentId,
    $status === 'Approved' ? 'Approved a 201 file upload.' : 'Returned a 201 file upload.'
);

$_SESSION['success_message'] = $status === 'Approved'
    ? 'Document approved successfully.'
    : 'Document returned successfully.';

header("Location: admin_201_tables.php");
exit;


