<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

$userModel = new User($pdo);
require_login();
require_validator($pdo, 'ipcrf');
require_once __DIR__ . '/partials/session.php';

$total = (int) $pdo->query("SELECT COUNT(*) FROM sdopang1_ipcrf")->fetchColumn();
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
    <div class="d-flex justify-content-between pb-20">
        <div>
            <h2>IPCRF Submissions (<?= htmlspecialchars((string) $total) ?>)</h2>
            <p class="text-700 mb-0">Monitor, review, approve, or return individual employee IPCRF submissions.</p>
        </div>
    </div>

    <div class="card-box pd-20">
        <div class="row mb-3">
            <div class="col-md-3 mb-2">
                <label>Status</label>
                <select id="filterStatus" class="form-control">
                    <option value="">All status</option>
                    <?php foreach (['Draft','For Review','Reviewed','Approved','Returned'] as $status): ?>
                        <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table id="ipcrfTable" class="table table-bordered w-100">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>School</th>
                        <th>Title</th>
                        <th>Period</th>
                        <th>Status</th>
                        <th>Rating</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
</div>
</div>

<script src="vendors/scripts/core.js"></script>
<script src="vendors/scripts/script.min.js"></script>
<script src="vendors/scripts/process.js"></script>
<script src="vendors/scripts/layout-settings.js"></script>
<script src="src/plugins/datatables/js/jquery.dataTables.min.js"></script>
<script src="src/plugins/datatables/js/dataTables.bootstrap4.min.js"></script>
<script src="src/plugins/datatables/js/dataTables.responsive.min.js"></script>
<script src="src/plugins/datatables/js/responsive.bootstrap4.min.js"></script>
<script>
const ipcrfTable = $('#ipcrfTable').DataTable({
  ajax: {
    url: 'admin_ajax_ipcrf_list.php',
    data: function(d) {
      d.status = $('#filterStatus').val();
    }
  },
  processing: true,
  columns: [
    {data: 'employee'},
    {data: 'school'},
    {data: 'title'},
    {data: 'period'},
    {data: 'status'},
    {data: 'overall_rating', className: 'text-end'},
    {data: 'action', orderable: false, searchable: false}
  ]
});

$('#filterStatus').change(function(){ ipcrfTable.ajax.reload(); });
</script>
</body>
</html>


