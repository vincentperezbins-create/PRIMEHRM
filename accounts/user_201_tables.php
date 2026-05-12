<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
$userModel = new User($pdo);
require_login();
require_role([1, 2, 3, 4, 5, 6, 7]);
$currentUser = $userModel->getUserById($_SESSION['user_id']);
$progress = $userModel->get201Progress($_SESSION['user_id']);

?>
<?php
    // show my uploads 201 files form core/databased.php
$userId = $_SESSION['user_id'];
$myUploads = $db->countWhere("sdopang1_documents", "user_id", $userId);

$countStatusStmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM sdopang1_documents
    WHERE user_id = ? AND status = ?
");
$countStatus = function ($status) use ($countStatusStmt, $userId) {
    $countStatusStmt->execute([$userId, $status]);
    return (int) $countStatusStmt->fetchColumn();
};

$myUploadspending = $countStatus('Pending');
$myUploadsapproved = $countStatus('Approved');
$myUploadsreturned = $countStatus('Returned');  

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
              <h2 class="mb-1">My 201 List<span class="fw-normal text-700 ms-3">(<?= $myUploads ?>)</span></h2>
          	<p class="text-700 mb-0">Your 201 file completion and review status.</p>
            </div>
         </div>

        <div class="row pb-10">
          <div class="col-12 col-lg-12">
            <div class="card h-100">
              <div class="card-body">
                <?php
                      $percent = $progress['percent'];
                      $total = $progress['total'];
                      $uploaded = $progress['uploaded'];

                      // color logic
                      if ($percent == 100) {
                          $color = 'bg-success';
                      } elseif ($percent >= 70) {
                          $color = 'bg-info';
                      } elseif ($percent >= 40) {
                          $color = 'bg-warning';
                      } else {
                          $color = 'bg-danger';
                      }
                      ?>

                      <div class="">
                          <label><strong>201 Completion Progress</strong></label>

                          <div class="progress" style="height: 25px;">
                              <div class="progress-bar <?= $color ?>" style="width: <?= $percent ?>%;">
                                  <?= $percent ?>%
                              </div>
                          </div>

                          <small><?= $uploaded ?> / <?= $total ?> documents uploaded</small>
                      </div>
                  
              </div>
            </div>
          </div>
        </div>





        <div class="row pb-10">
          <div class="col-md-12 mb-20">
            <div class="card">

	          <div class="card-body">
 					<ul class="nav nav-links mx-n2">
                  
                  <li class="nav-item"><a class="nav-link px-2 py-1 active" aria-current="page" href="user_201_tables.php#"><span>All</span><span class="text-700 fw-semi-bold">(<?= $myUploads ?>)</span></a></li>
                  <li class="nav-item"><a class="nav-link px-2 py-1" href="user_201_tables.php#"><span>Pending</span><span class="text-700 fw-semi-bold">(<?= $myUploadspending ?>)</span></a></li>
                  <li class="nav-item"><a class="nav-link px-2 py-1" href="user_201_tables.php#"><span>Approved</span><span class="text-700 fw-semi-bold">(<?= $myUploadsapproved ?>)</span></a></li>
                  <li class="nav-item"><a class="nav-link px-2 py-1" href="user_201_tables.php#"><span>Returned</span><span class="text-700 fw-semi-bold">(<?= $myUploadsreturned ?>)</span></a></li>
                  
                </ul>
                <hr>
	          	<div class="pb-20">

							<table class="data-table table stripe hover nowrap">
								<thead>
									<tr>
										<th class="table-plus datatable-nosort">Document Type</th>
										<th>Status</th>
										<th>Remarks</th>
										<th class="datatable-nosort">Action</th>
									</tr>
								</thead>
								<tbody>
									
									 <?php
$stmt = $pdo->prepare("
    SELECT 
        t.doc_type_id,
        t.doc_name,
        d.document_id,
        d.status,
        d.remarks
    FROM sdopang1_document_types t

    LEFT JOIN sdopang1_documents d 
        ON d.document_id = (
            SELECT d2.document_id
            FROM sdopang1_documents d2
            WHERE d2.user_id = ?
            AND d2.doc_type_id = t.doc_type_id
            ORDER BY d2.uploaded_at DESC
            LIMIT 1
        )

    ORDER BY t.doc_name ASC
");

$stmt->execute([$_SESSION['user_id']]);
while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {    

$status = $row->status ?? 'Missing';

    // 🎨 status color
    if ($status == 'Approved') {
        $badge = 'success';
        $label = 'Approved';
        $action = "View";
    } elseif ($status == 'Pending') {
        $badge = 'warning';
        $label = 'Pending';
        $action = "View";
    } elseif ($status == 'Returned') {
        $badge = 'danger';
        $label = 'Returned';
        $action = "Re-upload";
    } else {
        $badge = 'secondary';
        $label = 'Missing';
        $action = "Upload";
    }
    $actionClass = in_array($action, ['Upload', 'Re-upload'], true) ? 'btn-submit' : 'btn-view';
?>                  
                  <tr>
    <td><?= htmlspecialchars($row->doc_name) ?></td>

    <td><?= '<span class="prime-badge status-' . htmlspecialchars(strtolower(str_replace(' ', '-', $label)), ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label) . '</span>' ?></td>

    <td>
        <span class="prime-remarks <?= empty($row->remarks) ? 'is-empty' : '' ?>">
            <?= htmlspecialchars($row->remarks ?: 'No remarks') ?>
        </span>
    </td>

    <td>
        <button 
    class="btn btn-sm <?= htmlspecialchars($actionClass, ENT_QUOTES, 'UTF-8') ?> openModal" 
    data-id="<?= htmlspecialchars((string) $row->document_id) ?>"
    data-type="<?= htmlspecialchars((string) $row->doc_type_id) ?>"
    data-action="<?= htmlspecialchars($action) ?>"
>
    <?= htmlspecialchars($action) ?>
</button>
    </td>
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

    <div class="modal fade" id="actionModal" tabindex="-1">
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

  <script>
document.querySelectorAll('.openModal').forEach(btn => {
    btn.addEventListener('click', function() {

        let id = this.dataset.id;
        let type = this.dataset.type;
        let action = this.dataset.action;

        let url = "";

        if (action === "View") {
            url = "view_user_201_files.php?id=" + id;
        } else if (action === "Replace" || action === "Re-upload") {
            url = "update_user_201_files.php?id=" + id;
        } else {
            url = "insert_user_201_files.php?doc_type_id=" + type;
        }

        document.getElementById("modalTitle").innerText = action + " Document";
        PrimeUI.loading('Loading document', 'Preparing the document form.');

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
            PrimeUI.error('Unable to load this document form.');
        });

    });
});
</script>

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
		<script src="src/plugins/datatables/js/jszip.min.js"></script>
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



