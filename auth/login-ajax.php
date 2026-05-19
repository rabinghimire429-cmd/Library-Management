<?php
/**
 * login-ajax.php - AJAX Login Handler
 */

session_start();

// Enable error reporting for debugging (remove after fixing)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$conn = new mysqli('localhost', 'root', '', 'libtech_db');

// Check connection
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

// Return JSON response
header('Content-Type: application/json');

// Get login data from AJAX request
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Check if data was received
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received. Raw input: ' . $input]);
    exit();
}

$email = isset($data['email']) ? trim($data['email']) : '';
$password = isset($data['password']) ? $data['password'] : '';
$selected_role = isset($data['role']) ? $data['role'] : 'Member';

// Validate input
if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit();
}

// Prepare statement to prevent SQL injection
$stmt = $conn->prepare("SELECT * FROM admin WHERE Email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $admin = $result->fetch_assoc();
    
    // Check if account is locked
    if ($admin['locked_until'] !== NULL && strtotime($admin['locked_until']) > time()) {
        $remaining = ceil((strtotime($admin['locked_until']) - time()) / 60);
        echo json_encode(['success' => false, 'message' => "Account locked. Try again after $remaining minutes."]);
        exit();
    }
    
    // Check if account is active
    if ($admin['Is_active'] != 1) {
        echo json_encode(['success' => false, 'message' => "Account is blocked. Contact librarian."]);
        exit();
    }
    
    // Verify password - supports both bcrypt and plain text
    $password_valid = false;
    
    if (strpos($admin['Password_hash'], '$2y$') === 0) {
        // Bcrypt hash
        $password_valid = password_verify($password, $admin['Password_hash']);
    } else {
        // Plain text password (for existing test accounts)
        $password_valid = ($password == $admin['Password_hash']);
    }
    
    if ($password_valid) {
        
        // Role validation
        if ($selected_role == 'Librarian' && $admin['Role'] != 'Librarian') {
            echo json_encode(['success' => false, 'message' => 'This email belongs to a Member. Please select Member Login.']);
            exit();
        }
        if ($selected_role == 'Member' && $admin['Role'] != 'Member') {
            echo json_encode(['success' => false, 'message' => 'This email belongs to a Librarian. Please select Librarian Login.']);
            exit();
        }
        
        // Reset failed attempts
        $reset_stmt = $conn->prepare("UPDATE admin SET failed_attempts = 0, locked_until = NULL WHERE User_id = ?");
        $reset_stmt->bind_param("i", $admin['User_id']);
        $reset_stmt->execute();
        $reset_stmt->close();
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['admin_id'] = $admin['User_id'];
        $_SESSION['admin_email'] = $admin['Email'];
        $_SESSION['admin_role'] = $admin['Role'];
        $_SESSION['LAST_ACTIVITY'] = time();
        
        // Update last login
        $update_stmt = $conn->prepare("UPDATE admin SET Last_login = NOW() WHERE User_id = ?");
        $update_stmt->bind_param("i", $admin['User_id']);
        $update_stmt->execute();
        $update_stmt->close();
        
        echo json_encode(['success' => true, 'role' => $admin['Role']]);
        
    } else {
        // Invalid password - increment failed attempts
        $failed = $admin['failed_attempts'] + 1;
        
        if ($failed >= 5) {
            $lock_time = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $lock_stmt = $conn->prepare("UPDATE admin SET failed_attempts = ?, locked_until = ? WHERE User_id = ?");
            $lock_stmt->bind_param("isi", $failed, $lock_time, $admin['User_id']);
            $lock_stmt->execute();
            $lock_stmt->close();
            
            echo json_encode(['success' => false, 'message' => 'Too many failed attempts. Account locked for 15 minutes.']);
        } else {
            $update_stmt = $conn->prepare("UPDATE admin SET failed_attempts = ? WHERE User_id = ?");
            $update_stmt->bind_param("ii", $failed, $admin['User_id']);
            $update_stmt->execute();
            $update_stmt->close();
            
            $remaining = 5 - $failed;
            echo json_encode(['success' => false, 'message' => "Invalid password. $remaining attempt(s) remaining."]);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
}

$stmt->close();
$conn->close();
?>