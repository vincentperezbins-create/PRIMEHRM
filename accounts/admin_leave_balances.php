<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

$userModel = new User($pdo);
require_login();
require_role([1]);
require_once __DIR__ . '/partials/session.php';
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/partials/page_info.php';

$token = generateToken();
$users = $pdo->query("
    SELECT
        u.user_id,
        u.first_name,
        u.middle_name,
        u.last_name,
        u.employeeID,
        COALESCE(s.schoolname, 'No school') AS schoolname,
        COALESCE(p.position_category, 'Unclassified') AS personnel_type
    FROM sdopang1_user u
    LEFT JOIN sdopang1schoollist s ON s.schoolID = u.school_id
    LEFT JOIN sdopang1_position p ON p.position_id = u.position_id
    ORDER BY u.last_name, u.first_name, u.middle_name
")->fetchAll(PDO::FETCH_ASSOC);
$leaveTypes = $pdo->query("SELECT leave_type_id, leave_code, leave_name FROM leave_types ORDER BY leave_code")->fetchAll(PDO::FETCH_ASSOC);

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
        <?php page_info(
            'What this page does',
            'View current employee leave balances and record manual balance adjustments.',
            [
                'Positive adjustments add credits; negative adjustments reduce credits.',
                'Each adjustment is saved to the leave transaction log with remarks for audit purposes.'
            ]
        ); ?>

        <div class="card shadow-sm mb-20">
          <div class="card-header">Manual Balance Adjustment</div>
          <div class="card-body">
            <form id="adjustForm" class="row">
              <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
              <div class="col-md-4 mb-3">
                <label class="form-label">Employee</label>
                <select name="user_id" class="form-control custom-select2 form-control" required>
                  <option value="">Select employee</option>
                  <?php foreach ($users as $user): ?>
                    <option value="<?= htmlspecialchars((string) $user['user_id'], ENT_QUOTES, 'UTF-8') ?>">
                      <?= htmlspecialchars(($user['employeeID'] ?: 'No Employee ID') . ' - ' . trim($user['first_name'] . ' ' . ($user['middle_name'] ? $user['middle_name'] . ' ' : '') . $user['last_name']) . ' - ' . $user['schoolname'] . ' - ' . ucwords(strtolower($user['personnel_type']))) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3 mb-3">
                <label class="form-label">Leave Type</label>
                <select name="leave_type_id" class="form-control" required>
                  <option value="">Select type</option>
                  <?php foreach ($leaveTypes as $type): ?>
                    <option value="<?= htmlspecialchars((string) $type['leave_type_id'], ENT_QUOTES, 'UTF-8') ?>">
                      <?= htmlspecialchars($type['leave_code'] . ' - ' . $type['leave_name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-2 mb-3">
                <label class="form-label">Days</label>
                <input name="days" type="number" step="0.001" class="form-control" required placeholder="e.g. 1.25">
              </div>
              <div class="col-md-3 mb-3">
                <label class="form-label">Remarks</label>
                <input name="remarks" class="form-control" placeholder="Reason for adjustment">
              </div>
              <div class="col-md-3 mb-3">
                <label class="form-label">Date Earned</label>
                <input name="earned_date" type="date" class="form-control">
                <small class="form-text text-muted">Required for CTO earned credits; expiry is one year from this date.</small>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-primary">Save Adjustment</button>
                <span id="adjustMessage" class="ml-2"></span>
              </div>
            </form>
          </div>
        </div>

        <div class="card shadow-sm">
          <div class="card-header">Employee Leave Balances</div>
          <div class="card-body">
            <div class="row mb-3">
              <div class="col-md-3 mb-2">
                <label class="form-label">Personnel Type</label>
                <select id="filterPersonnel" class="form-control">
                  <option value="all">All</option>
                  <option value="teaching">Teaching</option>
                  <option value="non-teaching">Non-Teaching</option>
                </select>
              </div>
              <div class="col-md-9 mb-2">
                <label class="form-label">Employee</label>
                <select id="filterUser" class="form-control custom-select2 form-control">
                  <option value="">Select Employee</option>
                </select>
              </div>
            </div>
            <table id="tblBal" class="table table-striped table-bordered w-100"></table>
          </div>
        </div>


       
        
      



        <?php require_once __DIR__ . '/partials/footer.php'; ?> 
      </div>
    </div>
    <!-- welcome modal start -->
     <?php require_once __DIR__ . '/partials/welcomemodal.php'; ?>
    <button class="welcome-modal-btn">
      <i class="fa fa-download"></i> Download
    </button>
    <!-- welcome modal end -->
    <!-- js -->
    <script src="vendors/scripts/core.js"></script>
    <script src="vendors/scripts/script.min.js"></script>
    <script src="vendors/scripts/process.js"></script>
    <script src="vendors/scripts/layout-settings.js"></script>
    <script src="src/plugins/apexcharts/apexcharts.min.js"></script>
    <script src="src/plugins/datatables/js/jquery.dataTables.min.js"></script>
    <script src="src/plugins/datatables/js/dataTables.bootstrap4.min.js"></script>
    <script src="src/plugins/datatables/js/dataTables.responsive.min.js"></script>
    <script src="src/plugins/datatables/js/responsive.bootstrap4.min.js"></script>
    <script src="src/plugins/datatables/js/dataTables.buttons.min.js"></script>
    <script src="src/plugins/datatables/js/buttons.bootstrap4.min.js"></script>
    <script src="src/plugins/datatables/js/buttons.html5.min.js"></script>
    <script src="src/plugins/datatables/js/buttons.print.min.js"></script>
    <script src="src/plugins/datatables/js/jszip.min.js"></script>
    <script src="src/plugins/datatables/js/pdfmake.min.js"></script>
    <script src="src/plugins/datatables/js/vfs_fonts.js"></script>
    <script src="vendors/scripts/dashboard3.js"></script>
    <script src="src/plugins/switchery/switchery.min.js"></script>
    <!-- bootstrap-tagsinput js -->
    <script src="src/plugins/bootstrap-tagsinput/bootstrap-tagsinput.js"></script>
    <!-- bootstrap-touchspin js -->
    <script src="src/plugins/bootstrap-touchspin/jquery.bootstrap-touchspin.js"></script>
    <script src="vendors/scripts/advanced-components.js"></script>
    <script>
const balanceUsers = <?= json_encode(array_map(function ($user) {
    $middle = trim((string) ($user['middle_name'] ?? ''));
    $fullName = trim($user['first_name'] . ' ' . ($middle !== '' ? $middle . ' ' : '') . $user['last_name']);
    $personnel = strtolower(trim((string) $user['personnel_type']));

    return [
        'id' => (int) $user['user_id'],
        'personnel' => $personnel,
        'label' => (($user['employeeID'] ?? '') ?: 'No Employee ID') . ' - ' . $fullName . ' - ' . $user['schoolname'] . ' - ' . ucwords($personnel),
    ];
}, $users), JSON_UNESCAPED_UNICODE) ?>;

function renderBalanceUsers() {
  const personnel = $('#filterPersonnel').val();
  const current = $('#filterUser').val();
  const select = $('#filterUser');
  select.empty().append(new Option('Select Employee', ''));

  balanceUsers
    .filter(user => personnel === 'all' || user.personnel === personnel)
    .forEach(user => select.append(new Option(user.label, user.id)));

  if (current && select.find(`option[value="${current}"]`).length) {
    select.val(current);
  }
}

renderBalanceUsers();

const balancesTable = $('#tblBal').DataTable({
  ajax: {
    url: 'admin_ajax_leave_balances.php',
    data: function(d) {
      d.user_id = $('#filterUser').val();
    }
  },
  columns: [
    {data:'employee_id', title:'Employee ID', defaultContent:'-'},
    {data:'name', title:'Employee'},
    {data:'leave_name', title:'Leave Type'},
    {data:'balance', title:'Balance', className:'text-end'}
  ]
});

$('#filterPersonnel').change(function(){
  renderBalanceUsers();
  balancesTable.ajax.reload();
});

$('#filterUser').change(function(){
  balancesTable.ajax.reload();
});

$('#adjustForm').on('submit', function(e) {
  e.preventDefault();
  $('#adjustMessage').removeClass('text-success text-danger').text('Saving...');

  $.post('admin_leave_adjust_balance.php', $(this).serialize())
    .done(response => {
      if (response.status !== 'success') {
        $('#adjustMessage').addClass('text-danger').text(response.message || 'Adjustment failed');
        return;
      }
      $('#adjustMessage').addClass('text-success').text('Adjustment saved. New balance: ' + response.balance);
      balancesTable.ajax.reload(null, false);
      this.reset();
    })
    .fail(xhr => {
      const response = xhr.responseJSON || {};
      $('#adjustMessage').addClass('text-danger').text(response.message || 'Adjustment failed');
    });
});
</script>
    <!-- Google Tag Manager (noscript) -->
    <noscript
      ><iframe
        src="https://www.googletagmanager.com/ns.html?id=GTM-NXZMQSS"
        height="0"
        width="0"
        style="display: none; visibility: hidden"
      ></iframe
    ></noscript>
    <!-- End Google Tag Manager (noscript) -->
  </body>
</html>



