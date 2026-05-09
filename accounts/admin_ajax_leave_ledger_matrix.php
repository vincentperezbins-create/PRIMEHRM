<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

require_login();
require_role([1]);

header('Content-Type: application/json');

$year = $_GET['year'] ?? date('Y');
$user_id = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);

if (!$user_id) {
    echo json_encode(["data" => []]);
    exit;
}

$types = $pdo->query("
    SELECT leave_type_id, leave_code
    FROM leave_types
    WHERE is_active = 1
    ORDER BY leave_code
")->fetchAll(PDO::FETCH_ASSOC);

$typeMap = [];
foreach ($types as $type) {
    $typeMap[$type['leave_type_id']] = $type['leave_code'];
}

function ledger_transaction_remark(array $row, array $typeMap): string
{
    $parts = [];
    $typeCode = $typeMap[$row['leave_type_id']] ?? '';

    if ($typeCode !== '') {
        $parts[] = $typeCode;
    }

    $source = trim((string) ($row['source'] ?? ''));
    if ($source !== '') {
        $sourceLabel = ucwords(str_replace('_', ' ', $source));
        $referenceId = trim((string) ($row['reference_id'] ?? ''));

        if ($referenceId !== '') {
            $sourceLabel .= ' #' . $referenceId;
        }

        $parts[] = $sourceLabel;
    }

    $remarks = trim((string) ($row['remarks'] ?? ''));
    if ($remarks !== '') {
        $parts[] = $remarks;
    }

    return implode(' - ', $parts);
}

$balances = [];
foreach ($types as $type) {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(
            CASE
                WHEN transaction_type IN ('earn', 'adjust') THEN days
                ELSE -days
            END
        ), 0)
        FROM leave_transactions
        WHERE user_id = ? AND leave_type_id = ? AND YEAR(created_at) < ?
    ");
    $stmt->execute([$user_id, $type['leave_type_id'], $year]);
    $balances[$type['leave_type_id']] = (float) $stmt->fetchColumn();
}

$stmt = $pdo->prepare("
    SELECT *
    FROM leave_transactions
    WHERE user_id = ? AND YEAR(created_at) = ?
    ORDER BY created_at ASC
");
$stmt->execute([$user_id, $year]);

$data = [];

foreach ($stmt as $row) {
    $date = date('Y-m-d', strtotime($row['created_at']));

    if (!isset($data[$date])) {
        $data[$date] = ['date' => $date, 'remarks' => '-'];

        foreach ($types as $type) {
            $code = $type['leave_code'];
            $data[$date][$code . '_earn'] = 0;
            $data[$date][$code . '_used'] = 0;
            $data[$date][$code . '_bal'] = number_format($balances[$type['leave_type_id']], 3);
        }
    }

    $remark = ledger_transaction_remark($row, $typeMap);
    if ($remark !== '') {
        if ($data[$date]['remarks'] === '-') {
            $data[$date]['remarks'] = $remark;
        } else {
            $existingRemarks = array_map('trim', explode('<br>', $data[$date]['remarks']));
            if (!in_array($remark, $existingRemarks, true)) {
                $data[$date]['remarks'] .= '<br>' . $remark;
            }
        }
    }

    $code = $typeMap[$row['leave_type_id']] ?? null;
    if (!$code) {
        continue;
    }

    $typeId = $row['leave_type_id'];
    $days = (float) $row['days'];

    if (in_array($row['transaction_type'], ['earn', 'adjust'], true)) {
        $data[$date][$code . '_earn'] += $days;
        $balances[$typeId] += $days;
    } else {
        $data[$date][$code . '_used'] += $days;
        $balances[$typeId] -= $days;
    }

    $data[$date][$code . '_bal'] = number_format($balances[$typeId], 3);
}

if (!$data) {
    $opening = ['date' => 'Opening Balance', 'remarks' => 'Balance before selected year'];

    foreach ($types as $type) {
        $code = $type['leave_code'];
        $balance = (float) ($balances[$type['leave_type_id']] ?? 0);
        $opening[$code . '_earn'] = 0;
        $opening[$code . '_used'] = 0;
        $opening[$code . '_bal'] = number_format($balance, 3);
    }

    $data[] = $opening;
}

echo json_encode([
    "data" => array_values($data)
]);
