<?php

class User {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // 📄 GET ALL USERS
    public function getAllUsers() {
        $stmt = $this->pdo->query("SELECT * FROM sdopang1_user");
        return $stmt->fetchAll();
    }

    // 📄 GET ALL USERS WITH SCHOOL, DISTRICT, CONG
public function getAllUserswithschoolinfo() {
     $sql = "
        SELECT 
            *

        FROM sdopang1_user u

        LEFT JOIN sdopang1schoollist s 
            ON s.schoolID = u.school_id

        LEFT JOIN sdopang1_district d 
            ON d.district_name = s.district

        LEFT JOIN sdopang1_cong c 
            ON c.cong_name = s.cong

        LEFT JOIN sdopang1_position p
            ON p.position_id = u.position_id

        ORDER BY u.first_name ASC
    ";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

    // 📄 GET USER BY ID
    public function getUserById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM sdopang1_user WHERE user_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // ➕ CREATE USER
    public function createUser($first_name, $email, $password, $role_id) {

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
        $stmt = $this->pdo->prepare("
            INSERT INTO sdopang1_user (first_name, email, password, role_id)
            VALUES (?, ?, ?, ?)
        ");
    
        return $stmt->execute([
            $first_name,
            $email,
            $hashedPassword,
            $role_id
        ]);
    }

    public function updateUser($id, $first_name, $email, $role_id, $password = null) {

    // if password is provided → update it
    if (!empty($password)) {

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare("
            UPDATE sdopang1_user 
            SET first_name = ?, email = ?, role_id = ?, password = ?
            WHERE user_id = ?
        ");

        return $stmt->execute([
            $first_name,
            $email,
            $role_id,
            $hashedPassword,
            $id
        ]);

    } else {

        // no password change
        $stmt = $this->pdo->prepare("
            UPDATE sdopang1_user 
            SET first_name = ?, email = ?, role_id = ?
            WHERE user_id = ?
        ");

        return $stmt->execute([
            $first_name,
            $email,
            $role_id,
            $id
        ]);
    }
}

public function deleteUser($id) {
    $stmt = $this->pdo->prepare("DELETE FROM sdopang1_user WHERE user_id = ?");
    return $stmt->execute([$id]);
}

public function getRoleById($id) {
    $stmt = $this->pdo->prepare("SELECT * FROM sdopang1_roles WHERE role_id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

//ADMIN HERE------------------------------------------------------------------------------------------------------

 // 📄 GET ALL 201
    public function getAll201() {
        $stmt = $this->pdo->query("SELECT d.*, u.first_name, u.last_name, t.doc_name 
        FROM sdopang1_documents d
        JOIN sdopang1_user u ON d.user_id = u.user_id
        JOIN sdopang1_document_types t ON d.doc_type_id = t.doc_type_id
        ORDER BY d.uploaded_at DESC");
        return $stmt->fetchAll();
    }

// 📄 GET ALL Document types
public function getAllDocumenttypes() {
    $stmt = $this->pdo->query("SELECT * FROM sdopang1_document_types");
    return $stmt->fetchAll();
}


// EMPLOYEE HERE----------------------------------------------------------------------------------------------------

// 📄 GET MY 201
public function getMy201($user_id) {
    $stmt = $this->pdo->prepare("
        SELECT d.*, u.first_name, u.last_name, t.doc_name 
        FROM sdopang1_documents d
        JOIN sdopang1_user u ON d.user_id = u.user_id
        JOIN sdopang1_document_types t ON d.doc_type_id = t.doc_type_id
        WHERE d.user_id = ?
        ORDER BY d.uploaded_at DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

// 📊 GET USER 201 PROGRESS
public function get201Progress($user_id, $onlyApproved = false) {

    // total required documents
    $stmtTotal = $this->pdo->query("
        SELECT COUNT(*) as total 
        FROM sdopang1_document_types
    ");
    $totalDocs = $stmtTotal->fetch()['total'];

    // uploaded documents
    if ($onlyApproved) {
        $stmtUser = $this->pdo->prepare("
            SELECT COUNT(DISTINCT doc_type_id) as total 
            FROM sdopang1_documents 
            WHERE user_id = ? AND status = 'Approved'
        ");
    } else {
        $stmtUser = $this->pdo->prepare("
            SELECT COUNT(DISTINCT doc_type_id) as total 
            FROM sdopang1_documents 
            WHERE user_id = ?
        ");
    }

    $stmtUser->execute([$user_id]);
    $userDocs = $stmtUser->fetch()['total'];

    // calculate percentage
    $percent = ($totalDocs > 0) ? round(($userDocs / $totalDocs) * 100) : 0;

    return [
        'total' => $totalDocs,
        'uploaded' => $userDocs,
        'percent' => $percent
    ];
}


// INSERT 201 FILES 3

public function createDocument($user_id, $doc_type_id, $file_name, $file_path, $year, $remarks = null) {

    // check if document already exists
    $stmt = $this->pdo->prepare("
        SELECT document_id 
        FROM sdopang1_documents 
        WHERE user_id = ? AND doc_type_id = ?
    ");
    $stmt->execute([$user_id, $doc_type_id]);
    $existing = $stmt->fetch();

    if ($existing) {

        // 🔄 UPDATE (replace file)
        $stmt = $this->pdo->prepare("
            UPDATE sdopang1_documents
            SET file_name = ?, file_path = ?, year = ?, status = 'Pending', remarks = ?, uploaded_at = NOW()
            WHERE user_id = ? AND doc_type_id = ?
        ");

        return $stmt->execute([
            $file_name,
            $file_path,
            $year,
            $remarks,
            $user_id,
            $doc_type_id
        ]);

    } else {

        // ➕ INSERT (first upload)
        $stmt = $this->pdo->prepare("
            INSERT INTO sdopang1_documents 
            (user_id, doc_type_id, file_name, file_path, year, status, remarks, uploaded_at)
            VALUES (?, ?, ?, ?, ?, 'Pending', ?, NOW())
        ");

        return $stmt->execute([
            $user_id,
            $doc_type_id,
            $file_name,
            $file_path,
            $year,
            $remarks
        ]);
    }
}


}