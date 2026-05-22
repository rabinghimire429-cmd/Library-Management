<?php
/**
 * NOTIFICATION MODULE - LIBRARIAN MANAGEMENT PAGE
 * 
 * FUNCTIONS: LIST, FILTER, SEARCH, DELETE, MARK AS READ, TEMPLATES
 */

session_start();
if(!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] != 'Librarian') {
    header('Location: ../index.php');
    exit();
}

require_once '../config.php';
require_once 'NotificationClass.php';

$notificationManager = new NotificationManager($conn);
$message = '';

// DELETE
if(isset($_GET['delete'])) {
    if($notificationManager->deleteNotification($_GET['delete'])) {
        $message = "✅ Notification deleted successfully";
    }
}

// MARK AS READ
if(isset($_GET['mark_read'])) {
    $notificationManager->markAsRead($_GET['mark_read']);
    $message = "✅ Notification marked as read";
}

// Get filters
$type_filter = $_GET['type'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';
$member_filter = $_GET['member'] ?? 'all';
$search_query = $_GET['search'] ?? '';

// Get data
$notifications = $notificationManager->getAllNotifications($type_filter, $status_filter, $member_filter, $search_query);
$members = $notificationManager->getAllMembers();
$templates = $notificationManager->getTemplates();
$stats = $notificationManager->getStatistics();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Notifications - LibTech</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #0f0c29, #302b63, #24243e); min-height: 100vh; color: #e4e6eb; }
        .navbar { background: rgba(15,23,42,0.95); padding: 16px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .logo-text { font-size: 22px; font-weight: 800; background: linear-gradient(135deg, #fff, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .container { max-width: 1600px; margin: 30px auto; padding: 0 30px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: rgba(255,255,255,0.05); border-radius: 20px; padding: 20px; text-align: center; }
        .stat-number { font-size: 32px; font-weight: 800; background: linear-gradient(135deg, #818cf8, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .templates-section { background: rgba(99,102,241,0.1); border-radius: 20px; padding: 25px; margin-bottom: 30px; }
        .templates-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
        .template-card { background: rgba(255,255,255,0.05); border-radius: 16px; padding: 20px; border-left: 4px solid; cursor: pointer; transition: 0.3s; }
        .template-card:hover { transform: translateY(-3px); background: rgba(255,255,255,0.08); }
        .template-card.borrow { border-left-color: #3b82f6; }
        .template-card.reminder { border-left-color: #10b981; }
        .template-card.overdue { border-left-color: #ef4444; }
        .template-card.fine { border-left-color: #f59e0b; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; }
        h1 { font-size: 28px; }
        .badge { background: #ef4444; border-radius: 50%; padding: 4px 12px; font-size: 12px; margin-left: 10px; }
        .search-bar { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-input { flex: 1; padding: 12px 20px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 30px; color: white; }
        .search-btn { padding: 12px 25px; background: #6366f1; border: none; border-radius: 30px; color: white; cursor: pointer; }
        .filter-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-btn, select.filter-btn { padding: 8px 20px; background: rgba(255,255,255,0.1); border-radius: 25px; text-decoration: none; color: white; border: none; cursor: pointer; }
        .filter-btn.active { background: #6366f1; }
        .table-container { overflow-x: auto; background: rgba(255,255,255,0.05); border-radius: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); }
        th { background: rgba(0,0,0,0.3); }
        .type-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; }
        .type-BORROW { background: #dbeafe; color: #1e40af; }
        .type-REMINDER { background: #dcfce7; color: #166534; }
        .type-OVERDUE { background: #fee2e2; color: #b91c1c; }
        .type-FINE { background: #fef3c7; color: #92400e; }
        .status-unread { color: #34d399; font-weight: bold; }
        .actions a { margin-right: 12px; text-decoration: none; font-size: 13px; }
        .edit-btn { color: #818cf8; }
        .delete-btn { color: #f87171; }
        .btn-add { background: #10b981; padding: 10px 20px; border-radius: 12px; color: white; text-decoration: none; }
        .success-msg { background: rgba(16,185,129,0.2); color: #34d399; padding: 12px; border-radius: 12px; margin-bottom: 20px; text-align: center; }
        .back-link { color: #818cf8; text-decoration: none; }
    </style>
</head>
<body>
    <div class="navbar">
        <span class="logo-text">📚 LibTech - Notification Manager</span>
        <a href="../librarian-dashboard.php" class="back-link">← Dashboard</a>
    </div>
    
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-bell"></i> All Notifications <span class="badge"><?php echo count($notifications); ?> total</span></h1>
            <a href="send-notification.php" class="btn-add"><i class="fas fa-plus"></i> Send New</a>
        </div>
        
        <?php if($message): ?>
            <div class="success-msg"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-number"><?php echo $stats['total']; ?></div><div>Total</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $stats['unread']; ?></div><div>Unread</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $stats['by_type']['BORROW'] ?? 0; ?></div><div>Borrow</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $stats['by_type']['REMINDER'] ?? 0; ?></div><div>Reminder</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $stats['by_type']['OVERDUE'] ?? 0; ?></div><div>Overdue</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $stats['by_type']['FINE'] ?? 0; ?></div><div>Fine</div></div>
        </div>
        
        <!-- Templates -->
        <div class="templates-section">
            <h3 style="margin-bottom: 15px;">📋 Quick Templates (Click to use)</h3>
            <div class="templates-grid">
                <?php foreach($templates as $type => $template): ?>
                    <div class="template-card <?php echo strtolower($type); ?>" onclick="window.location.href='send-notification.php?template=<?php echo $type; ?>'">
                        <strong><?php echo $template['title']; ?></strong>
                        <div style="font-size: 11px; color: #9ca3af; margin-top: 8px;"><?php echo substr($template['template'], 0, 80); ?>...</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Search -->
        <div class="search-bar">
            <form method="GET" style="display: flex; gap: 10px; width: 100%;">
                <input type="text" name="search" class="search-input" placeholder="🔍 Search by message, member, or type..." value="<?php echo htmlspecialchars($search_query); ?>">
                <input type="hidden" name="type" value="<?php echo $type_filter; ?>">
                <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                <input type="hidden" name="member" value="<?php echo $member_filter; ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
                <?php if($search_query): ?>
                    <a href="?type=<?php echo $type_filter; ?>&status=<?php echo $status_filter; ?>&member=<?php echo $member_filter; ?>" class="filter-btn">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Filters -->
        <div class="filter-bar">
            <a href="?type=all&status=<?php echo $status_filter; ?>&member=<?php echo $member_filter; ?>&search=<?php echo urlencode($search_query); ?>" class="filter-btn <?php echo $type_filter=='all'?'active':''; ?>">All</a>
            <a href="?type=BORROW&status=<?php echo $status_filter; ?>&member=<?php echo $member_filter; ?>&search=<?php echo urlencode($search_query); ?>" class="filter-btn <?php echo $type_filter=='BORROW'?'active':''; ?>">📚 Borrow</a>
            <a href="?type=REMINDER&status=<?php echo $status_filter; ?>&member=<?php echo $member_filter; ?>&search=<?php echo urlencode($search_query); ?>" class="filter-btn <?php echo $type_filter=='REMINDER'?'active':''; ?>">⏰ Reminder</a>
            <a href="?type=OVERDUE&status=<?php echo $status_filter; ?>&member=<?php echo $member_filter; ?>&search=<?php echo urlencode($search_query); ?>" class="filter-btn <?php echo $type_filter=='OVERDUE'?'active':''; ?>">⚠️ Overdue</a>
            <a href="?type=FINE&status=<?php echo $status_filter; ?>&member=<?php echo $member_filter; ?>&search=<?php echo urlencode($search_query); ?>" class="filter-btn <?php echo $type_filter=='FINE'?'active':''; ?>">💰 Fine</a>
        </div>
        
        <div class="filter-bar">
            <a href="?type=<?php echo $type_filter; ?>&status=all&member=<?php echo $member_filter; ?>&search=<?php echo urlencode($search_query); ?>" class="filter-btn <?php echo $status_filter=='all'?'active':''; ?>">All Status</a>
            <a href="?type=<?php echo $type_filter; ?>&status=unread&member=<?php echo $member_filter; ?>&search=<?php echo urlencode($search_query); ?>" class="filter-btn <?php echo $status_filter=='unread'?'active':''; ?>">🔴 Unread</a>
            <a href="?type=<?php echo $type_filter; ?>&status=read&member=<?php echo $member_filter; ?>&search=<?php echo urlencode($search_query); ?>" class="filter-btn <?php echo $status_filter=='read'?'active':''; ?>">✅ Read</a>
        </div>
        
        <div class="filter-bar">
            <select class="filter-btn" onchange="window.location.href='?type=<?php echo $type_filter; ?>&status=<?php echo $status_filter; ?>&member='+this.value+'&search=<?php echo urlencode($search_query); ?>'">
                <option value="all">All Members</option>
                <?php foreach($members as $m): ?>
                    <option value="<?php echo $m['member_id']; ?>" <?php echo $member_filter==$m['member_id']?'selected':''; ?>><?php echo htmlspecialchars($m['full_name'] ?: $m['email']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr><th>ID</th><th>Member</th><th>Type</th><th>Message</th><th>Date</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php if(count($notifications) > 0): ?>
                        <?php foreach($notifications as $n): ?>
                            <tr>
                                <td><?php echo $n['notification_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($n['member_name']); ?></strong><br><small><?php echo $n['member_email']; ?></small></td>
                                <td><span class="type-badge type-<?php echo $n['notification_type']; ?>"><?php echo $n['notification_type']; ?></span></td>
                                <td style="max-width: 350px;"><?php echo htmlspecialchars(substr($n['message'], 0, 80)); ?>...</td>
                                <td><?php echo date('M j, Y', strtotime($n['sent_date'])); ?></td>
                                <td><?php echo $n['read_status'] ? '<span class="status-unread">🔴 Unread</span>' : '✅ Read'; ?></td>
                                <td class="actions">
                                    <?php if($n['read_status']): ?>
                                        <a href="?mark_read=<?php echo $n['notification_id']; ?>" class="edit-btn">✓ Read</a>
                                    <?php endif; ?>
                                    <a href="edit-notification.php?id=<?php echo $n['notification_id']; ?>" class="edit-btn">✏️ Edit</a>
                                    <a href="?delete=<?php echo $n['notification_id']; ?>" class="delete-btn" onclick="return confirm('Delete?')">🗑 Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align: center; padding: 40px;">No notifications found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>