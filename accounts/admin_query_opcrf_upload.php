<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/opcrf_helpers.php';

require_login();
require_role([1]);

try {
    $opcrfId = filter_input(INPUT_POST, 'opcrf_id', FILTER_VALIDATE_INT);
    $indicatorId = filter_input(INPUT_POST, 'indicator_id', FILTER_VALIDATE_INT) ?: null;

    if (!$opcrfId || empty($_FILES['mov_file']['name'])) {
        throw new RuntimeException('OPCRF and file are required');
    }

    $folder = 'uploads/opcrf/mov/' . $opcrfId . '/';
    $upload = opcrf_upload_file($_FILES['mov_file'], $folder, ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx']);

    $stmt = $pdo->prepare("
        INSERT INTO sdopang1_opcrf_movs
            (opcrf_id, indicator_id, file_name, file_path, file_type, file_size, uploaded_by)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $opcrfId,
        $indicatorId,
        $upload['file_name'],
        $upload['file_path'],
        $upload['file_type'],
        $upload['file_size'],
        $_SESSION['user_id'],
    ]);

    opcrf_log($pdo, $opcrfId, 'Uploaded MOV', $upload['file_name']);
    $_SESSION['success_message'] = 'MOV uploaded successfully.';
    header('Location: admin_view_opcrf.php?id=' . urlencode((string) $opcrfId));
    exit;
} catch (Throwable $e) {
    die($e->getMessage());
}
