<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/opcrf_helpers.php';

$userModel = new User($pdo);
require_login();
require_role([1, 2, 3, 4, 5, 6, 7]);
require_once __DIR__ . '/partials/session.php';

$opcrfId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$opcrfId) {
    die('Invalid request');
}

$officeId = $currentUser['office_id'] ?? null;

if (!$officeId) {
    $headOfficeStmt = $pdo->prepare("
        SELECT office_id
        FROM sdopang1_offices
        WHERE office_head = ?
           OR unit_head = ?
        ORDER BY office_id
        LIMIT 1
    ");
    $headOfficeStmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $headOfficeId = $headOfficeStmt->fetchColumn();

    if ($headOfficeId) {
        $officeId = (int) $headOfficeId;
    }
}

$stmt = $pdo->prepare("
    SELECT o.*, f.office_name, f.office_category
    FROM sdopang1_opcrf o
    JOIN sdopang1_offices f ON f.office_id = o.office_id
    WHERE o.opcrf_id = ?
      AND o.office_id = ?
");
$stmt->execute([$opcrfId, $officeId]);
$opcrf = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$opcrf) {
    die('OPCRF not found or access denied');
}

$canManageContent = opcrf_user_can_manage_content($pdo, $opcrfId);

$indicatorStmt = $pdo->prepare("SELECT * FROM sdopang1_opcrf_indicators WHERE opcrf_id = ? ORDER BY indicator_id");
$indicatorStmt->execute([$opcrfId]);
$indicators = $indicatorStmt->fetchAll(PDO::FETCH_ASSOC);

$movStmt = $pdo->prepare("SELECT * FROM sdopang1_opcrf_movs WHERE opcrf_id = ? ORDER BY uploaded_at DESC");
$movStmt->execute([$opcrfId]);
$movs = $movStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
 <?php require_once __DIR__ . '/partials/head.php'; ?>
  <body>
   <?php if (!empty($_SESSION['success_message'])): ?>
    <script>alert(<?= json_encode($_SESSION['success_message']) ?>);</script>
    <?php unset($_SESSION['success_message']); ?>
   <?php endif; ?>
   <?php require_once __DIR__ . '/partials/preloader.php'; ?>
    <?php require_once __DIR__ . '/partials/navbar.php'; ?>
    <?php require_once __DIR__ . '/partials/rightsidebar.php'; ?>
    <?php require_once __DIR__ . '/partials/leftsidebar.php'; ?>
    <div class="mobile-menu-overlay"></div>

    <div class="main-container">
      <div class="xs-pd-20-10 pd-ltr-20">
        <div class="d-flex justify-content-between pb-20">
          <div>
            <h2><?= htmlspecialchars($opcrf['title']) ?></h2>
            <p class="text-700 mb-0"><?= htmlspecialchars($opcrf['office_name']) ?> - <?= htmlspecialchars($opcrf['school_year']) ?> / <?= htmlspecialchars($opcrf['quarter']) ?></p>
          </div>
          <a href="user_opcrf_list.php" class="btn btn-outline-primary">Back to Office/Unit OPCRF</a>
        </div>

        <div class="card-box pd-20 mb-20">
          <div class="row">
            <div class="col-md-3 mb-2"><p class="text-700 mb-1">Status</p><h6><?= htmlspecialchars($opcrf['status']) ?></h6></div>
            <div class="col-md-3 mb-2"><p class="text-700 mb-1">Rating</p><h6><?= $opcrf['overall_rating'] !== null ? htmlspecialchars(number_format((float) $opcrf['overall_rating'], 2)) : '-' ?></h6></div>
            <div class="col-md-3 mb-2"><p class="text-700 mb-1">Category</p><h6><?= htmlspecialchars($opcrf['office_category']) ?></h6></div>
            <div class="col-md-3 mb-2"><p class="text-700 mb-1">Remarks</p><h6><?= htmlspecialchars($opcrf['remarks'] ?: '-') ?></h6></div>
          </div>
          <?php if ($opcrf['uploaded_pdf']): ?><a class="btn btn-sm btn-outline-primary mr-2" target="_blank" href="<?= htmlspecialchars($opcrf['uploaded_pdf'], ENT_QUOTES, 'UTF-8') ?>">Open PDF</a><?php endif; ?>
          <?php if ($opcrf['uploaded_excel']): ?><a class="btn btn-sm btn-outline-primary" target="_blank" href="<?= htmlspecialchars($opcrf['uploaded_excel'], ENT_QUOTES, 'UTF-8') ?>">Open Excel</a><?php endif; ?>
        </div>

        <div class="card-box pd-20 mb-20">
          <h5 class="mb-3">Indicators</h5>
          <div class="table-responsive">
            <table class="table table-bordered">
              <thead><tr><th>KRA</th><th>Objective</th><th>Success Indicator</th><th>Actual</th><th>Rating</th><?php if ($canManageContent): ?><th></th><?php endif; ?></tr></thead>
              <tbody>
                <?php foreach ($indicators as $indicator): ?>
                  <tr>
                    <td><?= htmlspecialchars($indicator['kra'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($indicator['objective']) ?></td>
                    <td><?= htmlspecialchars($indicator['success_indicator'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($indicator['actual_accomplishment'] ?: '-') ?></td>
                    <td><?= htmlspecialchars($indicator['rating'] !== null ? number_format((float) $indicator['rating'], 2) : '-') ?></td>
                    <?php if ($canManageContent): ?>
                      <td><button class="btn btn-sm btn-danger btnDeleteIndicator" data-id="<?= htmlspecialchars((string) $indicator['indicator_id']) ?>">Delete</button></td>
                    <?php endif; ?>
                  </tr>
                <?php endforeach; ?>
                <?php if (!$indicators): ?><tr><td colspan="<?= $canManageContent ? '6' : '5' ?>" class="text-center text-muted">No indicators yet.</td></tr><?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card-box pd-20">
          <h5 class="mb-3">MOV Attachments</h5>
          <div class="table-responsive">
            <table class="table table-bordered">
              <thead><tr><th>File</th><th>Type</th><th>Uploaded</th></tr></thead>
              <tbody>
                <?php foreach ($movs as $mov): ?>
                  <tr>
                    <td><a target="_blank" href="<?= htmlspecialchars($mov['file_path'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($mov['file_name']) ?></a></td>
                    <td><?= htmlspecialchars($mov['file_type'] ?: '-') ?></td>
                    <td><?= htmlspecialchars((string) $mov['uploaded_at']) ?></td>
                  </tr>
                <?php endforeach; ?>
                <?php if (!$movs): ?><tr><td colspan="3" class="text-center text-muted">No MOV uploaded yet.</td></tr><?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <?php if ($canManageContent): ?>
          <div class="row mt-20">
            <div class="col-lg-6 mb-20">
              <div class="card-box pd-20 h-100">
                <h5 class="mb-3">Add Indicator</h5>
                <form id="indicatorForm">
                  <input type="hidden" name="action" value="add">
                  <input type="hidden" name="opcrf_id" value="<?= htmlspecialchars((string) $opcrfId, ENT_QUOTES, 'UTF-8') ?>">
                  <input name="kra" class="form-control mb-2" placeholder="KRA">
                  <textarea name="objective" class="form-control mb-2" rows="2" required placeholder="Objective"></textarea>
                  <textarea name="success_indicator" class="form-control mb-2" rows="2" placeholder="Success indicator"></textarea>
                  <textarea name="actual_accomplishment" class="form-control mb-2" rows="2" placeholder="Actual accomplishment"></textarea>
                  <input name="quality" class="form-control mb-2" placeholder="Quality">
                  <input name="efficiency" class="form-control mb-2" placeholder="Efficiency">
                  <input name="timeliness" class="form-control mb-2" placeholder="Timeliness">
                  <input name="rating" type="number" step="0.01" class="form-control mb-2" placeholder="Rating">
                  <textarea name="remarks" class="form-control mb-2" rows="2" placeholder="Remarks"></textarea>
                  <button class="btn btn-primary w-100">Add Indicator</button>
                </form>
              </div>
            </div>

            <div class="col-lg-6 mb-20">
              <div class="card-box pd-20 h-100">
                <h5 class="mb-3">Upload MOV</h5>
                <form method="POST" action="admin_query_opcrf_upload.php" enctype="multipart/form-data">
                  <input type="hidden" name="opcrf_id" value="<?= htmlspecialchars((string) $opcrfId, ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" name="source" value="user">
                  <select name="indicator_id" class="form-control mb-2">
                    <option value="">General OPCRF MOV</option>
                    <?php foreach ($indicators as $indicator): ?>
                      <option value="<?= htmlspecialchars((string) $indicator['indicator_id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(substr($indicator['objective'], 0, 80)) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <input type="file" name="mov_file" class="form-control mb-2" required>
                  <button class="btn btn-primary w-100">Upload MOV</button>
                </form>
              </div>
            </div>
          </div>
        <?php endif; ?>

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
    function postForm(form, url) {
      fetch(url, {method: 'POST', body: new FormData(form)})
        .then(r => r.json())
        .then(res => {
          if (res.status !== 'success') {
            Swal.fire({icon: 'error', title: 'Action failed', text: res.message || 'Unable to save.'});
            return;
          }
          location.reload();
        });
    }

    const indicatorForm = document.getElementById('indicatorForm');
    if (indicatorForm) {
      indicatorForm.addEventListener('submit', function(e) {
        e.preventDefault();
        postForm(this, 'admin_query_opcrf_indicators.php');
      });
    }

    document.addEventListener('click', function(e) {
      const btn = e.target.closest('.btnDeleteIndicator');
      if (!btn) return;
      Swal.fire({
        icon: 'warning',
        title: 'Delete indicator?',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        confirmButtonColor: '#d33'
      }).then(result => {
        if (!result.isConfirmed) return;
        const body = new URLSearchParams({action: 'delete', indicator_id: btn.dataset.id});
        fetch('admin_query_opcrf_indicators.php', {method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body})
          .then(r => r.json())
          .then(res => {
            if (res.status === 'success') location.reload();
            else Swal.fire({icon: 'error', title: 'Delete failed', text: res.message || 'Unable to delete indicator.'});
          });
      });
    });
    </script>
  </body>
</html>

