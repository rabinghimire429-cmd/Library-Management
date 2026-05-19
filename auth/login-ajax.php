<?php
/**
 
 * 
 * This file processes login requests via AJAX (no page reload).
 * It uses password_verify() to check bcrypt hashed passwords.
 */

session_start();
require_once '../config.php';

// Return JSON response (for AJAX)
header('Content-Type: application/json');

// Get login data from AJAX request
$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';
$selected_role = $data['role'] ?? 'Member';

// =============================================
// FUNCTIONALITY: LOGIN - Find user by email
// =============================================
$result = $conn->query("SELECT * FROM admin WHERE Email = '$email'");

if($result->num_rows == 1) {
    $admin = $result->fetch_assoc();
    
    // =============================================
    // FUNCTIONALITY: VALIDATE - Check if account is locked
    // This implements the Risk Register mitigation for brute force attacks
    // =============================================
    if($admin['locked_until'] !== NULL && strtotime($admin['locked_until']) > time()) {
        $remaining = ceil((strtotime($admin['locked_until']) - time()) / 60);
        echo json_encode(['success' => false, 'message' => "Account locked. Try again after $remaining minutes."]);
        exit();
    }
    
    // =============================================
    // FUNCTIONALITY: VALIDATE - Check if account is active
    // =============================================
    if($admin['Is_active'] != 1) {
        echo json_encode(['success' => false, 'message' => "Account is blocked. Contact librarian."]);
        exit();
    }
    
    // =============================================
    // SECURITY: bcrypt password verification
    // password_verify() compares plain text password with stored hash
    // This implements the Risk Register mitigation for password database breach
    // =============================================
    if(password_verify($password, $admin['Password_hash'])) {
        
        // =============================================
        // FUNCTIONALITY: VALIDATE - Role-based access control
        // Prevents role escalation (Member trying to login as Librarian)
        // This implements the Risk Register mitigation for role escalation
        // =============================================
        if($selected_role == 'Librarian' && $admin['Role'] != 'Librarian') {
            echo json_encode(['success' => false, 'message' => 'This email belongs to a Member. Please select Member Login.']);
            exit();
        }
        if($selected_role == 'Member' && $admin['Role'] != 'Member') {
            echo json_encode(['success' => false, 'message' => 'This email belongs to a Librarian. Please select Librarian Login.']);
            exit();
        }
        
        // =============================================
        // SECURITY: Reset failed attempts on successful login
        // =============================================
        $conn->query("UPDATE admin SET failed_attempts = 0, locked_until = NULL WHERE User_id = " . $admin['User_id']);
        
        // =============================================
        // SECURITY: Regenerate session ID to prevent session hijacking
        // This implements the Risk Register mitigation for session hijacking
        // =============================================
        session_regenerate_id(true);
        
        // =============================================
        // FUNCTIONALITY: LOGIN - Create session variables
        // =============================================
        $_SESSION['admin_id'] = $admin['User_id'];
        $_SESSION['admin_email'] = $admin['Email'];
        $_SESSION['admin_role'] = $admin['Role'];
        $_SESSION['LAST_ACTIVITY'] = time();  // For session timeout
        
        // Update last login timestamp in database
        $conn->query("UPDATE admin SET Last_login = NOW() WHERE User_id = " . $admin['User_id']);
        
        echo json_encode(['success' => true, 'role' => $admin['Role']]);
        
    } else {
        // =============================================
        // SECURITY: Increment failed attempts counter
        // Implements account lockout after 5 failed attempts
        // This implements the Risk Register mitigation for brute force attacks
        // =============================================
        $failed = $admin['failed_attempts'] + 1;
        if($failed >= 5) {
            // Lock account for 15 minutes
            $conn->query("UPDATE admin SET failed_attempts = $failed, locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE User_id = " . $admin['User_id']);
            echo json_encode(['success' => false, 'message' => 'Too many failed attempts. Account locked for 15 minutes.']);
        } else {
            $conn->query("UPDATE admin SET failed_attempts = $failed WHERE User_id = " . $admin['User_id']);
            $remaining = 5 - $failed;
            echo json_encode(['success' => false, 'message' => "Invalid password. $remaining attempt(s) remaining."]);
        }
    }
} else {
    // User not found - generic message to prevent email harvesting
    echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
}
?>