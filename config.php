<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0);
    session_start();
}

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
?>