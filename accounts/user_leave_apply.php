<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/core/leave_helpers.php';

$userModel = new User($pdo);
require_login();
require_role([1, 2, 3, 4, 5, 6, 7]);
require_once __DIR__ . '/partials/session.php';
require_once __DIR__ . '/partials/page_info.php';

$userId = (int) $_SESSION['user_id'];
$token = generateToken();
$personnelType = leave_get_user_personnel_type($pdo, $userId);
$typeColumns = leave_table_columns($pdo, 'leave_types');
$typeSql = 'SELECT * FROM leave_types';

if (in_array('is_active', $typeColumns, true)) {
    $typeSql .= ' WHERE is_active = 1';
}

$typeSql .= ' ORDER BY leave_name';
$leaveTypes = $pdo->query($typeSql)->fetchAll(PDO::FETCH_ASSOC);

$leaveTypes = array_values(array_filter($leaveTypes, function ($type) use ($personnelType) {
    if (empty($type['personnel_type'])) {
        return true;
    }

    return in_array(strtolower(trim((string) $type['personnel_type'])), ['both', $personnelType], true);
}));
?>
<!DOCTYPE html>
<html>
 <?php require_once __DIR__ . '/partials/head.php'; ?>
  <body>
   <?php require_once __DIR__ . '/partials/preloader.php'; ?>
    <?php require_once __DIR__ . '/partials/navbar.php'; ?>
    <?php require_once __DIR__ . '/partials/rightsidebar.php'; ?>
    <?php require_once __DIR__ . '/partials/leftsidebar.php'; ?>
    <div class="mobile-menu-overlay"></div>

    <div class="main-container">
      <div class="xs-pd-20-10 pd-ltr-20">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 pb-20">
          <div>
            <h2 class="mb-1">Apply Leave</h2>
            <p class="text-700 mb-0">Submit a leave request for admin review.</p>
          </div>
          <a href="user_leave_history.php" class="btn btn-outline-primary">View History</a>
        </div>

        <?php page_info(
            'What this page does',
            'Submit a leave request for admin review.',
            [
                'The system counts working days from the selected date range.',
                'For credit-based leave, your available balance is checked before submission.'
            ]
        ); ?>

        <div class="card">
          <div class="card-body">
            <form id="leaveForm">
              <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

              <div class="row">
                <div class="col-md-4 mb-3">
                  <label class="form-label">Leave Type</label>
                  <select id="leaveType" name="leave_type_id" class="form-control" required>
                    <option value="">Select leave type</option>
                    <?php foreach ($leaveTypes as $type): ?>
                      <?php $balance = leave_get_balance($pdo, $userId, (int) $type['leave_type_id']); ?>
                      <option
                        value="<?= htmlspecialchars((string) $type['leave_type_id'], ENT_QUOTES, 'UTF-8') ?>"
                        data-balance="<?= htmlspecialchars(number_format($balance, 3, '.', ''), ENT_QUOTES, 'UTF-8') ?>"
                        data-credit="<?= leave_is_credit_based($type) ? '1' : '0' ?>"
                      >
                        <?= htmlspecialchars(($type['leave_code'] ?? '') . ' - ' . $type['leave_name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <small id="balanceHint" class="form-text text-muted">Select a leave type to see your balance.</small>
                </div>

                <div class="col-md-3 mb-3">
                  <label class="form-label">From</label>
                  <input id="dateFrom" type="date" name="date_from" class="form-control" required>
                </div>

                <div class="col-md-3 mb-3">
                  <label class="form-label">To</label>
                  <input id="dateTo" type="date" name="date_to" class="form-control" required>
                </div>

                <div class="col-md-2 mb-3">
                  <label class="form-label">Work Days</label>
                  <input id="daysPreview" type="text" class="form-control" value="0" readonly>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Reason</label>
                <textarea name="reason" class="form-control" rows="4" required></textarea>
              </div>

              <div class="d-flex align-items-center gap-2">
                <button type="submit" class="btn btn-primary">Submit Application</button>
                <span id="formMessage" class="ml-2"></span>
              </div>
            </form>
          </div>
        </div>

        <?php require_once __DIR__ . '/partials/footer.php'; ?>
      </div>
    </div>

    <?php require_once __DIR__ . '/partials/welcomemodal.php'; ?>
    <script src="vendors/scripts/core.js"></script>
    <script src="vendors/scripts/script.min.js"></script>
    <script src="vendors/scripts/process.js"></script>
    <script src="vendors/scripts/layout-settings.js"></script>
    <script>
function countWorkDays(from, to) {
  if (!from || !to) return 0;
  const start = new Date(from + 'T00:00:00');
  const end = new Date(to + 'T00:00:00');
  if (end < start) return 0;
  let days = 0;
  for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
    const day = d.getDay();
    if (day !== 0 && day !== 6) days++;
  }
  return days;
}

function refreshPreview() {
  const selected = $('#leaveType option:selected');
  const balance = selected.data('balance');
  const credit = selected.data('credit');
  const days = countWorkDays($('#dateFrom').val(), $('#dateTo').val());
  $('#daysPreview').val(days);

  if (balance !== undefined && selected.val()) {
    $('#balanceHint').text(credit === 1 ? `Available balance: ${balance} day(s)` : 'This leave type does not deduct credits.');
  } else {
    $('#balanceHint').text('Select a leave type to see your balance.');
  }
}

$('#leaveType, #dateFrom, #dateTo').on('change', refreshPreview);

$('#leaveForm').on('submit', function(e) {
  e.preventDefault();
  $('#formMessage').removeClass('text-success text-danger').text('Submitting...');

  $.post('apply_leave_save.php', $(this).serialize())
    .done(response => {
      if (response.status !== 'success') {
        $('#formMessage').addClass('text-danger').text(response.message || 'Submission failed');
        return;
      }
      const downloadLink = response.form6_url
        ? ` <a class="btn btn-sm btn-outline-success ml-2" href="${response.form6_url}">Download Form 6</a>`
        : '';
      $('#formMessage').addClass('text-success').html(`Submitted successfully.${downloadLink}`);
      this.reset();
      refreshPreview();
    })
    .fail(xhr => {
      const response = xhr.responseJSON || {};
      $('#formMessage').addClass('text-danger').text(response.message || 'Submission failed');
    });
});
</script>
  </body>
</html>

