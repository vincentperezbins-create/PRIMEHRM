<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/leave_helpers.php';

require_login();
require_role([1]);

header('Content-Type: application/json');

function leave_type_bool(string $key): int
{
    return isset($_POST[$key]) ? 1 : 0;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Invalid request');
    }

    $action = $_POST['action'] ?? '';
    if (!in_array($action, ['add', 'update'], true)) {
        throw new RuntimeException('Invalid action');
    }

    $leaveTypeId = filter_input(INPUT_POST, 'leave_type_id', FILTER_VALIDATE_INT);
    $leaveCode = strtoupper(trim((string) ($_POST['leave_code'] ?? '')));
    $leaveName = trim((string) ($_POST['leave_name'] ?? ''));
    $personnelType = trim((string) ($_POST['personnel_type'] ?? 'both'));
    $monthlyRate = filter_input(INPUT_POST, 'monthly_rate', FILTER_VALIDATE_FLOAT);
    $maxPerYearRaw = trim((string) ($_POST['max_per_year'] ?? ''));
    $expiryType = trim((string) ($_POST['expiry_type'] ?? 'none'));
    $expiryDaysRaw = trim((string) ($_POST['expiry_days'] ?? ''));
    $minUsageDaysRaw = trim((string) ($_POST['min_usage_days'] ?? '0'));

    if ($leaveCode === '' || $leaveName === '') {
        throw new RuntimeException('Leave code and leave name are required');
    }

    if (!preg_match('/^[A-Z0-9_-]{1,20}$/', $leaveCode)) {
        throw new RuntimeException('Leave code may only contain letters, numbers, dash, or underscore');
    }

    if (!in_array($personnelType, ['teaching', 'non-teaching', 'both'], true)) {
        throw new RuntimeException('Invalid personnel type');
    }

    if (!in_array($expiryType, ['none', 'end_of_year', 'fixed_days'], true)) {
        throw new RuntimeException('Invalid expiry type');
    }

    $monthlyRate = $monthlyRate === false ? 0 : (float) $monthlyRate;
    $maxPerYear = $maxPerYearRaw === '' ? null : (float) $maxPerYearRaw;
    $expiryDays = $expiryDaysRaw === '' ? null : (int) $expiryDaysRaw;
    $minUsageDays = $minUsageDaysRaw === '' ? 0 : (int) $minUsageDaysRaw;
    $hasExpiry = leave_type_bool('has_expiry');

    if ($expiryType === 'fixed_days' && (!$expiryDays || $expiryDays < 1)) {
        throw new RuntimeException('Expiry days is required when expiry type is Fixed Days');
    }

    if ($expiryType === 'none') {
        $hasExpiry = 0;
        $expiryDays = null;
    } else {
        $hasExpiry = 1;
    }

    $duplicateSql = "SELECT leave_type_id FROM leave_types WHERE leave_code = ?";
    $duplicateParams = [$leaveCode];
    if ($action === 'update') {
        if (!$leaveTypeId) {
            throw new RuntimeException('Leave type is required');
        }
        $duplicateSql .= " AND leave_type_id <> ?";
        $duplicateParams[] = $leaveTypeId;
    }
    $duplicate = $pdo->prepare($duplicateSql);
    $duplicate->execute($duplicateParams);
    if ($duplicate->fetchColumn()) {
        throw new RuntimeException('Leave code already exists');
    }

    $data = [
        'leave_code' => $leaveCode,
        'leave_name' => $leaveName,
        'personnel_type' => $personnelType,
        'is_monthly_accrual' => leave_type_bool('is_monthly_accrual'),
        'monthly_rate' => $monthlyRate,
        'is_credit_based' => leave_type_bool('is_credit_based'),
        'max_per_year' => $maxPerYear,
        'has_expiry' => $hasExpiry,
        'expiry_type' => $expiryType,
        'expiry_days' => $expiryDays,
        'requires_min_usage' => leave_type_bool('requires_min_usage'),
        'min_usage_days' => $minUsageDays,
        'is_monetizable' => leave_type_bool('is_monetizable'),
        'is_active' => leave_type_bool('is_active'),
    ];

    $available = leave_table_columns($pdo, 'leave_types');
    $data = array_intersect_key($data, array_flip($available));

    if ($action === 'add') {
        $columns = array_keys($data);
        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $stmt = $pdo->prepare('INSERT INTO leave_types (`' . implode('`,`', $columns) . "`) VALUES ($placeholders)");
        $stmt->execute(array_values($data));
    } else {
        $sets = [];
        $values = [];
        foreach ($data as $column => $value) {
            $sets[] = "`$column` = ?";
            $values[] = $value;
        }
        $values[] = $leaveTypeId;
        $stmt = $pdo->prepare('UPDATE leave_types SET ' . implode(', ', $sets) . ' WHERE leave_type_id = ?');
        $stmt->execute($values);
    }

    echo json_encode(['status' => 'success']);
} catch (Throwable $e) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
