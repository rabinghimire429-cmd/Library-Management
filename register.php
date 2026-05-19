<?php
/**

 * This page allows new members to create an account.
 * Passwords are hashed using bcrypt before storing in database.
 */

session_start();
require_once 'config.php';

$error = '';
$success = '';

// =============================================
// FUNCTIONALITY: ADD & VALIDATE - Process registration form
// =============================================
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    
    // FUNCTIONALITY: VALIDATE - Check if passwords match
    if($password !== $confirm) {
        $error = "Passwords do not match!";
    } 
    // FUNCTIONALITY: VALIDATE - Check password length
    elseif(strlen($password) < 4) {
        $error = "Password must be at least 4 characters!";
    } else {
        // FUNCTIONALITY: VALIDATE - Check if email already exists
        $check = $conn->query("SELECT * FROM admin WHERE Email = '$email'");
        if($check->num_rows > 0) {
            $error = "Email already exists!";
        } else {
            // =============================================
            // SECURITY: bcrypt password hashing
            // This converts plain text password to a secure hash
            // The hash cannot be reversed back to plain text
            // =============================================
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert into admin table with hashed password
            $conn->query("INSERT INTO admin (Email, Password_hash, Role, Created_at) VALUES ('$email', '$hashed_password', 'Member', NOW())");
            $admin_id = $conn->insert_id;
            
            // Insert into member table
            $conn->query("INSERT INTO member (admin_id, full_name, email, phone) VALUES ($admin_id, '$fullname', '$email', '$phone')");
            
            $success = "Registration successful! Please login.";
            header('refresh:2;url=index.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - LibTech Solutions</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .register-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(12px);
            border-radius: 30px;
            padding: 40px;
            width: 450px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #fff, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 12px;
            color: white;
        }
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            cursor: pointer;
        }
        .error { color: #f87171; text-align: center; margin-bottom: 15px; }
        .success { color: #34d399; text-align: center; margin-bottom: 15px; }
        a { color: #818cf8; text-decoration: none; display: block; text-align: center; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="register-card">
        <h2><i class="fas fa-user-plus"></i> Create Account</h2>
        <?php if($error): ?>
            <div class="error">❌ <?php echo $error; ?></div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="success">✅ <?php echo $success; ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="fullname" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="tel" name="phone" placeholder="Phone Number" required>
            <input type="password" name="password" placeholder="Password (min 4 characters)" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            <button type="submit">Register Now</button>
        </form>
        <a href="index.php">← Already have an account? Login</a>
    </div>
</body>
</html>