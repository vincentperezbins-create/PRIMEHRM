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

$totalAvailableCredits = 0.0;
$highestBalance = 0.0;
$leaveTypeCount = count($balances);
foreach ($balances as $balance) {
    $amount = (float) ($balance['balance'] ?? 0);
    $totalAvailableCredits += $amount;
    if ($amount > $highestBalance) {
        $highestBalance = $amount;
    }
}
?>
<!DOCTYPE html>
<html>
 <?php require_once __DIR__ . '/partials/head.php'; ?>
  <style>
    .leave-balance-page {
      background: #f4f7fb;
    }

    .leave-balance-shell {
      color: #0f172a;
    }

    .leave-balance-hero,
    .leave-balance-info,
    .leave-balance-summary-card,
    .leave-balance-card,
    .leave-balance-filter {
      background: #ffffff;
      border: 1px solid #dbe5f1;
      border-radius: 18px;
      box-shadow: 0 14px 34px rgba(15, 23, 42, 0.06);
    }

    .leave-balance-hero {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 18px;
      margin-bottom: 18px;
      padding: 24px;
    }

    .leave-balance-breadcrumb {
      display: flex;
      flex-wrap: wrap;
      gap: 7px;
      margin-bottom: 10px;
      color: #2563eb;
      font-size: 12px;
      font-weight: 800;
      text-transform: uppercase;
    }

    .leave-balance-breadcrumb span:not(:last-child)::after {
      content: "/";
      margin-left: 7px;
      color: #94a3b8;
    }

    .leave-balance-hero h2 {
      margin-bottom: 8px;
      color: #0f172a;
      font-size: 30px;
      line-height: 1.2;
    }

    .leave-balance-hero p,
    .leave-balance-muted {
      color: #64748b;
    }

    .leave-apply-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      min-height: 44px;
      padding: 10px 18px;
      border-radius: 999px;
      font-weight: 800;
      white-space: nowrap;
    }

    .leave-balance-info {
      display: flex;
      gap: 16px;
      margin-bottom: 18px;
      padding: 20px;
      background: #e8f7fb;
    }

    .leave-balance-info-icon,
    .leave-balance-summary-icon {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex: 0 0 auto;
      border-radius: 14px;
    }

    .leave-balance-info-icon {
      width: 44px;
      height: 44px;
      color: #2563eb;
      background: #ffffff;
      font-size: 20px;
    }

    .leave-balance-info h5 {
      margin-bottom: 6px;
      color: #0f172a;
      font-size: 17px;
    }

    .leave-balance-info p {
      margin-bottom: 10px;
      color: #334155;
    }

    .leave-balance-info ul {
      margin: 0;
      padding-left: 19px;
      color: #475569;
    }

    .leave-balance-info li + li {
      margin-top: 5px;
    }

    .leave-balance-summary-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 14px;
      margin-bottom: 18px;
    }

    .leave-balance-summary-card {
      display: flex;
      gap: 12px;
      align-items: center;
      min-height: 104px;
      padding: 16px;
    }

    .leave-balance-summary-icon {
      width: 46px;
      height: 46px;
      color: #2563eb;
      background: #eff6ff;
      font-size: 20px;
    }

    .leave-balance-summary-card span {
      display: block;
      color: #64748b;
      font-size: 12px;
      font-weight: 800;
      text-transform: uppercase;
    }

    .leave-balance-summary-card strong {
      display: block;
      margin-top: 4px;
      color: #0f172a;
      font-size: 22px;
      line-height: 1.15;
    }

    .leave-balance-filter {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 18px;
      padding: 16px;
      background: #f8fbff;
    }

    .leave-balance-search {
      max-width: 360px;
      width: 100%;
    }

    .leave-balance-search .form-control {
      height: 44px;
      border-color: #dbe5f1;
      border-radius: 999px;
      padding-left: 18px;
    }

    .leave-balance-filter-buttons {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }

    .leave-balance-filter-buttons .btn {
      border-radius: 999px;
      font-weight: 800;
    }

    .leave-balance-filter-buttons .active {
      color: #fff;
      border-color: #2563eb;
      background: #2563eb;
    }

    .leave-balance-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 16px;
    }

    .leave-balance-card {
      position: relative;
      min-height: 184px;
      padding: 18px;
      overflow: hidden;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .leave-balance-card::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 5px;
      background: var(--balance-color, #2563eb);
    }

    .leave-balance-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 18px 44px rgba(15, 23, 42, 0.11);
    }

    .leave-balance-card.is-zero {
      background: #f8fafc;
      box-shadow: none;
    }

    .leave-balance-card.is-featured:not(.is-zero) {
      border-color: rgba(37, 99, 235, 0.35);
      box-shadow: 0 16px 38px rgba(37, 99, 235, 0.1);
    }

    .leave-code-badge {
      display: inline-flex;
      align-items: center;
      margin-bottom: 14px;
      padding: 6px 10px;
      color: var(--balance-color, #2563eb);
      background: var(--balance-soft, #eff6ff);
      border-radius: 999px;
      font-size: 12px;
      font-weight: 800;
      letter-spacing: 0.02em;
    }

    .leave-balance-card h5 {
      min-height: 42px;
      margin-bottom: 18px;
      color: #0f172a;
      font-size: 17px;
      line-height: 1.25;
    }

    .leave-balance-value {
      margin: 0;
      color: var(--balance-color, #2563eb);
      font-size: 34px;
      font-weight: 800;
      line-height: 1;
    }

    .leave-balance-label {
      display: block;
      margin-top: 8px;
      color: #64748b;
      font-size: 12px;
      font-weight: 800;
      text-transform: uppercase;
    }

    .leave-empty-card {
      grid-column: 1 / -1;
      padding: 28px;
      text-align: center;
    }

    .leave-balance-hidden {
      display: none !important;
    }

    .leave-swal-popup {
      border-radius: 20px !important;
    }

    .leave-swal-confirm,
    .leave-swal-cancel {
      min-width: 118px;
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
      .leave-balance-hero,
      .leave-balance-filter {
        align-items: stretch;
        flex-direction: column;
      }

      .leave-balance-summary-grid,
      .leave-balance-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }

      .leave-balance-search {
        max-width: none;
      }
    }

    @media (max-width: 575.98px) {
      .leave-balance-hero,
      .leave-balance-info,
      .leave-balance-filter {
        padding: 18px;
        border-radius: 16px;
      }

      .leave-balance-info {
        align-items: flex-start;
        flex-direction: column;
      }

      .leave-balance-summary-grid,
      .leave-balance-grid {
        grid-template-columns: 1fr;
      }

      .leave-apply-btn {
        width: 100%;
      }
    }
  </style>
  <body class="leave-balance-page">
   <?php require_once __DIR__ . '/partials/preloader.php'; ?>
    <?php require_once __DIR__ . '/partials/navbar.php'; ?>
    <?php require_once __DIR__ . '/partials/rightsidebar.php'; ?>
    <?php require_once __DIR__ . '/partials/leftsidebar.php'; ?>
    <div class="mobile-menu-overlay"></div>

    <div class="main-container">
      <div class="xs-pd-20-10 pd-ltr-20 leave-balance-shell">
        <div class="leave-balance-hero">
          <div>
            <div class="leave-balance-breadcrumb">
              <span>PRIMEHR</span>
              <span>My Submission</span>
              <span>My Leave Credits</span>
            </div>
            <h2>My Leave Balance</h2>
            <p class="mb-0">Available leave credits by type.</p>
          </div>
          <a href="user_leave_apply.php" class="btn btn-primary leave-apply-btn" id="applyLeaveButton"><i class="bi bi-send"></i> Apply Leave</a>
        </div>

        <div class="leave-balance-info">
          <div class="leave-balance-info-icon"><i class="bi bi-info-circle"></i></div>
          <div>
            <h5>What this page does</h5>
            <p>View your current leave balances by leave type.</p>
            <ul>
              <li>Balances update after approved applications, accruals, yearly entitlements, and manual adjustments.</li>
              <li>Some leave types are fixed entitlements and may reset yearly instead of accumulating.</li>
            </ul>
          </div>
        </div>

        <div class="leave-balance-summary-grid">
          <div class="leave-balance-summary-card">
            <div class="leave-balance-summary-icon"><i class="bi bi-wallet2"></i></div>
            <div>
              <span>Total Available Credits</span>
              <strong><?= htmlspecialchars(number_format($totalAvailableCredits, 3)) ?></strong>
            </div>
          </div>
          <div class="leave-balance-summary-card">
            <div class="leave-balance-summary-icon"><i class="bi bi-arrow-up-circle"></i></div>
            <div>
              <span>Highest Balance</span>
              <strong><?= htmlspecialchars(number_format($highestBalance, 3)) ?></strong>
            </div>
          </div>
          <div class="leave-balance-summary-card">
            <div class="leave-balance-summary-icon"><i class="bi bi-arrow-down-circle"></i></div>
            <div>
              <span>Used / Deducted Credits</span>
              <strong>N/A</strong>
            </div>
          </div>
          <div class="leave-balance-summary-card">
            <div class="leave-balance-summary-icon"><i class="bi bi-grid"></i></div>
            <div>
              <span>Number of Leave Types</span>
              <strong><?= htmlspecialchars((string) $leaveTypeCount) ?></strong>
            </div>
          </div>
        </div>

        <div class="leave-balance-filter">
          <div class="leave-balance-search">
            <input type="search" class="form-control" id="leaveBalanceSearch" placeholder="Search leave type">
          </div>
          <div class="leave-balance-filter-buttons" role="group" aria-label="Leave balance filters">
            <button type="button" class="btn btn-outline-primary active" data-balance-filter="all">All</button>
            <button type="button" class="btn btn-outline-primary" data-balance-filter="with">With Balance</button>
            <button type="button" class="btn btn-outline-primary" data-balance-filter="zero">Zero Balance</button>
          </div>
        </div>

        <div class="leave-balance-grid" id="leaveBalanceGrid">
          <?php foreach ($balances as $balance): ?>
            <?php
              $leaveCode = strtoupper((string) ($balance['leave_code'] ?? ''));
              $leaveAmount = (float) ($balance['balance'] ?? 0);
              $isZero = $leaveAmount <= 0;
              $isLow = $leaveAmount > 0 && $leaveAmount < 3;
              $isFeatured = in_array($leaveCode, ['VL', 'SL', 'SPL', 'SOLO', 'VAWC', 'WL', 'CTO'], true) && $leaveAmount > 0;
              $balanceColor = $isZero ? '#94a3b8' : ($isLow ? '#f97316' : (in_array($leaveCode, ['VL', 'SL', 'CTO'], true) ? '#2563eb' : '#16a34a'));
              $balanceSoft = $isZero ? '#f1f5f9' : ($isLow ? '#fff7ed' : (in_array($leaveCode, ['VL', 'SL', 'CTO'], true) ? '#eff6ff' : '#ecfdf3'));
            ?>
            <div
              class="leave-balance-card <?= $isZero ? 'is-zero' : '' ?> <?= $isFeatured ? 'is-featured' : '' ?>"
              style="--balance-color: <?= htmlspecialchars($balanceColor, ENT_QUOTES, 'UTF-8') ?>; --balance-soft: <?= htmlspecialchars($balanceSoft, ENT_QUOTES, 'UTF-8') ?>;"
              data-code="<?= htmlspecialchars($leaveCode, ENT_QUOTES, 'UTF-8') ?>"
              data-name="<?= htmlspecialchars((string) $balance['leave_name'], ENT_QUOTES, 'UTF-8') ?>"
              data-balance="<?= htmlspecialchars((string) $leaveAmount, ENT_QUOTES, 'UTF-8') ?>"
            >
              <span class="leave-code-badge"><?= htmlspecialchars($balance['leave_code']) ?></span>
              <h5><?= htmlspecialchars($balance['leave_name']) ?></h5>
              <p class="leave-balance-value"><?= htmlspecialchars(number_format($leaveAmount, 3)) ?></p>
              <span class="leave-balance-label">Available Credits</span>
            </div>
          <?php endforeach; ?>
          <?php if (!$balances): ?>
            <div class="leave-balance-card leave-empty-card leave-balance-muted">No leave balances available.</div>
          <?php endif; ?>
          <div class="leave-balance-card leave-empty-card leave-balance-muted leave-balance-hidden" id="leaveBalanceNoResults">No matching leave balances found.</div>
        </div>

        <?php require_once __DIR__ . '/partials/footer.php'; ?>
      </div>
    </div>

    <?php require_once __DIR__ . '/partials/welcomemodal.php'; ?>
    <script src="vendors/scripts/core.js"></script>
    <script src="vendors/scripts/script.min.js"></script>
    <script src="vendors/scripts/process.js"></script>
    <script src="vendors/scripts/layout-settings.js"></script>
    <script>
      (function() {
        const applyButton = document.getElementById('applyLeaveButton');
        const searchInput = document.getElementById('leaveBalanceSearch');
        const filterButtons = document.querySelectorAll('[data-balance-filter]');
        const cards = Array.from(document.querySelectorAll('.leave-balance-card[data-code]'));
        const noResults = document.getElementById('leaveBalanceNoResults');
        let activeFilter = 'all';

        const swalBase = {
          customClass: {
            popup: 'leave-swal-popup',
            confirmButton: 'btn btn-primary leave-swal-confirm',
            cancelButton: 'btn btn-outline-secondary leave-swal-cancel'
          },
          buttonsStyling: false
        };

        if (applyButton && window.Swal) {
          applyButton.addEventListener('click', function(event) {
            event.preventDefault();
            const href = applyButton.getAttribute('href');

            Swal.fire({
              ...swalBase,
              title: 'Apply for Leave?',
              text: 'You will be redirected to the leave application form.',
              icon: 'question',
              showCancelButton: true,
              confirmButtonText: 'Continue',
              cancelButtonText: 'Cancel'
            }).then(function(result) {
              if (result.isConfirmed && href) {
                window.location.href = href;
              }
            });
          });
        }

        function applyLeaveFilters() {
          const query = (searchInput ? searchInput.value : '').trim().toLowerCase();
          let visibleCount = 0;

          cards.forEach(function(card) {
            const balance = parseFloat(card.dataset.balance || '0');
            const text = ((card.dataset.code || '') + ' ' + (card.dataset.name || '')).toLowerCase();
            const matchesSearch = !query || text.includes(query);
            const matchesFilter =
              activeFilter === 'all' ||
              (activeFilter === 'with' && balance > 0) ||
              (activeFilter === 'zero' && balance <= 0);
            const show = matchesSearch && matchesFilter;

            card.classList.toggle('leave-balance-hidden', !show);
            if (show) {
              visibleCount++;
            }
          });

          if (noResults) {
            noResults.classList.toggle('leave-balance-hidden', visibleCount !== 0);
          }
        }

        if (searchInput) {
          searchInput.addEventListener('input', applyLeaveFilters);
        }

        filterButtons.forEach(function(button) {
          button.addEventListener('click', function() {
            filterButtons.forEach(function(item) {
              item.classList.remove('active');
            });
            button.classList.add('active');
            activeFilter = button.dataset.balanceFilter || 'all';
            applyLeaveFilters();
          });
        });
      })();
    </script>
  </body>
</html>

