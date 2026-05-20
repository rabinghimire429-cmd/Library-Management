<?php

require_once '../config.php';

header('Content-Type: application/json');

// Get login data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit();
}

$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$selected_role = $data['role'] ?? 'Member';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit();
}

// Find user by email
$stmt = $conn->prepare("SELECT * FROM admin WHERE Email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();

if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    $conn->close();
    exit();
}

$admin = $result->fetch_assoc();

// Check if account is active
if ($admin['Is_active'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Account is blocked. Contact librarian.']);
    $conn->close();
    exit();
}

// Check if account is locked
if ($admin['locked_until'] !== null && strtotime($admin['locked_until']) > time()) {
    $remaining = ceil((strtotime($admin['locked_until']) - time()) / 60);
    echo json_encode(['success' => false, 'message' => "Account locked. Try again after $remaining minutes."]);
    $conn->close();
    exit();
}

// Verify password
$stored_hash = $admin['Password_hash'];
$password_valid = false;

// Try bcrypt verification
if (password_verify($password, $stored_hash)) {
    $password_valid = true;
} 
// Fallback for plain text passwords
elseif ($password == $stored_hash) {
    $password_valid = true;
    // Rehash the plain text password
    $new_hash = password_hash($password, PASSWORD_DEFAULT);
    $update_stmt = $conn->prepare("UPDATE admin SET Password_hash = ? WHERE User_id = ?");
    $update_stmt->bind_param("si", $new_hash, $admin['User_id']);
    $update_stmt->execute();
    $update_stmt->close();
}

if (!$password_valid) {
    // Increment failed attempts
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
    $conn->close();
    exit();
}

// Password is correct - reset failed attempts
$reset_stmt = $conn->prepare("UPDATE admin SET failed_attempts = 0, locked_until = NULL WHERE User_id = ?");
$reset_stmt->bind_param("i", $admin['User_id']);
$reset_stmt->execute();
$reset_stmt->close();

// ROLE VALIDATION - Critical for Member vs Librarian
if ($selected_role == 'Librarian' && $admin['Role'] != 'Librarian') {
    echo json_encode(['success' => false, 'message' => 'This email belongs to a Member. Please select "Member Login" instead.']);
    $conn->close();
    exit();
}

if ($selected_role == 'Member' && $admin['Role'] != 'Member') {
    echo json_encode(['success' => false, 'message' => 'This email belongs to a Librarian. Please select "Librarian Login" instead.']);
    $conn->close();
    exit();
}

// Regenerate session ID for security
session_regenerate_id(true);

// Set session variables
$_SESSION['admin_id'] = $admin['User_id'];
$_SESSION['admin_email'] = $admin['Email'];
$_SESSION['admin_role'] = $admin['Role'];
$_SESSION['LAST_ACTIVITY'] = time();

// Update last login time
$update_stmt = $conn->prepare("UPDATE admin SET Last_login = NOW() WHERE User_id = ?");
$update_stmt->bind_param("i", $admin['User_id']);
$update_stmt->execute();
$update_stmt->close();

// Log successful login
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
$log_stmt = $conn->prepare("INSERT INTO login_log (email, success, ip_address, attempt_time, reason, user_agent) VALUES (?, 1, ?, NOW(), 'success', ?)");
$log_stmt->bind_param("sss", $email, $ip, $user_agent);
$log_stmt->execute();
$log_stmt->close();

$conn->close();

// Return success
echo json_encode(['success' => true, 'role' => $admin['Role']]);
?>