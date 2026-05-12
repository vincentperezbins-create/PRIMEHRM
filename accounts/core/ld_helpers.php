<?php

function ld_ensure_schema(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ld_categories (
            category_id INT AUTO_INCREMENT PRIMARY KEY,
            category_name VARCHAR(160) NOT NULL UNIQUE,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ld_programs (
            program_id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT NULL,
            program_title VARCHAR(200) NOT NULL,
            description TEXT NULL,
            competency_focus TEXT NULL,
            target_participants TEXT NULL,
            status ENUM('Draft','Active','Completed','Archived') NOT NULL DEFAULT 'Draft',
            owner_user_id INT NULL,
            created_by INT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            INDEX idx_ld_program_status (status),
            INDEX idx_ld_program_owner (owner_user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ld_trainings (
            training_id INT AUTO_INCREMENT PRIMARY KEY,
            program_id INT NULL,
            category_id INT NULL,
            training_type VARCHAR(80) NOT NULL DEFAULT 'Seminar',
            title VARCHAR(220) NOT NULL,
            organizer VARCHAR(180) NULL,
            venue_platform VARCHAR(220) NULL,
            start_date DATE NULL,
            end_date DATE NULL,
            hours DECIMAL(6,2) NOT NULL DEFAULT 0,
            cpd_points DECIMAL(6,2) NOT NULL DEFAULT 0,
            capacity INT NULL,
            status ENUM('Draft','Open','Ongoing','Completed','Cancelled') NOT NULL DEFAULT 'Draft',
            owner_user_id INT NULL,
            created_by INT NULL,
            legacy_trainingmatrix_ucode VARCHAR(255) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            INDEX idx_ld_training_status (status),
            INDEX idx_ld_training_dates (start_date, end_date),
            INDEX idx_ld_training_owner (owner_user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ld_participants (
            participant_id INT AUTO_INCREMENT PRIMARY KEY,
            training_id INT NOT NULL,
            user_id INT NOT NULL,
            nominated_by INT NULL,
            school_id VARCHAR(50) NULL,
            status ENUM('Nominated','Registered','Attended','Completed','Waitlisted','Cancelled') NOT NULL DEFAULT 'Registered',
            attendance_status ENUM('Pending','Present','Absent') NOT NULL DEFAULT 'Pending',
            evaluation_status ENUM('Pending','Submitted') NOT NULL DEFAULT 'Pending',
            certificate_no VARCHAR(80) NULL,
            certificate_path VARCHAR(255) NULL,
            registered_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            attended_at DATETIME NULL,
            UNIQUE KEY uq_ld_training_user (training_id, user_id),
            INDEX idx_ld_participant_user (user_id),
            INDEX idx_ld_participant_school (school_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ld_training_requests (
            request_id INT AUTO_INCREMENT PRIMARY KEY,
            requested_by INT NOT NULL,
            school_id VARCHAR(50) NULL,
            topic VARCHAR(220) NOT NULL,
            justification TEXT NULL,
            target_participants TEXT NULL,
            status ENUM('Submitted','Approved','For Review','Rejected') NOT NULL DEFAULT 'Submitted',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL,
            INDEX idx_ld_request_school (school_id),
            INDEX idx_ld_request_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ld_certificate_submissions (
            certificate_submission_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            trainingmatrix_ucode VARCHAR(255) NULL,
            training_title VARCHAR(255) NOT NULL,
            organizer VARCHAR(180) NULL,
            start_date DATE NULL,
            end_date DATE NULL,
            hours DECIMAL(6,2) NULL,
            certificate_no VARCHAR(120) NULL,
            certificate_path VARCHAR(255) NOT NULL,
            status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
            submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reviewed_by INT NULL,
            reviewed_at DATETIME NULL,
            review_remarks TEXT NULL,
            INDEX idx_ld_cert_user (user_id),
            INDEX idx_ld_cert_status (status),
            INDEX idx_ld_cert_training (trainingmatrix_ucode)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS ld_generated_certificates (
            generated_certificate_id INT AUTO_INCREMENT PRIMARY KEY,
            trainingmatrix_ucode VARCHAR(255) NOT NULL,
            traininguniquecode VARCHAR(255) NOT NULL,
            app_info_id INT NOT NULL,
            user_id INT NULL,
            participant_name VARCHAR(255) NOT NULL,
            participant_email VARCHAR(180) NULL,
            employee_id VARCHAR(120) NULL,
            school_name VARCHAR(255) NULL,
            training_title VARCHAR(255) NOT NULL,
            inclusive_date VARCHAR(180) NULL,
            venue VARCHAR(255) NULL,
            certificate_no VARCHAR(80) NOT NULL UNIQUE,
            pdf_path VARCHAR(255) NULL,
            pdf_generated_at DATETIME NULL,
            status ENUM('Approved','Revoked') NOT NULL DEFAULT 'Approved',
            generated_by INT NULL,
            generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_ld_generated_app_training (trainingmatrix_ucode, app_info_id),
            INDEX idx_ld_generated_training (trainingmatrix_ucode),
            INDEX idx_ld_generated_user (user_id),
            INDEX idx_ld_generated_app_info (app_info_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $hasPdfPath = $pdo->query("SHOW COLUMNS FROM ld_generated_certificates LIKE 'pdf_path'")->fetch(PDO::FETCH_ASSOC);
    if (!$hasPdfPath) {
        $pdo->exec("ALTER TABLE ld_generated_certificates ADD COLUMN pdf_path VARCHAR(255) NULL AFTER certificate_no");
    }
    $hasPdfGeneratedAt = $pdo->query("SHOW COLUMNS FROM ld_generated_certificates LIKE 'pdf_generated_at'")->fetch(PDO::FETCH_ASSOC);
    if (!$hasPdfGeneratedAt) {
        $pdo->exec("ALTER TABLE ld_generated_certificates ADD COLUMN pdf_generated_at DATETIME NULL AFTER pdf_path");
    }

    $categories = [
        'Leadership Development',
        'ICT / Digital Skills',
        'Curriculum & Instruction',
        'HR / Administrative Skills',
        'Research Capability',
        'Finance / Procurement',
        'Wellness Programs',
        'Inclusive Education',
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO ld_categories (category_name) VALUES (?)");
    foreach ($categories as $category) {
        $stmt->execute([$category]);
    }
}

function ld_role_scope(): string {
    $roleId = (int) ($_SESSION['role_id'] ?? 0);
    if ($roleId === 1) return 'admin';
    if ($roleId === 3) return 'school_head';
    if ($roleId === 4) return 'employee';
    return 'program_owner';
}

function ld_user_name(array $row): string {
    return trim(($row['first_name'] ?? '') . ' ' . (($row['middle_name'] ?? '') ? $row['middle_name'] . ' ' : '') . ($row['last_name'] ?? ''));
}

function ld_clean_identity_value($value): string {
    $clean = strtolower(trim((string) $value));
    $clean = preg_replace('/[\s\-\.,]+/', '', $clean);
    return $clean ?? '';
}

function ld_is_valid_identity_value($value): bool {
    $clean = ld_clean_identity_value($value);
    return $clean !== '' && !in_array($clean, ['none', 'n/a', 'na', 'null', '0', '-'], true);
}

function ld_identity_sql(string $column): string {
    return "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(CAST($column AS CHAR)), ' ', ''), '-', ''), '.', ''), ',', ''))";
}

function ld_current_user_name_filter(array $currentUser, string $alias = 'ai'): array {
    $first = $currentUser['first_name'] ?? '';
    $last = $currentUser['last_name'] ?? '';

    if (!ld_is_valid_identity_value($first) || !ld_is_valid_identity_value($last)) {
        return ['', []];
    }

    return [
        '(' . ld_identity_sql($alias . '.app_infoFIRST') . ' = ? AND ' . ld_identity_sql($alias . '.app_infoLAST') . ' = ?)',
        [ld_clean_identity_value($first), ld_clean_identity_value($last)],
    ];
}

function ld_current_user_identity_filters(array $currentUser, string $alias = 'ai'): array {
    $conditions = [];
    $params = [];
    [$nameSql, $nameParams] = ld_current_user_name_filter($currentUser, $alias);

    $identityMap = [
        $alias . '.app_infoEMAIL' => $currentUser['email'] ?? '',
        $alias . '.app_infoEMPLOYEEID' => $currentUser['employeeID'] ?? '',
        $alias . '.app_infoTIN' => $currentUser['tin'] ?? '',
    ];

    foreach ($identityMap as $column => $value) {
        if (!ld_is_valid_identity_value($value)) {
            continue;
        }

        $condition = ld_identity_sql($column) . ' = ?';
        $conditionParams = [ld_clean_identity_value($value)];
        if ($nameSql !== '') {
            $condition = '(' . $condition . ' AND ' . $nameSql . ')';
            $conditionParams = array_merge($conditionParams, $nameParams);
        }

        $conditions[] = $condition;
        $params = array_merge($params, $conditionParams);
    }

    if (ld_is_valid_identity_value($currentUser['user_id'] ?? '')) {
        $supportConditions = [];
        $supportParams = [];

        foreach ($identityMap as $column => $value) {
            if (!ld_is_valid_identity_value($value)) {
                continue;
            }

            $supportConditions[] = ld_identity_sql($column) . ' = ?';
            $supportParams[] = ld_clean_identity_value($value);
        }

        if ($nameSql !== '') {
            $supportConditions[] = $nameSql;
            $supportParams = array_merge($supportParams, $nameParams);
        }

        if ($supportConditions) {
            $conditions[] = '(' . ld_identity_sql($alias . '.user_id') . ' = ? AND (' . implode(' OR ', $supportConditions) . '))';
            $params[] = ld_clean_identity_value($currentUser['user_id']);
            $params = array_merge($params, $supportParams);
        }
    } elseif ($nameSql !== '' && !$conditions) {
        $conditions[] = $nameSql;
        $params = array_merge($params, $nameParams);
    }

    return [$conditions, $params];
}

function ld_categories(PDO $pdo): array {
    ld_ensure_schema($pdo);
    return $pdo->query("SELECT * FROM ld_categories WHERE is_active = 1 ORDER BY category_name")->fetchAll(PDO::FETCH_ASSOC);
}

function ld_dashboard_counts(PDO $pdo, array $currentUser): array {
    ld_ensure_schema($pdo);
    $scope = ld_role_scope();
    $userId = (int) $currentUser['user_id'];
    $schoolId = (string) ($currentUser['school_id'] ?? '');
    $email = strtolower(trim((string) ($currentUser['email'] ?? '')));

    $legacyTrainings = (int) $pdo->query("SELECT COUNT(*) FROM sdopang1_trainingmatrix")->fetchColumn();
    $legacyTrainingDays = (int) $pdo->query("SELECT COUNT(*) FROM sdopang1_training_info")->fetchColumn();
    $legacyEvaluations = (int) $pdo->query("SELECT COUNT(*) FROM sdopang1_training_result")->fetchColumn();
    $legacyParticipants = 0;
    $certificates = 0;

    if ($scope === 'school_head') {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sdopang1_app_info WHERE app_infoSCHOOLID = ?");
        $stmt->execute([$schoolId]);
        $legacyParticipants = (int) $stmt->fetchColumn();
    } elseif ($scope === 'employee') {
        [$identityConditions, $identityParams] = ld_current_user_identity_filters($currentUser);
        $identitySql = $identityConditions ? '(' . implode(' OR ', $identityConditions) . ')' : '1=0';
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sdopang1_app_info ai WHERE $identitySql");
        $stmt->execute($identityParams);
        $legacyParticipants = (int) $stmt->fetchColumn();

        $certStmt = $pdo->prepare("SELECT COUNT(*) FROM ld_participants WHERE user_id = ? AND certificate_no IS NOT NULL");
        $certStmt->execute([$userId]);
        $certificates = (int) $certStmt->fetchColumn();
    } else {
        $legacyParticipants = (int) $pdo->query("SELECT COUNT(*) FROM sdopang1_app_info")->fetchColumn();
    }

    return [
        'active_programs' => (int) $pdo->query("SELECT COUNT(*) FROM ld_programs WHERE status = 'Active'")->fetchColumn(),
        'upcoming_trainings' => $legacyTrainings,
        'total_trainings' => $legacyTrainings,
        'training_days' => $legacyTrainingDays,
        'total_participants' => $legacyParticipants,
        'completed_trainings' => $legacyEvaluations,
        'certificates' => $certificates,
    ];
}

function ld_programs(PDO $pdo, array $currentUser): array {
    ld_ensure_schema($pdo);
    $scope = ld_role_scope();
    $where = '';
    $params = [];
    if ($scope === 'program_owner') {
        $where = 'WHERE p.owner_user_id = ?';
        $params[] = (int) $currentUser['user_id'];
    }

    $stmt = $pdo->prepare("
        SELECT p.*, c.category_name,
               CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) AS owner_name
        FROM ld_programs p
        LEFT JOIN ld_categories c ON c.category_id = p.category_id
        LEFT JOIN sdopang1_user u ON u.user_id = p.owner_user_id
        $where
        ORDER BY p.created_at DESC
    ");
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function ld_trainings(PDO $pdo, array $currentUser, bool $openOnly = false): array {
    ld_ensure_schema($pdo);
    $scope = ld_role_scope();
    $where = [];
    $params = [];
    if ($openOnly) {
        $where[] = "t.status = 'Open'";
    } elseif ($scope === 'program_owner') {
        $where[] = 't.owner_user_id = ?';
        $params[] = (int) $currentUser['user_id'];
    }

    $sql = "
        SELECT t.*, c.category_name, p.program_title,
               CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) AS owner_name,
               (SELECT COUNT(*) FROM ld_participants lp WHERE lp.training_id = t.training_id) AS participant_count
        FROM ld_trainings t
        LEFT JOIN ld_categories c ON c.category_id = t.category_id
        LEFT JOIN ld_programs p ON p.program_id = t.program_id
        LEFT JOIN sdopang1_user u ON u.user_id = t.owner_user_id
    ";
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY COALESCE(t.start_date, t.created_at) DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function ld_legacy_trainings(PDO $pdo, int $limit = 20): array {
    return $pdo->query("
        SELECT tm.*,
               (SELECT COUNT(*) FROM sdopang1_app a JOIN sdopang1_app_info ai ON ai.traininguniquecode = a.traininguniquecode WHERE a.trainingmatrixUCODE = tm.trainingmatrixUCODE) AS participant_count,
               (SELECT COUNT(*) FROM sdopang1_training_result tr JOIN sdopang1_training_info ti ON ti.traininguniquecode = tr.traininguniquecode WHERE ti.trainingmatrixUCODE = tm.trainingmatrixUCODE) AS evaluation_count
        FROM sdopang1_trainingmatrix tm
        ORDER BY tm.trainingmatrixDATEADDED DESC
        LIMIT " . (int) $limit
    )->fetchAll(PDO::FETCH_ASSOC);
}

function ld_training_mother_list(PDO $pdo, int $limit = 100): array {
    return $pdo->query("
        SELECT tm.*,
               (SELECT COUNT(*) FROM sdopang1_training_info ti WHERE ti.trainingmatrixUCODE = tm.trainingmatrixUCODE) AS day_count,
               (SELECT COUNT(*) FROM sdopang1_app a JOIN sdopang1_app_info ai ON ai.traininguniquecode = a.traininguniquecode WHERE a.trainingmatrixUCODE = tm.trainingmatrixUCODE) AS participant_count,
               (SELECT COUNT(*) FROM sdopang1_training_result tr JOIN sdopang1_training_info ti ON ti.traininguniquecode = tr.traininguniquecode WHERE ti.trainingmatrixUCODE = tm.trainingmatrixUCODE) AS evaluation_count,
               (SELECT COUNT(*) FROM sdopang1_training_speaker sp JOIN sdopang1_training_info ti ON ti.traininguniquecode = sp.traininguniquecode WHERE ti.trainingmatrixUCODE = tm.trainingmatrixUCODE) AS speaker_count
        FROM sdopang1_trainingmatrix tm
        ORDER BY tm.trainingmatrixDATEADDED DESC
        LIMIT " . (int) $limit
    )->fetchAll(PDO::FETCH_ASSOC);
}

function ld_certificate_training_options(PDO $pdo, int $limit = 500): array {
    $stmt = $pdo->query("
        SELECT trainingmatrixUCODE, trainingmatrixTITLE, trainingmatrixINCLUSIVEDATE
        FROM sdopang1_trainingmatrix
        WHERE trainingmatrixTITLE IS NOT NULL AND TRIM(trainingmatrixTITLE) <> ''
        ORDER BY trainingmatrixTITLE
        LIMIT " . (int) $limit
    );
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function ld_find_training_by_code(PDO $pdo, string $code): ?array {
    if (trim($code) === '') {
        return null;
    }

    $stmt = $pdo->prepare("
        SELECT trainingmatrixUCODE, trainingmatrixTITLE, trainingmatrixINCLUSIVEDATE
        FROM sdopang1_trainingmatrix
        WHERE trainingmatrixUCODE = ?
        LIMIT 1
    ");
    $stmt->execute([$code]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function ld_find_training_by_title(PDO $pdo, string $title): ?array {
    $cleanTitle = ld_clean_identity_value($title);
    if ($cleanTitle === '') {
        return null;
    }

    $stmt = $pdo->prepare("
        SELECT trainingmatrixUCODE, trainingmatrixTITLE, trainingmatrixINCLUSIVEDATE
        FROM sdopang1_trainingmatrix
        WHERE " . ld_identity_sql('trainingmatrixTITLE') . " = ?
        LIMIT 1
    ");
    $stmt->execute([$cleanTitle]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function ld_certificate_submissions(PDO $pdo, array $currentUser, ?string $status = null, int $limit = 200): array {
    ld_ensure_schema($pdo);
    $scope = ld_role_scope();
    $where = [];
    $params = [];

    if ($scope === 'employee') {
        $where[] = 'cs.user_id = ?';
        $params[] = (int) $currentUser['user_id'];
    } elseif ($scope === 'school_head') {
        $where[] = 'u.school_id = ?';
        $params[] = (string) ($currentUser['school_id'] ?? '');
    }

    if ($status !== null && $status !== '') {
        $where[] = 'cs.status = ?';
        $params[] = $status;
    }

    $sql = "
        SELECT cs.*,
               CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.middle_name,''), ' ', COALESCE(u.last_name,'')) AS employee_name,
               u.employeeID,
               u.email,
               u.school_id,
               s.schoolname,
               reviewer.first_name AS reviewer_first_name,
               reviewer.last_name AS reviewer_last_name
        FROM ld_certificate_submissions cs
        JOIN sdopang1_user u ON u.user_id = cs.user_id
        LEFT JOIN sdopang1schoollist s ON s.schoolID = u.school_id
        LEFT JOIN sdopang1_user reviewer ON reviewer.user_id = cs.reviewed_by
    ";

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY cs.submitted_at DESC LIMIT ' . (int) $limit;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function ld_certificate_serial(string $trainingCode, int $appInfoId): string {
    return 'LD-' . date('Y') . '-' . strtoupper(substr(hash('sha256', $trainingCode . ':' . $appInfoId), 0, 10));
}

function ld_pdf_safe_text($text): string {
    $text = trim(preg_replace('/\s+/', ' ', (string) $text));
    $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
    if ($converted !== false) {
        $text = $converted;
    }
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
}

function ld_pdf_text_command(string $text, float $x, float $y, int $size, string $align = 'left'): string {
    $safeText = ld_pdf_safe_text($text);
    $width = strlen($safeText) * $size * 0.45;
    if ($align === 'center') {
        $x -= $width / 2;
    } elseif ($align === 'right') {
        $x -= $width;
    }
    return "BT /F1 $size Tf 1 0 0 1 " . round($x, 2) . " " . round($y, 2) . " Tm ($safeText) Tj ET\n";
}

function ld_pdf_wrapped_text_commands(string $text, float $centerX, float $startY, int $size, int $maxChars, float $lineHeight): string {
    $words = preg_split('/\s+/', trim($text));
    $lines = [];
    $line = '';
    foreach ($words as $word) {
        $candidate = trim($line . ' ' . $word);
        if (strlen($candidate) > $maxChars && $line !== '') {
            $lines[] = $line;
            $line = $word;
        } else {
            $line = $candidate;
        }
    }
    if ($line !== '') {
        $lines[] = $line;
    }

    $commands = '';
    foreach ($lines as $index => $lineText) {
        $commands .= ld_pdf_text_command($lineText, $centerX, $startY - ($index * $lineHeight), $size, 'center');
    }
    return $commands;
}

function ld_write_simple_pdf(string $path, array $certificate): void {
    $issuedDate = !empty($certificate['generated_at']) ? date('F d, Y', strtotime((string) $certificate['generated_at'])) : date('F d, Y');
    $content = "q\n";
    $content .= "0.10 0.25 0.70 RG 8 w 34 34 774 527 re S\n";
    $content .= "0.58 0.76 1.00 RG 2 w 52 52 738 491 re S\n";
    $content .= "Q\n";
    $content .= ld_pdf_text_command('PRIMEHR LEARNING & DEVELOPMENT', 421, 505, 13, 'center');
    $content .= ld_pdf_text_command('Certificate', 421, 450, 48, 'center');
    $content .= ld_pdf_text_command('OF PARTICIPATION', 421, 420, 14, 'center');
    $content .= ld_pdf_text_command('This certificate is proudly presented to', 421, 366, 17, 'center');
    $content .= ld_pdf_wrapped_text_commands((string) $certificate['participant_name'], 421, 326, 34, 34, 35);
    $content .= "0.13 0.18 0.30 RG 2 w 190 304 m 652 304 l S\n";
    $content .= ld_pdf_text_command('for successfully participating in', 421, 268, 17, 'center');
    $content .= ld_pdf_wrapped_text_commands((string) $certificate['training_title'], 421, 235, 24, 54, 27);
    $content .= ld_pdf_wrapped_text_commands((string) ($certificate['inclusive_date'] ?: 'Division Training'), 421, 158, 13, 80, 16);
    if (!empty($certificate['venue'])) {
        $content .= ld_pdf_wrapped_text_commands((string) $certificate['venue'], 421, 140, 12, 80, 15);
    }
    $content .= ld_pdf_text_command('Issued on ' . $issuedDate, 421, 116, 12, 'center');
    $content .= "0.13 0.18 0.30 RG 1.4 w 155 80 m 345 80 l S\n";
    $content .= "0.13 0.18 0.30 RG 1.4 w 498 80 m 688 80 l S\n";
    $content .= ld_pdf_text_command('Training / Program Owner', 250, 62, 10, 'center');
    $content .= ld_pdf_text_command('Schools Division Office', 593, 62, 10, 'center');
    $content .= ld_pdf_text_command('Certificate No. ' . (string) $certificate['certificate_no'], 48, 24, 9, 'left');

    $objects = [];
    $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
    $objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
    $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>";
    $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
    $objects[] = "<< /Length " . strlen($content) . " >>\nstream\n$content\nendstream";

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $index => $object) {
        $offsets[] = strlen($pdf);
        $pdf .= ($index + 1) . " 0 obj\n$object\nendobj\n";
    }
    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
    }
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n$xrefOffset\n%%EOF";

    file_put_contents($path, $pdf);
}

function ld_save_generated_certificate_pdf(PDO $pdo, array $certificate): ?string {
    $uploadDir = __DIR__ . '/../uploads/ld_generated_certificates/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        return null;
    }

    $fileName = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) $certificate['certificate_no']) . '.pdf';
    $absolutePath = $uploadDir . $fileName;
    ld_write_simple_pdf($absolutePath, $certificate);
    $relativePath = 'uploads/ld_generated_certificates/' . $fileName;

    $stmt = $pdo->prepare("
        UPDATE ld_generated_certificates
        SET pdf_path = ?, pdf_generated_at = NOW()
        WHERE generated_certificate_id = ?
    ");
    $stmt->execute([$relativePath, (int) $certificate['generated_certificate_id']]);

    return $relativePath;
}

function ld_generate_missing_certificate_pdfs(PDO $pdo, string $trainingMatrixCode): array {
    $stmt = $pdo->prepare("
        SELECT *
        FROM ld_generated_certificates
        WHERE trainingmatrix_ucode = ? AND status = 'Approved'
        ORDER BY participant_name
    ");
    $stmt->execute([$trainingMatrixCode]);
    $certificates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $created = 0;
    foreach ($certificates as $certificate) {
        if (!empty($certificate['pdf_path']) && is_file(__DIR__ . '/../' . $certificate['pdf_path'])) {
            continue;
        }

        if (ld_save_generated_certificate_pdf($pdo, $certificate) !== null) {
            $created++;
        }
    }

    return ['created' => $created, 'total' => count($certificates)];
}

function ld_generate_certificates_for_training(PDO $pdo, string $trainingMatrixCode, array $currentUser): array {
    ld_ensure_schema($pdo);

    $training = ld_find_training_by_code($pdo, $trainingMatrixCode);
    if (!$training) {
        return ['created' => 0, 'existing' => 0, 'total' => 0];
    }

    $stmt = $pdo->prepare("
        SELECT ai.*,
               COALESCE(a.trainingmatrixUCODE, ti.trainingmatrixUCODE) AS trainingmatrixUCODE,
               COALESCE(tm.trainingmatrixTITLE, ?) AS trainingmatrixTITLE,
               tm.trainingmatrixINCLUSIVEDATE,
               tm.trainingmatrixVENUE
        FROM sdopang1_app_info ai
        LEFT JOIN sdopang1_app a ON a.traininguniquecode = ai.traininguniquecode
        LEFT JOIN sdopang1_training_info ti ON ti.traininguniquecode = ai.traininguniquecode
        LEFT JOIN sdopang1_trainingmatrix tm ON tm.trainingmatrixUCODE = COALESCE(a.trainingmatrixUCODE, ti.trainingmatrixUCODE)
        WHERE COALESCE(a.trainingmatrixUCODE, ti.trainingmatrixUCODE) = ?
        ORDER BY ai.app_infoLAST, ai.app_infoFIRST, ai.app_infoID
    ");
    $stmt->execute([(string) $training['trainingmatrixTITLE'], $trainingMatrixCode]);
    $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $insert = $pdo->prepare("
        INSERT IGNORE INTO ld_generated_certificates
            (trainingmatrix_ucode, traininguniquecode, app_info_id, user_id, participant_name, participant_email, employee_id, school_name, training_title, inclusive_date, venue, certificate_no, generated_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $created = 0;
    $existing = 0;
    foreach ($responses as $response) {
        $appInfoId = (int) $response['app_infoID'];
        $participantName = trim(implode(' ', array_filter([
            $response['app_infoFIRST'] ?? '',
            $response['app_infoMIDDLE'] ?? '',
            $response['app_infoLAST'] ?? '',
        ])));
        if ($participantName === '') {
            $participantName = 'Training Participant';
        }

        $userId = filter_var($response['user_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
        $insert->execute([
            $trainingMatrixCode,
            (string) $response['traininguniquecode'],
            $appInfoId,
            $userId,
            $participantName,
            trim((string) ($response['app_infoEMAIL'] ?? '')) ?: null,
            trim((string) ($response['app_infoEMPLOYEEID'] ?? '')) ?: null,
            trim((string) ($response['app_infoSCHOOLNAME'] ?? '')) ?: null,
            (string) ($response['trainingmatrixTITLE'] ?: $training['trainingmatrixTITLE']),
            trim((string) ($response['trainingmatrixINCLUSIVEDATE'] ?? $training['trainingmatrixINCLUSIVEDATE'] ?? '')) ?: null,
            trim((string) ($response['trainingmatrixVENUE'] ?? $training['trainingmatrixVENUE'] ?? '')) ?: null,
            ld_certificate_serial($trainingMatrixCode, $appInfoId),
            (int) $currentUser['user_id'],
        ]);

        if ($insert->rowCount() > 0) {
            $created++;
        } else {
            $existing++;
        }

        $certStmt = $pdo->prepare("
            SELECT *
            FROM ld_generated_certificates
            WHERE trainingmatrix_ucode = ? AND app_info_id = ?
            LIMIT 1
        ");
        $certStmt->execute([$trainingMatrixCode, $appInfoId]);
        $certificate = $certStmt->fetch(PDO::FETCH_ASSOC);
        if ($certificate) {
            ld_save_generated_certificate_pdf($pdo, $certificate);
        }
    }

    return ['created' => $created, 'existing' => $existing, 'total' => count($responses)];
}

function ld_generated_certificates(PDO $pdo, array $currentUser, ?string $trainingMatrixCode = null, int $limit = 200): array {
    ld_ensure_schema($pdo);
    $scope = ld_role_scope();
    $where = ['gc.status = ?'];
    $params = ['Approved'];

    if ($trainingMatrixCode !== null && $trainingMatrixCode !== '') {
        $where[] = 'gc.trainingmatrix_ucode = ?';
        $params[] = $trainingMatrixCode;
    }

    if ($scope === 'employee') {
        [$identityConditions, $identityParams] = ld_current_user_identity_filters($currentUser);
        $where[] = $identityConditions ? '(' . implode(' OR ', $identityConditions) . ')' : '1=0';
        $params = array_merge($params, $identityParams);
    } elseif ($scope === 'school_head') {
        $where[] = 'ai.app_infoSCHOOLID = ?';
        $params[] = (string) ($currentUser['school_id'] ?? '');
    }

    $sql = "
        SELECT gc.*, ai.app_infoFIRST, ai.app_infoMIDDLE, ai.app_infoLAST, ai.app_infoEMAIL, ai.app_infoEMPLOYEEID, ai.app_infoTIN, ai.app_infoSCHOOLID, ai.app_infoSCHOOLNAME
        FROM ld_generated_certificates gc
        JOIN sdopang1_app_info ai ON ai.app_infoID = gc.app_info_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY gc.generated_at DESC
        LIMIT " . (int) $limit;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function ld_generated_certificate_by_id(PDO $pdo, int $certificateId): ?array {
    $stmt = $pdo->prepare("
        SELECT gc.*, ai.app_infoFIRST, ai.app_infoMIDDLE, ai.app_infoLAST, ai.app_infoEMAIL, ai.app_infoEMPLOYEEID, ai.app_infoTIN, ai.app_infoSCHOOLID
        FROM ld_generated_certificates gc
        JOIN sdopang1_app_info ai ON ai.app_infoID = gc.app_info_id
        WHERE gc.generated_certificate_id = ?
        LIMIT 1
    ");
    $stmt->execute([$certificateId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function ld_can_view_generated_certificate(array $certificate, array $currentUser): bool {
    $scope = ld_role_scope();
    if (in_array($scope, ['admin', 'program_owner'], true)) {
        return true;
    }

    if ($scope === 'school_head') {
        return (string) ($certificate['app_infoSCHOOLID'] ?? '') === (string) ($currentUser['school_id'] ?? '');
    }

    $firstNameMatches = !ld_is_valid_identity_value($currentUser['first_name'] ?? '')
        || ld_clean_identity_value($certificate['app_infoFIRST'] ?? '') === ld_clean_identity_value($currentUser['first_name'] ?? '');
    $lastNameMatches = !ld_is_valid_identity_value($currentUser['last_name'] ?? '')
        || ld_clean_identity_value($certificate['app_infoLAST'] ?? '') === ld_clean_identity_value($currentUser['last_name'] ?? '');
    $nameMatches = $firstNameMatches && $lastNameMatches;

    $emailMatches = ld_is_valid_identity_value($currentUser['email'] ?? '')
        && ld_clean_identity_value($certificate['app_infoEMAIL'] ?? '') === ld_clean_identity_value($currentUser['email'] ?? '');
    $employeeMatches = ld_is_valid_identity_value($currentUser['employeeID'] ?? '')
        && ld_clean_identity_value($certificate['app_infoEMPLOYEEID'] ?? '') === ld_clean_identity_value($currentUser['employeeID'] ?? '');
    $tinMatches = ld_is_valid_identity_value($currentUser['tin'] ?? '')
        && ld_clean_identity_value($certificate['app_infoTIN'] ?? '') === ld_clean_identity_value($currentUser['tin'] ?? '');

    return $nameMatches && ($emailMatches || $employeeMatches || $tinMatches);
}

function ld_certificate_submission_can_review(array $submission, array $currentUser): bool {
    $scope = ld_role_scope();
    if (in_array($scope, ['admin', 'program_owner'], true)) {
        return true;
    }

    if ($scope === 'school_head') {
        return (string) ($submission['school_id'] ?? '') === (string) ($currentUser['school_id'] ?? '');
    }

    return false;
}

function ld_status_badge(string $status): string {
    $class = [
        'Active' => 'badge-success',
        'Open' => 'badge-success',
        'Completed' => 'badge-info',
        'Pending' => 'badge-warning',
        'Approved' => 'badge-success',
        'Rejected' => 'badge-danger',
    ][$status] ?? 'badge-secondary';

    return '<span class="badge ' . $class . '">' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '</span>';
}

function ld_training_days(PDO $pdo, string $trainingMatrixCode): array {
    $stmt = $pdo->prepare("
        SELECT ti.*,
               (SELECT COUNT(*) FROM sdopang1_training_result tr WHERE tr.traininguniquecode = ti.traininguniquecode) AS evaluation_count,
               (SELECT COUNT(*) FROM sdopang1_training_speaker sp WHERE sp.traininguniquecode = ti.traininguniquecode) AS speaker_count,
               (SELECT COUNT(*) FROM sdopang1_training_speakereval se WHERE se.traininguniquecode = ti.traininguniquecode) AS speaker_eval_count
        FROM sdopang1_training_info ti
        WHERE ti.trainingmatrixUCODE = ?
        ORDER BY CAST(ti.dayoftraining AS UNSIGNED), ti.dateoftrainings
    ");
    $stmt->execute([$trainingMatrixCode]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function ld_training_app_forms(PDO $pdo, string $trainingMatrixCode): array {
    $stmt = $pdo->prepare("
        SELECT a.*,
               (SELECT COUNT(*) FROM sdopang1_app_info ai WHERE ai.traininguniquecode = a.traininguniquecode) AS participant_count
        FROM sdopang1_app a
        WHERE a.trainingmatrixUCODE = ?
        ORDER BY a.sdopang1_appDATECREATED DESC
    ");
    $stmt->execute([$trainingMatrixCode]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function ld_legacy_participants(PDO $pdo, array $currentUser, ?string $trainingCode = null, int $limit = 200): array {
    $scope = ld_role_scope();
    $where = [];
    $params = [];
    if ($trainingCode) {
        $where[] = 'ai.traininguniquecode = ?';
        $params[] = $trainingCode;
    }
    if ($scope === 'school_head') {
        $where[] = 'ai.app_infoSCHOOLID = ?';
        $params[] = (string) ($currentUser['school_id'] ?? '');
    } elseif ($scope === 'employee') {
        [$identityConditions, $identityParams] = ld_current_user_identity_filters($currentUser);
        $where[] = $identityConditions ? '(' . implode(' OR ', $identityConditions) . ')' : '1=0';
        $params = array_merge($params, $identityParams);
    }
    $sql = "
        SELECT ai.*,
               COALESCE(a.trainingmatrixUCODE, ti.trainingmatrixUCODE) AS trainingmatrixUCODE,
               COALESCE(tm.trainingmatrixTITLE, 'Legacy Training') AS trainingmatrixTITLE,
               tm.trainingmatrixINCLUSIVEDATE,
               ti.dayoftraining,
               ti.dateoftrainings
        FROM sdopang1_app_info ai
        LEFT JOIN sdopang1_app a ON a.traininguniquecode = ai.traininguniquecode
        LEFT JOIN sdopang1_training_info ti ON ti.traininguniquecode = ai.traininguniquecode
        LEFT JOIN sdopang1_trainingmatrix tm ON tm.trainingmatrixUCODE = COALESCE(a.trainingmatrixUCODE, ti.trainingmatrixUCODE)
    ";
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY ai.app_infoDATE DESC LIMIT ' . (int) $limit;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function ld_legacy_evaluations(PDO $pdo, ?string $trainingCode = null, int $limit = 200): array {
    $where = '';
    $params = [];
    if ($trainingCode) {
        $where = 'WHERE tr.traininguniquecode = ?';
        $params[] = $trainingCode;
    }
    $stmt = $pdo->prepare("
        SELECT tr.*, ti.dayoftraining, ti.dateoftrainings, ti.trainingmatrixUCODE, tm.trainingmatrixTITLE
        FROM sdopang1_training_result tr
        LEFT JOIN sdopang1_training_info ti ON ti.traininguniquecode = tr.traininguniquecode
        LEFT JOIN sdopang1_trainingmatrix tm ON tm.trainingmatrixUCODE = ti.trainingmatrixUCODE
        $where
        ORDER BY tr.datecreated DESC
        LIMIT " . (int) $limit
    );
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function ld_legacy_speakers(PDO $pdo, ?string $trainingCode = null, int $limit = 200): array {
    $where = '';
    $params = [];
    if ($trainingCode) {
        $where = 'WHERE sp.traininguniquecode = ?';
        $params[] = $trainingCode;
    }
    $stmt = $pdo->prepare("
        SELECT sp.*, ti.dayoftraining, ti.dateoftrainings, ti.trainingmatrixUCODE,
               COALESCE(CONCAT(u.first_name, ' ', u.last_name), sp.fullname) AS speaker_label,
               (SELECT COUNT(*) FROM sdopang1_training_speakereval se WHERE se.training_speakerID = sp.training_speakerID) AS eval_count
        FROM sdopang1_training_speaker sp
        LEFT JOIN sdopang1_training_info ti ON ti.traininguniquecode = sp.traininguniquecode
        LEFT JOIN sdopang1_user u ON CAST(u.user_id AS CHAR) = sp.fullname
        $where
        ORDER BY sp.date_created DESC
        LIMIT " . (int) $limit
    );
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function ld_participants_for_scope(PDO $pdo, array $currentUser): array {
    ld_ensure_schema($pdo);
    $scope = ld_role_scope();
    $where = '1=1';
    $params = [];
    if ($scope === 'school_head') {
        $where = 'lp.school_id = ?';
        $params[] = (string) ($currentUser['school_id'] ?? '');
    } elseif ($scope === 'employee') {
        $where = 'lp.user_id = ?';
        $params[] = (int) $currentUser['user_id'];
    } elseif ($scope === 'program_owner') {
        $where = 't.owner_user_id = ?';
        $params[] = (int) $currentUser['user_id'];
    }

    $stmt = $pdo->prepare("
        SELECT lp.*, t.title, t.training_type, t.start_date, t.end_date, t.hours, t.status AS training_status,
               CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) AS participant_name,
               s.schoolname
        FROM ld_participants lp
        JOIN ld_trainings t ON t.training_id = lp.training_id
        LEFT JOIN sdopang1_user u ON u.user_id = lp.user_id
        LEFT JOIN sdopang1schoollist s ON s.schoolID = lp.school_id
        WHERE $where
        ORDER BY lp.registered_at DESC
    ");
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function ld_employee_options(PDO $pdo, array $currentUser): array {
    $scope = ld_role_scope();
    $where = '1=1';
    $params = [];
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
