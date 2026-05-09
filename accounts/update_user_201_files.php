
<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/csrf.php';
require_login();

$documentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$documentId) {
    die("Invalid request");
}

$stmt = $pdo->prepare("
    SELECT document_id
    FROM sdopang1_documents
    WHERE document_id = ? AND user_id = ?
");
$stmt->execute([$documentId, $_SESSION['user_id']]);

if (!$stmt->fetch()) {
    die("Access denied");
}
?>

<form method="POST" enctype="multipart/form-data" action="query.php" class="prime-form">

<input type="hidden" name="token" value="<?= htmlspecialchars(generateToken(), ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" name="redirect_to" value="user_201_tables.php">
<input type="hidden" name="document_id" value="<?= htmlspecialchars((string) $documentId, ENT_QUOTES, 'UTF-8') ?>">

<div class="form-group">
    <label class="required">Replace File</label>
    <input type="file" name="file" class="form-control" required>
    <span class="prime-help">Choose the corrected file to replace your previous upload.</span>
</div>

<div class="prime-form-actions">
    <button type="button" class="btn btn-cancel" data-dismiss="modal">Cancel</button>
    <button type="submit" name="update" class="btn btn-submit">Update</button>
</div>

</form>
