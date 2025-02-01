<?php

require_once 'includes/auth.php';
require_once 'includes/db_connect.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = connect_db();
    $user_id = $_SESSION['user_id'];
    
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
    
    // Check if email is already in use by another user
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        header("Location: profile.php?error=Email already in use");
        exit();
    }
    
    // Update profile
    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE user_id = ?");
    $stmt->bind_param("sssi", $name, $email, $phone, $user_id);
    
    if ($stmt->execute()) {
        header("Location: profile.php?success=Profile updated successfully");
    } else {
        header("Location: profile.php?error=Update failed");
    }
}