<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

require_login();
require_role([1]);

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("
        SELECT *
        FROM leave_types
        ORDER BY leave_code, leave_name
    ");

    $data = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $id = (int) $row['leave_type_id'];
        $expiry = 'None';
        if ((int) ($row['has_expiry'] ?? 0) === 1) {
            $expiryType = str_replace('_', ' ', (string) ($row['expiry_type'] ?? 'none'));
            $expiry = ucwords($expiryType);
            if (($row['expiry_type'] ?? '') === 'fixed_days' && $row['expiry_days'] !== null) {
                $expiry .= ' (' . (int) $row['expiry_days'] . ' days)';
            }
        }

        $data[] = [
            'leave_code' => htmlspecialchars((string) ($row['leave_code'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'leave_name' => htmlspecialchars((string) ($row['leave_name'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'personnel_type' => htmlspecialchars(ucwords(str_replace('-', ' ', (string) ($row['personnel_type'] ?? 'both'))), ENT_QUOTES, 'UTF-8'),
            'is_credit_based' => ((int) ($row['is_credit_based'] ?? 0) === 1) ? 'Yes' : 'No',
            'is_monthly_accrual' => ((int) ($row['is_monthly_accrual'] ?? 0) === 1) ? 'Yes' : 'No',
            'monthly_rate' => number_format((float) ($row['monthly_rate'] ?? 0), 3),
            'max_per_year' => $row['max_per_year'] !== null ? number_format((float) $row['max_per_year'], 3) : '-',
            'expiry' => htmlspecialchars($expiry, ENT_QUOTES, 'UTF-8'),
            'is_active' => ((int) ($row['is_active'] ?? 0) === 1) ? 'Active' : 'Inactive',
            'action' => '<button type="button" class="btn btn-sm btn-primary openLeaveTypeModal" data-action="Update" data-id="' . $id . '">Update</button>',
        ];
    }

    echo json_encode(['data' => $data]);
} catch (Throwable $e) {
    echo json_encode(['data' => [], 'error' => $e->getMessage()]);
}
