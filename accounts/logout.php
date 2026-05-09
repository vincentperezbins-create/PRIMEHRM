<?php
session_start();

// remove all session data
$_SESSION = [];

// destroy session
session_destroy();

// redirect to login page
header("Location: login.php");
exit;