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

$run_key = date('Y');

$check = $pdo->prepare("
    SELECT 1
    FROM system_runs
    WHERE process_name = 'yearly_forced_leave' AND run_key = ?
");
$check->execute([$run_key]);

if ($check->fetchColumn()) {
    exit("Already executed for year $run_key");
}

try {
    $pdo->beginTransaction();

    $vl = $pdo->query("SELECT leave_type_id FROM leave_types WHERE leave_code = 'VL'")->fetchColumn();

    if (!$vl) {
        throw new RuntimeException('VL leave type not found');
    }

    $users = $pdo->query("
        SELECT u.user_id
        FROM sdopang1_user u
        JOIN sdopang1_position p ON u.position_id = p.position_id
        WHERE LOWER(p.position_category) = 'non-teaching'
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($users as $user) {
        $usedStmt = $pdo->prepare("
            SELECT COALESCE(SUM(days), 0)
            FROM leave_transactions
            WHERE user_id = ?
              AND leave_type_id = ?
              AND transaction_type IN ('use', 'forced_leave')
              AND YEAR(created_at) = ?
        ");
        $usedStmt->execute([(int) $user['user_id'], (int) $vl, $run_key]);
        $used = (float) $usedStmt->fetchColumn();

        if ($used < 5) {
            $deduct = 5 - $used;
            leave_change_balance(
                $pdo,
                (int) $user['user_id'],
                (int) $vl,
                -$deduct,
                'forced_leave',
                'forced_rule',
                "Year-end forced leave compliance for $run_key"
            );
        }
    }

    $pdo->prepare("
        INSERT INTO system_runs (process_name, run_key)
        VALUES ('yearly_forced_leave', ?)
    ")->execute([$run_key]);

    $pdo->commit();
    echo "Year-end processing completed for $run_key";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(422);
    echo $e->getMessage();
}
