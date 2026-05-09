<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/auth.php';
require_login(); require_role([1]);

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

try {

    // APPLY
    if($action==='apply'){
        $stmt = $pdo->prepare("
            INSERT INTO leave_applications
            (user_id, leave_type_id, date_from, date_to, days, reason, status)
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([
            $_POST['user_id'],
            $_POST['leave_type_id'],
            $_POST['date_from'],
            $_POST['date_to'],
            $_POST['days'],
            $_POST['reason'] ?? null
        ]);
        echo json_encode(["status"=>"success"]);
        exit;
    }

    // GET (for view)
    if($action==='get'){
        $stmt = $pdo->prepare("
            SELECT a.*, t.leave_name,
                   CONCAT(u.first_name,' ',u.last_name) AS employee
            FROM leave_applications a
            LEFT JOIN leave_types t ON t.leave_type_id=a.leave_type_id
            LEFT JOIN sdopang1_user u ON u.user_id=a.user_id
            WHERE a.application_id=?
        ");
        $stmt->execute([$_POST['application_id']]);
        echo json_encode(["status"=>"success","data"=>$stmt->fetch(PDO::FETCH_ASSOC)]);
        exit;
    }

    // APPROVE (rule-driven, no year)
    if($action==='approve'){
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT * FROM leave_applications WHERE application_id=?");
        $stmt->execute([$_POST['application_id']]);
        $app = $stmt->fetch();

        // get type
        $t = $pdo->prepare("SELECT * FROM leave_types WHERE leave_type_id=?");
        $t->execute([$app['leave_type_id']]);
        $type = $t->fetch();

        // if credit-based, check balance
        if($type['is_credit_based']){
            $b = $pdo->prepare("SELECT * FROM leave_balances WHERE user_id=? AND leave_type_id=?");
            $b->execute([$app['user_id'],$app['leave_type_id']]);
            $bal = $b->fetch();

            if(!$bal || $bal['balance'] < $app['days']){
                throw new Exception("Insufficient balance");
            }

            $new = $bal['balance'] - $app['days'];

            $pdo->prepare("
                UPDATE leave_balances SET balance=?, last_updated=NOW()
                WHERE balance_id=?
            ")->execute([$new,$bal['balance_id']]);

            $pdo->prepare("
                INSERT INTO leave_transactions
                (user_id, leave_type_id, transaction_type, days, balance_after, source, reference_id, remarks)
                VALUES (?, ?, 'deduct', ?, ?, 'application', ?, 'Approved')
            ")->execute([
                $app['user_id'], $app['leave_type_id'], $app['days'], $new, $app['application_id']
            ]);
        }

        $pdo->prepare("
            UPDATE leave_applications 
            SET status='approved', approved_by=?, approved_at=NOW()
            WHERE application_id=?
        ")->execute([$_SESSION['user_id'], $_POST['application_id']]);

        $pdo->commit();
        echo json_encode(["status"=>"success"]);
        exit;
    }

    // REJECT
    if($action==='reject'){
        $pdo->prepare("
            UPDATE leave_applications 
            SET status='rejected', approved_by=?, approved_at=NOW()
            WHERE application_id=?
        ")->execute([$_SESSION['user_id'], $_POST['application_id']]);

        echo json_encode(["status"=>"success"]);
        exit;
    }

    echo json_encode(["status"=>"error","message"=>"Invalid action"]);

} catch(Throwable $e){
    echo json_encode(["status"=>"error","message"=>$e->getMessage()]);
}