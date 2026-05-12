<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/rewards_helpers.php';
$userModel = new User($pdo);
require_login();
require_role([1, 2, 3, 4, 5, 6, 7]);
require_once __DIR__ . '/partials/session.php';

rewards_ensure_schema($pdo);
$scope = rewards_role_scope();
$counts = rewards_dashboard_counts($pdo, $currentUser);
$programs = rewards_programs($pdo, $currentUser, $scope === 'school_head' || $scope === 'employee');
$nominations = rewards_nominees_for_scope($pdo, $currentUser);
$cards = [
    'admin' => ['Active Award Programs', 'Total Nominations', 'Pending Evaluations', 'Winners Announced', 'Certificates Generated'],
    'program_owner' => ['Open Nominations', 'Entries Received', 'Pending Screening', 'Evaluation Completion %', 'Certificates Generated'],
    'school_head' => ['Open Award Calls', 'Submitted School Nominations', 'Pending Endorsements', 'School Winners History', 'Certificates Generated'],
    'employee' => ['My Awards', 'Current Eligibility', 'Nominated This Year', 'Certificates Available', 'Award Announcements'],
][$scope];
$values = [$counts['active_programs'], $counts['nominations'], $counts['pending'], $counts['winners'], $counts['certificates']];
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
        <h2 class="mb-1">Rewards & Recognition</h2>
        <p class="text-700 mb-0">Manage awards, nominations, recognition programs, incentives, winners, and certificates.</p>
      </div>
      <div class="d-flex gap-2">
        <?php if (in_array($scope, ['admin', 'program_owner'], true)): ?>
          <a class="btn btn-primary" href="admin_rewards_programs.php">Award Programs</a>
          <a class="btn btn-outline-primary" href="admin_rewards_nominees.php">Nominees</a>
        <?php elseif ($scope === 'school_head'): ?>
          <a class="btn btn-primary" href="rewards_submit_nomination.php">Submit Nomination</a>
        <?php else: ?>
          <a class="btn btn-primary" href="rewards_my_recognitions.php">My Recognitions</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="row pb-10">
      <?php foreach ($cards as $index => $label): ?>
        <div class="col-md-6 col-xl mb-20">
          <div class="card-box pd-20 h-100">
            <p class="text-700 mb-1"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></p>
            <h3 class="mb-0"><?= htmlspecialchars((string) $values[$index], ENT_QUOTES, 'UTF-8') ?></h3>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="row">
      <div class="col-lg-6 mb-20">
        <div class="card-box pd-20 h-100">
          <h5 class="mb-3">Award Programs</h5>
          <div class="table-responsive">
            <table class="table table-bordered">
              <thead><tr><th>Program</th><th>Category</th><th>Status</th><th>Nomination Period</th></tr></thead>
              <tbody>
                <?php foreach (array_slice($programs, 0, 8) as $program): ?>
                  <tr>
                    <td><?= htmlspecialchars($program['title'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($program['category_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= PrimeUI_badge_fallback($program['status']) ?></td>
                    <td><?= htmlspecialchars(($program['nomination_start'] ?: '-') . ' to ' . ($program['nomination_end'] ?: '-'), ENT_QUOTES, 'UTF-8') ?></td>
                  </tr>
                <?php endforeach; ?>
                <?php if (!$programs): ?><tr><td colspan="4" class="text-center text-muted">No award programs yet.</td></tr><?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="col-lg-6 mb-20">
        <div class="card-box pd-20 h-100">
          <h5 class="mb-3"><?= $scope === 'employee' ? 'My Nominations' : 'Recent Nominations' ?></h5>
          <div class="table-responsive">
            <table class="table table-bordered">
              <thead><tr><th>Nominee</th><th>Program</th><th>Status</th><th>Score</th></tr></thead>
              <tbody>
                <?php foreach (array_slice($nominations, 0, 8) as $nomination): ?>
                  <tr>
                    <td><?= htmlspecialchars($nomination['nominee_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($nomination['program_title'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= PrimeUI_badge_fallback($nomination['status']) ?></td>
                    <td><?= htmlspecialchars($nomination['score'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                  </tr>
                <?php endforeach; ?>
                <?php if (!$nominations): ?><tr><td colspan="4" class="text-center text-muted">No nominations yet.</td></tr><?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <?php require_once __DIR__ . '/partials/footer.php'; ?>
  </div>
</div>
<?php require_once __DIR__ . '/partials/welcomemodal.php'; ?>
<script src="vendors/scripts/core.js"></script>
<script src="vendors/scripts/script.min.js"></script>
<script src="vendors/scripts/process.js"></script>
<script src="vendors/scripts/layout-settings.js"></script>
</body>
</html>
<?php
function PrimeUI_badge_fallback(string $status): string {
    return '<span class="prime-badge ' . htmlspecialchars(rewards_status_class($status), ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '</span>';
}
?>
