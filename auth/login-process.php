<?php
session_start();
require_once '../config.php';

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$selected_role = $_POST['role'] ?? 'Member';

// Query using your table column names (capital letters)
$result = $conn->query("SELECT * FROM admin WHERE Email = '$email' AND Is_active = 1");

if($result->num_rows == 1) {
    $admin = $result->fetch_assoc();
    
    // Plain text password comparison
    if($password == $admin['Password_hash']) {
        
        // Role validation
        if($selected_role == 'Librarian' && $admin['Role'] != 'Librarian') {
            header('Location: ../index.php?error=wrong_role&role=member');
            exit();
        }
        
        if($selected_role == 'Member' && $admin['Role'] != 'Member') {
            header('Location: ../index.php?error=wrong_role&role=librarian');
            exit();
        }
        
        session_regenerate_id(true);
        
        $_SESSION['admin_id'] = $admin['User_id'];
        $_SESSION['admin_email'] = $admin['Email'];
        $_SESSION['admin_role'] = $admin['Role'];
        $_SESSION['LAST_ACTIVITY'] = time();
        
        // Update last login
        $conn->query("UPDATE admin SET Last_login = NOW() WHERE User_id = " . $admin['User_id']);
        
        if($admin['Role'] == 'Librarian') {
            header('Location: ../librarian-dashboard.php');
        } else {
            header('Location: ../member-dashboard.php');
        }
        exit();
    }
}

header('Location: ../index.php?error=1');
exit();
?>