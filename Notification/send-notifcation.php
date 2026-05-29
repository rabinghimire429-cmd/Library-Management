<?php
// Notification/send-notification.php - STANDALONE WORKING VERSION
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if(!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] != 'Librarian') {
    header('Location: ../index.php');
    exit();
}

require_once '../config.php';

$success = '';
$error = '';

// Get members directly from database
$members = [];
$members_result = $conn->query("SELECT member_id, full_name, email FROM member ORDER BY full_name");
if($members_result) {
    while($row = $members_result->fetch_assoc()) {
        $members[] = $row;
    }
}

// Get templates directly from database
$templates = [];
$templates_result = $conn->query("SELECT * FROM notification_templates ORDER BY template_type, template_name");
if($templates_result) {
    while($row = $templates_result->fetch_assoc()) {
        $templates[] = $row;
    }
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $member_id = $_POST['member_id'];
    $template_id = $_POST['template_id'];
    $custom_message = trim($_POST['custom_message'] ?? '');
    
    // Get selected template
    $stmt = $conn->prepare("SELECT * FROM notification_templates WHERE template_id = ?");
    $stmt->bind_param("i", $template_id);
    $stmt->execute();
    $template = $stmt->get_result()->fetch_assoc();
    
    // Get selected member
    $stmt = $conn->prepare("SELECT * FROM member WHERE member_id = ?");
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $member = $stmt->get_result()->fetch_assoc();
    
    if($member && $template) {
        // Use custom message or template message
        $final_message = !empty($custom_message) ? $custom_message : $template['message'];
        // Replace placeholder with member name
        $final_message = str_replace('{member_name}', $member['full_name'] ?? 'Member', $final_message);
        
        // Insert notification
        $insert = $conn->prepare("INSERT INTO notification (member_id, message, notification_type, sent_date, read_status) VALUES (?, ?, ?, NOW(), 1)");
        $insert->bind_param("iss", $member_id, $final_message, $template['template_type']);
        
        if($insert->execute()) {
            $success = "✅ Notification sent successfully to " . htmlspecialchars($member['full_name'] ?? $member['email']) . "!";
        } else {
            $error = "Database error: " . $conn->error;
        }
    } else {
        $error = "Member or Template not found";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Send Notification</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #0f0c29, #302b63, #24243e); min-height: 100vh; color: #e4e6eb; padding: 40px; }
        .container { max-width: 600px; margin: 0 auto; background: rgba(255,255,255,0.1); border-radius: 30px; padding: 40px; border: 1px solid rgba(255,255,255,0.2); }
        h1 { text-align: center; margin-bottom: 30px; background: linear-gradient(135deg, #fff, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        label { display: block; margin: 15px 0 5px; font-weight: 500; }
        select, textarea { width: 100%; padding: 12px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.2); border-radius: 12px; color: white; font-family: inherit; }
        textarea { min-height: 120px; resize: vertical; }
        button { width: 100%; padding: 14px; margin-top: 20px; background: linear-gradient(135deg, #6366f1, #ec4899); border: none; border-radius: 12px; color: white; font-weight: bold; cursor: pointer; font-size: 16px; }
        button:hover { transform: translateY(-2px); }
        .success { background: rgba(16,185,129,0.2); color: #34d399; padding: 12px; border-radius: 12px; margin-bottom: 20px; text-align: center; border-left: 3px solid #34d399; }
        .error { background: rgba(239,68,68,0.2); color: #f87171; padding: 12px; border-radius: 12px; margin-bottom: 20px; text-align: center; border-left: 3px solid #f87171; }
        .back-link { display: inline-block; margin-top: 20px; color: #818cf8; text-decoration: none; }
        .preview-box { background: rgba(99,102,241,0.1); padding: 15px; border-radius: 12px; margin: 15px 0; font-size: 13px; border-left: 3px solid #6366f1; display: none; }
        .info-text { font-size: 12px; color: #9ca3af; margin-top: 5px; text-align: right; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-paper-plane"></i> Send Notification</h1>
        
        <?php if($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <label><i class="fas fa-user"></i> Select Member:</label>
            <select name="member_id" required>
                <option value="">-- Choose Member --</option>
                <?php foreach($members as $m): ?>
                    <option value="<?php echo $m['member_id']; ?>">
                        <?php echo htmlspecialchars($m['full_name'] ?: $m['email']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <label><i class="fas fa-file-alt"></i> Select Template:</label>
            <select name="template_id" id="template_select" required>
                <option value="">-- Choose Template --</option>
                <?php foreach($templates as $t): ?>
                    <option value="<?php echo $t['template_id']; ?>">
                        [<?php echo $t['template_type']; ?>] <?php echo htmlspecialchars($t['template_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <div id="preview_box" class="preview-box"></div>
            
            <label><i class="fas fa-edit"></i> Custom Message (Optional):</label>
            <textarea name="custom_message" id="custom_message" rows="5" placeholder="Leave blank to use template message"></textarea>
            <div class="info-text">💡 Tip: Use {member_name} to insert the member's name</div>
            
            <button type="submit"><i class="fas fa-paper-plane"></i> Send Notification</button>
        </form>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="manage.php" class="back-link">← Back to Template Manager</a>
        </div>
    </div>
    
    <script>
        // Store templates for preview
        const templates = <?php 
            $data = [];
            foreach($templates as $t) {
                $data[$t['template_id']] = [
                    'type' => $t['template_type'],
                    'name' => $t['template_name'],
                    'message' => $t['message']
                ];
            }
            echo json_encode($data);
        ?>;
        
        const templateSelect = document.getElementById('template_select');
        const previewBox = document.getElementById('preview_box');
        const messageBox = document.getElementById('custom_message');
        
        templateSelect.addEventListener('change', function() {
            const id = this.value;
            if(id && templates[id]) {
                previewBox.innerHTML = '<strong>📋 Template Preview:</strong><br>' + templates[id].message.replace(/\n/g, '<br>');
                previewBox.style.display = 'block';
                messageBox.placeholder = 'Leave blank to use the template above';
            } else {
                previewBox.style.display = 'none';
                messageBox.placeholder = 'Enter your custom message here...';
            }
        });
        
        // Trigger on page load if template pre-selected
        if(templateSelect.value) {
            templateSelect.dispatchEvent(new Event('change'));
        }
    </script>
</body>
</html>