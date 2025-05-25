<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/database.php';

function login($username, $password, $role) {
    global $mysqli;
    
    $stmt = $mysqli->prepare("SELECT id, username, password, display_name, role FROM users WHERE username = ? AND role = ?");
    $stmt->bind_param("ss", $username, $role);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['display_name'] = $user['display_name'];
            $_SESSION['role'] = $user['role'];
            
            if ($role === 'judge') {
                $stmt = $mysqli->prepare("SELECT id FROM judges WHERE user_id = ?");
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                $judge = $stmt->get_result()->fetch_assoc();
                $_SESSION['judge_id'] = $judge['id'];
            }
            
            return true;
        }
    }
    return false;
}

function login_by_email($email, $password) {
    global $mysqli;
    
    $stmt = $mysqli->prepare("SELECT id, username, password, display_name, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['display_name'] = $user['display_name'];
            $_SESSION['role'] = $user['role'];
            
            if ($user['role'] === 'judge') {
                $stmt = $mysqli->prepare("SELECT id FROM judges WHERE user_id = ?");
                $stmt->bind_param("i", $user['id']);
                $stmt->execute();
                $judge_result = $stmt->get_result();
                if ($judge_result->num_rows === 1) {
                    $judge = $judge_result->fetch_assoc();
                    $_SESSION['judge_id'] = $judge['id'];
                }
            }
            
            return true;
        }
    }
    return false;
}

function register($username, $email, $password, $display_name, $role = 'user') {
    global $mysqli;
    
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        return false;
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $mysqli->prepare("INSERT INTO users (username, email, password, display_name, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $email, $hashed_password, $display_name, $role);
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        
        if ($role === 'judge') {
            $stmt = $mysqli->prepare("INSERT INTO judges (user_id) VALUES (?)");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
        }
        
        return true;
    }
    
    return false;
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

function is_user() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'user';
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
        header("Location: index.php?error=unauthorized");
        exit();
    }
}

function require_judge() {
    require_login();
    if (!is_judge()) {
        header("Location: index.php?error=unauthorized");
        exit();
    }
}

function require_user() {
    require_login();
    if (!is_user()) {
        header("Location: index.php?error=unauthorized");
        exit();
    }
}

function require_role($required_roles) {
    require_login();
    
    if (is_array($required_roles)) {
        foreach ($required_roles as $role) {
            if ($_SESSION['role'] === $role) {
                return;
            }
        }
    } else {
        if ($_SESSION['role'] === $required_roles) {
            return;
        }
    }
    
    header("Location: index.php?error=unauthorized");
    exit();
}

function logout() {
    session_destroy();
    header("Location: login.php");
    exit();
}

function get_user_role() {
    return $_SESSION['role'] ?? null;
}

function get_user_display_name() {
    return $_SESSION['display_name'] ?? null;
}

function get_username() {
    return $_SESSION['username'] ?? null;
}

function has_access($required_role) {
    if (!is_logged_in()) {
        return false;
    }
    
    switch ($required_role) {
        case 'admin':
            return is_admin();
        case 'judge':
            return is_judge();
        case 'user':
            return is_user();
        default:
            return false;
    }
}

function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function get_judge_id() {
    return $_SESSION['judge_id'] ?? null;
}

function get_user_info($user_id) {
    global $mysqli;
    
    $stmt = $mysqli->prepare("SELECT username, email, display_name, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows === 1 ? $result->fetch_assoc() : false;
}

function update_user_role($user_id, $new_role) {
    global $mysqli;
    
    $stmt = $mysqli->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $new_role, $user_id);
    $result = $stmt->execute();
    
    if ($result && $new_role === 'judge') {
        $stmt = $mysqli->prepare("SELECT id FROM judges WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            $stmt = $mysqli->prepare("INSERT INTO judges (user_id) VALUES (?)");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
        }
    } elseif ($result && $new_role !== 'judge') {
        $stmt = $mysqli->prepare("DELETE FROM judges WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
    
    return $result;
}
?>