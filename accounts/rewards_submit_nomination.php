<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/rewards_helpers.php';
$userModel = new User($pdo);
require_login();
require_role([1, 3]);
require_once __DIR__ . '/partials/session.php';

rewards_ensure_schema($pdo);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomineeId = filter_input(INPUT_POST, 'nominee_user_id', FILTER_VALIDATE_INT);
    $programId = filter_input(INPUT_POST, 'program_id', FILTER_VALIDATE_INT);
    $schoolId = (string) ($currentUser['school_id'] ?? '');
    if ((int) $currentUser['role_id'] === 1) {
        $schoolStmt = $pdo->prepare("SELECT school_id FROM sdopang1_user WHERE user_id = ?");
        $schoolStmt->execute([$nomineeId]);
        $schoolId = (string) $schoolStmt->fetchColumn();
    }
    $stmt = $pdo->prepare("
        INSERT INTO reward_nominations (program_id, nominee_user_id, nominated_by, school_id, endorsement_text)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$programId, $nomineeId, (int) $currentUser['user_id'], $schoolId, trim((string) ($_POST['endorsement_text'] ?? ''))]);
    header('Location: rewards_submit_nomination.php?saved=1');
    exit;
}

$programs = rewards_programs($pdo, $currentUser, true);
$employees = rewards_employee_options($pdo, $currentUser);
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
    <div><h2>Submit Nomination</h2><p class="text-700 mb-0">Nominate teachers or staff for open award programs.</p></div>
    <a class="btn btn-outline-primary" href="rewards_dashboard.php">Dashboard</a>
  </div>
  <div class="card-box pd-20">
    <form method="post">
      <div class="row">
        <div class="col-md-6 mb-3"><label>Award Program</label><select name="program_id" class="form-control" required>
          <option value="">Select program</option>
          <?php foreach ($programs as $program): ?><option value="<?= (int) $program['program_id'] ?>"><?= htmlspecialchars($program['title'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
        </select></div>
        <div class="col-md-6 mb-3"><label>Nominee</label><select name="nominee_user_id" class="form-control" required>
          <option value="">Select employee</option>
          <?php foreach ($employees as $employee): ?><option value="<?= (int) $employee['user_id'] ?>"><?= htmlspecialchars((($employee['employeeID'] ?? '') ?: 'No ID') . ' - ' . rewards_user_name($employee) . ' - ' . ($employee['schoolname'] ?? ''), ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
        </select></div>
      </div>
      <div class="mb-3"><label>Endorsement / Narrative</label><textarea name="endorsement_text" class="form-control" rows="6" required placeholder="Summarize achievements, performance evidence, service impact, and supporting documents."></textarea></div>
      <div class="alert alert-info">Required documents: Nomination Form, Endorsement Letter, IPCRF / Performance Proof, Certificates / Achievements, Portfolio / Narrative, and Supporting Evidence.</div>
      <button class="btn btn-primary" type="submit">Submit Nomination</button>
    </form>
  </div>
  <?php require_once __DIR__ . '/partials/footer.php'; ?>
</div></div>
<?php require_once __DIR__ . '/partials/welcomemodal.php'; ?>
<script src="vendors/scripts/core.js"></script><script src="vendors/scripts/script.min.js"></script><script src="vendors/scripts/process.js"></script><script src="vendors/scripts/layout-settings.js"></script>
</body></html>
