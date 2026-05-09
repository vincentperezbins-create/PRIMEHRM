<?php

function leave_table_columns(PDO $pdo, string $table): array {
    static $cache = [];

    if (isset($cache[$table])) {
        return $cache[$table];
    }

    try {
        $stmt = $pdo->query("DESCRIBE `$table`");
        $cache[$table] = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
    } catch (Throwable $e) {
        $cache[$table] = [];
    }

    return $cache[$table];
}

function leave_has_column(PDO $pdo, string $table, string $column): bool {
    return in_array($column, leave_table_columns($pdo, $table), true);
}

function leave_json(array $payload, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function leave_work_days(string $dateFrom, string $dateTo): float {
    $start = DateTime::createFromFormat('Y-m-d', $dateFrom);
    $end = DateTime::createFromFormat('Y-m-d', $dateTo);

    if (!$start || !$end || $start->format('Y-m-d') !== $dateFrom || $end->format('Y-m-d') !== $dateTo || $end < $start) {
        throw new InvalidArgumentException('Invalid date range');
    }

    $days = 0;
    $cursor = clone $start;

    while ($cursor <= $end) {
        $dayOfWeek = (int) $cursor->format('N');
        if ($dayOfWeek <= 5) {
            $days++;
        }
        $cursor->modify('+1 day');
    }

    return (float) $days;
}

function leave_get_type(PDO $pdo, int $leaveTypeId): array {
    $stmt = $pdo->prepare("SELECT * FROM leave_types WHERE leave_type_id = ?");
    $stmt->execute([$leaveTypeId]);
    $type = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$type) {
        throw new RuntimeException('Leave type not found');
    }

    return $type;
}

function leave_is_credit_based(array $type): bool {
    if (!array_key_exists('is_credit_based', $type)) {
        return true;
    }

    return (int) $type['is_credit_based'] === 1;
}

function leave_deducts_balance(array $type): bool {
    if (leave_is_credit_based($type)) {
        return true;
    }

    $code = strtoupper((string) ($type['leave_code'] ?? ''));

    return in_array($code, ['SPL', 'SOLO', 'VAWC', 'WL'], true);
}

function leave_get_type_by_code(PDO $pdo, string $code): ?array {
    $stmt = $pdo->prepare("SELECT * FROM leave_types WHERE leave_code = ? ORDER BY is_active DESC, leave_type_id ASC LIMIT 1");
    $stmt->execute([$code]);
    $type = $stmt->fetch(PDO::FETCH_ASSOC);

    return $type ?: null;
}

function leave_get_approved_days_for_year(PDO $pdo, int $userId, int $leaveTypeId, string $dateFrom): float {
    $year = (int) date('Y', strtotime($dateFrom));
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(days), 0)
        FROM leave_applications
        WHERE user_id = ?
          AND leave_type_id = ?
          AND status = 'approved'
          AND YEAR(date_from) = ?
    ");
    $stmt->execute([$userId, $leaveTypeId, $year]);

    return (float) $stmt->fetchColumn();
}

function leave_validate_application_rules(PDO $pdo, int $userId, array $type, float $days, string $dateFrom): void {
    if (!empty($type['max_per_year'])) {
        $used = leave_get_approved_days_for_year($pdo, $userId, (int) $type['leave_type_id'], $dateFrom);
        $max = (float) $type['max_per_year'];

        if (($used + $days) > $max) {
            throw new RuntimeException('This request exceeds the annual limit for ' . $type['leave_name']);
        }
    }

    if (($type['leave_code'] ?? '') === 'FL') {
        $vl = leave_get_type_by_code($pdo, 'VL');
        $vlBalance = $vl ? leave_get_balance($pdo, $userId, (int) $vl['leave_type_id']) : 0.0;

        if ($vlBalance < 10) {
            throw new RuntimeException('Forced leave requires at least 10 VL credits');
        }
    }

    if (($type['leave_code'] ?? '') === 'CTO' && !in_array($days, [0.5, 1.0, 2.0, 3.0, 4.0, 5.0], true)) {
        throw new RuntimeException('CTO must be availed in 4-hour, 8-hour, or up to 5-day blocks');
    }
}

function leave_get_balance_row(PDO $pdo, int $userId, int $leaveTypeId, bool $forUpdate = false): ?array {
    $sql = "
        SELECT *
        FROM leave_balances
        WHERE user_id = ? AND leave_type_id = ?
        LIMIT 1
    ";

    if ($forUpdate) {
        $sql .= " FOR UPDATE";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $leaveTypeId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function leave_get_balance(PDO $pdo, int $userId, int $leaveTypeId): float {
    $type = leave_get_type($pdo, $leaveTypeId);
    if (($type['leave_code'] ?? '') === 'CTO' && leave_has_column($pdo, 'leave_transactions', 'expires_at')) {
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(
                CASE
                    WHEN transaction_type IN ('earn', 'adjust')
                         AND (expires_at IS NULL OR expires_at >= NOW())
                    THEN days
                    WHEN transaction_type NOT IN ('earn', 'adjust')
                    THEN -days
                    ELSE 0
                END
            ), 0)
            FROM leave_transactions
            WHERE user_id = ? AND leave_type_id = ?
        ");
        $stmt->execute([$userId, $leaveTypeId]);

        return (float) $stmt->fetchColumn();
    }

    $row = leave_get_balance_row($pdo, $userId, $leaveTypeId);
    if ($row) {
        return (float) $row['balance'];
    }

    $stmt = $pdo->prepare("
        SELECT balance_after
        FROM leave_transactions
        WHERE user_id = ? AND leave_type_id = ? AND balance_after IS NOT NULL
        ORDER BY created_at DESC, transaction_id DESC
        LIMIT 1
    ");
    $stmt->execute([$userId, $leaveTypeId]);
    $latest = $stmt->fetchColumn();

    if ($latest !== false) {
        return (float) $latest;
    }

    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(
            CASE
                WHEN transaction_type IN ('earn', 'adjust') THEN days
                ELSE -days
            END
        ), 0)
        FROM leave_transactions
        WHERE user_id = ? AND leave_type_id = ?
    ");
    $stmt->execute([$userId, $leaveTypeId]);

    return (float) $stmt->fetchColumn();
}

function leave_create_balance_row(PDO $pdo, int $userId, int $leaveTypeId): void {
    $columns = ['user_id', 'leave_type_id', 'balance'];
    $values = [$userId, $leaveTypeId, 0];

    if (leave_has_column($pdo, 'leave_balances', 'last_updated')) {
        $columns[] = 'last_updated';
        $values[] = date('Y-m-d H:i:s');
    }

    $placeholders = implode(',', array_fill(0, count($columns), '?'));
    $sql = 'INSERT INTO leave_balances (`' . implode('`,`', $columns) . "`) VALUES ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
}

function leave_set_balance(PDO $pdo, array $balanceRow, int $userId, int $leaveTypeId, float $newBalance): void {
    $sets = ['balance = ?'];
    $values = [$newBalance];

    if (leave_has_column($pdo, 'leave_balances', 'last_updated')) {
        $sets[] = 'last_updated = NOW()';
    }

    if (isset($balanceRow['balance_id'])) {
        $values[] = $balanceRow['balance_id'];
        $sql = 'UPDATE leave_balances SET ' . implode(', ', $sets) . ' WHERE balance_id = ?';
    } else {
        $values[] = $userId;
        $values[] = $leaveTypeId;
        $sql = 'UPDATE leave_balances SET ' . implode(', ', $sets) . ' WHERE user_id = ? AND leave_type_id = ?';
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
}

function leave_insert_transaction(
    PDO $pdo,
    int $userId,
    int $leaveTypeId,
    string $transactionType,
    float $days,
    float $balanceAfter,
    string $source,
    ?int $referenceId = null,
    ?string $remarks = null,
    ?string $earnedAt = null,
    ?string $expiresAt = null
): void {
    $available = leave_table_columns($pdo, 'leave_transactions');
    $data = [
        'user_id' => $userId,
        'leave_type_id' => $leaveTypeId,
        'transaction_type' => $transactionType,
        'days' => $days,
        'balance_after' => $balanceAfter,
        'source' => $source,
        'reference_id' => $referenceId,
        'remarks' => $remarks,
        'earned_at' => $earnedAt,
        'expires_at' => $expiresAt,
    ];

    if (in_array('created_at', $available, true)) {
        $data['created_at'] = date('Y-m-d H:i:s');
    }

    $data = array_intersect_key($data, array_flip($available));
    $columns = array_keys($data);
    $placeholders = implode(',', array_fill(0, count($columns), '?'));

    $sql = 'INSERT INTO leave_transactions (`' . implode('`,`', $columns) . "`) VALUES ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($data));
}

function leave_change_balance(
    PDO $pdo,
    int $userId,
    int $leaveTypeId,
    float $delta,
    string $transactionType,
    string $source,
    ?string $remarks = null,
    ?int $referenceId = null,
    ?string $earnedAt = null
): float {
    $balanceRow = leave_get_balance_row($pdo, $userId, $leaveTypeId, true);

    if (!$balanceRow) {
        leave_create_balance_row($pdo, $userId, $leaveTypeId);
        $balanceRow = leave_get_balance_row($pdo, $userId, $leaveTypeId, true);
    }

    if (!$balanceRow) {
        throw new RuntimeException('Unable to create leave balance row');
    }

    $newBalance = (float) $balanceRow['balance'] + $delta;
    $type = leave_get_type($pdo, $leaveTypeId);
    $expiresAt = null;

    if (($type['leave_code'] ?? '') === 'CTO' && $delta > 0) {
        $earnedAt = $earnedAt ?: date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', strtotime($earnedAt . ' +1 year'));
    }

    leave_set_balance($pdo, $balanceRow, $userId, $leaveTypeId, $newBalance);

    leave_insert_transaction(
        $pdo,
        $userId,
        $leaveTypeId,
        $transactionType,
        abs($delta),
        $newBalance,
        $source,
        $referenceId,
        $remarks,
        $earnedAt,
        $expiresAt
    );

    return $newBalance;
}

function leave_deduct_for_application(PDO $pdo, array $application, ?string $remarks = null): void {
    $userId = (int) $application['user_id'];
    $leaveTypeId = (int) $application['leave_type_id'];
    $days = (float) $application['days'];
    $applicationId = (int) $application['application_id'];
    $type = leave_get_type($pdo, $leaveTypeId);
    $code = $type['leave_code'] ?? '';

    if (!leave_deducts_balance($type)) {
        return;
    }

    if ($code === 'SL') {
        $slBalance = leave_get_balance($pdo, $userId, $leaveTypeId);
        $slDeduct = min($slBalance, $days);

        if ($slDeduct > 0) {
            leave_change_balance($pdo, $userId, $leaveTypeId, -$slDeduct, 'use', 'application', $remarks ?: 'Approved sick leave', $applicationId);
        }

        $excess = $days - $slDeduct;
        if ($excess > 0) {
            $vl = leave_get_type_by_code($pdo, 'VL');
            $vlBalance = $vl ? leave_get_balance($pdo, $userId, (int) $vl['leave_type_id']) : 0.0;

            if (!$vl || $vlBalance < $excess) {
                throw new RuntimeException('Insufficient SL/VL balance; remaining sick leave must be handled as LWOP');
            }

            leave_change_balance($pdo, $userId, (int) $vl['leave_type_id'], -$excess, 'use', 'application', 'SL excess charged to VL', $applicationId);
        }

        return;
    }

    if ($code === 'EML') {
        $sl = leave_get_type_by_code($pdo, 'SL');
        $vl = leave_get_type_by_code($pdo, 'VL');
        $remaining = $days;

        if ($sl) {
            $slBalance = leave_get_balance($pdo, $userId, (int) $sl['leave_type_id']);
            $slDeduct = min($slBalance, $remaining);
            if ($slDeduct > 0) {
                leave_change_balance($pdo, $userId, (int) $sl['leave_type_id'], -$slDeduct, 'use', 'application', 'Extended maternity leave charged to SL', $applicationId);
                $remaining -= $slDeduct;
            }
        }

        if ($remaining > 0 && $vl) {
            $vlBalance = leave_get_balance($pdo, $userId, (int) $vl['leave_type_id']);
            if ($vlBalance < $remaining) {
                throw new RuntimeException('Insufficient SL/VL balance for paid extended maternity leave');
            }
            leave_change_balance($pdo, $userId, (int) $vl['leave_type_id'], -$remaining, 'use', 'application', 'Extended maternity leave charged to VL', $applicationId);
        }

        return;
    }

    $balance = leave_get_balance($pdo, $userId, $leaveTypeId);
    if ($balance < $days) {
        throw new RuntimeException('Insufficient leave balance');
    }

    leave_change_balance($pdo, $userId, $leaveTypeId, -$days, 'use', 'application', $remarks ?: 'Approved leave application', $applicationId);
}

function leave_update_application(PDO $pdo, int $applicationId, array $fields): void {
    $available = leave_table_columns($pdo, 'leave_applications');
    $fields = array_intersect_key($fields, array_flip($available));

    if (!$fields) {
        return;
    }

    $sets = [];
    $values = [];

    foreach ($fields as $column => $value) {
        if ($value === '__NOW__') {
            $sets[] = "`$column` = NOW()";
            continue;
        }

        $sets[] = "`$column` = ?";
        $values[] = $value;
    }

    $values[] = $applicationId;
    $stmt = $pdo->prepare('UPDATE leave_applications SET ' . implode(', ', $sets) . ' WHERE application_id = ?');
    $stmt->execute($values);
}

function leave_get_user_personnel_type(PDO $pdo, int $userId): string {
    $stmt = $pdo->prepare("
        SELECT p.position_category
        FROM sdopang1_user u
        LEFT JOIN sdopang1_position p ON p.position_id = u.position_id
        WHERE u.user_id = ?
    ");
    $stmt->execute([$userId]);
    $value = $stmt->fetchColumn();

    return strtolower(trim((string) $value));
}
