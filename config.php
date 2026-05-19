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

// =============================================
// NOTIFICATION HELPER FUNCTIONS
// =============================================

/**
 * Get unread notification count for a member (uses read_status column)
 * @param mysqli $conn Database connection
 * @param int $member_id Member ID
 * @return int Number of unread notifications
 */
function getUnreadNotificationCount($conn, $member_id) {
    if(!$member_id) return 0;
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notification WHERE member_id = ? AND read_status = 1");
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    return $data['count'] ?? 0;
}

/**
 * Get all notifications for a member
 * @param mysqli $conn Database connection
 * @param int $member_id Member ID
 * @param string $type Filter by notification type (BORROW, REMINDER, OVERDUE, FINE, or 'all')
 * @param string $status Filter by status (unread, read, or 'all')
 * @return array Array of notifications
 */
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
        $query .= " AND read_status = ?";
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

/**
 * Mark a notification as read
 * @param mysqli $conn Database connection
 * @param int $notification_id Notification ID
 * @return bool True on success
 */
function markNotificationAsRead($conn, $notification_id) {
    $stmt = $conn->prepare("UPDATE notification SET read_status = 0 WHERE notification_id = ?");
    $stmt->bind_param("i", $notification_id);
    return $stmt->execute();
}

/**
 * Delete a notification
 * @param mysqli $conn Database connection
 * @param int $notification_id Notification ID
 * @return bool True on success
 */
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