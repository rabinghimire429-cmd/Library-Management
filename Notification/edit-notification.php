<?php
/**
 * EDIT SENT NOTIFICATION
 * Librarians can edit notifications that were already sent
 */

session_start();
if(!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] != 'Librarian') {
    header('Location: ../index.php');
    exit();
}

require_once '../config.php';
require_once 'NotificationClass.php';

$notificationManager = new NotificationManager($conn);
$id = $_GET['id'] ?? 0;
$notification = $notificationManager->findNotification($id);

if(!$notification) {
    header('Location: manage.php?error=notfound');
    exit();
}

$success = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $result = $notificationManager->editNotification($id, $_POST['message'], $_POST['notification_type']);
    
    if($result['success']) {
        $success = "✅ Notification updated successfully!";
        $notification = $notificationManager->findNotification($id);
    } else {
        $error = implode('<br>', $result['errors']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Notification</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #0f0c29, #302b63, #24243e); min-height: 100vh; color: #e4e6eb; padding: 40px; }
        .container { max-width: 600px; margin: 0 auto; background: rgba(255,255,255,0.1); border-radius: 30px; padding: 40px; }
        h1 { text-align: center; margin-bottom: 20px; }
        label { display: block; margin: 15px 0 5px; }
        select, textarea { width: 100%; padding: 12px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.2); border-radius: 12px; color: white; }
        textarea { min-height: 150px; }
        button { width: 100%; padding: 14px; margin-top: 20px; background: linear-gradient(135deg, #6366f1, #ec4899); border: none; border-radius: 12px; color: white; cursor: pointer; }
        .success { background: rgba(16,185,129,0.2); color: #34d399; padding: 12px; border-radius: 12px; margin-bottom: 20px; text-align: center; }
        .error { background: rgba(239,68,68,0.2); color: #f87171; padding: 12px; border-radius: 12px; margin-bottom: 20px; text-align: center; }
        .info-box { background: rgba(99,102,241,0.1); padding: 12px; border-radius: 12px; margin-bottom: 20px; font-size: 13px; }
        .back-link { display: inline-block; margin-top: 20px; color: #818cf8; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>✏️ Edit Notification</h1>
        
        <div class="info-box">
            <strong>Notification ID:</strong> <?php echo $notification['notification_id']; ?><br>
            <strong>Member ID:</strong> <?php echo $notification['member_id']; ?><br>
            <strong>Sent:</strong> <?php echo date('F j, Y g:i A', strtotime($notification['sent_date'])); ?>
        </div>
        
        <?php if($success): ?><div class="success"><?php echo $success; ?></div><?php endif; ?>
        <?php if($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
        
        <form method="POST">
            <label>Notification Type:</label>
            <select name="notification_type" required>
                <option value="BORROW" <?php echo $notification['notification_type'] == 'BORROW' ? 'selected' : ''; ?>>📚 Borrow</option>
                <option value="REMINDER" <?php echo $notification['notification_type'] == 'REMINDER' ? 'selected' : ''; ?>>⏰ Reminder</option>
                <option value="OVERDUE" <?php echo $notification['notification_type'] == 'OVERDUE' ? 'selected' : ''; ?>>⚠️ Overdue</option>
                <option value="FINE" <?php echo $notification['notification_type'] == 'FINE' ? 'selected' : ''; ?>>💰 Fine</option>
            </select>
            
            <label>Message:</label>
            <textarea name="message" maxlength="255" required><?php echo htmlspecialchars($notification['message']); ?></textarea>
            
            <button type="submit">Save Changes</button>
        </form>
        
        <div style="text-align: center;">
            <a href="manage.php" class="back-link">← Back</a>
        </div>
    </div>
</body>
</html>