<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'libtech_db');
$admin_id = $_SESSION['admin_id'];
$member = $conn->query("SELECT member_id FROM member WHERE admin_id = $admin_id")->fetch_assoc();
$member_id = $member['member_id'] ?? 0;
$type_filter = $_GET['type'] ?? 'all';
$query = "SELECT * FROM notification WHERE member_id = $member_id";
if($type_filter != 'all') $query .= " AND notification_type = '$type_filter'";
$query .= " ORDER BY sent_date DESC";
$notifications = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications</title>
    <style>
        body { font-family: Arial; background: #0a0a2a; color: white; }
        .container { max-width: 800px; margin: 40px auto; padding: 20px; }
        a { color: #818cf8; }
        .filter-bar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-btn { padding: 8px 16px; background: rgba(255,255,255,0.2); border-radius: 20px; text-decoration: none; color: white; }
        .filter-btn.active { background: #6366f1; }
        .notification { background: rgba(255,255,255,0.1); border-radius: 16px; padding: 15px; margin-bottom: 15px; }
        .type { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 11px; }
        .type-borrow { background: #dbeafe; color: #1e40af; }
        .type-return { background: #dcfce7; color: #166534; }
        .type-overdue { background: #fee2e2; color: #b91c1c; }
        .type-fine { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>
    <div class="container">
        <a href="<?php echo $_SESSION['admin_role'] == 'Librarian' ? '../librarian-dashboard.php' : '../member-dashboard.php'; ?>">← Back</a>
        <h2>🔔 Notifications</h2>
        <?php if($_SESSION['admin_role'] == 'Librarian'): ?>
            <div style="margin-bottom:20px;"><a href="send-notification.php" style="background:#10b981; padding:10px 20px; border-radius:20px; text-decoration:none; color:white;">+ Send Notification</a></div>
        <?php endif; ?>
        <div class="filter-bar">
            <a href="?type=all" class="filter-btn <?php echo $type_filter=='all'?'active':''; ?>">All</a>
            <a href="?type=borrow" class="filter-btn <?php echo $type_filter=='borrow'?'active':''; ?>">Borrow</a>
            <a href="?type=return" class="filter-btn <?php echo $type_filter=='return'?'active':''; ?>">Return</a>
            <a href="?type=overdue" class="filter-btn <?php echo $type_filter=='overdue'?'active':''; ?>">Overdue</a>
            <a href="?type=fine" class="filter-btn <?php echo $type_filter=='fine'?'active':''; ?>">Fine</a>
        </div>
        <?php while($n = $notifications->fetch_assoc()): ?>
        <div class="notification">
            <span class="type type-<?php echo $n['notification_type']; ?>"><?php echo ucfirst($n['notification_type']); ?></span>
            <div><strong><?php echo htmlspecialchars($n['subject']); ?></strong></div>
            <div><?php echo htmlspecialchars($n['message']); ?></div>
            <div style="font-size:11px; margin-top:8px;">📅 <?php echo $n['sent_date']; ?></div>
        </div>
        <?php endwhile; ?>
    </div>
</body>
</html>