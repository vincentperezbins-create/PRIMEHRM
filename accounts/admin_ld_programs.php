<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/ld_helpers.php';
$userModel = new User($pdo);
require_login();
require_role([1, 2, 5, 6, 7]);
require_once __DIR__ . '/partials/session.php';

ld_ensure_schema($pdo);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("
        INSERT INTO ld_programs (category_id, program_title, description, competency_focus, target_participants, status, owner_user_id, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT) ?: null,
        trim((string) $_POST['program_title']),
        trim((string) ($_POST['description'] ?? '')),
        trim((string) ($_POST['competency_focus'] ?? '')),
        trim((string) ($_POST['target_participants'] ?? '')),
        $_POST['status'] ?? 'Draft',
        filter_input(INPUT_POST, 'owner_user_id', FILTER_VALIDATE_INT) ?: (int) $currentUser['user_id'],
        (int) $currentUser['user_id'],
    ]);
    header('Location: admin_ld_programs.php?saved=1');
    exit;
}

$categories = ld_categories($pdo);
$programs = ld_programs($pdo, $currentUser);
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
    <div><h2>L&D Master Plan</h2><p class="text-700 mb-0">Create annual development plans, competency programs, and career growth initiatives.</p></div>
    <a class="btn btn-outline-primary" href="ld_dashboard.php">Dashboard</a>
  </div>
  <div class="row">
    <div class="col-lg-4 mb-20"><div class="card-box pd-20">
      <h5 class="mb-3">Create L&D Program</h5>
      <form method="post">
        <div class="mb-3"><label>Program Title</label><input name="program_title" class="form-control" required placeholder="Annual Leadership Development Program"></div>
        <div class="mb-3"><label>Category</label><select name="category_id" class="form-control"><?php foreach ($categories as $category): ?><option value="<?= (int) $category['category_id'] ?>"><?= htmlspecialchars($category['category_name'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><label>Owner</label><select name="owner_user_id" class="form-control"><?php foreach ($owners as $owner): ?><option value="<?= (int) $owner['user_id'] ?>" <?= (int) $owner['user_id'] === (int) $currentUser['user_id'] ? 'selected' : '' ?>><?= htmlspecialchars(trim($owner['first_name'] . ' ' . $owner['last_name']), ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
        <div class="mb-3"><label>Status</label><select name="status" class="form-control"><option>Draft</option><option>Active</option><option>Completed</option><option>Archived</option></select></div>
        <div class="mb-3"><label>Competency Focus</label><textarea name="competency_focus" class="form-control" rows="3"></textarea></div>
        <div class="mb-3"><label>Target Participants</label><textarea name="target_participants" class="form-control" rows="3"></textarea></div>
        <div class="mb-3"><label>Description</label><textarea name="description" class="form-control" rows="4"></textarea></div>
        <button class="btn btn-primary w-100">Save Program</button>
      </form>
    </div></div>
    <div class="col-lg-8 mb-20"><div class="card-box pd-20">
      <h5 class="mb-3">Programs</h5>
      <div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Program</th><th>Category</th><th>Owner</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($programs as $program): ?><tr><td><?= htmlspecialchars($program['program_title'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($program['category_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($program['owner_name'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td><td><?= ld_status_badge($program['status']) ?></td></tr><?php endforeach; ?>
        <?php if (!$programs): ?><tr><td colspan="4" class="text-center text-muted">No L&D programs yet.</td></tr><?php endif; ?>
      </tbody></table></div>
    </div></div>
  </div>
  <?php require_once __DIR__ . '/partials/footer.php'; ?>
</div></div>
<?php require_once __DIR__ . '/partials/welcomemodal.php'; ?>
<script src="vendors/scripts/core.js"></script><script src="vendors/scripts/script.min.js"></script><script src="vendors/scripts/process.js"></script><script src="vendors/scripts/layout-settings.js"></script>
</body></html>
