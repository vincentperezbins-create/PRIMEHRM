<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/partials/page_info.php';

$userModel = new User($pdo);
require_login();

$canValidate201 = user_can_validate($pdo, '201');
$canValidateOpcrf = user_can_validate_division_opcrf($pdo);
$canValidateIpcrf = user_can_validate($pdo, 'ipcrf');
$canValidateLeave = user_can_validate($pdo, 'leave');
$hasValidatorTasks = $canValidate201 || $canValidateOpcrf || $canValidateIpcrf || $canValidateLeave;

if (!$hasValidatorTasks) {
    access_denied('/PRIMEHR/accounts/index.php');
}

require_once __DIR__ . '/partials/session.php';

function dashboard_count(PDO $pdo, string $sql, array $params = []): int {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function dashboard_recent(PDO $pdo, string $sql, array $params = []): array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$taskDashboards = [];

if ($canValidate201) {
    $scope201 = user_area_validation_scope($pdo, '201');
    $join201 = '';
    $where201 = '';
    $params201 = [];
    $recentWhere201 = '';
    $recentParams201 = [];

    if ($scope201 === 'school') {
        $currentValidator = current_user_row($pdo);
        $join201 = ' JOIN sdopang1_user u ON u.user_id = d.user_id';
        $where201 = ' WHERE u.school_id = ?';
        $params201 = [(string) ($currentValidator['school_id'] ?? '')];
        $recentWhere201 = ' WHERE u.school_id = ?';
        $recentParams201 = $params201;
    }

    $taskDashboards[] = [
        'key' => '201',
        'title' => '201 Files Validator Dashboard',
        'description' => 'Monitor uploaded 201 files and prioritize pending validations.',
        'href' => 'admin_201_tables.php',
        'button' => 'Open 201 Files',
        'icon' => 'bi bi-folder-check',
        'cards' => [
            ['Total Files', dashboard_count($pdo, 'SELECT COUNT(*) FROM sdopang1_documents d' . $join201 . $where201, $params201), 'primary'],
            ['Pending', dashboard_count($pdo, 'SELECT COUNT(*) FROM sdopang1_documents d' . $join201 . ($where201 ? $where201 . ' AND d.status = ?' : ' WHERE d.status = ?'), array_merge($params201, ['Pending'])), 'warning'],
            ['Approved', dashboard_count($pdo, 'SELECT COUNT(*) FROM sdopang1_documents d' . $join201 . ($where201 ? $where201 . ' AND d.status = ?' : ' WHERE d.status = ?'), array_merge($params201, ['Approved'])), 'success'],
            ['Returned', dashboard_count($pdo, 'SELECT COUNT(*) FROM sdopang1_documents d' . $join201 . ($where201 ? $where201 . ' AND d.status = ?' : ' WHERE d.status = ?'), array_merge($params201, ['Returned'])), 'danger'],
        ],
        'recent_title' => 'Recent 201 Uploads',
        'recent' => dashboard_recent($pdo, "
            SELECT d.document_id AS id,
                   d.status,
                   d.uploaded_at AS date_text,
                   t.doc_name AS main_text,
                   TRIM(CONCAT(u.first_name, ' ', COALESCE(NULLIF(u.middle_name, ''), ''), IF(u.middle_name IS NULL OR u.middle_name = '', '', ' '), u.last_name)) AS sub_text
            FROM sdopang1_documents d
            JOIN sdopang1_user u ON u.user_id = d.user_id
            JOIN sdopang1_document_types t ON t.doc_type_id = d.doc_type_id
            " . $recentWhere201 . "
            ORDER BY d.uploaded_at DESC, d.document_id DESC
            LIMIT 6
        ", $recentParams201),
    ];
}

if ($canValidateOpcrf) {
    $taskDashboards[] = [
        'key' => 'opcrf',
        'title' => 'OPCRF Validator Dashboard',
        'description' => 'Track office and unit OPCRF records by validation status.',
        'href' => 'admin_opcrf_list.php',
        'button' => 'Open OPCRF',
        'icon' => 'bi bi-building-check',
        'cards' => [
            ['Total OPCRF', dashboard_count($pdo, 'SELECT COUNT(*) FROM sdopang1_opcrf'), 'primary'],
            ['For Review', dashboard_count($pdo, 'SELECT COUNT(*) FROM sdopang1_opcrf WHERE status = ?', ['For Review']), 'warning'],
            ['Approved', dashboard_count($pdo, 'SELECT COUNT(*) FROM sdopang1_opcrf WHERE status = ?', ['Approved']), 'success'],
            ['Returned', dashboard_count($pdo, 'SELECT COUNT(*) FROM sdopang1_opcrf WHERE status = ?', ['Returned']), 'danger'],
        ],
        'recent_title' => 'Recent OPCRF Records',
        'recent' => dashboard_recent($pdo, "
            SELECT o.opcrf_id AS id,
                   o.status,
                   CONCAT('SY ', COALESCE(o.school_year, '-'), ' / ', COALESCE(o.quarter, '-')) AS date_text,
                   o.title AS main_text,
                   COALESCE(ou.office_name, 'Office / Unit') AS sub_text
            FROM sdopang1_opcrf o
            LEFT JOIN sdopang1_offices ou ON ou.office_id = o.office_id
            ORDER BY o.updated_at DESC, o.created_at DESC, o.opcrf_id DESC
            LIMIT 6
        "),
    ];
}

if ($canValidateIpcrf) {
    $ipcrfScope = user_ipcrf_validation_scope($pdo);
    $ipcrfCountJoin = '';
    $ipcrfCountWhere = '';
    $ipcrfCountParams = [];
    $ipcrfRecentWhere = '';
    $ipcrfRecentParams = [];

    if ($ipcrfScope === 'school') {
        $currentValidator = current_user_row($pdo);
        $ipcrfCountJoin = ' JOIN sdopang1_user u ON u.user_id = i.user_id';
        $ipcrfCountWhere = ' WHERE u.school_id = ?';
        $ipcrfCountParams = [(string) ($currentValidator['school_id'] ?? '')];
        $ipcrfRecentWhere = ' WHERE u.school_id = ?';
        $ipcrfRecentParams = $ipcrfCountParams;
    }

    $taskDashboards[] = [
        'key' => 'ipcrf',
        'title' => 'IPCRF Validator Dashboard',
        'description' => 'Review individual employee IPCRF submissions and status movement.',
        'href' => 'admin_ipcrf_list.php',
        'button' => 'Open IPCRF',
        'icon' => 'bi bi-clipboard-data',
        'cards' => [
            ['Total IPCRF', dashboard_count($pdo, 'SELECT COUNT(*) FROM sdopang1_ipcrf i' . $ipcrfCountJoin . $ipcrfCountWhere, $ipcrfCountParams), 'primary'],
            ['For Review', dashboard_count($pdo, 'SELECT COUNT(*) FROM sdopang1_ipcrf i' . $ipcrfCountJoin . ($ipcrfCountWhere ? $ipcrfCountWhere . ' AND i.status = ?' : ' WHERE i.status = ?'), array_merge($ipcrfCountParams, ['For Review'])), 'warning'],
            ['Approved', dashboard_count($pdo, 'SELECT COUNT(*) FROM sdopang1_ipcrf i' . $ipcrfCountJoin . ($ipcrfCountWhere ? $ipcrfCountWhere . ' AND i.status = ?' : ' WHERE i.status = ?'), array_merge($ipcrfCountParams, ['Approved'])), 'success'],
            ['Returned', dashboard_count($pdo, 'SELECT COUNT(*) FROM sdopang1_ipcrf i' . $ipcrfCountJoin . ($ipcrfCountWhere ? $ipcrfCountWhere . ' AND i.status = ?' : ' WHERE i.status = ?'), array_merge($ipcrfCountParams, ['Returned'])), 'danger'],
        ],
        'recent_title' => 'Recent IPCRF Submissions',
        'recent' => dashboard_recent($pdo, "
            SELECT i.ipcrf_id AS id,
                   i.status,
                   CONCAT('SY ', COALESCE(i.school_year, '-'), ' / ', COALESCE(i.rating_period, '-')) AS date_text,
                   i.title AS main_text,
                   TRIM(CONCAT(u.first_name, ' ', COALESCE(NULLIF(u.middle_name, ''), ''), IF(u.middle_name IS NULL OR u.middle_name = '', '', ' '), u.last_name)) AS sub_text
            FROM sdopang1_ipcrf i
            LEFT JOIN sdopang1_user u ON u.user_id = i.user_id
            " . $ipcrfRecentWhere . "
            ORDER BY i.updated_at DESC, i.created_at DESC, i.ipcrf_id DESC
            LIMIT 6
        ", $ipcrfRecentParams),
    ];
}

if ($canValidateLeave) {
    $leaveScope = user_area_validation_scope($pdo, 'leave');
    $leaveJoin = '';
    $leaveWhere = '';
    $leaveParams = [];
    $leaveRecentWhere = '';
    $leaveRecentParams = [];

    if ($leaveScope === 'school') {
        $currentValidator = current_user_row($pdo);
        $leaveJoin = ' JOIN sdopang1_user u ON u.user_id = la.user_id';
        $leaveWhere = ' WHERE u.school_id = ?';
        $leaveParams = [(string) ($currentValidator['school_id'] ?? '')];
        $leaveRecentWhere = ' WHERE u.school_id = ?';
        $leaveRecentParams = $leaveParams;
    }

    $taskDashboards[] = [
        'key' => 'leave',
        'title' => 'Leave Validator Dashboard',
        'description' => 'Monitor leave requests, pending approvals, and recent application activity.',
        'href' => 'admin_leave_applications.php',
        'button' => 'Open Leave Applications',
        'icon' => 'bi bi-calendar-check',
        'cards' => [
            ['Total Leave', dashboard_count($pdo, 'SELECT COUNT(*) FROM leave_applications la' . $leaveJoin . $leaveWhere, $leaveParams), 'primary'],
            ['Pending', dashboard_count($pdo, 'SELECT COUNT(*) FROM leave_applications la' . $leaveJoin . ($leaveWhere ? $leaveWhere . ' AND la.status = ?' : ' WHERE la.status = ?'), array_merge($leaveParams, ['pending'])), 'warning'],
            ['Approved', dashboard_count($pdo, 'SELECT COUNT(*) FROM leave_applications la' . $leaveJoin . ($leaveWhere ? $leaveWhere . ' AND la.status = ?' : ' WHERE la.status = ?'), array_merge($leaveParams, ['approved'])), 'success'],
            ['Rejected', dashboard_count($pdo, 'SELECT COUNT(*) FROM leave_applications la' . $leaveJoin . ($leaveWhere ? $leaveWhere . ' AND la.status = ?' : ' WHERE la.status = ?'), array_merge($leaveParams, ['rejected'])), 'danger'],
        ],
        'recent_title' => 'Recent Leave Applications',
        'recent' => dashboard_recent($pdo, "
            SELECT la.application_id AS id,
                   la.status,
                   CONCAT(la.date_from, ' to ', la.date_to) AS date_text,
                   lt.leave_name AS main_text,
                   TRIM(CONCAT(u.first_name, ' ', COALESCE(NULLIF(u.middle_name, ''), ''), IF(u.middle_name IS NULL OR u.middle_name = '', '', ' '), u.last_name)) AS sub_text
            FROM leave_applications la
            JOIN sdopang1_user u ON u.user_id = la.user_id
            JOIN leave_types lt ON lt.leave_type_id = la.leave_type_id
            " . $leaveRecentWhere . "
            ORDER BY la.created_at DESC
            LIMIT 6
        ", $leaveRecentParams),
    ];
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
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center pb-20">
        <div>
            <h2 class="mb-1">Validator Dashboard</h2>
            <p class="text-700 mb-0">Task dashboards are shown based on your assigned validation permissions.</p>
        </div>
        <a href="user_dashboard.php" class="btn btn-outline-primary mt-3 mt-lg-0">My Personal Dashboard</a>
    </div>

    <?php page_info(
        'What this page does',
        'This dashboard gives validators a quick view of pending work and recent activity.',
        [
            'Only validator tasks assigned to your account are displayed.',
            'Open each module to review, approve, return, or manage records.'
        ]
    ); ?>

    <?php if (!$hasValidatorTasks): ?>
        <div class="card-box pd-20">
            <h5 class="mb-1">No Validator Tasks Assigned</h5>
            <p class="text-700 mb-0">Your account does not currently have 201, OPCRF, IPCRF, or leave validation permission.</p>
        </div>
    <?php endif; ?>

    <?php foreach ($taskDashboards as $task): ?>
        <div class="card-box pd-20 mb-20">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3">
                <div>
                    <h4 class="mb-1"><i class="<?= htmlspecialchars($task['icon'], ENT_QUOTES, 'UTF-8') ?> mr-2"></i><?= htmlspecialchars($task['title']) ?></h4>
                    <p class="text-700 mb-0"><?= htmlspecialchars($task['description']) ?></p>
                </div>
                <a href="<?= htmlspecialchars($task['href'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary mt-3 mt-lg-0"><?= htmlspecialchars($task['button']) ?></a>
            </div>

            <div class="row pb-10">
                <?php foreach ($task['cards'] as $card): ?>
                    <div class="col-xl-3 col-lg-3 col-md-6 mb-20">
                        <div class="prime-stat-card stat-<?= htmlspecialchars($card[2], ENT_QUOTES, 'UTF-8') ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="stat-label"><?= htmlspecialchars($card[0]) ?></p>
                                    <p class="stat-value"><?= htmlspecialchars((string) $card[1]) ?></p>
                                </div>
                                <div class="stat-icon">
                                    <i class="<?= htmlspecialchars($task['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="table-responsive">
                <h5 class="mb-3"><?= htmlspecialchars($task['recent_title']) ?></h5>
                <table class="table table-bordered table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Record</th>
                            <th>Employee / Office</th>
                            <th>Date / Period</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($task['recent'] as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars((string) ($row['main_text'] ?? '-')) ?></td>
                                <td><?= htmlspecialchars((string) ($row['sub_text'] ?? '-')) ?></td>
                                <td><?= htmlspecialchars((string) ($row['date_text'] ?? '-')) ?></td>
                                <td><?= htmlspecialchars((string) ($row['status'] ?? '-')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$task['recent']): ?>
                            <tr><td colspan="4" class="text-center text-700">No recent records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
</div>
</div>

<script src="vendors/scripts/core.js"></script>
<script src="vendors/scripts/script.min.js"></script>
<script src="vendors/scripts/process.js"></script>
<script src="vendors/scripts/layout-settings.js"></script>
</body>
</html>
