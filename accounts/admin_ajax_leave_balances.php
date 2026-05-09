<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

require_login();
require_role([1]); // admin only

header('Content-Type: application/json');

try {
    $userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);

    if (!$userId) {
        echo json_encode(["data" => []]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT 
            u.user_id,
            u.employeeID AS employee_id,
            lt.leave_type_id,
            CASE
                WHEN lt.leave_code = 'CTO' THEN (
                    SELECT COALESCE(SUM(
                        CASE
                            WHEN tx.transaction_type IN ('earn', 'adjust')
                                 AND (tx.expires_at IS NULL OR tx.expires_at >= NOW())
                            THEN tx.days
                            WHEN tx.transaction_type NOT IN ('earn', 'adjust')
                            THEN -tx.days
                            ELSE 0
                        END
                    ), 0)
                    FROM leave_transactions tx
                    WHERE tx.user_id = u.user_id
                      AND tx.leave_type_id = lt.leave_type_id
                )
                ELSE COALESCE(
                    lb.balance,
                    latest.balance_after,
                    (
                        SELECT COALESCE(SUM(
                            CASE
                                WHEN tx.transaction_type IN ('earn', 'adjust') THEN tx.days
                                ELSE -tx.days
                            END
                        ), 0)
                        FROM leave_transactions tx
                        WHERE tx.user_id = u.user_id
                          AND tx.leave_type_id = lt.leave_type_id
                    ),
                    0
                )
            END AS balance,

            TRIM(CONCAT(u.first_name, ' ', COALESCE(NULLIF(u.middle_name, ''), ''), IF(u.middle_name IS NULL OR u.middle_name = '', '', ' '), u.last_name)) AS name,
            lt.leave_code,
            lt.leave_name,

            s.schoolname

        FROM leave_types lt
        JOIN sdopang1_user u ON u.user_id = ?
        LEFT JOIN (
            SELECT t.user_id, t.leave_type_id, t.balance_after
            FROM leave_transactions t
            JOIN (
                SELECT user_id, leave_type_id, MAX(transaction_id) AS latest_transaction_id
                FROM leave_transactions
                GROUP BY user_id, leave_type_id
            ) x ON x.latest_transaction_id = t.transaction_id
        ) latest ON latest.user_id = u.user_id
                 AND latest.leave_type_id = lt.leave_type_id
        LEFT JOIN leave_balances lb
            ON lb.user_id = u.user_id
           AND lb.leave_type_id = lt.leave_type_id
        LEFT JOIN sdopang1schoollist s ON u.school_id = s.schoolID

        WHERE lt.is_active = 1
        ORDER BY lt.leave_name ASC
    ");
    $stmt->execute([$userId]);

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "data" => $data
    ]);

} catch (Exception $e) {

    echo json_encode([
        "data" => [],
        "error" => $e->getMessage()
    ]);
}
