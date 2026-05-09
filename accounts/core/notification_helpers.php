<?php

function notification_table_exists(PDO $pdo): bool {
    static $exists = null;

    if ($exists !== null) {
        return $exists;
    }

    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'sdopang1_notifications'");
        $exists = $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        $exists = false;
    }

    return $exists;
}

function notification_create(
    PDO $pdo,
    string $title,
    string $message,
    ?string $link = null,
    ?int $userId = null,
    ?int $roleId = null,
    string $type = 'info',
    ?int $createdBy = null
): void {
    if (!notification_table_exists($pdo)) {
        return;
    }

    $stmt = $pdo->prepare("
        INSERT INTO sdopang1_notifications
            (user_id, role_id, title, message, link, notification_type, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $roleId, $title, $message, $link, $type, $createdBy]);
}

function notification_unread_for_user(PDO $pdo, int $userId, int $roleId, int $limit = 10): array {
    if (!notification_table_exists($pdo)) {
        return [];
    }

    $stmt = $pdo->prepare("
        SELECT *
        FROM sdopang1_notifications
        WHERE is_read = 0
          AND (
              user_id = ?
              OR (user_id IS NULL AND role_id = ?)
              OR (user_id IS NULL AND role_id IS NULL)
          )
        ORDER BY created_at DESC, notification_id DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $roleId, PDO::PARAM_INT);
    $stmt->bindValue(3, $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function notification_mark_read(PDO $pdo, int $notificationId, int $userId, int $roleId): ?string {
    if (!notification_table_exists($pdo)) {
        return null;
    }

    $stmt = $pdo->prepare("
        SELECT link
        FROM sdopang1_notifications
        WHERE notification_id = ?
          AND (
              user_id = ?
              OR (user_id IS NULL AND role_id = ?)
              OR (user_id IS NULL AND role_id IS NULL)
          )
        LIMIT 1
    ");
    $stmt->execute([$notificationId, $userId, $roleId]);
    $link = $stmt->fetchColumn();

    if ($link === false) {
        return null;
    }

    $update = $pdo->prepare("
        UPDATE sdopang1_notifications
        SET is_read = 1, read_at = NOW()
        WHERE notification_id = ?
    ");
    $update->execute([$notificationId]);

    return (string) $link;
}
