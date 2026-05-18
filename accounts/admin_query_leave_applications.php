<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/core/audit.php';
require_once __DIR__ . '/core/leave_helpers.php';

require_login();
require_scoped_validator($pdo, 'leave');
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
$payStatus = $_POST['pay_status'] ?? 'with_pay';

if (!$applicationId || !in_array($action, ['approve', 'reject', 'update'], true)) {
    leave_json(['status' => 'error', 'message' => 'Invalid request'], 422);
}

if (!in_array($payStatus, ['with_pay', 'without_pay'], true)) {
    leave_json(['status' => 'error', 'message' => 'Invalid pay status'], 422);
}

if ($action === 'reject' && $remarks === '') {
    leave_json(['status' => 'error', 'message' => 'Rejection remarks are required'], 422);
}

if (($action === 'approve' || $action === 'update') && !leave_has_column($pdo, 'leave_applications', 'pay_status')) {
    leave_json(['status' => 'error', 'message' => 'Please run database/leave_pay_status_migration.sql before setting pay status'], 422);
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT * FROM leave_applications WHERE application_id = ? FOR UPDATE");
    $stmt->execute([$applicationId]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$application) {
        throw new RuntimeException('Leave application not found');
    }

    if (!user_can_validate_leave_application($pdo, $applicationId)) {
        throw new RuntimeException('You can only validate leave applications within your assigned scope.');
    }

    if (($application['status'] ?? '') !== 'pending') {
        throw new RuntimeException('Only pending applications can be changed');
    }

    if ($action === 'update') {
        $leaveTypeId = filter_input(INPUT_POST, 'leave_type_id', FILTER_VALIDATE_INT);
        $dateFrom = $_POST['date_from'] ?? '';
        $dateTo = $_POST['date_to'] ?? '';
        $reason = trim($_POST['reason'] ?? '');

        if (!$leaveTypeId || $reason === '') {
            throw new RuntimeException('Leave type and reason are required');
        }

        $days = leave_work_days($dateFrom, $dateTo);
        if ($days <= 0) {
            throw new RuntimeException('Leave date range has no working days');
        }

        $type = leave_get_type($pdo, $leaveTypeId);
        $personnelType = leave_get_user_personnel_type($pdo, (int) $application['user_id']);

        if (!empty($type['personnel_type'])) {
            $allowedPersonnel = strtolower(trim((string) $type['personnel_type']));
            if (!in_array($allowedPersonnel, ['both', $personnelType], true)) {
                throw new RuntimeException('This leave type is not available for the employee personnel type');
            }
        }

        if ($payStatus === 'with_pay' && leave_deducts_balance($type)) {
            if (($type['leave_code'] ?? '') === 'SL') {
                $vl = leave_get_type_by_code($pdo, 'VL');
                $available = leave_get_balance($pdo, (int) $application['user_id'], $leaveTypeId) + ($vl ? leave_get_balance($pdo, (int) $application['user_id'], (int) $vl['leave_type_id']) : 0);
            } else {
                $available = leave_get_balance($pdo, (int) $application['user_id'], $leaveTypeId);
            }

            if ($available < $days) {
                throw new RuntimeException('Insufficient leave balance');
            }
        }

        leave_validate_application_rules($pdo, (int) $application['user_id'], $type, $days, $dateFrom);

        leave_update_application($pdo, $applicationId, [
            'leave_type_id' => $leaveTypeId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'days' => $days,
            'reason' => $reason,
            'pay_status' => $payStatus,
            'remarks' => $remarks !== '' ? $remarks : null,
        ]);

        $pdo->commit();
        leave_json(['status' => 'success', 'days' => $days]);
    }

    if ($action === 'approve') {
        $type = leave_get_type($pdo, (int) $application['leave_type_id']);
        leave_update_application($pdo, $applicationId, ['pay_status' => $payStatus]);
        $application['pay_status'] = $payStatus;

        if ($payStatus === 'with_pay') {
            leave_validate_application_rules($pdo, (int) $application['user_id'], $type, (float) $application['days'], (string) $application['date_from']);
            leave_deduct_for_application($pdo, $application, $remarks !== '' ? $remarks : null);
        }

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
        'Leave Management',
        $applicationId,
        ($action === 'approve' ? 'Approved' : 'Rejected') . ' a leave application.'
    );
    leave_json(['status' => 'success']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    leave_json(['status' => 'error', 'message' => $e->getMessage()], 422);
}


