<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/core/ld_helpers.php';
require_once __DIR__ . '/core/audit.php';

$userModel = new User($pdo);
require_login();
require_role([1, 2, 3, 5, 6, 7]);
require_once __DIR__ . '/partials/session.php';

ld_ensure_schema($pdo);
$token = generateToken();
$h = static fn($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyToken($_POST['token'] ?? '')) {
        $_SESSION['error_message'] = 'Invalid request token. Please try again.';
        header('Location: admin_ld_certificates.php');
        exit;
    }

    $submissionId = (int) ($_POST['certificate_submission_id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');
    $status = $action === 'approve' ? 'Approved' : ($action === 'reject' ? 'Rejected' : '');
    $remarks = trim((string) ($_POST['review_remarks'] ?? ''));

    $stmt = $pdo->prepare("
        SELECT cs.*, u.school_id
        FROM ld_certificate_submissions cs
        JOIN sdopang1_user u ON u.user_id = cs.user_id
        WHERE cs.certificate_submission_id = ?
        LIMIT 1
    ");
    $stmt->execute([$submissionId]);
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$submission || $status === '' || !ld_certificate_submission_can_review($submission, $currentUser)) {
        $_SESSION['error_message'] = 'You are not allowed to review this certificate.';
        header('Location: admin_ld_certificates.php');
        exit;
    }

    $update = $pdo->prepare("
        UPDATE ld_certificate_submissions
        SET status = ?, reviewed_by = ?, reviewed_at = NOW(), review_remarks = NULLIF(?, '')
        WHERE certificate_submission_id = ?
    ");
    $update->execute([$status, (int) $currentUser['user_id'], $remarks, $submissionId]);

    audit_log(
        $pdo,
        $currentUser['user_id'] ?? null,
        audit_current_fullname($pdo),
        $status === 'Approved' ? 'APPROVE' : 'REJECT',
        'L&D Certificates',
        $submissionId,
        $status . ' a submitted training certificate.'
    );

    $_SESSION['success_message'] = 'Certificate has been ' . strtolower($status) . '.';
    header('Location: admin_ld_certificates.php');
    exit;
}

$statusFilter = trim((string) ($_GET['status'] ?? ''));
$allowedStatuses = ['', 'Pending', 'Approved', 'Rejected'];
if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = '';
}
$submissions = ld_certificate_submissions($pdo, $currentUser, $statusFilter !== '' ? $statusFilter : null, 300);
$successMessage = $_SESSION['success_message'] ?? '';
$errorMessage = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
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
  <div class="d-flex flex-column flex-lg-row justify-content-between pb-20">
    <div><h2>Certificate Validation</h2><p class="text-700 mb-0">Review uploaded training certificates before they appear as approved profile records.</p></div>
    <a class="btn btn-outline-primary" href="admin_ld_participants.php">Participants</a>
  </div>

  <?php if ($successMessage): ?><div class="alert alert-success"><?= $h($successMessage) ?></div><?php endif; ?>
  <?php if ($errorMessage): ?><div class="alert alert-danger"><?= $h($errorMessage) ?></div><?php endif; ?>

  <div class="card-box pd-20">
    <form class="form-inline mb-3" method="GET">
      <label class="mr-2">Status</label>
      <select class="form-control mr-2" name="status">
        <?php foreach ($allowedStatuses as $status): ?>
          <option value="<?= $h($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= $status === '' ? 'All' : $h($status) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-primary" type="submit">Filter</button>
    </form>
    <div class="table-responsive"><table class="table table-bordered">
      <thead><tr><th>Employee</th><th>Training / Certificate</th><th>School</th><th>Status</th><th>File</th><th>Review</th></tr></thead>
      <tbody>
        <?php foreach ($submissions as $submission): ?>
          <tr>
            <td>
              <strong><?= $h(trim($submission['employee_name'])) ?></strong><br>
              <small><?= $h($submission['employeeID'] ?: $submission['email']) ?></small>
            </td>
            <td>
              <?= $h($submission['training_title']) ?><br>
              <small><?= $submission['trainingmatrix_ucode'] ? 'Matched system title' : 'User-entered title' ?></small>
            </td>
            <td><?= $h($submission['schoolname'] ?: '-') ?></td>
            <td><?= ld_status_badge($submission['status']) ?></td>
            <td><a class="btn btn-sm btn-outline-primary" href="<?= $h($submission['certificate_path']) ?>" target="_blank">View</a></td>
            <td style="min-width: 260px;">
              <?php if ($submission['status'] === 'Pending' && ld_certificate_submission_can_review($submission, $currentUser)): ?>
                <form method="POST" class="mb-0">
                  <input type="hidden" name="token" value="<?= $h($token) ?>">
                  <input type="hidden" name="certificate_submission_id" value="<?= (int) $submission['certificate_submission_id'] ?>">
                  <textarea class="form-control mb-2" name="review_remarks" rows="2" placeholder="Remarks"></textarea>
                  <button class="btn btn-sm btn-success" name="action" value="approve" type="submit">Approve</button>
                  <button class="btn btn-sm btn-danger" name="action" value="reject" type="submit">Reject</button>
                </form>
              <?php else: ?>
                <span><?= $h($submission['review_remarks'] ?: '-') ?></span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$submissions): ?><tr><td colspan="6" class="text-center text-muted">No certificate submissions found.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
  </div>
  <?php require_once __DIR__ . '/partials/footer.php'; ?>
</div></div>
<?php require_once __DIR__ . '/partials/welcomemodal.php'; ?>
<script src="vendors/scripts/core.js"></script>
<script src="vendors/scripts/script.min.js"></script>
<script src="vendors/scripts/process.js"></script>
<script src="vendors/scripts/layout-settings.js"></script>
</body></html>
