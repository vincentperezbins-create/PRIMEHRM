<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/rewards_helpers.php';
$userModel = new User($pdo);
require_login();
require_role([1, 2, 5, 6, 7]);
require_once __DIR__ . '/partials/session.php';

rewards_ensure_schema($pdo);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nominationId = filter_input(INPUT_POST, 'nomination_id', FILTER_VALIDATE_INT);
    $status = $_POST['status'] ?? 'Submitted';
    $score = $_POST['score'] !== '' ? (float) $_POST['score'] : null;
    $remarks = trim((string) ($_POST['remarks'] ?? ''));
    $stmt = $pdo->prepare("UPDATE reward_nominations SET status = ?, score = ?, remarks = ?, updated_at = NOW() WHERE nomination_id = ?");
    $stmt->execute([$status, $score, $remarks, $nominationId]);
    if ($status === 'Winner') {
        $winner = $pdo->prepare("
            SELECT n.*, p.title, c.category_name
            FROM reward_nominations n
            JOIN reward_programs p ON p.program_id = n.program_id
            LEFT JOIN reward_categories c ON c.category_id = p.category_id
            WHERE n.nomination_id = ?
        ");
        $winner->execute([$nominationId]);
        $row = $winner->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $insert = $pdo->prepare("
                INSERT INTO reward_recognitions (nomination_id, program_id, user_id, title, category_name, awarded_at)
                SELECT ?, ?, ?, ?, ?, CURDATE()
                WHERE NOT EXISTS (SELECT 1 FROM reward_recognitions WHERE nomination_id = ?)
            ");
            $insert->execute([$nominationId, $row['program_id'], $row['nominee_user_id'], $row['title'], $row['category_name'], $nominationId]);
        }
    }
    header('Location: admin_rewards_nominees.php?saved=1');
    exit;
}

$nominations = rewards_nominees_for_scope($pdo, $currentUser);
$statuses = ['Submitted','Lacking Documents','Validated','Shortlisted','For Evaluation','Winner','Not Selected'];
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
    <div><h2>Nomination Review</h2><p class="text-700 mb-0">Validate requirements, score nominees, shortlist candidates, and approve winners.</p></div>
    <a class="btn btn-outline-primary" href="rewards_dashboard.php">Dashboard</a>
  </div>
  <div class="card-box pd-20">
    <div class="table-responsive"><table class="table table-bordered">
      <thead><tr><th>Nominee</th><th>Program</th><th>School</th><th>Status / Score</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach ($nominations as $nomination): ?>
          <tr>
            <td><?= htmlspecialchars($nomination['nominee_name'], ENT_QUOTES, 'UTF-8') ?><br><small><?= htmlspecialchars($nomination['nominated_by_name'], ENT_QUOTES, 'UTF-8') ?></small></td>
            <td><?= htmlspecialchars($nomination['program_title'], ENT_QUOTES, 'UTF-8') ?><br><small><?= htmlspecialchars($nomination['category_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></td>
            <td><?= htmlspecialchars($nomination['schoolname'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars($nomination['status'], ENT_QUOTES, 'UTF-8') ?><br><small>Score: <?= htmlspecialchars($nomination['score'] ?? '-', ENT_QUOTES, 'UTF-8') ?></small></td>
            <td>
              <form method="post" class="d-flex flex-wrap gap-2">
                <input type="hidden" name="nomination_id" value="<?= (int) $nomination['nomination_id'] ?>">
                <select name="status" class="form-control form-control-sm" style="width:180px"><?php foreach ($statuses as $status): ?><option <?= $status === $nomination['status'] ? 'selected' : '' ?>><?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select>
                <input name="score" type="number" step="0.01" class="form-control form-control-sm" style="width:90px" value="<?= htmlspecialchars((string) ($nomination['score'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Score">
                <input name="remarks" class="form-control form-control-sm" style="width:220px" value="<?= htmlspecialchars((string) ($nomination['remarks'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Remarks">
                <button class="btn btn-sm btn-primary" type="submit">Save</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$nominations): ?><tr><td colspan="5" class="text-center text-muted">No nominations yet.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </div>
  <?php require_once __DIR__ . '/partials/footer.php'; ?>
</div></div>
<?php require_once __DIR__ . '/partials/welcomemodal.php'; ?>
<script src="vendors/scripts/core.js"></script><script src="vendors/scripts/script.min.js"></script><script src="vendors/scripts/process.js"></script><script src="vendors/scripts/layout-settings.js"></script>
</body></html>
