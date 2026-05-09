<?php
require_once __DIR__ . '/../core/db.php';

$districts = $pdo->query("SELECT * FROM sdopang1_district ORDER BY district_name")->fetchAll();
$users = $pdo->query("SELECT user_id, first_name, last_name FROM sdopang1_user")->fetchAll();
?>

<form id="addSchoolForm">
<input type="hidden" name="action" value="add">

<div class="mb-2">
    <input name="schoolID" class="form-control" placeholder="School ID" required>
</div>

<div class="mb-2">
    <input name="schoolname" class="form-control" placeholder="School Name" required>
</div>

<div class="mb-2">
    <select name="district" class="form-control" required>
        <option value="">-- Select District --</option>
        <?php foreach($districts as $d): ?>
        <option value="<?= $d['districtID'] ?>"><?= $d['district_name'] ?></option>
        <?php endforeach; ?>
    </select>
</div>

<div class="mb-2">
    <input name="schooladdress" class="form-control" placeholder="Address">
</div>

<div class="mb-2">
    <select name="principalID" class="form-control">
        <option value="">-- Select Principal --</option>
        <?php foreach($users as $u): ?>
        <option value="<?= $u['user_id'] ?>">
            <?= $u['first_name'].' '.$u['last_name'] ?>
        </option>
        <?php endforeach; ?>
    </select>
</div>

<button class="btn btn-success w-100">Save</button>
</form>