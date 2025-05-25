<?php
session_start();
require_once 'auth.php';

$_SESSION = array();

if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

session_destroy();

header("Location: login.php?message=logged_out");
exit();
?> 