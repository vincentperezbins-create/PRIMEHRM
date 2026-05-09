<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

require_login();
require_role([1]);
header('Content-Type: application/json');

$action=$_POST['action']??'';

if($action==='add'){
$stmt=$pdo->prepare("INSERT INTO sdopang1_district (district_name, congID) VALUES (?,?)");
$stmt->execute([$_POST['district_name'],$_POST['congID']]);
echo json_encode(["status"=>"success"]);
}

elseif($action==='update'){
$stmt=$pdo->prepare("UPDATE sdopang1_district SET district_name=?, congID=? WHERE districtID=?");
$stmt->execute([$_POST['district_name'],$_POST['congID'],$_POST['districtID']]);
echo json_encode(["status"=>"success"]);
}

elseif($action==='delete'){
$stmt=$pdo->prepare("DELETE FROM sdopang1_district WHERE districtID=?");
$stmt->execute([$_POST['districtID']]);
echo json_encode(["status"=>"success"]);
}

elseif($action==='get'){

    $stmt = $pdo->prepare("
        SELECT 
            d.*,
            c.cong_name
        FROM sdopang1_district d
        LEFT JOIN sdopang1_cong c ON c.congID = d.congID
        WHERE d.districtID = ?
    ");

    $stmt->execute([$_POST['districtID']]);

    echo json_encode([
        "status" => "success",
        "data" => $stmt->fetch(PDO::FETCH_ASSOC)
    ]);
}