<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

$userModel = new User($pdo);
require_login();
require_role([1, 2, 3, 4, 5, 6, 7]);
require_once __DIR__ . '/partials/session.php';

$stmt = $pdo->prepare("
    SELECT *
    FROM sdopang1_ipcrf
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$ipcrfs = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            <h2>My IPCRF</h2>
            <p class="text-700 mb-0">Individual Performance Commitment and Review Form submissions.</p>
        </div>
        <button class="btn btn-primary" data-toggle="modal" data-target="#ipcrfModal">Submit IPCRF</button>
    </div>

    <div class="card-box pd-20">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Period</th>
                        <th>Status</th>
                        <th>Rating</th>
                        <th>Submitted</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ipcrfs as $ipcrf): ?>
                        <tr>
                            <td><?= htmlspecialchars($ipcrf['title']) ?></td>
                            <td><?= htmlspecialchars($ipcrf['school_year'] . ' / ' . $ipcrf['rating_period']) ?></td>
                            <td><?= htmlspecialchars($ipcrf['status']) ?></td>
                            <td><?= $ipcrf['overall_rating'] !== null ? htmlspecialchars(number_format((float) $ipcrf['overall_rating'], 2)) : '-' ?></td>
                            <td><?= htmlspecialchars((string) $ipcrf['created_at']) ?></td>
                            <td><a class="btn btn-sm btn-primary" href="user_view_ipcrf.php?id=<?= urlencode((string) $ipcrf['ipcrf_id']) ?>">Open</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$ipcrfs): ?>
                        <tr><td colspan="6" class="text-center text-muted">No IPCRF submitted yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="ipcrfModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white">Submit IPCRF</h5>
                    <button class="btn-close" data-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="ipcrfForm" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label>Title</label>
                                <input name="title" class="form-control" required placeholder="Individual Performance Commitment and Review Form">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label>School Year</label>
                                <input name="school_year" class="form-control" required placeholder="2025-2026">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label>Rating Period</label>
                                <select name="rating_period" class="form-control" required>
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
                            <div class="col-12 mb-2">
                                <label>Individual Targets</label>
                                <textarea name="employee_targets" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="col-12 mb-2">
                                <label>Individual Accomplishments</label>
                                <textarea name="employee_accomplishments" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="col-12 mb-2">
                                <label>Individual Indicators</label>
                                <textarea name="employee_indicators" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="col-12 mb-3">
                                <label>Remarks</label>
                                <textarea name="remarks" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                        <button class="btn btn-primary w-100">Submit IPCRF</button>
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
document.getElementById('ipcrfForm').addEventListener('submit', function(e) {
  e.preventDefault();
  fetch('user_query_ipcrf.php', {method: 'POST', body: new FormData(this)})
    .then(r => r.json())
    .then(res => {
      if (res.status !== 'success') {
        Swal.fire({icon: 'error', title: 'Save failed', text: res.message || 'Unable to submit IPCRF.'});
        return;
      }
      window.location.href = 'user_view_ipcrf.php?id=' + res.ipcrf_id;
    });
});
</script>
</body>
</html>

