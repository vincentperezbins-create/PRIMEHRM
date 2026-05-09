<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/csrf.php';

$userModel = new User($pdo);
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request");
}

// ✅ CSRF check
if (!isset($_POST['token']) || !verifyToken($_POST['token'])) {
    die("Invalid CSRF token");
}

$user_id = $_SESSION['user_id'];

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== 0) {
    die("File upload error");
}

$file = $_FILES['file'];
$tmp = $file['tmp_name'];
$originalName = $file['name'];
$size = $file['size'];

$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

// ✅ ALLOWED TYPES
$allowed = ['pdf', 'jpg', 'jpeg', 'png'];

if (!in_array($ext, $allowed)) {
    die("Invalid file type");
}

// ✅ MAX SIZE 5MB
if ($size > 5 * 1024 * 1024) {
    die("File too large");
}

// ✅ CREATE FOLDER
$folder = "uploads/user_" . $user_id . "/";
if (!is_dir($folder)) {
    mkdir($folder, 0777, true);
}

// ✅ SAFE NAME
$file_name = uniqid() . "." . $ext;
$file_path = $folder . $file_name;

if (!move_uploaded_file($tmp, $file_path)) {
    die("Upload failed");
}

// 🔄 UPDATE
$saveSuccess = false;
$successMessage = 'Document saved successfully.';

if (isset($_POST['update'])) {

    $document_id = filter_input(INPUT_POST, 'document_id', FILTER_VALIDATE_INT);
    if (!$document_id) {
        die("Invalid request");
    }

    $stmt = $pdo->prepare("
        SELECT doc_type_id, year, remarks
        FROM sdopang1_documents
        WHERE document_id = ? AND user_id = ?
    ");
    $stmt->execute([$document_id, $user_id]);
    $document = $stmt->fetch();

    if (!$document) {
        die("Access denied");
    }

    $saveSuccess = $userModel->createDocument(
        $user_id,
        $document['doc_type_id'],
        $file_name,
        $file_path,
        $document['year'],
        $document['remarks']
    );

    $successMessage = 'Document updated successfully.';

}

// ➕ INSERT
if (isset($_POST['upload'])) {

    $doc_type_id = filter_input(INPUT_POST, 'doc_type_id', FILTER_VALIDATE_INT);
    if (!$doc_type_id) {
        die("Invalid request");
    }

    $year = $_POST['year'];
    $remarks = $_POST['remarks'] ?? null;

    $saveSuccess = $userModel->createDocument(
        $user_id,
        $doc_type_id,
        $file_name,
        $file_path,
        $year,
        $remarks
    );

    $successMessage = 'Document uploaded successfully.';
}

if (!$saveSuccess) {
    die("Save failed");
}

$_SESSION['success_message'] = $successMessage;

$allowedRedirects = [
    'admin_201_tables.php',
    'user_201_tables.php',
];

$redirectTo = $_POST['redirect_to'] ?? 'user_201_tables.php';
if (!in_array($redirectTo, $allowedRedirects, true)) {
    $redirectTo = 'user_201_tables.php';
}

header("Location: " . $redirectTo);
exit;

