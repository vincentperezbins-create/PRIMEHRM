
<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/core/org_units.php';

require_login();
require_role([1]);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    http_response_code(400);
    die("Invalid request");
}

$stmt = $pdo->prepare("
    SELECT 
            *

        FROM sdopang1_user u

        LEFT JOIN sdopang1schoollist s 
            ON s.schoolID = u.school_id

        LEFT JOIN sdopang1_district d 
            ON d.districtID = s.district

        LEFT JOIN sdopang1_cong c 
            ON c.cong_name = s.cong

        LEFT JOIN sdopang1_position p
            ON p.position_id = u.position_id
    WHERE u.user_id = ?
");

$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// ✅ GET DROPDOWN DATA (CORRECT WAY)
$stmt2 = $pdo->query("SELECT * FROM sdopang1schoollist order by schoolname asc");
$schools = $stmt2->fetchAll(PDO::FETCH_ASSOC);

$stmt3 = $pdo->query("SELECT * FROM sdopang1_position order by position_title asc");
$positions = $stmt3->fetchAll(PDO::FETCH_ASSOC);

$roles = $pdo->query("SELECT role_id, role_name FROM sdopang1_roles ORDER BY role_id")->fetchAll(PDO::FETCH_ASSOC);
$offices = $pdo->query("SELECT office_id, office_name FROM sdopang1_offices WHERE status = 'Active' ORDER BY office_name")->fetchAll(PDO::FETCH_ASSOC);
$divisionUnits = org_division_units($pdo);
$officeUnitGroups = org_office_unit_groups($pdo);

if (!$user) {
    http_response_code(404);
    die("User not found");
}
?>

<form method="POST" action="admin_query_user.php">

<input type="hidden" name="token" value="<?= htmlspecialchars(generateToken()) ?>">
<input type="hidden" name="redirect_to" value="admin_users_list.php">
<input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">

<!-- NAME -->
<div class="mb-3">
  <label>Firstname</label>
  <input name="first_name" class="form-control" type="text" value="<?= htmlspecialchars($user['first_name']) ?>">
</div>

<div class="mb-3">
  <label>Middlename</label>
  <input name="middle_name" class="form-control" type="text" value="<?= htmlspecialchars($user['middle_name']) ?>">
</div>

<div class="mb-3">
  <label>Lastname</label>
  <input name="last_name" class="form-control" type="text" value="<?= htmlspecialchars($user['last_name']) ?>">
</div>

<!-- BASIC INFO -->
<div class="mb-3">
  <label>Age</label>
  <input name="age" class="form-control" type="text" value="<?= htmlspecialchars($user['age']) ?>">
</div>

<div class="mb-3">
  <label>Sex</label>
  <input name="sex" class="form-control" type="text" value="<?= htmlspecialchars($user['sex']) ?>">
</div>

<div class="mb-3">
  <label>Email</label>
  <input name="email" class="form-control" type="text" value="<?= htmlspecialchars($user['email']) ?>">
</div>

<div class="mb-3">
  <label>Username</label>
  <input name="username" class="form-control" type="text" value="<?= htmlspecialchars($user['username'] ?? '') ?>">
</div>

<div class="mb-3">
  <label>New Password</label>
  <input name="password" class="form-control" type="password" placeholder="Leave blank to keep current password">
</div>

<div class="mb-3">
  <label>User Role</label>
  <select name="role_id" class="form-control">
    <?php foreach ($roles as $role): ?>
      <option value="<?= htmlspecialchars((string) $role['role_id'], ENT_QUOTES, 'UTF-8') ?>" <?= ((string) ($user['role_id'] ?? '') === (string) $role['role_id']) ? 'selected' : '' ?>>
        <?= htmlspecialchars($role['role_name'], ENT_QUOTES, 'UTF-8') ?>
      </option>
    <?php endforeach; ?>
  </select>
</div>

<div class="mb-3">
  <label>Status</label>
  <select name="status" class="form-control">
    <?php foreach (['active' => 'Active', 'inactive' => 'Inactive'] as $status => $label): ?>
      <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" <?= (($user['status'] ?? 'active') === $status) ? 'selected' : '' ?>>
        <?= htmlspecialchars($label) ?>
      </option>
    <?php endforeach; ?>
  </select>
</div>

<div class="mb-3">
  <label>Civil Status</label>
  <input name="civil_status" class="form-control" type="text" value="<?= htmlspecialchars($user['civil_status']) ?>">
</div>

<div class="mb-3">
  <label>Religion</label>
  <input name="religion" class="form-control" type="text" value="<?= htmlspecialchars($user['religion']) ?>">
</div>

<div class="mb-3">
  <label>Region</label>
  <input name="region" class="form-control" type="text" value="<?= htmlspecialchars($user['region']) ?>">
</div>

<!-- POSITION -->
<div class="mb-3">
  <label>Position</label>
 <!--  <input name="position_id" class="form-control" type="text" value="<?= htmlspecialchars($user['position_id']) ?>"> -->


<select name="position_id" class="form-control">
<?php foreach ($positions as $p): ?>
    <option value="<?= $p['position_id'] ?>"
        <?= ($p['position_id'] == $user['position_id']) ? 'selected' : '' ?>>
        <?= $p['position_title'] ?>
    </option>
<?php endforeach; ?>
</select>
</div>

<div class="mb-3">
  <label>Form 6 Unit</label>
  <select name="division_unit_id" class="form-control">
    <?php foreach ($divisionUnits as $divisionUnit): ?>
      <option value="<?= htmlspecialchars((string) $divisionUnit['division_unit_id'], ENT_QUOTES, 'UTF-8') ?>" <?= ((string) ($user['division_unit_id'] ?? '') === (string) $divisionUnit['division_unit_id']) ? 'selected' : '' ?>>
        <?= htmlspecialchars($divisionUnit['unit_name'], ENT_QUOTES, 'UTF-8') ?>
      </option>
    <?php endforeach; ?>
  </select>
</div>

<div class="mb-3">
  <label>Office Unit</label>
  <select name="office_unit_id" class="form-control">
    <option value="">No office unit assigned</option>
    <?php foreach ($officeUnitGroups as $group): ?>
      <?php if ($group['items']): ?>
        <optgroup label="<?= htmlspecialchars($group['label'], ENT_QUOTES, 'UTF-8') ?>">
          <?php foreach ($group['items'] as $officeUnit): ?>
            <option value="<?= htmlspecialchars((string) $officeUnit['office_unit_id'], ENT_QUOTES, 'UTF-8') ?>"
              data-division-id="<?= htmlspecialchars((string) $officeUnit['division_unit_id'], ENT_QUOTES, 'UTF-8') ?>"
              <?= ((string) $officeUnit['office_unit_id'] === (string) ($user['office_unit_id'] ?? '')) ? 'selected' : '' ?>>
              <?= htmlspecialchars($officeUnit['unit_name'], ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </optgroup>
      <?php endif; ?>
    <?php endforeach; ?>
  </select>
</div>

<div class="mb-3">
  <label>OPCRF Office / Unit</label>
  <select name="office_id" class="form-control">
    <option value="">No OPCRF office assigned</option>
    <?php foreach ($offices as $office): ?>
      <option value="<?= htmlspecialchars((string) $office['office_id'], ENT_QUOTES, 'UTF-8') ?>" <?= ((string) $office['office_id'] === (string) ($user['office_id'] ?? '')) ? 'selected' : '' ?>>
        <?= htmlspecialchars($office['office_name'], ENT_QUOTES, 'UTF-8') ?>
      </option>
    <?php endforeach; ?>
  </select>
</div>

<div class="mb-3">
  <label>Office Role</label>
  <select name="office_role" class="form-control">
    <?php foreach (['Head', 'Assistant Head', 'Staff'] as $officeRole): ?>
      <option value="<?= htmlspecialchars($officeRole, ENT_QUOTES, 'UTF-8') ?>" <?= (($user['office_role'] ?? 'Staff') === $officeRole) ? 'selected' : '' ?>>
        <?= htmlspecialchars($officeRole) ?>
      </option>
    <?php endforeach; ?>
  </select>
</div>

<div class="mb-3">
  <label>Validator Tasks</label>
  <div class="custom-control custom-checkbox">
    <input type="checkbox" class="custom-control-input" id="editCanValidate201" name="can_validate_201" value="1" <?= ((int) ($user['can_validate_201'] ?? 0) === 1) ? 'checked' : '' ?>>
    <label class="custom-control-label" for="editCanValidate201">Validate 201 Files</label>
  </div>
  <div class="custom-control custom-checkbox">
    <input type="checkbox" class="custom-control-input" id="editCanValidateOpcrf" name="can_validate_opcrf" value="1" <?= ((int) ($user['can_validate_opcrf'] ?? 0) === 1) ? 'checked' : '' ?>>
    <label class="custom-control-label" for="editCanValidateOpcrf">Validate OPCRF</label>
  </div>
  <div class="custom-control custom-checkbox">
    <input type="checkbox" class="custom-control-input" id="editCanValidateIpcrf" name="can_validate_ipcrf" value="1" <?= ((int) ($user['can_validate_ipcrf'] ?? 0) === 1) ? 'checked' : '' ?>>
    <label class="custom-control-label" for="editCanValidateIpcrf">Validate IPCRF</label>
  </div>
  <div class="custom-control custom-checkbox">
    <input type="checkbox" class="custom-control-input" id="editCanValidateLeave" name="can_validate_leave" value="1" <?= ((int) ($user['can_validate_leave'] ?? 0) === 1) ? 'checked' : '' ?>>
    <label class="custom-control-label" for="editCanValidateLeave">Validate Leave</label>
  </div>
</div>

<!-- SCHOOL -->
School Info: <?= htmlspecialchars($user['school_id']) ?> - <?= htmlspecialchars($user['schoolname']) ?> - <?= htmlspecialchars($user['district']) ?>

<div class="mb-3">
  <label>School Name</label>
  <select name="school_id" class="form-control">
<?php foreach ($schools as $s): ?>
    <option value="<?= $s['schoolID'] ?>"
        <?= ($s['schoolID'] == $user['school_id']) ? 'selected' : '' ?>>
        <?= $s['schoolname'] ?>
    </option>
<?php endforeach; ?>
</select>
</div>





<!-- EDUCATION -->
<div class="mb-3">
  <label>Educational Background</label>
  <input name="educational_background" class="form-control" type="text" value="<?= htmlspecialchars($user['educational_background']) ?>">
</div>

<div class="mb-3">
  <label>Grade Level Taught</label>
  <input name="grade_level_taught" class="form-control" type="text" value="<?= htmlspecialchars($user['grade_level_taught']) ?>">
</div>

<div class="mb-3">
  <label>Specialization</label>
  <input name="specialization" class="form-control" type="text" value="<?= htmlspecialchars($user['specialization']) ?>">
</div>

<div class="mb-3">
  <label>Actual Subject Taught</label>
  <input name="actual_subjects_taught" class="form-control" type="text" value="<?= htmlspecialchars($user['actual_subjects_taught']) ?>">
</div>

<div class="mb-3">
  <label>Years in Current Position</label>
  <input name="years_in_current_position" class="form-control" type="text" value="<?= htmlspecialchars($user['years_in_current_position']) ?>">
</div>

<!-- IDs -->
<div class="mb-3">
  <label>Employee ID</label>
  <input name="employeeID" class="form-control" type="text" value="<?= htmlspecialchars($user['employeeID']) ?>">
</div>

<div class="mb-3">
  <label>TIN</label>
  <input name="tin" class="form-control" type="text" value="<?= htmlspecialchars($user['tin']) ?>">
</div>

<div class="mb-3">
  <label>PRC License</label>
  <input name="prc_license_number" class="form-control" type="text" value="<?= htmlspecialchars($user['prc_license_number']) ?>">
</div>

<!-- SUBMIT -->
<button type="submit" name="btnupdateuser" class="btn btn-primary">Update</button>

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
