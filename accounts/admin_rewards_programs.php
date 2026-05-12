<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/rewards_helpers.php';
$userModel = new User($pdo);
require_login();
require_role([1, 2, 5, 6, 7]);
require_once __DIR__ . '/partials/session.php';

rewards_ensure_schema($pdo);
$scope = rewards_role_scope();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("
        INSERT INTO reward_programs
            (category_id, title, description, eligibility, requirements, status, nomination_start, nomination_end, owner_user_id, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT) ?: null,
        trim((string) $_POST['title']),
        trim((string) ($_POST['description'] ?? '')),
        trim((string) ($_POST['eligibility'] ?? '')),
        trim((string) ($_POST['requirements'] ?? '')),
        $_POST['status'] ?? 'Draft',
        $_POST['nomination_start'] ?: null,
        $_POST['nomination_end'] ?: null,
        filter_input(INPUT_POST, 'owner_user_id', FILTER_VALIDATE_INT) ?: (int) $currentUser['user_id'],
        (int) $currentUser['user_id'],
    ]);
    header('Location: admin_rewards_programs.php?saved=1');
    exit;
}

$categories = rewards_categories($pdo);
$programs = rewards_programs($pdo, $currentUser);
$owners = $pdo->query("SELECT user_id, first_name, last_name FROM sdopang1_user ORDER BY first_name, last_name")->fetchAll(PDO::FETCH_ASSOC);
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
    <div><h2>Award Programs</h2><p class="text-700 mb-0">Create award calls, criteria, eligibility, requirements, and program ownership.</p></div>
    <a class="btn btn-outline-primary" href="rewards_dashboard.php">Dashboard</a>
  </div>

  <div class="row">
    <div class="col-lg-4 mb-20">
      <div class="card-box pd-20">
        <h5 class="mb-3">Create Program</h5>
        <form method="post">
          <div class="mb-3"><label class="form-label">Title</label><input name="title" class="form-control" required placeholder="Outstanding Teacher 2026"></div>
          <div class="mb-3"><label class="form-label">Category</label><select name="category_id" class="form-control" required>
            <?php foreach ($categories as $category): ?><option value="<?= (int) $category['category_id'] ?>"><?= htmlspecialchars($category['category_group'] . ' - ' . $category['category_name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
          </select></div>
          <div class="mb-3"><label class="form-label">Owner</label><select name="owner_user_id" class="form-control">
            <?php foreach ($owners as $owner): ?><option value="<?= (int) $owner['user_id'] ?>" <?= (int) $owner['user_id'] === (int) $currentUser['user_id'] ? 'selected' : '' ?>><?= htmlspecialchars(trim($owner['first_name'] . ' ' . $owner['last_name']), ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
          </select></div>
          <div class="row"><div class="col-md-6 mb-3"><label>Start</label><input name="nomination_start" type="date" class="form-control"></div><div class="col-md-6 mb-3"><label>End</label><input name="nomination_end" type="date" class="form-control"></div></div>
          <div class="mb-3"><label>Status</label><select name="status" class="form-control"><option>Draft</option><option>Open</option><option>Screening</option><option>Evaluation</option><option>Closed</option><option>Published</option></select></div>
          <div class="mb-3"><label>Description</label><textarea name="description" class="form-control" rows="3"></textarea></div>
          <div class="mb-3"><label>Eligibility</label><textarea name="eligibility" class="form-control" rows="3"></textarea></div>
          <div class="mb-3"><label>Requirements</label><textarea name="requirements" class="form-control" rows="3">Nomination Form
Endorsement Letter
IPCRF / Performance Proof
Certificates / Achievements
Portfolio / Narrative
Supporting Evidence</textarea></div>
          <button class="btn btn-primary w-100" type="submit">Create Program</button>
        </form>
      </div>
    </div>
    <div class="col-lg-8 mb-20">
      <div class="card-box pd-20">
        <h5 class="mb-3">Programs</h5>
        <div class="table-responsive"><table class="table table-bordered">
          <thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Owner</th><th>Period</th></tr></thead>
          <tbody><?php foreach ($programs as $program): ?><tr>
            <td><?= htmlspecialchars($program['title'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($program['category_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($program['status'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($program['owner_name'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars(($program['nomination_start'] ?: '-') . ' to ' . ($program['nomination_end'] ?: '-'), ENT_QUOTES, 'UTF-8') ?></td>
          </tr><?php endforeach; ?><?php if (!$programs): ?><tr><td colspan="5" class="text-center text-muted">No programs yet.</td></tr><?php endif; ?></tbody>
        </table></div>
      </div>
    </div>
  </div>
  <?php require_once __DIR__ . '/partials/footer.php'; ?>
</div></div>
<?php require_once __DIR__ . '/partials/welcomemodal.php'; ?>
<script src="vendors/scripts/core.js"></script><script src="vendors/scripts/script.min.js"></script><script src="vendors/scripts/process.js"></script><script src="vendors/scripts/layout-settings.js"></script>
</body></html>
