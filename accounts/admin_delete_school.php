<?php
require_once __DIR__ . '/../core/db.php';

$id = $_GET['id'];

$stmt = $pdo->prepare("DELETE FROM sdopang1schoollist WHERE schoolID = ?");
$stmt->execute([$id]);

echo "success";