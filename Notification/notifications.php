<?php
// Notification/notifications.php
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

// Get member_id
$member_id = $_SESSION['member_id'] ?? 0;
if(!$member_id) {
    $member_query = $conn->query("SELECT member_id FROM member WHERE admin_id = $admin_id");
    if($member_query && $member_query->num_rows > 0) {
        $member_id = $member_query->fetch_assoc()['member_id'];
        $_SESSION['member_id'] = $member_id;
    }
}

// Handle actions
$message = '';
if(isset($_GET['mark_read'])) {
    if($notificationManager->markAsRead($_GET['mark_read'])) {
        $message = "Notification marked as read";
    }
}

if(isset($_GET['delete']) && $admin_role == 'Librarian') {
    if($notificationManager->deleteNotification($_GET['delete'])) {
        header("Location: notifications.php");
        exit();
    }
}

// Get filters
$type_filter = $_GET['type'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';

// Get notifications
$notifications = $notificationManager->listNotifications($member_id, $type_filter, $status_filter);
$unread_count = $notificationManager->getUnreadCount($member_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications - LibTech</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #0f0c29, #302b63, #24243e); min-height: 100vh; color: #e4e6eb; }
        .navbar { background: rgba(15,23,42,0.95); padding: 16px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .logo-text { font-size: 22px; font-weight: 800; background: linear-gradient(135deg, #fff, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .container { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; }
        h1 { font-size: 28px; }
        .badge { background: #ef4444; border-radius: 50%; padding: 4px 10px; font-size: 12px; margin-left: 10px; }
        .filter-bar { display: flex; gap: 10px; margin-bottom: 30px; flex-wrap: wrap; }
        .filter-btn { padding: 8px 20px; background: rgba(255,255,255,0.1); border-radius: 25px; text-decoration: none; color: white; transition: 0.3s; }
        .filter-btn:hover { background: rgba(99,102,241,0.5); }
        .filter-btn.active { background: #6366f1; }
        .notification { background: rgba(255,255,255,0.05); border-radius: 16px; padding: 20px; margin-bottom: 15px; border-left: 4px solid; transition: 0.3s; }
        .notification:hover { transform: translateX(5px); }
        .notification.unread { border-left-color: #6366f1; background: rgba(99,102,241,0.1); }
        .type-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; margin-bottom: 10px; }
        .type-BORROW { background: #dbeafe; color: #1e40af; }
        .type-REMINDER { background: #dcfce7; color: #166534; }
        .type-OVERDUE { background: #fee2e2; color: #b91c1c; }
        .type-FINE { background: #fef3c7; color: #92400e; }
        .message { margin: 10px 0; line-height: 1.5; }
        .date { font-size: 11px; color: #9ca3af; margin-top: 10px; }
        .actions { margin-top: 15px; display: flex; gap: 15px; }
        .actions a { color: #818cf8; text-decoration: none; font-size: 13px; }
        .delete-btn { color: #f87171 !important; }
        .empty-state { text-align: center; padding: 60px; background: rgba(255,255,255,0.05); border-radius: 20px; }
        .btn-send { background: #10b981; padding: 10px 20px; border-radius: 12px; color: white; text-decoration: none; }
        .success-msg { background: rgba(16,185,129,0.2); color: #34d399; padding: 10px; border-radius: 12px; margin-bottom: 20px; text-align: center; }
    </style>
</head>
<body>
    <div class="navbar">
        <span class="logo-text">🔔 Notifications</span>
        <a href="<?php echo $admin_role == 'Librarian' ? '../librarian-dashboard.php' : '../member-dashboard.php'; ?>" style="color: #818cf8;">← Back</a>
    </div>
    <div class="container">
        <div class="header">
            <h1>Notifications <span class="badge"><?php echo $unread_count; ?> new</span></h1>
            <?php if($admin_role == 'Librarian'): ?>
                <a href="send-notification.php" class="btn-send"><i class="fas fa-plus"></i> Send Notification</a>
            <?php endif; ?>
        </div>
        
        <?php if($message): ?>
            <div class="success-msg">✅ <?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="filter-bar">
            <a href="?type=all&status=<?php echo $status_filter; ?>" class="filter-btn <?php echo $type_filter=='all'?'active':''; ?>">All</a>
            <a href="?type=BORROW&status=<?php echo $status_filter; ?>" class="filter-btn <?php echo $type_filter=='BORROW'?'active':''; ?>">📚 Borrow</a>
            <a href="?type=REMINDER&status=<?php echo $status_filter; ?>" class="filter-btn <?php echo $type_filter=='REMINDER'?'active':''; ?>">⏰ Reminder</a>
            <a href="?type=OVERDUE&status=<?php echo $status_filter; ?>" class="filter-btn <?php echo $type_filter=='OVERDUE'?'active':''; ?>">⚠️ Overdue</a>
            <a href="?type=FINE&status=<?php echo $status_filter; ?>" class="filter-btn <?php echo $type_filter=='FINE'?'active':''; ?>">💰 Fine</a>
        </div>
        
        <div class="filter-bar">
            <a href="?type=<?php echo $type_filter; ?>&status=all" class="filter-btn <?php echo $status_filter=='all'?'active':''; ?>">All Status</a>
            <a href="?type=<?php echo $type_filter; ?>&status=unread" class="filter-btn <?php echo $status_filter=='unread'?'active':''; ?>">Unread</a>
            <a href="?type=<?php echo $type_filter; ?>&status=read" class="filter-btn <?php echo $status_filter=='read'?'active':''; ?>">Read</a>
        </div>
        
        <?php if(count($notifications) > 0): ?>
            <?php foreach($notifications as $n): ?>
                <div class="notification <?php echo $n['read_status'] ? 'unread' : 'read'; ?>">
                    <span class="type-badge type-<?php echo $n['notification_type']; ?>"><?php echo $n['notification_type']; ?></span>
                    <div class="message"><?php echo htmlspecialchars($n['message']); ?></div>
                    <div class="date">📅 <?php echo date('M j, Y g:i A', strtotime($n['sent_date'])); ?></div>
                    <div class="actions">
                        <?php if($n['read_status']): ?>
                            <a href="?mark_read=<?php echo $n['notification_id']; ?>&type=<?php echo $type_filter; ?>&status=<?php echo $status_filter; ?>">
                                ✓ Mark as Read
                            </a>
                        <?php endif; ?>
                        <?php if($admin_role == 'Librarian'): ?>
                            <a href="edit-notification.php?id=<?php echo $n['notification_id']; ?>">✏️ Edit</a>
                            <a href="?delete=<?php echo $n['notification_id']; ?>" class="delete-btn" onclick="return confirm('Delete?')">🗑 Delete</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="far fa-bell-slash" style="font-size: 48px; margin-bottom: 15px;"></i>
                <p>No notifications found</p>
                <small>When you borrow books or have due dates, notifications will appear here.</small>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>