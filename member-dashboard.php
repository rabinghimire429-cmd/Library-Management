<?php


// Start session and check if user is logged in
session_start();

// =============================================
// SECURITY: Role-based access control
// Only logged-in members can access this page
// =============================================
if(!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

// Include database configuration and helper functions
require_once 'config.php';
require_once 'includes/2fa.php';

$admin_id = $_SESSION['admin_id'];
$admin_email = $_SESSION['admin_email'];

// =============================================
// FUNCTIONALITY: Get member details from database
// Using prepared statement to prevent SQL injection
// =============================================
$stmt = $conn->prepare("SELECT * FROM member WHERE admin_id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle display name (use full_name or email username)
if($member && !empty($member['full_name']) && $member['full_name'] != $member['email']) {
    $name = $member['full_name'];
} else {
    $email_parts = explode('@', $admin_email);
    $name = ucfirst($email_parts[0]);
}

$member_id = $member['member_id'] ?? 0;
$member_phone = $member['phone'] ?? '';
$member_email = $member['email'] ?? $admin_email;

// =============================================
// FUNCTIONALITY: Get last login time for display
// ETHICS: Transparency - Show users when their account was last accessed
// This helps users detect unauthorized access (credential theft mitigation)
// =============================================
$last_login_stmt = $conn->prepare("SELECT Last_login FROM admin WHERE User_id = ?");
$last_login_stmt->bind_param("i", $admin_id);
$last_login_stmt->execute();
$last_login_result = $last_login_stmt->get_result();
$last_login_row = $last_login_result->fetch_assoc();
$last_login = $last_login_row['Last_login'] ?? null;
$last_login_stmt->close();

// Format last login for display
if($last_login) {
    $last_login_formatted = date('F j, Y \a\t g:i A', strtotime($last_login));
    $days_ago = floor((time() - strtotime($last_login)) / (60 * 60 * 24));
} else {
    $last_login_formatted = 'First time login - Welcome!';
    $days_ago = null;
}

// =============================================
// FUNCTIONALITY: Get borrowed books count
// =============================================
$borrow_stmt = $conn->prepare("SELECT COUNT(*) as count FROM transaction WHERE member_id = ? AND return_date IS NULL");
$borrow_stmt->bind_param("i", $member_id);
$borrow_stmt->execute();
$borrowed_count = $borrow_stmt->get_result()->fetch_assoc()['count'];
$borrow_stmt->close();

// =============================================
// FUNCTIONALITY: Get total pending fines
// =============================================
$fine_stmt = $conn->prepare("SELECT SUM(fine_amount) as total FROM transaction WHERE member_id = ? AND fine_paid = 0");
$fine_stmt->bind_param("i", $member_id);
$fine_stmt->execute();
$total_fines = $fine_stmt->get_result()->fetch_assoc()['total'] ?? 0;
$fine_stmt->close();

// =============================================
// FUNCTIONALITY: Get unread notifications count
// =============================================
$notif_count = 0;
if($member_id > 0) {
    $notif_stmt = $conn->prepare("SELECT COUNT(*) as count FROM notification WHERE member_id = ? AND (read_status = 0 OR read_status IS NULL)");
    $notif_stmt->bind_param("i", $member_id);
    $notif_stmt->execute();
    $notif_result = $notif_stmt->get_result();
    if($notif_result) {
        $notif_count = $notif_result->fetch_assoc()['count'];
    }
    $notif_stmt->close();
}

// =============================================
// FUNCTIONALITY: Get 2FA status for this member
// =============================================
$is_2fa_enabled = is2FAEnabled($conn, $admin_id);

// =============================================
// FUNCTIONALITY: Update profile
// Processes form submission for editing profile
// =============================================
$update_msg = '';
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    // CSRF Protection - Verify token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $update_msg = "<div class='success-msg' style='background:rgba(239,68,68,0.2); color:#f87171;'>❌ Security validation failed!</div>";
    } else {
        $full_name = htmlspecialchars($_POST['full_name']);
        $phone = htmlspecialchars($_POST['phone']);
        
        $update_stmt = $conn->prepare("UPDATE member SET full_name = ?, phone = ? WHERE member_id = ?");
        $update_stmt->bind_param("ssi", $full_name, $phone, $member_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        $update_msg = "<div class='success-msg'>✅ Profile updated successfully!</div>";
        $name = $full_name;
    }
}

// =============================================
// FUNCTIONALITY: Enable/Disable 2FA
// =============================================
if(isset($_POST['enable_2fa'])) {
    if(enable2FAForUser($conn, $admin_id)) {
        $update_msg = "<div class='success-msg'>✅ Two-Factor Authentication has been ENABLED for your account!</div>";
        $is_2fa_enabled = true;
    }
}

if(isset($_POST['disable_2fa'])) {
    if(disable2FAForUser($conn, $admin_id)) {
        $update_msg = "<div class='success-msg'>✅ Two-Factor Authentication has been DISABLED for your account.</div>";
        $is_2fa_enabled = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard - LibTech Solutions</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* =============================================
           PRESENTATION LAYER - CSS STYLES
           Modern glassmorphism design for member dashboard
        ============================================= */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            min-height: 100vh;
            color: #e4e6eb;
        }
        
        /* Navigation Bar */
        .navbar {
            background: rgba(15,23,42,0.95);
            backdrop-filter: blur(12px);
            padding: 16px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .logo-img { height: 45px; width: auto; border-radius: 10px; }
        .logo-text { font-size: 22px; font-weight: 800; background: linear-gradient(135deg, #fff, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        /* Profile Dropdown Menu */
        .profile-dropdown {
            position: relative;
            display: inline-block;
        }
        .profile-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 50px;
            padding: 6px 15px 6px 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .profile-btn:hover {
            background: rgba(255,255,255,0.2);
        }
        .avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 18px;
            color: white;
        }
        .profile-name { color: white; font-weight: 500; }
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 55px;
            background: rgba(20,20,50,0.98);
            backdrop-filter: blur(12px);
            min-width: 220px;
            border-radius: 16px;
            padding: 10px 0;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.1);
            z-index: 200;
        }
        .profile-dropdown:hover .dropdown-content {
            display: block;
        }
        .dropdown-content a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: #e4e6eb;
            text-decoration: none;
            transition: all 0.3s;
        }
        .dropdown-content a:hover {
            background: rgba(99,102,241,0.3);
        }
        .logout-btn {
            color: #f87171 !important;
        }
        
        /* Main Container */
        .container { max-width: 1400px; margin: 40px auto; padding: 0 40px; }
        
        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, rgba(99,102,241,0.15), rgba(236,72,153,0.15));
            border-radius: 40px;
            padding: 50px;
            margin-bottom: 40px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .welcome-section h1 { font-size: 42px; font-weight: 800; background: linear-gradient(135deg, #fff, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 10px; }
        .welcome-section p { color: #b9bbbe; }
        
        /* Last Login Info Box - ETHICS: Transparency */
        .last-login-info {
            background: rgba(99,102,241,0.1);
            border-radius: 12px;
            padding: 12px 20px;
            margin-top: 15px;
            border-left: 3px solid #818cf8;
            text-align: left;
        }
        .last-login-info i {
            color: #818cf8;
            margin-right: 8px;
        }
        .last-login-warning {
            color: #fbbf24;
            margin-left: 10px;
        }
        .last-login-help {
            font-size: 12px;
            color: #8b8d94;
            margin-top: 5px;
        }
        
        /* Statistics Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; margin-bottom: 50px; }
        .stat-card { background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 30px; padding: 30px; text-align: center; transition: all 0.3s; }
        .stat-card:hover { transform: translateY(-5px); background: rgba(255,255,255,0.08); border-color: #6366f1; }
        .stat-number { font-size: 48px; font-weight: 800; background: linear-gradient(135deg, #818cf8, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 10px; }
        .stat-label { color: #b9bbbe; font-size: 14px; }
        
        /* Menu Grid - Navigation Cards */
        .menu-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 25px; margin-bottom: 40px; }
        .menu-card { background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 30px; padding: 30px; text-align: center; text-decoration: none; transition: all 0.3s; display: block; }
        .menu-card:hover { transform: translateY(-8px); background: rgba(255,255,255,0.1); border-color: #6366f1; }
        .menu-icon { font-size: 50px; margin-bottom: 15px; }
        .menu-card h3 { font-size: 18px; font-weight: 600; color: white; margin-bottom: 8px; }
        .menu-card p { color: #8b8d94; font-size: 12px; }
        
        /* Currently Borrowed Books Section */
        .borrowed-section { background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 30px; padding: 30px; margin-top: 40px; }
        .borrowed-section h3 { font-size: 20px; margin-bottom: 20px; color: #a78bfa; }
        .book-list { display: flex; flex-direction: column; gap: 15px; }
        .book-item { background: rgba(255,255,255,0.03); border-radius: 20px; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .book-title { font-weight: 600; color: white; }
        .due-date { font-size: 13px; color: #b9bbbe; }
        .overdue { color: #f87171; }
        
        /* Settings Card for 2FA Toggle */
        .settings-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 30px;
            padding: 30px;
            margin-top: 30px;
        }
        .settings-card h3 {
            color: #a78bfa;
            margin-bottom: 15px;
        }
        .settings-card p {
            color: #b9bbbe;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        .btn-2fa-enable {
            background: linear-gradient(135deg, #10b981, #059669);
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            color: white;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-2fa-disable {
            background: linear-gradient(135deg, #f87171, #dc2626);
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            color: white;
            cursor: pointer;
            font-weight: 600;
        }
        
        /* ETHICS: Transparency - Help Section */
        .help-section { background: rgba(99,102,241,0.08); border-radius: 24px; padding: 25px; margin-top: 40px; border: 1px solid rgba(99,102,241,0.2); }
        .help-section h3 { color: #a78bfa; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        .help-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
        .help-card { background: rgba(255,255,255,0.03); border-radius: 16px; padding: 15px; }
        .help-card strong { display: block; margin-bottom: 8px; }
        .help-card p { font-size: 13px; color: #b9bbbe; line-height: 1.5; }
        .help-footer { margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255,255,255,0.1); text-align: center; }
        
        /* Modal Styling */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: rgba(20,20,50,0.98); border-radius: 30px; padding: 30px; width: 450px; max-width: 90%; border: 1px solid rgba(255,255,255,0.2); }
        .modal-content h3 { margin-bottom: 20px; color: #a78bfa; }
        .modal-content input { width: 100%; padding: 12px; margin: 10px 0; background: #0f1419; border: 1px solid #2d3139; border-radius: 12px; color: #e4e6eb; }
        .modal-content button { width: 100%; padding: 12px; background: linear-gradient(135deg, #6366f1, #ec4899); border: none; border-radius: 12px; color: white; cursor: pointer; }
        .close-btn { float: right; font-size: 24px; cursor: pointer; color: #888; }
        .success-msg { background: rgba(16,185,129,0.2); color: #34d399; padding: 12px; border-radius: 12px; margin-bottom: 20px; text-align: center; }
        
        /* Footer */
        .footer { text-align: center; padding: 30px; color: #8b8d94; font-size: 12px; margin-top: 40px; border-top: 1px solid rgba(255,255,255,0.05); }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar { flex-direction: column; gap: 15px; padding: 15px 20px; }
            .container { padding: 0 20px; }
            .welcome-section h1 { font-size: 28px; }
            .welcome-section { padding: 30px; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar with Logo and Profile Dropdown -->
    <div class="navbar">
        <div class="logo">
            <img src="logo.jpg" alt="Logo" class="logo-img" onerror="this.style.display='none'">
            <span class="logo-text">LibTech Solutions</span>
        </div>
        <div class="profile-dropdown">
            <div class="profile-btn">
                <div class="avatar"><?php echo strtoupper(substr($name, 0, 1)); ?></div>
                <span class="profile-name"><?php echo htmlspecialchars($name); ?></span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="dropdown-content">
                <a href="#" onclick="openEditProfileModal()"><i class="fas fa-user-edit"></i> Edit Profile</a>
                <a href="B/my-fines.php"><i class="fas fa-coins"></i> My Fines</a>
                <a href="B/my-borrowings.php"><i class="fas fa-book"></i> My Borrowings</a>
                <a href="auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Welcome Section with Last Login Display -->
        <div class="welcome-section">
            <h1>Welcome back, <?php echo htmlspecialchars($name); ?>! 👋</h1>
            <p>Your library journey continues here.</p>
            
            <!-- ============================================= -->
            <!-- ETHICS & SECURITY: Last Login Display        -->
            <!-- Shows users when their account was last used -->
            <!-- Helps detect unauthorized access             -->
            <!-- This addresses the Risk Register requirement -->
            <!-- for "Credential theft via phishing"          -->
            <!-- ============================================= -->
            <div class="last-login-info">
                <i class="fas fa-clock"></i>
                <strong>Last account activity:</strong> <?php echo $last_login_formatted; ?>
                <?php if($days_ago && $days_ago > 7): ?>
                    <span class="last-login-warning">
                        <i class="fas fa-exclamation-triangle"></i> It's been <?php echo $days_ago; ?> days since your last login
                    </span>
                <?php endif; ?>
                <div class="last-login-help">
                    <i class="fas fa-shield-alt"></i> 
                    If you don't recognize this activity, please contact your librarian immediately.
                    This helps protect your account from unauthorized access.
                </div>
            </div>
            
            <?php if($is_2fa_enabled): ?>
                <p style="margin-top: 10px; color: #34d399;"><i class="fas fa-shield-alt"></i> 2FA Protected Account</p>
            <?php endif; ?>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-number"><?php echo $borrowed_count; ?></div><div class="stat-label"><i class="fas fa-book"></i> Books Borrowed</div></div>
            <div class="stat-card"><div class="stat-number">$<?php echo number_format($total_fines, 2); ?></div><div class="stat-label"><i class="fas fa-coins"></i> Pending Fines</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $notif_count; ?></div><div class="stat-label"><i class="fas fa-bell"></i> Unread Notifications</div></div>
        </div>

        <!-- Menu Grid - Navigation Cards -->
        <div class="menu-grid">
            <a href="Books/search-books.php" class="menu-card"><div class="menu-icon">🔍</div><h3>Search Books</h3><p>Find your next read</p></a>
            <a href="B/borrow-book.php" class="menu-card"><div class="menu-icon">📚</div><h3>Borrow a Book</h3><p>Checkout books</p></a>
            <a href="B/my-borrowings.php" class="menu-card"><div class="menu-icon">📋</div><h3>My Borrowings</h3><p>Track your books</p></a>
            <a href="B/my-fines.php" class="menu-card"><div class="menu-icon">💰</div><h3>My Fines</h3><p>View & pay fines</p></a>
            <a href="Notification/notifications.php" class="menu-card"><div class="menu-icon">🔔</div><h3>Notifications</h3><p>Stay updated</p></a>
        </div>

        <!-- Currently Borrowed Books Section (Loaded via AJAX) -->
        <div class="borrowed-section" id="borrowedBooks">
            <h3><i class="fas fa-book-open"></i> Currently Borrowed Books</h3>
            <div class="book-list" id="bookList"><p style="text-align:center; padding:20px;">Loading your borrowed books...</p></div>
        </div>

        <!-- ============================================= -->
        <!-- SECURITY: Two-Factor Authentication Settings  -->
        <!-- Allows users to enable/disable 2FA on their account -->
        <!-- ============================================= -->
        <div class="settings-card">
            <h3><i class="fas fa-shield-alt"></i> Security Settings</h3>
            <p>Two-Factor Authentication (2FA) adds an extra layer of security to your account. When enabled, you'll need to enter a verification code after logging in with your password.</p>
            
            <?php if($is_2fa_enabled): ?>
                <p style="color: #34d399; margin-bottom: 15px;">
                    <i class="fas fa-check-circle"></i> 2FA is currently ENABLED on your account
                </p>
                <form method="POST">
                    <button type="submit" name="disable_2fa" class="btn-2fa-disable" onclick="return confirm('Are you sure you want to disable Two-Factor Authentication? This will make your account less secure.')">
                        <i class="fas fa-lock-open"></i> Disable 2FA
                    </button>
                </form>
            <?php else: ?>
                <p style="color: #fbbf24; margin-bottom: 15px;">
                    <i class="fas fa-exclamation-triangle"></i> 2FA is currently DISABLED on your account
                </p>
                <form method="POST">
                    <button type="submit" name="enable_2fa" class="btn-2fa-enable">
                        <i class="fas fa-shield-alt"></i> Enable 2FA
                    </button>
                </form>
            <?php endif; ?>
            <p style="font-size: 12px; color: #8b8d94; margin-top: 15px;">
                <i class="fas fa-info-circle"></i> When enabled, you'll receive a 6-digit code during login. Check your email for the verification code.
            </p>
        </div>

        <!-- ============================================= -->
        <!-- ETHICS: Transparency - Help Section          -->
        <!-- Explains how the system works to users       -->
        <!-- This addresses the Ethics Checklist requirement -->
        <!-- ============================================= -->
        <div class="help-section">
            <h3><i class="fas fa-question-circle"></i> How the Library System Works</h3>
            <div class="help-grid">
                <div class="help-card">
                    <strong style="color: #34d399;">📖 Borrowing Books</strong>
                    <p>Members can borrow books for 14 days. The due date is automatically calculated from the borrow date. You can view your borrowed books and due dates on this dashboard.</p>
                </div>
                <div class="help-card">
                    <strong style="color: #f87171;">💰 Fine Calculation</strong>
                    <p>Late returns incur a fine of <strong>$0.50 per day</strong>. The fine is calculated automatically based on the due date. All members are charged the same rate - no exceptions.</p>
                </div>
                <div class="help-card">
                    <strong style="color: #818cf8;">🔔 Notifications</strong>
                    <p>You will receive email notifications for borrow confirmations, due date reminders, and fine payments. Check your notification center for all communications.</p>
                </div>
                <div class="help-card">
                    <strong style="color: #fbbf24;">🔒 Privacy & Security</strong>
                    <p>Your personal information is only used for library operations. Passwords are encrypted using bcrypt. Sessions automatically expire after 30 minutes of inactivity. Your last login time is displayed to help you detect unauthorized access.</p>
                </div>
            </div>
            <div class="help-footer">
                <p><i class="fas fa-shield-alt"></i> For questions about fines or due dates, please contact your librarian.</p>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeEditProfileModal()">&times;</span>
            <h3>✏️ Edit Profile</h3>
            <?php echo $update_msg; ?>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="text" name="full_name" placeholder="Full Name" value="<?php echo htmlspecialchars($name); ?>" required>
                <input type="tel" name="phone" placeholder="Phone Number" value="<?php echo htmlspecialchars($member_phone); ?>" required>
                <button type="submit" name="update_profile">Save Changes</button>
            </form>
        </div>
    </div>

    <div class="footer">
        <p>© 2026 LibTech Solutions | Secure Library Management System | Built with <i class="fas fa-heart" style="color:#ec4899;"></i> by DMU Students</p>
    </div>

    <!-- ============================================= -->
    <!-- JAVASCRIPT - Loads borrowed books via AJAX   -->
    <!-- ============================================= -->
    <script>
        // Open edit profile modal
        function openEditProfileModal() { 
            document.getElementById('editProfileModal').classList.add('active'); 
        }
        
        // Close edit profile modal
        function closeEditProfileModal() { 
            document.getElementById('editProfileModal').classList.remove('active'); 
        }
        
        // Close modal when clicking outside
        window.onclick = function(e) { 
            if(e.target.classList.contains('modal')) e.target.classList.remove('active'); 
        }
        
        // =============================================
        // FUNCTIONALITY: Load borrowed books via AJAX
        // Fetches current borrowings from API
        // =============================================
        fetch('api/get-borrowed-books.php')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var list = document.getElementById('bookList');
                if(data.success && data.books.length > 0) {
                    var html = '';
                    for(var i = 0; i < data.books.length; i++) {
                        var book = data.books[i];
                        var isOverdue = book.status === 'Overdue';
                        html += '<div class="book-item">' +
                                    '<div>' +
                                        '<div class="book-title">' + book.title + '</div>' +
                                        '<div class="due-date">by ' + book.author + '</div>' +
                                    '</div>' +
                                    '<div>' +
                                        '<div class="due-date">Borrowed: ' + book.borrow_date + '</div>' +
                                        '<div class="due-date ' + (isOverdue ? 'overdue' : '') + '">Due: ' + book.due_date + '</div>' +
                                        (isOverdue ? '<div class="overdue">⚠️ Overdue by ' + book.days_overdue + ' days</div>' : '') +
                                        (book.fine_amount > 0 ? '<div class="overdue">Fine: $' + parseFloat(book.fine_amount).toFixed(2) + '</div>' : '') +
                                    '</div>' +
                                '</div>';
                    }
                    list.innerHTML = html;
                } else { 
                    list.innerHTML = '<p style="text-align:center; padding:20px;">📭 You have no borrowed books.</p>'; 
                }
            })
            .catch(function() { 
                document.getElementById('bookList').innerHTML = '<p style="text-align:center; padding:20px;">❌ Error loading borrowed books.</p>'; 
            });
    </script>
</body>
</html>