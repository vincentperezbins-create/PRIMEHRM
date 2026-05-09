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

if (!$leaveTypeId || $reason === '') {
    leave_json(['status' => 'error', 'message' => 'Leave type and reason are required'], 422);
}

try {
    $days = leave_work_days($dateFrom, $dateTo);

    if ($days <= 0) {
        throw new RuntimeException('Leave date range has no working days');
    }

    $type = leave_get_type($pdo, $leaveTypeId);
    $personnelType = leave_get_user_personnel_type($pdo, $userId);

    if (!empty($type['personnel_type'])) {
        $allowedPersonnel = strtolower(trim((string) $type['personnel_type']));
        if (!in_array($allowedPersonnel, ['both', $personnelType], true)) {
            throw new RuntimeException('This leave type is not available for your personnel type');
        }
    }

    if (leave_deducts_balance($type)) {
        if (($type['leave_code'] ?? '') === 'SL') {
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

