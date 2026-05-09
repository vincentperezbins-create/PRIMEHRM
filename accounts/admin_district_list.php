<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
$userModel = new User($pdo);
require_login();
require_role([1]);
require_once __DIR__ . '/partials/session.php';

// COUNT
$total = $pdo->query("SELECT COUNT(*) FROM sdopang1_district")->fetchColumn();
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

    <!-- HEADER -->
    <div class="d-flex justify-content-between pb-20">
        <h2>District List (<?= $total ?>)</h2>

        <button class="btn btn-primary openModal" data-action="Add">
            Add District
        </button>
    </div>

    <!-- TABLE -->
    <div class="card-box pd-20">
        <div class="table-responsive">
            <table id="districtTable" class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>District Name</th>
                        <th>Congressional</th>
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
                <div class="modal-body" id="modalContent">
                    Loading...
                </div>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
</div>
</div>

<!-- JS -->
<script src="vendors/scripts/core.js"></script>
        <script src="vendors/scripts/script.min.js"></script>
        <script src="vendors/scripts/process.js"></script>
        <script src="vendors/scripts/layout-settings.js"></script>
        <script src="src/plugins/datatables/js/jquery.dataTables.min.js"></script>
        <script src="src/plugins/datatables/js/dataTables.bootstrap4.min.js"></script>
        <script src="src/plugins/datatables/js/dataTables.responsive.min.js"></script>
        <script src="src/plugins/datatables/js/responsive.bootstrap4.min.js"></script>
        <!-- buttons for Export datatable -->
        <script src="src/plugins/datatables/js/dataTables.buttons.min.js"></script>
        <script src="src/plugins/datatables/js/buttons.bootstrap4.min.js"></script>
        <script src="src/plugins/datatables/js/buttons.print.min.js"></script>
        <script src="src/plugins/datatables/js/buttons.html5.min.js"></script>
        <script src="src/plugins/datatables/js/buttons.flash.min.js"></script>
        <script src="src/plugins/datatables/js/pdfmake.min.js"></script>
        <script src="src/plugins/datatables/js/vfs_fonts.js"></script>
        <!-- Datatable Setting js -->
        <script src="vendors/scripts/datatable-setting.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function () {

    const table = $('#districtTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "admin_ajax_district_list.php",
            type: "POST"
        },
        pageLength: 10,
        columns: [
            { data: "districtID" },
            { data: "district_name" },
            { data: "cong" },
            { data: "action", orderable: false, searchable: false }
        ]
    });

    // MODAL HANDLER
    document.addEventListener("click", function (e) {
        let btn = e.target.closest('.openModal');
        if (!btn) return;

        let id = btn.dataset.id;
        let action = btn.dataset.action;

        let url = "";

        if (action === "Add") url = "admin_add_district.php";
        else if (action === "View") url = "admin_view_district.php?id=" + id;
        else if (action === "Update") url = "admin_update_district.php?id=" + id;

        document.getElementById("modalTitle").innerText = action + " District";

        fetch(url)
        .then(res => res.text())
        .then(html => {
            document.getElementById("modalContent").innerHTML = html;

            // 🔥 important for modal JS execution
            let scripts = document.getElementById("modalContent").querySelectorAll("script");
            scripts.forEach(script => eval(script.innerText));

            new bootstrap.Modal(document.getElementById('actionModal')).show();
        });
    });

    // DELETE
    document.addEventListener("click", function (e) {
        let btn = e.target.closest('.btnDelete');
        if (!btn) return;

        let id = btn.dataset.id;

        Swal.fire({
            title: 'Are you sure?',
            text: "District will be deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {

            if (result.isConfirmed) {

                fetch('admin_query_district.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=delete&districtID=' + id
                })
                .then(res => res.json())
                .then(res => {

                    if (res.status === "success") {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        table.ajax.reload(null, false);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: res.message
                        });
                    }

                });

            }

        });
    });

    // ADD + UPDATE HANDLER
    document.addEventListener('submit', function(e){

        if(e.target.id === 'addDistrictForm' || e.target.id === 'updateDistrictForm'){
            e.preventDefault();

            fetch('admin_query_district.php',{
                method:'POST',
                body:new FormData(e.target)
            })
            .then(r=>r.json())
            .then(res=>{
                if(res.status==='success'){
                    Swal.fire({
                        icon:'success',
                        title:'Saved!',
                        timer:1500,
                        showConfirmButton:false
                    });

                    $('#actionModal').modal('hide');
                    table.ajax.reload(null,false);

                } else {
                    Swal.fire({
                        icon:'error',
                        title:'Error',
                        text:res.message
                    });
                }
            });
        }

    });

});
</script>

</body>
</html>