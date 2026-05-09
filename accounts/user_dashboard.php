<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/leave_helpers.php';
require_once __DIR__ . '/partials/page_info.php';

$userModel = new User($pdo);
require_login();
require_role([4]);
$currentUser = $userModel->getUserById($_SESSION['user_id']);
$userId = $_SESSION['user_id'];
$fullName = trim(($currentUser['first_name'] ?? '') . ' ' . ($currentUser['last_name'] ?? ''));

$positionStmt = $pdo->prepare("
    SELECT position_title, position_category
    FROM sdopang1_position
    WHERE position_id = ?
    LIMIT 1
");
$positionStmt->execute([$currentUser['position_id'] ?? null]);
$position = $positionStmt->fetch(PDO::FETCH_ASSOC) ?: [];

$progress = $userModel->get201Progress($userId);

$countStatusStmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM sdopang1_documents
    WHERE user_id = ? AND status = ?
");
$countStatus = function ($status) use ($countStatusStmt, $userId) {
    $countStatusStmt->execute([$userId, $status]);
    return (int) $countStatusStmt->fetchColumn();
};

$pending = $countStatus('Pending');
$approved = $countStatus('Approved');
$returned = $countStatus('Returned');

$recentStmt = $pdo->prepare("
    SELECT d.document_id, d.status, d.remarks, d.uploaded_at, t.doc_name
    FROM sdopang1_documents d
    JOIN sdopang1_document_types t ON d.doc_type_id = t.doc_type_id
    WHERE d.user_id = ?
    ORDER BY d.uploaded_at DESC
    LIMIT 8
");
$recentStmt->execute([$userId]);
$recentDocuments = $recentStmt->fetchAll();

$leaveCountStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM leave_applications
    WHERE user_id = ? AND status = ?
");
$countLeave = function ($status) use ($leaveCountStmt, $userId) {
    $leaveCountStmt->execute([$userId, $status]);
    return (int) $leaveCountStmt->fetchColumn();
};

$leavePending = $countLeave('pending');
$leaveApproved = $countLeave('approved');
$leaveRejected = $countLeave('rejected');

$personnelType = leave_get_user_personnel_type($pdo, (int) $userId);
$leaveTypes = $pdo->query("
    SELECT *
    FROM leave_types
    WHERE is_active = 1
    ORDER BY leave_code
")->fetchAll(PDO::FETCH_ASSOC);
$visibleLeaveTypes = array_values(array_filter($leaveTypes, function ($type) use ($personnelType) {
    if (empty($type['personnel_type'])) {
        return true;
    }

    return in_array(strtolower(trim((string) $type['personnel_type'])), ['both', $personnelType], true);
}));
$leaveBalances = array_slice(array_map(function ($type) use ($pdo, $userId) {
    return [
        'code' => $type['leave_code'],
        'name' => $type['leave_name'],
        'balance' => leave_get_balance($pdo, (int) $userId, (int) $type['leave_type_id']),
    ];
}, $visibleLeaveTypes), 0, 6);

$recentLeaveStmt = $pdo->prepare("
    SELECT la.date_from, la.date_to, la.days, la.status, la.reason, lt.leave_name
    FROM leave_applications la
    JOIN leave_types lt ON lt.leave_type_id = la.leave_type_id
    WHERE la.user_id = ?
    ORDER BY la.created_at DESC
    LIMIT 6
");
$recentLeaveStmt->execute([$userId]);
$recentLeaves = $recentLeaveStmt->fetchAll(PDO::FETCH_ASSOC);
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
              <h2 class="mb-1">My Dashboard</h2>
          	<p class="text-700 mb-0">Your 201 file status, leave balances, and leave application activity.</p>
            </div>
            <div class="dashboard-actions">
              <a href="user_leave_apply.php" class="btn btn-primary">Apply Leave</a>
              <a href="user_leave_balance.php" class="btn btn-outline-primary">View Leave Balance</a>
            </div>
          </div>
        <?php page_info(
            'Regular user leave system',
            'Use this dashboard to check leave balances, submit leave requests, and monitor leave application status.',
            [
                'Credit-based leave types are checked against your available balance before submission.',
                'Approved requests appear in your leave history and update the ledger.'
            ]
        ); ?>

        <div class="row pb-10">
          <div class="col-12 mb-20">
            <div class="card-box pd-20 h-100">
              <h5 class="mb-3">Profile</h5>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <p class="text-700 mb-1">Full Name</p>
                  <h6 class="mb-0"><?= htmlspecialchars($fullName !== '' ? $fullName : '-') ?></h6>
                </div>
                <div class="col-md-6 mb-3">
                  <p class="text-700 mb-1">Email</p>
                  <h6 class="mb-0"><?= htmlspecialchars($currentUser['email'] ?: '-') ?></h6>
                </div>
                <div class="col-md-6 mb-3">
                  <p class="text-700 mb-1">Employee ID</p>
                  <h6 class="mb-0"><?= htmlspecialchars($currentUser['employeeID'] ?: 'N/A') ?></h6>
                </div>
                <div class="col-md-6 mb-3">
                  <p class="text-700 mb-1">Position</p>
                  <h6 class="mb-0"><?= htmlspecialchars($position['position_title'] ?? 'Not set') ?></h6>
                </div>
                <div class="col-md-6 mb-3">
                  <p class="text-700 mb-1">Position Category</p>
                  <h6 class="mb-0"><?= htmlspecialchars($position['position_category'] ?? '-') ?></h6>
                </div>
                <div class="col-md-6 mb-3">
                  <p class="text-700 mb-1">Role</p>
                  <h6 class="mb-0">Employee</h6>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="row g-3 mb-4">
          <div class="col-12 col-lg-6">
            <div class="card h-100">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h5 class="mb-0">201 Completion</h5>
                  <strong><?= htmlspecialchars((string) $progress['percent']) ?>%</strong>
                </div>
                <div class="progress mb-2" style="height: 18px;">
                  <div class="progress-bar bg-success" style="width: <?= htmlspecialchars((string) $progress['percent']) ?>%;">
                    <?= htmlspecialchars((string) $progress['percent']) ?>%
                  </div>
                </div>
                <p class="mb-0 text-700"><?= htmlspecialchars((string) $progress['uploaded']) ?> / <?= htmlspecialchars((string) $progress['total']) ?> documents uploaded</p>
                <a href="user_201_tables.php" class="font-12 font-weight-bold">View here</a>
              </div>
            </div>
          </div>

          <?php
          $cards = [
              ['Pending', $pending, 'warning', 'user_201_tables.php', 'bi bi-hourglass-split'],
              ['Approved', $approved, 'success', 'user_201_tables.php', 'bi bi-check2-circle'],
              ['Returned', $returned, 'danger', 'user_201_tables.php', 'bi bi-arrow-counterclockwise'],
          ];
          foreach ($cards as $card):
          ?>
            <div class="col-12 col-sm-4 col-lg-2">
              <div class="prime-stat-card stat-<?= htmlspecialchars($card[2]) ?>">
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <p class="stat-label"><?= htmlspecialchars($card[0]) ?></p>
                    <p class="stat-value"><?= htmlspecialchars((string) $card[1]) ?></p>
                  </div>
                  <div class="stat-icon">
                    <i class="<?= htmlspecialchars($card[4], ENT_QUOTES, 'UTF-8') ?>"></i>
                  </div>
                </div>
                <a href="<?= htmlspecialchars($card[3], ENT_QUOTES, 'UTF-8') ?>" class="stat-link">View here</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="row g-3 mb-4">
          <?php
          $leaveCards = [
              ['Pending Leave', $leavePending, 'warning', 'user_leave_history.php', 'bi bi-calendar-event'],
              ['Approved Leave', $leaveApproved, 'success', 'user_leave_history.php', 'bi bi-calendar-check'],
              ['Rejected Leave', $leaveRejected, 'danger', 'user_leave_history.php', 'bi bi-calendar-x'],
          ];
          foreach ($leaveCards as $card):
          ?>
            <div class="col-12 col-sm-4">
              <div class="prime-stat-card stat-<?= htmlspecialchars($card[2]) ?>">
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <p class="stat-label"><?= htmlspecialchars($card[0]) ?></p>
                    <p class="stat-value"><?= htmlspecialchars((string) $card[1]) ?></p>
                  </div>
                  <div class="stat-icon">
                    <i class="<?= htmlspecialchars($card[4], ENT_QUOTES, 'UTF-8') ?>"></i>
                  </div>
                </div>
                <a href="<?= htmlspecialchars($card[3], ENT_QUOTES, 'UTF-8') ?>" class="stat-link">View here</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="row pb-10">
          <div class="col-md-5 mb-20">
            <div class="card h-100">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h5 class="mb-0">Leave Balances</h5>
                  <a href="user_leave_balance.php" class="btn btn-sm btn-outline-primary">All Balances</a>
                </div>
                <div class="row">
                  <?php foreach ($leaveBalances as $balance): ?>
                    <div class="col-12 col-sm-6 mb-3">
                      <p class="text-700 mb-1"><?= htmlspecialchars($balance['name']) ?></p>
                      <h4 class="mb-0"><?= htmlspecialchars(number_format((float) $balance['balance'], 3)) ?></h4>
                    </div>
                  <?php endforeach; ?>
                  <?php if (!$leaveBalances): ?>
                    <div class="col-12 text-muted">No leave balances available.</div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-7 mb-20">
            <div class="card h-100">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h5 class="mb-0">Recent Leave Applications</h5>
                  <a href="user_leave_history.php" class="btn btn-sm btn-outline-primary">View History</a>
                </div>
                <div class="table-responsive">
                  <table class="table fs--1 mb-0">
                    <thead>
                      <tr>
                        <th>Leave</th>
                        <th>Date Range</th>
                        <th class="text-end">Days</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($recentLeaves as $leave): ?>
                        <tr>
                          <td><?= htmlspecialchars($leave['leave_name']) ?></td>
                          <td><?= htmlspecialchars(date('M d, Y', strtotime($leave['date_from'])) . ' - ' . date('M d, Y', strtotime($leave['date_to']))) ?></td>
                          <td class="text-end"><?= htmlspecialchars(number_format((float) $leave['days'], 3)) ?></td>
                          <td><?= htmlspecialchars(ucfirst((string) $leave['status'])) ?></td>
                        </tr>
                      <?php endforeach; ?>
                      <?php if (!$recentLeaves): ?>
                        <tr><td colspan="4" class="text-center text-700">No leave applications yet.</td></tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>



        <div class="row pb-10">
          <div class="col-md-12 mb-20">
            
            <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h5 class="mb-0">Recent Documents</h5>
              <a href="user_201_tables.php" class="btn btn-sm btn-primary">Manage 201 Files</a>
            </div>
            <div class="table-responsive">
              <table class="table fs--1 mb-0">
                <thead>
                  <tr>
                    <th>Document</th>
                    <th>Status</th>
                    <th>Remarks</th>
                    <th>Uploaded</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($recentDocuments as $document): ?>
                    <tr>
                      <td><?= htmlspecialchars($document['doc_name']) ?></td>
                      <td><?= htmlspecialchars($document['status']) ?></td>
                      <td><?= htmlspecialchars($document['remarks'] ?: '-') ?></td>
                      <td><?= htmlspecialchars((string) $document['uploaded_at']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                  <?php if (!$recentDocuments): ?>
                    <tr><td colspan="4" class="text-center text-700">No uploads yet.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
          </div>
        </div>

        
      



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


