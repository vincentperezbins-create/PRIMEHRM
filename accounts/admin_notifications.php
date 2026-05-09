<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/csrf.php';

$userModel = new User($pdo);
require_login();
require_role([1]);
require_once __DIR__ . '/partials/session.php';

$token = generateToken();
$roles = $pdo->query("SELECT role_id, role_name FROM sdopang1_roles ORDER BY role_id")->fetchAll(PDO::FETCH_ASSOC);
$users = $pdo->query("SELECT user_id, first_name, last_name FROM sdopang1_user ORDER BY last_name, first_name LIMIT 500")->fetchAll(PDO::FETCH_ASSOC);
$recent = $pdo->query("
    SELECT n.*, r.role_name, CONCAT(u.first_name, ' ', u.last_name) AS user_name
    FROM sdopang1_notifications n
    LEFT JOIN sdopang1_roles r ON r.role_id = n.role_id
    LEFT JOIN sdopang1_user u ON u.user_id = n.user_id
    ORDER BY n.created_at DESC
    LIMIT 20
")->fetchAll(PDO::FETCH_ASSOC);
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
    <div class="pb-20">
        <h2>Notifications</h2>
        <p class="text-700 mb-0">Create database notifications for all users, a role, or a specific user.</p>
    </div>

    <div class="row">
        <div class="col-lg-5 mb-20">
            <div class="card-box pd-20">
                <h5 class="mb-3">Create Notification</h5>
                <form method="POST" action="admin_notifications_save.php">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="mb-2">
                        <label>Send To</label>
                        <select id="targetType" name="target_type" class="form-control">
                            <option value="all">All users</option>
                            <option value="role">Role</option>
                            <option value="user">Specific user</option>
                        </select>
                    </div>
                    <div class="mb-2 target-role d-none">
                        <label>Role</label>
                        <select name="role_id" class="form-control">
                            <option value="">Select role</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= htmlspecialchars((string) $role['role_id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($role['role_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2 target-user d-none">
                        <label>User</label>
                        <select name="user_id" class="form-control">
                            <option value="">Select user</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= htmlspecialchars((string) $user['user_id'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($user['last_name'] . ', ' . $user['first_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label>Title</label>
                        <input name="title" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Message</label>
                        <textarea name="message" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Link</label>
                        <input name="link" class="form-control" placeholder="Example: user_201_tables.php">
                    </div>
                    <button class="btn btn-primary w-100">Send Notification</button>
                </form>
            </div>
        </div>

        <div class="col-lg-7 mb-20">
            <div class="card-box pd-20">
                <h5 class="mb-3">Recent Notifications</h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead><tr><th>Title</th><th>Target</th><th>Read</th><th>Created</th></tr></thead>
                        <tbody>
                            <?php foreach ($recent as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['title']) ?></td>
                                    <td><?= htmlspecialchars($row['user_name'] ?: ($row['role_name'] ?: 'All users')) ?></td>
                                    <td><?= (int) $row['is_read'] === 1 ? 'Yes' : 'No' ?></td>
                                    <td><?= htmlspecialchars((string) $row['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$recent): ?>
                                <tr><td colspan="4" class="text-center text-muted">No notifications yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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
<script>
function refreshTargetFields() {
    const type = document.getElementById('targetType').value;
    document.querySelector('.target-role').classList.toggle('d-none', type !== 'role');
    document.querySelector('.target-user').classList.toggle('d-none', type !== 'user');
}
document.getElementById('targetType').addEventListener('change', refreshTargetFields);
refreshTargetFields();
</script>
</body>
</html>
