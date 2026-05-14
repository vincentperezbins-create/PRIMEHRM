<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/partials/page_info.php';

$userModel = new User($pdo);
require_login();
require_role([3]);
require_once __DIR__ . '/partials/session.php';

$schoolId = $currentUser['school_id'] ?? null;

$schoolStmt = $pdo->prepare("
    SELECT schoolID, schoolname, district
    FROM sdopang1schoollist
    WHERE schoolID = ?
    LIMIT 1
");
$schoolStmt->execute([$schoolId]);
$school = $schoolStmt->fetch(PDO::FETCH_ASSOC) ?: [];

$employeeCountStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM sdopang1_user
    WHERE school_id = ?
      AND role_id = 4
");
$employeeCountStmt->execute([$schoolId]);
$totalEmployees = (int) $employeeCountStmt->fetchColumn();

$unitHeadCountStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM sdopang1_user
    WHERE school_id = ?
      AND role_id = 3
");
$unitHeadCountStmt->execute([$schoolId]);
$totalUnitHeads = (int) $unitHeadCountStmt->fetchColumn();

$documentCountStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM sdopang1_documents d
    JOIN sdopang1_user u ON u.user_id = d.user_id
    WHERE u.school_id = ?
      AND u.role_id = 4
      AND u.user_id <> ?
");
$documentCountStmt->execute([$schoolId, $_SESSION['user_id']]);
$totalDocuments = (int) $documentCountStmt->fetchColumn();

$documentStatusStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM sdopang1_documents d
    JOIN sdopang1_user u ON u.user_id = d.user_id
    WHERE u.school_id = ?
      AND u.role_id = 4
      AND u.user_id <> ?
      AND d.status = ?
");
$countDocumentStatus = function (string $status) use ($documentStatusStmt, $schoolId): int {
    $documentStatusStmt->execute([$schoolId, $_SESSION['user_id'], $status]);
    return (int) $documentStatusStmt->fetchColumn();
};

$pendingDocuments = $countDocumentStatus('Pending');
$approvedDocuments = $countDocumentStatus('Approved');
$returnedDocuments = $countDocumentStatus('Returned');

$leaveStatusStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM leave_applications la
    JOIN sdopang1_user u ON u.user_id = la.user_id
    WHERE u.school_id = ?
      AND la.status = ?
");
$countLeaveStatus = function (string $status) use ($leaveStatusStmt, $schoolId): int {
    $leaveStatusStmt->execute([$schoolId, $status]);
    return (int) $leaveStatusStmt->fetchColumn();
};

$leavePending = $countLeaveStatus('pending');
$leaveApproved = $countLeaveStatus('approved');
$leaveRejected = $countLeaveStatus('rejected');

$ipcrfCountStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM sdopang1_ipcrf i
    JOIN sdopang1_user u ON u.user_id = i.user_id
    WHERE u.school_id = ?
");
$ipcrfCountStmt->execute([$schoolId]);
$totalIpcrf = (int) $ipcrfCountStmt->fetchColumn();

$opcrfCountStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM sdopang1_opcrf o
    JOIN sdopang1_user u ON u.user_id = o.created_by
    WHERE u.school_id = ?
");
$opcrfCountStmt->execute([$schoolId]);
$totalOpcrf = (int) $opcrfCountStmt->fetchColumn();

$attendanceTables = ['attendance', 'employee_attendance', 'sdopang1_attendance', 'daily_time_records', 'dtr', 'time_logs'];
$attendanceTable = null;
foreach ($attendanceTables as $candidateTable) {
    $tableStmt = $pdo->query('SHOW TABLES LIKE ' . $pdo->quote($candidateTable));
    if ($tableStmt->fetchColumn()) {
        $attendanceTable = $candidateTable;
        break;
    }
}
$attendanceCount = 0;
if ($attendanceTable !== null) {
    $attendanceCount = (int) $pdo->query("SELECT COUNT(*) FROM `$attendanceTable`")->fetchColumn();
}

$recentDocumentsStmt = $pdo->prepare("
    SELECT d.document_id, d.status, d.remarks, d.uploaded_at, u.first_name, u.last_name, t.doc_name
    FROM sdopang1_documents d
    JOIN sdopang1_user u ON u.user_id = d.user_id
    JOIN sdopang1_document_types t ON t.doc_type_id = d.doc_type_id
    WHERE u.school_id = ?
      AND u.role_id = 4
      AND u.user_id <> ?
    ORDER BY d.uploaded_at DESC
    LIMIT 8
");
$recentDocumentsStmt->execute([$schoolId, $_SESSION['user_id']]);
$recentDocuments = $recentDocumentsStmt->fetchAll(PDO::FETCH_ASSOC);

$recentLeaveStmt = $pdo->prepare("
    SELECT la.application_id, la.date_from, la.date_to, la.days, la.status, u.first_name, u.last_name, lt.leave_name
    FROM leave_applications la
    JOIN sdopang1_user u ON u.user_id = la.user_id
    JOIN leave_types lt ON lt.leave_type_id = la.leave_type_id
    WHERE u.school_id = ?
    ORDER BY la.created_at DESC
    LIMIT 8
");
$recentLeaveStmt->execute([$schoolId]);
$recentLeaves = $recentLeaveStmt->fetchAll(PDO::FETCH_ASSOC);

$recentEmployeesStmt = $pdo->prepare("
    SELECT user_id, first_name, middle_name, last_name, employeeID, email
    FROM sdopang1_user
    WHERE school_id = ?
      AND role_id = 4
    ORDER BY user_id DESC
    LIMIT 6
");
$recentEmployeesStmt->execute([$schoolId]);
$recentEmployees = $recentEmployeesStmt->fetchAll(PDO::FETCH_ASSOC);

$schoolActions = [
    ['School Employees', 'View and manage school employee accounts.', 'admin_employee_school_units.php', 'bi bi-people'],
    ['Unit Heads', 'Review school head and unit lead account assignments.', 'admin_employee_school_units.php', 'bi bi-person-badge'],
    ['Attendance', $attendanceTable ? 'Open attendance monitoring records.' : 'Attendance module/table is not yet configured.', 'school_dashboard.php', 'bi bi-clock-history'],
    ['IPCRF / OPCRF', 'Open performance form monitoring and submissions.', 'user_ipcrf_list.php', 'bi bi-clipboard-data'],
    ['Reports', 'Open school 201, leave, and ledger reports.', 'school_leave_ledger.php', 'bi bi-file-earmark-bar-graph'],
];
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
            <h2 class="mb-1">School Dashboard</h2>
            <p class="text-700 mb-0"><?= htmlspecialchars($school['schoolname'] ?? 'Assigned school') ?> overview for 201 files and leave monitoring.</p>
          </div>
          <div class="dashboard-actions">
            <a href="school_201_tables.php" class="btn btn-primary">Review 201 Files</a>
            <a href="school_leave_applications.php" class="btn btn-outline-primary">Review Leave</a>
          </div>
        </div>

        <?php page_info(
            'What this page does',
            'This dashboard summarizes school employee 201 files and leave activity.',
            [
                'School Head reviews regular school employee 201 files only.',
                'School Head and division employee 201 files are validated by admin.'
            ]
        ); ?>

        <div class="row pb-10">
          <?php
          $cards = [
              ['Employees', $totalEmployees, 'primary', 'bi bi-people'],
              ['Unit Heads', $totalUnitHeads, 'info', 'bi bi-person-badge'],
              ['201 Files', $totalDocuments, 'secondary', 'bi bi-folder-check'],
              ['Pending 201', $pendingDocuments, 'warning', 'bi bi-hourglass-split'],
              ['Approved 201', $approvedDocuments, 'success', 'bi bi-check2-circle'],
              ['Returned 201', $returnedDocuments, 'danger', 'bi bi-arrow-counterclockwise'],
              ['Pending Leave', $leavePending, 'warning', 'bi bi-calendar-event'],
              ['Approved Leave', $leaveApproved, 'success', 'bi bi-calendar-check'],
              ['Rejected Leave', $leaveRejected, 'danger', 'bi bi-calendar-x'],
              ['Attendance', $attendanceCount, $attendanceTable ? 'primary' : 'secondary', 'bi bi-clock-history'],
              ['IPCRF', $totalIpcrf, 'primary', 'bi bi-clipboard-data'],
              ['OPCRF', $totalOpcrf, 'primary', 'bi bi-building-check'],
          ];
          foreach ($cards as $card):
          ?>
            <div class="col-xl-3 col-lg-4 col-md-6 mb-20">
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

        <div class="row pb-10">
          <?php foreach ($schoolActions as $action): ?>
            <div class="col-xl-2 col-lg-4 col-md-6 mb-20">
              <a href="<?= htmlspecialchars($action[2], ENT_QUOTES, 'UTF-8') ?>" class="card-box d-block height-100-p pd-20 text-decoration-none">
                <div class="d-flex align-items-start">
                  <div class="mr-3">
                    <span class="<?= htmlspecialchars($action[3], ENT_QUOTES, 'UTF-8') ?>" style="font-size: 28px;"></span>
                  </div>
                  <div>
                    <h6 class="mb-1 text-dark"><?= htmlspecialchars($action[0]) ?></h6>
                    <p class="mb-0 text-700"><?= htmlspecialchars($action[1]) ?></p>
                  </div>
                </div>
              </a>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="row pb-10">
          <div class="col-md-4 mb-20">
            <div class="card h-100">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h5 class="mb-0">Recent Employees</h5>
                  <a href="admin_employee_school_units.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="table-responsive">
                  <table class="table fs--1 mb-0">
                    <thead>
                      <tr>
                        <th>Employee</th>
                        <th>ID</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($recentEmployees as $employee): ?>
                        <tr>
                          <td><?= htmlspecialchars(trim($employee['first_name'] . ' ' . ($employee['middle_name'] ? $employee['middle_name'] . ' ' : '') . $employee['last_name'])) ?></td>
                          <td><?= htmlspecialchars((string) ($employee['employeeID'] ?: $employee['user_id'])) ?></td>
                        </tr>
                      <?php endforeach; ?>
                      <?php if (!$recentEmployees): ?>
                        <tr><td colspan="2" class="text-center text-700">No school employees yet.</td></tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-4 mb-20">
            <div class="card h-100">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h5 class="mb-0">Recent 201 Uploads</h5>
                  <a href="school_201_tables.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="table-responsive">
                  <table class="table fs--1 mb-0">
                    <thead>
                      <tr>
                        <th>Employee</th>
                        <th>Document</th>
                        <th>Status</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($recentDocuments as $document): ?>
                        <tr>
                          <td><?= htmlspecialchars($document['first_name'] . ' ' . $document['last_name']) ?></td>
                          <td><?= htmlspecialchars($document['doc_name']) ?></td>
                          <td><?= htmlspecialchars($document['status']) ?></td>
                          <td><a href="school_view_201_file.php?id=<?= urlencode((string) $document['document_id']) ?>">Review</a></td>
                        </tr>
                      <?php endforeach; ?>
                      <?php if (!$recentDocuments): ?>
                        <tr><td colspan="4" class="text-center text-700">No school employee uploads yet.</td></tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-4 mb-20">
            <div class="card h-100">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h5 class="mb-0">Recent Leave Applications</h5>
                  <a href="school_leave_applications.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="table-responsive">
                  <table class="table fs--1 mb-0">
                    <thead>
                      <tr>
                        <th>Employee</th>
                        <th>Leave</th>
                        <th class="text-end">Days</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($recentLeaves as $leave): ?>
                        <tr>
                          <td><?= htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']) ?></td>
                          <td><?= htmlspecialchars($leave['leave_name']) ?></td>
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
                <h5 class="mb-3">School Assignment</h5>
                <div class="row">
                  <div class="col-md-4 mb-3">
                    <p class="text-700 mb-1">School</p>
                    <h6 class="mb-0"><?= htmlspecialchars($school['schoolname'] ?? '-') ?></h6>
                  </div>
                  <div class="col-md-4 mb-3">
                    <p class="text-700 mb-1">School ID</p>
                    <h6 class="mb-0"><?= htmlspecialchars((string) ($school['schoolID'] ?? $schoolId ?? '-')) ?></h6>
                  </div>
                  <div class="col-md-4 mb-3">
                    <p class="text-700 mb-1">District</p>
                    <h6 class="mb-0"><?= htmlspecialchars($school['district'] ?? '-') ?></h6>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <?php require_once __DIR__ . '/partials/footer.php'; ?>
      </div>
    </div>

    <?php require_once __DIR__ . '/partials/welcomemodal.php'; ?>
    <button class="welcome-modal-btn">
      <i class="fa fa-download"></i> Download
    </button>
    <script src="vendors/scripts/core.js"></script>
    <script src="vendors/scripts/script.min.js"></script>
    <script src="vendors/scripts/process.js"></script>
    <script src="vendors/scripts/layout-settings.js"></script>
    <script src="src/plugins/datatables/js/jquery.dataTables.min.js"></script>
    <script src="src/plugins/datatables/js/dataTables.bootstrap4.min.js"></script>
    <script src="src/plugins/datatables/js/dataTables.responsive.min.js"></script>
    <script src="src/plugins/datatables/js/responsive.bootstrap4.min.js"></script>
  </body>
</html>
