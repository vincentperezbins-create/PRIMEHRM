<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/org_units.php';

require_login();
require_role([1]);

$token = generateToken();

// dropdowns
$schools = $pdo->query("SELECT schoolID, schoolname FROM sdopang1schoollist order by schoolname")->fetchAll(PDO::FETCH_ASSOC);
$positions = $pdo->query("SELECT position_id, position_title FROM sdopang1_position order by position_title")->fetchAll(PDO::FETCH_ASSOC);
$roles = $pdo->query("SELECT role_id, role_name FROM sdopang1_roles ORDER BY role_id")->fetchAll(PDO::FETCH_ASSOC);
$offices = $pdo->query("SELECT office_id, office_name FROM sdopang1_offices WHERE status = 'Active' ORDER BY office_name")->fetchAll(PDO::FETCH_ASSOC);
$divisionUnits = org_division_units($pdo);
$officeUnitGroups = org_office_unit_groups($pdo);
?>

<form method="POST" action="admin_query_user.php">

<input type="hidden" name="token" value="<?= $token ?>">
<input type="hidden" name="redirect_to" value="admin_users_list.php">

<div class="mb-2">
  <label>First Name</label>
  <input name="first_name" class="form-control" required>
</div>

<div class="mb-2">
  <label>Middle Name</label>
  <input name="middle_name" class="form-control">
</div>

<div class="mb-2">
  <label>Last Name</label>
  <input name="last_name" class="form-control" required>
</div>

<div class="mb-2">
  <label>Email</label>
  <input type="email" name="email" class="form-control" required>
</div>

<div class="mb-2">
  <label>Username</label>
  <input name="username" class="form-control">
</div>

<div class="mb-2">
  <label>Password</label>
  <input type="password" name="password" class="form-control" required>
</div>

<div class="mb-2">
  <label>User Role</label>
  <select name="role_id" class="form-control" required>
    <?php foreach ($roles as $role): ?>
      <option value="<?= htmlspecialchars((string) $role['role_id'], ENT_QUOTES, 'UTF-8') ?>" <?= ((int) $role['role_id'] === 4) ? 'selected' : '' ?>>
        <?= htmlspecialchars($role['role_name'], ENT_QUOTES, 'UTF-8') ?>
      </option>
    <?php endforeach; ?>
  </select>
</div>

<div class="mb-2">
  <label>Status</label>
  <select name="status" class="form-control">
    <option value="active" selected>Active</option>
    <option value="inactive">Inactive</option>
  </select>
</div>

<div class="mb-2">
  <label>School</label>
  <select name="school_id" class="form-control">
    <option value="">No school assigned</option>
    <?php foreach ($schools as $s): ?>
      <option value="<?= $s['schoolID'] ?>"><?= $s['schoolname'] ?></option>
    <?php endforeach; ?>
  </select>
</div>

<div class="mb-2">
  <label>Position</label>
  <select name="position_id" class="form-control" required>
    <option value="">Select</option>
    <?php foreach ($positions as $p): ?>
      <option value="<?= $p['position_id'] ?>"><?= $p['position_title'] ?></option>
    <?php endforeach; ?>
  </select>
</div>

<div class="mb-2">
  <label>Form 6 Unit</label>
  <select name="division_unit_id" class="form-control">
    <?php foreach ($divisionUnits as $unit): ?>
      <option value="<?= htmlspecialchars((string) $unit['division_unit_id'], ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars($unit['unit_name'], ENT_QUOTES, 'UTF-8') ?>
      </option>
    <?php endforeach; ?>
  </select>
</div>

<div class="mb-2">
  <label>Office Unit</label>
  <select name="office_unit_id" class="form-control">
    <option value="">No office unit assigned</option>
    <?php foreach ($officeUnitGroups as $group): ?>
      <?php if ($group['items']): ?>
        <optgroup label="<?= htmlspecialchars($group['label'], ENT_QUOTES, 'UTF-8') ?>">
          <?php foreach ($group['items'] as $officeUnit): ?>
            <option value="<?= htmlspecialchars((string) $officeUnit['office_unit_id'], ENT_QUOTES, 'UTF-8') ?>" data-division-id="<?= htmlspecialchars((string) $officeUnit['division_unit_id'], ENT_QUOTES, 'UTF-8') ?>">
              <?= htmlspecialchars($officeUnit['unit_name'], ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </optgroup>
      <?php endif; ?>
    <?php endforeach; ?>
  </select>
</div>

<div class="mb-2">
  <label>OPCRF Office / Unit</label>
  <select name="office_id" class="form-control">
    <option value="">No OPCRF office assigned</option>
    <?php foreach ($offices as $office): ?>
      <option value="<?= htmlspecialchars((string) $office['office_id'], ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars($office['office_name'], ENT_QUOTES, 'UTF-8') ?>
      </option>
    <?php endforeach; ?>
  </select>
</div>

<div class="mb-2">
  <label>Office Role</label>
  <select name="office_role" class="form-control">
    <option value="Staff">Staff</option>
    <option value="Assistant Head">Assistant Head</option>
    <option value="Head">Head</option>
  </select>
</div>

<div class="mb-2">
  <label>Validator Tasks</label>
  <div class="custom-control custom-checkbox">
    <input type="checkbox" class="custom-control-input" id="addCanValidate201" name="can_validate_201" value="1">
    <label class="custom-control-label" for="addCanValidate201">Validate 201 Files</label>
  </div>
  <div class="custom-control custom-checkbox">
    <input type="checkbox" class="custom-control-input" id="addCanValidateOpcrf" name="can_validate_opcrf" value="1">
    <label class="custom-control-label" for="addCanValidateOpcrf">Validate OPCRF</label>
  </div>
  <div class="custom-control custom-checkbox">
    <input type="checkbox" class="custom-control-input" id="addCanValidateIpcrf" name="can_validate_ipcrf" value="1">
    <label class="custom-control-label" for="addCanValidateIpcrf">Validate IPCRF</label>
  </div>
  <div class="custom-control custom-checkbox">
    <input type="checkbox" class="custom-control-input" id="addCanValidateLeave" name="can_validate_leave" value="1">
    <label class="custom-control-label" for="addCanValidateLeave">Validate Leave</label>
  </div>
</div>

<div class="mt-3">
  <button type="submit" name="btnadduser" class="btn btn-success">Save</button>
</div>

</form>
<script>
(function() {
  const divisionSelect = document.querySelector('select[name="division_unit_id"]');
  const officeSelect = document.querySelector('select[name="office_unit_id"]');
  if (!divisionSelect || !officeSelect) return;

  function filterOfficeUnits() {
    const divisionId = divisionSelect.value;
    Array.from(officeSelect.options).forEach(option => {
      if (!option.value) {
        option.hidden = false;
        return;
      }

      option.hidden = option.dataset.divisionId !== divisionId;
    });

    if (officeSelect.selectedOptions[0]?.hidden) {
      officeSelect.value = '';
    }
  }

  divisionSelect.addEventListener('change', filterOfficeUnits);
  filterOfficeUnits();
})();
</script>
