<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/org_units.php';
$userModel = new User($pdo);
require_login();
require_role([1]);
require_once __DIR__ . '/partials/session.php';

$totaluser = $db->count("sdopang1_user");
$schools = $pdo->query("
    SELECT schoolID, schoolname
    FROM sdopang1schoollist
    ORDER BY schoolname
")->fetchAll(PDO::FETCH_ASSOC);
$divisionUnits = org_division_units($pdo);
$officeUnitGroups = org_office_unit_groups($pdo);
$roles = $pdo->query("SELECT role_id, role_name FROM sdopang1_roles ORDER BY role_id")->fetchAll(PDO::FETCH_ASSOC);
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
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 pb-20">
            <div>
              <h2 class="mb-1">User List <span class="fw-normal text-700 ms-3">(<?= $totaluser ?>)</span></h2>
              <p class="text-700 mb-0">Manage accounts, roles, units, and validation task assignments.</p>
            </div>
            <button class="btn btn-add openModal" data-action="Add">
                <i class="bi bi-plus-lg"></i> Add User
            </button>
          </div>



        <div class="row pb-10">

          <div class="col-md-12 mb-20">
            <div class="card-box height-100-p pd-20">
             
             <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h5 class="mb-0">Accounts</h5>
                </div>
                <div class="row mb-3">
                  <div class="col-md-3 mb-2">
                    <label class="form-label">User Role</label>
                    <select id="filterRole" class="form-control">
                      <option value="">All roles</option>
                      <?php foreach ($roles as $role): ?>
                        <option value="<?= htmlspecialchars((string) $role['role_id'], ENT_QUOTES, 'UTF-8') ?>">
                          <?= htmlspecialchars($role['role_name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-3 mb-2">
                    <label class="form-label">Personnel Type</label>
                    <select id="filterPersonnelType" class="form-control">
                      <option value="">All</option>
                      <option value="Teaching">Teaching</option>
                      <option value="Non-Teaching">Non-Teaching</option>
                    </select>
                  </div>
                  <div class="col-md-3 mb-2">
                    <label class="form-label">Form 6 Unit</label>
                    <select id="filterDivisionUnit" class="form-control">
                      <option value="">All</option>
                      <?php foreach ($divisionUnits as $divisionUnit): ?>
                        <option value="<?= htmlspecialchars((string) $divisionUnit['division_unit_id'], ENT_QUOTES, 'UTF-8') ?>">
                          <?= htmlspecialchars($divisionUnit['unit_name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-3 mb-2">
                    <label class="form-label">School</label>
                    <select id="filterSchool" class="form-control custom-select2">
                      <option value="">All schools</option>
                      <?php foreach ($schools as $school): ?>
                        <option value="<?= htmlspecialchars((string) $school['schoolID'], ENT_QUOTES, 'UTF-8') ?>">
                          <?= htmlspecialchars($school['schoolname'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-3 mb-2">
                    <label class="form-label">Office Unit</label>
                    <select id="filterOffice" class="form-control custom-select2">
                      <option value="">All office units</option>
                      <?php foreach ($officeUnitGroups as $group): ?>
                        <?php if ($group['items']): ?>
                          <optgroup label="<?= htmlspecialchars($group['label'], ENT_QUOTES, 'UTF-8') ?>">
                            <?php foreach ($group['items'] as $officeUnit): ?>
                              <option value="<?= htmlspecialchars((string) $officeUnit['office_unit_id'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($officeUnit['unit_name'], ENT_QUOTES, 'UTF-8') ?>
                              </option>
                            <?php endforeach; ?>
                          </optgroup>
                        <?php endif; ?>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="table-responsive prime-table-wrap">
                <table id="userTable" class="table table-bordered">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>School ID</th>
                    <th>School</th>
                    <th>Position</th>
                    <th>Role</th>
                    <th>Form 6 Unit</th>
                    <th>Office Unit</th>
                    <th>Action</th>
                </tr>
                </thead>
                </table>
                </div>
              </div>
              
            </div>
          </div>
          
        </div>

        <div class="modal fade" id="actionModal">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">

              <div class="modal-header bg-primary">
                <h5 class="modal-title text-white" id="modalTitle"></h5>
                <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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

    <!-- SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function () {

    const table = $('#userTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "admin_ajax_users_list.php",
            type: "POST",
            data: function(d) {
                d.personnel_type = $('#filterPersonnelType').val();
                d.division_unit_id = $('#filterDivisionUnit').val();
                d.school_id = $('#filterSchool').val();
                d.office_unit_id = $('#filterOffice').val();
                d.role_id = $('#filterRole').val();
            }
        },
        pageLength: 10,
        columns: [
            { data: "name" },
            { data: "school_id" },
            { data: "schoolname" },
            { data: "position_title" },
            { data: "role_name" },
            { data: "division_unit" },
            { data: "office_name" },
            { data: "action", orderable: false, searchable: false }
        ],
        drawCallback: function () {
            PrimeUI.enhance(document.getElementById('userTable'));
        }
    });

    $('#filterRole, #filterPersonnelType, #filterDivisionUnit, #filterSchool, #filterOffice').on('change', function () {
        table.ajax.reload();
    });

    // MODAL
    document.addEventListener("click", function (e) {
        let btn = e.target.closest('.openModal');
        if (!btn) return;

        let id = btn.dataset.id;
        let action = btn.dataset.action;

        let url = "";

        if (action === "Add") url = "admin_add_user.php";
        else if (action === "View") url = "admin_view_user.php?id=" + id;
        else if (action === "Update") url = "admin_update_user.php?id=" + id;

        document.getElementById("modalTitle").innerText = action + " User";
        PrimeUI.loading('Loading form', 'Preparing the user form.');

        fetch(url)
        .then(res => res.text())
        .then(html => {
            if (window.Swal) Swal.close();
            document.getElementById("modalContent").innerHTML = html;
            PrimeUI.enhance(document.getElementById("modalContent"));
            new bootstrap.Modal(document.getElementById('actionModal')).show();
        })
        .catch(() => {
            if (window.Swal) Swal.close();
            PrimeUI.error('Unable to load the selected form.');
        });
    });

    // DELETE
    document.addEventListener("click", function (e) {
        let btn = e.target.closest('.btnDelete');
        if (!btn) return;

        let id = btn.dataset.id;

        PrimeUI.confirmDelete("This user account will be deleted.").then((result) => {

            if (result.isConfirmed) {
                PrimeUI.loading('Deleting user', 'Please wait while the account is removed.');
                fetch("admin_delete_user.php?id=" + id)
                .then(() => {
                    PrimeUI.success('User deleted successfully.', 'Deleted');
                    table.ajax.reload(null, false);
                })
                .catch(() => {
                    PrimeUI.error('Unable to delete this user.');
                });
            }

        });
    });

});
</script>

<!-- SUCCESS ALERT -->
<?php if (isset($_SESSION['success_message'])): ?>
<script>
PrimeUI.success(<?= json_encode($_SESSION['success_message']) ?>, 'Success');
</script>
<?php unset($_SESSION['success_message']); endif; ?>
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


