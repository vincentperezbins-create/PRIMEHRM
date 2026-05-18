<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/opcrf_helpers.php';

require_login();
require_ipcrf_validator($pdo);
$action = $_POST['action'] ?? '';

try {
    if ($action === 'status') {
        $ipcrfId = filter_input(INPUT_POST, 'ipcrf_id', FILTER_VALIDATE_INT);
        $status = $_POST['status'] ?? '';
        $remarks = trim($_POST['remarks'] ?? '');

        if (!$ipcrfId || !in_array($status, ['Draft','For Review','Reviewed','Approved','Returned'], true)) {
            opcrf_json(['status' => 'error', 'message' => 'Invalid status request'], 422);
        }

        if (!user_can_validate_ipcrf_record($pdo, $ipcrfId)) {
            opcrf_json(['status' => 'error', 'message' => 'You can only validate IPCRF records within your assigned scope.'], 403);
        }

        $sets = ['status = ?', 'remarks = ?'];
        $values = [$status, $remarks ?: null];

        if ($status === 'Reviewed') {
            $sets[] = 'reviewed_by = ?';
            $sets[] = 'date_reviewed = CURDATE()';
            $values[] = $_SESSION['user_id'];
        }

        if ($status === 'Approved') {
            $sets[] = 'approved_by = ?';
            $sets[] = 'date_approved = CURDATE()';
            $values[] = $_SESSION['user_id'];
        }

        $values[] = $ipcrfId;
        $stmt = $pdo->prepare('UPDATE sdopang1_ipcrf SET ' . implode(', ', $sets) . ' WHERE ipcrf_id = ?');
        $stmt->execute($values);
        opcrf_json(['status' => 'success']);
    }

    if ($action === 'rating') {
        $ipcrfId = filter_input(INPUT_POST, 'ipcrf_id', FILTER_VALIDATE_INT);
        $rating = filter_input(INPUT_POST, 'overall_rating', FILTER_VALIDATE_FLOAT);

        if (!$ipcrfId || $rating === false) {
            opcrf_json(['status' => 'error', 'message' => 'Rating is required'], 422);
        }

        if (!user_can_validate_ipcrf_record($pdo, $ipcrfId)) {
            opcrf_json(['status' => 'error', 'message' => 'You can only validate IPCRF records within your assigned scope.'], 403);
        }

        $stmt = $pdo->prepare("UPDATE sdopang1_ipcrf SET overall_rating = ? WHERE ipcrf_id = ?");
        $stmt->execute([$rating, $ipcrfId]);
        opcrf_json(['status' => 'success']);
    }

    opcrf_json(['status' => 'error', 'message' => 'Invalid action'], 422);
} catch (Throwable $e) {
    opcrf_json(['status' => 'error', 'message' => $e->getMessage()], 422);
}


