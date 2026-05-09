<?php
$currentUser = $userModel->getUserById($_SESSION['user_id']);
$role = $userModel->getRoleById($currentUser['role_id']);
?>