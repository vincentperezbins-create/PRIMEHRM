<?php
require_once __DIR__ . '/../core/db.php';
$userModel = new User($pdo);

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first_name = $_POST['first_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role_id = $_POST['role_id'];

    // create user using your class
    $result = $userModel->createUser($first_name, $email, $password, $role_id);

    if ($result) {
        $message = "User created successfully!";
    } else {
        $message = "Failed to create user.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add User</title>
</head>
<body>

<h2>Create User</h2>

<p style="color:green;"><?= $message ?></p>

<form method="POST">

    <label>First Name</label><br>
    <input type="text" name="first_name" required><br><br>

    <label>Email</label><br>
    <input type="email" name="email" required><br><br>

    <label>Password</label><br>
    <input type="password" name="password" required><br><br>

    <label>Role</label><br>
    <select name="role_id">
        <option value="1">Admin</option>
        <option value="2">Program Owner</option>
        <option value="3">School Head</option>
        <option value="4">User</option>
    </select><br><br>

    <button type="submit">Create User</button>

</form>

</body>
</html>