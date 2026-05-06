<?php
session_start();
if(!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'Librarian') {
    header('Location: ../index.php');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'libtech_db');
$msg = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $member_id = $_POST['member_id'];
    $type = $_POST['notification_type'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    $stmt = $conn->prepare("INSERT INTO notification (member_id, notification_type, subject, message, status) VALUES (?, ?, ?, ?, 'sent')");
    $stmt->bind_param("isss", $member_id, $type, $subject, $message);
    $stmt->execute();
    $msg = "<p style='color:#34d399'>✅ Notification sent!</p>";
}

$members = $conn->query("SELECT member_id, full_name FROM member ORDER BY full_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Send Notification</title>
    <style>
        body { font-family: Arial; background: #0a0a2a; color: white; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .form-container { background: rgba(255,255,255,0.1); border-radius: 30px; padding: 40px; width: 500px; }
        input, select, textarea { width: 100%; padding: 12px; margin: 10px 0; background: rgba(255,255,255,0.2); border-radius: 12px; color: white; border: none; }
        button { width: 100%; padding: 12px; background: #10b981; border: none; border-radius: 12px; color: white; cursor: pointer; }
        a { color: #818cf8; display: block; margin-top: 15px; text-align: center; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>📧 Send Notification</h2>
        <?php echo $msg; ?>
        <form method="POST">
            <select name="member_id" required><option value="">Select Member</option><?php while($m = $members->fetch_assoc()): ?><option value="<?php echo $m['member_id']; ?>"><?php echo $m['full_name']; ?></option><?php endwhile; ?></select>
            <select name="notification_type"><option value="borrow">Borrow</option><option value="return">Return</option><option value="overdue">Overdue</option><option value="fine">Fine</option></select>
            <input type="text" name="subject" placeholder="Subject" required>
            <textarea name="message" rows="5" placeholder="Message" required></textarea>
            <button type="submit">Send</button>
        </form>
        <a href="../librarian-dashboard.php">← Back</a>
    </div>
</body>
</html>