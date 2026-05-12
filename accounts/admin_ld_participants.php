<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/ld_helpers.php';
$userModel = new User($pdo);
require_login();
require_role([1, 2, 3, 5, 6, 7]);
require_once __DIR__ . '/partials/session.php';

ld_ensure_schema($pdo);
$participants = ld_legacy_participants($pdo, $currentUser, null, 300);
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
    <div><h2>Participants</h2><p class="text-700 mb-0">Manage nominations, attendance, evaluations, and certificate release.</p></div>
    <div>
      <a class="btn btn-primary" href="admin_ld_certificates.php">Certificate Validation</a>
      <a class="btn btn-outline-primary" href="admin_ld_trainings.php">Trainings</a>
    </div>
  </div>
  <div class="card-box pd-20">
    <div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Participant</th><th>Training</th><th>School</th><th>Email</th><th>Employee ID</th></tr></thead><tbody>
      <?php foreach ($participants as $participant): ?><tr>
        <td><?= htmlspecialchars(trim($participant['app_infoFIRST'] . ' ' . $participant['app_infoMIDDLE'] . ' ' . $participant['app_infoLAST']), ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($participant['trainingmatrixTITLE'] ?: $participant['traininguniquecode'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($participant['app_infoSCHOOLNAME'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($participant['app_infoEMAIL'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
        <td><?= htmlspecialchars($participant['app_infoEMPLOYEEID'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
      </tr><?php endforeach; ?>
      <?php if (!$participants): ?><tr><td colspan="5" class="text-center text-muted">No participants yet.</td></tr><?php endif; ?>
    </tbody></table></div>
  </div>
  <?php require_once __DIR__ . '/partials/footer.php'; ?>
</div></div>
<?php require_once __DIR__ . '/partials/welcomemodal.php'; ?>
<script src="vendors/scripts/core.js"></script><script src="vendors/scripts/script.min.js"></script><script src="vendors/scripts/process.js"></script><script src="vendors/scripts/layout-settings.js"></script>
</body></html>
