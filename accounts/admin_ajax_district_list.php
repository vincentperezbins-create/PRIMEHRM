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
$where=" WHERE d.district_name LIKE :s1";
$params[':s1']="%$search%";
}

$total=$pdo->query("SELECT COUNT(*) FROM sdopang1_district")->fetchColumn();

$stmt=$pdo->prepare("SELECT COUNT(*) FROM sdopang1_district d $where");
$stmt->execute($params);
$filtered=$stmt->fetchColumn();

$sql="
SELECT d.*, c.cong_name
FROM sdopang1_district d
LEFT JOIN sdopang1_cong c ON c.congID=d.congID
$where
LIMIT :l OFFSET :s
";

$stmt=$pdo->prepare($sql);
foreach($params as $k=>$v) $stmt->bindValue($k,$v);
$stmt->bindValue(':s',(int)$start,PDO::PARAM_INT);
$stmt->bindValue(':l',(int)$length,PDO::PARAM_INT);
$stmt->execute();

$data=[];
while($r=$stmt->fetch()){
$data[]=[
"districtID"=>$r['districtID'],
"district_name"=>$r['district_name'],
"cong"=>$r['cong_name']??'',
"action"=>'
<button class="btn btn-info btn-sm openModal" data-id="'.$r['districtID'].'" data-action="View">View</button>
<button class="btn btn-warning btn-sm openModal" data-id="'.$r['districtID'].'" data-action="Update">Update</button>
<button class="btn btn-danger btn-sm btnDelete" data-id="'.$r['districtID'].'">Delete</button>
'
];
}

echo json_encode([
"draw"=>$draw,
"recordsTotal"=>$total,
"recordsFiltered"=>$filtered,
"data"=>$data
]);