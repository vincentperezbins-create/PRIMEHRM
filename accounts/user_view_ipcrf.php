<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

$userModel = new User($pdo);
require_login();
require_role([1, 2, 3, 4, 5, 6, 7]);
require_once __DIR__ . '/partials/session.php';

$ipcrfId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$ipcrfId) {
    die('Invalid request');
}

$stmt = $pdo->prepare("
    SELECT i.*, CONCAT(u.first_name, ' ', u.last_name) AS employee_name, u.employeeID
    FROM sdopang1_ipcrf i
    JOIN sdopang1_user u ON u.user_id = i.user_id
    WHERE i.ipcrf_id = ? AND i.user_id = ?
");
$stmt->execute([$ipcrfId, $_SESSION['user_id']]);
$ipcrf = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ipcrf) {
    die('IPCRF not found or access denied');
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
            <p class="text-700 mb-0"><?= htmlspecialchars($ipcrf['school_year'] . ' / ' . $ipcrf['rating_period']) ?></p>
        </div>
        <a href="user_ipcrf_list.php" class="btn btn-outline-primary">Back to My IPCRF</a>
    </div>

    <div class="card-box pd-20 mb-20">
        <div class="row">
            <div class="col-md-3 mb-2"><p class="text-700 mb-1">Status</p><h6><?= htmlspecialchars($ipcrf['status']) ?></h6></div>
            <div class="col-md-3 mb-2"><p class="text-700 mb-1">Rating</p><h6><?= $ipcrf['overall_rating'] !== null ? htmlspecialchars(number_format((float) $ipcrf['overall_rating'], 2)) : '-' ?></h6></div>
            <div class="col-md-3 mb-2"><p class="text-700 mb-1">Date Prepared</p><h6><?= htmlspecialchars($ipcrf['date_prepared'] ?: '-') ?></h6></div>
            <div class="col-md-3 mb-2"><p class="text-700 mb-1">Remarks</p><h6><?= htmlspecialchars($ipcrf['remarks'] ?: '-') ?></h6></div>
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

