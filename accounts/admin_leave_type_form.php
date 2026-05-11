<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

require_login();
require_role([1]);

$leaveTypeId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$type = [
    'leave_type_id' => '',
    'leave_code' => '',
    'leave_name' => '',
    'personnel_type' => 'both',
    'is_monthly_accrual' => 0,
    'monthly_rate' => '0.000',
    'is_credit_based' => 1,
    'max_per_year' => '',
    'has_expiry' => 0,
    'expiry_type' => 'none',
    'expiry_days' => '',
    'requires_min_usage' => 0,
    'min_usage_days' => '0',
    'is_monetizable' => 0,
    'is_active' => 1,
];

if ($leaveTypeId) {
    $stmt = $pdo->prepare("SELECT * FROM leave_types WHERE leave_type_id = ?");
    $stmt->execute([$leaveTypeId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$existing) {
        die('Leave type not found');
    }
    $type = array_merge($type, $existing);
}
?>
<form id="leaveTypeForm">
  <input type="hidden" name="action" value="<?= $leaveTypeId ? 'update' : 'add' ?>">
  <input type="hidden" name="leave_type_id" value="<?= htmlspecialchars((string) $type['leave_type_id'], ENT_QUOTES, 'UTF-8') ?>">

  <div class="row">
    <div class="col-md-3 mb-3">
      <label class="form-label">Leave Code <span class="text-danger">*</span></label>
      <input name="leave_code" class="form-control" maxlength="20" required value="<?= htmlspecialchars((string) $type['leave_code'], ENT_QUOTES, 'UTF-8') ?>" placeholder="VL">
    </div>
    <div class="col-md-9 mb-3">
      <label class="form-label">Leave Name <span class="text-danger">*</span></label>
      <input name="leave_name" class="form-control" maxlength="100" required value="<?= htmlspecialchars((string) $type['leave_name'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Vacation Leave">
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label">Personnel Type</label>
      <select name="personnel_type" class="form-control">
        <?php foreach (['both' => 'Both', 'teaching' => 'Teaching', 'non-teaching' => 'Non-Teaching'] as $value => $label): ?>
          <option value="<?= $value ?>" <?= (string) $type['personnel_type'] === $value ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label">Monthly Rate</label>
      <input name="monthly_rate" type="number" step="0.001" min="0" class="form-control" value="<?= htmlspecialchars((string) $type['monthly_rate'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label">Max Per Year</label>
      <input name="max_per_year" type="number" step="0.001" min="0" class="form-control" value="<?= htmlspecialchars((string) $type['max_per_year'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Leave blank if unlimited">
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label">Expiry Type</label>
      <select name="expiry_type" id="expiryType" class="form-control">
        <?php foreach (['none' => 'None', 'end_of_year' => 'End of Year', 'fixed_days' => 'Fixed Days'] as $value => $label): ?>
          <option value="<?= $value ?>" <?= (string) $type['expiry_type'] === $value ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label">Expiry Days</label>
      <input name="expiry_days" id="expiryDays" type="number" min="0" class="form-control" value="<?= htmlspecialchars((string) $type['expiry_days'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Required for fixed days">
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label">Minimum Usage Days</label>
      <input name="min_usage_days" type="number" min="0" class="form-control" value="<?= htmlspecialchars((string) $type['min_usage_days'], ENT_QUOTES, 'UTF-8') ?>">
    </div>
  </div>

  <div class="row">
    <?php
    $checks = [
        'is_credit_based' => 'Deducts / uses credits',
        'is_monthly_accrual' => 'Earns monthly accrual',
        'has_expiry' => 'Has expiry rule',
        'requires_min_usage' => 'Requires minimum usage',
        'is_monetizable' => 'Can be monetized',
        'is_active' => 'Active',
    ];
    foreach ($checks as $field => $label):
    ?>
      <div class="col-md-4 mb-2">
        <div class="custom-control custom-checkbox">
          <input type="checkbox" class="custom-control-input" id="<?= htmlspecialchars($field, ENT_QUOTES, 'UTF-8') ?>" name="<?= htmlspecialchars($field, ENT_QUOTES, 'UTF-8') ?>" value="1" <?= ((int) ($type[$field] ?? 0) === 1) ? 'checked' : '' ?>>
          <label class="custom-control-label" for="<?= htmlspecialchars($field, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></label>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="alert alert-info mb-3">
    Keep leave codes short and unique. `CTO` is treated specially by the application form for selectable earned credits and AM/PM filing.
  </div>

  <button class="btn btn-primary w-100"><?= $leaveTypeId ? 'Update Leave Type' : 'Save Leave Type' ?></button>
</form>

<script>
(function() {
  const expiryType = document.getElementById('expiryType');
  const expiryDays = document.getElementById('expiryDays');
  function syncExpiryDays() {
    expiryDays.disabled = expiryType.value !== 'fixed_days';
    if (expiryType.value !== 'fixed_days') {
      expiryDays.value = '';
    }
  }
  expiryType.addEventListener('change', syncExpiryDays);
  syncExpiryDays();
})();
</script>
