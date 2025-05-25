<?php
require_once 'config/database.php';

// Create demo admin account
$admin_email = 'admin@demo.com';
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$admin_name = 'Demo Admin';

$stmt = $mysqli->prepare("INSERT INTO judges (email, password, display_name, is_admin) VALUES (?, ?, ?, 1)");
$stmt->bind_param("sss", $admin_email, $admin_password, $admin_name);
$stmt->execute();
$stmt->close();

// Create demo judge account
$judge_email = 'judge@demo.com';
$judge_password = password_hash('judge123', PASSWORD_DEFAULT);
$judge_name = 'Demo Judge';

$stmt = $mysqli->prepare("INSERT INTO judges (email, password, display_name, is_admin) VALUES (?, ?, ?, 0)");
$stmt->bind_param("sss", $judge_email, $judge_password, $judge_name);
$stmt->execute();
$stmt->close();

// Create demo participant account
$user_email = 'user@demo.com';
$user_password = password_hash('user123', PASSWORD_DEFAULT);
$user_name = 'Demo User';

$stmt = $mysqli->prepare("INSERT INTO users (email, password, display_name) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $user_email, $user_password, $user_name);
$stmt->execute();
$stmt->close();

echo "Demo accounts created successfully!";
?> 