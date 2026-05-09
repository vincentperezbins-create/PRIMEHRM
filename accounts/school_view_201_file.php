<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/csrf.php';

$userModel = new User($pdo);
require_login();
require_role([3]);
$currentUser = $userModel->getUserById($_SESSION['user_id']);

$documentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$documentId) {
    die("Invalid request");
}

$stmt = $pdo->prepare("
    SELECT d.*, u.first_name, u.last_name, t.doc_name
    FROM sdopang1_documents d
    JOIN sdopang1_user u ON d.user_id = u.user_id
    JOIN sdopang1_document_types t ON d.doc_type_id = t.doc_type_id
    WHERE d.document_id = ?
      AND u.school_id = ?
      AND u.role_id = 4
      AND u.user_id <> ?
");
$stmt->execute([$documentId, $currentUser['school_id'], $_SESSION['user_id']]);
$file = $stmt->fetch();

if (!$file) {
    die("Document not found or access denied");
}

$filePath = $file['file_path'];
$safeFilePath = htmlspecialchars($filePath, ENT_QUOTES, 'UTF-8');
$fileExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
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
              <h2 class="mb-1">Review Employee 201 Document</h2>
          	<p class="text-700 mb-0">School employee: <?= htmlspecialchars($file['first_name'] . ' ' . $file['last_name']) ?>
            -
            <?= htmlspecialchars($file['doc_name']) ?></p>
            </div>
         </div>



        <div class="row pb-10">

          <div class="col-md-8 mb-20">
            <div class="card">
	           <div class="card-body">
              <div class="mb-3">
                <strong>File:</strong> <?= htmlspecialchars($file['file_name']) ?>
              </div>

              <?php if ($fileExt === 'pdf'): ?>
                <iframe src="<?= $safeFilePath ?>" width="100%" height="650" class="border rounded"></iframe>
              <?php elseif (in_array($fileExt, ['jpg', 'jpeg', 'png'], true)): ?>
                <img src="<?= $safeFilePath ?>" alt="Uploaded document" class="img-fluid rounded border">
              <?php else: ?>
                <p>Preview not available.</p>
                <a class="btn btn-sm btn-primary" href="<?= $safeFilePath ?>" target="_blank">Download File</a>
              <?php endif; ?>
	           </div>
        	  </div>
          </div>

          <div class="col-md-4 mb-20">
            <div class="card">
             <div class="card-body">
              <h5 class="mb-3">Document Details</h5>
              <p class="mb-2"><strong>Status:</strong> <?= htmlspecialchars($file['status']) ?></p>
              <p class="mb-2"><strong>Year:</strong> <?= htmlspecialchars((string) $file['year']) ?></p>
              <p class="mb-4"><strong>Remarks:</strong> <?= htmlspecialchars($file['remarks'] ?: 'None') ?></p>

              <form method="POST" action="school_201_action.php" class="mb-3">
                <input type="hidden" name="token" value="<?= htmlspecialchars(generateToken(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="document_id" value="<?= htmlspecialchars((string) $file['document_id'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="approve">
                <button type="submit" class="btn btn-success w-100">Approve</button>
              </form>

              <form method="POST" action="school_201_action.php">
                <input type="hidden" name="token" value="<?= htmlspecialchars(generateToken(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="document_id" value="<?= htmlspecialchars((string) $file['document_id'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="return">

                <label for="remarks" class="form-label">Return Remarks</label>
                <textarea id="remarks" name="remarks" class="form-control mb-3" rows="4" required placeholder="Reason for return"></textarea>

                <button type="submit" class="btn btn-danger w-100">Return Document</button>
              </form>
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


