<?php
/**
 * admin-process.php - Add/Edit Admin Handler (SECURE VERSION)
 * Fixed SQL Injection vulnerabilities
 */

session_start();
require_once 'config.php';

// CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// FUNCTIONALITY: ADD - Create new admin
if(isset($_POST['add'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF validation failed');
    }
    
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    
    // Validate email
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: admin-management.php?error=invalid_email');
        exit();
    }
    
    // Validate password match
    if($password !== $confirm_password) {
        header('Location: admin-management.php?error=password_mismatch');
        exit();
    }
    
    // Validate password length
    if(strlen($password) < 4) {
        header('Location: admin-management.php?error=password_short');
        exit();
    }
    
    // Check if email already exists (using prepared statement)
    $check_stmt = $conn->prepare("SELECT User_id FROM admin WHERE Email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if($check_result->num_rows > 0) {
        header('Location: admin-management.php?error=email_exists');
        exit();
    }
    $check_stmt->close();
    
    // Hash password with bcrypt
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new admin using prepared statement
    $insert_stmt = $conn->prepare("INSERT INTO admin (Email, Password_hash, Role, Created_at, Is_active) VALUES (?, ?, ?, NOW(), 1)");
    $insert_stmt->bind_param("sss", $email, $hashed_password, $role);
    
    if($insert_stmt->execute()) {
        header('Location: admin-management.php?msg=added');
    } else {
        header('Location: admin-management.php?error=db_error');
    }
    $insert_stmt->close();
    exit();
}

// FUNCTIONALITY: EDIT - Update existing admin
if(isset($_POST['edit'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('CSRF validation failed');
    }
    
    $id = filter_var($_POST['admin_id'], FILTER_VALIDATE_INT);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $role = $_POST['role'];
    $new_password = $_POST['new_password'] ?? '';
    
    if(!$id) {
        header('Location: admin-management.php?error=invalid_id');
        exit();
    }
    
    // Validate email
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: admin-management.php?error=invalid_email');
        exit();
    }
    
    // Update with or without password change
    if(!empty($new_password)) {
        if(strlen($new_password) < 4) {
            header('Location: admin-management.php?error=password_short');
            exit();
        }
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_stmt = $conn->prepare("UPDATE admin SET Email = ?, Role = ?, Password_hash = ? WHERE User_id = ?");
        $update_stmt->bind_param("sssi", $email, $role, $hashed_password, $id);
    } else {
        $update_stmt = $conn->prepare("UPDATE admin SET Email = ?, Role = ? WHERE User_id = ?");
        $update_stmt->bind_param("ssi", $email, $role, $id);
    }
    
    if($update_stmt->execute()) {
        header('Location: admin-management.php?msg=edited');
    } else {
        header('Location: admin-management.php?error=db_error');
    }
    $update_stmt->close();
    exit();
}

header('Location: admin-management.php');
exit();
?>