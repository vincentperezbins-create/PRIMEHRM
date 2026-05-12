<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/ld_helpers.php';
$userModel = new User($pdo);
require_login();
require_role([1, 2, 3, 4, 5, 6, 7]);
require_once __DIR__ . '/partials/session.php';

ld_ensure_schema($pdo);
$legacy = ld_legacy_participants($pdo, $currentUser, null, 50);
$certificates = ld_certificate_submissions($pdo, $currentUser, null, 50);
$generatedCertificates = ld_generated_certificates($pdo, $currentUser, null, 50);
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
    <div><h2>My Trainings</h2><p class="text-700 mb-0">View registrations, attendance, evaluations, certificates, and historical training records.</p></div>
    <div>
      <a class="btn btn-primary" href="ld_certificate_submit.php">Submit Certificate</a>
      <a class="btn btn-outline-primary" href="ld_available_trainings.php">Available Trainings</a>
    </div>
  </div>
  <div class="row">
    <div class="col-lg-12 mb-20"><div class="card-box pd-20 h-100">
      <h5 class="mb-3">My Certificates</h5>
      <div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Certificate / Training</th><th>Status</th><th>Submitted</th><th>File</th><th>Remarks</th></tr></thead><tbody>
        <?php foreach ($generatedCertificates as $certificate): ?><tr><td><?= htmlspecialchars($certificate['training_title'], ENT_QUOTES, 'UTF-8') ?></td><td><?= ld_status_badge('Approved') ?></td><td><?= htmlspecialchars($certificate['generated_at'], ENT_QUOTES, 'UTF-8') ?></td><td><a class="btn btn-sm btn-outline-primary" href="ld_certificate_view.php?id=<?= (int) $certificate['generated_certificate_id'] ?>" target="_blank">View E-Certificate</a><?php if (!empty($certificate['pdf_path'])): ?> <a class="btn btn-sm btn-primary" href="<?= htmlspecialchars($certificate['pdf_path'], ENT_QUOTES, 'UTF-8') ?>" target="_blank">PDF</a><?php endif; ?></td><td>Generated from attendance response</td></tr><?php endforeach; ?>
        <?php foreach ($certificates as $certificate): ?><tr><td><?= htmlspecialchars($certificate['training_title'], ENT_QUOTES, 'UTF-8') ?></td><td><?= ld_status_badge($certificate['status']) ?></td><td><?= htmlspecialchars($certificate['submitted_at'], ENT_QUOTES, 'UTF-8') ?></td><td><a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($certificate['certificate_path'], ENT_QUOTES, 'UTF-8') ?>" target="_blank">View</a></td><td><?= htmlspecialchars($certificate['review_remarks'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; ?>
        <?php if (!$certificates && !$generatedCertificates): ?><tr><td colspan="5" class="text-center text-muted">No certificate submissions yet.</td></tr><?php endif; ?>
      </tbody></table></div>
    </div></div>
    <div class="col-lg-12 mb-20"><div class="card-box pd-20 h-100">
      <h5 class="mb-3">Training Attendance History</h5>
      <div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Training</th><th>Date</th><th>Form Code</th><th>Email Reference</th></tr></thead><tbody>
        <?php foreach ($legacy as $row): ?><tr><td><?= htmlspecialchars($row['trainingmatrixTITLE'] ?: 'Legacy Training', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($row['trainingmatrixINCLUSIVEDATE'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($row['traininguniquecode'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($row['app_infoEMAIL'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; ?>
        <?php if (!$legacy): ?><tr><td colspan="4" class="text-center text-muted">No training attendance records found for your account/email.</td></tr><?php endif; ?>
      </tbody></table></div>
    </div></div>
  </div>
  <?php require_once __DIR__ . '/partials/footer.php'; ?>
</div></div>
<?php require_once __DIR__ . '/partials/welcomemodal.php'; ?>
<script src="vendors/scripts/core.js"></script><script src="vendors/scripts/script.min.js"></script><script src="vendors/scripts/process.js"></script><script src="vendors/scripts/layout-settings.js"></script>
</body></html>
