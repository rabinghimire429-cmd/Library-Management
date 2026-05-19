<?php
// =============================================
// SECURE CONFIGURATION
// =============================================

// Security Headers - Add at top of every page
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Force HTTPS in production (uncomment when using HTTPS)
// if($_SERVER['HTTP_HOST'] != 'localhost' && empty($_SERVER['HTTPS'])) {
//     header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
//     exit();
// }

// =============================================
// DATABASE CONNECTION
// =============================================
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'libtech_db';

$conn = new mysqli($host, $user, $password, $dbname);

if($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("System temporarily unavailable. Please try again later.");
}

// =============================================
// SESSION CONFIGURATION
// =============================================
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

// Session timeout (30 minutes)
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset();
    session_destroy();
    if(basename($_SERVER['PHP_SELF']) != 'index.php') {
        header('Location: index.php?timeout=1');
        exit();
    }
}
$_SESSION['LAST_ACTIVITY'] = time();

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include helper functions
if(file_exists(__DIR__ . '/includes/validation.php')) {
    require_once __DIR__ . '/includes/validation.php';
}
if(file_exists(__DIR__ . '/includes/audit-log.php')) {
    require_once __DIR__ . '/includes/audit-log.php';
}
?>