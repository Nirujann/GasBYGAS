<?php
session_start();
require_once "includes/db_connect.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = connect_db();

    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, password_hash, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];

            if ($_SESSION['role'] === 'head_office') {
                header("Location: dashboard_headoffice.php");
                exit;
            }
            
            if ($_SESSION['role'] === 'outlet_manager' || $_SESSION['role'] === 'consumer') {
                header("Location: dashboard.php");
                exit;
            }
        }
    }

    // If login fails, store error message in session
    $_SESSION['login_error'] = "Incorrect username or password";
    header("Location: index.php");
    exit;
}
?>