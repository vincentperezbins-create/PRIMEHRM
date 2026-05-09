<?php
require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../core/leave_helpers.php';

if (PHP_SAPI !== 'cli') {
    require_once __DIR__ . '/../core/auth.php';
    require_once __DIR__ . '/../core/csrf.php';

    require_login();
    require_role([1]);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['token']) || !verifyToken($_POST['token'])) {
        http_response_code(403);
        exit('Invalid request');
    }
}

$run_key = date('Y-m');

$check = $pdo->prepare("
    SELECT 1
    FROM system_runs
    WHERE process_name = 'monthly_accrual' AND run_key = ?
");
$check->execute([$run_key]);

if ($check->fetchColumn()) {
    exit("Already executed for this month ($run_key)");
}

try {
    $pdo->beginTransaction();

    $leaveTypeStmt = $pdo->prepare("SELECT leave_type_id FROM leave_types WHERE leave_code = ?");
    $users = $pdo->query("
        SELECT u.user_id
        FROM sdopang1_user u
        JOIN sdopang1_position p ON u.position_id = p.position_id
        WHERE LOWER(p.position_category) = 'non-teaching'
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as $user) {
        foreach (['VL', 'SL'] as $code) {
            $leaveTypeStmt->execute([$code]);
            $leaveTypeId = $leaveTypeStmt->fetchColumn();

            if (!$leaveTypeId) {
                continue;
            }

            leave_change_balance(
                $pdo,
                (int) $user['user_id'],
                (int) $leaveTypeId,
                1.25,
                'earn',
                'monthly_accrual',
                "Monthly accrual for $run_key"
            );
        }
    }

    $pdo->prepare("
        INSERT INTO system_runs (process_name, run_key)
        VALUES ('monthly_accrual', ?)
    ")->execute([$run_key]);

    $pdo->commit();
    echo "Monthly accrual completed for $run_key";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(422);
    echo $e->getMessage();
}
