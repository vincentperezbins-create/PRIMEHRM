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
    $stmt = $pdo->prepare("
        SELECT
            la.application_id,
            la.user_id,
            la.leave_type_id,
            la.date_from,
            la.date_to,
            la.days,
            la.status,
            la.created_at,
            u.employeeID AS employee_id,
            TRIM(CONCAT(u.first_name, ' ', COALESCE(NULLIF(u.middle_name, ''), ''), IF(u.middle_name IS NULL OR u.middle_name = '', '', ' '), u.last_name)) AS name,
            lt.leave_name,
            CONCAT(DATE_FORMAT(la.date_from, '%b %d, %Y'), ' - ', DATE_FORMAT(la.date_to, '%b %d, %Y')) AS date_range
        FROM leave_applications la
        JOIN sdopang1_user u ON la.user_id = u.user_id
        JOIN leave_types lt ON la.leave_type_id = lt.leave_type_id
        WHERE u.school_id = ?
        ORDER BY la.created_at DESC
    ");
    $stmt->execute([$schoolId]);

    echo json_encode(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
    echo json_encode(['data' => [], 'error' => $e->getMessage()]);
}
