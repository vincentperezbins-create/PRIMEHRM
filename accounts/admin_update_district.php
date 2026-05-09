<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

require_login();
require_role([1]);
$id=$_GET['id'];

$row=$pdo->prepare("SELECT * FROM sdopang1_district WHERE districtID=?");
$row->execute([$id]);
$row=$row->fetch();

$cong=$pdo->query("SELECT * FROM sdopang1_cong")->fetchAll();
?>

<form id="updateDistrictForm">
<input type="hidden" name="action" value="update">
<input type="hidden" name="districtID" value="<?= $row['districtID'] ?>">

<input name="district_name" value="<?= $row['district_name'] ?>" class="form-control mb-2">

<select name="congID" class="form-control mb-2">
<?php foreach($cong as $c): ?>
<option value="<?= $c['congID'] ?>" <?= $c['congID']==$row['congID']?'selected':'' ?>>
<?= $c['cong_name'] ?>
</option>
<?php endforeach; ?>
</select>

<button class="btn btn-primary">Update</button>
</form>