<?php
require_once __DIR__ . '/../core/db.php';

$id = $_GET['id'];

$row = $pdo->prepare("SELECT * FROM sdopang1schoollist WHERE schoolID=?");
$row->execute([$id]);
$row = $row->fetch();

$districts = $pdo->query("SELECT * FROM sdopang1_district")->fetchAll();
$users = $pdo->query("SELECT user_id, first_name, last_name FROM sdopang1_user")->fetchAll();
?>

<form id="updateSchoolForm">
<input type="hidden" name="action" value="update">
<input type="hidden" name="schoolID" value="<?= $row['schoolID'] ?>">

<input value="<?= $row['schoolID'] ?>" class="form-control mb-2" disabled>

<input name="schoolname" value="<?= $row['schoolname'] ?>" class="form-control mb-2">

<select name="district" class="form-control mb-2">
<?php foreach($districts as $d): ?>
<option value="<?= $d['districtID'] ?>" <?= $d['districtID']==$row['district']?'selected':'' ?>>
<?= $d['district_name'] ?>
</option>
<?php endforeach; ?>
</select>

<input name="schooladdress" value="<?= $row['schooladdress'] ?>" class="form-control mb-2">

<select name="principalID" class="form-control mb-2">
<?php foreach($users as $u): ?>
<option value="<?= $u['user_id'] ?>" <?= $u['user_id']==$row['principalID']?'selected':'' ?>>
<?= $u['first_name'].' '.$u['last_name'] ?>
</option>
<?php endforeach; ?>
</select>

<button class="btn btn-primary">Update</button>
</form>