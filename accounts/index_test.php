<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
$userModel = new User($pdo);
require_login();
?>

<nav>

<a href="/SMME/accounts/index.php">Home</a> <br>

<?php if ($_SESSION['role_id'] == 1): ?>
    <a href="/SMME/accounts/adduser.php">Add User (Admin)</a><br>
<?php endif; ?>

<?php if ($_SESSION['role_id'] == 4): ?>
    <a href="/SMME/accounts/addforms.php">Add Forms (Teacher)</a><br>
<?php endif; ?>

<?php if ($_SESSION['role_id'] == 3): ?>
    <a href="/SMME/accounts/validatingform.php">Validate Forms (School Head)</a><br>
<?php endif; ?>

<a href="/SMME/accounts/logout.php">Logout</a>

</nav>



<?php
// count users
$totalUsers = $db->count("sdopang1_user");
?>
<h3>Total Users: <?= $totalUsers ?></h3>

<?php
// count admins
$totalAdmins = $db->count("sdopang1_user", "role_id = 1");
?>
<h3>Total Admins: <?= $totalAdmins ?></h3>

<?php
//Get all users
// $users = $userModel->getAllUsers();
// foreach ($users as $u) {
//     echo $u['first_name'] . "<br>";
// }
?>

<?php
//Get user by ID
$user = $userModel->getUserById(id: 1);
echo $user['first_name'];
?>
<a href="deleteuser.php?id=<?= $user['user_id'] ?>">Delete</a>



<hr>