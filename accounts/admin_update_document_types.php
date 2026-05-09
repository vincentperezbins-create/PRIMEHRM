<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
$userModel = new User($pdo);
require_login();
require_role([1]);

$id = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM sdopang1_document_types WHERE doc_type_id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<form id="updateDocForm">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="doc_type_id" value="<?= $row['doc_type_id'] ?>">

    <div class="mb-2">
        <label>Document Name</label>
        <input name="doc_name" value="<?= $row['doc_name'] ?>" class="form-control" required>
    </div>

    <div class="mb-2">
        <label>Required</label>
        <select name="is_required" class="form-control">
            <option value="1" <?= $row['is_required'] ? 'selected' : '' ?>>Required</option>
            <option value="0" <?= !$row['is_required'] ? 'selected' : '' ?>>Optional</option>
        </select>
    </div>

    <button class="btn btn-primary w-100">Update</button>
</form>