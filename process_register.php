<?php

require_once 'includes/db_connect.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = connect_db();
    

    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $nic = filter_var($_POST['nic'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_type = $_POST['user_type'];
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: register.php?error=Invalid email format");
        exit();
    }
    
    if ($password !== $confirm_password) {
        header("Location: register.php?error=Passwords do not match");
        exit();
    }
    
    // Check if user already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? OR nic = ? OR phone = ?");
    $stmt->bind_param("sss", $email, $nic, $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        header("Location: register.php?error=User already exists");
        exit();
    }
    
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    
    $stmt = $conn->prepare("INSERT INTO users (name, nic, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $name, $nic, $email, $phone, $password_hash, $user_type);
    
    if ($stmt->execute()) {
        $_SESSION['registration_success'] = true;
        header("Location: login.php?success=1");
    } else {
        header("Location: register.php?error=Registration failed");
    }
    
    $stmt->close();
    $conn->close();
}