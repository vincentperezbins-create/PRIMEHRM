<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/core/ld_helpers.php';

$userModel = new User($pdo);
require_login();
require_role([1, 2, 3, 4, 5, 6, 7]);
require_once __DIR__ . '/partials/session.php';

ld_ensure_schema($pdo);
$token = generateToken();
$trainingOptions = ld_certificate_training_options($pdo);
$submissions = ld_certificate_submissions($pdo, $currentUser, null, 100);
$successMessage = $_SESSION['success_message'] ?? '';
$errorMessage = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$h = static fn($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyToken($_POST['token'] ?? '')) {
        $_SESSION['error_message'] = 'Invalid request token. Please try again.';
        header('Location: ld_certificate_submit.php');
        exit;
    }

    $trainingCode = trim((string) ($_POST['trainingmatrix_ucode'] ?? ''));
    $customTitle = trim((string) ($_POST['custom_training_title'] ?? ''));
    $trainingTitle = '';

    if ($trainingCode !== '') {
        $training = ld_find_training_by_code($pdo, $trainingCode);
        if (!$training) {
            $_SESSION['error_message'] = 'Selected training was not found in the system.';
            header('Location: ld_certificate_submit.php');
            exit;
        }
        $trainingTitle = trim((string) $training['trainingmatrixTITLE']);
    } else {
        $trainingTitle = $customTitle;
    }

    if ($trainingTitle === '') {
        $_SESSION['error_message'] = 'Please select an existing training or enter the certificate title.';
        header('Location: ld_certificate_submit.php');
        exit;
    }

    if ($trainingCode === '') {
        $matchedTraining = ld_find_training_by_title($pdo, $trainingTitle);
        if ($matchedTraining) {
            $trainingCode = (string) $matchedTraining['trainingmatrixUCODE'];
            $trainingTitle = trim((string) $matchedTraining['trainingmatrixTITLE']);
        }
    }

    if (!isset($_FILES['certificate_file']) || $_FILES['certificate_file']['error'] === UPLOAD_ERR_NO_FILE) {
        $_SESSION['error_message'] = 'Please upload the certificate file.';
        header('Location: ld_certificate_submit.php');
        exit;
    }

    $file = $_FILES['certificate_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['error_message'] = 'Unable to upload the certificate file.';
        header('Location: ld_certificate_submit.php');
        exit;
    }

    if ((int) $file['size'] > 8 * 1024 * 1024) {
        $_SESSION['error_message'] = 'Certificate file must not exceed 8MB.';
        header('Location: ld_certificate_submit.php');
        exit;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowed[$mime])) {
        $_SESSION['error_message'] = 'Please upload a PDF, JPG, PNG, or WEBP certificate.';
        header('Location: ld_certificate_submit.php');
        exit;
    }

    $uploadDir = __DIR__ . '/uploads/ld_certificates/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        $_SESSION['error_message'] = 'Unable to prepare the certificate upload folder.';
        header('Location: ld_certificate_submit.php');
        exit;
    }

    $fileName = 'certificate_' . (int) $currentUser['user_id'] . '_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    $targetPath = $uploadDir . $fileName;
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        $_SESSION['error_message'] = 'Unable to save the certificate file.';
        header('Location: ld_certificate_submit.php');
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO ld_certificate_submissions
            (user_id, trainingmatrix_ucode, training_title, organizer, start_date, end_date, hours, certificate_no, certificate_path)
        VALUES (?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''), ?)
    ");
    $stmt->execute([
        (int) $currentUser['user_id'],
        $trainingCode !== '' ? $trainingCode : null,
        $trainingTitle,
        trim((string) ($_POST['organizer'] ?? '')) ?: null,
        trim((string) ($_POST['start_date'] ?? '')),
        trim((string) ($_POST['end_date'] ?? '')),
        trim((string) ($_POST['hours'] ?? '')),
        trim((string) ($_POST['certificate_no'] ?? '')),
        'uploads/ld_certificates/' . $fileName,
    ]);

    $_SESSION['success_message'] = 'Certificate submitted for validation.';
    header('Location: ld_certificate_submit.php');
    exit;
}
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
<div class="main-container"><div class="xs-pd-20-10 pd-ltr-20">
  <div class="d-flex flex-column flex-lg-row justify-content-between pb-20">
    <div><h2>Submit Training Certificate</h2><p class="text-700 mb-0">Select an existing training title first. If it is not listed, enter the certificate title manually for validation.</p></div>
    <a class="btn btn-outline-primary" href="ld_my_trainings.php">My Trainings</a>
  </div>

  <?php if ($successMessage): ?><div class="alert alert-success"><?= $h($successMessage) ?></div><?php endif; ?>
  <?php if ($errorMessage): ?><div class="alert alert-danger"><?= $h($errorMessage) ?></div><?php endif; ?>

  <div class="row">
    <div class="col-lg-5 mb-20">
      <div class="card-box pd-20 h-100">
        <h5 class="mb-3">Certificate Details</h5>
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="token" value="<?= $h($token) ?>">
          <div class="form-group">
            <label>Existing Training Title</label>
            <select class="form-control" name="trainingmatrix_ucode" id="trainingTitleSelect">
              <option value="">Not listed / enter new title</option>
              <?php foreach ($trainingOptions as $training): ?>
                <option value="<?= $h($training['trainingmatrixUCODE']) ?>"><?= $h($training['trainingmatrixTITLE']) ?><?= $training['trainingmatrixINCLUSIVEDATE'] ? ' - ' . $h($training['trainingmatrixINCLUSIVEDATE']) : '' ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Certificate Title if Not Listed</label>
            <input class="form-control" name="custom_training_title" id="customTrainingTitle" placeholder="Type the exact title shown on the certificate">
          </div>
          <div class="form-group">
            <label>Organizer</label>
            <input class="form-control" name="organizer">
          </div>
          <div class="row">
            <div class="col-md-6 form-group"><label>Start Date</label><input class="form-control" type="date" name="start_date"></div>
            <div class="col-md-6 form-group"><label>End Date</label><input class="form-control" type="date" name="end_date"></div>
          </div>
          <div class="row">
            <div class="col-md-6 form-group"><label>Hours</label><input class="form-control" type="number" name="hours" min="0" step="0.25"></div>
            <div class="col-md-6 form-group"><label>Certificate No.</label><input class="form-control" name="certificate_no"></div>
          </div>
          <div class="form-group">
            <label>Certificate File</label>
            <input class="form-control" type="file" name="certificate_file" accept="application/pdf,image/jpeg,image/png,image/webp" required>
          </div>
          <button class="btn btn-primary btn-block" type="submit">Submit for Validation</button>
        </form>
      </div>
    </div>

    <div class="col-lg-7 mb-20">
      <div class="card-box pd-20 h-100">
        <h5 class="mb-3">My Submitted Certificates</h5>
        <div class="table-responsive"><table class="table table-bordered">
          <thead><tr><th>Title</th><th>Status</th><th>Submitted</th><th>File</th><th>Remarks</th></tr></thead>
          <tbody>
            <?php foreach ($submissions as $submission): ?>
              <tr>
                <td><?= $h($submission['training_title']) ?></td>
                <td><?= ld_status_badge($submission['status']) ?></td>
                <td><?= $h($submission['submitted_at']) ?></td>
                <td><a class="btn btn-sm btn-outline-primary" href="<?= $h($submission['certificate_path']) ?>" target="_blank">View</a></td>
                <td><?= $h($submission['review_remarks'] ?: '-') ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$submissions): ?><tr><td colspan="5" class="text-center text-muted">No certificates submitted yet.</td></tr><?php endif; ?>
          </tbody>
        </table></div>
      </div>
    </div>
  </div>
  <?php require_once __DIR__ . '/partials/footer.php'; ?>
</div></div>
<?php require_once __DIR__ . '/partials/welcomemodal.php'; ?>
<script src="vendors/scripts/core.js"></script>
<script src="vendors/scripts/script.min.js"></script>
<script src="vendors/scripts/process.js"></script>
<script src="vendors/scripts/layout-settings.js"></script>
<script>
  (function() {
    const select = document.getElementById('trainingTitleSelect');
    const custom = document.getElementById('customTrainingTitle');
    if (!select || !custom) return;
    const syncCustom = function() {
      custom.disabled = select.value !== '';
      if (custom.disabled) custom.value = '';
    };
    select.addEventListener('change', syncCustom);
    syncCustom();
  })();
</script>
</body></html>
