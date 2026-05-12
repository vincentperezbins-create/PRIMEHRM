<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
$userModel = new User($pdo);
require_login();
require_role([1]);
require_once __DIR__ . '/partials/session.php';

$total = $pdo->query("SELECT COUNT(*) FROM leave_applications")->fetchColumn();
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
        <h2>Leave Applications (<?= $total ?>)</h2>
        <button class="btn btn-primary openModal" data-action="Add">
            Apply Leave
        </button>
    </div>

    <div class="card-box pd-20">
        <div class="table-responsive">
            <table id="leaveTable" class="table  stripe hover nowrap">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Type</th>
                        <th>Date From</th>
                        <th>Date To</th>
                        <th>Days</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <!-- MODAL -->
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

<!-- JS (same as your other pages) -->
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

<script src="vendors/scripts/datatable-setting.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(function(){

    const table = $('#leaveTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: "admin_ajax_leave_list.php", type: "POST" },
        columns: [
            { data: "employee" },
            { data: "leave_name" },
            { data: "date_from" },
            { data: "date_to" },
            { data: "days" },
            { data: "status_badge" },
            { data: "action", orderable:false, searchable:false }
        ]
    });

    // MODAL
    document.addEventListener("click", function(e){
        let btn = e.target.closest('.openModal');
        if(!btn) return;

        let id = btn.dataset.id;
        let action = btn.dataset.action;

        let url = "";
        if(action === "Add") url = "admin_add_leave.php";
        else if(action === "View") url = "admin_view_leave.php?id="+id;

        document.getElementById("modalTitle").innerText = action + " Leave";

        fetch(url).then(r=>r.text()).then(html=>{
            document.getElementById("modalContent").innerHTML = html;
            // execute inline scripts
            document.querySelectorAll("#modalContent script").forEach(s=>eval(s.innerText));
            new bootstrap.Modal(document.getElementById('actionModal')).show();
        });
    });

    // APPROVE / REJECT
    document.addEventListener("click", function(e){
        let approve = e.target.closest('.btnApprove');
        let reject  = e.target.closest('.btnReject');
        if(!approve && !reject) return;

        let id = (approve || reject).dataset.id;
        let action = approve ? 'approve' : 'reject';

        fetch('admin_query_leave.php', {
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:`action=${action}&application_id=${id}`
        })
        .then(r=>r.json())
        .then(res=>{
            if(res.status==='success'){
                Swal.fire({icon:'success',title:'Updated',timer:1200,showConfirmButton:false});
                table.ajax.reload(null,false);
            }else{
                Swal.fire({icon:'error',title:'Error',text:res.message});
            }
        });
    });

    // APPLY (modal form)
    document.addEventListener('submit', function(e){
        if(e.target.id !== 'addLeaveForm') return;
        e.preventDefault();

        fetch('admin_query_leave.php',{
            method:'POST',
            body:new FormData(e.target)
        })
        .then(r=>r.json())
        .then(res=>{
            if(res.status==='success'){
                Swal.fire({icon:'success',title:'Submitted',timer:1200,showConfirmButton:false});
                $('#actionModal').modal('hide');
                table.ajax.reload(null,false);
            }else{
                Swal.fire({icon:'error',title:'Error',text:res.message});
            }
        });
    });

});
</script>

</body>
</html>



