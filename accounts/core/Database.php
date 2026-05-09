<?php

class Database {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // generic count function
    public function count($table, $where = "") {
        $sql = "SELECT COUNT(*) as total FROM $table";

        if ($where != "") {
            $sql .= " WHERE $where";
        }

        $stmt = $this->pdo->query($sql);
        $result = $stmt->fetch();

        return $result['total'];
    }

    // get all data
    public function getAll($table) {
        $stmt = $this->pdo->query("SELECT * FROM $table");
        return $stmt->fetchAll();
    }

    // get by condition
    public function getWhere($table, $column, $value) {
        $stmt = $this->pdo->prepare("SELECT * FROM $table WHERE $column = ?");
        $stmt->execute([$value]);
        return $stmt->fetchAll();
    }

    //count my uploads 201 files
    public function countWhere($table, $column, $value) {
    $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM $table WHERE $column = ?");
    $stmt->execute([$value]);
    return $stmt->fetch()['total'];
    }
    // $myUploads = $db->countWhere("sdopang1_documents", "user_id", $user_id);
    // $pending = $db->countWhere("sdopang1_documents", "status", "Pending");
    // $approved = $db->countWhere("sdopang1_documents", "status", "Approved");





}