<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

$userModel = new User($pdo);
require_login();
require_role([3]);
require_once __DIR__ . '/partials/session.php';
require_once __DIR__ . '/partials/page_info.php';

$schoolId = $currentUser['school_id'] ?? null;
$usersStmt = $pdo->prepare("
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
    WHERE u.school_id = ?
    ORDER BY u.last_name, u.first_name, u.middle_name
");
$usersStmt->execute([$schoolId]);
$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
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
            'View leave balances for employees assigned to your school.',
            [
                'Choose Teaching, Non-Teaching, or All to narrow the employee list.',
                'Employees are shown even when they have no leave transactions yet.'
            ]
        ); ?>

        <div class="card shadow-sm">
          <div class="card-header">School Leave Balances</div>
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

    <?php require_once __DIR__ . '/partials/welcomemodal.php'; ?>
    <script src="vendors/scripts/core.js"></script>
    <script src="vendors/scripts/script.min.js"></script>
    <script src="vendors/scripts/process.js"></script>
    <script src="vendors/scripts/layout-settings.js"></script>
    <script src="src/plugins/datatables/js/jquery.dataTables.min.js"></script>
    <script src="src/plugins/datatables/js/dataTables.bootstrap4.min.js"></script>
    <script src="src/plugins/datatables/js/dataTables.responsive.min.js"></script>
    <script src="src/plugins/datatables/js/responsive.bootstrap4.min.js"></script>
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
    url: 'school_ajax_leave_balances.php',
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
    </script>
  </body>
</html>
