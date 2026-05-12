<?php
// Notification/notifications.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if(!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../config.php';

$admin_id = $_SESSION['admin_id'];
$admin_role = $_SESSION['admin_role'];
$member_id = null;

// Check what status column exists in notification table
$status_column = 'status'; // default
$columns_check = $conn->query("SHOW COLUMNS FROM notification");
if($columns_check) {
    while($col = $columns_check->fetch_assoc()) {
        if($col['Field'] == 'read_status') {
            $status_column = 'read_status';
            break;
        }
        if($col['Field'] == 'status') {
            $status_column = 'status';
            break;
        }
    }
}

// Get member_id based on role
if($admin_role == 'Librarian') {
    // Get first member under this librarian
    $member_query = $conn->query("SELECT member_id FROM member WHERE admin_id = $admin_id LIMIT 1");
    if($member_query && $member_query->num_rows > 0) {
        $member_id = $member_query->fetch_assoc()['member_id'];
    }
    
    // If no member found, get any member to show notifications
    if(!$member_id) {
        $any_member = $conn->query("SELECT member_id FROM member LIMIT 1");
        if($any_member && $any_member->num_rows > 0) {
            $member_id = $any_member->fetch_assoc()['member_id'];
        }
    }
} else {
    // Member role - get their member_id
    $member_query = $conn->query("SELECT member_id FROM member WHERE admin_id = $admin_id");
    if($member_query && $member_query->num_rows > 0) {
        $member_id = $member_query->fetch_assoc()['member_id'];
    }
}

// Handle mark as read
if(isset($_GET['mark_read'])) {
    $notif_id = (int)$_GET['mark_read'];
    $conn->query("UPDATE notification SET $status_column = 0 WHERE notification_id = $notif_id");
    header("Location: notifications.php?type=" . ($_GET['type'] ?? 'all') . "&status=" . ($_GET['status'] ?? 'all'));
    exit();
}

// Handle mark all as read
if(isset($_GET['mark_all_read']) && $member_id) {
    $conn->query("UPDATE notification SET $status_column = 0 WHERE member_id = $member_id AND $status_column = 1");
    header("Location: notifications.php?type=" . ($_GET['type'] ?? 'all'));
    exit();
}

// Handle delete (admin only)
if($admin_role == 'Librarian' && isset($_GET['delete'])) {
    $notif_id = (int)$_GET['delete'];
    $conn->query("DELETE FROM notification WHERE notification_id = $notif_id");
    header("Location: notifications.php?type=" . ($_GET['type'] ?? 'all') . "&status=" . ($_GET['status'] ?? 'all'));
    exit();
}

// Get filters
$type_filter = $_GET['type'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';

// Build query
$query = "SELECT *, $status_column as current_status FROM notification WHERE 1=1";
if($member_id) {
    $query .= " AND member_id = $member_id";
}
if($type_filter != 'all') {
    $query .= " AND notification_type = '" . strtoupper($type_filter) . "'";
}
if($status_filter != 'all') {
    $status_value = ($status_filter == 'unread') ? '1' : '0';
    $query .= " AND $status_column = $status_value";
}
$query .= " ORDER BY sent_date DESC";

$notifications = $conn->query($query);

// Get unread count
$unread_query = "SELECT COUNT(*) as count FROM notification WHERE 1=1";
if($member_id) $unread_query .= " AND member_id = $member_id";
$unread_query .= " AND $status_column = 1";
$unread_result = $conn->query($unread_query);
$unread_count = $unread_result ? $unread_result->fetch_assoc()['count'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - LibTech Solutions</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #0f0c29, #302b63, #24243e); min-height: 100vh; color: #e4e6eb; }
        
        .navbar { background: rgba(15,23,42,0.95); backdrop-filter: blur(12px); padding: 16px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1); position: sticky; top: 0; z-index: 100; }
        .logo { display: flex; align-items: center; gap: 15px; }
        .logo-img { height: 45px; width: auto; border-radius: 10px; }
        .logo-text { font-size: 22px; font-weight: 800; background: linear-gradient(135deg, #fff, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        .container { max-width: 1000px; margin: 40px auto; padding: 0 40px; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px; }
        .header h1 { font-size: 32px; background: linear-gradient(135deg, #fff, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .unread-badge { background: #ef4444; border-radius: 50%; padding: 4px 10px; font-size: 12px; margin-left: 10px; vertical-align: middle; }
        
        .filter-bar { display: flex; gap: 10px; margin-bottom: 30px; flex-wrap: wrap; }
        .filter-btn, .status-btn { padding: 8px 20px; background: rgba(255,255,255,0.1); border-radius: 25px; text-decoration: none; color: #e4e6eb; transition: all 0.3s; font-size: 14px; display: inline-block; }
        .filter-btn:hover, .status-btn:hover { background: rgba(99,102,241,0.5); transform: translateY(-2px); }
        .filter-btn.active, .status-btn.active { background: #6366f1; color: white; }
        
        .notification { background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 20px; margin-bottom: 15px; transition: all 0.3s; border-left: 4px solid; }
        .notification:hover { transform: translateX(5px); background: rgba(255,255,255,0.08); }
        .notification.unread { border-left-color: #6366f1; background: rgba(99,102,241,0.1); }
        .notification.read { border-left-color: #4b5563; opacity: 0.7; }
        
        .type-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; margin-bottom: 12px; }
        .type-BORROW { background: #dbeafe; color: #1e40af; }
        .type-REMINDER { background: #dcfce7; color: #166534; }
        .type-OVERDUE { background: #fee2e2; color: #b91c1c; }
        .type-FINE { background: #fef3c7; color: #92400e; }
        
        .message { font-size: 16px; margin: 10px 0; line-height: 1.5; }
        .date { font-size: 12px; color: #9ca3af; margin-top: 10px; }
        
        .actions { margin-top: 15px; display: flex; gap: 15px; flex-wrap: wrap; }
        .actions a { color: #818cf8; text-decoration: none; font-size: 13px; transition: all 0.3s; }
        .actions a:hover { text-decoration: underline; color: #a78bfa; }
        .delete-btn { color: #f87171 !important; }
        .delete-btn:hover { color: #ef4444 !important; }
        
        .empty-state { text-align: center; padding: 60px 20px; background: rgba(255,255,255,0.05); border-radius: 30px; }
        .empty-state i { font-size: 64px; color: #4b5563; margin-bottom: 20px; }
        .empty-state p { color: #9ca3af; margin-bottom: 10px; }
        
        .send-notif-btn { background: linear-gradient(135deg, #10b981, #059669); padding: 12px 24px; border-radius: 12px; color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; }
        .send-notif-btn:hover { transform: translateY(-2px); background: linear-gradient(135deg, #34d399, #10b981); }
        
        .mark-all-btn { background: rgba(99,102,241,0.2); padding: 8px 16px; border-radius: 12px; color: #818cf8; text-decoration: none; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; border: 1px solid rgba(99,102,241,0.3); transition: all 0.3s; }
        .mark-all-btn:hover { background: rgba(99,102,241,0.4); }
        
        @media (max-width: 768px) { 
            .navbar { padding: 15px 20px; } 
            .container { padding: 0 20px; } 
            .header { flex-direction: column; align-items: flex-start; }
            .notification:hover { transform: translateX(0); }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="logo">
            <img src="../logo.jpg" alt="Logo" class="logo-img" onerror="this.style.display='none'">
            <span class="logo-text">LibTech Solutions</span>
        </div>
        <div>
            <a href="<?php echo $admin_role == 'Librarian' ? '../librarian-dashboard.php' : '../member-dashboard.php'; ?>" style="color: #818cf8; text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="container">
        <div class="header">
            <div>
                <h1>
                    <i class="fas fa-bell"></i> Notifications 
                    <?php if($unread_count > 0): ?>
                        <span class="unread-badge"><?php echo $unread_count; ?> new</span>
                    <?php endif; ?>
                </h1>
            </div>
            <div style="display: flex; gap: 12px;">
                <?php if($unread_count > 0 && $member_id): ?>
                    <a href="?mark_all_read=1&type=<?php echo $type_filter; ?>" class="mark-all-btn" onclick="return confirm('Mark all notifications as read?')">
                        <i class="fas fa-check-double"></i> Mark All Read
                    </a>
                <?php endif; ?>
                <?php if($admin_role == 'Librarian'): ?>
                    <a href="send-notification.php" class="send-notif-btn">
                        <i class="fas fa-plus"></i> Send Notification
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Filter Bar -->
        <div class="filter-bar">
            <a href="?type=all&status=<?php echo $status_filter; ?>" class="filter-btn <?php echo $type_filter=='all'?'active':''; ?>">
                <i class="fas fa-list"></i> All
            </a>
            <a href="?type=BORROW&status=<?php echo $status_filter; ?>" class="filter-btn <?php echo $type_filter=='BORROW'?'active':''; ?>">
                <i class="fas fa-book"></i> Borrow
            </a>
            <a href="?type=REMINDER&status=<?php echo $status_filter; ?>" class="filter-btn <?php echo $type_filter=='REMINDER'?'active':''; ?>">
                <i class="fas fa-bell"></i> Reminder
            </a>
            <a href="?type=OVERDUE&status=<?php echo $status_filter; ?>" class="filter-btn <?php echo $type_filter=='OVERDUE'?'active':''; ?>">
                <i class="fas fa-exclamation-triangle"></i> Overdue
            </a>
            <a href="?type=FINE&status=<?php echo $status_filter; ?>" class="filter-btn <?php echo $type_filter=='FINE'?'active':''; ?>">
                <i class="fas fa-coins"></i> Fine
            </a>
        </div>
        
        <div class="filter-bar">
            <a href="?type=<?php echo $type_filter; ?>&status=all" class="status-btn <?php echo $status_filter=='all'?'active':''; ?>">
                <i class="fas fa-inbox"></i> All Status
            </a>
            <a href="?type=<?php echo $type_filter; ?>&status=unread" class="status-btn <?php echo $status_filter=='unread'?'active':''; ?>">
                <i class="fas fa-envelope"></i> Unread
                <?php if($unread_count > 0): ?>
                    <span style="background:#ef4444; border-radius:50%; padding:2px 6px; font-size:10px; margin-left:5px;"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="?type=<?php echo $type_filter; ?>&status=read" class="status-btn <?php echo $status_filter=='read'?'active':''; ?>">
                <i class="fas fa-envelope-open"></i> Read
            </a>
        </div>
        
        <?php if($notifications && $notifications->num_rows > 0): ?>
            <?php while($n = $notifications->fetch_assoc()): ?>
                <div class="notification <?php echo $n['current_status'] ? 'unread' : 'read'; ?>">
                    <span class="type-badge type-<?php echo $n['notification_type']; ?>">
                        <?php 
                        $type_icon = [
                            'BORROW' => '📚',
                            'REMINDER' => '⏰',
                            'OVERDUE' => '⚠️',
                            'FINE' => '💰'
                        ];
                        echo $type_icon[$n['notification_type']] ?? '🔔';
                        ?> <?php echo ucfirst(strtolower($n['notification_type'])); ?>
                    </span>
                    <div class="message">
                        <?php echo nl2br(htmlspecialchars($n['message'])); ?>
                    </div>
                    <div class="date">
                        <i class="far fa-calendar-alt"></i> <?php echo date('F j, Y \a\t g:i A', strtotime($n['sent_date'])); ?>
                    </div>
                    <div class="actions">
                        <?php if($n['current_status']): ?>
                            <a href="?mark_read=<?php echo $n['notification_id']; ?>&type=<?php echo $type_filter; ?>&status=<?php echo $status_filter; ?>">
                                <i class="fas fa-check-circle"></i> Mark as Read
                            </a>
                        <?php endif; ?>
                        <?php if($admin_role == 'Librarian'): ?>
                            <a href="edit-notification.php?id=<?php echo $n['notification_id']; ?>">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="?delete=<?php echo $n['notification_id']; ?>&type=<?php echo $type_filter; ?>&status=<?php echo $status_filter; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this notification?')">
                                <i class="fas fa-trash-alt"></i> Delete
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="far fa-bell-slash"></i>
                <p><strong>No notifications found</strong></p>
                <small style="color: #6b7280;">
                    <?php if($admin_role == 'Librarian'): ?>
                        Click the "Send Notification" button to create notifications for members.
                    <?php else: ?>
                        When you borrow books or have due dates approaching, notifications will appear here.
                    <?php endif; ?>
                </small>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>