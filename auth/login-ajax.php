<?php
/**
 
 * Includes user_agent logging for better security tracking
 */

session_start();
require_once '../config.php';

// Return JSON response for AJAX
header('Content-Type: application/json');

// =============================================
// SECURITY: Get user IP address and User Agent for audit trail
// =============================================
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
$attempt_time = date('Y-m-d H:i:s');

// Get login data from AJAX request
$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';
$selected_role = $data['role'] ?? 'Member';

// =============================================
// SECURITY: Prepared statement to prevent SQL injection
// =============================================
$stmt = $conn->prepare("SELECT * FROM admin WHERE Email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 1) {
    $admin = $result->fetch_assoc();
    
    // =============================================
    // SECURITY: Check if account is locked
    // =============================================
    if($admin['locked_until'] !== NULL && strtotime($admin['locked_until']) > time()) {
        $remaining = ceil((strtotime($admin['locked_until']) - time()) / 60);
        
        // Log failed attempt due to lockout with user_agent
        $log_stmt = $conn->prepare("INSERT INTO login_log (email, success, ip_address, attempt_time, reason, user_agent) VALUES (?, 0, ?, ?, 'account_locked', ?)");
        $log_stmt->bind_param("sssss", $email, $ip_address, $attempt_time, $user_agent);
        $log_stmt->execute();
        
        echo json_encode(['success' => false, 'message' => "Account locked. Try again after $remaining minutes."]);
        exit();
    }
    
    // =============================================
    // SECURITY: Check if account is active
    // =============================================
    if($admin['Is_active'] != 1) {
        // Log blocked attempt with user_agent
        $log_stmt = $conn->prepare("INSERT INTO login_log (email, success, ip_address, attempt_time, reason, user_agent) VALUES (?, 0, ?, ?, 'account_blocked', ?)");
        $log_stmt->bind_param("sssss", $email, $ip_address, $attempt_time, $user_agent);
        $log_stmt->execute();
        
        echo json_encode(['success' => false, 'message' => "Account is blocked. Contact librarian."]);
        exit();
    }
    
    // =============================================
    // SECURITY: bcrypt password verification
    // =============================================
    if(password_verify($password, $admin['Password_hash'])) {
        
        // =============================================
        // SECURITY: Role-based access control
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
        $reset_stmt = $conn->prepare("UPDATE admin SET failed_attempts = 0, locked_until = NULL WHERE User_id = ?");
        $reset_stmt->bind_param("i", $admin['User_id']);
        $reset_stmt->execute();
        
        // =============================================
        // SECURITY: Regenerate session ID to prevent session hijacking
        // =============================================
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['admin_id'] = $admin['User_id'];
        $_SESSION['admin_email'] = $admin['Email'];
        $_SESSION['admin_role'] = $admin['Role'];
        $_SESSION['LAST_ACTIVITY'] = time();
        
        // =============================================
        // SECURITY: Update last login timestamp
        // =============================================
        $update_stmt = $conn->prepare("UPDATE admin SET Last_login = NOW() WHERE User_id = ?");
        $update_stmt->bind_param("i", $admin['User_id']);
        $update_stmt->execute();
        
        // =============================================
        // AUDIT TRAIL: Log successful login with IP and User Agent
        // =============================================
        $log_stmt = $conn->prepare("INSERT INTO login_log (email, success, ip_address, attempt_time, reason, user_agent) VALUES (?, 1, ?, ?, 'success', ?)");
        $log_stmt->bind_param("sssss", $email, $ip_address, $attempt_time, $user_agent);
        $log_stmt->execute();
        
        echo json_encode(['success' => true, 'role' => $admin['Role']]);
        
    } else {
        // =============================================
        // SECURITY: Increment failed attempts counter
        // =============================================
        $failed = $admin['failed_attempts'] + 1;
        
        if($failed >= 5) {
            // Lock account for 15 minutes
            $lock_time = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $lock_stmt = $conn->prepare("UPDATE admin SET failed_attempts = ?, locked_until = ? WHERE User_id = ?");
            $lock_stmt->bind_param("isi", $failed, $lock_time, $admin['User_id']);
            $lock_stmt->execute();
            
            // Log failed attempt with lockout
            $log_stmt = $conn->prepare("INSERT INTO login_log (email, success, ip_address, attempt_time, reason, user_agent) VALUES (?, 0, ?, ?, 'locked_out', ?)");
            $log_stmt->bind_param("sssss", $email, $ip_address, $attempt_time, $user_agent);
            $log_stmt->execute();
            
            echo json_encode(['success' => false, 'message' => 'Too many failed attempts. Account locked for 15 minutes.']);
        } else {
            $update_stmt = $conn->prepare("UPDATE admin SET failed_attempts = ? WHERE User_id = ?");
            $update_stmt->bind_param("ii", $failed, $admin['User_id']);
            $update_stmt->execute();
            
            // Log failed attempt
            $log_stmt = $conn->prepare("INSERT INTO login_log (email, success, ip_address, attempt_time, reason, user_agent) VALUES (?, 0, ?, ?, 'invalid_password', ?)");
            $log_stmt->bind_param("sssss", $email, $ip_address, $attempt_time, $user_agent);
            $log_stmt->execute();
            
            $remaining = 5 - $failed;
            echo json_encode(['success' => false, 'message' => "Invalid password. $remaining attempt(s) remaining."]);
        }
    }
} else {
    // =============================================
    // SECURITY: User not found - generic message to prevent email harvesting
    // =============================================
    
    // Log failed attempt (email not found)
    $log_stmt = $conn->prepare("INSERT INTO login_log (email, success, ip_address, attempt_time, reason, user_agent) VALUES (?, 0, ?, ?, 'user_not_found', ?)");
    $log_stmt->bind_param("sssss", $email, $ip_address, $attempt_time, $user_agent);
    $log_stmt->execute();
    
    echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
}

// Close statements
if(isset($stmt)) $stmt->close();
if(isset($log_stmt)) $log_stmt->close();
if(isset($update_stmt)) $update_stmt->close();
if(isset($reset_stmt)) $reset_stmt->close();
if(isset($lock_stmt)) $lock_stmt->close();
?>