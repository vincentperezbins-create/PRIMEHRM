<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

$userModel = new User($pdo);
require_login();
require_once __DIR__ . '/partials/session.php';

$roleId = (int) ($currentUser['role_id'] ?? 0);
$fullName = trim(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? ''));

$positionStmt = $pdo->prepare("
    SELECT position_title, position_category
    FROM sdopang1_position
    WHERE position_id = ?
    LIMIT 1
");
$positionStmt->execute([$currentUser['position_id'] ?? null]);
$position = $positionStmt->fetch(PDO::FETCH_ASSOC) ?: [];

$shortcuts = [
    [
        'roles' => [1],
        'href' => 'admin_dashboard.php',
        'icon' => 'bi bi-speedometer2',
        'label' => 'Admin Dashboard',
    ],
    [
        'roles' => [1],
        'href' => 'admin_201_tables.php',
        'icon' => 'bi bi-folder-check',
        'label' => 'Validate 201 Files',
    ],
    [
        'roles' => [1],
        'href' => 'admin_leave_dashboard.php',
        'icon' => 'bi bi-calendar-check',
        'label' => 'Leave Dashboard',
    ],
    [
        'roles' => [1],
        'href' => 'admin_users_list.php',
        'icon' => 'bi bi-people',
        'label' => 'Users',
    ],
    [
        'roles' => [1],
        'href' => 'admin_school_list.php',
        'icon' => 'bi bi-building',
        'label' => 'Schools',
    ],
    [
        'roles' => [3],
        'href' => 'school_dashboard.php',
        'icon' => 'bi bi-speedometer2',
        'label' => 'School Dashboard',
    ],
    [
        'roles' => [3],
        'href' => 'school_201_tables.php',
        'icon' => 'bi bi-folder-check',
        'label' => 'School 201 Files',
    ],
    [
        'roles' => [3],
        'href' => 'user_201_tables.php',
        'icon' => 'bi bi-folder',
        'label' => 'My 201 Files',
    ],
    [
        'roles' => [3],
        'href' => 'school_leave_applications.php',
        'icon' => 'bi bi-calendar-check',
        'label' => 'School Leave',
    ],
    [
        'roles' => [3],
        'href' => 'user_leave_apply.php',
        'icon' => 'bi bi-calendar-plus',
        'label' => 'Apply Leave',
    ],
    [
        'roles' => [2, 5, 6, 7],
        'href' => 'user_201_tables.php',
        'icon' => 'bi bi-inbox',
        'label' => 'My Submissions',
    ],
    [
        'roles' => [2, 5, 6, 7],
        'href' => 'user_ipcrf_list.php',
        'icon' => 'bi bi-clipboard-data',
        'label' => 'My IPCRF',
    ],
    [
        'roles' => [2, 5, 6, 7],
        'href' => 'user_leave_balance.php',
        'icon' => 'bi bi-wallet2',
        'label' => 'Leave Credits',
    ],
    [
        'roles' => [4],
        'href' => 'user_dashboard.php',
        'icon' => 'bi bi-speedometer2',
        'label' => 'My Dashboard',
    ],
    [
        'roles' => [4],
        'href' => 'user_201_tables.php',
        'icon' => 'bi bi-folder',
        'label' => 'My 201 Files',
    ],
    [
        'roles' => [4],
        'href' => 'user_leave_apply.php',
        'icon' => 'bi bi-calendar-plus',
        'label' => 'Apply Leave',
    ],
    [
        'roles' => [4],
        'href' => 'user_leave_balance.php',
        'icon' => 'bi bi-wallet2',
        'label' => 'Leave Balance',
    ],
];

$visibleShortcuts = array_values(array_filter($shortcuts, function ($shortcut) use ($roleId) {
    return in_array($roleId, $shortcut['roles'], true);
}));
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
        <div class="pb-20">
          <p class="text-700 mb-1"><?= htmlspecialchars($role['role_name'] ?? 'User') ?></p>
          <h2 class="mb-1">Welcome, <?= htmlspecialchars($fullName !== '' ? $fullName : 'User') ?></h2>
          <p class="text-700 mb-0">Choose where you want to go.</p>
        </div>

        <div class="row">
          <div class="col-12 mb-20">
            <div class="card-box pd-20">
              <h5 class="mb-3">Profile</h5>
              <div class="row">
                <div class="col-md-4 mb-3">
                  <p class="text-700 mb-1">Full Name</p>
                  <h6 class="mb-0"><?= htmlspecialchars($fullName !== '' ? $fullName : '-') ?></h6>
                </div>
                <div class="col-md-4 mb-3">
                  <p class="text-700 mb-1">Email</p>
                  <h6 class="mb-0"><?= htmlspecialchars($currentUser['email'] ?: '-') ?></h6>
                </div>
                <div class="col-md-4 mb-3">
                  <p class="text-700 mb-1">Employee ID</p>
                  <h6 class="mb-0"><?= htmlspecialchars($currentUser['employeeID'] ?: 'N/A') ?></h6>
                </div>
                <div class="col-md-4 mb-3">
                  <p class="text-700 mb-1">Position</p>
                  <h6 class="mb-0"><?= htmlspecialchars($position['position_title'] ?? 'Not set') ?></h6>
                </div>
                <div class="col-md-4 mb-3">
                  <p class="text-700 mb-1">Position Category</p>
                  <h6 class="mb-0"><?= htmlspecialchars($position['position_category'] ?? '-') ?></h6>
                </div>
                <div class="col-md-4 mb-3">
                  <p class="text-700 mb-1">Role</p>
                  <h6 class="mb-0"><?= htmlspecialchars($role['role_name'] ?? '-') ?></h6>
                </div>
              </div>
            </div>
          </div>

          <?php foreach ($visibleShortcuts as $shortcut): ?>
            <div class="col-xl-3 col-lg-4 col-md-6 mb-20">
              <a href="<?= htmlspecialchars($shortcut['href'], ENT_QUOTES, 'UTF-8') ?>" class="card-box d-block height-100-p pd-20 text-decoration-none">
                <div class="d-flex align-items-center">
                  <div class="mr-3">
                    <span class="micon <?= htmlspecialchars($shortcut['icon'], ENT_QUOTES, 'UTF-8') ?>" style="font-size: 32px;"></span>
                  </div>
                  <div>
                    <h5 class="mb-1 text-dark"><?= htmlspecialchars($shortcut['label']) ?></h5>
                    <p class="mb-0 text-700">Open</p>
                  </div>
                </div>
              </a>
            </div>
          <?php endforeach; ?>
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
