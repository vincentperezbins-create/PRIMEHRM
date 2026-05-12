<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/core/ld_helpers.php';
$userModel = new User($pdo);
require_login();
require_role([1, 2, 3, 4, 5, 6, 7]);
require_once __DIR__ . '/partials/session.php';

$code = trim((string) ($_GET['code'] ?? ''));
if ($code === '') {
    die('Training code is required.');
}

$stmt = $pdo->prepare("SELECT * FROM sdopang1_trainingmatrix WHERE trainingmatrixUCODE = ? LIMIT 1");
$stmt->execute([$code]);
$training = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$training) {
    die('Training not found.');
}

ld_ensure_schema($pdo);
$token = generateToken();
$scope = ld_role_scope();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'generate_certificates') {
    if (!verifyToken($_POST['token'] ?? '')) {
        $_SESSION['error_message'] = 'Invalid request token. Please try again.';
        header('Location: ld_training_view.php?code=' . urlencode($code));
        exit;
    }

    if (!in_array($scope, ['admin', 'program_owner'], true)) {
        $_SESSION['error_message'] = 'You are not allowed to generate certificates for this training.';
        header('Location: ld_training_view.php?code=' . urlencode($code));
        exit;
    }

    $result = ld_generate_certificates_for_training($pdo, $code, $currentUser);
    $_SESSION['success_message'] = 'Certificates ready: ' . $result['created'] . ' new, ' . $result['existing'] . ' already existing, from ' . $result['total'] . ' attendance responses.';
    header('Location: ld_training_view.php?code=' . urlencode($code));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'generate_certificate_pdfs') {
    if (!verifyToken($_POST['token'] ?? '')) {
        $_SESSION['error_message'] = 'Invalid request token. Please try again.';
        header('Location: ld_training_view.php?code=' . urlencode($code));
        exit;
    }

    if (!in_array($scope, ['admin', 'program_owner'], true)) {
        $_SESSION['error_message'] = 'You are not allowed to generate certificate PDFs for this training.';
        header('Location: ld_training_view.php?code=' . urlencode($code));
        exit;
    }

    $result = ld_generate_missing_certificate_pdfs($pdo, $code);
    $_SESSION['success_message'] = 'PDF files saved: ' . $result['created'] . ' generated/updated from ' . $result['total'] . ' certificate records.';
    header('Location: ld_training_view.php?code=' . urlencode($code));
    exit;
}

$days = ld_training_days($pdo, $code);
$apps = ld_training_app_forms($pdo, $code);
$participants = [];
foreach ($apps as $app) {
    $participants = array_merge($participants, ld_legacy_participants($pdo, $currentUser, $app['traininguniquecode'], 50));
}
$speakers = [];
foreach ($days as $day) {
    $speakers = array_merge($speakers, ld_legacy_speakers($pdo, $day['traininguniquecode'], 50));
}
$generatedCertificates = ld_generated_certificates($pdo, $currentUser, $code, 300);
$successMessage = $_SESSION['success_message'] ?? '';
$errorMessage = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
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
  <div class="d-flex justify-content-between pb-20">
    <div>
      <h2><?= htmlspecialchars($training['trainingmatrixTITLE'], ENT_QUOTES, 'UTF-8') ?></h2>
      <p class="text-700 mb-0"><?= htmlspecialchars($training['trainingmatrixINCLUSIVEDATE'] ?: '-', ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars($training['trainingmatrixUCODE'], ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <div>
      <?php if (in_array($scope, ['admin', 'program_owner'], true)): ?>
        <form method="POST" class="d-inline">
          <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="action" value="generate_certificates">
          <button class="btn btn-primary" type="submit">Generate Certificates</button>
        </form>
        <form method="POST" class="d-inline">
          <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="action" value="generate_certificate_pdfs">
          <button class="btn btn-outline-primary" type="submit">Generate PDFs</button>
        </form>
      <?php endif; ?>
      <a class="btn btn-outline-primary" href="admin_ld_trainings.php">Back to Trainings</a>
    </div>
  </div>

  <?php if ($successMessage): ?><div class="alert alert-success"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
  <?php if ($errorMessage): ?><div class="alert alert-danger"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

  <div class="row">
    <div class="col-lg-6 mb-20"><div class="card-box pd-20 h-100">
      <h5 class="mb-3">Daily Training Forms</h5>
      <div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Day</th><th>Date</th><th>Unique Code</th><th>Evaluations</th><th>Speakers</th></tr></thead><tbody>
        <?php foreach ($days as $day): ?><tr><td><?= htmlspecialchars($day['dayoftraining'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($day['dateoftrainings'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($day['traininguniquecode'], ENT_QUOTES, 'UTF-8') ?></td><td><?= (int) $day['evaluation_count'] ?></td><td><?= (int) $day['speaker_count'] ?></td></tr><?php endforeach; ?>
        <?php if (!$days): ?><tr><td colspan="5" class="text-center text-muted">No daily forms found.</td></tr><?php endif; ?>
      </tbody></table></div>
    </div></div>

    <div class="col-lg-6 mb-20"><div class="card-box pd-20 h-100">
      <h5 class="mb-3">Attendance Form Codes</h5>
      <div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Type</th><th>Code</th><th>Participants</th><th>Created</th></tr></thead><tbody>
        <?php foreach ($apps as $app): ?><tr><td><?= htmlspecialchars($app['sdopang1_appTYPE'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($app['traininguniquecode'], ENT_QUOTES, 'UTF-8') ?></td><td><?= (int) $app['participant_count'] ?></td><td><?= htmlspecialchars($app['sdopang1_appDATECREATED'], ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; ?>
        <?php if (!$apps): ?><tr><td colspan="4" class="text-center text-muted">No attendance app codes found.</td></tr><?php endif; ?>
      </tbody></table></div>
    </div></div>
  </div>

  <div class="row">
    <div class="col-lg-6 mb-20"><div class="card-box pd-20 h-100">
      <h5 class="mb-3">Speakers</h5>
      <div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Speaker</th><th>Day Code</th><th>Order</th><th>Evaluations</th></tr></thead><tbody>
        <?php foreach ($speakers as $speaker): ?><tr><td><?= htmlspecialchars($speaker['speaker_label'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($speaker['traininguniquecode'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($speaker['numberasc'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td><td><?= (int) $speaker['eval_count'] ?></td></tr><?php endforeach; ?>
        <?php if (!$speakers): ?><tr><td colspan="4" class="text-center text-muted">No speakers found.</td></tr><?php endif; ?>
      </tbody></table></div>
    </div></div>

    <div class="col-lg-6 mb-20"><div class="card-box pd-20 h-100">
      <h5 class="mb-3">Recent Attendance Responses</h5>
      <div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Name</th><th>School</th><th>Email</th><th>Code</th></tr></thead><tbody>
        <?php foreach (array_slice($participants, 0, 50) as $participant): ?><tr><td><?= htmlspecialchars(trim($participant['app_infoFIRST'] . ' ' . $participant['app_infoLAST']), ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($participant['app_infoSCHOOLNAME'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($participant['app_infoEMAIL'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($participant['traininguniquecode'], ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; ?>
        <?php if (!$participants): ?><tr><td colspan="4" class="text-center text-muted">No attendance responses found.</td></tr><?php endif; ?>
      </tbody></table></div>
    </div></div>
  </div>

  <div class="row">
    <div class="col-12 mb-20"><div class="card-box pd-20 h-100">
      <h5 class="mb-3">Generated E-Certificates</h5>
      <div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Participant</th><th>School</th><th>Email</th><th>Certificate No.</th><th>Action</th></tr></thead><tbody>
        <?php foreach ($generatedCertificates as $certificate): ?><tr><td><?= htmlspecialchars($certificate['participant_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($certificate['school_name'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($certificate['participant_email'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($certificate['certificate_no'], ENT_QUOTES, 'UTF-8') ?></td><td><a class="btn btn-sm btn-outline-primary" href="ld_certificate_view.php?id=<?= (int) $certificate['generated_certificate_id'] ?>" target="_blank">View Certificate</a><?php if (!empty($certificate['pdf_path'])): ?> <a class="btn btn-sm btn-primary" href="<?= htmlspecialchars($certificate['pdf_path'], ENT_QUOTES, 'UTF-8') ?>" target="_blank">PDF</a><?php endif; ?></td></tr><?php endforeach; ?>
        <?php if (!$generatedCertificates): ?><tr><td colspan="5" class="text-center text-muted">No e-certificates generated yet.</td></tr><?php endif; ?>
      </tbody></table></div>
    </div></div>
  </div>

  <?php require_once __DIR__ . '/partials/footer.php'; ?>
</div></div>
<?php require_once __DIR__ . '/partials/welcomemodal.php'; ?>
<script src="vendors/scripts/core.js"></script><script src="vendors/scripts/script.min.js"></script><script src="vendors/scripts/process.js"></script><script src="vendors/scripts/layout-settings.js"></script>
</body></html>
