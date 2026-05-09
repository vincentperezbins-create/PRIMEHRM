<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/leave_helpers.php';

require_login();
require_role([1]);

header('Content-Type: application/json');

$hasEarnedAt = leave_has_column($pdo, 'leave_transactions', 'earned_at');
$hasExpiresAt = leave_has_column($pdo, 'leave_transactions', 'expires_at');
$earnedSelect = $hasEarnedAt ? "DATE(t.earned_at) as earned_date," : "NULL as earned_date,";
$expiresSelect = $hasExpiresAt ? "DATE(t.expires_at) as expires_date," : "NULL as expires_date,";

$stmt = $pdo->query("
SELECT 
DATE(t.created_at) as date,
$earnedSelect
$expiresSelect
CONCAT(u.first_name,' ',u.last_name) as employee,
lt.leave_code,
t.transaction_type as type,
t.days,
t.balance_after as balance,
t.source,
t.remarks
FROM leave_transactions t
JOIN sdopang1_user u ON t.user_id = u.user_id
JOIN leave_types lt ON t.leave_type_id = lt.leave_type_id
ORDER BY t.created_at DESC
");

echo json_encode(['data'=>$stmt->fetchAll()]);
