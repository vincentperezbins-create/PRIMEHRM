<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
$userModel = new User($pdo);
require_login();
require_role([1]);
require_once __DIR__ . '/partials/session.php';

// COUNT
$totalSchool = $pdo->query("SELECT COUNT(*) FROM sdopang1schoollist")->fetchColumn();
?>

<!DOCTYPE html>
<html>
<?php require_once __DIR__ . '/partials/head.php'; ?>
<style>
    .school-export-actions {
        display: flex;
        justify-content: flex-start;
    }

    .school-export-actions .dt-buttons {
        display: inline-flex !important;
        flex-wrap: wrap;
        align-items: center;
        gap: 7px;
        width: fit-content !important;
        max-width: 100%;
    }

    .school-export-actions .dt-buttons .school-export-btn,
    .school-export-actions .dt-buttons .buttons-excel,
    .school-export-actions .dt-buttons .buttons-pdf,
    .school-export-actions .dt-buttons .buttons-print,
    .school-export-actions .dt-buttons button.school-export-btn,
    .school-export-actions .dt-buttons a.school-export-btn {
        position: relative;
        display: inline-flex !important;
        align-items: center;
        justify-content: center;
        gap: 7px;
        min-height: 36px;
        padding: 8px 14px !important;
        margin: 0 !important;
        border-radius: 10px !important;
        border: 1px solid transparent !important;
        font-size: 13px !important;
        font-weight: 800 !important;
        line-height: 1;
        letter-spacing: 0;
        box-shadow: 0 5px 12px rgba(15, 23, 42, 0.10) !important;
        background-image: none !important;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease, background 0.2s ease;
    }

    .school-export-actions .dt-buttons .school-export-btn:hover,
    .school-export-actions .dt-buttons .school-export-btn:focus,
    .school-export-actions .dt-buttons .buttons-excel:hover,
    .school-export-actions .dt-buttons .buttons-excel:focus,
    .school-export-actions .dt-buttons .buttons-pdf:hover,
    .school-export-actions .dt-buttons .buttons-pdf:focus,
    .school-export-actions .dt-buttons .buttons-print:hover,
    .school-export-actions .dt-buttons .buttons-print:focus {
        transform: translateY(-1px);
        box-shadow: 0 8px 16px rgba(15, 23, 42, 0.14) !important;
    }

    .school-export-actions .dt-buttons .school-export-btn i,
    .school-export-actions .dt-buttons .buttons-excel i,
    .school-export-actions .dt-buttons .buttons-pdf i,
    .school-export-actions .dt-buttons .buttons-print i {
        font-size: 14px;
        line-height: 1;
    }

    .school-export-actions .dt-buttons .school-export-btn--excel,
    .school-export-actions .dt-buttons .buttons-excel {
        color: #fff !important;
        background: linear-gradient(135deg, #16a34a, #22c55e) !important;
        background-color: #16a34a !important;
        background-image: linear-gradient(135deg, #16a34a, #22c55e) !important;
        border-color: #16a34a !important;
    }

    .school-export-actions .dt-buttons .school-export-btn--pdf,
    .school-export-actions .dt-buttons .buttons-pdf {
        color: #fff !important;
        background: linear-gradient(135deg, #dc2626, #ef4444) !important;
        background-color: #dc2626 !important;
        background-image: linear-gradient(135deg, #dc2626, #ef4444) !important;
        border-color: #dc2626 !important;
    }

    .school-export-actions .dt-buttons .school-export-btn--print,
    .school-export-actions .dt-buttons .buttons-print {
        color: #2563eb !important;
        background: #fff !important;
        background-color: #fff !important;
        background-image: none !important;
        border-color: #2563eb !important;
        box-shadow: 0 5px 12px rgba(37, 99, 235, 0.11) !important;
    }

    .school-export-actions .dt-buttons .school-export-btn--print:hover,
    .school-export-actions .dt-buttons .school-export-btn--print:focus,
    .school-export-actions .dt-buttons .buttons-print:hover,
    .school-export-actions .dt-buttons .buttons-print:focus {
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

    <!-- HEADER -->
    <div class="d-flex justify-content-between pb-20">
        <h2>School List (<?= $totalSchool ?>)</h2>

        <button class="btn btn-primary openModal" data-action="Add">
            Add School
        </button>
    </div>

    <!-- TABLE -->
    <div class="card-box pd-20">
        <div class="table-responsive">
            <table id="schoolTable" class="table table-bordered">
                <thead>
                    <tr>
                        <th>School ID</th>
                        <th>School Name</th>
                        <th>District</th>
                        <th>Address</th>
                        <th>Principal</th>
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
    const exportColumns = [
        { key: 'schoolID', label: 'School ID' },
        { key: 'schoolname', label: 'School Name' },
        { key: 'district', label: 'District' },
        { key: 'address', label: 'Address' },
        { key: 'principal_name', label: 'Principal' }
    ];

    function htmlEscape(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function fetchAllSchoolsForExport(dt) {
        return $.ajax({
            url: 'admin_ajax_school_list.php',
            type: 'POST',
            dataType: 'json',
            data: {
                draw: 1,
                start: 0,
                length: 1000000,
                search: { value: dt.search() }
            }
        }).then(function(response) {
            return response && response.data ? response.data : [];
        });
    }

    function notifyExportError(message) {
        if (window.PrimeUI && PrimeUI.error) {
            PrimeUI.error(message);
            return;
        }

        Swal.fire({ icon: 'error', title: 'Export failed', text: message });
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
        link.download = 'PRIMEHR_School_List.xls';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    function downloadPdf(rows) {
        if (!window.pdfMake) {
            notifyExportError('PDF export tools are not loaded. Please refresh the page.');
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
                { text: 'PRIMEHR School List', style: 'title' },
                {
                    table: {
                        headerRows: 1,
                        widths: ['auto', '*', '*', '*', '*'],
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
        }).download('PRIMEHR_School_List.pdf');
    }

    function printRows(rows) {
        const printWindow = window.open('', '_blank');
        if (!printWindow) {
            notifyExportError('Please allow pop-ups to print the school list.');
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
                <title>PRIMEHR School List</title>
                <style>
                  body { font-family: Arial, sans-serif; color: #0f172a; }
                  h2 { margin-bottom: 12px; }
                  table { width: 100%; border-collapse: collapse; font-size: 11px; }
                  th, td { border: 1px solid #dbe5f1; padding: 7px; text-align: left; }
                  th { background: #eff6ff; }
                </style>
              </head>
              <body>
                <h2>PRIMEHR School List</h2>
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

        fetchAllSchoolsForExport(dt)
            .then(function(rows) {
                if (!rows.length) {
                    notifyExportError('No school records found for export.');
                    return;
                }

                if (config.exportType === 'excel') downloadExcel(rows);
                if (config.exportType === 'pdf') downloadPdf(rows);
                if (config.exportType === 'print') printRows(rows);
            })
            .catch(function() {
                notifyExportError('Unable to export the school list. Please try again.');
            })
            .always(function() {
                buttonNode.prop('disabled', false).html(originalText);
            });
    }

    // ✅ DATATABLE
    const table = $('#schoolTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "admin_ajax_school_list.php",
            type: "POST"
        },
        pageLength: 10,
        dom:
            "<'row align-items-center mb-2'<'col-md-6'l><'col-md-6'f>>" +
            "<'row mb-3'<'col-12 school-export-actions'B>>" +
            "rt" +
            "<'row align-items-center mt-3'<'col-md-5'i><'col-md-7'p>>",
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="bi bi-file-earmark-excel"></i><span class="export-btn-label">Export Excel</span>',
                title: 'PRIMEHR School List',
                className: 'btn school-export-btn school-export-btn--excel',
                exportType: 'excel',
                action: exportAllRows
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="bi bi-file-earmark-pdf"></i><span class="export-btn-label">Export PDF</span>',
                title: 'PRIMEHR School List',
                orientation: 'landscape',
                pageSize: 'A4',
                className: 'btn school-export-btn school-export-btn--pdf',
                exportType: 'pdf',
                action: exportAllRows
            },
            {
                extend: 'print',
                text: '<i class="bi bi-printer"></i><span class="export-btn-label">Print</span>',
                title: 'PRIMEHR School List',
                className: 'btn school-export-btn school-export-btn--print',
                exportType: 'print',
                action: exportAllRows
            }
        ],
        columns: [
            { data: "schoolID" },
            { data: "schoolname" },
            { data: "district" },
            { data: "address" },
            { data: "principal_name" },
            { data: "action", orderable: false, searchable: false }
        ]
    });

    // ✅ MODAL HANDLER
    document.addEventListener("click", function (e) {
        let btn = e.target.closest('.openModal');
        if (!btn) return;

        let id = btn.dataset.id;
        let action = btn.dataset.action;

        let url = "";

        if (action === "Add") url = "admin_add_school.php";
        else if (action === "View") url = "admin_view_school.php?id=" + id;
        else if (action === "Update") url = "admin_update_school.php?id=" + id;
        

        document.getElementById("modalTitle").innerText = action + " School";

        fetch(url)
        .then(res => res.text())
        .then(html => {
            document.getElementById("modalContent").innerHTML = html;

            // 🔥 FORCE SCRIPT EXECUTION
            let scripts = document.getElementById("modalContent").querySelectorAll("script");
            scripts.forEach(script => {
                eval(script.innerText);
            });

            new bootstrap.Modal(document.getElementById('actionModal')).show();
        });
            });

    // ✅ DELETE (UPDATED)
    document.addEventListener("click", function (e) {
        let btn = e.target.closest('.btnDelete');
        if (!btn) return;

        let id = btn.dataset.id;

        Swal.fire({
            title: 'Are you sure?',
            text: "School will be deleted!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {

            if (result.isConfirmed) {

                fetch('admin_query_school.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=delete&schoolID=' + id
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

    // ✅ HANDLE ADD FORM (modal-safe)
document.addEventListener('submit', function(e){

    if(e.target.id === 'addSchoolForm'){
        e.preventDefault();

        let formData = new FormData(e.target);

        fetch('admin_query_school.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {

            if(res.status === "success"){
                Swal.fire({
                    icon: 'success',
                    title: 'School Added!',
                    timer: 1500,
                    showConfirmButton: false
                });

                $('#actionModal').modal('hide');
                $('#schoolTable').DataTable().ajax.reload(null, false);

            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: res.message
                });
            }

        });
    }

    // ✅ HANDLE UPDATE FORM
    if(e.target.id === 'updateSchoolForm'){
        e.preventDefault();

        let formData = new FormData(e.target);

        fetch('admin_query_school.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {

            if(res.status === "success"){
                Swal.fire({
                    icon: 'success',
                    title: 'Updated!',
                    timer: 1500,
                    showConfirmButton: false
                });

                $('#actionModal').modal('hide');
                $('#schoolTable').DataTable().ajax.reload(null, false);

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
</script>

</body>
</html>
