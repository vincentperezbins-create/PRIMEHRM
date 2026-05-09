<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

$userModel = new User($pdo);
require_login();
require_role([3]);
$currentUser = $userModel->getUserById($_SESSION['user_id']);
$schoolId = $currentUser['school_id'] ?? null;

header('Content-Type: application/json');

try {
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
                ELSE COALESCE(lb.balance, 0)
            END AS balance,
            TRIM(CONCAT(u.first_name, ' ', COALESCE(NULLIF(u.middle_name, ''), ''), IF(u.middle_name IS NULL OR u.middle_name = '', '', ' '), u.last_name)) AS name,
            lt.leave_name
        FROM leave_types lt
        JOIN sdopang1_user u ON u.user_id = ?
        LEFT JOIN leave_balances lb
            ON lb.user_id = u.user_id
           AND lb.leave_type_id = lt.leave_type_id
        WHERE lt.is_active = 1
        ORDER BY lt.leave_name ASC
    ");
    $stmt->execute([$userId]);

    echo json_encode(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
    echo json_encode(['data' => [], 'error' => $e->getMessage()]);
}
