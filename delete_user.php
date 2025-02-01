<?php
session_start();

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'head_office') {
    $_SESSION['error_message'] = "Unauthorized access";
    header('Location: index.php');
    exit();
}

// Verify if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Invalid request method";
    header('Location: manage_users.php');
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error_message'] = "Invalid security token";
    header('Location: manage_users.php');
    exit();
}

// Database configuration
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=gasbygas;charset=utf8mb4",
        "root",
        "",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Validate user_id
    if (!isset($_POST['user_id']) || !is_numeric($_POST['user_id'])) {
        throw new Exception("Invalid user ID");
    }

    $userId = (int)$_POST['user_id'];

    // Begin transaction
    $pdo->beginTransaction();

    // Check if user exists and get their role
    $checkStmt = $pdo->prepare("SELECT user_id, role FROM users WHERE user_id = ?");
    $checkStmt->execute([$userId]);
    $user = $checkStmt->fetch();

    if (!$user) {
        throw new Exception("User not found");
    }

    // Prevent deleting own account
    if ($userId === $_SESSION['user_id']) {
        throw new Exception("Cannot delete your own account");
    }

    // Prevent deleting other admins (optional security measure)
    if ($user['role'] === 'admin' && $_SESSION['role'] !== 'super_admin') {
        throw new Exception("Cannot delete admin accounts");
    }

   
    $deleteUserStmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $deleteUserStmt->execute([$userId]);

    // Commit transaction
    $pdo->commit();

    // Log the action
    error_log("User {$_SESSION['user_id']} deleted user {$userId}");
    
    $_SESSION['success_message'] = "User deleted successfully!";

} catch (PDOException $e) {
    // Database error handling
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log("Database Error in user deletion: " . $e->getMessage());
    $_SESSION['error_message'] = "Database error occurred. Please try again.";

} catch (Exception $e) {
    // General error handling
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    $_SESSION['error_message'] = $e->getMessage();
}

// Redirect back to manage users page
header('Location: manage_users.php');
exit();
?>