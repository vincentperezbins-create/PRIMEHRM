<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

$userModel = new User($pdo);
require_login();
require_role([1]);
require_once __DIR__ . '/partials/session.php';

$total = (int) $pdo->query("SELECT COUNT(*) FROM sdopang1_offices")->fetchColumn();
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
            <h2>OPCRF Offices (<?= htmlspecialchars((string) $total) ?>)</h2>
            <p class="text-700 mb-0">Manage Office Heads for Form 6 signing and Unit Heads for OPCRF submission.</p>
        </div>
        <button class="btn btn-primary openModal" data-action="Add">Add Office</button>
    </div>

    <div class="card-box pd-20">
        <div class="table-responsive">
            <table id="officeTable" class="table table-bordered w-100">
                <thead>
                    <tr>
                        <th>Office</th>
                        <th>Type</th>
                        <th>Office Head<br><small>Form 6 Signatory</small></th>
                        <th>Unit Head<br><small>OPCRF Submitter</small></th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="modal fade" id="actionModal">
        <div class="modal-dialog">
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const officeTable = $('#officeTable').DataTable({
  ajax: {url: 'admin_ajax_opcrf_offices.php', type: 'POST'},
  processing: true,
  columns: [
    {data: 'office_name'},
    {data: 'office_type'},
    {data: 'office_head_name'},
    {data: 'unit_head_name'},
    {data: 'status'},
    {data: 'action', orderable: false, searchable: false}
  ]
});

document.addEventListener('click', function(e) {
  const btn = e.target.closest('.openModal');
  if (!btn) return;
  const id = btn.dataset.id || '';
  const action = btn.dataset.action;
  let url = 'admin_add_opcrf_office.php';
  if (action === 'Update') url = 'admin_update_opcrf_office.php?id=' + encodeURIComponent(id);
  document.getElementById('modalTitle').innerText = action + ' Office';
  fetch(url).then(r => r.text()).then(html => {
    document.getElementById('modalContent').innerHTML = html;
    new bootstrap.Modal(document.getElementById('actionModal')).show();
  });
});

document.addEventListener('submit', function(e) {
  if (e.target.id !== 'opcrfOfficeForm') return;
  e.preventDefault();
  fetch('admin_query_opcrf_offices.php', {method: 'POST', body: new FormData(e.target)})
    .then(r => r.json())
    .then(res => {
      if (res.status !== 'success') {
        Swal.fire({icon: 'error', title: 'Save failed', text: res.message || 'Unable to save office.'});
        return;
      }
      $('#actionModal').modal('hide');
      Swal.fire({icon: 'success', title: 'Saved!', timer: 1400, showConfirmButton: false});
      officeTable.ajax.reload(null, false);
    });
});

document.addEventListener('click', function(e) {
  const btn = e.target.closest('.btnDeleteOffice');
  if (!btn) return;
  Swal.fire({
    icon: 'warning',
    title: 'Delete office?',
    text: 'OPCRF records under this office will also be deleted.',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete',
    confirmButtonColor: '#d33'
  }).then(result => {
    if (!result.isConfirmed) return;
    const body = new URLSearchParams({action: 'delete', office_id: btn.dataset.id});
    fetch('admin_query_opcrf_offices.php', {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body})
      .then(r => r.json())
      .then(res => {
        if (res.status === 'success') {
          Swal.fire({icon: 'success', title: 'Deleted!', timer: 1400, showConfirmButton: false});
          officeTable.ajax.reload(null, false);
        } else {
          Swal.fire({icon: 'error', title: 'Delete failed', text: res.message || 'Unable to delete office.'});
        }
      });
  });
});
</script>
</body>
</html>
