<?php

function opcrf_json(array $payload, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function opcrf_log(PDO $pdo, int $opcrfId, string $action, ?string $remarks = null): void {
    $stmt = $pdo->prepare("
        INSERT INTO sdopang1_opcrf_logs (opcrf_id, action_taken, action_by, remarks)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$opcrfId, $action, $_SESSION['user_id'] ?? null, $remarks]);
}

function opcrf_user_can_manage_office(PDO $pdo, int $officeId): bool {
    require_login();

    if ((int) ($_SESSION['role_id'] ?? 0) === 1) {
        return true;
    }

    $user = current_user_row($pdo);
    $stmt = $pdo->prepare("SELECT * FROM sdopang1_offices WHERE office_id = ? LIMIT 1");
    $stmt->execute([$officeId]);
    $office = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$office) {
        return false;
    }

    $userId = (int) ($_SESSION['user_id'] ?? 0);
    $isAssignedUnitHead = (int) ($office['unit_head'] ?? 0) === $userId;
    $isLinkedSchoolHead = ($office['office_category'] ?? '') === 'School'
        && (string) ($office['school_id'] ?? '') !== ''
        && (string) ($office['school_id'] ?? '') === (string) ($user['school_id'] ?? '')
        && (
            (string) ($user['office_role'] ?? '') === 'Head'
            || (int) ($_SESSION['role_id'] ?? 0) === 3
            || (int) ($office['office_head'] ?? 0) === $userId
        );
    $isDivisionOfficeHead = ($office['office_category'] ?? '') === 'Division Office'
        && (string) ($user['office_role'] ?? '') === 'Head'
        && (int) ($user['office_id'] ?? 0) === $officeId;

    return $isAssignedUnitHead || $isLinkedSchoolHead || $isDivisionOfficeHead;
}

function opcrf_user_can_manage_content(PDO $pdo, int $opcrfId): bool {
    $stmt = $pdo->prepare("SELECT office_id FROM sdopang1_opcrf WHERE opcrf_id = ? LIMIT 1");
    $stmt->execute([$opcrfId]);
    $officeId = (int) $stmt->fetchColumn();

    return $officeId > 0 && opcrf_user_can_manage_office($pdo, $officeId);
}

function opcrf_upload_file(array $file, string $folder, array $allowedExtensions): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('File upload error');
    }

    $originalName = (string) ($file['name'] ?? '');
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException('Invalid file type');
    }

    if (($file['size'] ?? 0) > 20 * 1024 * 1024) {
        throw new RuntimeException('File too large. Maximum size is 20MB');
    }

    if (!is_dir($folder) && !mkdir($folder, 0777, true)) {
        throw new RuntimeException('Unable to create upload folder');
    }

    $safeName = uniqid('opcrf_', true) . '.' . $extension;
    $path = rtrim($folder, '/\\') . '/' . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $path)) {
        throw new RuntimeException('Upload failed');
    }

    return [
        'file_name' => $originalName,
        'file_path' => $path,
        'file_type' => $extension,
        'file_size' => (int) $file['size'],
    ];
}
