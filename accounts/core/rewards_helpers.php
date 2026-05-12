<?php

function rewards_ensure_schema(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reward_categories (
            category_id INT AUTO_INCREMENT PRIMARY KEY,
            category_group VARCHAR(80) NOT NULL,
            category_name VARCHAR(160) NOT NULL,
            description TEXT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_reward_category (category_group, category_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reward_programs (
            program_id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT NULL,
            title VARCHAR(180) NOT NULL,
            description TEXT NULL,
            eligibility TEXT NULL,
            requirements TEXT NULL,
            status ENUM('Draft','Open','Screening','Evaluation','Closed','Published') NOT NULL DEFAULT 'Draft',
            nomination_start DATE NULL,
            nomination_end DATE NULL,
            owner_user_id INT NULL,
            created_by INT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            INDEX idx_reward_program_status (status),
            INDEX idx_reward_program_owner (owner_user_id),
            CONSTRAINT fk_reward_program_category FOREIGN KEY (category_id) REFERENCES reward_categories(category_id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reward_nominations (
            nomination_id INT AUTO_INCREMENT PRIMARY KEY,
            program_id INT NOT NULL,
            nominee_user_id INT NOT NULL,
            nominated_by INT NOT NULL,
            school_id VARCHAR(50) NULL,
            endorsement_text TEXT NULL,
            status ENUM('Submitted','Lacking Documents','Validated','Shortlisted','For Evaluation','Winner','Not Selected') NOT NULL DEFAULT 'Submitted',
            score DECIMAL(6,2) NULL,
            remarks TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            INDEX idx_reward_nomination_program (program_id),
            INDEX idx_reward_nomination_nominee (nominee_user_id),
            INDEX idx_reward_nomination_school (school_id),
            CONSTRAINT fk_reward_nomination_program FOREIGN KEY (program_id) REFERENCES reward_programs(program_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reward_documents (
            document_id INT AUTO_INCREMENT PRIMARY KEY,
            nomination_id INT NOT NULL,
            document_type VARCHAR(120) NOT NULL,
            file_name VARCHAR(255) NULL,
            file_path VARCHAR(255) NULL,
            uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_reward_document_nomination FOREIGN KEY (nomination_id) REFERENCES reward_nominations(nomination_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reward_recognitions (
            recognition_id INT AUTO_INCREMENT PRIMARY KEY,
            nomination_id INT NULL,
            program_id INT NULL,
            user_id INT NOT NULL,
            title VARCHAR(180) NOT NULL,
            category_name VARCHAR(180) NULL,
            awarded_at DATE NULL,
            certificate_path VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_reward_recognition_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $seed = [
        ['Performance Awards', 'Outstanding Teacher'],
        ['Performance Awards', 'Outstanding Non-Teaching'],
        ['Performance Awards', 'Top Performer'],
        ['Loyalty Awards', '10 Years Service'],
        ['Loyalty Awards', '20 Years Service'],
        ['Loyalty Awards', '30 Years Service'],
        ['Innovation Awards', 'Best Project'],
        ['Innovation Awards', 'Best Research'],
        ['Innovation Awards', 'Best Digital Initiative'],
        ['Behavior / Values', 'Leadership Award'],
        ['Behavior / Values', 'Service Excellence'],
        ['Behavior / Values', 'Teamwork Award'],
    ];

    $stmt = $pdo->prepare("
        INSERT IGNORE INTO reward_categories (category_group, category_name)
        VALUES (?, ?)
    ");
    foreach ($seed as $row) {
        $stmt->execute($row);
    }
}

function rewards_role_scope(): string {
    $roleId = (int) ($_SESSION['role_id'] ?? 0);
    if ($roleId === 1) return 'admin';
    if ($roleId === 3) return 'school_head';
    if ($roleId === 4) return 'employee';
    return 'program_owner';
}

function rewards_user_name(array $row): string {
    return trim(($row['first_name'] ?? '') . ' ' . (($row['middle_name'] ?? '') ? $row['middle_name'] . ' ' : '') . ($row['last_name'] ?? ''));
}

function rewards_categories(PDO $pdo): array {
    rewards_ensure_schema($pdo);
    return $pdo->query("SELECT * FROM reward_categories WHERE is_active = 1 ORDER BY category_group, category_name")->fetchAll(PDO::FETCH_ASSOC);
}

function rewards_dashboard_counts(PDO $pdo, array $currentUser): array {
    rewards_ensure_schema($pdo);
    $scope = rewards_role_scope();
    $userId = (int) $currentUser['user_id'];
    $schoolId = (string) ($currentUser['school_id'] ?? '');

    $where = '1=1';
    $params = [];
    if ($scope === 'program_owner') {
        $where = 'p.owner_user_id = ?';
        $params[] = $userId;
    } elseif ($scope === 'school_head') {
        $where = 'n.school_id = ?';
        $params[] = $schoolId;
    } elseif ($scope === 'employee') {
        $where = 'n.nominee_user_id = ?';
        $params[] = $userId;
    }

    $programs = (int) $pdo->query("SELECT COUNT(*) FROM reward_programs WHERE status IN ('Open','Screening','Evaluation')")->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT
            COUNT(n.nomination_id) AS nominations,
            SUM(CASE WHEN n.status IN ('Submitted','Lacking Documents','Validated','For Evaluation') THEN 1 ELSE 0 END) AS pending,
            SUM(CASE WHEN n.status = 'Winner' THEN 1 ELSE 0 END) AS winners
        FROM reward_nominations n
        JOIN reward_programs p ON p.program_id = n.program_id
        WHERE $where
    ");
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $certStmt = $pdo->prepare($scope === 'employee'
        ? "SELECT COUNT(*) FROM reward_recognitions WHERE user_id = ?"
        : "SELECT COUNT(*) FROM reward_recognitions");
    $certStmt->execute($scope === 'employee' ? [$userId] : []);

    return [
        'active_programs' => $programs,
        'nominations' => (int) ($row['nominations'] ?? 0),
        'pending' => (int) ($row['pending'] ?? 0),
        'winners' => (int) ($row['winners'] ?? 0),
        'certificates' => (int) $certStmt->fetchColumn(),
    ];
}

function rewards_programs(PDO $pdo, array $currentUser, bool $openOnly = false): array {
    rewards_ensure_schema($pdo);
    $scope = rewards_role_scope();
    $where = [];
    $params = [];

    if ($openOnly) {
        $where[] = "p.status = 'Open'";
    } elseif ($scope === 'program_owner') {
        $where[] = 'p.owner_user_id = ?';
        $params[] = (int) $currentUser['user_id'];
    }

    $sql = "
        SELECT p.*, c.category_group, c.category_name,
               CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) AS owner_name
        FROM reward_programs p
        LEFT JOIN reward_categories c ON c.category_id = p.category_id
        LEFT JOIN sdopang1_user u ON u.user_id = p.owner_user_id
    ";
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY p.created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function rewards_nominees_for_scope(PDO $pdo, array $currentUser): array {
    rewards_ensure_schema($pdo);
    $scope = rewards_role_scope();
    $params = [];
    $where = '1=1';

    if ($scope === 'program_owner') {
        $where = 'p.owner_user_id = ?';
        $params[] = (int) $currentUser['user_id'];
    } elseif ($scope === 'school_head') {
        $where = 'n.school_id = ?';
        $params[] = (string) ($currentUser['school_id'] ?? '');
    } elseif ($scope === 'employee') {
        $where = 'n.nominee_user_id = ?';
        $params[] = (int) $currentUser['user_id'];
    }

    $stmt = $pdo->prepare("
        SELECT n.*, p.title AS program_title, p.status AS program_status,
               c.category_name, s.schoolname,
               CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) AS nominee_name,
               CONCAT(COALESCE(nb.first_name,''), ' ', COALESCE(nb.last_name,'')) AS nominated_by_name
        FROM reward_nominations n
        JOIN reward_programs p ON p.program_id = n.program_id
        LEFT JOIN reward_categories c ON c.category_id = p.category_id
        LEFT JOIN sdopang1_user u ON u.user_id = n.nominee_user_id
        LEFT JOIN sdopang1_user nb ON nb.user_id = n.nominated_by
        LEFT JOIN sdopang1schoollist s ON s.schoolID = n.school_id
        WHERE $where
        ORDER BY n.created_at DESC
    ");
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function rewards_employee_options(PDO $pdo, array $currentUser): array {
    $scope = rewards_role_scope();
    $params = [];
    $where = '1=1';
    if ($scope === 'school_head') {
        $where = 'u.school_id = ?';
        $params[] = (string) ($currentUser['school_id'] ?? '');
    }

    $stmt = $pdo->prepare("
        SELECT u.user_id, u.employeeID, u.first_name, u.middle_name, u.last_name, u.school_id, s.schoolname
        FROM sdopang1_user u
        LEFT JOIN sdopang1schoollist s ON s.schoolID = u.school_id
        WHERE $where
        ORDER BY u.last_name, u.first_name
    ");
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function rewards_status_class(string $status): string {
    return 'status-' . strtolower(str_replace(' ', '-', $status));
}
