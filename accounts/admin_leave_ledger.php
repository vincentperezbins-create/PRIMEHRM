<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

$userModel = new User($pdo);
require_login();
require_role([1]);
require_once __DIR__ . '/partials/session.php';
require_once __DIR__ . '/partials/page_info.php';

// get leave types
$types = $pdo->query("SELECT leave_code, leave_name FROM leave_types WHERE is_active = 1 ORDER BY leave_code")->fetchAll();
$years = $pdo->query("
    SELECT DISTINCT YEAR(created_at) AS year
    FROM leave_transactions
    WHERE created_at IS NOT NULL
    ORDER BY year DESC
")->fetchAll(PDO::FETCH_COLUMN);

if (!$years) {
    $years = [date('Y')];
}

$users = $pdo->query("
    SELECT
        u.user_id,
        u.first_name,
        u.middle_name,
        u.last_name,
        COALESCE(s.schoolname, 'No school') AS schoolname,
        COALESCE(p.position_category, 'Unclassified') AS personnel_type
    FROM sdopang1_user u
    LEFT JOIN sdopang1schoollist s ON s.schoolID = u.school_id
    LEFT JOIN sdopang1_position p ON p.position_id = u.position_id
    ORDER BY u.last_name, u.first_name, u.middle_name
")->fetchAll(PDO::FETCH_ASSOC);

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
            'Inspect an employee leave ledger by year, personnel type, and employee.',
            [
                'The table shows earned credits, used credits, and running balances for each leave type.',
                'Employees with no transactions still show an opening balance row.'
            ]
        ); ?>
        <div class="card shadow-sm">
          <div class="card-body" style="overflow: auto;">
  <h4 class="mb-3">DepEd Leave Ledger</h4>
<div class="row mb-3">
  <div class="col-md-3 mb-2">
    <label class="form-label">Personnel Type</label>
    <select id="filterPersonnel" class="form-control">
      <option value="all">All</option>
      <option value="teaching">Teaching</option>
      <option value="non-teaching">Non-Teaching</option>
    </select>
  </div>
  <div class="col-md-7 mb-2">
    <label class="form-label">Employee</label>
    <select id="filterUser" class="form-control custom-select2 form-control">
      <option value="">Select Employee</option>
    </select>
  </div>
  <div class="col-md-2 mb-2">
    <label class="form-label">Year</label>
    <select id="filterYear" class="form-control">
<?php
foreach($years as $y){
    echo '<option value="' . htmlspecialchars((string) $y, ENT_QUOTES, 'UTF-8') . '">' .
        htmlspecialchars((string) $y, ENT_QUOTES, 'UTF-8') .
        '</option>';
}
?>
    </select>
  </div>
</div>
<div style="overflow-x:auto;">
<table id="ledgerTable" class="table table-bordered table-striped nowrap" style="width:100%">

<thead>
<tr>
    <th rowspan="2">Date</th>
    <th rowspan="2">Remarks</th>
    <?php foreach($types as $t): ?>
        <th colspan="3"><?= htmlspecialchars($t['leave_name'], ENT_QUOTES, 'UTF-8') ?></th>
    <?php endforeach; ?>
</tr>
<tr>
    <?php foreach($types as $t): ?>
        <th>Earn</th>
        <th>Used</th>
        <th>Bal</th>
    <?php endforeach; ?>
</tr>
</thead>

</table>

</div>
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
    <script src="vendors/scripts/dashboard3.js"></script>
    <script src="src/plugins/switchery/switchery.min.js"></script>
    <!-- bootstrap-tagsinput js -->
    <script src="src/plugins/bootstrap-tagsinput/bootstrap-tagsinput.js"></script>
    <!-- bootstrap-touchspin js -->
    <script src="src/plugins/bootstrap-touchspin/jquery.bootstrap-touchspin.js"></script>
    <script src="vendors/scripts/advanced-components.js"></script>

  <script>
let columns = [
    {data:'date'},
    {
        data:'remarks',
        defaultContent:'-',
        render: function(data, type) {
            if (type !== 'display' || !data) {
                return data || '-';
            }

            return String(data)
                .split('<br>')
                .map(function(line) {
                    return $('<div>').text(line).html();
                })
                .join('<br>');
        }
    }
];
const ledgerUsers = <?= json_encode(array_map(function ($user) {
    $middle = trim((string) ($user['middle_name'] ?? ''));
    $fullName = trim($user['first_name'] . ' ' . ($middle !== '' ? $middle . ' ' : '') . $user['last_name']);
    $personnel = strtolower(trim((string) $user['personnel_type']));

    return [
        'id' => (int) $user['user_id'],
        'name' => $fullName,
        'school' => $user['schoolname'],
        'personnel' => $personnel,
        'label' => $fullName . ' - ' . $user['schoolname'] . ' - ' . ucwords($personnel),
    ];
}, $users), JSON_UNESCAPED_UNICODE) ?>;

<?php foreach($types as $t): ?>
columns.push({data:<?= json_encode($t['leave_code'] . '_earn') ?>, className:'text-end'});
columns.push({data:<?= json_encode($t['leave_code'] . '_used') ?>, className:'text-end'});
columns.push({data:<?= json_encode($t['leave_code'] . '_bal') ?>, className:'text-end'});
<?php endforeach; ?>

// initialize table
let table = $('#ledgerTable').DataTable({
    ajax: {
        url: 'admin_ajax_leave_ledger_matrix.php',
        data: function(d){
            d.user_id = $('#filterUser').val();
            d.year = $('#filterYear').val();
        }
    },
    columns: columns,
    order: [[0,'asc']],
    processing: true
});

function renderUsers() {
    const personnel = $('#filterPersonnel').val();
    const current = $('#filterUser').val();
    const select = $('#filterUser');
    select.empty().append(new Option('Select Employee', ''));

    ledgerUsers
      .filter(user => personnel === 'all' || user.personnel === personnel)
      .forEach(user => {
        const option = new Option(user.label, user.id);
        select.append(option);
      });

    if (current && select.find(`option[value="${current}"]`).length) {
      select.val(current);
    }
}

renderUsers();

$('#filterPersonnel').change(function(){
    renderUsers();
    table.ajax.reload();
});

$('#filterUser').change(function(){
    table.ajax.reload();
});

// reload when year changes
$('#filterYear').change(function(){
    table.ajax.reload();
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
