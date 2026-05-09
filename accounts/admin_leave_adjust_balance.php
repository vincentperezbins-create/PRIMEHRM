<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/core/leave_helpers.php';

require_login();
require_role([1]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    leave_json(['status' => 'error', 'message' => 'Invalid request'], 405);
}

if (!isset($_POST['token']) || !verifyToken($_POST['token'])) {
    leave_json(['status' => 'error', 'message' => 'Invalid CSRF token'], 403);
}

$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
$leaveTypeId = filter_input(INPUT_POST, 'leave_type_id', FILTER_VALIDATE_INT);
$days = filter_input(INPUT_POST, 'days', FILTER_VALIDATE_FLOAT);
$remarks = trim($_POST['remarks'] ?? 'Admin adjustment');
$earnedDate = trim($_POST['earned_date'] ?? '');
$earnedAt = null;

if ($earnedDate !== '') {
    $parsed = DateTime::createFromFormat('Y-m-d', $earnedDate);
    if (!$parsed || $parsed->format('Y-m-d') !== $earnedDate) {
        leave_json(['status' => 'error', 'message' => 'Invalid earned date'], 422);
    }
    $earnedAt = $parsed->format('Y-m-d 00:00:00');
}

if (!$userId || !$leaveTypeId || $days === false || $days == 0.0) {
    leave_json(['status' => 'error', 'message' => 'User, leave type, and non-zero days are required'], 422);
}

try {
    $pdo->beginTransaction();
    $transactionType = 'adjust';
    $newBalance = leave_change_balance($pdo, $userId, $leaveTypeId, (float) $days, $transactionType, 'manual', $remarks, null, $earnedAt);
    $pdo->commit();

    leave_json([
        'status' => 'success',
        'balance' => number_format($newBalance, 3, '.', ''),
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    leave_json(['status' => 'error', 'message' => $e->getMessage()], 422);
}
