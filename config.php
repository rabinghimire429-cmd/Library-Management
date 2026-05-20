<?php
// =============================================
// SECURE CONFIGURATION - Using Environment Variables
// =============================================

// =============================================
// LOAD ENVIRONMENT VARIABLES FROM .env FILE
// =============================================
function loadEnv($path = __DIR__) {
    $envFile = $path . '/.env';
    if (!file_exists($envFile)) {
        return; // .env file doesn't exist - use defaults
    }
    
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse key=value
        $parts = explode('=', $line, 2);
        if (count($parts) == 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// Load environment variables
loadEnv();

// =============================================
// DATABASE CONNECTION - Using Environment Variables
// =============================================
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$dbname = getenv('DB_NAME') ?: 'libtech_db';

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

// Session timeout (from environment variable or default 30 minutes)
$session_timeout = (int)(getenv('SESSION_TIMEOUT') ?: 1800);
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $session_timeout)) {
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

// =============================================
// SECURITY HEADERS - ONLY FOR NON-AJAX PAGES
// =============================================
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strpos($_SERVER['SCRIPT_NAME'], 'login-ajax.php') === false) {
    header("X-Frame-Options: DENY");
    header("X-XSS-Protection: 1; mode=block");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: strict-origin-when-cross-origin");
}

// =============================================
// NOTIFICATION HELPER FUNCTIONS
// =============================================

function getUnreadNotificationCount($conn, $member_id) {
    if(!$member_id) return 0;
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notification WHERE member_id = ? AND (read_status = 1 OR read_status IS NULL)");
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    return $data['count'] ?? 0;
}

function getMemberNotifications($conn, $member_id, $type = 'all', $status = 'all') {
    if(!$member_id) return [];
    
    $query = "SELECT * FROM notification WHERE member_id = ?";
    $params = [$member_id];
    $types = "i";
    
    if($type != 'all') {
        $query .= " AND notification_type = ?";
        $params[] = strtoupper($type);
        $types .= "s";
    }
    
    if($status != 'all') {
        $status_value = ($status == 'unread') ? 1 : 0;
        $query .= " AND (read_status = ? OR read_status IS NULL)";
        $params[] = $status_value;
        $types .= "i";
    }
    
    $query .= " ORDER BY sent_date DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    return $notifications;
}

function markNotificationAsRead($conn, $notification_id) {
    $stmt = $conn->prepare("UPDATE notification SET read_status = 0 WHERE notification_id = ?");
    $stmt->bind_param("i", $notification_id);
    return $stmt->execute();
}

function deleteNotification($conn, $notification_id) {
    $stmt = $conn->prepare("DELETE FROM notification WHERE notification_id = ?");
    $stmt->bind_param("i", $notification_id);
    return $stmt->execute();
}

// Include helper functions
if(file_exists(__DIR__ . '/includes/validation.php')) {
    require_once __DIR__ . '/includes/validation.php';
}
if(file_exists(__DIR__ . '/includes/audit-log.php')) {
    require_once __DIR__ . '/includes/audit-log.php';
}
?>