<?php
require_once __DIR__ . '/Database.php';

$con = Database::getInstance()->getConnection(); // <-- fixed

$adminUsername = "admin";
$adminPassword = "AdminPass123";

// Hash password
$hash = password_hash($adminPassword, PASSWORD_DEFAULT);

// 1. Insert into User if not exists
$stmt = $con->prepare("SELECT user_id FROM User WHERE username = ?");
$stmt->bind_param("s", $adminUsername);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $stmt->close();
    $insertUser = $con->prepare("INSERT INTO User (username, password, user_created) VALUES (?, ?, CURDATE())");
    $insertUser->bind_param("ss", $adminUsername, $hash);
    $insertUser->execute();
    $userId = $insertUser->insert_id; // get the new user ID
    $insertUser->close();
} else {
    $stmt->bind_result($userId);
    $stmt->fetch();
    $stmt->close();
}

// 2. Insert into Admin if not exists
$checkAdmin = $con->prepare("SELECT admin_id FROM Admin WHERE user_id = ?");
$checkAdmin->bind_param("i", $userId);
$checkAdmin->execute();
$checkAdmin->store_result();

if ($checkAdmin->num_rows === 0) {
    $checkAdmin->close();
    $insertAdmin = $con->prepare("INSERT INTO Admin (user_id) VALUES (?)");
    $insertAdmin->bind_param("i", $userId);
    $insertAdmin->execute();
    $insertAdmin->close();
    echo "Admin created successfully.";
} else {
    echo "Admin already exists.";
}

$con->close();
