<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/csrf.php';

$userModel = new User($pdo);
require_login();
require_role([1, 2, 3, 5, 6, 7]);
require_once __DIR__ . '/partials/session.php';

$pdo->exec("
    CREATE TABLE IF NOT EXISTS employee_school_unit_assignments (
        assignment_id INT AUTO_INCREMENT PRIMARY KEY,
        school_id VARCHAR(50) NOT NULL,
        employee_name VARCHAR(255) NOT NULL,
        school_name VARCHAR(255) NOT NULL,
        functional_division_unit_name VARCHAR(255) NOT NULL,
        unit_head_name VARCHAR(255) NOT NULL,
        assigned_role VARCHAR(80) NOT NULL DEFAULT 'Employee',
        scope_type ENUM('admin','school','division_unit','office_unit') NOT NULL DEFAULT 'admin',
        scope_school_id VARCHAR(50) NULL,
        scope_division_unit_id INT NULL,
        scope_office_unit_id INT NULL,
        created_by INT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_school_id (school_id),
        INDEX idx_scope_school_id (scope_school_id),
        INDEX idx_scope_division_unit_id (scope_division_unit_id),
        INDEX idx_scope_office_unit_id (scope_office_unit_id),
        INDEX idx_employee_name (employee_name),
        INDEX idx_unit_head_name (unit_head_name),
        INDEX idx_assigned_role (assigned_role)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$columns = $pdo->query("SHOW COLUMNS FROM employee_school_unit_assignments")->fetchAll(PDO::FETCH_COLUMN);
$columnSql = [
    'scope_type' => "ALTER TABLE employee_school_unit_assignments ADD COLUMN scope_type ENUM('admin','school','division_unit','office_unit') NOT NULL DEFAULT 'admin' AFTER assigned_role",
    'scope_school_id' => "ALTER TABLE employee_school_unit_assignments ADD COLUMN scope_school_id VARCHAR(50) NULL AFTER scope_type",
    'scope_division_unit_id' => "ALTER TABLE employee_school_unit_assignments ADD COLUMN scope_division_unit_id INT NULL AFTER scope_school_id",
    'scope_office_unit_id' => "ALTER TABLE employee_school_unit_assignments ADD COLUMN scope_office_unit_id INT NULL AFTER scope_division_unit_id",
    'created_by' => "ALTER TABLE employee_school_unit_assignments ADD COLUMN created_by INT NULL AFTER scope_office_unit_id",
];
foreach ($columnSql as $column => $sql) {
    if (!in_array($column, $columns, true)) {
        $pdo->exec($sql);
    }
}

$scopeStmt = $pdo->prepare("
    SELECT u.user_id, u.role_id, u.school_id, u.division_unit_id, u.office_unit_id,
           CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) AS fullname,
           s.schoolname,
           du.unit_name AS division_unit_name,
           ou.unit_name AS office_unit_name
    FROM sdopang1_user u
    LEFT JOIN sdopang1schoollist s ON s.schoolID = u.school_id
    LEFT JOIN division_units du ON du.division_unit_id = u.division_unit_id
    LEFT JOIN office_units ou ON ou.office_unit_id = u.office_unit_id
    WHERE u.user_id = ?
    LIMIT 1
");
$scopeStmt->execute([$_SESSION['user_id']]);
$currentAccount = $scopeStmt->fetch(PDO::FETCH_ASSOC) ?: [];
$roleId = (int) ($currentAccount['role_id'] ?? $_SESSION['role_id'] ?? 0);
$isAdmin = $roleId === 1;
$isSchoolScope = $roleId === 3;
$scopeLabel = 'All school and division unit assignments';
$lockedSchoolId = '';
$lockedSchoolName = '';
$lockedUnitName = '';
$lockedUnitHead = trim((string) ($currentAccount['fullname'] ?? ''));
if ($isSchoolScope) {
    $lockedSchoolId = (string) ($currentAccount['school_id'] ?? '');
    $lockedSchoolName = (string) ($currentAccount['schoolname'] ?? '');
    $lockedUnitName = 'School Personnel';
    $scopeLabel = 'Only employees under ' . ($lockedSchoolName ?: 'your assigned school');
} elseif (!$isAdmin) {
    $lockedSchoolId = 'DIVISION';
    $lockedSchoolName = 'Schools Division Office 1 Pangasinan';
    $lockedUnitName = (string) (($currentAccount['office_unit_name'] ?? '') ?: ($currentAccount['division_unit_name'] ?? 'Assigned Division Unit'));
    $scopeLabel = 'Only employees under ' . $lockedUnitName;
}

$assignmentWhere = '1=1';
$assignmentParams = [];
if ($isSchoolScope) {
    $assignmentWhere = "scope_type = 'school' AND scope_school_id = ?";
    $assignmentParams[] = (string) ($currentAccount['school_id'] ?? '');
} elseif (!$isAdmin) {
    $assignmentWhere = "((scope_type = 'office_unit' AND scope_office_unit_id = ?) OR (scope_type = 'division_unit' AND scope_division_unit_id = ?))";
    $assignmentParams[] = (int) ($currentAccount['office_unit_id'] ?? 0);
    $assignmentParams[] = (int) ($currentAccount['division_unit_id'] ?? 0);
}
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM employee_school_unit_assignments WHERE $assignmentWhere");
$totalStmt->execute($assignmentParams);
$total = (int) $totalStmt->fetchColumn();
$userWhere = '1=1';
$userParams = [];
if ($isSchoolScope) {
    $userWhere = 'u.school_id = ?';
    $userParams[] = (string) ($currentAccount['school_id'] ?? '');
} elseif (!$isAdmin) {
    $officeUnitId = (int) ($currentAccount['office_unit_id'] ?? 0);
    $divisionUnitId = (int) ($currentAccount['division_unit_id'] ?? 0);
    $userWhere = '((? > 0 AND u.office_unit_id = ?) OR (? > 0 AND u.division_unit_id = ?))';
    $userParams = [$officeUnitId, $officeUnitId, $divisionUnitId, $divisionUnitId];
}
$userCountStmt = $pdo->prepare("SELECT COUNT(*) FROM sdopang1_user u WHERE $userWhere");
$userCountStmt->execute($userParams);
$scopedUserTotal = (int) $userCountStmt->fetchColumn();
$token = generateToken();
$canManageSchoolEmployees = $isSchoolScope;
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

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center pb-20">
        <div>
            <h2 class="mb-1"><?= $isSchoolScope ? 'My School Employees' : 'Employee School / Unit Assignments' ?></h2>
            <p class="text-700 mb-0"><?= htmlspecialchars($scopeLabel, ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <?php if ($canManageSchoolEmployees): ?>
        <button type="button" class="btn btn-primary mt-3 mt-lg-0" id="btnAddUser">
            Add Employee
        </button>
        <?php endif; ?>
    </div>

    <div class="card-box pd-20 mb-20">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3">
            <div>
                <h5 class="mb-1">Existing PRIMEHR Users (<?= $scopedUserTotal ?>)</h5>
                <p class="text-700 mb-0">Actual user records from the User List, filtered by your assigned school or unit.</p>
            </div>
        </div>
        <div class="table-responsive">
            <table id="scopedUserTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>School ID</th>
                        <th>Employee Name</th>
                        <th>School Name</th>
                        <th>Role</th>
                        <th>Form 6 Unit</th>
                        <th>Office Unit</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="card-box pd-20">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3">
            <div>
                <h5 class="mb-1">Manual School / Unit Assignments (<?= $total ?>)</h5>
                <p class="text-700 mb-0">Supplemental records for school/unit assignment notes.</p>
            </div>
            <button type="button" class="btn btn-outline-primary mt-3 mt-lg-0" id="btnAddAssignment">
                Add Assignment
            </button>
        </div>
        <div class="table-responsive">
            <table id="assignmentTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>School ID</th>
                        <th>Employee Name</th>
                        <th>School Name</th>
                        <th>Functional Division / Unit</th>
                        <th>Unit Head</th>
                        <th>Role</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="modal fade" id="userFormModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white" id="userFormTitle">Add Employee</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="userForm">
                    <div class="modal-body">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" id="userFormAction" value="add">
                        <input type="hidden" name="user_id" id="userFormUserId">
                        <input type="hidden" name="school_id" id="userFormSchoolId" value="<?= htmlspecialchars($lockedSchoolId, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label>First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" id="userFirstName" class="form-control" required maxlength="100">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Middle Name</label>
                                <input type="text" name="middle_name" id="userMiddleName" class="form-control" maxlength="100">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" id="userLastName" class="form-control" required maxlength="100">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Employee ID</label>
                                <input type="text" name="employeeID" id="userEmployeeId" class="form-control" maxlength="120">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Email <span class="text-danger">*</span></label>
                                <input type="email" name="email" id="userEmail" class="form-control" required maxlength="180">
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Password <span class="text-danger password-required">*</span></label>
                                <input type="password" name="password" id="userPassword" class="form-control" autocomplete="new-password">
                                <small class="form-text text-muted">Required for new employee. Leave blank when updating to keep the current password.</small>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Status <span class="text-danger">*</span></label>
                                <select name="status" id="userStatus" class="form-control" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>School ID</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($lockedSchoolId, ENT_QUOTES, 'UTF-8') ?>" readonly>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>School Name</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($lockedSchoolName, ENT_QUOTES, 'UTF-8') ?>" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Employee</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="userViewModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info">
                    <h5 class="modal-title text-white">View Employee</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group"><label>User ID</label><input id="viewUserId" class="form-control" readonly></div>
                        <div class="col-md-6 form-group"><label>Employee ID</label><input id="viewEmployeeId" class="form-control" readonly></div>
                        <div class="col-md-6 form-group"><label>Employee Name</label><input id="viewEmployeeName" class="form-control" readonly></div>
                        <div class="col-md-6 form-group"><label>Email</label><input id="viewEmail" class="form-control" readonly></div>
                        <div class="col-md-6 form-group"><label>School ID</label><input id="viewSchoolId" class="form-control" readonly></div>
                        <div class="col-md-6 form-group"><label>School Name</label><input id="viewSchoolName" class="form-control" readonly></div>
                        <div class="col-md-4 form-group"><label>Role</label><input id="viewRoleName" class="form-control" readonly></div>
                        <div class="col-md-4 form-group"><label>Form 6 Unit</label><input id="viewDivisionUnit" class="form-control" readonly></div>
                        <div class="col-md-4 form-group"><label>Office Unit</label><input id="viewOfficeUnit" class="form-control" readonly></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="assignmentModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white" id="assignmentModalTitle">Add Assignment</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="assignmentForm">
                    <div class="modal-body">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="assignment_id" id="assignmentId">

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="schoolId">School ID <span class="text-danger">*</span></label>
                                <input type="text" name="school_id" id="schoolId" class="form-control" required maxlength="50" placeholder="e.g. 100123" value="<?= htmlspecialchars($lockedSchoolId, ENT_QUOTES, 'UTF-8') ?>" <?= !$isAdmin ? 'readonly' : '' ?>>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="assignedRole">Role to Assign <span class="text-danger">*</span></label>
                                <select name="assigned_role" id="assignedRole" class="form-control" required>
                                    <option value="Employee">Employee</option>
                                    <option value="Unit Head">Unit Head</option>
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="employeeName">Employee Name <span class="text-danger">*</span></label>
                                <input type="text" name="employee_name" id="employeeName" class="form-control" required maxlength="255" placeholder="Complete employee name">
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="schoolName">School Name <span class="text-danger">*</span></label>
                                <input type="text" name="school_name" id="schoolName" class="form-control" required maxlength="255" placeholder="Official school name" value="<?= htmlspecialchars($lockedSchoolName, ENT_QUOTES, 'UTF-8') ?>" <?= !$isAdmin ? 'readonly' : '' ?>>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="divisionUnitName">Functional Division / Unit Name <span class="text-danger">*</span></label>
                                <input type="text" name="functional_division_unit_name" id="divisionUnitName" class="form-control" required maxlength="255" placeholder="e.g. HR Unit, Curriculum Unit" value="<?= htmlspecialchars($lockedUnitName, ENT_QUOTES, 'UTF-8') ?>" <?= !$isAdmin ? 'readonly' : '' ?>>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="unitHeadName">Unit Head Name <span class="text-danger">*</span></label>
                                <input type="text" name="unit_head_name" id="unitHeadName" class="form-control" required maxlength="255" placeholder="Complete unit head name" value="<?= htmlspecialchars($lockedUnitHead, ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="btnSaveAssignment">Save Assignment</button>
                    </div>
                </form>
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
$(document).ready(function () {
    const employeeTable = $('#scopedUserTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'admin_ajax_scoped_users.php',
            type: 'POST'
        },
        pageLength: 10,
        dom: "<'row mb-3'<'col-md-6'B><'col-md-6'f>>" +
             "<'row'<'col-12'tr>>" +
             "<'row mt-3'<'col-md-5'i><'col-md-7'p>>",
        buttons: [
            { extend: 'excelHtml5', text: 'Export Excel', className: 'btn btn-success btn-sm' },
            { extend: 'csvHtml5', text: 'Export CSV', className: 'btn btn-info btn-sm' },
            { extend: 'pdfHtml5', text: 'Export PDF', className: 'btn btn-danger btn-sm', orientation: 'landscape', pageSize: 'LEGAL' },
            { extend: 'print', text: 'Print', className: 'btn btn-secondary btn-sm' }
        ],
        columns: [
            { data: 'user_id' },
            { data: 'school_id' },
            { data: 'employee_name' },
            { data: 'school_name' },
            { data: 'role_name' },
            { data: 'division_unit' },
            { data: 'office_unit' },
            { data: 'action', orderable: false, searchable: false }
        ]
    });

    const table = $('#assignmentTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'admin_ajax_employee_school_units.php',
            type: 'POST'
        },
        pageLength: 10,
        dom: "<'row mb-3'<'col-md-6'B><'col-md-6'f>>" +
             "<'row'<'col-12'tr>>" +
             "<'row mt-3'<'col-md-5'i><'col-md-7'p>>",
        buttons: [
            { extend: 'excelHtml5', text: 'Export Excel', className: 'btn btn-success btn-sm' },
            { extend: 'csvHtml5', text: 'Export CSV', className: 'btn btn-info btn-sm' },
            { extend: 'pdfHtml5', text: 'Export PDF', className: 'btn btn-danger btn-sm', orientation: 'landscape', pageSize: 'LEGAL' },
            { extend: 'print', text: 'Print', className: 'btn btn-secondary btn-sm' }
        ],
        columns: [
            { data: 'assignment_id' },
            { data: 'school_id' },
            { data: 'employee_name' },
            { data: 'school_name' },
            { data: 'functional_division_unit_name' },
            { data: 'unit_head_name' },
            { data: 'assigned_role' },
            { data: 'action', orderable: false, searchable: false }
        ]
    });

    function resetUserForm() {
        $('#userForm')[0].reset();
        $('#userFormAction').val('add');
        $('#userFormUserId').val('');
        $('#userFormSchoolId').val(<?= json_encode($lockedSchoolId) ?>);
        $('#userPassword').prop('required', true);
        $('.password-required').removeClass('d-none');
        $('#userFormTitle').text('Add Employee');
    }

    $('#btnAddUser').on('click', function () {
        resetUserForm();
        $('#userFormModal').modal('show');
    });

    function resetForm() {
        $('#assignmentForm')[0].reset();
        $('#assignmentId').val('');
        $('#formAction').val('add');
        $('#schoolId').val(<?= json_encode($lockedSchoolId) ?>);
        $('#schoolName').val(<?= json_encode($lockedSchoolName) ?>);
        $('#divisionUnitName').val(<?= json_encode($lockedUnitName) ?>);
        $('#unitHeadName').val(<?= json_encode($lockedUnitHead) ?>);
        $('#assignmentForm').find('input, select').prop('disabled', false);
        $('#assignmentForm input[name="token"], #formAction, #assignmentId').prop('disabled', false);
        <?php if (!$isAdmin): ?>
        $('#schoolId, #schoolName, #divisionUnitName').prop('readonly', true);
        <?php endif; ?>
        $('#btnSaveAssignment').removeClass('d-none');
        $('#assignmentModalTitle').text('Add Assignment');
    }

    $('#btnAddAssignment').on('click', function () {
        resetForm();
        $('#assignmentModal').modal('show');
    });

    document.addEventListener('click', function (e) {
        const viewUserBtn = e.target.closest('.btnViewUser');
        if (viewUserBtn) {
            $('#viewUserId').val(viewUserBtn.dataset.userId || '');
            $('#viewEmployeeId').val(viewUserBtn.dataset.employeeId || '');
            $('#viewEmployeeName').val(viewUserBtn.dataset.employeeName || '');
            $('#viewEmail').val(viewUserBtn.dataset.email || '');
            $('#viewSchoolId').val(viewUserBtn.dataset.schoolId || '');
            $('#viewSchoolName').val(viewUserBtn.dataset.schoolName || '');
            $('#viewRoleName').val(viewUserBtn.dataset.roleName || '');
            $('#viewDivisionUnit').val(viewUserBtn.dataset.divisionUnit || '');
            $('#viewOfficeUnit').val(viewUserBtn.dataset.officeUnit || '');
            $('#userViewModal').modal('show');
            return;
        }

        const updateUserBtn = e.target.closest('.btnUpdateUser');
        if (updateUserBtn) {
            resetUserForm();
            $('#userFormTitle').text('Update Employee');
            $('#userFormAction').val('update');
            $('#userFormUserId').val(updateUserBtn.dataset.userId || '');
            $('#userFirstName').val(updateUserBtn.dataset.firstName || '');
            $('#userMiddleName').val(updateUserBtn.dataset.middleName || '');
            $('#userLastName').val(updateUserBtn.dataset.lastName || '');
            $('#userEmployeeId').val(updateUserBtn.dataset.employeeId || '');
            $('#userEmail').val(updateUserBtn.dataset.email || '');
            $('#userStatus').val(updateUserBtn.dataset.status || 'active');
            $('#userPassword').val('').prop('required', false);
            $('.password-required').addClass('d-none');
            $('#userFormModal').modal('show');
            return;
        }

        const deleteUserBtn = e.target.closest('.btnDeleteUser');
        if (deleteUserBtn) {
            Swal.fire({
                icon: 'warning',
                title: 'Delete employee?',
                text: 'This employee user account will be removed from your school list.',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#d33'
            }).then(result => {
                if (!result.isConfirmed) return;

                const body = new URLSearchParams();
                body.set('action', 'delete');
                body.set('user_id', deleteUserBtn.dataset.userId);
                body.set('token', '<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>');

                fetch('admin_query_scoped_user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body.toString()
                })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'Deleted', timer: 1400, showConfirmButton: false });
                        employeeTable.ajax.reload(null, false);
                    } else {
                        Swal.fire({ icon: 'error', title: 'Delete failed', text: res.message || 'Unable to delete employee.' });
                    }
                })
                .catch(() => Swal.fire({ icon: 'error', title: 'Delete failed', text: 'Please check the connection or server logs.' }));
            });
            return;
        }

        const viewBtn = e.target.closest('.btnViewAssignment');
        if (viewBtn) {
            resetForm();
            $('#assignmentModalTitle').text('View Assignment');
            $('#assignmentId').val(viewBtn.dataset.id);
            $('#schoolId').val(viewBtn.dataset.schoolId);
            $('#employeeName').val(viewBtn.dataset.employeeName);
            $('#schoolName').val(viewBtn.dataset.schoolName);
            $('#divisionUnitName').val(viewBtn.dataset.divisionUnit);
            $('#unitHeadName').val(viewBtn.dataset.unitHead);
            $('#assignedRole').val(viewBtn.dataset.assignedRole);
            $('#assignmentForm').find('input, select').prop('disabled', true);
            $('#btnSaveAssignment').addClass('d-none');
            $('#assignmentModal').modal('show');
            return;
        }

        const updateBtn = e.target.closest('.btnUpdateAssignment');
        if (updateBtn) {
            resetForm();
            $('#assignmentModalTitle').text('Update Assignment');
            $('#formAction').val('update');
            $('#assignmentId').val(updateBtn.dataset.id);
            $('#schoolId').val(updateBtn.dataset.schoolId);
            $('#employeeName').val(updateBtn.dataset.employeeName);
            $('#schoolName').val(updateBtn.dataset.schoolName);
            $('#divisionUnitName').val(updateBtn.dataset.divisionUnit);
            $('#unitHeadName').val(updateBtn.dataset.unitHead);
            $('#assignedRole').val(updateBtn.dataset.assignedRole);
            $('#assignmentModal').modal('show');
            return;
        }

        const deleteBtn = e.target.closest('.btnDeleteAssignment');
        if (!deleteBtn) return;

        Swal.fire({
            icon: 'warning',
            title: 'Delete assignment?',
            text: 'This school/unit assignment record will be removed.',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#d33'
        }).then(result => {
            if (!result.isConfirmed) return;

            const body = new URLSearchParams();
            body.set('action', 'delete');
            body.set('assignment_id', deleteBtn.dataset.id);
            body.set('token', '<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>');

            fetch('admin_query_employee_school_units.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Deleted', timer: 1400, showConfirmButton: false });
                    table.ajax.reload(null, false);
                } else {
                    Swal.fire({ icon: 'error', title: 'Delete failed', text: res.message || 'Unable to delete assignment.' });
                }
            })
            .catch(() => Swal.fire({ icon: 'error', title: 'Delete failed', text: 'Please check the connection or server logs.' }));
        });
    });

    $('#userForm').on('submit', function (e) {
        e.preventDefault();

        fetch('admin_query_scoped_user.php', {
            method: 'POST',
            body: new FormData(this)
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Saved', timer: 1400, showConfirmButton: false });
                $('#userFormModal').modal('hide');
                employeeTable.ajax.reload(null, false);
            } else {
                Swal.fire({ icon: 'error', title: 'Save failed', text: res.message || 'Unable to save employee.' });
            }
        })
        .catch(() => Swal.fire({ icon: 'error', title: 'Save failed', text: 'Please check the connection or server logs.' }));
    });

    $('#assignmentForm').on('submit', function (e) {
        e.preventDefault();

        fetch('admin_query_employee_school_units.php', {
            method: 'POST',
            body: new FormData(this)
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Saved', timer: 1400, showConfirmButton: false });
                $('#assignmentModal').modal('hide');
                table.ajax.reload(null, false);
            } else {
                Swal.fire({ icon: 'error', title: 'Save failed', text: res.message || 'Unable to save assignment.' });
            }
        })
        .catch(() => Swal.fire({ icon: 'error', title: 'Save failed', text: 'Please check the connection or server logs.' }));
    });
});
</script>

</body>
</html>
