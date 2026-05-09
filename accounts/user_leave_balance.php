<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

$userModel = new User($pdo);
require_login();
require_role([1, 2, 3, 4, 5, 6, 7]);
require_once __DIR__ . '/partials/session.php';
require_once __DIR__ . '/partials/page_info.php';

$stmt = $pdo->prepare("
    SELECT lt.leave_code, lt.leave_name, COALESCE(lb.balance, 0) AS balance
    FROM leave_types lt
    LEFT JOIN leave_balances lb
      ON lb.leave_type_id = lt.leave_type_id
     AND lb.user_id = ?
    WHERE lt.is_active = 1
    ORDER BY lt.leave_code
");
$stmt->execute([$_SESSION['user_id']]);
$balances = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            <h2 class="mb-1">My Leave Balance</h2>
            <p class="text-700 mb-0">Available leave credits by type.</p>
          </div>
          <a href="user_leave_apply.php" class="btn btn-primary">Apply Leave</a>
        </div>

        <?php page_info(
            'What this page does',
            'View your current leave balances by leave type.',
            [
                'Balances update after approved applications, accruals, yearly entitlements, and manual adjustments.',
                'Some leave types are fixed entitlements and may reset yearly instead of accumulating.'
            ]
        ); ?>

        <div class="row">
          <?php foreach ($balances as $balance): ?>
            <div class="col-md-4 mb-20">
              <div class="card h-100">
                <div class="card-body">
                  <p class="text-700 mb-1"><?= htmlspecialchars($balance['leave_code']) ?></p>
                  <h5 class="mb-2"><?= htmlspecialchars($balance['leave_name']) ?></h5>
                  <h2 class="mb-0"><?= htmlspecialchars(number_format((float) $balance['balance'], 3)) ?></h2>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
          <?php if (!$balances): ?>
            <div class="col-12">
              <div class="card"><div class="card-body text-center text-muted">No leave balances available.</div></div>
            </div>
          <?php endif; ?>
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

