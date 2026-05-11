<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

$userModel = new User($pdo);
require_login();
require_role([1]);
require_once __DIR__ . '/partials/session.php';

$total = (int) $pdo->query("SELECT COUNT(*) FROM leave_types")->fetchColumn();
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
        <h2 class="mb-1">Leave Types <span class="fw-normal text-700">(<?= htmlspecialchars((string) $total) ?>)</span></h2>
        <p class="text-700 mb-0">Add and edit leave type rules used by applications, balances, ledger, and accrual processes.</p>
      </div>
      <button class="btn btn-primary openLeaveTypeModal" data-action="Add">Add Leave Type</button>
    </div>

    <div class="card-box pd-20">
      <div class="table-responsive">
        <table id="leaveTypeTable" class="table table-bordered w-100">
          <thead>
            <tr>
              <th>Code</th>
              <th>Leave Type</th>
              <th>Personnel</th>
              <th>Credit Based</th>
              <th>Monthly Accrual</th>
              <th>Monthly Rate</th>
              <th>Max / Year</th>
              <th>Expiry</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>

    <div class="modal fade" id="leaveTypeModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header bg-primary">
            <h5 class="modal-title text-white" id="leaveTypeModalTitle"></h5>
            <button class="btn-close" data-dismiss="modal"></button>
          </div>
          <div class="modal-body" id="leaveTypeModalContent">Loading...</div>
        </div>
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
const leaveTypeTable = $('#leaveTypeTable').DataTable({
  ajax: { url: 'admin_ajax_leave_types.php', type: 'POST' },
  processing: true,
  columns: [
    { data: 'leave_code' },
    { data: 'leave_name' },
    { data: 'personnel_type' },
    { data: 'is_credit_based' },
    { data: 'is_monthly_accrual' },
    { data: 'monthly_rate', className: 'text-end' },
    { data: 'max_per_year', className: 'text-end' },
    { data: 'expiry' },
    { data: 'is_active' },
    { data: 'action', orderable: false, searchable: false }
  ]
});

document.addEventListener('click', function(e) {
  const btn = e.target.closest('.openLeaveTypeModal');
  if (!btn) return;

  const action = btn.dataset.action;
  const id = btn.dataset.id || '';
  const url = action === 'Update'
    ? 'admin_leave_type_form.php?id=' + encodeURIComponent(id)
    : 'admin_leave_type_form.php';

  document.getElementById('leaveTypeModalTitle').innerText = action + ' Leave Type';
  document.getElementById('leaveTypeModalContent').innerHTML = 'Loading...';

  fetch(url)
    .then(r => r.text())
    .then(html => {
      document.getElementById('leaveTypeModalContent').innerHTML = html;
      $('#leaveTypeModal').modal('show');
      PrimeUI.enhance(document.getElementById('leaveTypeModalContent'));
    });
});

document.addEventListener('submit', function(e) {
  if (e.target.id !== 'leaveTypeForm') return;
  e.preventDefault();

  PrimeUI.confirmSave('Save this leave type?').then(result => {
    if (!result.isConfirmed) return;

    fetch('admin_query_leave_types.php', { method: 'POST', body: new FormData(e.target) })
      .then(r => r.json())
      .then(res => {
        if (res.status !== 'success') {
          PrimeUI.error(res.message || 'Unable to save leave type.');
          return;
        }

        $('#leaveTypeModal').modal('hide');
        PrimeUI.success('Leave type saved.');
        leaveTypeTable.ajax.reload(null, false);
      })
      .catch(() => PrimeUI.error('Please check the connection or server logs.'));
  });
});
</script>
</body>
</html>
