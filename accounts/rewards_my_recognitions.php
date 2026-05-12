<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/rewards_helpers.php';
$userModel = new User($pdo);
require_login();
require_role([1, 2, 3, 4, 5, 6, 7]);
require_once __DIR__ . '/partials/session.php';

rewards_ensure_schema($pdo);
$stmt = $pdo->prepare("SELECT * FROM reward_recognitions WHERE user_id = ? ORDER BY awarded_at DESC, created_at DESC");
$stmt->execute([(int) $currentUser['user_id']]);
$recognitions = $stmt->fetchAll(PDO::FETCH_ASSOC);
$nominations = rewards_nominees_for_scope($pdo, $currentUser);
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
    <div><h2>My Recognitions</h2><p class="text-700 mb-0">View your awards, nomination status, and available certificates.</p></div>
    <a class="btn btn-outline-primary" href="rewards_dashboard.php">Dashboard</a>
  </div>
  <div class="row">
    <div class="col-lg-6 mb-20"><div class="card-box pd-20 h-100"><h5 class="mb-3">My Awards</h5>
      <div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Award</th><th>Category</th><th>Date</th><th>Certificate</th></tr></thead><tbody>
        <?php foreach ($recognitions as $recognition): ?><tr><td><?= htmlspecialchars($recognition['title'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($recognition['category_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($recognition['awarded_at'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td><td><?= $recognition['certificate_path'] ? '<a class="btn btn-sm btn-outline-primary" href="' . htmlspecialchars($recognition['certificate_path'], ENT_QUOTES, 'UTF-8') . '">Download</a>' : '<span class="text-muted">Pending</span>' ?></td></tr><?php endforeach; ?>
        <?php if (!$recognitions): ?><tr><td colspan="4" class="text-center text-muted">No recognitions recorded yet.</td></tr><?php endif; ?>
      </tbody></table></div>
    </div></div>
    <div class="col-lg-6 mb-20"><div class="card-box pd-20 h-100"><h5 class="mb-3">My Nominations</h5>
      <div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Program</th><th>Status</th><th>Score</th><th>Remarks</th></tr></thead><tbody>
        <?php foreach ($nominations as $nomination): ?><tr><td><?= htmlspecialchars($nomination['program_title'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($nomination['status'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($nomination['score'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($nomination['remarks'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; ?>
        <?php if (!$nominations): ?><tr><td colspan="4" class="text-center text-muted">No nominations yet.</td></tr><?php endif; ?>
      </tbody></table></div>
    </div></div>
  </div>
  <?php require_once __DIR__ . '/partials/footer.php'; ?>
</div></div>
<?php require_once __DIR__ . '/partials/welcomemodal.php'; ?>
<script src="vendors/scripts/core.js"></script><script src="vendors/scripts/script.min.js"></script><script src="vendors/scripts/process.js"></script><script src="vendors/scripts/layout-settings.js"></script>
</body></html>
