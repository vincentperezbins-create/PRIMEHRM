<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/core/ld_helpers.php';
$userModel = new User($pdo);
require_login();
require_role([1, 2, 5, 6, 7]);
require_once __DIR__ . '/partials/session.php';

ld_ensure_schema($pdo);
$token = generateToken();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'generate_certificates') {
        if (!verifyToken($_POST['token'] ?? '')) {
            $_SESSION['error_message'] = 'Invalid request token. Please try again.';
            header('Location: admin_ld_trainings.php');
            exit;
        }

        $trainingCode = trim((string) ($_POST['trainingmatrix_ucode'] ?? ''));
        $result = ld_generate_certificates_for_training($pdo, $trainingCode, $currentUser);
        $_SESSION['success_message'] = 'Certificates ready: ' . $result['created'] . ' new, ' . $result['existing'] . ' already existing, from ' . $result['total'] . ' attendance responses.';
        header('Location: admin_ld_trainings.php');
        exit;
    }

    if (($_POST['action'] ?? '') === 'generate_certificate_pdfs') {
        if (!verifyToken($_POST['token'] ?? '')) {
            $_SESSION['error_message'] = 'Invalid request token. Please try again.';
            header('Location: admin_ld_trainings.php');
            exit;
        }

        $trainingCode = trim((string) ($_POST['trainingmatrix_ucode'] ?? ''));
        $result = ld_generate_missing_certificate_pdfs($pdo, $trainingCode);
        $_SESSION['success_message'] = 'PDF files saved: ' . $result['created'] . ' generated/updated from ' . $result['total'] . ' certificate records.';
        header('Location: admin_ld_trainings.php');
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO ld_trainings
            (program_id, category_id, training_type, title, organizer, venue_platform, start_date, end_date, hours, cpd_points, capacity, status, owner_user_id, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        filter_input(INPUT_POST, 'program_id', FILTER_VALIDATE_INT) ?: null,
        filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT) ?: null,
        $_POST['training_type'] ?? 'Seminar',
        trim((string) $_POST['title']),
        trim((string) ($_POST['organizer'] ?? '')),
        trim((string) ($_POST['venue_platform'] ?? '')),
        $_POST['start_date'] ?: null,
        $_POST['end_date'] ?: null,
        (float) ($_POST['hours'] ?? 0),
        (float) ($_POST['cpd_points'] ?? 0),
        filter_input(INPUT_POST, 'capacity', FILTER_VALIDATE_INT) ?: null,
        $_POST['status'] ?? 'Draft',
        (int) $currentUser['user_id'],
        (int) $currentUser['user_id'],
    ]);
    header('Location: admin_ld_trainings.php?saved=1');
    exit;
}

$categories = ld_categories($pdo);
$programs = ld_programs($pdo, $currentUser);
$trainings = ld_training_mother_list($pdo, 100);
$types = ['Webinar','Face-to-Face Seminar','Workshop','Coaching / Mentoring','INSET','Conference','Technical Assistance Session'];
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
    <div><h2>All Trainings</h2><p class="text-700 mb-0">Create seminars, workshops, webinars, INSET, coaching, and technical assistance sessions.</p></div>
    <a class="btn btn-outline-primary" href="admin_ld_programs.php">L&D Master Plan</a>
  </div>
  <?php if ($successMessage): ?><div class="alert alert-success"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
  <?php if ($errorMessage): ?><div class="alert alert-danger"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
  <div class="row">
    <div class="col-lg-4 mb-20"><div class="card-box pd-20">
      <h5 class="mb-3">Create Training</h5>
      <form method="post">
        <div class="mb-3"><label>Title</label><input name="title" class="form-control" required></div>
        <div class="mb-3"><label>Program</label><select name="program_id" class="form-control"><option value="">Standalone training</option><?php foreach ($programs as $program): ?><option value="<?= (int) $program['program_id'] ?>"><?= htmlspecialchars($program['program_title'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><label>Category</label><select name="category_id" class="form-control"><?php foreach ($categories as $category): ?><option value="<?= (int) $category['category_id'] ?>"><?= htmlspecialchars($category['category_name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><label>Training Type</label><select name="training_type" class="form-control"><?php foreach ($types as $type): ?><option><?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><label>Organizer</label><input name="organizer" class="form-control" placeholder="HRD / SGOD / CID"></div>
        <div class="mb-3"><label>Venue / Platform</label><input name="venue_platform" class="form-control" placeholder="Venue, Zoom, Google Meet"></div>
        <div class="row"><div class="col-md-6 mb-3"><label>Start</label><input name="start_date" type="date" class="form-control"></div><div class="col-md-6 mb-3"><label>End</label><input name="end_date" type="date" class="form-control"></div></div>
        <div class="row"><div class="col-md-4 mb-3"><label>Hours</label><input name="hours" type="number" step="0.25" class="form-control"></div><div class="col-md-4 mb-3"><label>CPD</label><input name="cpd_points" type="number" step="0.25" class="form-control"></div><div class="col-md-4 mb-3"><label>Capacity</label><input name="capacity" type="number" class="form-control"></div></div>
        <div class="mb-3"><label>Status</label><select name="status" class="form-control"><option>Draft</option><option>Open</option><option>Ongoing</option><option>Completed</option><option>Cancelled</option></select></div>
        <button class="btn btn-primary w-100">Save Training</button>
      </form>
    </div></div>
    <div class="col-lg-8 mb-20"><div class="card-box pd-20">
      <h5 class="mb-3">Training Mother List</h5>
      <div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Title</th><th>Inclusive Date</th><th>Daily Forms</th><th>Participants</th><th>Evaluations</th><th>Action</th></tr></thead><tbody>
        <?php foreach ($trainings as $training): ?><tr><td><a href="ld_training_view.php?code=<?= urlencode($training['trainingmatrixUCODE']) ?>"><?= htmlspecialchars($training['trainingmatrixTITLE'], ENT_QUOTES, 'UTF-8') ?></a></td><td><?= htmlspecialchars($training['trainingmatrixINCLUSIVEDATE'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td><td><?= (int) $training['day_count'] ?></td><td><?= (int) $training['participant_count'] ?></td><td><?= (int) $training['evaluation_count'] ?></td><td><div class="d-flex flex-wrap" style="gap:6px;"><form method="POST" class="mb-0"><input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="action" value="generate_certificates"><input type="hidden" name="trainingmatrix_ucode" value="<?= htmlspecialchars($training['trainingmatrixUCODE'], ENT_QUOTES, 'UTF-8') ?>"><button class="btn btn-sm btn-primary" type="submit">Generate Certificates</button></form><form method="POST" class="mb-0"><input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="action" value="generate_certificate_pdfs"><input type="hidden" name="trainingmatrix_ucode" value="<?= htmlspecialchars($training['trainingmatrixUCODE'], ENT_QUOTES, 'UTF-8') ?>"><button class="btn btn-sm btn-outline-primary" type="submit">Generate PDFs</button></form></div></td></tr><?php endforeach; ?>
        <?php if (!$trainings): ?><tr><td colspan="6" class="text-center text-muted">No training mother records yet.</td></tr><?php endif; ?>
      </tbody></table></div>
    </div></div>
  </div>
  <?php require_once __DIR__ . '/partials/footer.php'; ?>
</div></div>
<?php require_once __DIR__ . '/partials/welcomemodal.php'; ?>
<script src="vendors/scripts/core.js"></script><script src="vendors/scripts/script.min.js"></script><script src="vendors/scripts/process.js"></script><script src="vendors/scripts/layout-settings.js"></script>
</body></html>
