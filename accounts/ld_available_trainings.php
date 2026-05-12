<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/ld_helpers.php';
$userModel = new User($pdo);
require_login();
require_role([1, 2, 3, 4, 5, 6, 7]);
require_once __DIR__ . '/partials/session.php';

ld_ensure_schema($pdo);
$scope = ld_role_scope();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trainingId = filter_input(INPUT_POST, 'training_id', FILTER_VALIDATE_INT);
    $userId = $scope === 'school_head'
        ? (filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT) ?: (int) $currentUser['user_id'])
        : (int) $currentUser['user_id'];
    $schoolStmt = $pdo->prepare("SELECT school_id FROM sdopang1_user WHERE user_id = ?");
    $schoolStmt->execute([$userId]);
    $schoolId = (string) $schoolStmt->fetchColumn();
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO ld_participants (training_id, user_id, nominated_by, school_id, status)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$trainingId, $userId, (int) $currentUser['user_id'], $schoolId, $scope === 'school_head' ? 'Nominated' : 'Registered']);
    header('Location: ld_available_trainings.php?saved=1');
    exit;
}

$trainings = ld_training_mother_list($pdo, 100);
$employees = $scope === 'school_head' ? ld_employee_options($pdo, $currentUser) : [];
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
    <div><h2>Available Trainings</h2><p class="text-700 mb-0"><?= $scope === 'school_head' ? 'Nominate school personnel for open trainings.' : 'Register for open learning and development opportunities.' ?></p></div>
    <a class="btn btn-outline-primary" href="ld_my_trainings.php">My Trainings</a>
  </div>
  <div class="card-box pd-20">
    <div class="table-responsive"><table class="table table-bordered">
      <thead><tr><th>Training</th><th>Type</th><th>Venue / Platform</th><th>Date</th><th>Hours</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach ($trainings as $training): ?>
          <tr>
            <td><?= htmlspecialchars($training['trainingmatrixTITLE'], ENT_QUOTES, 'UTF-8') ?><br><small><?= htmlspecialchars($training['trainingmatrixUCODE'], ENT_QUOTES, 'UTF-8') ?></small></td>
            <td>Legacy Training</td>
            <td><?= htmlspecialchars($training['trainingmatrixVENUE'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($training['trainingmatrixINCLUSIVEDATE'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string) $training['day_count'], ENT_QUOTES, 'UTF-8') ?> day(s)</td>
            <td>
              <span class="text-muted">Use generated app form code</span>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$trainings): ?><tr><td colspan="6" class="text-center text-muted">No open trainings right now.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </div>
  <?php require_once __DIR__ . '/partials/footer.php'; ?>
</div></div>
<?php require_once __DIR__ . '/partials/welcomemodal.php'; ?>
<script src="vendors/scripts/core.js"></script><script src="vendors/scripts/script.min.js"></script><script src="vendors/scripts/process.js"></script><script src="vendors/scripts/layout-settings.js"></script>
</body></html>
