<?php
require_once __DIR__ . '/../core/db.php';

// 1. Get all users
$stmt = $pdo->query("SELECT user_id FROM sdopang1_user where password LIKE '1234'");
$users = $stmt->fetchAll();

// 2. Loop and update password to hashed "1234"
foreach ($users as $user) {

    $hashed = password_hash("1234", PASSWORD_DEFAULT);

    $update = $pdo->prepare("
        UPDATE sdopang1_user 
        SET password = ? 
        WHERE user_id = ?
    ");

    $update->execute([$hashed, $user['user_id']]);
}

echo "All passwords updated to hashed 1234!";