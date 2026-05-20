<?php
/**
 * 2fa-verify.php - Two-Factor Authentication Verification Page
 * Location: Same directory as index.php
 */

session_start();
require_once 'config.php';
require_once 'includes/2fa.php';

// Check if this is a 2FA pending login
if (!isset($_SESSION['2fa_pending_user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$email = $_SESSION['2fa_pending_email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $code = $_POST['code'] ?? '';
    
    // Verify the code
    if ($code == $_SESSION['2fa_code'] && $_SESSION['2fa_expires'] > time()) {
        // Code is correct - complete the login
        $_SESSION['admin_id'] = $_SESSION['2fa_pending_user_id'];
        $_SESSION['admin_email'] = $_SESSION['2fa_pending_email'];
        $_SESSION['admin_role'] = $_SESSION['2fa_pending_role'];
        $_SESSION['LAST_ACTIVITY'] = time();
        
        // Update last login time
        $update_stmt = $conn->prepare("UPDATE admin SET Last_login = NOW() WHERE User_id = ?");
        $update_stmt->bind_param("i", $_SESSION['admin_id']);
        $update_stmt->execute();
        $update_stmt->close();
        
        // Log successful login
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $log_stmt = $conn->prepare("INSERT INTO login_log (email, success, ip_address, attempt_time, reason, user_agent) VALUES (?, 1, ?, NOW(), '2fa_success', ?)");
        $log_stmt->bind_param("sss", $_SESSION['admin_email'], $ip, $user_agent);
        $log_stmt->execute();
        $log_stmt->close();
        
        // Clear 2FA session data
        unset($_SESSION['2fa_pending_user_id']);
        unset($_SESSION['2fa_pending_email']);
        unset($_SESSION['2fa_pending_role']);
        unset($_SESSION['2fa_code']);
        unset($_SESSION['2fa_expires']);
        
        // Redirect to appropriate dashboard
        if ($_SESSION['admin_role'] == 'Librarian') {
            header('Location: librarian-dashboard.php');
        } else {
            header('Location: member-dashboard.php');
        }
        exit();
    } else {
        $error = "Invalid or expired verification code. Please try again.";
        
        // Allow user to request new code
        if (isset($_POST['resend']) && $_POST['resend'] == '1') {
            $new_code = generate2FACode();
            $_SESSION['2fa_code'] = $new_code;
            $_SESSION['2fa_expires'] = time() + 300;
            error_log("New 2FA code for {$_SESSION['2fa_pending_email']}: $new_code");
            $error = "A new verification code has been sent.";
        }
    }
}

// Get the demo code for console display
$demo_code = $_SESSION['2fa_code'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication - LibTech Solutions</title>
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
        .container {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 30px;
            padding: 40px;
            width: 450px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.2);
        }
        h2 {
            background: linear-gradient(135deg, #fff, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
        }
        p {
            color: #b9bbbe;
            margin-bottom: 25px;
            line-height: 1.6;
        }
        .email-display {
            background: rgba(99,102,241,0.2);
            padding: 10px;
            border-radius: 12px;
            margin: 15px 0;
            font-weight: 600;
        }
        input {
            width: 100%;
            padding: 14px;
            margin: 15px 0;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 12px;
            color: white;
            font-size: 28px;
            text-align: center;
            letter-spacing: 8px;
            font-weight: 600;
        }
        input:focus {
            outline: none;
            border-color: #6366f1;
        }
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
            transition: all 0.3s;
        }
        button:hover {
            transform: translateY(-2px);
        }
        .resend-btn {
            background: rgba(255,255,255,0.1);
            margin-top: 10px;
        }
        .resend-btn:hover {
            background: rgba(255,255,255,0.2);
        }
        .error {
            color: #f87171;
            margin-bottom: 15px;
            padding: 10px;
            background: rgba(239,68,68,0.1);
            border-radius: 12px;
        }
        .demo-note {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            font-size: 12px;
            color: #8b8d94;
        }
        .demo-note i {
            color: #fbbf24;
            margin-right: 5px;
        }
        .back-link {
            color: #818cf8;
            text-decoration: none;
            font-size: 14px;
            display: inline-block;
            margin-top: 15px;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-shield-alt"></i> Two-Factor Authentication</h2>
        
        <p>Please enter the 6-digit verification code to complete your login.</p>
        
        <div class="email-display">
            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($email); ?>
        </div>
        
        <?php if($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="text" name="code" placeholder="000000" maxlength="6" autofocus required>
            <button type="submit"><i class="fas fa-check-circle"></i> Verify & Login</button>
        </form>
        
        <form method="POST">
            <input type="hidden" name="resend" value="1">
            <button type="submit" class="resend-btn"><i class="fas fa-redo-alt"></i> Resend Code</button>
        </form>
        
        <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Login</a>
        
        <div class="demo-note">
            <i class="fas fa-info-circle"></i> 
            <strong>Demo Mode:</strong> Open browser console to see the verification code.
        </div>
    </div>
    
    <script>
        // Display 2FA code in console for demo purposes
        <?php if($demo_code): ?>
            console.log('%c🔐 2FA Verification Code: <?php echo $demo_code; ?>', 'color: #10b981; font-size: 16px; font-weight: bold;');
            console.log('%cEnter this code to complete login', 'color: #fbbf24; font-size: 12px;');
        <?php endif; ?>
    </script>
</body>
</html>