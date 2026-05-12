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
$counts = ld_dashboard_counts($pdo, $currentUser);
$trainings = ld_training_mother_list($pdo, 8);
$participants = ld_legacy_participants($pdo, $currentUser, null, 8);
$cardLabels = [
    'admin' => ['Active Programs', 'Upcoming Trainings', 'Total Participants', 'Completed Trainings', 'Certificates Issued'],
    'program_owner' => ['Open Trainings', 'Registrations Received', 'Attendance Rate', 'Pending Evaluations', 'Certificates Pending Release'],
    'school_head' => ['Assigned Trainings', 'Staff Registered', 'Completed Participants', 'Pending School Nominations', 'Staff Certificates'],
    'employee' => ['Upcoming Trainings', 'My Registrations', 'Completed Trainings', 'Certificates Available', 'Training History'],
][$scope];
$cardValues = [
    $counts['active_programs'],
    $counts['upcoming_trainings'],
    $counts['total_participants'],
    $counts['training_days'],
    $counts['certificates'],
];
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
  <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 pb-20">
    <div>
      <h2 class="mb-1">Learning & Development</h2>
      <p class="text-700 mb-0">Manage L&D programs, trainings, participants, attendance, evaluations, certificates, and history.</p>
    </div>
    <div class="d-flex gap-2">
      <?php if (in_array($scope, ['admin', 'program_owner'], true)): ?>
        <a class="btn btn-primary" href="admin_ld_trainings.php">Create Training</a>
        <a class="btn btn-outline-primary" href="admin_ld_participants.php">Participants</a>
      <?php elseif ($scope === 'school_head'): ?>
        <a class="btn btn-primary" href="ld_available_trainings.php">Nominate Staff</a>
      <?php else: ?>
        <a class="btn btn-primary" href="ld_available_trainings.php">Available Trainings</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="row pb-10">
    <?php foreach ($cardLabels as $index => $label): ?>
      <div class="col-md-6 col-xl mb-20">
        <div class="card-box pd-20 h-100">
          <p class="text-700 mb-1"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></p>
          <h3 class="mb-0"><?= htmlspecialchars((string) $cardValues[$index], ENT_QUOTES, 'UTF-8') ?></h3>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="row">
    <div class="col-lg-6 mb-20">
      <div class="card-box pd-20 h-100">
        <h5 class="mb-3">Training Mother List</h5>
        <div class="table-responsive"><table class="table table-bordered">
          <thead><tr><th>Training</th><th>Inclusive Date</th><th>Forms / Days</th><th>Participants</th></tr></thead>
          <tbody>
            <?php foreach (array_slice($trainings, 0, 8) as $training): ?>
              <tr>
                <td><a href="ld_training_view.php?code=<?= urlencode($training['trainingmatrixUCODE']) ?>"><?= htmlspecialchars($training['trainingmatrixTITLE'], ENT_QUOTES, 'UTF-8') ?></a></td>
                <td><?= htmlspecialchars($training['trainingmatrixINCLUSIVEDATE'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) $training['day_count'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) $training['participant_count'], ENT_QUOTES, 'UTF-8') ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$trainings): ?><tr><td colspan="4" class="text-center text-muted">No training records yet.</td></tr><?php endif; ?>
          </tbody>
        </table></div>
      </div>
    </div>

    <div class="col-lg-6 mb-20">
      <div class="card-box pd-20 h-100">
        <h5 class="mb-3"><?= $scope === 'employee' ? 'My Attendance Records' : 'Recent Attendance Records' ?></h5>
        <div class="table-responsive"><table class="table table-bordered">
          <thead><tr><th>Participant</th><th>Training</th><th>School</th><th>Email</th></tr></thead>
          <tbody>
            <?php foreach ($participants as $participant): ?>
              <tr>
                <td><?= htmlspecialchars(trim($participant['app_infoFIRST'] . ' ' . $participant['app_infoLAST']), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($participant['trainingmatrixTITLE'] ?: $participant['traininguniquecode'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($participant['app_infoSCHOOLNAME'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($participant['app_infoEMAIL'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$participants): ?><tr><td colspan="4" class="text-center text-muted">No attendance records yet.</td></tr><?php endif; ?>
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
</body></html>
