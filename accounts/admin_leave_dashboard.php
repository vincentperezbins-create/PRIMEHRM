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
  <style>
    .leave-dashboard-page {
      background: #f4f7fb;
    }

    .leave-dashboard-shell {
      color: #0f172a;
    }

    .leave-hero-card,
    .leave-info-card,
    .leave-stat-card,
    .leave-tools-card,
    .leave-tool-card {
      background: #ffffff;
      border: 1px solid #dbe5f1;
      border-radius: 18px;
      box-shadow: 0 14px 34px rgba(15, 23, 42, 0.06);
    }

    .leave-hero-card {
      margin-bottom: 18px;
      padding: 24px;
    }

    .leave-breadcrumb {
      display: flex;
      flex-wrap: wrap;
      gap: 7px;
      margin-bottom: 10px;
      color: #2563eb;
      font-size: 12px;
      font-weight: 800;
      text-transform: uppercase;
    }

    .leave-breadcrumb span:not(:last-child)::after {
      content: "/";
      margin-left: 7px;
      color: #94a3b8;
    }

    .leave-hero-card h2 {
      margin-bottom: 8px;
      color: #0f172a;
      font-size: 30px;
      line-height: 1.2;
    }

    .leave-hero-card p,
    .leave-section-subtitle,
    .leave-tool-card p,
    .leave-stat-helper {
      color: #64748b;
    }

    .leave-info-card {
      display: flex;
      gap: 16px;
      margin-bottom: 18px;
      padding: 20px;
      background: #e8f7fb;
    }

    .leave-info-icon,
    .leave-section-icon,
    .leave-stat-icon {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex: 0 0 auto;
      border-radius: 14px;
    }

    .leave-info-icon {
      width: 44px;
      height: 44px;
      color: #2563eb;
      background: #ffffff;
      font-size: 20px;
    }

    .leave-info-card h5 {
      margin-bottom: 6px;
      color: #0f172a;
      font-size: 17px;
    }

    .leave-info-card p {
      margin-bottom: 10px;
      color: #334155;
    }

    .leave-info-card ul {
      margin: 0;
      padding-left: 19px;
      color: #475569;
    }

    .leave-info-card li + li {
      margin-top: 5px;
    }

    .leave-stats-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 16px;
      margin-bottom: 22px;
    }

    .leave-stat-card {
      position: relative;
      min-height: 148px;
      padding: 18px;
      overflow: hidden;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .leave-stat-card::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 5px;
      background: var(--stat-color, #2563eb);
    }

    .leave-stat-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 18px 44px rgba(15, 23, 42, 0.11);
    }

    .leave-stat-top {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 18px;
    }

    .leave-stat-icon {
      width: 48px;
      height: 48px;
      color: var(--stat-color, #2563eb);
      background: var(--stat-soft, #eff6ff);
      font-size: 22px;
    }

    .leave-stat-label {
      margin: 0;
      color: #64748b;
      font-size: 12px;
      font-weight: 800;
      text-transform: uppercase;
    }

    .leave-stat-value {
      margin: 0;
      color: #0f172a;
      font-size: 34px;
      font-weight: 800;
      line-height: 1;
    }

    .leave-stat-helper {
      display: block;
      margin-top: 8px;
      font-size: 12px;
      font-weight: 700;
    }

    .leave-tools-card {
      margin-bottom: 22px;
      overflow: hidden;
    }

    .leave-tools-header {
      display: flex;
      gap: 14px;
      align-items: center;
      padding: 20px 22px;
      background: #ffffff;
      border-bottom: 1px solid #dbe5f1;
    }

    .leave-section-icon {
      width: 46px;
      height: 46px;
      color: #2563eb;
      background: #eff6ff;
      font-size: 20px;
    }

    .leave-tools-header h4 {
      margin-bottom: 4px;
      color: #0f172a;
      font-size: 19px;
    }

    .leave-section-subtitle {
      margin: 0;
      font-size: 13px;
    }

    .leave-tools-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 16px;
      padding: 18px;
      background: #f8fbff;
    }

    .leave-tool-card {
      display: flex;
      flex-direction: column;
      min-height: 260px;
      padding: 18px;
    }

    .leave-tool-card h5 {
      margin-bottom: 8px;
      color: #0f172a;
      font-size: 17px;
    }

    .leave-tool-card p {
      margin-bottom: 12px;
      font-size: 13px;
      line-height: 1.5;
    }

    .leave-tool-status {
      display: inline-flex;
      align-items: center;
      align-self: flex-start;
      gap: 6px;
      margin-bottom: 14px;
      padding: 6px 10px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 800;
    }

    .leave-status-completed {
      color: #166534;
      background: #dcfce7;
    }

    .leave-status-locked {
      color: #9a3412;
      background: #ffedd5;
    }

    .leave-status-open {
      color: #1d4ed8;
      background: #dbeafe;
    }

    .leave-tool-action {
      margin-top: auto;
    }

    .leave-tool-action .btn {
      width: 100%;
      min-height: 44px;
      border-radius: 999px;
      font-weight: 800;
    }

    .leave-tool-action .btn:disabled {
      color: #64748b;
      border-color: #cbd5e1;
      background: #e2e8f0;
      opacity: 1;
    }

    .leave-cron-status {
      margin: 0 18px 18px;
      border-radius: 14px;
    }

    .leave-swal-popup {
      border-radius: 20px !important;
    }

    .leave-swal-confirm,
    .leave-swal-cancel {
      min-width: 128px;
      border-radius: 999px !important;
      padding: 10px 18px !important;
      font-weight: 800 !important;
    }

    .leave-swal-confirm {
      color: #fff !important;
      border-color: #2563eb !important;
      background: #2563eb !important;
    }

    .leave-swal-cancel {
      color: #475569 !important;
      border-color: #cbd5e1 !important;
      background: #f8fafc !important;
    }

    @media (max-width: 991.98px) {
      .leave-stats-grid,
      .leave-tools-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }

    @media (max-width: 575.98px) {
      .leave-hero-card,
      .leave-info-card {
        padding: 18px;
        border-radius: 16px;
      }

      .leave-info-card,
      .leave-tools-header {
        align-items: flex-start;
        flex-direction: column;
      }

      .leave-stats-grid,
      .leave-tools-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
  <body class="leave-dashboard-page">
   <?php require_once __DIR__ . '/partials/preloader.php'; ?>

    <?php require_once __DIR__ . '/partials/navbar.php'; ?>
    
    <?php require_once __DIR__ . '/partials/rightsidebar.php'; ?>
    <?php require_once __DIR__ . '/partials/leftsidebar.php'; ?>
    <div class="mobile-menu-overlay"></div>

    <div class="main-container">
      <div class="xs-pd-20-10 pd-ltr-20 leave-dashboard-shell">
        <div class="leave-hero-card">
          <div class="leave-breadcrumb">
            <span>PRIMEHR</span>
            <span>Leave Management</span>
            <span>Leave Credits</span>
          </div>
          <h2>Leave Credits Dashboard</h2>
          <p class="mb-0">System-wide leave monitoring, balances, transactions, and credit processes.</p>
        </div>

        <div class="leave-info-card">
          <div class="leave-info-icon"><i class="bi bi-info-circle"></i></div>
          <div>
            <h5>What this page does</h5>
            <p>This dashboard summarizes leave activity and runs controlled leave-credit processes.</p>
            <ul>
              <li>Monthly accrual adds VL and SL credits for eligible non-teaching employees.</li>
              <li>Yearly entitlements reset annual leave balances such as SPL, Solo Parent, VAWC, and Wellness Leave.</li>
              <li>Year-end processing applies forced leave rules and can deduct unused mandatory VL.</li>
            </ul>
          </div>
        </div>

        <div class="leave-stats-grid">
          <?php
          $leaveCards = [
              ['Total Employees', $totalEmployees, 'bi bi-people', '#2563eb', '#eff6ff', 'All users registered in PRIMEHR.'],
              ['Pending Applications', $pendingApplications, 'bi bi-hourglass-split', '#f97316', '#fff7ed', 'Applications waiting for review.'],
              ['Approved This Month', $approvedThisMonth, 'bi bi-calendar-check', '#16a34a', '#ecfdf3', 'Approved within the current month.'],
              ['Rejected This Month', $rejectedThisMonth, 'bi bi-calendar-x', '#dc2626', '#fef2f2', 'Rejected within the current month.'],
              ['Low Balance Employees', $lowBalanceEmployees, 'bi bi-exclamation-triangle', '#dc2626', '#fef2f2', 'Employees below 3 leave credits.'],
              ['Transactions', $transactions, 'bi bi-journal-text', '#2563eb', '#eff6ff', 'Total leave transaction records.'],
          ];
          foreach ($leaveCards as $card):
          ?>
            <div class="leave-stat-card" style="--stat-color: <?= htmlspecialchars($card[3], ENT_QUOTES, 'UTF-8') ?>; --stat-soft: <?= htmlspecialchars($card[4], ENT_QUOTES, 'UTF-8') ?>;">
              <div class="leave-stat-top">
                <p class="leave-stat-label"><?= htmlspecialchars($card[0]) ?></p>
                <div class="leave-stat-icon">
                  <i class="<?= htmlspecialchars($card[2], ENT_QUOTES, 'UTF-8') ?>"></i>
                </div>
              </div>
              <p class="leave-stat-value"><?= htmlspecialchars((string) $card[1]) ?></p>
              <span class="leave-stat-helper"><?= htmlspecialchars($card[5]) ?></span>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="leave-tools-card">
          <div class="leave-tools-header">
            <div class="leave-section-icon"><i class="bi bi-tools"></i></div>
            <div>
              <h4>Leave System Tools</h4>
              <p class="leave-section-subtitle">Run controlled credit processes and review current run status.</p>
            </div>
          </div>

          <div class="leave-tools-grid">
            <div class="leave-tool-card">
              <span class="leave-tool-status <?= $monthlyRunAt ? 'leave-status-locked' : 'leave-status-open' ?>">
                <i class="bi <?= $monthlyRunAt ? 'bi-lock-fill' : 'bi-play-circle' ?>"></i>
                <?= htmlspecialchars($monthlyRunAt ? 'Locked' : 'Not Run') ?>
              </span>
              <h5>Monthly Accrual</h5>
              <p>Monthly accrual: <?= htmlspecialchars($monthlyRunAt ? "completed for " . date('Y-m') : "not run for " . date('Y-m')) ?></p>
              <?php if ($monthlyRunAt): ?>
                <p>This process is locked because it already ran for <?= htmlspecialchars(date('Y-m')) ?>.</p>
              <?php endif; ?>
              <div class="leave-tool-action">
                <button id="btnMonthly" class="btn btn-primary" onclick="runMonthly()" <?= $monthlyRunAt ? 'disabled' : '' ?>>
                    Run Monthly Accrual
                </button>
              </div>
            </div>

            <div class="leave-tool-card">
              <span class="leave-tool-status <?= $yearlyEntitlementRunAt ? 'leave-status-completed' : 'leave-status-open' ?>">
                <i class="bi <?= $yearlyEntitlementRunAt ? 'bi-check-circle-fill' : 'bi-play-circle' ?>"></i>
                <?= htmlspecialchars($yearlyEntitlementRunAt ? 'Completed' : 'Not Run') ?>
              </span>
              <h5>Yearly Leave Entitlements</h5>
              <p>Yearly leave entitlements: <?= htmlspecialchars($yearlyEntitlementRunAt ? "completed for " . date('Y') : "not run for " . date('Y')) ?></p>
              <?php if ($yearlyEntitlementRunAt): ?>
                <p>This process is locked because it already ran for <?= htmlspecialchars(date('Y')) ?>.</p>
              <?php endif; ?>
              <div class="leave-tool-action">
                <button id="btnYearlyEntitlements" class="btn btn-success" onclick="runYearlyEntitlements()" <?= $yearlyEntitlementRunAt ? 'disabled' : '' ?>>
                    Add Yearly Leave Entitlements
                </button>
              </div>
            </div>

            <div class="leave-tool-card">
              <span class="leave-tool-status <?= $yearlyRunAt ? 'leave-status-locked' : 'leave-status-open' ?>">
                <i class="bi <?= $yearlyRunAt ? 'bi-lock-fill' : 'bi-play-circle' ?>"></i>
                <?= htmlspecialchars($yearlyRunAt ? 'Locked' : 'Not Run') ?>
              </span>
              <h5>Year-End Process</h5>
              <p>Year-end process: <?= htmlspecialchars($yearlyRunAt ? "completed for " . date('Y') : "not run for " . date('Y')) ?></p>
              <?php if ($yearlyRunAt): ?>
                <p>This process is locked because it already ran for <?= htmlspecialchars(date('Y')) ?>.</p>
              <?php endif; ?>
              <div class="leave-tool-action">
                <button id="btnYearly" class="btn btn-danger" onclick="runYearly()" <?= $yearlyRunAt ? 'disabled' : '' ?>>
                    Run Year-End Process
                </button>
              </div>
            </div>
          </div>

          <div id="cronStatus" class="alert alert-secondary leave-cron-status d-none"></div>
        </div>

<script>
const leaveSwalBase = {
    customClass: {
      popup: 'leave-swal-popup',
      confirmButton: 'btn btn-primary leave-swal-confirm',
      cancelButton: 'btn btn-outline-secondary leave-swal-cancel'
    },
    buttonsStyling: false
};

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
        if (window.Swal) {
          await Swal.fire({
            ...leaveSwalBase,
            title: 'Process Completed',
            text: msg || 'The leave process completed successfully.',
            icon: 'success',
            confirmButtonText: 'OK'
          });
        }
        setTimeout(() => location.reload(), 1200);
      } else if (window.Swal) {
        Swal.fire({
          ...leaveSwalBase,
          title: 'Process Failed',
          text: msg || 'The leave process failed. Please check the server response.',
          icon: 'error',
          confirmButtonText: 'OK'
        });
      }
    })
    .catch(() => {
      const message = 'Process failed. Please check the connection or server logs.';
      setCronResult(buttonId, message, false);
      if (window.Swal) {
        Swal.fire({
          ...leaveSwalBase,
          title: 'Process Failed',
          text: message,
          icon: 'error',
          confirmButtonText: 'OK'
        });
      }
    });
}

function runMonthly(){
    Swal.fire({
      ...leaveSwalBase,
      title: 'Run Monthly Accrual?',
      text: 'This will add VL and SL credits for eligible non-teaching employees.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, Run Accrual',
      cancelButtonText: 'Cancel'
    }).then(result => {
      if (!result.isConfirmed) return;
      postCron('btnMonthly', 'cron/leave_run_monthly.php', 'Monthly accrual is running. Please wait...');
    });
}

function runYearly(){
    Swal.fire({
      ...leaveSwalBase,
      title: 'Run Year-End Process?',
      text: 'This may force leave rules and deduct unused mandatory VL.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, Run Year-End',
      cancelButtonText: 'Cancel'
    }).then(result => {
      if (!result.isConfirmed) return;
      postCron('btnYearly', 'cron/leave_run_yearly.php', 'Year-end process is running. Please wait...');
    });
}

function runYearlyEntitlements(){
    Swal.fire({
      ...leaveSwalBase,
      title: 'Add Yearly Leave Entitlements?',
      text: 'This will add annual leave balances such as SPL, Solo Parent, VAWC, and Wellness Leave.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Yes, Add Entitlements',
      cancelButtonText: 'Cancel'
    }).then(result => {
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


