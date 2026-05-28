<?php
/**
 * SEND NOTIFICATION - Using Templates
 * 
 * FUNCTIONS: SELECT TEMPLATE, SELECT MEMBER, SEND, VALIDATE
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if(!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] != 'Librarian') {
    header('Location: ../index.php');
    exit();
}

require_once '../config.php';
require_once 'NotificationClass.php';

$notificationManager = new NotificationManager($conn);
$success = '';
$error = '';
$selected_template = null;

// Get template if selected
if(isset($_GET['template_id'])) {
    $selected_template = $notificationManager->findTemplate($_GET['template_id']);
}

// Get all members - DEBUG to see if members exist
$members = $notificationManager->getAllMembers();
$templates = $notificationManager->getTemplates();

// Debug: Check if members exist
if(empty($members)) {
    $error = "No members found in database. Please add members first.";
}

// Handle sending
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $member_id = $_POST['member_id'];
    $template_id = $_POST['template_id'];
    $custom_message = !empty($_POST['custom_message']) ? $_POST['custom_message'] : null;
    $custom_subject = !empty($_POST['custom_subject']) ? $_POST['custom_subject'] : null;
    
    $result = $notificationManager->sendNotification($member_id, $template_id, $custom_message, $custom_subject);
    
    if($result['success']) {
        $success = "✅ Notification sent successfully!";
    } else {
        $error = implode('<br>', $result['errors']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Send Notification - LibTech</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #0f0c29, #302b63, #24243e); min-height: 100vh; color: #e4e6eb; padding: 40px; }
        .container { max-width: 700px; margin: 0 auto; background: rgba(255,255,255,0.1); border-radius: 30px; padding: 40px; border: 1px solid rgba(255,255,255,0.2); }
        h1 { text-align: center; margin-bottom: 30px; background: linear-gradient(135deg, #fff, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        label { display: block; margin: 15px 0 5px; font-weight: 500; }
        select, textarea, input { width: 100%; padding: 12px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.2); border-radius: 12px; color: white; font-family: inherit; }
        textarea { min-height: 150px; resize: vertical; }
        button { width: 100%; padding: 14px; margin-top: 20px; background: linear-gradient(135deg, #6366f1, #ec4899); border: none; border-radius: 12px; color: white; font-weight: bold; cursor: pointer; font-size: 16px; }
        button:hover { transform: translateY(-2px); }
        .success { background: rgba(16,185,129,0.2); color: #34d399; padding: 12px; border-radius: 12px; margin-bottom: 20px; text-align: center; }
        .error { background: rgba(239,68,68,0.2); color: #f87171; padding: 12px; border-radius: 12px; margin-bottom: 20px; text-align: center; }
        .back-link { display: inline-block; margin-top: 20px; color: #818cf8; text-decoration: none; }
        .template-preview { background: rgba(99,102,241,0.1); padding: 15px; border-radius: 12px; margin: 15px 0; font-size: 13px; border-left: 3px solid #6366f1; }
        .help-text { font-size: 12px; color: #9ca3af; margin-top: 5px; }
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
            <!-- Select Member Dropdown -->
            <label><i class="fas fa-user"></i> Select Member:</label>
            <select name="member_id" required>
                <option value="">-- Choose Member --</option>
                <?php if(!empty($members)): ?>
                    <?php foreach($members as $m): ?>
                        <option value="<?php echo $m['member_id']; ?>">
                            <?php echo htmlspecialchars($m['full_name'] ?: $m['email']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="" disabled>No members found</option>
                <?php endif; ?>
            </select>
            
            <!-- Select Template Dropdown -->
            <label><i class="fas fa-file-alt"></i> Select Template:</label>
            <select name="template_id" id="template_select" required>
                <option value="">-- Choose Template --</option>
                <?php if(!empty($templates)): ?>
                    <?php foreach($templates as $t): ?>
                        <option value="<?php echo $t['template_id']; ?>" <?php echo ($selected_template && $selected_template['template_id'] == $t['template_id']) ? 'selected' : ''; ?>>
                            [<?php echo $t['template_type']; ?>] <?php echo htmlspecialchars($t['template_name']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="" disabled>No templates found. Create one first.</option>
                <?php endif; ?>
            </select>
            
            <!-- Optional Override Fields -->
            <label><i class="fas fa-edit"></i> Custom Subject (Optional):</label>
            <input type="text" name="custom_subject" id="custom_subject" placeholder="Leave blank to use template subject">
            
            <label><i class="fas fa-comment"></i> Custom Message (Optional):</label>
            <textarea name="custom_message" id="custom_message" placeholder="Leave blank to use template message"></textarea>
            <div class="help-text">💡 Tip: Leave blank to use the template's message. Customize if you want to override.</div>
            
            <!-- Template Preview -->
            <div id="template_preview" class="template-preview" style="display: none;"></div>
            
            <button type="submit"><i class="fas fa-paper-plane"></i> Send Notification</button>
        </form>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="manage.php" class="back-link">← Back to Template Manager</a>
        </div>
    </div>
    
    <script>
        // Template data for preview
        const templates = <?php 
            $template_data = [];
            if(!empty($templates)) {
                foreach($templates as $t) {
                    $template_data[$t['template_id']] = [
                        'subject' => $t['subject'],
                        'message' => $t['message']
                    ];
                }
            }
            echo json_encode($template_data);
        ?>;
        
        const templateSelect = document.getElementById('template_select');
        const subjectInput = document.getElementById('custom_subject');
        const messageTextarea = document.getElementById('custom_message');
        const previewDiv = document.getElementById('template_preview');
        
        if(templateSelect) {
            templateSelect.addEventListener('change', function() {
                const templateId = this.value;
                if(templateId && templates[templateId]) {
                    subjectInput.placeholder = templates[templateId].subject;
                    messageTextarea.placeholder = templates[templateId].message;
                    previewDiv.innerHTML = '<strong>📋 Template Preview:</strong><br>📧 ' + templates[templateId].subject + '<br><br>' + templates[templateId].message.replace(/\n/g, '<br>');
                    previewDiv.style.display = 'block';
                } else {
                    subjectInput.placeholder = 'Leave blank to use template subject';
                    messageTextarea.placeholder = 'Leave blank to use template message';
                    previewDiv.style.display = 'none';
                }
            });
            
            // Trigger change if template pre-selected
            if(templateSelect.value) {
                templateSelect.dispatchEvent(new Event('change'));
            }
        }
    </script>
</body>
</html>