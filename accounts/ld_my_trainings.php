<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/ld_helpers.php';
$userModel = new User($pdo);
require_login();
require_role([1, 2, 3, 4, 5, 6, 7]);
require_once __DIR__ . '/partials/session.php';

ld_ensure_schema($pdo);
$legacy = ld_legacy_participants($pdo, $currentUser, null, 50, true);
$certificates = ld_certificate_submissions($pdo, $currentUser, null, 50, true);
$generatedCertificates = ld_generated_certificates($pdo, $currentUser, null, 50, true);
$uniqueLegacy = [];
$seenFormCodes = [];
foreach ($legacy as $row) {
    $formCode = trim((string) ($row['traininguniquecode'] ?? ''));
    $key = $formCode !== '' ? $formCode : 'app-' . (string) ($row['app_infoID'] ?? count($uniqueLegacy));
    if (isset($seenFormCodes[$key])) {
        continue;
    }
    $seenFormCodes[$key] = true;
    $uniqueLegacy[] = $row;
}

$generatedByTrainingCode = [];
$generatedByAppInfo = [];
foreach ($generatedCertificates as $certificate) {
    $trainingCode = trim((string) ($certificate['traininguniquecode'] ?? ''));
    if ($trainingCode !== '' && !isset($generatedByTrainingCode[$trainingCode])) {
        $generatedByTrainingCode[$trainingCode] = $certificate;
    }

    $appInfoId = (int) ($certificate['app_info_id'] ?? 0);
    if ($appInfoId > 0 && !isset($generatedByAppInfo[$appInfoId])) {
        $generatedByAppInfo[$appInfoId] = $certificate;
    }
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
      <div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Training</th><th>Date</th><th>Form Code</th><th>Email Reference</th><th>Action</th></tr></thead><tbody>
        <?php foreach ($uniqueLegacy as $row): ?><?php
            $trainingCode = trim((string) ($row['traininguniquecode'] ?? ''));
            $appInfoId = (int) ($row['app_infoID'] ?? 0);
            $matchedCertificate = ($trainingCode !== '' && isset($generatedByTrainingCode[$trainingCode])) ? $generatedByTrainingCode[$trainingCode] : ($generatedByAppInfo[$appInfoId] ?? null);
        ?><tr><td><?= htmlspecialchars($row['trainingmatrixTITLE'] ?: 'Legacy Training', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($row['trainingmatrixINCLUSIVEDATE'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($trainingCode, ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($row['app_infoEMAIL'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td><td><?php if ($matchedCertificate): ?><a class="btn btn-sm btn-outline-primary" href="ld_certificate_view.php?id=<?= (int) $matchedCertificate['generated_certificate_id'] ?>" target="_blank">View Certificate</a><?php if (!empty($matchedCertificate['pdf_path'])): ?> <a class="btn btn-sm btn-primary" href="<?= htmlspecialchars($matchedCertificate['pdf_path'], ENT_QUOTES, 'UTF-8') ?>" target="_blank">PDF</a><?php endif; ?><?php else: ?><span class="text-muted">No certificate yet</span><?php endif; ?></td></tr><?php endforeach; ?>
        <?php if (!$uniqueLegacy): ?><tr><td colspan="5" class="text-center text-muted">No training attendance records found for your account/email.</td></tr><?php endif; ?>
      </tbody></table></div>
    </div></div>
  </div>
  <?php require_once __DIR__ . '/partials/footer.php'; ?>
</div></div>
<?php require_once __DIR__ . '/partials/welcomemodal.php'; ?>
<script src="vendors/scripts/core.js"></script><script src="vendors/scripts/script.min.js"></script><script src="vendors/scripts/process.js"></script><script src="vendors/scripts/layout-settings.js"></script>
</body></html>
