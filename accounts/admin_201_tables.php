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
                  
                  <li class="nav-item"><a class="nav-link px-2 py-1 active" aria-current="page" href="admin_201_tables.php#"><span>All</span><span class="text-700 fw-semi-bold">(<?= $total201 ?>)</span></a></li>
                  <li class="nav-item"><a class="nav-link px-2 py-1" href="admin_201_tables.php#"><span>Pending</span><span class="text-700 fw-semi-bold">(<?= $total201Pending ?>)</span></a></li>
                  <li class="nav-item"><a class="nav-link px-2 py-1" href="admin_201_tables.php#"><span>Approved</span><span class="text-700 fw-semi-bold">(<?= $total201Approved ?>)</span></a></li>
                  <li class="nav-item"><a class="nav-link px-2 py-1" href="admin_201_tables.php#"><span>Returned</span><span class="text-700 fw-semi-bold">(<?= $total201Returned ?>)</span></a></li>
                  
                </ul>
              </div>
          </div>



        <div class="row pb-10">

          <div class="col-md-12 mb-20">
            <div class="card-box height-100-p pd-20">
             
             <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h5 class="mb-0">201 Files Uploads</h5>
                  <a href="admin_201_tables.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="table-responsive">
                  <table class="table fs--1 mb-0">
                    <thead>
                      <tr>
                        <th>Employee</th>
                        <th>Document</th>
                        <th>Year</th>
                        <th>Status</th>
                        <th>Remarks</th>
                        <th>Uploaded</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                        //Get all 201
                        $getAll201 = $userModel->getAll201();
                        foreach ($getAll201 as $rowgetAll201) {
                      ?>
                      <tr>
                        <td><?= htmlspecialchars($rowgetAll201['first_name'] . ' ' . $rowgetAll201['last_name']) ?></td>
                        <td><?= htmlspecialchars($rowgetAll201['doc_name']) ?></td>
                        <td><?= htmlspecialchars((string) $rowgetAll201['year']) ?></td>
                        <td><?= htmlspecialchars($rowgetAll201['status']) ?></td>
                        <td><?= htmlspecialchars($rowgetAll201['remarks'] ?: '-') ?></td>
                        <td><a class="btn btn-sm btn-primary" href="admin_view_201_file.php?id=<?= urlencode((string) $rowgetAll201['document_id']) ?>">View</a></td>
                      </tr>
                    <?php } ?>

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




