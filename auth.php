<?php
session_start();
require_once 'config/database.php';

function login($email, $password, $role) {
    global $mysqli;
    
    $table = $role === 'judge' ? 'judges' : 'users';
    $stmt = $mysqli->prepare("SELECT * FROM $table WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $role;
            $_SESSION['display_name'] = $user['display_name'];
            return true;
        }
    }
    return false;
}

function register($email, $password, $display_name) {
    global $mysqli;
    
    // Check if email already exists
    $stmt = $mysqli->prepare("SELECT 1 FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return false;
    }
    
    // Hash password and insert new user
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $mysqli->prepare("INSERT INTO users (email, password, display_name) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $hashed_password, $display_name);
    return $stmt->execute();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function is_judge() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'judge';
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit();
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        header("Location: index.php");
        exit();
    }
}

function require_judge() {
    require_login();
    if (!is_judge()) {
        header("Location: index.php");
        exit();
    }
}

function logout() {
    session_destroy();
    header("Location: login.php");
    exit();
}
?> 