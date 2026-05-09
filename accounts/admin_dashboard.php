<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

$userModel = new User($pdo);
require_login();
require_role([1]);
require_once __DIR__ . '/partials/session.php';


$totalUsers = (int) $db->count("sdopang1_user");
$totalSchools = (int) $db->count("sdopang1schoollist");
$totalDocuments = (int) $db->count("sdopang1_documents");
$totalPending = (int) $db->count("sdopang1_documents", "status = 'Pending'");
$totalApproved = (int) $db->count("sdopang1_documents", "status = 'Approved'");
$totalReturned = (int) $db->count("sdopang1_documents", "status = 'Returned'");
$totalEmployees = (int) $db->count("sdopang1_user", "role_id = 4");
$leavePending = (int) $pdo->query("SELECT COUNT(*) FROM leave_applications WHERE status = 'pending'")->fetchColumn();
$leaveApproved = (int) $pdo->query("SELECT COUNT(*) FROM leave_applications WHERE status = 'approved'")->fetchColumn();
$leaveRejected = (int) $pdo->query("SELECT COUNT(*) FROM leave_applications WHERE status = 'rejected'")->fetchColumn();
$principalStmt = $pdo->query("
    SELECT COUNT(DISTINCT COALESCE(NULLIF(u.employeeID, ''), NULLIF(u.email, ''), u.user_id)) AS total
    FROM sdopang1_user u
    JOIN sdopang1_position p ON u.position_id = p.position_id
    WHERE p.position_title LIKE 'Principal%'
");
$totalPrincipals = (int) $principalStmt->fetchColumn();

$recentStmt = $pdo->query("
    SELECT d.document_id, d.status, d.uploaded_at, u.first_name, u.last_name, t.doc_name
    FROM sdopang1_documents d
    JOIN sdopang1_user u ON d.user_id = u.user_id
    JOIN sdopang1_document_types t ON d.doc_type_id = t.doc_type_id
    ORDER BY d.uploaded_at DESC
    LIMIT 8
");
$recentDocuments = $recentStmt->fetchAll();
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
              <h2 class="mb-1">Admin Dashboard</h2>
              <p class="text-700 mb-0">System-wide 201 files, leave monitoring, schools, and users.</p>
            </div>
            <div class="dashboard-actions">
              <a href="admin_201_tables.php" class="btn btn-primary">Validate 201 Files</a>
              <a href="admin_leave_dashboard.php" class="btn btn-outline-primary">Leave Tools</a>
            </div>
          </div>

        <div class="row pb-10">
          <?php
          $cards = [
              ['Users', $totalUsers, 'primary', 'admin_users_list.php', 'bi bi-people'],
              ['Employees', $totalEmployees, 'info', 'admin_users_list.php', 'bi bi-person-badge'],
              ['Principals', $totalPrincipals, 'info', 'admin_users_list.php', 'bi bi-person-check'],
              ['Schools', $totalSchools, 'primary', 'admin_school_list.php', 'bi bi-building'],
              ['201 Files', $totalDocuments, 'secondary', 'admin_201_tables.php', 'bi bi-folder-check'],
              ['Pending', $totalPending, 'warning', 'admin_201_tables.php', 'bi bi-hourglass-split'],
              ['Approved', $totalApproved, 'success', 'admin_201_tables.php', 'bi bi-check2-circle'],
              ['Returned', $totalReturned, 'danger', 'admin_201_tables.php', 'bi bi-arrow-counterclockwise'],
              ['Pending Leave', $leavePending, 'warning', 'admin_leave_applications.php', 'bi bi-calendar-event'],
              ['Approved Leave', $leaveApproved, 'success', 'admin_leave_applications.php', 'bi bi-calendar-check'],
              ['Rejected Leave', $leaveRejected, 'danger', 'admin_leave_applications.php', 'bi bi-calendar-x'],
          ];
          foreach ($cards as $card):
          ?>
          <div class="col-xl-4 col-lg-4 col-md-6 mb-20">
            <div class="prime-stat-card stat-<?= htmlspecialchars($card[2]) ?>">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <p class="stat-label"><?= htmlspecialchars($card[0]) ?></p>
                  <p class="stat-value"><?= htmlspecialchars((string) $card[1]) ?></p>
                  <a href="<?= htmlspecialchars($card[3], ENT_QUOTES, 'UTF-8') ?>" class="stat-link">View details <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="stat-icon">
                  <i class="<?= htmlspecialchars($card[4], ENT_QUOTES, 'UTF-8') ?>"></i>
                </div>
              </div>
            </div>
          </div>
           <?php endforeach; ?>
        </div>

        <div class="row pb-10">
          <div class="col-md-8 mb-20">
            <div class="card-box height-100-p pd-20">
             
             <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h5 class="mb-0">Recent 201 Uploads</h5>
                  <a href="admin_201_tables.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="table-responsive">
                  <table class="table fs--1 mb-0">
                    <thead>
                      <tr>
                        <th>Employee</th>
                        <th>Document</th>
                        <th>Status</th>
                        <th>Uploaded</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($recentDocuments as $document): ?>
                        <tr>
                          <td><?= htmlspecialchars($document['first_name'] . ' ' . $document['last_name']) ?></td>
                          <td><?= htmlspecialchars($document['doc_name']) ?></td>
                          <td><?= htmlspecialchars($document['status']) ?></td>
                          <td><?= htmlspecialchars((string) $document['uploaded_at']) ?></td>
                          <td><a href="admin_view_201_file.php?id=<?= urlencode((string) $document['document_id']) ?>">Review</a></td>
                        </tr>
                      <?php endforeach; ?>
                      <?php if (!$recentDocuments): ?>
                        <tr><td colspan="5" class="text-center text-700">No uploads yet.</td></tr>
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
                <h5 class="mb-3">201 Status Summary</h5>
                <?php
                $statusRows = [
                    ['Pending', $totalPending, 'warning'],
                    ['Approved', $totalApproved, 'success'],
                    ['Returned', $totalReturned, 'danger'],
                ];
                foreach ($statusRows as $row):
                    $percent = $totalDocuments > 0 ? round(($row[1] / $totalDocuments) * 100) : 0;
                ?>
                  <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                      <span><?= htmlspecialchars($row[0]) ?></span>
                      <span><?= $percent ?>%</span>
                    </div>
                    <div class="progress" style="height: 10px;">
                      <div class="progress-bar bg-<?= htmlspecialchars($row[2]) ?>" style="width: <?= $percent ?>%;"></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <hr>
              <h5 class="mb-3">Leave Status Summary</h5>
              <?php
              $leaveTotal = $leavePending + $leaveApproved + $leaveRejected;
              $leaveRows = [
                  ['Pending', $leavePending, 'warning'],
                  ['Approved', $leaveApproved, 'success'],
                  ['Rejected', $leaveRejected, 'danger'],
              ];
              foreach ($leaveRows as $row):
                  $percent = $leaveTotal > 0 ? round(($row[1] / $leaveTotal) * 100) : 0;
              ?>
                <div class="mb-3">
                  <div class="d-flex justify-content-between mb-1">
                    <span><?= htmlspecialchars($row[0]) ?></span>
                    <span><?= $percent ?>%</span>
                  </div>
                  <div class="progress" style="height: 10px;">
                    <div class="progress-bar bg-<?= htmlspecialchars($row[2]) ?>" style="width: <?= $percent ?>%;"></div>
                  </div>
                </div>
              <?php endforeach; ?>
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


