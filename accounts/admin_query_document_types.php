<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
header('Content-Type: application/json');

require_login();
require_role([1]);

$action=$_POST['action']??'';

if($action==='add'){
$docName = trim($_POST['doc_name'] ?? '');
$isRequired = isset($_POST['is_required']) && (int) $_POST['is_required'] === 1 ? 1 : 0;

if ($docName === '') {
echo json_encode(["status"=>"error","message"=>"Document name is required"]);
exit;
}

$stmt=$pdo->prepare("INSERT INTO sdopang1_document_types (doc_name,is_required) VALUES (?,?)");
$stmt->execute([$docName,$isRequired]);
echo json_encode(["status"=>"success"]);
}

elseif($action==='update'){
$docName = trim($_POST['doc_name'] ?? '');
$isRequired = isset($_POST['is_required']) && (int) $_POST['is_required'] === 1 ? 1 : 0;
$docTypeId = filter_input(INPUT_POST, 'doc_type_id', FILTER_VALIDATE_INT);

if (!$docTypeId || $docName === '') {
echo json_encode(["status"=>"error","message"=>"Document type and name are required"]);
exit;
}

$stmt=$pdo->prepare("UPDATE sdopang1_document_types SET doc_name=?, is_required=? WHERE doc_type_id=?");
$stmt->execute([$docName,$isRequired,$docTypeId]);
echo json_encode(["status"=>"success"]);
}

elseif($action==='delete'){
$docTypeId = filter_input(INPUT_POST, 'doc_type_id', FILTER_VALIDATE_INT);
if (!$docTypeId) {
echo json_encode(["status"=>"error","message"=>"Document type is required"]);
exit;
}
$stmt=$pdo->prepare("DELETE FROM sdopang1_document_types WHERE doc_type_id=?");
$stmt->execute([$docTypeId]);
echo json_encode(["status"=>"success"]);
}

elseif($action==='get'){
$docTypeId = filter_input(INPUT_POST, 'doc_type_id', FILTER_VALIDATE_INT);
if (!$docTypeId) {
echo json_encode(["status"=>"error","message"=>"Document type is required"]);
exit;
}
$stmt=$pdo->prepare("SELECT * FROM sdopang1_document_types WHERE doc_type_id=?");
$stmt->execute([$docTypeId]);
echo json_encode(["status"=>"success","data"=>$stmt->fetch()]);
}
else{
echo json_encode(["status"=>"error","message"=>"Invalid action"]);
}
