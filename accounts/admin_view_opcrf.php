<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

$userModel = new User($pdo);
require_login();
require_validator($pdo, 'opcrf');
require_once __DIR__ . '/partials/session.php';

$opcrfId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$opcrfId) {
    die('Invalid request');
}

$stmt = $pdo->prepare("
    SELECT o.*, f.office_name, f.office_type,
           CONCAT(prep.first_name, ' ', prep.last_name) AS prepared_name,
           CONCAT(rev.first_name, ' ', rev.last_name) AS reviewed_name,
           CONCAT(app.first_name, ' ', app.last_name) AS approved_name
    FROM sdopang1_opcrf o
    JOIN sdopang1_offices f ON f.office_id = o.office_id
    LEFT JOIN sdopang1_user prep ON prep.user_id = o.prepared_by
    LEFT JOIN sdopang1_user rev ON rev.user_id = o.reviewed_by
    LEFT JOIN sdopang1_user app ON app.user_id = o.approved_by
    WHERE o.opcrf_id = ?
");
$stmt->execute([$opcrfId]);
$opcrf = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$opcrf) {
    die('OPCRF not found');
}

$indicatorStmt = $pdo->prepare("SELECT * FROM sdopang1_opcrf_indicators WHERE opcrf_id = ? ORDER BY indicator_id");
$indicatorStmt->execute([$opcrfId]);
$indicators = $indicatorStmt->fetchAll(PDO::FETCH_ASSOC);

$movStmt = $pdo->prepare("SELECT * FROM sdopang1_opcrf_movs WHERE opcrf_id = ? ORDER BY uploaded_at DESC");
$movStmt->execute([$opcrfId]);
$movs = $movStmt->fetchAll(PDO::FETCH_ASSOC);

$logStmt = $pdo->prepare("
    SELECT l.*, CONCAT(u.first_name, ' ', u.last_name) AS action_by_name
    FROM sdopang1_opcrf_logs l
    LEFT JOIN sdopang1_user u ON u.user_id = l.action_by
    WHERE l.opcrf_id = ?
    ORDER BY l.action_date DESC
");
$logStmt->execute([$opcrfId]);
$logs = $logStmt->fetchAll(PDO::FETCH_ASSOC);
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
        <a href="admin_opcrf_list.php" class="btn btn-outline-primary">Back to OPCRF List</a>
    </div>

    <div class="row">
        <div class="col-lg-8 mb-20">
            <div class="card-box pd-20 mb-20">
                <h5 class="mb-3">OPCRF Details</h5>
                <div class="row">
                    <div class="col-md-4 mb-3"><p class="text-700 mb-1">Status</p><h6><?= htmlspecialchars($opcrf['status']) ?></h6></div>
                    <div class="col-md-4 mb-3"><p class="text-700 mb-1">Overall Rating</p><h6><?= $opcrf['overall_rating'] !== null ? htmlspecialchars(number_format((float) $opcrf['overall_rating'], 2)) : '-' ?></h6></div>
                    <div class="col-md-4 mb-3"><p class="text-700 mb-1">Prepared By</p><h6><?= htmlspecialchars($opcrf['prepared_name'] ?: '-') ?></h6></div>
                    <div class="col-md-4 mb-3"><p class="text-700 mb-1">Reviewed By</p><h6><?= htmlspecialchars($opcrf['reviewed_name'] ?: '-') ?></h6></div>
                    <div class="col-md-4 mb-3"><p class="text-700 mb-1">Approved By</p><h6><?= htmlspecialchars($opcrf['approved_name'] ?: '-') ?></h6></div>
                    <div class="col-md-4 mb-3"><p class="text-700 mb-1">Remarks</p><h6><?= htmlspecialchars($opcrf['remarks'] ?: '-') ?></h6></div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <?php if ($opcrf['uploaded_pdf']): ?><a class="btn btn-sm btn-outline-primary mr-2" target="_blank" href="<?= htmlspecialchars($opcrf['uploaded_pdf'], ENT_QUOTES, 'UTF-8') ?>">Open PDF</a><?php endif; ?>
                    <?php if ($opcrf['uploaded_excel']): ?><a class="btn btn-sm btn-outline-primary" target="_blank" href="<?= htmlspecialchars($opcrf['uploaded_excel'], ENT_QUOTES, 'UTF-8') ?>">Open Excel</a><?php endif; ?>
                </div>
            </div>

            <div class="card-box pd-20 mb-20">
                <h5 class="mb-3">Indicators</h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>KRA</th>
                                <th>Objective</th>
                                <th>Success Indicator</th>
                                <th>Actual</th>
                                <th>Rating</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($indicators as $indicator): ?>
                                <tr>
                                    <td><?= htmlspecialchars($indicator['kra'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($indicator['objective']) ?></td>
                                    <td><?= htmlspecialchars($indicator['success_indicator'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($indicator['actual_accomplishment'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($indicator['rating'] !== null ? number_format((float) $indicator['rating'], 2) : '-') ?></td>
                                    <td><button class="btn btn-sm btn-danger btnDeleteIndicator" data-id="<?= htmlspecialchars((string) $indicator['indicator_id']) ?>">Delete</button></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$indicators): ?>
                                <tr><td colspan="6" class="text-center text-muted">No indicators yet.</td></tr>
                            <?php endif; ?>
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
                            <?php if (!$movs): ?>
                                <tr><td colspan="3" class="text-center text-muted">No MOV uploaded yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-20">
            <div class="card-box pd-20 mb-20">
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

            <div class="card-box pd-20 mb-20">
                <h5 class="mb-3">Upload MOV</h5>
                <form method="POST" action="admin_query_opcrf_upload.php" enctype="multipart/form-data">
                    <input type="hidden" name="opcrf_id" value="<?= htmlspecialchars((string) $opcrfId, ENT_QUOTES, 'UTF-8') ?>">
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

            <div class="card-box pd-20 mb-20">
                <h5 class="mb-3">Rating and Status</h5>
                <form id="ratingForm" class="mb-3">
                    <input type="hidden" name="action" value="rating">
                    <input type="hidden" name="opcrf_id" value="<?= htmlspecialchars((string) $opcrfId, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="number" step="0.01" name="overall_rating" class="form-control mb-2" value="<?= htmlspecialchars((string) $opcrf['overall_rating'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Overall rating">
                    <button class="btn btn-outline-primary w-100">Save Rating</button>
                </form>
                <form id="statusForm">
                    <input type="hidden" name="action" value="status">
                    <input type="hidden" name="opcrf_id" value="<?= htmlspecialchars((string) $opcrfId, ENT_QUOTES, 'UTF-8') ?>">
                    <select name="status" class="form-control mb-2">
                        <?php foreach (['Draft','For Review','Reviewed','Approved','Returned'] as $status): ?>
                            <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" <?= $opcrf['status'] === $status ? 'selected' : '' ?>><?= htmlspecialchars($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <textarea name="remarks" class="form-control mb-2" rows="2" placeholder="Status remarks"></textarea>
                    <button class="btn btn-primary w-100">Update Status</button>
                </form>
            </div>

            <div class="card-box pd-20">
                <h5 class="mb-3">History</h5>
                <?php foreach ($logs as $log): ?>
                    <div class="border-bottom pb-2 mb-2">
                        <strong><?= htmlspecialchars($log['action_taken']) ?></strong>
                        <div class="text-700"><?= htmlspecialchars($log['action_by_name'] ?: '-') ?> - <?= htmlspecialchars((string) $log['action_date']) ?></div>
                        <div><?= htmlspecialchars($log['remarks'] ?: '') ?></div>
                    </div>
                <?php endforeach; ?>
                <?php if (!$logs): ?>
                    <p class="text-muted mb-0">No history yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
</div>
</div>

<script src="vendors/scripts/core.js"></script>
<script src="vendors/scripts/script.min.js"></script>
<script src="vendors/scripts/process.js"></script>
<script src="vendors/scripts/layout-settings.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function postForm(form, url, reload = true) {
  fetch(url, {method: 'POST', body: new FormData(form)})
    .then(r => r.json())
    .then(res => {
      if (res.status !== 'success') {
        Swal.fire({icon: 'error', title: 'Action failed', text: res.message || 'Unable to save.'});
        return;
      }
      if (reload) location.reload();
    });
}

document.getElementById('indicatorForm').addEventListener('submit', function(e) {
  e.preventDefault();
  postForm(this, 'admin_query_opcrf_indicators.php');
});

document.getElementById('ratingForm').addEventListener('submit', function(e) {
  e.preventDefault();
  postForm(this, 'admin_query_opcrf.php');
});

document.getElementById('statusForm').addEventListener('submit', function(e) {
  e.preventDefault();
  postForm(this, 'admin_query_opcrf.php');
});

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


