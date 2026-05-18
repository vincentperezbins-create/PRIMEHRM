<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

$userModel = new User($pdo);
require_login();
require_division_opcrf_validator($pdo);
require_once __DIR__ . '/partials/session.php';

$isAdmin = (int) ($_SESSION['role_id'] ?? 0) === 1;
$total = (int) $pdo->query("SELECT COUNT(*) FROM sdopang1_opcrf")->fetchColumn();
$offices = $pdo->query("SELECT office_id, office_name FROM sdopang1_offices WHERE status='Active' ORDER BY office_name")->fetchAll(PDO::FETCH_ASSOC);
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
            <h2>Office/Unit OPCRF (<?= htmlspecialchars((string) $total) ?>)</h2>
            <p class="text-700 mb-0">Monitor, review, and approve office-level OPCRF for division functional units and schools.</p>
        </div>
        <?php if ($isAdmin): ?>
            <button class="btn btn-primary openModal" data-action="Add">Add Office/Unit OPCRF</button>
        <?php endif; ?>
    </div>

    <div class="card-box pd-20">
        <div class="row mb-3">
            <div class="col-md-4 mb-2">
                <label>Office</label>
                <select id="filterOffice" class="form-control">
                    <option value="">All offices</option>
                    <?php foreach ($offices as $office): ?>
                        <option value="<?= htmlspecialchars((string) $office['office_id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($office['office_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
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
            <table id="opcrfTable" class="table table-bordered w-100">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Office</th>
                        <th>Period</th>
                        <th>Status</th>
                        <th>Rating</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="modal fade" id="actionModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white" id="modalTitle"></h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalContent">Loading...</div>
            </div>
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
<script src="src/plugins/datatables/js/dataTables.buttons.min.js"></script>
<script src="src/plugins/datatables/js/buttons.bootstrap4.min.js"></script>
<script src="src/plugins/datatables/js/buttons.html5.min.js"></script>
<script src="src/plugins/datatables/js/buttons.print.min.js"></script>
<script src="src/plugins/datatables/js/jszip.min.js"></script>
<script src="src/plugins/datatables/js/pdfmake.min.js"></script>
<script src="src/plugins/datatables/js/vfs_fonts.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const opcrfTable = $('#opcrfTable').DataTable({
  ajax: {
    url: 'admin_ajax_opcrf_list.php',
    data: function(d) {
      d.office_id = $('#filterOffice').val();
      d.status = $('#filterStatus').val();
    }
  },
  processing: true,
  columns: [
    {data: 'title'},
    {data: 'office_name'},
    {data: 'period'},
    {data: 'status'},
    {data: 'overall_rating', className: 'text-end'},
    {data: 'action', orderable: false, searchable: false}
  ]
});

$('#filterOffice, #filterStatus').change(function(){ opcrfTable.ajax.reload(); });

document.addEventListener('click', function(e) {
  const btn = e.target.closest('.openModal');
  if (!btn) return;
  document.getElementById('modalTitle').innerText = 'Add Office/Unit OPCRF';
  fetch('admin_add_opcrf.php').then(r => r.text()).then(html => {
    document.getElementById('modalContent').innerHTML = html;
    new bootstrap.Modal(document.getElementById('actionModal')).show();
  });
});

document.addEventListener('submit', function(e) {
  if (e.target.id !== 'opcrfForm') return;
  e.preventDefault();
  fetch('admin_query_opcrf.php', {method: 'POST', body: new FormData(e.target)})
    .then(r => r.json())
    .then(res => {
      if (res.status !== 'success') {
        Swal.fire({icon: 'error', title: 'Save failed', text: res.message || 'Unable to save OPCRF.'});
        return;
      }
      window.location.href = 'admin_view_opcrf.php?id=' + res.opcrf_id;
    });
});
</script>
</body>
</html>





