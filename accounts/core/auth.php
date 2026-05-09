<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// redirect if not logged in
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /PRIMEHR/accounts/login.php");
        exit;
    }
}

// allow only specific roles
function require_role($roles = []) {
    require_login();

    if (!in_array($_SESSION['role_id'], $roles)) {
        die("Access denied");
    }
}

function current_user_row(PDO $pdo): array {
    $stmt = $pdo->prepare("SELECT * FROM sdopang1_user WHERE user_id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id'] ?? 0]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

function user_can_validate(PDO $pdo, string $area): bool {
    require_login();

    if ((int) ($_SESSION['role_id'] ?? 0) === 1) {
        return true;
    }

    $columns = [
        '201' => 'can_validate_201',
        'opcrf' => 'can_validate_opcrf',
        'ipcrf' => 'can_validate_ipcrf',
        'leave' => 'can_validate_leave',
    ];

    if (!isset($columns[$area])) {
        return false;
    }

    $user = current_user_row($pdo);
    return (int) ($user[$columns[$area]] ?? 0) === 1;
}

function require_validator(PDO $pdo, string $area): void {
    if (!user_can_validate($pdo, $area)) {
        die("Access denied");
    }
}

function current_user($userModel) {
    return $userModel->getUserById($_SESSION['user_id']);
}
