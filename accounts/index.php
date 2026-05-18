<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

$userModel = new User($pdo);
require_login();
require_once __DIR__ . '/partials/session.php';

$roleId = (int) ($currentUser['role_id'] ?? 0);
$fullName = trim(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? ''));
$canValidate201 = user_can_validate($pdo, '201');
$canValidateOpcrf = user_can_validate_division_opcrf($pdo);
$canValidateIpcrf = user_can_validate($pdo, 'ipcrf');
$canValidateLeave = user_can_validate($pdo, 'leave');
$hasValidatorTasks = $canValidate201 || $canValidateOpcrf || $canValidateIpcrf || $canValidateLeave;

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

if ($hasValidatorTasks) {
    array_unshift($visibleShortcuts, [
        'href' => 'validator_dashboard.php',
        'icon' => 'bi bi-speedometer2',
        'label' => 'Validator Dashboard',
    ]);
}
?>
<!DOCTYPE html>
<html>
 <?php require_once __DIR__ . '/partials/head.php'; ?>
  <style>
    .prime-home-hero {
      padding: 6px 0 18px;
    }

    .prime-home-role {
      display: inline-flex;
      align-items: center;
      min-height: 28px;
      padding: 5px 10px;
      border: 1px solid #dbeafe;
      border-radius: 999px;
      color: #1d4ed8;
      background: #eff6ff;
      font-size: 12px;
      font-weight: 800;
      line-height: 1;
    }

    .prime-shortcut-panel {
      margin-bottom: 20px;
      padding: 18px 0 8px;
    }

    .prime-shortcut-header {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 14px;
    }

    .prime-shortcut-header h5 {
      margin: 0;
      color: #111827;
      font-weight: 800;
      letter-spacing: 0;
    }

    .prime-shortcut-header p {
      margin: 3px 0 0;
      color: #667085;
      font-size: 13px;
      font-weight: 600;
    }

    .prime-shortcut-count {
      color: #475467;
      font-size: 12px;
      font-weight: 800;
      white-space: nowrap;
    }

    .prime-shortcut-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(108px, 1fr));
      gap: 12px;
    }

    .prime-shortcut-link {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 112px;
      padding: 14px 8px;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      color: #1f2937;
      background: #ffffff;
      text-align: center;
      text-decoration: none;
      box-shadow: 0 8px 20px rgba(15, 23, 42, .06);
      transition: transform .16s ease, border-color .16s ease, box-shadow .16s ease;
    }

    .prime-shortcut-link:hover {
      color: #155eef;
      border-color: #bfdbfe;
      text-decoration: none;
      transform: translateY(-2px);
      box-shadow: 0 12px 28px rgba(37, 99, 235, .12);
    }

    .prime-shortcut-icon {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 46px;
      height: 46px;
      margin-bottom: 10px;
      border-radius: 8px;
      color: #155eef;
      background: #eff6ff;
      font-size: 24px;
      line-height: 1;
    }

    .prime-shortcut-label {
      display: block;
      width: 100%;
      color: inherit;
      font-size: 13px;
      font-weight: 800;
      line-height: 1.25;
      overflow-wrap: anywhere;
    }

    .prime-profile-card {
      margin-top: 4px;
    }

    @media (max-width: 575.98px) {
      .prime-home-hero {
        padding-top: 0;
      }

      .prime-home-hero h2 {
        font-size: 22px;
      }

      .prime-shortcut-panel {
        padding-top: 8px;
      }

      .prime-shortcut-header {
        align-items: flex-start;
      }

      .prime-shortcut-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 8px;
      }

      .prime-shortcut-link {
        min-height: 88px;
        padding: 10px 4px;
        box-shadow: none;
      }

      .prime-shortcut-icon {
        width: 38px;
        height: 38px;
        margin-bottom: 7px;
        font-size: 20px;
      }

      .prime-shortcut-label {
        font-size: 11px;
        line-height: 1.15;
      }
    }
  </style>
  <body>
   <?php require_once __DIR__ . '/partials/preloader.php'; ?>

    <?php require_once __DIR__ . '/partials/navbar.php'; ?>
    <?php require_once __DIR__ . '/partials/rightsidebar.php'; ?>
    <?php require_once __DIR__ . '/partials/leftsidebar.php'; ?>
    <div class="mobile-menu-overlay"></div>

    <div class="main-container">
      <div class="xs-pd-20-10 pd-ltr-20">
        <div class="prime-home-hero">
          <span class="prime-home-role mb-2"><?= htmlspecialchars($role['role_name'] ?? 'User') ?></span>
          <h2 class="mb-1">Welcome, <?= htmlspecialchars($fullName !== '' ? $fullName : 'User') ?></h2>
          <p class="text-700 mb-0">Open your most important PRIMEHR tools.</p>
        </div>

        <div class="prime-shortcut-panel">
          <div class="prime-shortcut-header">
            <div>
              <h5>Shortcuts</h5>
              <p>Tap an icon to continue.</p>
            </div>
            <span class="prime-shortcut-count"><?= count($visibleShortcuts) ?> available</span>
          </div>
          <div class="prime-shortcut-grid">
            <?php foreach ($visibleShortcuts as $shortcut): ?>
              <a href="<?= htmlspecialchars($shortcut['href'], ENT_QUOTES, 'UTF-8') ?>" class="prime-shortcut-link" aria-label="<?= htmlspecialchars($shortcut['label'], ENT_QUOTES, 'UTF-8') ?>">
                <span class="prime-shortcut-icon">
                  <i class="<?= htmlspecialchars($shortcut['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                </span>
                <span class="prime-shortcut-label"><?= htmlspecialchars($shortcut['label']) ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="row prime-profile-card">
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
