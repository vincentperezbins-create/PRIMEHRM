<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

$userModel = new User($pdo);
require_login();
require_scoped_validator($pdo, 'leave');
require_once __DIR__ . '/partials/session.php';
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/core/leave_helpers.php';
require_once __DIR__ . '/partials/page_info.php';

$token = generateToken();
$typeColumns = leave_table_columns($pdo, 'leave_types');
$typeSql = 'SELECT * FROM leave_types';

if (in_array('is_active', $typeColumns, true)) {
    $typeSql .= ' WHERE is_active = 1';
}

$typeSql .= ' ORDER BY leave_name';
$leaveTypes = $pdo->query($typeSql)->fetchAll(PDO::FETCH_ASSOC);

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
            'Review submitted leave applications and approve or reject pending requests.',
            [
                'Approving a credit-based leave deducts the correct balance and writes a transaction log.',
                'Rejecting requires a reason so the employee can see why the request was not approved.'
            ]
        ); ?>
        
        <div class="card shadow-sm">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Leave Applications</span>
    <?php if ((int) ($_SESSION['role_id'] ?? 0) === 1): ?>
      <a class="btn btn-sm btn-outline-primary" href="admin_leave_balances.php">View Balances</a>
    <?php endif; ?>
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
          <th>Pay Status</th>
          <th>Status</th>
          <th width="220">Action</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

<div class="modal fade" id="editLeaveModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <form id="editLeaveForm">
        <div class="modal-header">
          <h5 class="modal-title">Update Leave Application</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="action" value="update">
          <input type="hidden" id="editApplicationId" name="application_id">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Leave Type</label>
              <select id="editLeaveType" name="leave_type_id" class="form-control" required>
                <?php foreach ($leaveTypes as $type): ?>
                  <option value="<?= htmlspecialchars((string) $type['leave_type_id'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars(($type['leave_code'] ?? '') . ' - ' . $type['leave_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">From</label>
              <input id="editDateFrom" name="date_from" type="date" class="form-control" required>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">To</label>
              <input id="editDateTo" name="date_to" type="date" class="form-control" required>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Pay Status</label>
              <select id="editPayStatus" name="pay_status" class="form-control" required>
                <option value="with_pay">With Pay</option>
                <option value="without_pay">Without Pay</option>
              </select>
            </div>
            <div class="col-md-8 mb-3">
              <label class="form-label">Admin Remarks</label>
              <input id="editRemarks" name="remarks" class="form-control" placeholder="Optional correction note">
            </div>
            <div class="col-12 mb-3">
              <label class="form-label">Reason</label>
              <textarea id="editReason" name="reason" class="form-control" rows="4" required></textarea>
            </div>
          </div>
          <div id="editLeaveMessage" class="small"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
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
    <script src="src/plugins/datatables/js/dataTables.buttons.min.js"></script>
    <script src="src/plugins/datatables/js/buttons.bootstrap4.min.js"></script>
    <script src="src/plugins/datatables/js/buttons.html5.min.js"></script>
    <script src="src/plugins/datatables/js/buttons.print.min.js"></script>
    <script src="src/plugins/datatables/js/jszip.min.js"></script>
    <script src="src/plugins/datatables/js/pdfmake.min.js"></script>
    <script src="src/plugins/datatables/js/vfs_fonts.js"></script>
    <script src="vendors/scripts/dashboard3.js"></script>

    <script>
const appRows = {};
const tbl = $('#tblApps').DataTable({
  ajax: {
    url: 'admin_ajax_leave_applications.php',
    dataSrc: function(json) {
      Object.keys(appRows).forEach(key => delete appRows[key]);
      (json.data || []).forEach(row => { appRows[row.application_id] = row; });
      return json.data || [];
    }
  },
  processing: true,
  columns: [
    {data:'employee_id', defaultContent:'-'},
    {data:'name'},
    {data:'leave_name'},
    {data:'date_range'},
    {data:'days', className:'text-end'},
    {
      data:'pay_status',
      render: (value, type, r) => {
        const selected = value === 'without_pay' ? 'without_pay' : 'with_pay';
        if (type !== 'display') return selected === 'without_pay' ? 'Without Pay' : 'With Pay';
        if (r.status !== 'pending') return selected === 'without_pay' ? 'Without Pay' : 'With Pay';
        return `
          <select id="pay-${r.application_id}" class="form-control form-control-sm">
            <option value="with_pay" ${selected === 'with_pay' ? 'selected' : ''}>With Pay</option>
            <option value="without_pay" ${selected === 'without_pay' ? 'selected' : ''}>Without Pay</option>
          </select>`;
      }
    },
    {
      data:'status',
      render: s => {
        if(s==='approved') return '<span class="badge bg-success">Approved</span>';
        if(s==='rejected') return '<span class="badge bg-danger">Rejected</span>';
        return '<span class="badge bg-warning text-dark">Pending</span>';
      }
    },
    {
      data:null,
      orderable:false,
      render: r => `
        <div class="btn-group">
          ${r.status === 'pending' ? `
            <button class="btn btn-outline-primary btn-sm" onclick="openEdit(${r.application_id})">Edit</button>
            <button class="btn btn-success btn-sm" onclick="act(${r.application_id},'approve')">Approve</button>
            <button class="btn btn-outline-danger btn-sm" onclick="act(${r.application_id},'reject')">Reject</button>
          ` : '<span class="text-muted">Reviewed</span>'}
        </div>`
    }
  ]
});

function showLeaveActionError(message) {
  const text = message || 'Action failed';
  const isMigration = text.includes('leave_pay_status_migration.sql');

  if (window.Swal) {
    Swal.fire({
      icon: isMigration ? 'warning' : 'error',
      title: isMigration ? 'Database Update Required' : 'Action failed',
      text
    });
    return;
  }

  window.alert(text);
}

async function act(id, action){
  let remarks = '';
  const payStatus = $(`#pay-${id}`).val() || 'with_pay';

  if (action === 'reject') {
    const result = await Swal.fire({
      icon: 'warning',
      title: 'Reject leave application?',
      input: 'textarea',
      inputLabel: 'Reason for rejection',
      inputPlaceholder: 'Enter the reason the employee will see',
      inputAttributes: { 'aria-label': 'Reason for rejection' },
      showCancelButton: true,
      confirmButtonText: 'Reject',
      confirmButtonColor: '#d33',
      inputValidator: value => !value.trim() ? 'Rejection remarks are required.' : undefined
    });

    if (!result.isConfirmed) return;
    remarks = result.value.trim();
  } else {
    const result = await Swal.fire({
      icon: 'question',
      title: 'Approve leave application?',
      input: 'textarea',
      inputLabel: 'Approval remarks',
      inputPlaceholder: 'Optional',
      showCancelButton: true,
      confirmButtonText: 'Approve',
      confirmButtonColor: '#28a745'
    });

    if (!result.isConfirmed) return;
    remarks = (result.value || '').trim();
  }

  $.post('admin_query_leave_applications.php', {
      token: <?= json_encode($token) ?>,
      application_id: id,
      action,
      remarks,
      pay_status: payStatus
    })
    .done(response => {
      if (response.status !== 'success') {
        showLeaveActionError(response.message);
        return;
      }
      Swal.fire({icon: 'success', title: 'Saved', timer: 1200, showConfirmButton: false});
      tbl.ajax.reload(null,false);
    })
    .fail(xhr => {
      const response = xhr.responseJSON || {};
      showLeaveActionError(response.message);
    });
}

function openEdit(id) {
  const row = appRows[id];
  if (!row) return;

  $('#editApplicationId').val(row.application_id);
  $('#editLeaveType').val(row.leave_type_id);
  $('#editDateFrom').val(row.date_from);
  $('#editDateTo').val(row.date_to);
  $('#editPayStatus').val(row.pay_status === 'without_pay' ? 'without_pay' : 'with_pay');
  $('#editReason').val(row.reason || '');
  $('#editRemarks').val('');
  $('#editLeaveMessage').removeClass('text-success text-danger').text('');
  $('#editLeaveModal').modal('show');
}

$('#editLeaveForm').on('submit', function(e) {
  e.preventDefault();
  $('#editLeaveMessage').removeClass('text-success text-danger').text('Saving...');

  $.post('admin_query_leave_applications.php', $(this).serialize())
    .done(response => {
      if (response.status !== 'success') {
        $('#editLeaveMessage').addClass('text-danger').text(response.message || 'Update failed');
        showLeaveActionError(response.message || 'Update failed');
        return;
      }
      $('#editLeaveMessage').addClass('text-success').text('Leave application updated.');
      Swal.fire({icon: 'success', title: 'Updated', timer: 1200, showConfirmButton: false});
      tbl.ajax.reload(null, false);
      window.setTimeout(() => $('#editLeaveModal').modal('hide'), 500);
    })
    .fail(xhr => {
      const response = xhr.responseJSON || {};
      $('#editLeaveMessage').addClass('text-danger').text(response.message || 'Update failed');
      showLeaveActionError(response.message || 'Update failed');
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





