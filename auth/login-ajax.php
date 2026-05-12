<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';
$selected_role = $data['role'] ?? 'Member';

$result = $conn->query("SELECT * FROM admin WHERE Email = '$email'");

if($result->num_rows == 1) {
    $admin = $result->fetch_assoc();
    
    // Check if account is locked
    if($admin['locked_until'] !== NULL && strtotime($admin['locked_until']) > time()) {
        $remaining = ceil((strtotime($admin['locked_until']) - time()) / 60);
        echo json_encode(['success' => false, 'message' => "Account locked. Try again after $remaining minutes."]);
        exit();
    }
    
    // Check if account is active
    if($admin['Is_active'] != 1) {
        echo json_encode(['success' => false, 'message' => "Account is blocked. Contact librarian."]);
        exit();
    }
    
    // PLAIN TEXT PASSWORD VERIFICATION (since your database has '1234' as plain text)
    if($password == $admin['Password_hash']) {
        
        // Role validation
        if($selected_role == 'Librarian' && $admin['Role'] != 'Librarian') {
            echo json_encode(['success' => false, 'message' => 'This email belongs to a Member. Please select Member Login.']);
            exit();
        }
        if($selected_role == 'Member' && $admin['Role'] != 'Member') {
            echo json_encode(['success' => false, 'message' => 'This email belongs to a Librarian. Please select Librarian Login.']);
            exit();
        }
        
        // Reset failed attempts
        $conn->query("UPDATE admin SET failed_attempts = 0, locked_until = NULL WHERE User_id = " . $admin['User_id']);
        
        // Create session
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['User_id'];
        $_SESSION['admin_email'] = $admin['Email'];
        $_SESSION['admin_role'] = $admin['Role'];
        
        // Update last login
        $conn->query("UPDATE admin SET Last_login = NOW() WHERE User_id = " . $admin['User_id']);
        
        echo json_encode(['success' => true, 'role' => $admin['Role']]);
        
    } else {
        // Failed attempt
        $failed = $admin['failed_attempts'] + 1;
        if($failed >= 5) {
            $conn->query("UPDATE admin SET failed_attempts = $failed, locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE User_id = " . $admin['User_id']);
            echo json_encode(['success' => false, 'message' => 'Too many failed attempts. Account locked for 15 minutes.']);
        } else {
            $conn->query("UPDATE admin SET failed_attempts = $failed WHERE User_id = " . $admin['User_id']);
            $remaining = 5 - $failed;
            echo json_encode(['success' => false, 'message' => "Invalid password. $remaining attempt(s) remaining."]);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
}
?>