<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// generate token
function generateToken() {
    if (empty($_SESSION['token'])) {
        $_SESSION['token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['token'];
}

// validate token
function verifyToken($token) {
    return isset($_SESSION['token']) && hash_equals($_SESSION['token'], $token);
}