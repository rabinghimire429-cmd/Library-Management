<?php
// Notification/send-notification.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if(!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] != 'Librarian') {
    header('Location: ../index.php');
    exit();
}

require_once '../config.php';

$admin_id = $_SESSION['admin_id'];
$success_msg = '';
$error_msg = '';

// Debug: Check connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Get all members
$members_query = "SELECT member_id, full_name, email FROM member ORDER BY full_name";
$members = $conn->query($members_query);

if (!$members) {
    $error_msg = "Database error: " . $conn->error;
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get and validate inputs
    $member_id = isset($_POST['member_id']) ? (int)$_POST['member_id'] : 0;
    $notification_type = isset($_POST['notification_type']) ? trim($_POST['notification_type']) : '';
    $message_text = isset($_POST['message']) ? trim($_POST['message']) : '';
    $sent_date = date('Y-m-d H:i:s');
    
    // Validate
    $errors = [];
    if($member_id <= 0) {
        $errors[] = "Please select a member";
    }
    if(empty($notification_type)) {
        $errors[] = "Please select notification type";
    }
    if(empty($message_text)) {
        $errors[] = "Please enter a message";
    }
    if(strlen($message_text) > 255) {
        $errors[] = "Message cannot exceed 255 characters";
    }
    
    // Check if member exists
    $check_member = $conn->query("SELECT member_id FROM member WHERE member_id = $member_id");
    if($check_member->num_rows == 0) {
        $errors[] = "Selected member does not exist";
    }
    
    if(empty($errors)) {
        // Check what columns exist in notification table
        $columns_query = "SHOW COLUMNS FROM notification";
        $columns_result = $conn->query($columns_query);
        $existing_columns = [];
        while($col = $columns_result->fetch_assoc()) {
            $existing_columns[] = $col['Field'];
        }
        
        // Build query based on existing columns
        if(in_array('read_status', $existing_columns)) {
            $insert = $conn->prepare("INSERT INTO notification (member_id, message, notification_type, sent_date, read_status) VALUES (?, ?, ?, ?, 0)");
            $insert->bind_param("isss", $member_id, $message_text, $notification_type, $sent_date);
        } 
        elseif(in_array('status', $existing_columns)) {
            $insert = $conn->prepare("INSERT INTO notification (member_id, message, notification_type, sent_date, status) VALUES (?, ?, ?, ?, 1)");
            $insert->bind_param("isss", $member_id, $message_text, $notification_type, $sent_date);
        }
        else {
            $insert = $conn->prepare("INSERT INTO notification (member_id, message, notification_type, sent_date) VALUES (?, ?, ?, ?)");
            $insert->bind_param("isss", $member_id, $message_text, $notification_type, $sent_date);
        }
        
        if($insert->execute()) {
            $success_msg = "✅ Notification sent successfully!";
            // Clear form
            $_POST = array();
        } else {
            $error_msg = "❌ Database error: " . $insert->error;
        }
        $insert->close();
    } else {
        $error_msg = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Notification - Librarian</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #0f0c29, #302b63, #24243e); min-height: 100vh; color: #e4e6eb; padding: 40px; }
        .container { max-width: 600px; margin: 0 auto; background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 30px; padding: 40px; border: 1px solid rgba(255,255,255,0.2); }
        h1 { margin-bottom: 20px; text-align: center; background: linear-gradient(135deg, #fff, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        label { display: block; margin: 15px 0 5px; font-weight: 500; }
        select, textarea, input { width: 100%; padding: 12px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.2); border-radius: 12px; color: white; font-family: inherit; }
        textarea { resize: vertical; min-height: 100px; }
        button { width: 100%; padding: 14px; margin-top: 20px; background: linear-gradient(135deg, #6366f1, #ec4899); border: none; border-radius: 12px; color: white; font-weight: bold; cursor: pointer; font-size: 16px; transition: all 0.3s; }
        button:hover { transform: translateY(-2px); background: linear-gradient(135deg, #818cf8, #f472b6); }
        .back-link { display: inline-block; margin-top: 20px; color: #818cf8; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
        .success { background: rgba(16,185,129,0.2); color: #34d399; padding: 12px; border-radius: 12px; margin-bottom: 20px; text-align: center; border-left: 3px solid #34d399; }
        .error { background: rgba(239,68,68,0.2); color: #f87171; padding: 12px; border-radius: 12px; margin-bottom: 20px; text-align: center; border-left: 3px solid #f87171; }
        .char-count { font-size: 12px; color: #9ca3af; text-align: right; margin-top: 5px; }
        .button-group { display: flex; gap: 15px; margin-top: 20px; }
        .button-group button { margin-top: 0; }
        hr { border-color: rgba(255,255,255,0.1); margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-paper-plane"></i> Send Notification</h1>
        
        <?php if($success_msg): ?>
            <div class="success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        
        <?php if($error_msg): ?>
            <div class="error"><?php echo $error_msg; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <label><i class="fas fa-user"></i> Select Member:</label>
            <select name="member_id" required>
                <option value="">-- Choose Member --</option>
                <?php 
                if($members && $members->num_rows > 0):
                    while($m = $members->fetch_assoc()): 
                        $display_name = !empty($m['full_name']) ? $m['full_name'] : $m['email'];
                ?>
                    <option value="<?php echo $m['member_id']; ?>" <?php echo (isset($_POST['member_id']) && $_POST['member_id'] == $m['member_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($display_name); ?>
                    </option>
                <?php 
                    endwhile;
                else: 
                ?>
                    <option value="" disabled>No members found</option>
                <?php endif; ?>
            </select>
            
            <label><i class="fas fa-tag"></i> Notification Type:</label>
            <select name="notification_type" required>
                <option value="">-- Select Type --</option>
                <option value="BORROW" <?php echo (isset($_POST['notification_type']) && $_POST['notification_type'] == 'BORROW') ? 'selected' : ''; ?>>📚 Borrow</option>
                <option value="REMINDER" <?php echo (isset($_POST['notification_type']) && $_POST['notification_type'] == 'REMINDER') ? 'selected' : ''; ?>>⏰ Reminder</option>
                <option value="OVERDUE" <?php echo (isset($_POST['notification_type']) && $_POST['notification_type'] == 'OVERDUE') ? 'selected' : ''; ?>>⚠️ Overdue</option>
                <option value="FINE" <?php echo (isset($_POST['notification_type']) && $_POST['notification_type'] == 'FINE') ? 'selected' : ''; ?>>💰 Fine</option>
            </select>
            
            <label><i class="fas fa-comment"></i> Message (max 255 characters):</label>
            <textarea name="message" maxlength="255" required placeholder="Enter notification message..."><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
            <div class="char-count"><span id="charCount"><?php echo isset($_POST['message']) ? strlen($_POST['message']) : 0; ?></span>/255 characters</div>
            
            <button type="submit"><i class="fas fa-paper-plane"></i> Send Notification</button>
        </form>
        
        <hr>
        
        <div style="display: flex; justify-content: space-between; margin-top: 20px;">
            <a href="notifications.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Notifications</a>
            <a href="../librarian-dashboard.php" class="back-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        </div>
    </div>
    
    <script>
        const textarea = document.querySelector('textarea');
        const charCount = document.getElementById('charCount');
        if(textarea) {
            textarea.addEventListener('input', function() {
                charCount.textContent = this.value.length;
            });
        }
    </script>
</body>
</html>