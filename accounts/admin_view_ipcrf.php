<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

$userModel = new User($pdo);
require_login();
require_validator($pdo, 'ipcrf');
require_once __DIR__ . '/partials/session.php';

$ipcrfId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$ipcrfId) {
    die('Invalid request');
}

$stmt = $pdo->prepare("
    SELECT
        i.*,
        CONCAT(u.first_name, ' ', u.last_name) AS employee_name,
        u.employeeID,
        COALESCE(s.schoolname, 'No school') AS schoolname,
        CONCAT(rev.first_name, ' ', rev.last_name) AS reviewed_name,
        CONCAT(app.first_name, ' ', app.last_name) AS approved_name
    FROM sdopang1_ipcrf i
    JOIN sdopang1_user u ON u.user_id = i.user_id
    LEFT JOIN sdopang1schoollist s ON s.schoolID = u.school_id
    LEFT JOIN sdopang1_user rev ON rev.user_id = i.reviewed_by
    LEFT JOIN sdopang1_user app ON app.user_id = i.approved_by
    WHERE i.ipcrf_id = ?
");
$stmt->execute([$ipcrfId]);
$ipcrf = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ipcrf) {
    die('IPCRF not found');
}
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
    <div class="d-flex justify-content-between pb-20">
        <div>
            <h2><?= htmlspecialchars($ipcrf['title']) ?></h2>
            <p class="text-700 mb-0"><?= htmlspecialchars($ipcrf['employeeID'] . ' - ' . $ipcrf['employee_name']) ?></p>
        </div>
        <a href="admin_ipcrf_list.php" class="btn btn-outline-primary">Back to IPCRF List</a>
    </div>

    <div class="row">
        <div class="col-lg-8 mb-20">
            <div class="card-box pd-20 mb-20">
                <h5 class="mb-3">IPCRF Details</h5>
                <div class="row">
                    <div class="col-md-4 mb-2"><p class="text-700 mb-1">School/Office</p><h6><?= htmlspecialchars($ipcrf['schoolname']) ?></h6></div>
                    <div class="col-md-4 mb-2"><p class="text-700 mb-1">Period</p><h6><?= htmlspecialchars($ipcrf['school_year'] . ' / ' . $ipcrf['rating_period']) ?></h6></div>
                    <div class="col-md-4 mb-2"><p class="text-700 mb-1">Status</p><h6><?= htmlspecialchars($ipcrf['status']) ?></h6></div>
                    <div class="col-md-4 mb-2"><p class="text-700 mb-1">Rating</p><h6><?= $ipcrf['overall_rating'] !== null ? htmlspecialchars(number_format((float) $ipcrf['overall_rating'], 2)) : '-' ?></h6></div>
                    <div class="col-md-4 mb-2"><p class="text-700 mb-1">Reviewed By</p><h6><?= htmlspecialchars($ipcrf['reviewed_name'] ?: '-') ?></h6></div>
                    <div class="col-md-4 mb-2"><p class="text-700 mb-1">Approved By</p><h6><?= htmlspecialchars($ipcrf['approved_name'] ?: '-') ?></h6></div>
                </div>
                <?php if ($ipcrf['uploaded_pdf']): ?><a class="btn btn-sm btn-outline-primary mr-2" target="_blank" href="<?= htmlspecialchars($ipcrf['uploaded_pdf'], ENT_QUOTES, 'UTF-8') ?>">Open PDF</a><?php endif; ?>
                <?php if ($ipcrf['uploaded_excel']): ?><a class="btn btn-sm btn-outline-primary" target="_blank" href="<?= htmlspecialchars($ipcrf['uploaded_excel'], ENT_QUOTES, 'UTF-8') ?>">Open Excel</a><?php endif; ?>
            </div>

            <div class="card-box pd-20">
                <h5 class="mb-3">IPCRF Contents</h5>
                <p class="text-700 mb-1">Individual Targets</p>
                <div class="border rounded p-3 mb-3"><?= nl2br(htmlspecialchars($ipcrf['employee_targets'] ?: '-')) ?></div>
                <p class="text-700 mb-1">Individual Accomplishments</p>
                <div class="border rounded p-3 mb-3"><?= nl2br(htmlspecialchars($ipcrf['employee_accomplishments'] ?: '-')) ?></div>
                <p class="text-700 mb-1">Individual Indicators</p>
                <div class="border rounded p-3"><?= nl2br(htmlspecialchars($ipcrf['employee_indicators'] ?: '-')) ?></div>
            </div>
        </div>

        <div class="col-lg-4 mb-20">
            <div class="card-box pd-20">
                <h5 class="mb-3">Rating and Status</h5>
                <form id="ratingForm" class="mb-3">
                    <input type="hidden" name="action" value="rating">
                    <input type="hidden" name="ipcrf_id" value="<?= htmlspecialchars((string) $ipcrfId, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="number" step="0.01" name="overall_rating" class="form-control mb-2" value="<?= htmlspecialchars((string) $ipcrf['overall_rating'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Overall rating">
                    <button class="btn btn-outline-primary w-100">Save Rating</button>
                </form>
                <form id="statusForm">
                    <input type="hidden" name="action" value="status">
                    <input type="hidden" name="ipcrf_id" value="<?= htmlspecialchars((string) $ipcrfId, ENT_QUOTES, 'UTF-8') ?>">
                    <select name="status" class="form-control mb-2">
                        <?php foreach (['Draft','For Review','Reviewed','Approved','Returned'] as $status): ?>
                            <option value="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>" <?= $ipcrf['status'] === $status ? 'selected' : '' ?>><?= htmlspecialchars($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <textarea name="remarks" class="form-control mb-2" rows="2" placeholder="Status remarks"><?= htmlspecialchars($ipcrf['remarks'] ?: '') ?></textarea>
                    <button class="btn btn-primary w-100">Update Status</button>
                </form>
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
function postForm(form) {
  fetch('admin_query_ipcrf.php', {method: 'POST', body: new FormData(form)})
    .then(r => r.json())
    .then(res => {
      if (res.status !== 'success') {
        Swal.fire({icon: 'error', title: 'Action failed', text: res.message || 'Unable to save.'});
        return;
      }
      location.reload();
    });
}

document.getElementById('ratingForm').addEventListener('submit', function(e) {
  e.preventDefault();
  postForm(this);
});

document.getElementById('statusForm').addEventListener('submit', function(e) {
  e.preventDefault();
  postForm(this);
});
</script>
</body>
</html>


