<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

$userModel = new User($pdo);
require_login();
require_role([3]);
$currentUser = $userModel->getUserById($_SESSION['user_id']);
$schoolId = $currentUser['school_id'] ?? null;

header('Content-Type: application/json');

$year = $_GET['year'] ?? date('Y');
$userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);

if (!$userId) {
    echo json_encode(['data' => []]);
    exit;
}

$scope = $pdo->prepare("SELECT COUNT(*) FROM sdopang1_user WHERE user_id = ? AND school_id = ?");
$scope->execute([$userId, $schoolId]);

if ((int) $scope->fetchColumn() === 0) {
    echo json_encode(['data' => []]);
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
    $stmt->execute([$userId, $type['leave_type_id'], $year]);
    $balances[$type['leave_type_id']] = (float) $stmt->fetchColumn();
}

$stmt = $pdo->prepare("
    SELECT *
    FROM leave_transactions
    WHERE user_id = ? AND YEAR(created_at) = ?
    ORDER BY created_at ASC
");
$stmt->execute([$userId, $year]);

$data = [];

foreach ($stmt as $row) {
    $date = date('Y-m-d', strtotime($row['created_at']));

    if (!isset($data[$date])) {
        $data[$date] = ['date' => $date];

        foreach ($types as $type) {
            $code = $type['leave_code'];
            $data[$date][$code . '_earn'] = 0;
            $data[$date][$code . '_used'] = 0;
            $data[$date][$code . '_bal'] = number_format($balances[$type['leave_type_id']], 3);
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
    $opening = ['date' => 'Opening Balance'];

    foreach ($types as $type) {
        $code = $type['leave_code'];
        $balance = (float) ($balances[$type['leave_type_id']] ?? 0);
        $opening[$code . '_earn'] = 0;
        $opening[$code . '_used'] = 0;
        $opening[$code . '_bal'] = number_format($balance, 3);
    }

    $data[] = $opening;
}

echo json_encode(['data' => array_values($data)]);
