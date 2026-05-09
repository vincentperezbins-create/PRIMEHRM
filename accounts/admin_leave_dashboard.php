<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

$userModel = new User($pdo);
require_login();
require_role([1]);
require_once __DIR__ . '/partials/session.php';
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/partials/page_info.php';

$token = generateToken();
$totalEmployees = (int) $pdo->query("SELECT COUNT(*) FROM sdopang1_user")->fetchColumn();
$pendingApplications = (int) $pdo->query("SELECT COUNT(*) FROM leave_applications WHERE status='pending'")->fetchColumn();
$approvedThisMonth = (int) $pdo->query("
    SELECT COUNT(*)
    FROM leave_applications
    WHERE status = 'approved'
      AND YEAR(approved_at) = YEAR(CURDATE())
      AND MONTH(approved_at) = MONTH(CURDATE())
")->fetchColumn();
$rejectedThisMonth = (int) $pdo->query("
    SELECT COUNT(*)
    FROM leave_applications
    WHERE status = 'rejected'
      AND YEAR(approved_at) = YEAR(CURDATE())
      AND MONTH(approved_at) = MONTH(CURDATE())
")->fetchColumn();
$lowBalanceEmployees = (int) $pdo->query("
    SELECT COUNT(DISTINCT user_id)
    FROM leave_balances
    WHERE balance < 3
")->fetchColumn();
$transactions = (int) $pdo->query("SELECT COUNT(*) FROM leave_transactions")->fetchColumn();
$monthlyRun = $pdo->prepare("SELECT run_key FROM system_runs WHERE process_name='monthly_accrual' AND run_key=? LIMIT 1");
$monthlyRun->execute([date('Y-m')]);
$monthlyRunAt = $monthlyRun->fetchColumn();
$yearlyRun = $pdo->prepare("SELECT run_key FROM system_runs WHERE process_name='yearly_forced_leave' AND run_key=? LIMIT 1");
$yearlyRun->execute([date('Y')]);
$yearlyRunAt = $yearlyRun->fetchColumn();
$yearlyEntitlementRun = $pdo->prepare("SELECT run_key FROM system_runs WHERE process_name='yearly_entitlements' AND run_key=? LIMIT 1");
$yearlyEntitlementRun->execute([date('Y')]);
$yearlyEntitlementRunAt = $yearlyEntitlementRun->fetchColumn();

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
            <h2 class="mb-1">Leave Credits Dashboard</h2>
            <p class="text-700 mb-0">System-wide leave monitoring, balances, transactions, and credit processes.</p>
          </div>
          </div>

          <?php page_info(
              'What this page does',
              'This dashboard summarizes leave activity and runs controlled leave-credit processes.',
              [
                  'Monthly accrual adds VL and SL credits for eligible non-teaching employees.',
                  'Yearly entitlements reset annual leave balances such as SPL, Solo Parent, VAWC, and Wellness Leave.',
                  'Year-end processing applies forced leave rules and can deduct unused mandatory VL.'
              ]
          ); ?>

          <div class="row g-3">
            <?php
            $leaveCards = [
                ['Total Employees', $totalEmployees, 'primary', 'bi bi-people'],
                ['Pending Applications', $pendingApplications, 'warning', 'bi bi-hourglass-split'],
                ['Approved This Month', $approvedThisMonth, 'success', 'bi bi-calendar-check'],
                ['Rejected This Month', $rejectedThisMonth, 'danger', 'bi bi-calendar-x'],
                ['Low Balance Employees', $lowBalanceEmployees, 'danger', 'bi bi-exclamation-triangle'],
                ['Transactions', $transactions, 'info', 'bi bi-journal-text'],
            ];
            foreach ($leaveCards as $card):
            ?>
            <div class="col-md-4 mb-20">
              <div class="prime-stat-card stat-<?= htmlspecialchars($card[2]) ?>">
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <p class="stat-label"><?= htmlspecialchars($card[0]) ?></p>
                    <p class="stat-value"><?= htmlspecialchars((string) $card[1]) ?></p>
                  </div>
                  <div class="stat-icon">
                    <i class="<?= htmlspecialchars($card[3], ENT_QUOTES, 'UTF-8') ?>"></i>
                  </div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
       <h4 class="mb-3">Leave System Tools</h4>

<div class="card p-3 mb-20">
    <p class="mb-2">Monthly accrual: <?= htmlspecialchars($monthlyRunAt ? "completed for " . date('Y-m') : "not run for " . date('Y-m')) ?></p>

    <button id="btnMonthly" class="btn btn-primary mb-2" onclick="runMonthly()" <?= $monthlyRunAt ? 'disabled' : '' ?>>
        Run Monthly Accrual
    </button>
    <?php if ($monthlyRunAt): ?>
      <small class="d-block text-muted mb-2">This process is locked because it already ran for <?= htmlspecialchars(date('Y-m')) ?>.</small>
    <?php endif; ?>

    <p class="mb-2">Yearly leave entitlements: <?= htmlspecialchars($yearlyEntitlementRunAt ? "completed for " . date('Y') : "not run for " . date('Y')) ?></p>

    <button id="btnYearlyEntitlements" class="btn btn-success mb-2" onclick="runYearlyEntitlements()" <?= $yearlyEntitlementRunAt ? 'disabled' : '' ?>>
        Add Yearly Leave Entitlements
    </button>
    <?php if ($yearlyEntitlementRunAt): ?>
      <small class="d-block text-muted mb-2">This process is locked because it already ran for <?= htmlspecialchars(date('Y')) ?>.</small>
    <?php endif; ?>

    <p class="mb-2">Year-end process: <?= htmlspecialchars($yearlyRunAt ? "completed for " . date('Y') : "not run for " . date('Y')) ?></p>

    <button id="btnYearly" class="btn btn-danger" onclick="runYearly()" <?= $yearlyRunAt ? 'disabled' : '' ?>>
        Run Year-End Process
    </button>
    <?php if ($yearlyRunAt): ?>
      <small class="d-block text-muted">This process is locked because it already ran for <?= htmlspecialchars(date('Y')) ?>.</small>
    <?php endif; ?>
    <div id="cronStatus" class="alert alert-secondary mt-3 mb-0 d-none"></div>

</div>

<script>
function setCronLoading(buttonId, message) {
    const button = document.getElementById(buttonId);
    const status = document.getElementById('cronStatus');
    button.dataset.originalText = button.innerText;
    button.disabled = true;
    button.innerText = 'Running...';
    status.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-secondary');
    status.classList.add('alert-info');
    status.innerText = message;
}

function setCronResult(buttonId, message, ok) {
    const button = document.getElementById(buttonId);
    const status = document.getElementById('cronStatus');
    status.classList.remove('alert-info', 'alert-success', 'alert-danger', 'alert-secondary');
    status.classList.add(ok ? 'alert-success' : 'alert-danger');
    status.innerText = message;

    if (!ok) {
      button.disabled = false;
      button.innerText = button.dataset.originalText || 'Run';
    }
}

function postCron(buttonId, url, loadingMessage) {
    setCronLoading(buttonId, loadingMessage);

    fetch(url, {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: new URLSearchParams({token: <?= json_encode($token) ?>})
    })
    .then(async res => {
      const msg = await res.text();
      setCronResult(buttonId, msg, res.ok);
      if (res.ok) {
        setTimeout(() => location.reload(), 1200);
      }
    })
    .catch(() => {
      setCronResult(buttonId, 'Process failed. Please check the connection or server logs.', false);
    });
}

function runMonthly(){
    PrimeUI.confirmSave('Run monthly accrual for <?= date('Y-m') ?>? This can only run once for this month.').then(result => {
      if (!result.isConfirmed) return;
      postCron('btnMonthly', 'cron/leave_run_monthly.php', 'Monthly accrual is running. Please wait...');
    });
}

function runYearly(){
    PrimeUI.confirm({
      title: 'Run year-end process?',
      text: 'Run year-end forced leave process for <?= date('Y') ?>? This can only run once for this year and may deduct unused mandatory VL.',
      confirmButtonText: 'Run Process',
      confirmButtonColor: '#b42318'
    }).then(result => {
      if (!result.isConfirmed) return;
      postCron('btnYearly', 'cron/leave_run_yearly.php', 'Year-end process is running. Please wait...');
    });
}

function runYearlyEntitlements(){
    PrimeUI.confirmSave('Add yearly leave entitlements for <?= date('Y') ?>? This can only run once for this year and will reset annual entitlements such as SPL, Solo Parent, VAWC, and Wellness Leave.').then(result => {
      if (!result.isConfirmed) return;
      postCron('btnYearlyEntitlements', 'cron/leave_run_yearly_entitlements.php', 'Yearly entitlements are being added. Please wait...');
    });
}
</script>
        
      



        <?php require_once __DIR__ . '/partials/footer.php'; ?> 
      </div>
    </div>
    <!-- welcome modal start -->
     <?php require_once __DIR__ . '/partials/welcomemodal.php'; ?>
    <button class="welcome-modal-btn">
      <i class="fa fa-download"></i> Download
    </button>
    <!-- welcome modal end -->
    <!-- js -->
    <script src="vendors/scripts/core.js"></script>
    <script src="vendors/scripts/script.min.js"></script>
    <script src="vendors/scripts/process.js"></script>
    <script src="vendors/scripts/layout-settings.js"></script>
    <script src="src/plugins/apexcharts/apexcharts.min.js"></script>
    <script src="src/plugins/datatables/js/jquery.dataTables.min.js"></script>
    <script src="src/plugins/datatables/js/dataTables.bootstrap4.min.js"></script>
    <script src="src/plugins/datatables/js/dataTables.responsive.min.js"></script>
    <script src="src/plugins/datatables/js/responsive.bootstrap4.min.js"></script>
    <script src="vendors/scripts/dashboard3.js"></script>
    <!-- Google Tag Manager (noscript) -->
    <noscript
      ><iframe
        src="https://www.googletagmanager.com/ns.html?id=GTM-NXZMQSS"
        height="0"
        width="0"
        style="display: none; visibility: hidden"
      ></iframe
    ></noscript>
    <!-- End Google Tag Manager (noscript) -->
  </body>
</html>


