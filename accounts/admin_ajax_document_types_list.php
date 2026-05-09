<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

require_login();
require_role([1]);

$draw=$_POST['draw']??1;
$start=$_POST['start']??0;
$length=$_POST['length']??10;
$search=$_POST['search']['value']??'';

$where="";
$params=[];

if($search){
$where=" WHERE doc_name LIKE :s1";
$params[':s1']="%$search%";
}

$total=$pdo->query("SELECT COUNT(*) FROM sdopang1_document_types")->fetchColumn();

$stmt=$pdo->prepare("SELECT COUNT(*) FROM sdopang1_document_types $where");
$stmt->execute($params);
$filtered=$stmt->fetchColumn();

$sql="SELECT * FROM sdopang1_document_types $where LIMIT :l OFFSET :s";
$stmt=$pdo->prepare($sql);

foreach($params as $k=>$v) $stmt->bindValue($k,$v);
$stmt->bindValue(':s',(int)$start,PDO::PARAM_INT);
$stmt->bindValue(':l',(int)$length,PDO::PARAM_INT);
$stmt->execute();

$data=[];
while($r=$stmt->fetch()){
$data[]=[
"doc_type_id"=>$r['doc_type_id'],
"doc_name"=>$r['doc_name'],
"is_required"=>$r['is_required']?'Yes':'No',
"action"=>'
<button class="btn btn-info btn-sm openModal" data-id="'.$r['doc_type_id'].'" data-action="View">View</button>
<button class="btn btn-warning btn-sm openModal" data-id="'.$r['doc_type_id'].'" data-action="Update">Update</button>
<button class="btn btn-danger btn-sm btnDelete" data-id="'.$r['doc_type_id'].'">Delete</button>
'
];
}

echo json_encode([
"draw"=>$draw,
"recordsTotal"=>$total,
"recordsFiltered"=>$filtered,
"data"=>$data
]);