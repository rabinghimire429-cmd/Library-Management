<?php
/**
 * login-process.php - Login Authentication Handler
 * Author: Rabin Ghimire
 * Module: Authentication & Dashboard
 * 
 * FUNCTIONALITY: Login, Validate, Account Lockout, bcrypt encryption
 */

session_start();
require_once '../config.php';

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$selected_role = $_POST['role'] ?? 'Member';

// Query user by email
$result = $conn->query("SELECT * FROM admin WHERE Email = '$email'");

if($result->num_rows == 1) {
    $admin = $result->fetch_assoc();
    
    // =============================================
    // ACCOUNT LOCKOUT CHECK
    // =============================================
    if($admin['locked_until'] !== NULL && strtotime($admin['locked_until']) > time()) {
        $remaining = ceil((strtotime($admin['locked_until']) - time()) / 60);
        header("Location: ../index.php?error=locked&minutes=$remaining");
        exit();
    }
    
    // =============================================
    // PASSWORD VERIFICATION (bcrypt)
    // =============================================
    if(password_verify($password, $admin['Password_hash'])) {
        
        // =============================================
        // ROLE VALIDATION
        // =============================================
        if($selected_role == 'Librarian' && $admin['Role'] != 'Librarian') {
            header('Location: ../index.php?error=wrong_role&role=member');
            exit();
        }
        
        if($selected_role == 'Member' && $admin['Role'] != 'Member') {
            header('Location: ../index.php?error=wrong_role&role=librarian');
            exit();
        }
        
        // =============================================
        // RESET FAILED ATTEMPTS ON SUCCESSFUL LOGIN
        // =============================================
        $conn->query("UPDATE admin SET failed_attempts = 0, locked_until = NULL WHERE User_id = " . $admin['User_id']);
        
        // =============================================
        // CREATE SESSION
        // =============================================
        session_regenerate_id(true);
        
        $_SESSION['admin_id'] = $admin['User_id'];
        $_SESSION['admin_email'] = $admin['Email'];
        $_SESSION['admin_role'] = $admin['Role'];
        $_SESSION['LAST_ACTIVITY'] = time();
        
        // Update last login
        $conn->query("UPDATE admin SET Last_login = NOW() WHERE User_id = " . $admin['User_id']);
        
        // Redirect based on role
        if($admin['Role'] == 'Librarian') {
            header('Location: ../librarian-dashboard.php');
        } else {
            header('Location: ../member-dashboard.php');
        }
        exit();
        
    } else {
        // =============================================
        // INCREMENT FAILED ATTEMPTS
        // =============================================
        $failed = $admin['failed_attempts'] + 1;
        
        if($failed >= 5) {
            // Lock account for 15 minutes
            $conn->query("UPDATE admin SET failed_attempts = $failed, locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE User_id = " . $admin['User_id']);
            header("Location: ../index.php?error=locked&minutes=15");
        } else {
            $conn->query("UPDATE admin SET failed_attempts = $failed WHERE User_id = " . $admin['User_id']);
            $remaining = 5 - $failed;
            header("Location: ../index.php?error=failed&remaining=$remaining");
        }
        exit();
    }
}

// User not found
header('Location: ../index.php?error=1');
exit();
?>