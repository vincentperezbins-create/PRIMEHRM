<?php

function audit_ensure_table(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS audit_logs (
            audit_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            fullname VARCHAR(255) NULL,
            action_type VARCHAR(50) NOT NULL,
            module_name VARCHAR(100) NOT NULL,
            record_id VARCHAR(100) NULL,
            description TEXT NULL,
            ip_address VARCHAR(100) NULL,
            device_info TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_audit_user (user_id),
            INDEX idx_audit_action (action_type),
            INDEX idx_audit_module (module_name),
            INDEX idx_audit_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function audit_request_ip(): string {
    $candidates = [
        $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '',
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
        $_SERVER['HTTP_CLIENT_IP'] ?? '',
        $_SERVER['REMOTE_ADDR'] ?? '',
    ];

    foreach ($candidates as $candidate) {
        $ip = trim(explode(',', (string) $candidate)[0]);
        if ($ip !== '') {
            return substr($ip, 0, 100);
        }
    }

    return '';
}

function audit_session_fullname(): string {
    if (!empty($_SESSION['fullname'])) {
        return (string) $_SESSION['fullname'];
    }

    if (!empty($_SESSION['name'])) {
        return (string) $_SESSION['name'];
    }

    return 'Guest';
}

function audit_log(
    PDO $pdo,
    $user_id,
    $fullname,
    string $action_type,
    string $module_name,
    $record_id = null,
    ?string $description = null
): void {
    try {
        audit_ensure_table($pdo);

        $sessionUserId = $_SESSION['user_id'] ?? null;
        $safeUserId = $user_id !== null && $user_id !== '' ? (int) $user_id : ($sessionUserId !== null ? (int) $sessionUserId : null);
        $safeFullname = trim((string) ($fullname ?: audit_session_fullname()));
        $safeFullname = $safeFullname !== '' ? $safeFullname : null;
        $safeRecordId = $record_id !== null && $record_id !== '' ? substr((string) $record_id, 0, 100) : null;

        $stmt = $pdo->prepare("
            INSERT INTO audit_logs
                (user_id, fullname, action_type, module_name, record_id, description, ip_address, device_info)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $safeUserId,
            $safeFullname,
            strtoupper(substr($action_type, 0, 50)),
            substr($module_name, 0, 100),
            $safeRecordId,
            $description !== null ? trim($description) : null,
            audit_request_ip(),
            $_SERVER['HTTP_USER_AGENT'] ?? '',
        ]);
    } catch (Throwable $e) {
        error_log('Audit log failed: ' . $e->getMessage());
    }
}

function audit_current_fullname(PDO $pdo): string {
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    if ($userId <= 0) {
        return audit_session_fullname();
    }

    try {
        $stmt = $pdo->prepare("
            SELECT TRIM(CONCAT(first_name, ' ', COALESCE(NULLIF(middle_name, ''), ''), IF(middle_name IS NULL OR middle_name = '', '', ' '), last_name)) AS fullname
            FROM sdopang1_user
            WHERE user_id = ?
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $name = trim((string) $stmt->fetchColumn());
        return $name !== '' ? $name : audit_session_fullname();
    } catch (Throwable $e) {
        return audit_session_fullname();
    }
}
