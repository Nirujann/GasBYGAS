<?php

require_once 'includes/auth.php';
require_once 'includes/db_connect.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = connect_db();
    $user_id = $_SESSION['user_id'];
    
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];
    
    // Verify current password
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!password_verify($current_password, $user['password_hash'])) {
        header("Location: profile.php?error=Current password is incorrect");
        exit();
    }
    
    if ($new_password !== $confirm_new_password) {
        header("Location: profile.php?error=New passwords do not match");
        exit();
    }
    
    // Update password
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
    $stmt->bind_param("si", $password_hash, $user_id);
    
    if ($stmt->execute()) {
        header("Location: profile.php?success=Password updated successfully");
    } else {
        header("Location: profile.php?error=Password update failed");
    }
}
