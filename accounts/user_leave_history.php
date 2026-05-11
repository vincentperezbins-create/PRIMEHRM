<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/leave_helpers.php';

$userModel = new User($pdo);
require_login();
require_role([1, 2, 3, 4, 5, 6, 7]);
require_once __DIR__ . '/partials/session.php';
require_once __DIR__ . '/partials/page_info.php';

$stmt = $pdo->prepare("
    SELECT la.*, lt.leave_code, lt.leave_name, u.employeeID
    FROM leave_applications la
    JOIN leave_types lt ON lt.leave_type_id = la.leave_type_id
    JOIN sdopang1_user u ON u.user_id = la.user_id
    WHERE la.user_id = ?
    ORDER BY la.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            <h2 class="mb-1">Leave History</h2>
            <p class="text-700 mb-0">Your submitted leave requests and review status.</p>
          </div>
          <a href="user_leave_apply.php" class="btn btn-primary">Apply Leave</a>
        </div>

        <?php page_info(
            'What this page does',
            'Review your submitted leave applications and their approval status.',
            [
                'Pending requests are waiting for admin action.',
                'Approved requests may deduct leave credits depending on the leave type.'
            ]
        ); ?>

        <div class="card">
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-striped table-bordered">
                <thead>
                  <tr>
                    <th>Employee ID</th>
                    <th>Leave</th>
                    <th>Date Range</th>
                    <th class="text-end">Days</th>
                    <th>Status</th>
                    <th>Reason</th>
                    <th>Remarks</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($applications as $application): ?>
                    <?php
                    $dateRange = date('M d, Y', strtotime($application['date_from'])) . ' - ' . date('M d, Y', strtotime($application['date_to']));
                    $scheduleJson = $application['leave_schedule'] ?? ($application['cto_schedule'] ?? null);
                    if (!empty($scheduleJson)) {
                        $schedule = json_decode((string) $scheduleJson, true);
                        if (is_array($schedule)) {
                            $dateRange = leave_cto_schedule_label($schedule);
                        }
                    }
                    ?>
                    <tr>
                      <td><?= htmlspecialchars($application['employeeID'] ?? '-') ?></td>
                      <td><?= htmlspecialchars(($application['leave_code'] ?? '') . ' - ' . $application['leave_name']) ?></td>
                      <td><?= htmlspecialchars($dateRange) ?></td>
                      <td class="text-end"><?= htmlspecialchars(number_format((float) $application['days'], 3)) ?></td>
                      <td><?= htmlspecialchars(ucfirst((string) $application['status'])) ?></td>
                      <td><?= htmlspecialchars($application['reason'] ?? '-') ?></td>
                      <td><?= htmlspecialchars($application['remarks'] ?? ($application['rejection_reason'] ?? '-')) ?></td>
                      <td>
                        <a
                          href="download_form6.php?application_id=<?= htmlspecialchars((string) $application['application_id'], ENT_QUOTES, 'UTF-8') ?>"
                          class="btn btn-sm btn-outline-success"
                        >
                          <i class="fa fa-download"></i> Form 6
                        </a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                  <?php if (!$applications): ?>
                    <tr><td colspan="8" class="text-center text-muted">No leave applications yet.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
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

