<?php
require_once __DIR__ . '/../../core/db.php';
require_once __DIR__ . '/../core/leave_helpers.php';

if (function_exists('set_time_limit')) {
    set_time_limit(0);
}

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
$runRemarksLike = "Yearly entitlement reset%($run_key)%";

$check = $pdo->prepare("
    SELECT 1
    FROM system_runs
    WHERE process_name = 'yearly_entitlements' AND run_key = ?
");
$check->execute([$run_key]);
$alreadyRun = (bool) $check->fetchColumn();

if ($alreadyRun) {
    $txCheck = $pdo->prepare("
        SELECT COUNT(*)
        FROM leave_transactions
        WHERE remarks LIKE ?
    ");
    $txCheck->execute([$runRemarksLike]);

    if ((int) $txCheck->fetchColumn() > 0) {
        exit("Already added yearly leave entitlements for $run_key");
    }
}

try {
    $pdo->beginTransaction();

    $types = $pdo->query("
        SELECT leave_type_id, leave_code, leave_name, personnel_type, max_per_year
        FROM leave_types
        WHERE is_active = 1
          AND leave_code IN ('SPL', 'SOLO', 'VAWC', 'WL')
          AND max_per_year IS NOT NULL
        ORDER BY leave_code
    ")->fetchAll(PDO::FETCH_ASSOC);

    $credited = 0;
    $reset = 0;

    foreach ($types as $type) {
        $personnelType = strtolower(trim((string) $type['personnel_type']));
        $leaveTypeId = (int) $type['leave_type_id'];
        $targetBalance = (float) $type['max_per_year'];
        $remarks = "Yearly entitlement reset for {$type['leave_name']} ($run_key)";
        $personnelFilter = ($personnelType !== '' && $personnelType !== 'both') ? $personnelType : 'both';

        $countStmt = $pdo->prepare("
            SELECT
                SUM(CASE WHEN ? > COALESCE(b.balance, 0) THEN 1 ELSE 0 END) AS credited_count,
                SUM(CASE WHEN ? < COALESCE(b.balance, 0) THEN 1 ELSE 0 END) AS reset_count
            FROM sdopang1_user u
            LEFT JOIN sdopang1_position p ON p.position_id = u.position_id
            LEFT JOIN leave_balances b
                ON b.user_id = u.user_id AND b.leave_type_id = ?
            WHERE (? = 'both' OR LOWER(COALESCE(p.position_category, '')) = ?)
              AND ABS(? - COALESCE(b.balance, 0)) >= 0.001
        ");
        $countStmt->execute([$targetBalance, $targetBalance, $leaveTypeId, $personnelFilter, $personnelFilter, $targetBalance]);
        $counts = $countStmt->fetch(PDO::FETCH_ASSOC) ?: ['credited_count' => 0, 'reset_count' => 0];
        $credited += (int) $counts['credited_count'];
        $reset += (int) $counts['reset_count'];

        $txStmt = $pdo->prepare("
            INSERT INTO leave_transactions
                (user_id, leave_type_id, transaction_type, days, balance_after, source, reference_id, remarks, created_at)
            SELECT
                u.user_id,
                ?,
                CASE WHEN ? > COALESCE(b.balance, 0) THEN 'earn' ELSE 'use' END,
                ABS(? - COALESCE(b.balance, 0)),
                ?,
                'manual',
                NULL,
                ?,
                NOW()
            FROM sdopang1_user u
            LEFT JOIN sdopang1_position p ON p.position_id = u.position_id
            LEFT JOIN leave_balances b
                ON b.user_id = u.user_id AND b.leave_type_id = ?
            WHERE (? = 'both' OR LOWER(COALESCE(p.position_category, '')) = ?)
              AND ABS(? - COALESCE(b.balance, 0)) >= 0.001
        ");
        $txStmt->execute([
            $leaveTypeId,
            $targetBalance,
            $targetBalance,
            $targetBalance,
            $remarks,
            $leaveTypeId,
            $personnelFilter,
            $personnelFilter,
            $targetBalance,
        ]);

        $balanceStmt = $pdo->prepare("
            INSERT INTO leave_balances (user_id, leave_type_id, balance)
            SELECT u.user_id, ?, ?
            FROM sdopang1_user u
            LEFT JOIN sdopang1_position p ON p.position_id = u.position_id
            WHERE (? = 'both' OR LOWER(COALESCE(p.position_category, '')) = ?)
            ON DUPLICATE KEY UPDATE balance = VALUES(balance)
        ");
        $balanceStmt->execute([$leaveTypeId, $targetBalance, $personnelFilter, $personnelFilter]);
    }

    if (!$alreadyRun) {
        $pdo->prepare("
            INSERT INTO system_runs (process_name, run_key)
            VALUES ('yearly_entitlements', ?)
        ")->execute([$run_key]);
    }

    $pdo->commit();
    echo "Yearly leave entitlements added for $run_key. Credited: $credited. Reset deductions: $reset.";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(422);
    echo $e->getMessage();
}
