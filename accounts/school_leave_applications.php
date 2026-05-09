<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

$userModel = new User($pdo);
require_login();
require_role([3]);
require_once __DIR__ . '/partials/session.php';
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/partials/page_info.php';

$token = generateToken();
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
            'Review leave applications submitted by employees assigned to your school.',
            [
                'Approving a credit-based leave deducts the employee balance and writes a transaction log.',
                'You can only review leave applications for employees in your school.'
            ]
        ); ?>

        <div class="card shadow-sm">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span>School Leave Applications</span>
            <a class="btn btn-sm btn-outline-primary" href="school_leave_balances.php">View Balances</a>
          </div>
          <div class="card-body">
            <table id="tblApps" class="table table-hover table-bordered w-100">
              <thead class="table-light">
                <tr>
                  <th>Employee ID</th>
                  <th>Employee</th>
                  <th>Leave</th>
                  <th>Date</th>
                  <th class="text-end">Days</th>
                  <th>Status</th>
                  <th width="160">Action</th>
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
    <script>
const tbl = $('#tblApps').DataTable({
  ajax: 'school_ajax_leave_applications.php',
  processing: true,
  columns: [
    {data:'employee_id', defaultContent:'-'},
    {data:'name'},
    {data:'leave_name'},
    {data:'date_range'},
    {data:'days', className:'text-end'},
    {
      data:'status',
      render: s => {
        if (s === 'approved') return '<span class="badge bg-success">Approved</span>';
        if (s === 'rejected') return '<span class="badge bg-danger">Rejected</span>';
        return '<span class="badge bg-warning text-dark">Pending</span>';
      }
    },
    {
      data:null,
      orderable:false,
      render: r => `
        <div class="btn-group">
          ${r.status === 'pending' ? `
            <button class="btn btn-success btn-sm" onclick="act(${r.application_id},'approve')">Approve</button>
            <button class="btn btn-outline-danger btn-sm" onclick="act(${r.application_id},'reject')">Reject</button>
          ` : '<span class="text-muted">Reviewed</span>'}
        </div>`
    }
  ]
});

function act(id, action) {
  let remarks = '';
  if (action === 'reject') {
    remarks = prompt('Reason for rejection');
    if (!remarks) return;
  } else {
    remarks = prompt('Approval remarks (optional)') || '';
  }

  $.post('school_query_leave_applications.php', {
      token: <?= json_encode($token) ?>,
      application_id: id,
      action,
      remarks
    })
    .done(response => {
      if (response.status !== 'success') {
        alert(response.message || 'Action failed');
        return;
      }
      tbl.ajax.reload(null, false);
    })
    .fail(xhr => {
      const response = xhr.responseJSON || {};
      alert(response.message || 'Action failed');
    });
}
    </script>
  </body>
</html>
