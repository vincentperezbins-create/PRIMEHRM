
<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/csrf.php';
require_login();

$docTypeId = filter_input(INPUT_GET, 'doc_type_id', FILTER_VALIDATE_INT);
if (!$docTypeId) {
    die("Invalid request");
}
?>

<form method="POST" enctype="multipart/form-data" action="query.php" class="prime-form">

<input type="hidden" name="token" value="<?= htmlspecialchars(generateToken(), ENT_QUOTES, 'UTF-8') ?>">
<input type="hidden" name="redirect_to" value="user_201_tables.php">
<input type="hidden" name="doc_type_id" value="<?= htmlspecialchars((string) $docTypeId, ENT_QUOTES, 'UTF-8') ?>">

<div class="form-group">
    <label class="required">Upload File</label>
    <input type="file" name="file" class="form-control" required>
    <span class="prime-help">Upload the required 201 document. PDF files are recommended for preview.</span>
</div>

<div class="form-group">
    <label class="required">Year</label>
    <input type="text" name="year" class="form-control" value="<?= date('Y') ?>" required>
</div>

<div class="prime-form-actions">
    <button type="button" class="btn btn-cancel" data-dismiss="modal">Cancel</button>
    <button type="submit" name="upload" class="btn btn-submit">Upload</button>
</div>

</form>
