<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/audit.php';

$userModel = new User($pdo);
require_login();
require_role([1]);
require_once __DIR__ . '/partials/session.php';

audit_ensure_table($pdo);

$h = static fn($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$search = trim((string) ($_GET['search'] ?? ''));
$userFilter = trim((string) ($_GET['user_id'] ?? ''));
$moduleFilter = trim((string) ($_GET['module_name'] ?? ''));
$actionFilter = trim((string) ($_GET['action_type'] ?? ''));
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(fullname LIKE ? OR module_name LIKE ? OR action_type LIKE ? OR record_id LIKE ? OR description LIKE ? OR ip_address LIKE ?)";
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like, $like, $like);
}

if ($userFilter !== '') {
    $where[] = "user_id = ?";
    $params[] = $userFilter;
}

if ($moduleFilter !== '') {
    $where[] = "module_name = ?";
    $params[] = $moduleFilter;
}

if ($actionFilter !== '') {
    $where[] = "action_type = ?";
    $params[] = $actionFilter;
}

if ($dateFrom !== '') {
    $where[] = "created_at >= ?";
    $params[] = $dateFrom . ' 00:00:00';
}

if ($dateTo !== '') {
    $where[] = "created_at <= ?";
    $params[] = $dateTo . ' 23:59:59';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$users = $pdo->query("
    SELECT user_id, MAX(fullname) AS fullname
    FROM audit_logs
    WHERE user_id IS NOT NULL
    GROUP BY user_id
    ORDER BY fullname
")->fetchAll(PDO::FETCH_ASSOC);

$modules = $pdo->query("SELECT DISTINCT module_name FROM audit_logs ORDER BY module_name")->fetchAll(PDO::FETCH_COLUMN);
$actions = $pdo->query("SELECT DISTINCT action_type FROM audit_logs ORDER BY action_type")->fetchAll(PDO::FETCH_COLUMN);

if (($_GET['export'] ?? '') === 'csv') {
    $stmt = $pdo->prepare("
        SELECT audit_id, user_id, fullname, action_type, module_name, record_id, description, ip_address, device_info, created_at
        FROM audit_logs
        $whereSql
        ORDER BY created_at DESC, audit_id DESC
    ");
    $stmt->execute($params);

    audit_log($pdo, $_SESSION['user_id'] ?? null, audit_current_fullname($pdo), 'EXPORT', 'Audit Logs', null, 'Exported audit logs to CSV.');

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="PRIMEHR_Audit_Logs_' . date('Ymd_His') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Audit ID', 'User ID', 'Fullname', 'Action', 'Module', 'Record ID', 'Description', 'IP Address', 'Device Info', 'Created At']);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs $whereSql");
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

$listStmt = $pdo->prepare("
    SELECT audit_id, user_id, fullname, action_type, module_name, record_id, description, ip_address, device_info, created_at
    FROM audit_logs
    $whereSql
    ORDER BY created_at DESC, audit_id DESC
    LIMIT $perPage OFFSET $offset
");
$listStmt->execute($params);
$logs = $listStmt->fetchAll(PDO::FETCH_ASSOC);

$buildUrl = static function (array $overrides = []): string {
    $query = array_merge($_GET, $overrides);
    foreach ($query as $key => $value) {
        if ($value === '' || $value === null) {
            unset($query[$key]);
        }
    }
    return 'admin_audit_logs.php?' . http_build_query($query);
};

$badgeClass = static function (string $action): string {
    $classes = [
        'CREATE' => 'audit-badge-create',
        'UPDATE' => 'audit-badge-update',
        'DELETE' => 'audit-badge-delete',
        'LOGIN' => 'audit-badge-login',
        'LOGOUT' => 'audit-badge-logout',
        'FAILED_LOGIN' => 'audit-badge-failed-login',
        'EXPORT' => 'audit-badge-export',
        'PRINT' => 'audit-badge-print',
        'UPLOAD' => 'audit-badge-upload',
        'DOWNLOAD' => 'audit-badge-download',
        'APPROVE' => 'audit-badge-create',
        'REJECT' => 'audit-badge-delete',
    ];

    return $classes[$action] ?? 'audit-badge-default';
};
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
<style>
  .audit-page {
    background: #f8fafc;
  }
  .audit-card {
    border: 1px solid #eef2f7;
    border-radius: 18px;
    background: #fff;
    box-shadow: 0 14px 34px rgba(15, 23, 42, .06);
  }
  .audit-filter {
    border-radius: 14px;
    border: 1px solid #e5e7eb;
    background: #f8fafc;
  }
  .audit-table {
    font-size: 13px;
  }
  .audit-table th {
    color: #475569;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .04em;
    text-transform: uppercase;
    background: #f8fafc;
    border-top: 0;
  }
  .audit-action-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 24px;
    padding: 4px 9px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: .02em;
  }
  .audit-badge-create,
  .audit-badge-login {
    color: #047857;
    background: #d1fae5;
  }
  .audit-badge-update {
    color: #1d4ed8;
    background: #dbeafe;
  }
  .audit-badge-delete {
    color: #b91c1c;
    background: #fee2e2;
  }
  .audit-badge-logout {
    color: #475569;
    background: #e2e8f0;
  }
  .audit-badge-failed-login {
    color: #92400e;
    background: #fef3c7;
  }
  .audit-badge-export {
    color: #0369a1;
    background: #e0f2fe;
  }
  .audit-badge-print {
    color: #111827;
    background: #e5e7eb;
  }
  .audit-badge-upload {
    color: #1d4ed8;
    background: #dbeafe;
  }
  .audit-badge-download {
    color: #6d28d9;
    background: #ede9fe;
  }
  .audit-badge-default {
    color: #334155;
    background: #f1f5f9;
  }
  .audit-description {
    max-width: 360px;
    white-space: normal;
  }
</style>
<div class="main-container audit-page">
  <div class="xs-pd-20-10 pd-ltr-20">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center pb-20">
      <div>
        <h2 class="mb-1">Audit Logs</h2>
        <p class="text-700 mb-0">Track login, updates, approvals, uploads, exports, and other important system actions.</p>
      </div>
      <a class="btn btn-success mt-3 mt-lg-0" href="<?= $h($buildUrl(['export' => 'csv', 'page' => null])) ?>">
        <i class="bi bi-file-earmark-excel"></i> Export CSV
      </a>
    </div>

    <div class="audit-card pd-20 mb-20">
      <form class="audit-filter p-3" method="GET">
        <div class="row">
          <div class="col-lg-3 col-md-6 mb-2">
            <label class="font-weight-bold">Search</label>
            <input class="form-control" name="search" value="<?= $h($search) ?>" placeholder="User, module, description">
          </div>
          <div class="col-lg-2 col-md-6 mb-2">
            <label class="font-weight-bold">User</label>
            <select class="form-control" name="user_id">
              <option value="">All users</option>
              <?php foreach ($users as $user): ?>
                <option value="<?= $h($user['user_id']) ?>" <?= $userFilter === (string) $user['user_id'] ? 'selected' : '' ?>>
                  <?= $h($user['fullname'] ?: ('User #' . $user['user_id'])) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-lg-2 col-md-6 mb-2">
            <label class="font-weight-bold">Module</label>
            <select class="form-control" name="module_name">
              <option value="">All modules</option>
              <?php foreach ($modules as $module): ?>
                <option value="<?= $h($module) ?>" <?= $moduleFilter === $module ? 'selected' : '' ?>><?= $h($module) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-lg-2 col-md-6 mb-2">
            <label class="font-weight-bold">Action</label>
            <select class="form-control" name="action_type">
              <option value="">All actions</option>
              <?php foreach ($actions as $action): ?>
                <option value="<?= $h($action) ?>" <?= $actionFilter === $action ? 'selected' : '' ?>><?= $h($action) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-lg-1 col-md-6 mb-2">
            <label class="font-weight-bold">From</label>
            <input class="form-control" type="date" name="date_from" value="<?= $h($dateFrom) ?>">
          </div>
          <div class="col-lg-1 col-md-6 mb-2">
            <label class="font-weight-bold">To</label>
            <input class="form-control" type="date" name="date_to" value="<?= $h($dateTo) ?>">
          </div>
          <div class="col-lg-1 col-md-12 mb-2 d-flex align-items-end">
            <button class="btn btn-primary btn-block" type="submit">Filter</button>
          </div>
        </div>
      </form>
    </div>

    <div class="audit-card pd-20">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <strong><?= number_format($totalRows) ?> log<?= $totalRows === 1 ? '' : 's' ?> found</strong>
        <a class="btn btn-sm btn-outline-secondary" href="admin_audit_logs.php">Reset</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover audit-table">
          <thead>
            <tr>
              <th>Date/Time</th>
              <th>User</th>
              <th>Action</th>
              <th>Module</th>
              <th>Record</th>
              <th>Description</th>
              <th>IP</th>
              <th>Device</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($logs as $log): ?>
              <tr>
                <td class="text-nowrap"><?= $h($log['created_at']) ?></td>
                <td>
                  <strong><?= $h($log['fullname'] ?: 'Guest') ?></strong><br>
                  <small class="text-muted"><?= $log['user_id'] ? 'ID: ' . $h($log['user_id']) : 'No user ID' ?></small>
                </td>
                <td><span class="audit-action-badge <?= $h($badgeClass($log['action_type'])) ?>"><?= $h($log['action_type']) ?></span></td>
                <td><?= $h($log['module_name']) ?></td>
                <td><?= $h($log['record_id'] ?: '-') ?></td>
                <td class="audit-description"><?= $h($log['description'] ?: '-') ?></td>
                <td><?= $h($log['ip_address'] ?: '-') ?></td>
                <td class="audit-description"><small><?= $h($log['device_info'] ?: '-') ?></small></td>
              </tr>
            <?php endforeach; ?>
            <?php if (!$logs): ?>
              <tr><td colspan="8" class="text-center text-muted py-4">No audit logs found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mt-3">
        <span class="text-muted mb-2 mb-md-0">Page <?= number_format($page) ?> of <?= number_format($totalPages) ?></span>
        <div>
          <a class="btn btn-sm btn-outline-primary <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= $h($buildUrl(['page' => max(1, $page - 1)])) ?>">Previous</a>
          <a class="btn btn-sm btn-outline-primary <?= $page >= $totalPages ? 'disabled' : '' ?>" href="<?= $h($buildUrl(['page' => min($totalPages, $page + 1)])) ?>">Next</a>
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
