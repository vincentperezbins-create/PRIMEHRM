<?php
session_start();
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/core/audit.php';

audit_log(
    $pdo,
    $_SESSION['user_id'] ?? null,
    audit_session_fullname(),
    'LOGOUT',
    'Authentication',
    $_SESSION['user_id'] ?? null,
    'User signed out.'
);

// remove all session data
$_SESSION = [];

// destroy session
session_destroy();

// redirect to login page
header("Location: login.php");
exit;
