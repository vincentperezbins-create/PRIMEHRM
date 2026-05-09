<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

require_login();
require_role([1]);


$cong=$pdo->query("SELECT * FROM sdopang1_cong")->fetchAll();
?>

<form id="addDistrictForm">
<input type="hidden" name="action" value="add">

<input name="district_name" class="form-control mb-2" placeholder="District Name">

<select name="congID" class="form-control mb-2">
<?php foreach($cong as $c): ?>
<option value="<?= $c['congID'] ?>"><?= $c['cong_name'] ?></option>
<?php endforeach; ?>
</select>

<button class="btn btn-success">Save</button>
</form>