<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

$userModel = new User($pdo);
require_login();
require_role([1, 2, 3, 4, 5, 6, 7]);
require_once __DIR__ . '/partials/session.php';

$officeId = $currentUser['office_id'] ?? null;
$officeRole = $currentUser['office_role'] ?? 'Staff';
$isAssignedUnitHead = false;
$isLinkedSchoolHead = false;
$isDivisionOfficeHead = false;

$headOfficeStmt = $pdo->prepare("
    SELECT office_id
    FROM sdopang1_offices
    WHERE unit_head = ?
    ORDER BY office_id
    LIMIT 1
");
$headOfficeStmt->execute([$_SESSION['user_id']]);
$headOfficeId = $headOfficeStmt->fetchColumn();

if ($headOfficeId) {
    $officeId = (int) $headOfficeId;
    $officeRole = 'Unit Head';
}

$office = null;
$opcrfs = [];

if ($officeId) {
    $officeStmt = $pdo->prepare("
        SELECT o.*, s.schoolname
        FROM sdopang1_offices o
        LEFT JOIN sdopang1schoollist s ON s.schoolID = o.school_id
        WHERE o.office_id = ?
    ");
    $officeStmt->execute([$officeId]);
    $office = $officeStmt->fetch(PDO::FETCH_ASSOC);
    $isAssignedUnitHead = (int) ($office['unit_head'] ?? 0) === (int) $_SESSION['user_id'];
    $isLinkedSchoolHead = ($office['office_category'] ?? '') === 'School'
        && (string) ($office['school_id'] ?? '') !== ''
        && (string) ($office['school_id'] ?? '') === (string) ($currentUser['school_id'] ?? '')
        && (
            (string) ($currentUser['office_role'] ?? '') === 'Head'
            || (int) ($_SESSION['role_id'] ?? 0) === 3
            || (int) ($office['office_head'] ?? 0) === (int) $_SESSION['user_id']
        );
    $isDivisionOfficeHead = ($office['office_category'] ?? '') === 'Division Office'
        && (string) ($currentUser['office_role'] ?? '') === 'Head'
        && (int) ($currentUser['office_id'] ?? 0) === (int) $officeId;

    $stmt = $pdo->prepare("
        SELECT *
        FROM sdopang1_opcrf
        WHERE office_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$officeId]);
    $opcrfs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$canFile = $isAssignedUnitHead || $isLinkedSchoolHead || $isDivisionOfficeHead;
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
            <h2 class="mb-1">Office/Unit OPCRF</h2>
            <p class="text-700 mb-0">
              <?= $office ? htmlspecialchars($office['office_name']) : 'No office assigned yet.' ?>
            </p>
          </div>
          <?php if ($office && $canFile): ?>
            <button class="btn btn-primary" data-toggle="modal" data-target="#opcrfModal">File Office/Unit OPCRF</button>
          <?php endif; ?>
        </div>

        <?php if (!$office): ?>
          <div class="alert alert-warning">Your account is not assigned to an office yet. Ask the admin to set your Office and Office Role in User Management.</div>
        <?php else: ?>
          <div class="card-box pd-20 mb-20">
            <div class="row">
              <div class="col-md-4 mb-2">
                <p class="text-700 mb-1">Office Category</p>
                <h6><?= htmlspecialchars($office['office_category'] ?? '-') ?></h6>
              </div>
              <div class="col-md-4 mb-2">
                <p class="text-700 mb-1">Office Role</p>
                <h6><?= htmlspecialchars($isAssignedUnitHead ? 'Unit Head' : ($isLinkedSchoolHead ? 'School Head' : ($isDivisionOfficeHead ? 'Head' : $officeRole))) ?></h6>
              </div>
              <div class="col-md-4 mb-2">
                <p class="text-700 mb-1">Linked School</p>
                <h6><?= htmlspecialchars($office['schoolname'] ?? '-') ?></h6>
              </div>
            </div>
          </div>

          <div class="card-box pd-20">
            <div class="table-responsive">
              <table class="table table-bordered">
                <thead>
                  <tr>
                    <th>Title</th>
                    <th>Period</th>
                    <th>Status</th>
                    <th class="text-end">Rating</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($opcrfs as $opcrf): ?>
                    <tr>
                      <td><?= htmlspecialchars($opcrf['title']) ?></td>
                      <td><?= htmlspecialchars($opcrf['school_year'] . ' / ' . $opcrf['quarter']) ?></td>
                      <td><?= htmlspecialchars($opcrf['status']) ?></td>
                      <td class="text-end"><?= $opcrf['overall_rating'] !== null ? htmlspecialchars(number_format((float) $opcrf['overall_rating'], 2)) : '-' ?></td>
                      <td><a class="btn btn-sm btn-primary" href="user_view_opcrf.php?id=<?= urlencode((string) $opcrf['opcrf_id']) ?>">Open</a></td>
                    </tr>
                  <?php endforeach; ?>
                  <?php if (!$opcrfs): ?>
                    <tr><td colspan="5" class="text-center text-muted">No OPCRF records yet.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endif; ?>

        <div class="modal fade" id="opcrfModal" tabindex="-1">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header bg-primary">
                <h5 class="modal-title text-white">File Office/Unit OPCRF</h5>
                <button class="btn-close" data-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <form id="opcrfForm" enctype="multipart/form-data">
                  <input type="hidden" name="action" value="add">
                  <div class="row">
                    <div class="col-md-6 mb-2">
                      <label>Title</label>
                      <input name="title" class="form-control" required placeholder="Office Performance Commitment and Review Form">
                    </div>
                    <div class="col-md-3 mb-2">
                      <label>School Year</label>
                      <input name="school_year" class="form-control" required placeholder="2025-2026">
                    </div>
                    <div class="col-md-3 mb-2">
                      <label>Quarter</label>
                      <select name="quarter" class="form-control" required>
                        <option value="Q1">Q1</option>
                        <option value="Q2">Q2</option>
                        <option value="Q3">Q3</option>
                        <option value="Q4">Q4</option>
                        <option value="Annual">Annual</option>
                      </select>
                    </div>
                    <div class="col-md-4 mb-2">
                      <label>Date Prepared</label>
                      <input type="date" name="date_prepared" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-4 mb-2">
                      <label>Upload PDF</label>
                      <input type="file" name="uploaded_pdf" class="form-control" accept=".pdf">
                    </div>
                    <div class="col-md-4 mb-2">
                      <label>Upload Excel</label>
                      <input type="file" name="uploaded_excel" class="form-control" accept=".xls,.xlsx">
                    </div>
                    <div class="col-12 mb-3">
                      <label>Remarks</label>
                      <textarea name="remarks" class="form-control" rows="3"></textarea>
                    </div>
                  </div>
                  <button class="btn btn-primary w-100">Submit Office/Unit OPCRF</button>
                </form>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
document.getElementById('opcrfForm')?.addEventListener('submit', function(e) {
  e.preventDefault();
  fetch('user_query_opcrf.php', {method: 'POST', body: new FormData(this)})
    .then(r => r.json())
    .then(res => {
      if (res.status !== 'success') {
        Swal.fire({icon: 'error', title: 'Save failed', text: res.message || 'Unable to file OPCRF.'});
        return;
      }
      window.location.href = 'user_view_opcrf.php?id=' + res.opcrf_id;
    });
});
    </script>
  </body>
</html>

