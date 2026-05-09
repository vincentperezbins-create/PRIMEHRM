<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

require_login();
require_role([1]);

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

try {

    // 🔎 READ ONE (for view modal)
    if ($action === 'get') {
        $stmt = $pdo->prepare("
            SELECT s.*,
                   d.district_name,
                   CONCAT(u.first_name,' ',u.last_name) AS principal_name
            FROM sdopang1schoollist s
            LEFT JOIN sdopang1_district d ON d.districtID = s.district
            LEFT JOIN sdopang1_user u ON u.user_id = s.principalID
            WHERE s.schoolID = ?
        ");
        $stmt->execute([$_POST['schoolID']]);
        echo json_encode(["status"=>"success","data"=>$stmt->fetch(PDO::FETCH_ASSOC)]);
        exit;
    }

    // ➕ ADD
    if ($action === 'add') {

    $check = $pdo->prepare("SELECT COUNT(*) FROM sdopang1schoollist WHERE schoolID = ?");
    $check->execute([$_POST['schoolID']]);

    if ($check->fetchColumn() > 0) {
        echo json_encode(["status"=>"error","message"=>"School ID already exists"]);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO sdopang1schoollist
        (schoolID, schoolname, district, schooladdress, principalID)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $_POST['schoolID'],
        $_POST['schoolname'],
        $_POST['district'],
        $_POST['schooladdress'],
        $_POST['principalID']
    ]);

    echo json_encode(["status"=>"success"]);
    exit;
}

    // ✏️ UPDATE
    if ($action === 'update') {
        $stmt = $pdo->prepare("
            UPDATE sdopang1schoollist SET
                schoolname = ?,
                district = ?,
                schooladdress = ?,
                principalID = ?
            WHERE schoolID = ?
        ");
        $stmt->execute([
            $_POST['schoolname'],
            $_POST['district'] ?: null,
            $_POST['schooladdress'] ?? null,
            $_POST['principalID'] ?: null,
            $_POST['schoolID']
        ]);

        echo json_encode(["status"=>"success"]);
        exit;
    }

    // ❌ DELETE
    if ($action === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM sdopang1schoollist WHERE schoolID = ?");
        $stmt->execute([$_POST['schoolID']]);

        echo json_encode(["status"=>"success"]);
        exit;
    }

    echo json_encode(["status"=>"error","message"=>"Invalid action"]);

} catch (Throwable $e) {
    echo json_encode(["status"=>"error","message"=>$e->getMessage()]);
}