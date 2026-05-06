<?php
session_start();
require_once '../config.php';

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$selected_role = $_POST['role'] ?? 'Member';

$result = $conn->query("SELECT * FROM admin WHERE email = '$email' AND is_active = 1");

if($result->num_rows == 1) {
    $admin = $result->fetch_assoc();
    
    // Verify password using password_verify (works with bcrypt hash)
    if(password_verify($password, $admin['password_hash'])) {
        
        // Role validation
        if($selected_role == 'Librarian' && $admin['role'] != 'Librarian') {
            header('Location: ../index.php?error=wrong_role&role=member');
            exit();
        }
        
        if($selected_role == 'Member' && $admin['role'] != 'Member') {
            header('Location: ../index.php?error=wrong_role&role=librarian');
            exit();
        }
        
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_email'] = $admin['email'];
        $_SESSION['admin_role'] = $admin['role'];
        
        $conn->query("UPDATE admin SET last_login = NOW() WHERE admin_id = " . $admin['admin_id']);
        
        if($admin['role'] == 'Librarian') {
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