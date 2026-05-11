<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0);
    session_start();
}

// Session timeout after 30 minutes (1800 seconds)
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: index.php?timeout=1');
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'libtech_db';

$conn = new mysqli($host, $user, $password, $dbname);

if($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?><?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0);
    session_start();
}

// Session timeout
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: index.php?timeout=1');
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// Database configuration
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'libtech_db';

// Create connection with error reporting
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if($conn->connect_error) {
    // Log error but don't show details to user
    error_log("Connection failed: " . $conn->connect_error);
    
    // Show user-friendly message
    die("<!DOCTYPE html>
    <html>
    <head><title>Database Connection Error</title>
    <style>
        body { font-family: Arial; background: #0a0a2a; color: white; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .error-box { background: #1c1e26; padding: 30px; border-radius: 20px; text-align: center; border: 1px solid #ef4444; }
        .error-box h2 { color: #f87171; margin-bottom: 10px; }
        .error-box p { color: #b9bbbe; margin-bottom: 20px; }
        .error-box a { color: #818cf8; text-decoration: none; }
    </style>
    </head>
    <body>
        <div class='error-box'>
            <h2>⚠️ Database Connection Error</h2>
            <p>Cannot connect to the database. Please make sure XAMPP is running with MySQL started.</p>
            <a href='index.php'>← Try Again</a>
        </div>
    </body>
    </html>");
}
?>