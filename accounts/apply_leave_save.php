<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/core/leave_helpers.php';

require_login();
require_role([1, 2, 3, 4, 5, 6, 7]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    leave_json(['status' => 'error', 'message' => 'Invalid request'], 405);
}

if (!isset($_POST['token']) || !verifyToken($_POST['token'])) {
    leave_json(['status' => 'error', 'message' => 'Invalid CSRF token'], 403);
}

$userId = (int) $_SESSION['user_id'];
$leaveTypeId = filter_input(INPUT_POST, 'leave_type_id', FILTER_VALIDATE_INT);
$dateFrom = $_POST['date_from'] ?? '';
$dateTo = $_POST['date_to'] ?? '';
$reason = trim($_POST['reason'] ?? '');
$ctoTransactionIds = isset($_POST['cto_transaction_ids']) && is_array($_POST['cto_transaction_ids'])
    ? leave_normalize_cto_selection($_POST['cto_transaction_ids'])
    : [];
$leaveSchedule = [];

if (!$leaveTypeId || $reason === '') {
    leave_json(['status' => 'error', 'message' => 'Leave type and reason are required'], 422);
}

try {
    $type = leave_get_type($pdo, $leaveTypeId);
    $personnelType = leave_get_user_personnel_type($pdo, $userId);

    if (!empty($_POST['cto_schedule']) && is_array($_POST['cto_schedule'])) {
        if (!leave_has_column($pdo, 'leave_applications', 'leave_schedule')) {
            throw new RuntimeException('Please run database/leave_schedule_migration.sql before filing leave by schedule');
        }

        $leaveSchedule = leave_cto_parse_schedule($_POST['cto_schedule'], ($type['leave_code'] ?? '') === 'CTO');
        $summary = leave_cto_schedule_summary($leaveSchedule);
        $dateFrom = $summary['date_from'];
        $dateTo = $summary['date_to'];
        $days = (float) $summary['days'];
    } else {
        $days = leave_work_days($dateFrom, $dateTo);
    }

    if ($days <= 0) {
        throw new RuntimeException('Leave date range has no working days');
    }

    if (!empty($type['personnel_type'])) {
        $allowedPersonnel = strtolower(trim((string) $type['personnel_type']));
        if (!in_array($allowedPersonnel, ['both', $personnelType], true)) {
            throw new RuntimeException('This leave type is not available for your personnel type');
        }
    }

    if (leave_deducts_balance($type)) {
        if (($type['leave_code'] ?? '') === 'CTO') {
            if (!leave_has_column($pdo, 'leave_applications', 'cto_transaction_ids')) {
                throw new RuntimeException('Please run database/leave_cto_selection_migration.sql before filing CTO leave');
            }

            leave_validate_cto_selection($pdo, $userId, $leaveTypeId, $days, $ctoTransactionIds, $dateTo);
            $available = array_sum(array_map('floatval', array_column(leave_cto_available_batches($pdo, $userId, $leaveTypeId, $dateTo), 'remaining')));
        } elseif (($type['leave_code'] ?? '') === 'SL') {
            $vl = leave_get_type_by_code($pdo, 'VL');
            $available = leave_get_balance($pdo, $userId, $leaveTypeId) + ($vl ? leave_get_balance($pdo, $userId, (int) $vl['leave_type_id']) : 0);
        } else {
            $available = leave_get_balance($pdo, $userId, $leaveTypeId);
        }

        if ($available < $days) {
            throw new RuntimeException('Insufficient leave balance');
        }
    }

    leave_validate_application_rules($pdo, $userId, $type, $days, $dateFrom);

    $available = leave_table_columns($pdo, 'leave_applications');
    $data = [
        'user_id' => $userId,
        'leave_type_id' => $leaveTypeId,
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'days' => $days,
        'reason' => $reason,
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
        'created_by' => $userId,
        'cto_transaction_ids' => ($type['leave_code'] ?? '') === 'CTO' ? json_encode($ctoTransactionIds) : null,
        'cto_schedule' => ($type['leave_code'] ?? '') === 'CTO' ? json_encode($leaveSchedule) : null,
        'leave_schedule' => $leaveSchedule ? json_encode($leaveSchedule) : null,
    ];
    $data = array_intersect_key($data, array_flip($available));

    $columns = array_keys($data);
    $placeholders = implode(',', array_fill(0, count($columns), '?'));
    $sql = 'INSERT INTO leave_applications (`' . implode('`,`', $columns) . "`) VALUES ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($data));
    $applicationId = (int) $pdo->lastInsertId();

    leave_json([
        'status' => 'success',
        'message' => 'Leave application submitted',
        'days' => $days,
        'application_id' => $applicationId,
        'form6_url' => 'download_form6.php?application_id=' . $applicationId,
    ]);
} catch (Throwable $e) {
    leave_json(['status' => 'error', 'message' => $e->getMessage()], 422);
}

