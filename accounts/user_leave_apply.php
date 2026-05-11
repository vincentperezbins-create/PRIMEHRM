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

$ctoBatches = [];
foreach ($leaveTypes as $type) {
    if (($type['leave_code'] ?? '') === 'CTO') {
        $ctoBatches[(int) $type['leave_type_id']] = leave_cto_available_batches($pdo, $userId, (int) $type['leave_type_id']);
    }
}
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
                        data-code="<?= htmlspecialchars((string) ($type['leave_code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
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

              <div id="ctoSchedulePanel" class="card mb-3 d-none">
                <div class="card-body">
                  <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-2">
                    <div>
                      <h6 class="mb-1">Leave Dates</h6>
                      <p class="text-700 mb-0">Add leave dates individually or as date ranges. AM/PM half-day entries are available for CTO only.</p>
                    </div>
                    <button type="button" id="addCtoScheduleRow" class="btn btn-sm btn-outline-primary">Add Leave Date</button>
                  </div>
                  <div id="ctoScheduleRows"></div>
                  <small class="form-text text-muted">Examples: May 18-19 and May 22 as separate rows. For CTO, you may also select AM only or PM only.</small>
                </div>
              </div>

              <div id="ctoCreditPanel" class="card mb-3 d-none">
                <div class="card-body">
                  <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-2">
                    <div>
                      <h6 class="mb-1">Available CTO Credits</h6>
                      <p class="text-700 mb-0">Select the CTO earned credits to use. CTO expires one year after it is earned.</p>
                    </div>
                    <strong id="ctoSelectedTotal">Selected: 0.000 day(s)</strong>
                  </div>

                  <?php foreach ($ctoBatches as $leaveTypeId => $batches): ?>
                    <div class="cto-batch-list d-none" data-leave-type-id="<?= htmlspecialchars((string) $leaveTypeId, ENT_QUOTES, 'UTF-8') ?>">
                      <?php foreach ($batches as $batch): ?>
                        <label class="d-flex align-items-start gap-2 border rounded p-2 mb-2 cto-batch-option"
                               data-remaining="<?= htmlspecialchars(number_format((float) $batch['remaining'], 3, '.', ''), ENT_QUOTES, 'UTF-8') ?>"
                               data-expires="<?= htmlspecialchars($batch['expires_date'], ENT_QUOTES, 'UTF-8') ?>">
                          <input type="checkbox"
                                 class="mt-1 cto-credit-checkbox"
                                 name="cto_transaction_ids[]"
                                 value="<?= htmlspecialchars((string) $batch['transaction_id'], ENT_QUOTES, 'UTF-8') ?>">
                          <span>
                            <strong><?= htmlspecialchars(number_format((float) $batch['remaining'], 3), ENT_QUOTES, 'UTF-8') ?> day(s)</strong>
                            <span class="d-block text-700">
                              Earned: <?= htmlspecialchars(date('M d, Y', strtotime($batch['earned_at'])), ENT_QUOTES, 'UTF-8') ?>
                              | Expires: <?= htmlspecialchars(date('M d, Y', strtotime($batch['expires_at'])), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <?php if (trim((string) $batch['remarks']) !== ''): ?>
                              <span class="d-block text-muted"><?= htmlspecialchars((string) $batch['remarks'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                          </span>
                        </label>
                      <?php endforeach; ?>
                      <?php if (!$batches): ?>
                        <div class="alert alert-warning mb-0">No available CTO credits. Expired CTO credits cannot be selected.</div>
                      <?php endif; ?>
                    </div>
                  <?php endforeach; ?>
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

let ctoScheduleIndex = 0;

function ctoRowTemplate(index) {
  return `
    <div class="row align-items-end cto-schedule-row mb-2">
      <div class="col-md-3 mb-2">
        <label class="form-label">Date From</label>
        <input type="date" name="cto_schedule[${index}][date_from]" class="form-control cto-date-from">
      </div>
      <div class="col-md-3 mb-2">
        <label class="form-label">Date To</label>
        <input type="date" name="cto_schedule[${index}][date_to]" class="form-control cto-date-to">
      </div>
      <div class="col-md-4 mb-2">
        <label class="form-label">Period</label>
        <select name="cto_schedule[${index}][period]" class="form-control cto-period">
          <option value="WHOLE">Whole day / date range</option>
          <option value="AM">AM only</option>
          <option value="PM">PM only</option>
        </select>
      </div>
      <div class="col-md-2 mb-2">
        <button type="button" class="btn btn-outline-danger btn-sm w-100 remove-cto-row">Remove</button>
      </div>
    </div>
  `;
}

function addCtoScheduleRow() {
  $('#ctoScheduleRows').append(ctoRowTemplate(ctoScheduleIndex));
  ctoScheduleIndex += 1;
}

function countCtoScheduleDays() {
  let days = 0;

  $('.cto-schedule-row').each(function() {
    const from = $(this).find('.cto-date-from').val();
    const to = $(this).find('.cto-date-to').val() || from;
    const period = $(this).find('.cto-period').val();

    if (!from) return;
    if (period === 'AM' || period === 'PM') {
      days += 0.5;
      return;
    }

    days += countWorkDays(from, to);
  });

  return days;
}

function refreshPreview() {
  const selected = $('#leaveType option:selected');
  const balance = selected.data('balance');
  const credit = selected.data('credit');
  const code = selected.data('code');
  const hasLeaveType = !!selected.val();
  const isCto = code === 'CTO';
  const days = hasLeaveType ? countCtoScheduleDays() : countWorkDays($('#dateFrom').val(), $('#dateTo').val());
  $('#daysPreview').val(days);

  $('#ctoSchedulePanel').toggleClass('d-none', !hasLeaveType);
  $('#dateFrom, #dateTo').prop('required', !hasLeaveType);
  $('.cto-period option[value="AM"], .cto-period option[value="PM"]').prop('disabled', !isCto).toggle(isCto);
  if (!isCto) {
    $('.cto-period').val('WHOLE');
    $('.cto-date-to').prop('readonly', false);
  }

  if (balance !== undefined && selected.val()) {
    $('#balanceHint').text(credit === 1 ? `Available balance: ${balance} day(s)` : 'This leave type does not deduct credits.');
  } else {
    $('#balanceHint').text('Select a leave type to see your balance.');
  }

  refreshCtoPanel();
}

function refreshCtoPanel() {
  const selected = $('#leaveType option:selected');
  const code = selected.data('code');
  const leaveTypeId = selected.val();
  const dateTo = $('#dateTo').val();
  const scheduleDates = $('.cto-schedule-row .cto-date-to, .cto-schedule-row .cto-date-from').map(function() {
    return this.value;
  }).get().filter(Boolean).sort();
  const ctoEndDate = scheduleDates.length ? scheduleDates[scheduleDates.length - 1] : dateTo;
  let selectedTotal = 0;

  $('.cto-credit-checkbox').prop('disabled', true);
  $('.cto-batch-list').addClass('d-none');
  $('.cto-batch-option').removeClass('border-danger').find('.cto-expired-note').remove();

  if (code !== 'CTO' || !leaveTypeId) {
    $('#ctoCreditPanel').addClass('d-none');
    $('.cto-credit-checkbox').prop('checked', false);
    $('#ctoSelectedTotal').text('Selected: 0.000 day(s)');
    return;
  }

  $('#ctoCreditPanel').removeClass('d-none');
  const list = $(`.cto-batch-list[data-leave-type-id="${leaveTypeId}"]`);
  list.removeClass('d-none');

  list.find('.cto-batch-option').each(function() {
    const option = $(this);
    const checkbox = option.find('.cto-credit-checkbox');
    const expires = option.data('expires');
    const expiredForDate = ctoEndDate && expires && expires < ctoEndDate;

    checkbox.prop('disabled', !!expiredForDate);
    if (expiredForDate) {
      checkbox.prop('checked', false);
      option.addClass('border-danger');
      option.append('<span class="cto-expired-note text-danger ml-2">Expired before selected CTO date</span>');
    }

    if (checkbox.is(':checked') && !checkbox.prop('disabled')) {
      selectedTotal += parseFloat(option.data('remaining')) || 0;
    }
  });

  $('#ctoSelectedTotal').text(`Selected: ${selectedTotal.toFixed(3)} day(s)`);
}

$('#leaveType, #dateFrom, #dateTo').on('change', refreshPreview);
$('#addCtoScheduleRow').on('click', function() {
  addCtoScheduleRow();
  refreshPreview();
});
$(document).on('change', '.cto-date-from, .cto-date-to, .cto-period', function() {
  const row = $(this).closest('.cto-schedule-row');
  const from = row.find('.cto-date-from').val();
  const period = row.find('.cto-period').val();

  if (period === 'AM' || period === 'PM') {
    row.find('.cto-date-to').val(from).prop('readonly', true);
  } else {
    row.find('.cto-date-to').prop('readonly', false);
  }

  refreshPreview();
});
$(document).on('click', '.remove-cto-row', function() {
  $(this).closest('.cto-schedule-row').remove();
  refreshPreview();
});
$(document).on('change', '.cto-credit-checkbox', refreshCtoPanel);

addCtoScheduleRow();
refreshPreview();

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
      $('.cto-credit-checkbox').prop('checked', false);
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

