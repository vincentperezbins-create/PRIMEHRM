<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/core/audit.php';
require_once __DIR__ . '/core/leave_helpers.php';

$userModel = new User($pdo);
require_login();
require_role([3]);
$currentUser = $userModel->getUserById($_SESSION['user_id']);
$schoolId = $currentUser['school_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    leave_json(['status' => 'error', 'message' => 'Invalid request'], 405);
}

if (!isset($_POST['token']) || !verifyToken($_POST['token'])) {
    leave_json(['status' => 'error', 'message' => 'Invalid CSRF token'], 403);
}

$applicationId = filter_input(INPUT_POST, 'application_id', FILTER_VALIDATE_INT)
    ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$action = $_POST['action'] ?? '';
$remarks = trim($_POST['remarks'] ?? '');

if (!$applicationId || !in_array($action, ['approve', 'reject'], true)) {
    leave_json(['status' => 'error', 'message' => 'Invalid request'], 422);
}

if ($action === 'reject' && $remarks === '') {
    leave_json(['status' => 'error', 'message' => 'Rejection remarks are required'], 422);
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT la.*
        FROM leave_applications la
        JOIN sdopang1_user u ON u.user_id = la.user_id
        WHERE la.application_id = ?
          AND u.school_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$applicationId, $schoolId]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        throw new RuntimeException('Leave application not found for your school');
    }

    if ((int) $application['user_id'] === (int) $_SESSION['user_id']) {
        throw new RuntimeException('You cannot approve or reject your own leave application');
    }

    if (($application['status'] ?? '') !== 'pending') {
        throw new RuntimeException('Only pending applications can be changed');
    }

    if ($action === 'approve') {
        $type = leave_get_type($pdo, (int) $application['leave_type_id']);
        leave_validate_application_rules($pdo, (int) $application['user_id'], $type, (float) $application['days'], (string) $application['date_from']);
        leave_deduct_for_application($pdo, $application, $remarks !== '' ? $remarks : null);

        leave_update_application($pdo, $applicationId, [
            'status' => 'approved',
            'approved_by' => $_SESSION['user_id'],
            'approved_at' => '__NOW__',
            'remarks' => $remarks !== '' ? $remarks : null,
        ]);
    } else {
        leave_update_application($pdo, $applicationId, [
            'status' => 'rejected',
            'approved_by' => $_SESSION['user_id'],
            'approved_at' => '__NOW__',
            'rejection_reason' => $remarks,
            'remarks' => $remarks,
        ]);
    }

    $pdo->commit();
    audit_log(
        $pdo,
        $_SESSION['user_id'] ?? null,
        audit_current_fullname($pdo),
        $action === 'approve' ? 'APPROVE' : 'REJECT',
        'School Leave',
        $applicationId,
        ($action === 'approve' ? 'Approved' : 'Rejected') . ' a school leave application.'
    );
    leave_json(['status' => 'success']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    leave_json(['status' => 'error', 'message' => $e->getMessage()], 422);
}
