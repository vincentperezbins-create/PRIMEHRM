<?php
require_once __DIR__ . '/../core/db.php';
$users = $pdo->query("SELECT user_id, first_name, last_name FROM sdopang1_user ORDER BY first_name")->fetchAll();
$types = $pdo->query("SELECT leave_type_id, leave_name FROM leave_types WHERE is_active=1 ORDER BY leave_name")->fetchAll();
?>

<form id="addLeaveForm">
<input type="hidden" name="action" value="apply">

<div class="mb-2">
    <label>Employee</label>
    <select name="user_id" class="form-control" required>
        <option value="">-- Select --</option>
        <?php foreach($users as $u): ?>
        <option value="<?= $u['user_id'] ?>">
            <?= $u['first_name'].' '.$u['last_name'] ?>
        </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="mb-2">
    <label>Leave Type</label>
    <select name="leave_type_id" class="form-control" required>
        <option value="">-- Select --</option>
        <?php foreach($types as $t): ?>
        <option value="<?= $t['leave_type_id'] ?>">
            <?= $t['leave_name'] ?>
        </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="mb-2">
    <label>Date From</label>
    <input type="date" name="date_from" class="form-control" required>
</div>

<div class="mb-2">
    <label>Date To</label>
    <input type="date" name="date_to" class="form-control" required>
</div>

<div class="mb-2">
    <label>Days</label>
    <input type="number" step="0.5" name="days" class="form-control" required>
</div>

<div class="mb-2">
    <label>Reason</label>
    <textarea name="reason" class="form-control"></textarea>
</div>

<button class="btn btn-success w-100">Submit</button>
</form>