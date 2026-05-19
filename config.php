<?php
// =============================================
// DATABASE CONFIGURATION
// =============================================
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'libtech_db';

$conn = new mysqli($host, $user, $password, $dbname);

if($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// =============================================
// SESSION CONFIGURATION
// =============================================
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0);
    session_start();
}

// Session timeout after 30 minutes
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset();
    session_destroy();
    if(basename($_SERVER['PHP_SELF']) != 'index.php') {
        header('Location: index.php?timeout=1');
        exit();
    }
}
$_SESSION['LAST_ACTIVITY'] = time();

// CSRF Token for forms
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>