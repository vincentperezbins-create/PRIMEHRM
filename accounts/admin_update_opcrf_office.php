<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

require_login();
require_role([1]);

$officeId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$officeId) {
    die('Invalid request');
}

$stmt = $pdo->prepare("SELECT * FROM sdopang1_offices WHERE office_id = ?");
$stmt->execute([$officeId]);
$office = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$office) {
    die('Office not found');
}

$users = $pdo->query("SELECT user_id, first_name, last_name FROM sdopang1_user ORDER BY last_name, first_name LIMIT 500")->fetchAll(PDO::FETCH_ASSOC);
$offices = $pdo->prepare("SELECT office_id, office_name FROM sdopang1_offices WHERE status = 'Active' AND office_id <> ? ORDER BY office_name");
$offices->execute([$officeId]);
$offices = $offices->fetchAll(PDO::FETCH_ASSOC);
$schools = $pdo->query("SELECT schoolID, schoolname FROM sdopang1schoollist ORDER BY schoolname")->fetchAll(PDO::FETCH_ASSOC);
?>
<form id="opcrfOfficeForm">
    <input type="hidden" name="action" value="update">
    <input type="hidden" name="office_id" value="<?= htmlspecialchars((string) $office['office_id'], ENT_QUOTES, 'UTF-8') ?>">
    <div class="mb-2">
        <label>Office Name</label>
        <input name="office_name" class="form-control" required value="<?= htmlspecialchars($office['office_name'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="mb-2">
        <label>Office Type</label>
        <input name="office_type" class="form-control" value="<?= htmlspecialchars((string) $office['office_type'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="mb-2">
        <label>Office Category</label>
        <select name="office_category" class="form-control">
            <option value="Division Office" <?= ($office['office_category'] ?? '') === 'Division Office' ? 'selected' : '' ?>>Division Office</option>
            <option value="School" <?= ($office['office_category'] ?? '') === 'School' ? 'selected' : '' ?>>School</option>
        </select>
    </div>
    <div class="mb-2">
        <label>Linked School</label>
        <select name="school_id" class="form-control">
            <option value="">No linked school</option>
            <?php foreach ($schools as $school): ?>
                <option value="<?= htmlspecialchars((string) $school['schoolID'], ENT_QUOTES, 'UTF-8') ?>" <?= (string) ($office['school_id'] ?? '') === (string) $school['schoolID'] ? 'selected' : '' ?>><?= htmlspecialchars($school['schoolname']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-2">
        <label>Parent Office</label>
        <select name="parent_office_id" class="form-control">
            <option value="">No parent office</option>
            <?php foreach ($offices as $parentOffice): ?>
                <option value="<?= htmlspecialchars((string) $parentOffice['office_id'], ENT_QUOTES, 'UTF-8') ?>" <?= (int) ($office['parent_office_id'] ?? 0) === (int) $parentOffice['office_id'] ? 'selected' : '' ?>><?= htmlspecialchars($parentOffice['office_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-2">
        <label>Office Head / Form 6 Signatory</label>
        <select name="office_head" class="form-control">
            <option value="">No assigned office head</option>
            <?php foreach ($users as $user): ?>
                <option value="<?= htmlspecialchars((string) $user['user_id'], ENT_QUOTES, 'UTF-8') ?>" <?= (int) $office['office_head'] === (int) $user['user_id'] ? 'selected' : '' ?>><?= htmlspecialchars($user['last_name'] . ', ' . $user['first_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <small class="form-text text-muted">Higher than Unit Head. This person is used as the office signatory for Form 6.</small>
    </div>
    <div class="mb-2">
        <label>Unit Head / OPCRF Submitter</label>
        <select name="unit_head" class="form-control">
            <option value="">No assigned unit head</option>
            <?php foreach ($users as $user): ?>
                <option value="<?= htmlspecialchars((string) $user['user_id'], ENT_QUOTES, 'UTF-8') ?>" <?= (int) ($office['unit_head'] ?? 0) === (int) $user['user_id'] ? 'selected' : '' ?>><?= htmlspecialchars($user['last_name'] . ', ' . $user['first_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <small class="form-text text-muted">This person can submit the office/unit OPCRF.</small>
    </div>
    <div class="mb-3">
        <label>Status</label>
        <select name="status" class="form-control">
            <option value="Active" <?= $office['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
            <option value="Inactive" <?= $office['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
    </div>
    <button class="btn btn-primary w-100">Update Office</button>
</form>
