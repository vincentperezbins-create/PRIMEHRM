<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
$userModel = new User($pdo);
require_login();
require_role([1]);
require_once __DIR__ . '/partials/session.php';

$user_id = $_GET['user_id'] ?? 0;

// USER INFO
$stmt = $pdo->prepare("SELECT * FROM sdopang1_user WHERE user_id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// TRANSACTIONS
$stmt = $pdo->prepare("
    SELECT 
        t.*,
        lt.leave_name
    FROM leave_transactions t
    LEFT JOIN leave_types lt ON lt.leave_type_id = t.leave_type_id
    WHERE t.user_id = ?
    ORDER BY t.created_at ASC
");
$stmt->execute([$user_id]);
$transactions = $stmt->fetchAll();
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

    <!-- HEADER -->
    <div class="d-flex justify-content-between pb-20">
        <h2>
            Leave Card - 
            <?= $user ? $user['first_name'].' '.$user['last_name'] : 'Unknown User' ?>
        </h2>

        <a href="admin_leave_list.php" class="btn btn-secondary">
            Back
        </a>
    </div>

    <!-- TABLE -->
    <div class="card-box pd-20">
        <div class="table-responsive">

            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Leave Type</th>
                        <th>Earned</th>
                        <th>Used</th>
                        <th>Balance</th>
                        <th>Remarks</th>
                    </tr>
                </thead>

                <tbody>

                <?php if($transactions): ?>
                    <?php foreach($transactions as $t): ?>
                        <tr>
                            <td><?= date('Y-m-d', strtotime($t['created_at'])) ?></td>
                            <td><?= $t['leave_name'] ?></td>

                            <td>
                                <?= $t['transaction_type'] === 'credit' ? $t['days'] : '' ?>
                            </td>

                            <td>
                                <?= $t['transaction_type'] === 'deduct' ? $t['days'] : '' ?>
                            </td>

                            <td><?= $t['balance_after'] ?></td>

                            <td><?= $t['remarks'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">No records found</td>
                    </tr>
                <?php endif; ?>

                </tbody>
            </table>

        </div>
    </div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
</div>
</div>

</body>
</html>