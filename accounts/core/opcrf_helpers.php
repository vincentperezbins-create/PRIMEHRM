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
