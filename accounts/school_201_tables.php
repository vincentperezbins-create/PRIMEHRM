<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
$userModel = new User($pdo);
require_login();
$currentUser = $userModel->getUserById($_SESSION['user_id']);
require_role([3]);
$schoolId = $currentUser['school_id'];
?>
<?php
$countSchoolDocsStmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM sdopang1_documents d
    JOIN sdopang1_user u ON d.user_id = u.user_id
    WHERE u.school_id = ?
      AND u.role_id = 4
      AND u.user_id <> ?
");
$countSchoolDocsStmt->execute([$schoolId, $_SESSION['user_id']]);
$total201 = (int) $countSchoolDocsStmt->fetchColumn();

$countSchoolStatusStmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM sdopang1_documents d
    JOIN sdopang1_user u ON d.user_id = u.user_id
    WHERE u.school_id = ?
      AND u.role_id = 4
      AND u.user_id <> ?
      AND d.status = ?
");
$countSchoolStatus = function ($status) use ($countSchoolStatusStmt, $schoolId) {
    $countSchoolStatusStmt->execute([$schoolId, $_SESSION['user_id'], $status]);
    return (int) $countSchoolStatusStmt->fetchColumn();
};

$total201Pending = $countSchoolStatus('Pending');
$total201Approved = $countSchoolStatus('Approved');
$total201Returned = $countSchoolStatus('Returned');
?>
<!DOCTYPE html>
<html>
 <?php require_once __DIR__ . '/partials/head.php'; ?>
  <body>
  	<?php if (!empty($_SESSION['success_message'])): ?>
      <script>
        alert(<?= json_encode($_SESSION['success_message']) ?>);
      </script>
      <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
   <?php require_once __DIR__ . '/partials/preloader.php'; ?>

    <?php require_once __DIR__ . '/partials/navbar.php'; ?>
    
    <?php require_once __DIR__ . '/partials/rightsidebar.php'; ?>
    <?php require_once __DIR__ . '/partials/leftsidebar.php'; ?>
    <div class="mobile-menu-overlay"></div>

    <div class="main-container">
      <div class="xs-pd-20-10 pd-ltr-20">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 pb-20">
            <div>
              <h2 class="mb-1">School 201 List <span class="fw-normal text-700 ms-3">(<?= $total201 ?>)</span></h2>
          	<p class="text-700 mb-0">School employee 201 file completion and review status.</p>
            </div>
         </div>



        <div class="row pb-10">
          <div class="col-md-12 mb-20">
            <div class="card">

	          <div class="card-body">
 					      <ul class="nav nav-links mx-n2">
               <li class="nav-item"><a class="nav-link px-2 py-1 active" aria-current="page" href="school_201_tables.php#"><span>All</span><span class="text-700 fw-semi-bold">(<?= $total201 ?>)</span></a></li>
                  <li class="nav-item"><a class="nav-link px-2 py-1" href="school_201_tables.php#"><span>Pending</span><span class="text-700 fw-semi-bold">(<?= $total201Pending ?>)</span></a></li>
                  <li class="nav-item"><a class="nav-link px-2 py-1" href="school_201_tables.php#"><span>Approved</span><span class="text-700 fw-semi-bold">(<?= $total201Approved ?>)</span></a></li>
                  <li class="nav-item"><a class="nav-link px-2 py-1" href="school_201_tables.php#"><span>Returned</span><span class="text-700 fw-semi-bold">(<?= $total201Returned ?>)</span></a></li>
                  
                </ul>
                <hr>
	          	<div class="pb-20">

							<table class="data-table table stripe hover nowrap">
								<thead>
									<tr>
										<th class="table-plus datatable-nosort">Employee</th>
										<th>Document</th>
										<th>Year</th>
                    <th>Status</th>
                    <th>Remarks</th>
										<th class="datatable-nosort">Action</th>
									</tr>
								</thead>
								<tbody>
							<?php
$stmt = $pdo->prepare("
    SELECT d.*, u.first_name, u.last_name, t.doc_name
    FROM sdopang1_documents d
    JOIN sdopang1_user u ON d.user_id = u.user_id
    JOIN sdopang1_document_types t ON d.doc_type_id = t.doc_type_id
    WHERE u.school_id = ?
      AND u.role_id = 4
      AND u.user_id <> ?
    ORDER BY d.uploaded_at DESC
");
$stmt->execute([$schoolId, $_SESSION['user_id']]);
foreach ($stmt->fetchAll() as $rowgetAll201) {
?>

                  <tr class="position-static">
                    <td class="align-middle white-space-nowrap start ps-3 py-4">
                      <p class="mb-0 fs--1 text-900"><?= htmlspecialchars($rowgetAll201['first_name'] . ' ' . $rowgetAll201['last_name']) ?></p>
                    </td>
                    <td class="align-middle white-space-nowrap deadline ps-3 py-4">
                      <p class="mb-0 fs--1 text-900"><?= htmlspecialchars($rowgetAll201['doc_name']) ?></p>
                    </td>
                    <td class="align-middle white-space-nowrap ps-3 projectprogress">
                      <p class="text-800 fs--2 mb-0"><?= htmlspecialchars((string) $rowgetAll201['year']) ?></p>
                    </td>
                    <td class="align-middle white-space-nowrap text-end statuses"><span class="badge badge-phoenix fs--2 badge-phoenix-success"><?= htmlspecialchars($rowgetAll201['status']) ?></span></td>
                    <td class="align-middle ps-3">
                      <p class="mb-0 fs--1 text-900"><?= htmlspecialchars($rowgetAll201['remarks'] ?: '-') ?></p>
                    </td>
                    <td class="align-middle white-space-nowrap ps-3 projectprogress">
                      <a class="btn btn-sm btn-primary" href="school_view_201_file.php?id=<?= urlencode((string) $rowgetAll201['document_id']) ?>">View</a>
                    </td>
                  </tr>
<?php  
}
?>
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
<!--     <script src="vendors/scripts/dashboard3.js"></script> -->


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


