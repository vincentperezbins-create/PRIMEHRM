<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/form6_generator.php';

require_login();
require_role([1, 2, 3, 4, 5, 6, 7]);

$applicationId = filter_input(INPUT_GET, 'application_id', FILTER_VALIDATE_INT);

if (!$applicationId) {
    http_response_code(422);
    exit('Invalid leave application.');
}

try {
    $application = form6_application_row($pdo, $applicationId);
    $currentUserId = (int) $_SESSION['user_id'];
    $currentRoleId = (int) $_SESSION['role_id'];

    if ((int) $application['user_id'] !== $currentUserId && !in_array($currentRoleId, [1, 2], true)) {
        http_response_code(403);
        exit('Access denied.');
    }

    $tmpFile = tempnam(sys_get_temp_dir(), 'form6_');
    if ($tmpFile === false) {
        throw new RuntimeException('Unable to create temporary file.');
    }

    $xlsxFile = $tmpFile . '.xlsx';
    rename($tmpFile, $xlsxFile);
    form6_populate_xlsx(form6_template_path(), $xlsxFile, $application, $pdo);

    $filename = form6_safe_filename('Form6_' . form6_full_name($application) . '_' . $applicationId) . '.xlsx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($xlsxFile));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    readfile($xlsxFile);
    unlink($xlsxFile);
    exit;
} catch (Throwable $e) {
    if (!headers_sent()) {
        http_response_code(500);
    }

    exit($e->getMessage());
}

