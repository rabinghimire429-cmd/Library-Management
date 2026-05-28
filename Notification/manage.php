<?php
/**
 * NOTIFICATION TEMPLATE MANAGEMENT
 * 
 * FUNCTIONS: ADD, EDIT, DELETE, LIST, SEARCH, FILTER templates
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
$error = '';
$edit_template = null;

// ============ ADD TEMPLATE ============
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_template'])) {
    $result = $notificationManager->addTemplate(
        $_POST['template_name'],
        $_POST['template_type'],
        $_POST['subject'],
        $_POST['message'],
        $_POST['placeholders'] ?? ''
    );
    
    if($result['success']) {
        $message = "✅ Template created successfully!";
    } else {
        $error = implode('<br>', $result['errors']);
    }
}

// ============ EDIT TEMPLATE - Get template data ============
if(isset($_GET['edit'])) {
    $edit_template = $notificationManager->findTemplate($_GET['edit']);
}

// ============ UPDATE TEMPLATE ============
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_template'])) {
    $result = $notificationManager->editTemplate(
        $_POST['template_id'],
        $_POST['template_name'],
        $_POST['template_type'],
        $_POST['subject'],
        $_POST['message'],
        $_POST['placeholders'] ?? ''
    );
    
    if($result['success']) {
        $message = "✅ Template updated successfully!";
        $edit_template = null;
    } else {
        $error = implode('<br>', $result['errors']);
    }
}

// ============ DELETE TEMPLATE ============
if(isset($_GET['delete'])) {
    if($notificationManager->deleteTemplate($_GET['delete'])) {
        $message = "✅ Template deleted successfully!";
    }
}

// Get filters and search
$type_filter = $_GET['type'] ?? 'all';
$search_query = $_GET['search'] ?? '';

// Get templates
$templates = $notificationManager->getTemplates($type_filter, $search_query);
$stats = $notificationManager->getStatistics();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notification Templates - Manage</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #0f0c29, #302b63, #24243e); min-height: 100vh; color: #e4e6eb; }
        .navbar { background: rgba(15,23,42,0.95); padding: 16px 40px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .logo-text { font-size: 22px; font-weight: 800; background: linear-gradient(135deg, #fff, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .container { max-width: 1400px; margin: 30px auto; padding: 0 30px; }
        
        /* Stats Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: rgba(255,255,255,0.05); border-radius: 20px; padding: 20px; text-align: center; }
        .stat-number { font-size: 32px; font-weight: 800; background: linear-gradient(135deg, #818cf8, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        /* Forms */
        .form-card { background: rgba(255,255,255,0.05); border-radius: 20px; padding: 25px; margin-bottom: 30px; }
        .form-card h3 { margin-bottom: 20px; color: #a78bfa; }
        .form-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 15px; }
        input, select, textarea { width: 100%; padding: 12px; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.2); border-radius: 12px; color: white; font-family: inherit; }
        textarea { min-height: 120px; resize: vertical; }
        button { padding: 12px 25px; background: linear-gradient(135deg, #6366f1, #ec4899); border: none; border-radius: 12px; color: white; cursor: pointer; font-weight: 600; }
        button:hover { transform: translateY(-2px); }
        .cancel-btn { background: #4b5563; }
        
        /* Search and Filter */
        .search-filter { display: flex; gap: 15px; margin-bottom: 30px; flex-wrap: wrap; align-items: center; }
        .search-box { flex: 1; display: flex; gap: 10px; }
        .search-box input { flex: 1; }
        .filter-btns { display: flex; gap: 10px; flex-wrap: wrap; }
        .filter-btn { padding: 8px 20px; background: rgba(255,255,255,0.1); border-radius: 25px; text-decoration: none; color: white; }
        .filter-btn.active { background: #6366f1; }
        
        /* Templates Grid */
        .templates-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; }
        .template-card { background: rgba(255,255,255,0.05); border-radius: 20px; padding: 20px; transition: 0.3s; }
        .template-card:hover { transform: translateY(-5px); background: rgba(255,255,255,0.08); }
        .template-type { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; margin-bottom: 10px; }
        .type-BORROW { background: #dbeafe; color: #1e40af; }
        .type-REMINDER { background: #dcfce7; color: #166534; }
        .type-OVERDUE { background: #fee2e2; color: #b91c1c; }
        .type-FINE { background: #fef3c7; color: #92400e; }
        .template-name { font-size: 18px; font-weight: 700; margin-bottom: 8px; }
        .template-subject { font-size: 13px; color: #9ca3af; margin-bottom: 10px; }
        .template-message { font-size: 12px; color: #cbd5e1; line-height: 1.4; max-height: 60px; overflow: hidden; }
        .template-actions { margin-top: 15px; display: flex; gap: 12px; }
        .template-actions a { text-decoration: none; font-size: 13px; }
        .send-btn { color: #34d399; }
        .edit-btn { color: #818cf8; }
        .delete-btn { color: #f87171; }
        
        .success-msg { background: rgba(16,185,129,0.2); color: #34d399; padding: 12px; border-radius: 12px; margin-bottom: 20px; text-align: center; }
        .error-msg { background: rgba(239,68,68,0.2); color: #f87171; padding: 12px; border-radius: 12px; margin-bottom: 20px; text-align: center; }
        .btn-send-new { background: #10b981; padding: 10px 20px; border-radius: 12px; color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .back-link { color: #818cf8; text-decoration: none; }
        
        @media (max-width: 768px) { .container { padding: 0 15px; } .templates-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="navbar">
        <span class="logo-text">📋 Notification Template Manager</span>
        <a href="../librarian-dashboard.php" class="back-link">← Dashboard</a>
    </div>
    
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h1><i class="fas fa-file-alt"></i> Notification Templates</h1>
            <a href="send-notification.php" class="btn-send-new"><i class="fas fa-paper-plane"></i> Send Notification</a>
        </div>
        
        <?php if($message): ?><div class="success-msg"><?php echo $message; ?></div><?php endif; ?>
        <?php if($error): ?><div class="error-msg"><?php echo $error; ?></div><?php endif; ?>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-number"><?php echo $stats['total_templates']; ?></div><div>Templates</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $stats['total_notifications']; ?></div><div>Notifications Sent</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $stats['unread']; ?></div><div>Unread</div></div>
        </div>
        
        <!-- ============ ADD / EDIT TEMPLATE FORM ============ -->
        <div class="form-card">
            <h3><i class="fas fa-<?php echo $edit_template ? 'edit' : 'plus'; ?>"></i> <?php echo $edit_template ? 'Edit Template' : 'Create New Template'; ?></h3>
            <form method="POST">
                <?php if($edit_template): ?>
                    <input type="hidden" name="template_id" value="<?php echo $edit_template['template_id']; ?>">
                <?php endif; ?>
                <div class="form-row">
                    <div>
                        <label>Template Name</label>
                        <input type="text" name="template_name" placeholder="e.g., Welcome Email, Overdue Notice" value="<?php echo $edit_template['template_name'] ?? ''; ?>" required>
                    </div>
                    <div>
                        <label>Template Type</label>
                        <select name="template_type" required>
                            <option value="">Select Type</option>
                            <option value="BORROW" <?php echo ($edit_template['template_type'] ?? '') == 'BORROW' ? 'selected' : ''; ?>>📚 Borrow</option>
                            <option value="REMINDER" <?php echo ($edit_template['template_type'] ?? '') == 'REMINDER' ? 'selected' : ''; ?>>⏰ Reminder</option>
                            <option value="OVERDUE" <?php echo ($edit_template['template_type'] ?? '') == 'OVERDUE' ? 'selected' : ''; ?>>⚠️ Overdue</option>
                            <option value="FINE" <?php echo ($edit_template['template_type'] ?? '') == 'FINE' ? 'selected' : ''; ?>>💰 Fine</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label>Subject</label>
                    <input type="text" name="subject" placeholder="Notification subject" value="<?php echo $edit_template['subject'] ?? ''; ?>" required>
                </div>
                <div>
                    <label>Message Template</label>
                    <textarea name="message" placeholder="Write your message here. Use {member_name} to insert member's name" required><?php echo $edit_template['message'] ?? ''; ?></textarea>
                    <small style="color: #9ca3af;">💡 Tip: Use {member_name} to automatically insert the member's name</small>
                </div>
                <div>
                    <label>Placeholders (comma separated, optional)</label>
                    <input type="text" name="placeholders" placeholder="e.g., {book_title}, {due_date}, {fine_amount}" value="<?php echo $edit_template['placeholders'] ?? ''; ?>">
                </div>
                <div style="margin-top: 20px;">
                    <?php if($edit_template): ?>
                        <button type="submit" name="update_template"><i class="fas fa-save"></i> Update Template</button>
                        <a href="manage.php" class="cancel-btn" style="padding: 12px 25px; background: #4b5563; border-radius: 12px; text-decoration: none; color: white; margin-left: 10px;">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="add_template"><i class="fas fa-plus"></i> Create Template</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- ============ SEARCH AND FILTER ============ -->
        <div class="search-filter">
            <div class="search-box">
                <form method="GET" style="display: flex; gap: 10px; width: 100%;">
                    <input type="text" name="search" placeholder="🔍 Search templates by name, subject, or message..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <input type="hidden" name="type" value="<?php echo $type_filter; ?>">
                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                    <?php if($search_query): ?>
                        <a href="?type=<?php echo $type_filter; ?>" class="filter-btn">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="filter-btns">
                <a href="?type=all&search=<?php echo urlencode($search_query); ?>" class="filter-btn <?php echo $type_filter=='all'?'active':''; ?>">All</a>
                <a href="?type=BORROW&search=<?php echo urlencode($search_query); ?>" class="filter-btn <?php echo $type_filter=='BORROW'?'active':''; ?>">📚 Borrow</a>
                <a href="?type=REMINDER&search=<?php echo urlencode($search_query); ?>" class="filter-btn <?php echo $type_filter=='REMINDER'?'active':''; ?>">⏰ Reminder</a>
                <a href="?type=OVERDUE&search=<?php echo urlencode($search_query); ?>" class="filter-btn <?php echo $type_filter=='OVERDUE'?'active':''; ?>">⚠️ Overdue</a>
                <a href="?type=FINE&search=<?php echo urlencode($search_query); ?>" class="filter-btn <?php echo $type_filter=='FINE'?'active':''; ?>">💰 Fine</a>
            </div>
        </div>
        
        <!-- ============ TEMPLATES LIST ============ -->
        <div class="templates-grid">
            <?php if(count($templates) > 0): ?>
                <?php foreach($templates as $template): ?>
                    <div class="template-card">
                        <span class="template-type type-<?php echo $template['template_type']; ?>">
                            <?php echo $template['template_type']; ?>
                        </span>
                        <div class="template-name"><?php echo htmlspecialchars($template['template_name']); ?></div>
                        <div class="template-subject">📧 <?php echo htmlspecialchars($template['subject']); ?></div>
                        <div class="template-message"><?php echo htmlspecialchars(substr($template['message'], 0, 100)); ?>...</div>
                        <div class="template-actions">
                            <a href="send-notification.php?template_id=<?php echo $template['template_id']; ?>" class="send-btn"><i class="fas fa-paper-plane"></i> Send</a>
                            <a href="?edit=<?php echo $template['template_id']; ?>" class="edit-btn"><i class="fas fa-edit"></i> Edit</a>
                            <a href="?delete=<?php echo $template['template_id']; ?>" class="delete-btn" onclick="return confirm('Delete this template?')"><i class="fas fa-trash"></i> Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 60px; grid-column: 1/-1; background: rgba(255,255,255,0.05); border-radius: 20px;">
                    <i class="fas fa-file-alt" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                    No templates found. Create your first template above!
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>