<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/opcrf_helpers.php';

require_login();
require_division_opcrf_validator($pdo);
$action = $_POST['action'] ?? '';

try {
    if ($action === 'add') {
        if ((int) ($_SESSION['role_id'] ?? 0) !== 1) {
            opcrf_json(['status' => 'error', 'message' => 'Only admin can create OPCRF records from the validator module'], 403);
        }

        $officeId = filter_input(INPUT_POST, 'office_id', FILTER_VALIDATE_INT);
        $title = trim($_POST['title'] ?? '');
        $schoolYear = trim($_POST['school_year'] ?? '');
        $quarter = trim($_POST['quarter'] ?? '');
        $datePrepared = $_POST['date_prepared'] ?: null;
        $remarks = trim($_POST['remarks'] ?? '');

        if (!$officeId || $title === '' || $schoolYear === '' || $quarter === '') {
            opcrf_json(['status' => 'error', 'message' => 'Office, title, school year, and quarter are required'], 422);
        }

        $pdfPath = null;
        $excelPath = null;
        $folder = 'uploads/opcrf/' . date('Y') . '/';

        if (!empty($_FILES['uploaded_pdf']['name'])) {
            $pdf = opcrf_upload_file($_FILES['uploaded_pdf'], $folder, ['pdf']);
            $pdfPath = $pdf['file_path'];
        }

        if (!empty($_FILES['uploaded_excel']['name'])) {
            $excel = opcrf_upload_file($_FILES['uploaded_excel'], $folder, ['xls', 'xlsx']);
            $excelPath = $excel['file_path'];
        }

        $stmt = $pdo->prepare("
            INSERT INTO sdopang1_opcrf
                (office_id, title, school_year, quarter, prepared_by, date_prepared, remarks, uploaded_pdf, uploaded_excel, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $officeId,
            $title,
            $schoolYear,
            $quarter,
            $_SESSION['user_id'],
            $datePrepared,
            $remarks ?: null,
            $pdfPath,
            $excelPath,
            $_SESSION['user_id'],
        ]);

        $opcrfId = (int) $pdo->lastInsertId();
        opcrf_log($pdo, $opcrfId, 'Created OPCRF', $remarks ?: null);
        opcrf_json(['status' => 'success', 'opcrf_id' => $opcrfId]);
    }

    if ($action === 'status') {
        $opcrfId = filter_input(INPUT_POST, 'opcrf_id', FILTER_VALIDATE_INT);
        $status = $_POST['status'] ?? '';
        $remarks = trim($_POST['remarks'] ?? '');

        if (!$opcrfId || !in_array($status, ['Draft','For Review','Reviewed','Approved','Returned'], true)) {
            opcrf_json(['status' => 'error', 'message' => 'Invalid status request'], 422);
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

        $values[] = $opcrfId;
        $stmt = $pdo->prepare('UPDATE sdopang1_opcrf SET ' . implode(', ', $sets) . ' WHERE opcrf_id = ?');
        $stmt->execute($values);

        opcrf_log($pdo, $opcrfId, 'Status changed to ' . $status, $remarks ?: null);
        opcrf_json(['status' => 'success']);
    }

    if ($action === 'rating') {
        $opcrfId = filter_input(INPUT_POST, 'opcrf_id', FILTER_VALIDATE_INT);
        $rating = filter_input(INPUT_POST, 'overall_rating', FILTER_VALIDATE_FLOAT);

        if (!$opcrfId || $rating === false) {
            opcrf_json(['status' => 'error', 'message' => 'Rating is required'], 422);
        }

        $stmt = $pdo->prepare("UPDATE sdopang1_opcrf SET overall_rating = ? WHERE opcrf_id = ?");
        $stmt->execute([$rating, $opcrfId]);
        opcrf_log($pdo, $opcrfId, 'Updated overall rating', 'Rating: ' . $rating);
        opcrf_json(['status' => 'success']);
    }

    opcrf_json(['status' => 'error', 'message' => 'Invalid action'], 422);
} catch (Throwable $e) {
    opcrf_json(['status' => 'error', 'message' => $e->getMessage()], 422);
}


