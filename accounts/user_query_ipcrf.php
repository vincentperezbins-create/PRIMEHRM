<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/opcrf_helpers.php';

require_login();
require_role([1, 2, 3, 4, 5, 6, 7]);

try {
    $title = trim($_POST['title'] ?? '');
    $schoolYear = trim($_POST['school_year'] ?? '');
    $ratingPeriod = trim($_POST['rating_period'] ?? '');
    $datePrepared = $_POST['date_prepared'] ?: null;
    $targets = trim($_POST['employee_targets'] ?? '');
    $accomplishments = trim($_POST['employee_accomplishments'] ?? '');
    $indicators = trim($_POST['employee_indicators'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');

    if ($title === '' || $schoolYear === '' || $ratingPeriod === '') {
        opcrf_json(['status' => 'error', 'message' => 'Title, school year, and rating period are required'], 422);
    }

    $pdfPath = null;
    $excelPath = null;
    $folder = 'uploads/ipcrf/' . date('Y') . '/';

    if (!empty($_FILES['uploaded_pdf']['name'])) {
        $pdf = opcrf_upload_file($_FILES['uploaded_pdf'], $folder, ['pdf']);
        $pdfPath = $pdf['file_path'];
    }

    if (!empty($_FILES['uploaded_excel']['name'])) {
        $excel = opcrf_upload_file($_FILES['uploaded_excel'], $folder, ['xls', 'xlsx']);
        $excelPath = $excel['file_path'];
    }

    $stmt = $pdo->prepare("
        INSERT INTO sdopang1_ipcrf
            (user_id, title, school_year, rating_period, date_prepared, employee_targets, employee_accomplishments, employee_indicators, remarks, uploaded_pdf, uploaded_excel)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $title,
        $schoolYear,
        $ratingPeriod,
        $datePrepared,
        $targets ?: null,
        $accomplishments ?: null,
        $indicators ?: null,
        $remarks ?: null,
        $pdfPath,
        $excelPath,
    ]);

    opcrf_json(['status' => 'success', 'ipcrf_id' => (int) $pdo->lastInsertId()]);
} catch (Throwable $e) {
    opcrf_json(['status' => 'error', 'message' => $e->getMessage()], 422);
}

