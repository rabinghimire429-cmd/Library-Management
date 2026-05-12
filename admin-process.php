<?php
/**
 * admin-process.php - Add/Edit Admin Handler (Middle Layer)
 * Author: Rabin Ghimire
 * Module: Authentication & Dashboard
 * 
 * FUNCTIONALITY: Add, Edit, Validate
 */

session_start();
$conn = new mysqli('localhost', 'root', '', 'libtech_db');

// FUNCTIONALITY: ADD - Create new admin
if(isset($_POST['add'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    // FUNCTIONALITY: VALIDATE - Check if email already exists
    $check = $conn->query("SELECT * FROM admin WHERE email = '$email'");
    if($check->num_rows > 0) {
        header('Location: admin-management.php?error=email_exists');
        exit();
    }
    
    // Insert new admin
    $conn->query("INSERT INTO admin (email, password_hash, role) VALUES ('$email', '$password', '$role')");
    header('Location: admin-management.php?msg=added');
    exit();
}

// FUNCTIONALITY: EDIT - Update existing admin
if(isset($_GET['edit'])) {
    $id = $_GET['edit'];
    // Show edit form (simplified)
    header('Location: admin-management.php');
    exit();
}

header('Location: admin-management.php');
?>