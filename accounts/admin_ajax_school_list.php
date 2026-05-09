<?php
// 🔒 CLEAN OUTPUT
if (ob_get_level()) ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';

require_login();
require_role([1]);

// ❌ DO NOT SHOW ERRORS IN JSON
error_reporting(0);
ini_set('display_errors', 0);

// 📥 DataTables params
$draw   = $_POST['draw'] ?? 1;
$start  = $_POST['start'] ?? 0;
$length = $_POST['length'] ?? 10;
$search = $_POST['search']['value'] ?? '';

$where = "";
$params = [];

try {

    // 🔍 SEARCH (FIXED HY093)
    if (!empty($search)) {
        $where = " WHERE 
            s.schoolID LIKE :s1 OR
            s.schoolname LIKE :s2 OR
            s.schooladdress LIKE :s3 OR
            d.district_name LIKE :s4 OR
            CONCAT(u.first_name, ' ', u.last_name) LIKE :s5
        ";

        $params = [
            ':s1' => "%$search%",
            ':s2' => "%$search%",
            ':s3' => "%$search%",
            ':s4' => "%$search%",
            ':s5' => "%$search%"
        ];
    }

    // 🔢 TOTAL RECORDS
    $total = $pdo->query("SELECT COUNT(*) FROM sdopang1schoollist")->fetchColumn();

    // 🔢 FILTERED RECORDS
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM sdopang1schoollist s
        LEFT JOIN sdopang1_district d ON d.districtID = s.district
        LEFT JOIN sdopang1_user u ON u.user_id = s.principalID
        $where
    ");
    $stmt->execute($params);
    $filtered = $stmt->fetchColumn();

    // 📄 MAIN QUERY
    $sql = "
    SELECT 
        s.schoolID,
        s.schoolname,
        s.schooladdress,
        d.district_name,
        CONCAT(u.first_name, ' ', u.last_name) AS principal_name
    FROM sdopang1schoollist s
    LEFT JOIN sdopang1_district d ON d.districtID = s.district
    LEFT JOIN sdopang1_user u ON u.user_id = s.principalID
    $where
    ORDER BY s.schoolname ASC
    LIMIT :length OFFSET :start
    ";

    $stmt = $pdo->prepare($sql);

    // 🔗 bind search
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, PDO::PARAM_STR);
    }

    // 🔗 bind pagination
    $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
    $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);

    $stmt->execute();

    $data = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data[] = [
            "schoolID" => $row['schoolID'],
            "schoolname" => $row['schoolname'],
            "district" => $row['district_name'] ?? '',
            "address" => $row['schooladdress'] ?? '',
            "principal_name" => $row['principal_name'] ?? '',
            "action" => '
                <button class="btn btn-info btn-sm openModal" data-id="'.$row['schoolID'].'" data-action="View">View</button>
                <button class="btn btn-warning btn-sm openModal" data-id="'.$row['schoolID'].'" data-action="Update">Update</button>
                <button class="btn btn-danger btn-sm btnDelete" data-id="'.$row['schoolID'].'">Delete</button>
            '
        ];
    }

    // 📦 JSON RESPONSE
    echo json_encode([
        "draw" => intval($draw),
        "recordsTotal" => intval($total),
        "recordsFiltered" => intval($filtered),
        "data" => $data
    ]);

} catch (Throwable $e) {

    // ❌ NEVER BREAK JSON
    echo json_encode([
        "draw" => intval($draw),
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "data" => [],
        "error" => $e->getMessage()
    ]);
}

exit;