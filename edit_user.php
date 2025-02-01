<?php
session_start();
require_once 'config/database.php';
require_once 'includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'head_office') {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['user_id'])) {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        $stmt = $conn->prepare("SELECT user_id, name, email, phone, role, nic, outlet_id FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $_GET['user_id']);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user) {
            echo json_encode(['success' => true, 'user' => $user]);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }

        $stmt->close();
        $conn->close();
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error fetching user data']);
        error_log("Error fetching user: " . $e->getMessage());
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        $required_fields = ['user_id', 'name', 'email', 'phone', 'role', 'nic'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("$field is required");
            }
        }

        // Sanitize inputs
        $user_id = filter_var($_POST['user_id'], FILTER_SANITIZE_NUMBER_INT);
        $name = $conn->real_escape_string($_POST['name']);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $phone = $conn->real_escape_string($_POST['phone']);
        $role = $conn->real_escape_string($_POST['role']);
        $nic = $conn->real_escape_string($_POST['nic']);
        $outlet_id = ($role === 'outlet_manager' && !empty($_POST['outlet_id'])) ? 
            filter_var($_POST['outlet_id'], FILTER_SANITIZE_NUMBER_INT) : null;

        $conn->begin_transaction();

        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, role = ?, nic = ?, outlet_id = ? WHERE user_id = ?");
        $stmt->bind_param("sssssii", $name, $email, $phone, $role, $nic, $outlet_id, $user_id);
        $stmt->execute();

        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            $stmt->bind_param("si", $password, $user_id);
            $stmt->execute();
        }
        $conn->commit();

        $_SESSION['success_message'] = "User updated successfully!";
        
        $stmt->close();
        $conn->close();

    } catch (Exception $e) {
        if (isset($conn) && $conn->connect_error === false) {
            $conn->rollback();
        }
        $_SESSION['error_message'] = "Error updating user: " . $e->getMessage();
        error_log("Error updating user: " . $e->getMessage());
    }

    header('Location: manage_users.php');
    exit();
}