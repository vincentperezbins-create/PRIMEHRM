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

function access_denied(string $redirect = '/PRIMEHR/accounts/index.php'): void {
    if (!headers_sent()) {
        http_response_code(403);
    }

    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    if (stripos($accept, 'application/json') !== false || strtolower($requestedWith) === 'xmlhttprequest') {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['success' => false, 'message' => 'Access denied.']);
        exit;
    }

    $safeRedirect = htmlspecialchars($redirect, ENT_QUOTES, 'UTF-8');
    $redirectJson = json_encode($redirect, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Access Denied | PRIMEHR</title>
        <script src="/PRIMEHR/accounts/src/plugins/sweetalert2/sweetalert2.all.js"></script>
    </head>
    <body>
        <script>
            var options = {
                icon: 'error',
                title: 'Access Denied',
                text: 'You do not have permission to access this page.',
                confirmButtonText: 'Go back',
                allowOutsideClick: false
            };
            var redirectUrl = <?= $redirectJson ?>;
            var goBack = function () {
                window.location.href = redirectUrl;
            };

            if (window.Swal && Swal.fire) {
                Swal.fire(options).then(goBack);
            } else if (window.swal) {
                swal(options).then(goBack);
            } else {
                window.alert(options.text);
                goBack();
            }
        </script>
        <noscript>
            Access denied. <a href="<?= $safeRedirect ?>">Go back</a>
        </noscript>
    </body>
    </html>
    <?php
    exit;
}

// allow only specific roles
function require_role($roles = []) {
    require_login();

    $roleId = (int) ($_SESSION['role_id'] ?? 0);
    $allowedRoles = array_map('intval', $roles);

    if (!in_array($roleId, $allowedRoles, true)) {
        access_denied();
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
    $column = $columns[$area];
    $canValidate = (int) ($user[$column] ?? ($_SESSION[$column] ?? 0));
    $_SESSION[$column] = $canValidate;

    return $canValidate === 1;
}

function require_validator(PDO $pdo, string $area): void {
    if (!user_can_validate($pdo, $area)) {
        access_denied();
    }
}

function require_any_validator(PDO $pdo): void {
    $areas = ['201', 'opcrf', 'ipcrf', 'leave'];

    foreach ($areas as $area) {
        if (user_can_validate($pdo, $area)) {
            return;
        }
    }

    access_denied();
}

function user_has_any_validator_permission(PDO $pdo): bool {
    $areas = ['201', 'opcrf', 'ipcrf', 'leave'];

    foreach ($areas as $area) {
        if (user_can_validate($pdo, $area)) {
            return true;
        }
    }

    return false;
}

function current_user($userModel) {
    return $userModel->getUserById($_SESSION['user_id']);
}
