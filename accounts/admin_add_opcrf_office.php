<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

require_login();
require_role([1]);

$users = $pdo->query("SELECT user_id, first_name, last_name FROM sdopang1_user ORDER BY last_name, first_name LIMIT 500")->fetchAll(PDO::FETCH_ASSOC);
$offices = $pdo->query("SELECT office_id, office_name FROM sdopang1_offices WHERE status = 'Active' ORDER BY office_name")->fetchAll(PDO::FETCH_ASSOC);
$schools = $pdo->query("SELECT schoolID, schoolname FROM sdopang1schoollist ORDER BY schoolname")->fetchAll(PDO::FETCH_ASSOC);
?>
<form id="opcrfOfficeForm">
    <input type="hidden" name="action" value="add">
    <div class="mb-2">
        <label>Office Name</label>
        <input name="office_name" class="form-control" required>
    </div>
    <div class="mb-2">
        <label>Office Type</label>
        <input name="office_type" class="form-control" placeholder="Division, Unit, Section, School">
    </div>
    <div class="mb-2">
        <label>Office Category</label>
        <select name="office_category" class="form-control">
            <option value="Division Office">Division Office</option>
            <option value="School">School</option>
        </select>
    </div>
    <div class="mb-2">
        <label>Linked School</label>
        <select name="school_id" class="form-control">
            <option value="">No linked school</option>
            <?php foreach ($schools as $school): ?>
                <option value="<?= htmlspecialchars((string) $school['schoolID'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($school['schoolname']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-2">
        <label>Parent Office</label>
        <select name="parent_office_id" class="form-control">
            <option value="">No parent office</option>
            <?php foreach ($offices as $office): ?>
                <option value="<?= htmlspecialchars((string) $office['office_id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($office['office_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-2">
        <label>Office Head / Form 6 Signatory</label>
        <select name="office_head" class="form-control">
            <option value="">No assigned office head</option>
            <?php foreach ($users as $user): ?>
                <option value="<?= htmlspecialchars((string) $user['user_id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($user['last_name'] . ', ' . $user['first_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <small class="form-text text-muted">Higher than Unit Head. This person is used as the office signatory for Form 6.</small>
    </div>
    <div class="mb-2">
        <label>Unit Head / OPCRF Submitter</label>
        <select name="unit_head" class="form-control">
            <option value="">No assigned unit head</option>
            <?php foreach ($users as $user): ?>
                <option value="<?= htmlspecialchars((string) $user['user_id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($user['last_name'] . ', ' . $user['first_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <small class="form-text text-muted">This person can submit the office/unit OPCRF.</small>
    </div>
    <div class="mb-3">
        <label>Status</label>
        <select name="status" class="form-control">
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
        </select>
    </div>
    <button class="btn btn-primary w-100">Save Office</button>
</form>
