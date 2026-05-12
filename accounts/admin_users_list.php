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
  <style>
    .user-export-actions .dt-buttons {
      display: inline-flex;
      flex-wrap: wrap;
      gap: 7px;
      align-items: center;
      width: fit-content;
      max-width: 100%;
    }

    .user-export-btn {
      position: relative;
      min-height: 36px;
      border: 0 !important;
      border-radius: 10px !important;
      padding: 8px 14px !important;
      display: inline-flex !important;
      align-items: center;
      justify-content: center;
      gap: 7px;
      overflow: hidden;
      font-size: 13px !important;
      font-weight: 800 !important;
      letter-spacing: 0;
      line-height: 1;
      box-shadow: 0 5px 12px rgba(15, 23, 42, 0.1) !important;
      transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease, background 0.22s ease;
    }

    .user-export-btn::before {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.22), rgba(255, 255, 255, 0));
      pointer-events: none;
    }

    .user-export-btn:hover,
    .user-export-btn:focus {
      transform: translateY(-1px);
      box-shadow: 0 8px 16px rgba(15, 23, 42, 0.14) !important;
    }

    .user-export-btn i {
      position: relative;
      z-index: 1;
      width: 22px;
      height: 22px;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex: 0 0 22px;
      font-size: 14px;
      background: rgba(255, 255, 255, 0.22);
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.24);
      backdrop-filter: blur(8px);
    }

    .user-export-btn .export-btn-label {
      position: relative;
      z-index: 1;
      line-height: 1.15;
      text-align: center;
      white-space: nowrap;
    }

    .user-export-btn--excel {
      color: #fff !important;
      background: linear-gradient(135deg, #16a34a, #22c55e) !important;
    }

    .user-export-btn--pdf {
      color: #fff !important;
      background: linear-gradient(135deg, #dc2626, #ef4444) !important;
    }

    .user-export-btn--print {
      color: #2563eb !important;
      background: #fff !important;
      border: 2px solid #2563eb !important;
      box-shadow: 0 5px 12px rgba(37, 99, 235, 0.11) !important;
    }

    .user-export-btn--print::before {
      background: linear-gradient(135deg, rgba(37, 99, 235, 0.08), rgba(255, 255, 255, 0));
    }

    .user-export-btn--print i {
      background: rgba(37, 99, 235, 0.1);
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
    }

    .user-export-btn--print:hover,
    .user-export-btn--print:focus {
      border-color: #1d4ed8 !important;
      box-shadow: 0 8px 16px rgba(37, 99, 235, 0.15) !important;
    }
  </style>
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
    <script src="src/plugins/datatables/js/dataTables.buttons.min.js"></script>
    <script src="src/plugins/datatables/js/buttons.bootstrap4.min.js"></script>
    <script src="src/plugins/datatables/js/jszip.min.js"></script>
    <script src="src/plugins/datatables/js/pdfmake.min.js"></script>
    <script src="src/plugins/datatables/js/vfs_fonts.js"></script>
    <script src="src/plugins/datatables/js/buttons.html5.min.js"></script>
    <script src="src/plugins/datatables/js/buttons.print.min.js"></script>
    <script src="src/plugins/datatables/js/dataTables.responsive.min.js"></script>
    <script src="src/plugins/datatables/js/responsive.bootstrap4.min.js"></script>

    <!-- SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function () {

    const exportColumns = [
        { key: 'name', label: 'Name' },
        { key: 'school_id', label: 'School ID' },
        { key: 'schoolname', label: 'School' },
        { key: 'position_title', label: 'Position' },
        { key: 'role_name', label: 'Role' },
        { key: 'division_unit', label: 'Form 6 Unit' },
        { key: 'office_name', label: 'Office Unit' }
    ];

    function htmlEscape(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function fetchAllUsersForExport(dt) {
        return $.ajax({
            url: 'admin_ajax_users_list.php',
            type: 'POST',
            dataType: 'json',
            data: {
                draw: 1,
                start: 0,
                length: 1000000,
                search: { value: dt.search() },
                personnel_type: $('#filterPersonnelType').val(),
                division_unit_id: $('#filterDivisionUnit').val(),
                school_id: $('#filterSchool').val(),
                office_unit_id: $('#filterOffice').val(),
                role_id: $('#filterRole').val()
            }
        }).then(function(response) {
            return response && response.data ? response.data : [];
        });
    }

    function downloadExcel(rows) {
        let tableHtml = '<table><thead><tr>';
        exportColumns.forEach(function(column) {
            tableHtml += '<th>' + htmlEscape(column.label) + '</th>';
        });
        tableHtml += '</tr></thead><tbody>';
        rows.forEach(function(row) {
            tableHtml += '<tr>';
            exportColumns.forEach(function(column) {
                tableHtml += '<td>' + htmlEscape(row[column.key]) + '</td>';
            });
            tableHtml += '</tr>';
        });
        tableHtml += '</tbody></table>';

        const blob = new Blob(['\ufeff' + tableHtml], { type: 'application/vnd.ms-excel;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'PRIMEHR_User_List.xls';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    function downloadPdf(rows) {
        if (!window.pdfMake) {
            PrimeUI.error('PDF export tools are not loaded. Please refresh the page.');
            return;
        }

        const body = [
            exportColumns.map(function(column) {
                return { text: column.label, style: 'tableHeader' };
            })
        ];

        rows.forEach(function(row) {
            body.push(exportColumns.map(function(column) {
                return String(row[column.key] ?? '');
            }));
        });

        pdfMake.createPdf({
            pageOrientation: 'landscape',
            pageSize: 'A4',
            content: [
                { text: 'PRIMEHR User List', style: 'title' },
                {
                    table: {
                        headerRows: 1,
                        widths: ['*', 'auto', '*', '*', '*', '*', '*'],
                        body: body
                    },
                    layout: 'lightHorizontalLines'
                }
            ],
            styles: {
                title: { fontSize: 16, bold: true, margin: [0, 0, 0, 12] },
                tableHeader: { bold: true, fillColor: '#eff6ff', color: '#0f172a' }
            },
            defaultStyle: { fontSize: 8 }
        }).download('PRIMEHR_User_List.pdf');
    }

    function printRows(rows) {
        const printWindow = window.open('', '_blank');
        if (!printWindow) {
            PrimeUI.error('Please allow pop-ups to print the user list.');
            return;
        }

        let rowsHtml = '';
        rows.forEach(function(row) {
            rowsHtml += '<tr>';
            exportColumns.forEach(function(column) {
                rowsHtml += '<td>' + htmlEscape(row[column.key]) + '</td>';
            });
            rowsHtml += '</tr>';
        });

        printWindow.document.write(`
            <!doctype html>
            <html>
              <head>
                <title>PRIMEHR User List</title>
                <style>
                  body { font-family: Arial, sans-serif; color: #0f172a; }
                  h2 { margin-bottom: 12px; }
                  table { width: 100%; border-collapse: collapse; font-size: 11px; }
                  th, td { border: 1px solid #dbe5f1; padding: 7px; text-align: left; }
                  th { background: #eff6ff; }
                </style>
              </head>
              <body>
                <h2>PRIMEHR User List</h2>
                <table>
                  <thead>
                    <tr>${exportColumns.map(column => '<th>' + htmlEscape(column.label) + '</th>').join('')}</tr>
                  </thead>
                  <tbody>${rowsHtml}</tbody>
                </table>
              </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
    }

    function exportAllRows(e, dt, button, config) {
        const buttonNode = $(button);
        const originalText = buttonNode.html();
        buttonNode.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Preparing...');

        fetchAllUsersForExport(dt)
            .then(function(rows) {
                if (!rows.length) {
                    PrimeUI.error('No user records found for export.');
                    return;
                }

                if (config.exportType === 'excel') downloadExcel(rows);
                if (config.exportType === 'pdf') downloadPdf(rows);
                if (config.exportType === 'print') printRows(rows);
            })
            .catch(function() {
                PrimeUI.error('Unable to export the user list. Please try again.');
            })
            .always(function() {
                buttonNode.prop('disabled', false).html(originalText);
            });
    }

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
        dom:
            "<'row align-items-center mb-2'<'col-md-6'l><'col-md-6'f>>" +
            "<'row mb-3'<'col-12 user-export-actions'B>>" +
            "rt" +
            "<'row align-items-center mt-3'<'col-md-5'i><'col-md-7'p>>",
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="bi bi-file-earmark-excel"></i><span class="export-btn-label">Export Excel</span>',
                title: 'PRIMEHR User List',
                className: 'btn user-export-btn user-export-btn--excel',
                exportType: 'excel',
                action: exportAllRows,
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
                }
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="bi bi-file-earmark-pdf"></i><span class="export-btn-label">Export PDF</span>',
                title: 'PRIMEHR User List',
                orientation: 'landscape',
                pageSize: 'A4',
                className: 'btn user-export-btn user-export-btn--pdf',
                exportType: 'pdf',
                action: exportAllRows,
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
                }
            },
            {
                extend: 'print',
                text: '<i class="bi bi-printer"></i><span class="export-btn-label">Print</span>',
                title: 'PRIMEHR User List',
                className: 'btn user-export-btn user-export-btn--print',
                exportType: 'print',
                action: exportAllRows,
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
                }
            }
        ],
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


