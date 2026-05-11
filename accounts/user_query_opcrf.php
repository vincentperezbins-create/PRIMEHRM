<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/opcrf_helpers.php';

$userModel = new User($pdo);
require_login();
require_role([1, 2, 3, 4, 5, 6, 7]);
$currentUser = $userModel->getUserById($_SESSION['user_id']);

$officeId = $currentUser['office_id'] ?? null;
$officeRole = $currentUser['office_role'] ?? 'Staff';
$isAssignedUnitHead = false;
$isLinkedSchoolHead = false;

$headOfficeStmt = $pdo->prepare("
    SELECT office_id
    FROM sdopang1_offices
    WHERE unit_head = ?
    ORDER BY office_id
    LIMIT 1
");
$headOfficeStmt->execute([$_SESSION['user_id']]);
$headOfficeId = $headOfficeStmt->fetchColumn();

if ($headOfficeId) {
    $officeId = (int) $headOfficeId;
    $officeRole = 'Unit Head';
}

if ($officeId) {
    $officeStmt = $pdo->prepare("
        SELECT *
        FROM sdopang1_offices
        WHERE office_id = ?
        LIMIT 1
    ");
    $officeStmt->execute([$officeId]);
    $office = $officeStmt->fetch(PDO::FETCH_ASSOC);

    if ($office) {
        $isAssignedUnitHead = (int) ($office['unit_head'] ?? 0) === (int) $_SESSION['user_id'];
        $isLinkedSchoolHead = ($office['office_category'] ?? '') === 'School'
            && (string) ($office['school_id'] ?? '') !== ''
            && (string) ($office['school_id'] ?? '') === (string) ($currentUser['school_id'] ?? '')
            && (
                (string) ($currentUser['office_role'] ?? '') === 'Head'
                || (int) ($_SESSION['role_id'] ?? 0) === 3
                || (int) ($office['office_head'] ?? 0) === (int) $_SESSION['user_id']
            );
    }
}

if (!$officeId || (!$isAssignedUnitHead && !$isLinkedSchoolHead)) {
    opcrf_json(['status' => 'error', 'message' => 'Only the assigned unit head or linked school head can file this office/unit OPCRF'], 403);
}

try {
    $title = trim($_POST['title'] ?? '');
    $schoolYear = trim($_POST['school_year'] ?? '');
    $quarter = trim($_POST['quarter'] ?? '');
    $datePrepared = $_POST['date_prepared'] ?: null;
    $remarks = trim($_POST['remarks'] ?? '');

    if ($title === '' || $schoolYear === '' || $quarter === '') {
        opcrf_json(['status' => 'error', 'message' => 'Title, school year, and quarter are required'], 422);
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
            (office_id, title, school_year, quarter, prepared_by, date_prepared, status, remarks, uploaded_pdf, uploaded_excel, created_by)
        VALUES (?, ?, ?, ?, ?, ?, 'For Review', ?, ?, ?, ?)
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
    opcrf_log($pdo, $opcrfId, 'Filed OPCRF', $remarks ?: null);
    opcrf_json(['status' => 'success', 'opcrf_id' => $opcrfId]);
} catch (Throwable $e) {
    opcrf_json(['status' => 'error', 'message' => $e->getMessage()], 422);
}

