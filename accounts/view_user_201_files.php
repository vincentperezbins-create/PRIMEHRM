<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$user_id = $_SESSION['user_id'];

if (!$id) {
    die("Invalid request");
}

$stmt = $pdo->prepare("
    SELECT * FROM sdopang1_documents 
    WHERE document_id = ? AND user_id = ?
");
$stmt->execute([$id, $user_id]);
$file = $stmt->fetch();

if (!$file) {
    die("Access denied");
}
?>

<div class="mb-3">
    <p class="text-700 mb-1">File</p>
    <h6 class="mb-0"><?= htmlspecialchars($file['file_name']) ?></h6>
</div>

<?php
$filePath = $file['file_path'];
$safeFilePath = htmlspecialchars($filePath, ENT_QUOTES, 'UTF-8');
$fileExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
?>

<?php if ($fileExt === 'pdf'): ?>
    <div class="prime-file-preview">
        <iframe src="<?= $safeFilePath ?>"></iframe>
    </div>
<?php else: ?>
    <div class="prime-empty-state mb-3">Preview is not available for this file type.</div>
    <a class="btn btn-download" href="<?= $safeFilePath ?>" target="_blank">Download File</a>
<?php endif; ?>
