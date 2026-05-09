<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

$userModel = new User($pdo);
require_login();
require_role([1]);
require_once __DIR__ . '/partials/session.php';
require_once __DIR__ . '/partials/page_info.php';

$schools = $pdo->query("
    SELECT schoolID, schoolname
    FROM sdopang1schoollist
    WHERE schoolID IS NOT NULL AND schoolID <> ''
    ORDER BY schoolname
")->fetchAll(PDO::FETCH_ASSOC);

$offices = $pdo->query("
    SELECT office_id, office_name
    FROM sdopang1_offices
    WHERE status = 'Active'
    ORDER BY office_name
")->fetchAll(PDO::FETCH_ASSOC);

$years = $pdo->query("
    SELECT DISTINCT YEAR(date_from) AS leave_year
    FROM leave_applications
    WHERE status = 'approved' AND date_from IS NOT NULL
    ORDER BY leave_year DESC
")->fetchAll(PDO::FETCH_COLUMN);

$months = [
    1 => 'January',
    2 => 'February',
    3 => 'March',
    4 => 'April',
    5 => 'May',
    6 => 'June',
    7 => 'July',
    8 => 'August',
    9 => 'September',
    10 => 'October',
    11 => 'November',
    12 => 'December',
];
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
            <h2 class="mb-1">Approved Leave Applications</h2>
            <p class="text-700 mb-0">Approved leave requests with employee, school, office, and date filters.</p>
          </div>
          <a href="admin_leave_applications.php" class="btn btn-outline-primary">Review Applications</a>
        </div>

        <?php page_info(
            'What this page does',
            'Display approved leave applications only, then export the filtered results.',
            [
                'Use Employee ID, name, school, office, year, and month to narrow the report.',
                'CSV and PDF exports use the current filtered table.'
            ]
        ); ?>

        <div class="card shadow-sm mb-20">
          <div class="card-header">Filters</div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-3 mb-3">
                <label class="form-label">Employee ID</label>
                <input id="filterEmployeeId" class="form-control" placeholder="Search employee ID">
              </div>
              <div class="col-md-3 mb-3">
                <label class="form-label">Name</label>
                <input id="filterName" class="form-control" placeholder="Search name">
              </div>
              <div class="col-md-3 mb-3">
                <label class="form-label">School</label>
                <select id="filterSchool" class="form-control custom-select2">
                  <option value="">All schools</option>
                  <?php foreach ($schools as $school): ?>
                    <option value="<?= htmlspecialchars((string) $school['schoolID'], ENT_QUOTES, 'UTF-8') ?>">
                      <?= htmlspecialchars($school['schoolname']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3 mb-3">
                <label class="form-label">Office</label>
                <select id="filterOffice" class="form-control custom-select2">
                  <option value="">All offices</option>
                  <?php foreach ($offices as $office): ?>
                    <option value="<?= htmlspecialchars((string) $office['office_id'], ENT_QUOTES, 'UTF-8') ?>">
                      <?= htmlspecialchars($office['office_name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3 mb-3">
                <label class="form-label">Year</label>
                <select id="filterYear" class="form-control">
                  <option value="">All years</option>
                  <?php foreach ($years as $year): ?>
                    <option value="<?= htmlspecialchars((string) $year, ENT_QUOTES, 'UTF-8') ?>">
                      <?= htmlspecialchars((string) $year) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3 mb-3">
                <label class="form-label">Month</label>
                <select id="filterMonth" class="form-control">
                  <option value="">All months</option>
                  <?php foreach ($months as $monthNumber => $monthName): ?>
                    <option value="<?= htmlspecialchars((string) $monthNumber, ENT_QUOTES, 'UTF-8') ?>">
                      <?= htmlspecialchars($monthName) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-3 mb-3 d-flex align-items-end">
                <button id="resetFilters" type="button" class="btn btn-outline-secondary">Reset Filters</button>
              </div>
            </div>
          </div>
        </div>

        <div class="card shadow-sm">
          <div class="card-header">Approved Applications</div>
          <div class="card-body">
            <table id="tblApprovedApps" class="table table-hover table-bordered w-100">
              <thead class="table-light">
                <tr>
                  <th>Employee ID</th>
                  <th>Employee</th>
                  <th>School</th>
                  <th>Office</th>
                  <th>Leave</th>
                  <th>Date</th>
                  <th class="text-end">Days</th>
                  <th>Pay Status</th>
                  <th>Approved</th>
                  <th>Form 6</th>
                </tr>
              </thead>
            </table>
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
    <script src="src/plugins/datatables/js/dataTables.buttons.min.js"></script>
    <script src="src/plugins/datatables/js/buttons.bootstrap4.min.js"></script>
    <script src="src/plugins/datatables/js/buttons.html5.min.js"></script>
    <script src="src/plugins/datatables/js/jszip.min.js"></script>
    <script src="src/plugins/datatables/js/pdfmake.min.js"></script>
    <script src="src/plugins/datatables/js/vfs_fonts.js"></script>
    <script>
const tbl = $('#tblApprovedApps').DataTable({
  ajax: {
    url: 'admin_ajax_approved_leave_applications.php',
    data: function(d) {
      d.employee_id = $('#filterEmployeeId').val();
      d.name = $('#filterName').val();
      d.school_id = $('#filterSchool').val();
      d.office_id = $('#filterOffice').val();
      d.year = $('#filterYear').val();
      d.month = $('#filterMonth').val();
    }
  },
  processing: true,
  responsive: true,
  dom: 'Bfrtip',
  buttons: [
    {
      extend: 'csvHtml5',
      text: 'Export CSV',
      className: 'btn btn-sm btn-outline-success',
      title: 'approved_leave_applications',
      exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] }
    },
    {
      extend: 'pdfHtml5',
      text: 'Export PDF',
      className: 'btn btn-sm btn-outline-danger',
      title: 'Approved Leave Applications',
      orientation: 'landscape',
      pageSize: 'A4',
      exportOptions: { columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] }
    }
  ],
  columns: [
    {data:'employee_id', defaultContent:'-'},
    {data:'name'},
    {data:'schoolname', defaultContent:'-'},
    {data:'office_name', defaultContent:'-'},
    {
      data:null,
      render: r => `${r.leave_code || ''} - ${r.leave_name || ''}`
    },
    {data:'date_range'},
    {data:'days', className:'text-end'},
    {
      data:'pay_status',
      render: value => value === 'without_pay' ? 'Without Pay' : 'With Pay'
    },
    {data:'approved_date', defaultContent:'-'},
    {
      data:null,
      orderable:false,
      searchable:false,
      render: r => `<a class="btn btn-sm btn-outline-success" href="download_form6.php?application_id=${r.application_id}"><i class="fa fa-download"></i> Form 6</a>`
    }
  ]
});

let filterTimer = null;
function reloadApprovedTable() {
  window.clearTimeout(filterTimer);
  filterTimer = window.setTimeout(() => tbl.ajax.reload(), 250);
}

$('#filterEmployeeId, #filterName').on('input', reloadApprovedTable);
$('#filterSchool, #filterOffice, #filterYear, #filterMonth').on('change', () => tbl.ajax.reload());

$('#resetFilters').on('click', function() {
  $('#filterEmployeeId, #filterName').val('');
  $('#filterSchool, #filterOffice, #filterYear, #filterMonth').val('').trigger('change.select2');
  tbl.ajax.reload();
});
    </script>
  </body>
</html>
