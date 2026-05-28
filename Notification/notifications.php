<?php
/**
 * MEMBER NOTIFICATION VIEW
 * Members see only their own notifications
 */

session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../config.php';
require_once 'NotificationClass.php';

$notificationManager = new NotificationManager($conn);
$admin_role = $_SESSION['admin_role'];
$admin_id = $_SESSION['admin_id'];

// If librarian, redirect to manage page
if($admin_role == 'Librarian') {
    header('Location: manage.php');
    exit();
}

// Get member_id
$member_id = $_SESSION['member_id'] ?? 0;
if(!$member_id) {
    $member_query = $conn->query("SELECT member_id FROM member WHERE admin_id = $admin_id");
    if($member_query && $member_query->num_rows > 0) {
        $member_id = $member_query->fetch_assoc()['member_id'];
        $_SESSION['member_id'] = $member_id;
    }
}

// Mark as read
if(isset($_GET['mark_read'])) {
    $notificationManager->markAsRead($_GET['mark_read']);
    header("Location: notifications.php");
    exit();
}

// Get filters
$type_filter = $_GET['type'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';

// Get member's notifications
$notifications = $notificationManager->getMemberNotifications($member_id, $type_filter, $status_filter);
$unread_count = $notificationManager->getUnreadCount($member_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Notifications</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #0f0c29, #302b63, #24243e); min-height: 100vh; color: #e4e6eb; }
        .navbar { background: rgba(15,23,42,0.95); padding: 16px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        h1 { font-size: 28px; margin-bottom: 30px; }
        .badge { background: #ef4444; border-radius: 50%; padding: 4px 12px; font-size: 12px; margin-left: 10px; }
        .filter-bar { display: flex; gap: 10px; margin-bottom: 30px; flex-wrap: wrap; }
        .filter-btn { padding: 8px 20px; background: rgba(255,255,255,0.1); border-radius: 25px; text-decoration: none; color: white; }
        .filter-btn.active { background: #6366f1; }
        .notification { background: rgba(255,255,255,0.05); border-radius: 16px; padding: 20px; margin-bottom: 15px; border-left: 4px solid; }
        .notification.unread { border-left-color: #6366f1; background: rgba(99,102,241,0.1); }
        .type-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; margin-bottom: 10px; }
        .type-BORROW { background: #dbeafe; color: #1e40af; }
        .type-REMINDER { background: #dcfce7; color: #166534; }
        .type-OVERDUE { background: #fee2e2; color: #b91c1c; }
        .type-FINE { background: #fef3c7; color: #92400e; }
        .message { margin: 10px 0; line-height: 1.5; }
        .date { font-size: 11px; color: #9ca3af; margin-top: 10px; }
        .actions a { color: #818cf8; text-decoration: none; font-size: 13px; }
        .empty-state { text-align: center; padding: 60px; }
        .back-link { color: #818cf8; text-decoration: none; }
    </style>
</head>
<body>
    <div class="navbar">
        <span class="logo-text">🔔 My Notifications</span>
        <a href="../member-dashboard.php" class="back-link">← Back</a>
    </div>
    <div class="container">
        <h1>Notifications <span class="badge"><?php echo $unread_count; ?> unread</span></h1>
        
        <div class="filter-bar">
            <a href="?type=all&status=<?php echo $status_filter; ?>" class="filter-btn <?php echo $type_filter=='all'?'active':''; ?>">All</a>
            <a href="?type=BORROW&status=<?php echo $status_filter; ?>" class="filter-btn <?php echo $type_filter=='BORROW'?'active':''; ?>">Borrow</a>
            <a href="?type=REMINDER&status=<?php echo $status_filter; ?>" class="filter-btn <?php echo $type_filter=='REMINDER'?'active':''; ?>">Reminder</a>
            <a href="?type=OVERDUE&status=<?php echo $status_filter; ?>" class="filter-btn <?php echo $type_filter=='OVERDUE'?'active':''; ?>">Overdue</a>
            <a href="?type=FINE&status=<?php echo $status_filter; ?>" class="filter-btn <?php echo $type_filter=='FINE'?'active':''; ?>">Fine</a>
        </div>
        
        <div class="filter-bar">
            <a href="?type=<?php echo $type_filter; ?>&status=all" class="filter-btn <?php echo $status_filter=='all'?'active':''; ?>">All</a>
            <a href="?type=<?php echo $type_filter; ?>&status=unread" class="filter-btn <?php echo $status_filter=='unread'?'active':''; ?>">Unread</a>
            <a href="?type=<?php echo $type_filter; ?>&status=read" class="filter-btn <?php echo $status_filter=='read'?'active':''; ?>">Read</a>
        </div>
        
        <?php if(count($notifications) > 0): ?>
            <?php foreach($notifications as $n): ?>
                <div class="notification <?php echo $n['read_status'] ? 'unread' : 'read'; ?>">
                    <span class="type-badge type-<?php echo $n['notification_type']; ?>"><?php echo $n['notification_type']; ?></span>
                    <div class="message"><?php echo nl2br(htmlspecialchars($n['message'])); ?></div>
                    <div class="date">📅 <?php echo date('M j, Y g:i A', strtotime($n['sent_date'])); ?></div>
                    <div class="actions">
                        <?php if($n['read_status']): ?>
                            <a href="?mark_read=<?php echo $n['notification_id']; ?>">✓ Mark as Read</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="far fa-bell-slash" style="font-size: 48px; margin-bottom: 15px;"></i>
                <p>No notifications found</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>