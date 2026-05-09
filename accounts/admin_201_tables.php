<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
$userModel = new User($pdo);
require_login();
require_once __DIR__ . '/partials/session.php';
require_validator($pdo, '201');
?>
<?php
                    // count admins
                    $total201 = $db->count("sdopang1_documents");
$total201Pending = $db->count("sdopang1_documents", "status = 'Pending'");
$total201Approved = $db->count("sdopang1_documents", "status = 'Approved'");
$total201Returned = $db->count("sdopang1_documents", "status = 'Returned'");

$users = $pdo->query("
    SELECT DISTINCT
        u.user_id,
        u.first_name,
        u.middle_name,
        u.last_name,
        u.employeeID
    FROM sdopang1_documents d
    JOIN sdopang1_user u ON u.user_id = d.user_id
    ORDER BY u.last_name, u.first_name, u.middle_name
")->fetchAll(PDO::FETCH_ASSOC);

$schools = $pdo->query("
    SELECT DISTINCT s.schoolID, s.schoolname
    FROM sdopang1_documents d
    JOIN sdopang1_user u ON u.user_id = d.user_id
    JOIN sdopang1schoollist s ON s.schoolID = u.school_id
    ORDER BY s.schoolname
")->fetchAll(PDO::FETCH_ASSOC);

$divisionUnits = $pdo->query("
    SELECT DISTINCT du.division_unit_id, du.unit_name
    FROM sdopang1_documents d
    JOIN sdopang1_user u ON u.user_id = d.user_id
    JOIN division_units du ON du.division_unit_id = u.division_unit_id
    ORDER BY du.sort_order, du.unit_name
")->fetchAll(PDO::FETCH_ASSOC);

$months = $pdo->query("
    SELECT DISTINCT DATE_FORMAT(uploaded_at, '%Y-%m') AS month_key
    FROM sdopang1_documents
    WHERE uploaded_at IS NOT NULL
    ORDER BY month_key DESC
")->fetchAll(PDO::FETCH_COLUMN);

$documents = $pdo->query("
    SELECT
        d.document_id,
        d.year,
        d.status,
        d.remarks,
        d.uploaded_at,
        t.doc_name,
        u.user_id,
        u.employeeID,
        u.first_name,
        u.middle_name,
        u.last_name,
        COALESCE(s.schoolID, '') AS school_id,
        COALESCE(s.schoolname, 'No school') AS schoolname,
        COALESCE(du.division_unit_id, '') AS division_unit_id,
        COALESCE(du.unit_name, u.division_unit, 'School') AS division_unit
    FROM sdopang1_documents d
    JOIN sdopang1_user u ON u.user_id = d.user_id
    JOIN sdopang1_document_types t ON t.doc_type_id = d.doc_type_id
    LEFT JOIN sdopang1schoollist s ON s.schoolID = u.school_id
    LEFT JOIN division_units du ON du.division_unit_id = u.division_unit_id
    ORDER BY d.uploaded_at DESC, d.document_id DESC
")->fetchAll(PDO::FETCH_ASSOC);
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
              <h2 class="mb-1">201 List <span class="fw-normal text-700 ms-3">(<?= $total201 ?>)</span></h2>
              <p class="text-700 mb-0">Admin validates School Head and division employee 201 files, plus any system-wide 201 review.</p>
            </div>
         
          <div class="col-12 col-sm-auto">
                <ul class="nav nav-links mx-n2">
                  
                  <li class="nav-item"><a class="nav-link px-2 py-1 active js-status-filter" aria-current="page" href="#" data-status=""><span>All</span><span class="text-700 fw-semi-bold">(<?= $total201 ?>)</span></a></li>
                  <li class="nav-item"><a class="nav-link px-2 py-1 js-status-filter" href="#" data-status="Pending"><span>Pending</span><span class="text-700 fw-semi-bold">(<?= $total201Pending ?>)</span></a></li>
                  <li class="nav-item"><a class="nav-link px-2 py-1 js-status-filter" href="#" data-status="Approved"><span>Approved</span><span class="text-700 fw-semi-bold">(<?= $total201Approved ?>)</span></a></li>
                  <li class="nav-item"><a class="nav-link px-2 py-1 js-status-filter" href="#" data-status="Returned"><span>Returned</span><span class="text-700 fw-semi-bold">(<?= $total201Returned ?>)</span></a></li>
                  
                </ul>
              </div>
          </div>



        <div class="row pb-10">

          <div class="col-md-12 mb-20">
            <div class="card-box height-100-p pd-20">
             
             <div class="card-body">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
                  <div>
                    <h5 class="mb-1">201 Files Uploads</h5>
                    <p class="text-700 mb-0">Filter uploaded 201 files by employee, school, functional division, month, status, or keyword.</p>
                  </div>
                  <button type="button" id="resetFilters" class="btn btn-sm btn-outline-primary">Reset Filters</button>
                </div>

                <div class="row mb-3">
                  <div class="col-md-3 mb-2">
                    <label class="form-label" for="filterSearch">Search</label>
                    <input type="search" id="filterSearch" class="form-control" placeholder="Search employee, document, remarks">
                  </div>
                  <div class="col-md-3 mb-2">
                    <label class="form-label" for="filterUser">User</label>
                    <select id="filterUser" class="form-control">
                      <option value="">All users</option>
                      <?php foreach ($users as $user): ?>
                        <?php
                        $middle = trim((string) ($user['middle_name'] ?? ''));
                        $fullName = trim(($user['first_name'] ?? '') . ' ' . ($middle !== '' ? $middle . ' ' : '') . ($user['last_name'] ?? ''));
                        $label = (($user['employeeID'] ?? '') ?: 'No Employee ID') . ' - ' . $fullName;
                        ?>
                        <option value="<?= htmlspecialchars((string) $user['user_id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-3 mb-2">
                    <label class="form-label" for="filterSchool">School</label>
                    <select id="filterSchool" class="form-control">
                      <option value="">All schools</option>
                      <?php foreach ($schools as $school): ?>
                        <option value="<?= htmlspecialchars((string) $school['schoolID'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($school['schoolname'], ENT_QUOTES, 'UTF-8') ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-3 mb-2">
                    <label class="form-label" for="filterDivision">Functional Division</label>
                    <select id="filterDivision" class="form-control">
                      <option value="">All divisions</option>
                      <?php foreach ($divisionUnits as $division): ?>
                        <option value="<?= htmlspecialchars((string) $division['division_unit_id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($division['unit_name'], ENT_QUOTES, 'UTF-8') ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-3 mb-2">
                    <label class="form-label" for="filterMonth">Month Uploaded</label>
                    <select id="filterMonth" class="form-control">
                      <option value="">All months</option>
                      <?php foreach ($months as $month): ?>
                        <option value="<?= htmlspecialchars((string) $month, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(date('F Y', strtotime($month . '-01')), ENT_QUOTES, 'UTF-8') ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-3 mb-2">
                    <label class="form-label" for="filterStatus">Status</label>
                    <select id="filterStatus" class="form-control">
                      <option value="">All statuses</option>
                      <option value="Pending">Pending</option>
                      <option value="Approved">Approved</option>
                      <option value="Returned">Returned</option>
                      <option value="Rejected">Rejected</option>
                    </select>
                  </div>
                </div>

                <div class="table-responsive">
                  <table id="admin201Table" class="table fs--1 mb-0">
                    <thead>
                      <tr>
                        <th>Employee</th>
                        <th>School</th>
                        <th>Functional Division</th>
                        <th>Document</th>
                        <th>Year</th>
                        <th>Status</th>
                        <th>Remarks</th>
                        <th>Uploaded</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($documents as $document): ?>
                      <?php
                        $middle = trim((string) ($document['middle_name'] ?? ''));
                        $employeeName = trim(($document['first_name'] ?? '') . ' ' . ($middle !== '' ? $middle . ' ' : '') . ($document['last_name'] ?? ''));
                        $employeeLabel = (($document['employeeID'] ?? '') ?: 'No Employee ID') . ' - ' . $employeeName;
                        $uploadedAt = $document['uploaded_at'] ? date('M d, Y h:i A', strtotime($document['uploaded_at'])) : '-';
                        $monthKey = $document['uploaded_at'] ? date('Y-m', strtotime($document['uploaded_at'])) : '';
                      ?>
                      <tr
                        data-user="<?= htmlspecialchars((string) $document['user_id'], ENT_QUOTES, 'UTF-8') ?>"
                        data-school="<?= htmlspecialchars((string) $document['school_id'], ENT_QUOTES, 'UTF-8') ?>"
                        data-division="<?= htmlspecialchars((string) $document['division_unit_id'], ENT_QUOTES, 'UTF-8') ?>"
                        data-month="<?= htmlspecialchars($monthKey, ENT_QUOTES, 'UTF-8') ?>"
                        data-status="<?= htmlspecialchars((string) $document['status'], ENT_QUOTES, 'UTF-8') ?>"
                      >
                        <td><?= htmlspecialchars($employeeLabel, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($document['schoolname'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($document['division_unit'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($document['doc_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $document['year'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($document['status'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($document['remarks'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($uploadedAt, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><a class="btn btn-sm btn-primary" href="admin_view_201_file.php?id=<?= urlencode((string) $document['document_id']) ?>">View</a></td>
                      </tr>
                    <?php endforeach; ?>

                    </tbody>
                  </table>
                </div>
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
    <script src="vendors/scripts/dashboard3.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- SUCCESS ALERT -->
      <?php if (isset($_SESSION['success_message'])): ?>
      <script>
      Swal.fire({
          icon: 'success',
          title: 'Success',
          text: '<?= $_SESSION['success_message'] ?>',
          timer: 2000,
          showConfirmButton: false
      });
      </script>
      <?php unset($_SESSION['success_message']); endif; ?>
      <script>
      $(function () {
          let table;

          $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
              if (settings.nTable.id !== 'admin201Table') {
                  return true;
              }

              const row = settings.aoData[dataIndex].nTr;
              const user = $('#filterUser').val();
              const school = $('#filterSchool').val();
              const division = $('#filterDivision').val();
              const month = $('#filterMonth').val();
              const status = $('#filterStatus').val();

              if (user && row.dataset.user !== user) return false;
              if (school && row.dataset.school !== school) return false;
              if (division && row.dataset.division !== division) return false;
              if (month && row.dataset.month !== month) return false;
              if (status && row.dataset.status !== status) return false;

              return true;
          });

          table = $('#admin201Table').DataTable({
              pageLength: 10,
              order: [[7, 'desc']],
              dom: 'rt<"d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mt-3"ip>',
              language: {
                  emptyTable: 'No 201 files found.',
                  zeroRecords: 'No 201 files match the selected filters.'
              },
              columnDefs: [
                  { orderable: false, targets: 8 }
              ]
          });

          function redrawTable() {
              table.search($('#filterSearch').val()).draw();
          }

          $('#filterSearch').on('keyup search input', redrawTable);
          $('#filterUser, #filterSchool, #filterDivision, #filterMonth, #filterStatus').on('change', redrawTable);

          $('.js-status-filter').on('click', function (event) {
              event.preventDefault();
              $('.js-status-filter').removeClass('active').removeAttr('aria-current');
              $(this).addClass('active').attr('aria-current', 'page');
              $('#filterStatus').val($(this).data('status') || '');
              redrawTable();
          });

          $('#resetFilters').on('click', function () {
              $('#filterSearch').val('');
              $('#filterUser, #filterSchool, #filterDivision, #filterMonth, #filterStatus').val('');
              $('.js-status-filter').removeClass('active').removeAttr('aria-current');
              $('.js-status-filter[data-status=""]').addClass('active').attr('aria-current', 'page');
              redrawTable();
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




