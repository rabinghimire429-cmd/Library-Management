<?php
/**
 * member-dashboard.php - Member Dashboard Page
 
 */

session_start();

// =============================================
// SECURITY: Only logged-in users can access
// =============================================
if(!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

require_once 'config.php';
require_once 'includes/2fa.php';

$admin_id = $_SESSION['admin_id'];
$admin_email = $_SESSION['admin_email'];

// =============================================
// Get member details
// =============================================
$stmt = $conn->prepare("SELECT * FROM member WHERE admin_id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();
$stmt->close();

if($member && !empty($member['full_name']) && $member['full_name'] != $member['email']) {
    $name = $member['full_name'];
} else {
    $email_parts = explode('@', $admin_email);
    $name = ucfirst($email_parts[0]);
}

$member_id = $member['member_id'] ?? 0;
$member_phone = $member['phone'] ?? '';
$member_email = $member['email'] ?? $admin_email;
$profile_picture = $member['profile_picture'] ?? 'default-avatar.png';

// =============================================
// Handle profile picture upload
// =============================================
$profile_update_msg = '';
if(isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $_FILES['profile_picture']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if(in_array($ext, $allowed)) {
        $new_filename = 'profile_' . $admin_id . '_' . time() . '.' . $ext;
        $upload_path = 'uploads/profiles/' . $new_filename;
        
        if(!is_dir('uploads/profiles')) {
            mkdir('uploads/profiles', 0777, true);
        }
        
        if(move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
            $update_stmt = $conn->prepare("UPDATE member SET profile_picture = ? WHERE member_id = ?");
            $update_stmt->bind_param("si", $new_filename, $member_id);
            $update_stmt->execute();
            $update_stmt->close();
            $profile_picture = $new_filename;
            $profile_update_msg = "<div class='success-msg'>✅ Profile picture updated!</div>";
        }
    }
}

// =============================================
// Get last login time
// =============================================
$last_login_stmt = $conn->prepare("SELECT Last_login FROM admin WHERE User_id = ?");
$last_login_stmt->bind_param("i", $admin_id);
$last_login_stmt->execute();
$last_login_result = $last_login_stmt->get_result();
$last_login_row = $last_login_result->fetch_assoc();
$last_login = $last_login_row['Last_login'] ?? null;
$last_login_stmt->close();

if($last_login) {
    $last_login_formatted = date('F j, Y \a\t g:i A', strtotime($last_login));
    $days_ago = floor((time() - strtotime($last_login)) / (60 * 60 * 24));
} else {
    $last_login_formatted = 'First time login - Welcome!';
    $days_ago = null;
}

// =============================================
// Get statistics
// =============================================
$borrow_stmt = $conn->prepare("SELECT COUNT(*) as count FROM transaction WHERE member_id = ? AND return_date IS NULL");
$borrow_stmt->bind_param("i", $member_id);
$borrow_stmt->execute();
$borrowed_count = $borrow_stmt->get_result()->fetch_assoc()['count'];
$borrow_stmt->close();

$fine_stmt = $conn->prepare("SELECT SUM(fine_amount) as total FROM transaction WHERE member_id = ? AND fine_paid = 0");
$fine_stmt->bind_param("i", $member_id);
$fine_stmt->execute();
$total_fines = $fine_stmt->get_result()->fetch_assoc()['total'] ?? 0;
$fine_stmt->close();

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
// Get 2FA status
// =============================================
$is_2fa_enabled = is2FAEnabled($conn, $admin_id);
$update_msg = '';

// Handle 2FA toggle
if(isset($_POST['enable_2fa'])) {
    if(enable2FAForUser($conn, $admin_id)) {
        $update_msg = "<div class='success-msg'>✅ 2FA has been ENABLED!</div>";
        $is_2fa_enabled = true;
    }
}
if(isset($_POST['disable_2fa'])) {
    if(disable2FAForUser($conn, $admin_id)) {
        $update_msg = "<div class='success-msg'>✅ 2FA has been DISABLED!</div>";
        $is_2fa_enabled = false;
    }
}

// Handle profile update
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            min-height: 100vh;
            color: #e4e6eb;
        }
        
        /* ============================================= */
        /* TOP NAVIGATION BAR                           */
        /* ============================================= */
        .top-nav {
            background: rgba(15,23,42,0.95);
            backdrop-filter: blur(12px);
            padding: 12px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
            flex-wrap: wrap;
            gap: 15px;
        }
        .logo-area {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .logo-img { height: 40px; width: auto; border-radius: 10px; }
        .logo-text { font-size: 18px; font-weight: 800; background: linear-gradient(135deg, #fff, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        .nav-links {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        .nav-item {
            position: relative;
        }
        .nav-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            background: rgba(255,255,255,0.08);
            border-radius: 30px;
            color: #e4e6eb;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }
        .nav-btn:hover {
            background: rgba(99,102,241,0.4);
            transform: translateY(-2px);
        }
        .nav-btn i {
            color: #818cf8;
        }
        
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 45px;
            right: 0;
            background: rgba(20,20,50,0.98);
            backdrop-filter: blur(12px);
            min-width: 280px;
            border-radius: 16px;
            padding: 15px;
            border: 1px solid rgba(255,255,255,0.1);
            z-index: 200;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }
        .nav-item:hover .dropdown-menu {
            display: block;
        }
        .dropdown-title {
            font-size: 14px;
            font-weight: 600;
            color: #818cf8;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .dropdown-item {
            padding: 10px 0;
            font-size: 13px;
            color: #e4e6eb;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .dropdown-item:last-child {
            border-bottom: none;
        }
        .dropdown-btn {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            border-radius: 10px;
            color: white;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
        }
        .dropdown-btn-danger {
            background: linear-gradient(135deg, #f87171, #dc2626);
        }
        
        /* Profile Dropdown */
        .profile-dropdown {
            position: relative;
            display: inline-block;
        }
        .profile-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.08);
            padding: 5px 15px 5px 8px;
            border-radius: 40px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .profile-btn:hover {
            background: rgba(255,255,255,0.15);
        }
        .profile-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
            color: white;
            overflow: hidden;
        }
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .profile-name {
            font-size: 13px;
            font-weight: 500;
        }
        .profile-dropdown-menu {
            display: none;
            position: absolute;
            top: 50px;
            right: 0;
            background: rgba(20,20,50,0.98);
            backdrop-filter: blur(12px);
            min-width: 200px;
            border-radius: 16px;
            padding: 10px;
            border: 1px solid rgba(255,255,255,0.1);
            z-index: 200;
        }
        .profile-dropdown:hover .profile-dropdown-menu {
            display: block;
        }
        .profile-dropdown-item {
            padding: 10px;
            color: #e4e6eb;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            border-radius: 10px;
            transition: all 0.3s;
        }
        .profile-dropdown-item:hover {
            background: rgba(99,102,241,0.3);
        }
        
        /* Main Container */
        .container { max-width: 1400px; margin: 30px auto; padding: 0 40px; }
        
        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, rgba(99,102,241,0.15), rgba(236,72,153,0.15));
            border-radius: 25px;
            padding: 25px;
            margin-bottom: 25px;
            text-align: center;
        }
        .welcome-section h1 { font-size: 26px; font-weight: 800; background: linear-gradient(135deg, #fff, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        /* Last Login Info */
        .last-login-info {
            background: rgba(99,102,241,0.15);
            border-radius: 12px;
            padding: 10px 15px;
            margin-top: 15px;
            border-left: 3px solid #818cf8;
            text-align: left;
            font-size: 13px;
        }
        .last-login-info i { color: #818cf8; margin-right: 8px; }
        .last-login-warning { color: #fbbf24; margin-left: 10px; }
        
        /* Statistics Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 20px; text-align: center; transition: all 0.3s; }
        .stat-card:hover { transform: translateY(-5px); background: rgba(255,255,255,0.08); border-color: #6366f1; }
        .stat-number { font-size: 36px; font-weight: 800; background: linear-gradient(135deg, #818cf8, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat-label { color: #b9bbbe; font-size: 12px; margin-top: 5px; }
        
        /* Quick Menu Cards */
        .quick-menu { margin-bottom: 30px; }
        .quick-menu h3 { margin-bottom: 15px; color: #a78bfa; font-size: 18px; }
        .menu-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 15px; }
        .menu-card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 15px; padding: 15px; text-align: center; text-decoration: none; transition: all 0.3s; display: block; }
        .menu-card:hover { transform: translateY(-5px); background: rgba(255,255,255,0.1); border-color: #6366f1; }
        .menu-icon { font-size: 30px; margin-bottom: 8px; }
        .menu-card h4 { font-size: 14px; font-weight: 600; color: white; }
        .menu-card p { color: #8b8d94; font-size: 10px; margin-top: 5px; }
        
        /* Borrowed Books Section */
        .borrowed-section { background: rgba(255,255,255,0.05); border-radius: 20px; padding: 20px; margin-bottom: 25px; }
        .borrowed-section h3 { color: #a78bfa; margin-bottom: 15px; font-size: 18px; }
        .book-list { display: flex; flex-direction: column; gap: 10px; }
        .book-item { background: rgba(255,255,255,0.03); border-radius: 12px; padding: 12px 15px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .book-title { font-weight: 600; color: white; }
        .due-date { font-size: 12px; color: #b9bbbe; }
        .overdue { color: #f87171; }
        
        /* Help Section - Bottom of page */
        .help-section {
            background: rgba(99,102,241,0.08);
            border-radius: 20px;
            padding: 25px;
            margin-top: 30px;
            border: 1px solid rgba(99,102,241,0.2);
            scroll-margin-top: 80px;
        }
        .help-section h3 {
            color: #a78bfa;
            margin-bottom: 15px;
            font-size: 20px;
        }
        .tips-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 15px;
        }
        .tip-card {
            background: rgba(255,255,255,0.03);
            border-radius: 12px;
            padding: 15px;
        }
        .tip-card strong {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #fbbf24;
        }
        .tip-card p {
            font-size: 12px;
            color: #b9bbbe;
            line-height: 1.5;
        }
        
        /* Modal */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: rgba(20,20,50,0.98); border-radius: 20px; padding: 25px; width: 450px; max-width: 90%; }
        .modal-content h3 { margin-bottom: 15px; color: #a78bfa; }
        .modal-content input, .modal-content select { width: 100%; padding: 10px; margin: 8px 0; background: #0f1419; border: 1px solid #2d3139; border-radius: 10px; color: white; }
        .modal-content input[type="file"] { padding: 5px; }
        .modal-content button { width: 100%; padding: 10px; background: linear-gradient(135deg, #6366f1, #ec4899); border: none; border-radius: 10px; color: white; cursor: pointer; margin-top: 10px; }
        .close-btn { float: right; font-size: 22px; cursor: pointer; color: #888; }
        .success-msg { background: rgba(16,185,129,0.2); color: #34d399; padding: 10px; border-radius: 10px; margin-bottom: 15px; text-align: center; font-size: 13px; }
        .profile-preview {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin: 10px auto;
            display: block;
            object-fit: cover;
            background: #2d3139;
        }
        
        .footer { text-align: center; padding: 20px; color: #8b8d94; font-size: 11px; border-top: 1px solid rgba(255,255,255,0.05); margin-top: 30px; }
        
        @media (max-width: 900px) {
            .top-nav { flex-direction: column; }
            .nav-links { justify-content: center; }
            .container { padding: 0 20px; }
        }
    </style>
</head>
<body>

<!-- ============================================= -->
<!-- TOP NAVIGATION BAR                           -->
<!-- Menu, Help, Contact, Settings, Profile       -->
<!-- ============================================= -->
<div class="top-nav">
    <!-- Logo Section -->
    <div class="logo-area">
        <img src="logo.jpg" alt="Logo" class="logo-img" onerror="this.style.display='none'">
        <span class="logo-text">LibTech Solutions</span>
    </div>
    
    <div class="nav-links">
        
        <!-- ========================================= -->
        <!-- MENU BUTTON - Redirects to Dashboard      -->
        <!-- ========================================= -->
        <a href="member-dashboard.php" class="nav-btn" id="menuBtn">
            <i class="fas fa-bars"></i> Menu
        </a>
        
        <!-- ========================================= -->
        <!-- HELP BUTTON - Scrolls to Help Section     -->
        <!-- ========================================= -->
        <a href="#helpSection" class="nav-btn" id="helpBtn">
            <i class="fas fa-question-circle"></i> Help
        </a>
        
        <!-- ========================================= -->
        <!-- CONTACT US - Dropdown with contact info   -->
        <!-- ========================================= -->
        <div class="nav-item">
            <div class="nav-btn">
                <i class="fas fa-envelope"></i> Contact <i class="fas fa-chevron-down"></i>
            </div>
            <div class="dropdown-menu">
                <div class="dropdown-title">📞 Contact Information</div>
                <div class="dropdown-item">
                    <p><i class="fas fa-map-marker-alt" style="width: 25px;"></i> <strong>Address:</strong> Niels Brock College, Copenhagen, Denmark</p>
                </div>
                <div class="dropdown-item">
                    <p><i class="fas fa-envelope" style="width: 25px;"></i> <strong>Email:</strong> support@libtechsolutions.com</p>
                </div>
                <div class="dropdown-item">
                    <p><i class="fas fa-phone" style="width: 25px;"></i> <strong>Phone:</strong> +45 1234 5678</p>
                </div>
                <div class="dropdown-item">
                    <p><i class="fas fa-clock" style="width: 25px;"></i> <strong>Hours:</strong> Monday - Friday: 9:00 AM - 5:00 PM</p>
                </div>
            </div>
        </div>
        
        <!-- ========================================= -->
        <!-- SETTINGS - 2FA Toggle inside dropdown    -->
        <!-- ========================================= -->
        <div class="nav-item">
            <div class="nav-btn">
                <i class="fas fa-cog"></i> Settings <i class="fas fa-chevron-down"></i>
            </div>
            <div class="dropdown-menu">
                <div class="dropdown-title">🔐 Security Settings</div>
                <div class="dropdown-item">
                    <strong>Two-Factor Authentication (2FA)</strong>
                    <p style="font-size: 11px; color: #8b8d94; margin-top: 5px;">
                        Adds an extra layer of security to your account.
                    </p>
                    <?php if($is_2fa_enabled): ?>
                        <form method="POST">
                            <button type="submit" name="disable_2fa" class="dropdown-btn dropdown-btn-danger" onclick="return confirm('Disable 2FA?')">
                                <i class="fas fa-lock-open"></i> Disable 2FA
                            </button>
                        </form>
                        <p style="font-size: 11px; color: #34d399; margin-top: 8px;">✅ 2FA is ENABLED</p>
                    <?php else: ?>
                        <form method="POST">
                            <button type="submit" name="enable_2fa" class="dropdown-btn">
                                <i class="fas fa-shield-alt"></i> Enable 2FA
                            </button>
                        </form>
                        <p style="font-size: 11px; color: #fbbf24; margin-top: 8px;">⚠️ 2FA is DISABLED</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- ========================================= -->
        <!-- LOGOUT BUTTON                            -->
        <!-- ========================================= -->
        <a href="auth/logout.php" class="nav-btn" style="background: rgba(239,68,68,0.2);">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
    
    <!-- ========================================= -->
    <!-- PROFILE DROPDOWN - Edit Profile          -->
    <!-- ========================================= -->
    <div class="profile-dropdown">
        <div class="profile-btn">
            <div class="profile-avatar">
                <?php 
                $avatar_path = 'uploads/profiles/' . $profile_picture;
                if($profile_picture != 'default-avatar.png' && file_exists($avatar_path)): ?>
                    <img src="<?php echo $avatar_path; ?>" alt="Profile">
                <?php else: ?>
                    <?php echo strtoupper(substr($name, 0, 1)); ?>
                <?php endif; ?>
            </div>
            <span class="profile-name"><?php echo htmlspecialchars($name); ?></span>
            <i class="fas fa-chevron-down"></i>
        </div>
        <div class="profile-dropdown-menu">
            <a href="#" onclick="openEditProfileModal()" class="profile-dropdown-item">
                <i class="fas fa-user-edit"></i> Edit Profile
            </a>
            <a href="B/my-borrowings.php" class="profile-dropdown-item">
                <i class="fas fa-book"></i> My Borrowings
            </a>
            <a href="B/my-fines.php" class="profile-dropdown-item">
                <i class="fas fa-coins"></i> My Fines
            </a>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- MAIN DASHBOARD CONTENT                        -->
<!-- ============================================= -->
<div class="container">
    
    <!-- Welcome Section -->
    <div class="welcome-section">
        <h1>Welcome back, <?php echo htmlspecialchars($name); ?>! 👋</h1>
        <p>Your library journey continues here.</p>
        
        <!-- Last Login Display -->
        <div class="last-login-info">
            <i class="fas fa-clock"></i>
            <strong>Last account activity:</strong> <?php echo $last_login_formatted; ?>
            <?php if($days_ago && $days_ago > 7): ?>
                <span class="last-login-warning">
                    <i class="fas fa-exclamation-triangle"></i> It's been <?php echo $days_ago; ?> days since your last login
                </span>
            <?php endif; ?>
            <div style="font-size: 11px; color: #8b8d94; margin-top: 5px;">
                <i class="fas fa-shield-alt"></i> If you don't recognize this activity, please contact your librarian.
            </div>
        </div>
        
        <?php if($is_2fa_enabled): ?>
            <p style="margin-top: 10px; color: #34d399; font-size: 12px;">
                <i class="fas fa-shield-alt"></i> 2FA Protected Account
            </p>
        <?php endif; ?>
        
        <?php echo $update_msg; ?>
        <?php echo $profile_update_msg; ?>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $borrowed_count; ?></div>
            <div class="stat-label"><i class="fas fa-book"></i> Books Borrowed</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">$<?php echo number_format($total_fines, 2); ?></div>
            <div class="stat-label"><i class="fas fa-coins"></i> Pending Fines</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $notif_count; ?></div>
            <div class="stat-label"><i class="fas fa-bell"></i> Unread Notifications</div>
        </div>
    </div>

    <!-- Quick Menu Cards -->
    <div class="quick-menu">
        <h3><i class="fas fa-rocket"></i> Quick Actions</h3>
        <div class="menu-grid">
            <a href="Books/search-books.php" class="menu-card"><div class="menu-icon">🔍</div><h4>Search Books</h4><p>Find your next read</p></a>
            <a href="B/borrow-book.php" class="menu-card"><div class="menu-icon">📚</div><h4>Borrow a Book</h4><p>Checkout books</p></a>
            <a href="B/my-borrowings.php" class="menu-card"><div class="menu-icon">📋</div><h4>My Borrowings</h4><p>Track your books</p></a>
            <a href="B/my-fines.php" class="menu-card"><div class="menu-icon">💰</div><h4>My Fines</h4><p>View & pay fines</p></a>
            <a href="Notification/notifications.php" class="menu-card"><div class="menu-icon">🔔</div><h4>Notifications</h4><p>Stay updated</p></a>
        </div>
    </div>

    <!-- Currently Borrowed Books -->
    <div class="borrowed-section">
        <h3><i class="fas fa-book-open"></i> Currently Borrowed Books</h3>
        <div class="book-list" id="bookList">
            <p style="text-align:center; padding:20px;">Loading your borrowed books...</p>
        </div>
    </div>
    
    <!-- ============================================= -->
    <!-- HELP SECTION - Bottom of page                -->
    <!-- Clicking Help button scrolls to here         -->
    <!-- ============================================= -->
    <div id="helpSection" class="help-section">
        <h3><i class="fas fa-lightbulb"></i> Helpful Tips for Members</h3>
        <div class="tips-grid">
            <div class="tip-card">
                <strong><i class="fas fa-book"></i> Borrowing Books</strong>
                <p>You can borrow up to 5 books at a time. Each book can be borrowed for 14 days.</p>
            </div>
            <div class="tip-card">
                <strong><i class="fas fa-coins"></i> Avoid Fines</strong>
                <p>Return books before the due date to avoid $0.50/day late fees. Check "My Borrowings" for due dates.</p>
            </div>
            <div class="tip-card">
                <strong><i class="fas fa-bell"></i> Notifications</strong>
                <p>Check your notifications for borrow confirmations and due date reminders.</p>
            </div>
            <div class="tip-card">
                <strong><i class="fas fa-shield-alt"></i> Account Security</strong>
                <p>Enable 2FA from Settings for extra security. Never share your password with anyone.</p>
            </div>
            <div class="tip-card">
                <strong><i class="fas fa-clock"></i> Session Timeout</strong>
                <p>Your session expires after 30 minutes of inactivity. Log out on shared computers.</p>
            </div>
            <div class="tip-card">
                <strong><i class="fas fa-search"></i> Finding Books</strong>
                <p>Use "Search Books" to find books by title, author, or genre. Check availability before visiting.</p>
            </div>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- EDIT PROFILE MODAL                           -->
<!-- ============================================= -->
<div id="editProfileModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeEditProfileModal()">&times;</span>
        <h3><i class="fas fa-user-edit"></i> Edit Profile</h3>
        
        <!-- Profile Picture Preview -->
        <div style="text-align: center;">
            <?php if($profile_picture != 'default-avatar.png' && file_exists('uploads/profiles/' . $profile_picture)): ?>
                <img src="uploads/profiles/<?php echo $profile_picture; ?>" class="profile-preview" id="profilePreview">
            <?php else: ?>
                <div class="profile-preview" style="display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #6366f1, #ec4899); font-size: 40px;">
                    <?php echo strtoupper(substr($name, 0, 1)); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <label>Profile Picture</label>
            <input type="file" name="profile_picture" accept="image/jpeg,image/png,image/gif" onchange="previewImage(this)">
            
            <label>Full Name</label>
            <input type="text" name="full_name" placeholder="Full Name" value="<?php echo htmlspecialchars($name); ?>" required>
            
            <label>Phone Number</label>
            <input type="tel" name="phone" placeholder="Phone Number" value="<?php echo htmlspecialchars($member_phone); ?>" required>
            
            <button type="submit" name="update_profile">Save Changes</button>
        </form>
    </div>
</div>

<!-- Footer -->
<div class="footer">
    <p>© 2026 LibTech Solutions | Secure Library Management System | Built with <i class="fas fa-heart" style="color:#ec4899;"></i> by DMU Students</p>
</div>

<!-- JavaScript -->
<script>
    function openEditProfileModal() { 
        document.getElementById('editProfileModal').classList.add('active'); 
    }
    
    function closeEditProfileModal() { 
        document.getElementById('editProfileModal').classList.remove('active'); 
    }
    
    window.onclick = function(e) { 
        if(e.target.classList.contains('modal')) e.target.classList.remove('active'); 
    }
    
    function previewImage(input) {
        if(input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var preview = document.getElementById('profilePreview');
                if(preview) {
                    preview.src = e.target.result;
                } else {
                    var newPreview = document.createElement('img');
                    newPreview.src = e.target.result;
                    newPreview.className = 'profile-preview';
                    newPreview.style.margin = '10px auto';
                    newPreview.style.display = 'block';
                    newPreview.style.width = '80px';
                    newPreview.style.height = '80px';
                    newPreview.style.borderRadius = '50%';
                    newPreview.style.objectFit = 'cover';
                    input.parentElement.insertBefore(newPreview, input);
                }
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Smooth scroll to help section
    document.getElementById('helpBtn').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('helpSection').scrollIntoView({ 
            behavior: 'smooth', 
            block: 'start' 
        });
    });
    
    // Load borrowed books via AJAX
    fetch('api/get-borrowed-books.php')
        .then(function(response) { return response.json(); })
        .then(function(data) {
            var list = document.getElementById('bookList');
            if(data.success && data.books.length > 0) {
                var html = '';
                for(var i = 0; i < data.books.length; i++) {
                    var book = data.books[i];
                    var isOverdue = book.status === 'Overdue';
                    html += '<div class="book-item">' +
                                '<div>' +
                                    '<div class="book-title">' + (book.title || '') + '</div>' +
                                    '<div class="due-date">by ' + (book.author || '') + '</div>' +
                                '</div>' +
                                '<div>' +
                                    '<div class="due-date">Borrowed: ' + (book.borrow_date || '') + '</div>' +
                                    '<div class="due-date ' + (isOverdue ? 'overdue' : '') + '">Due: ' + (book.due_date || '') + '</div>' +
                                    (isOverdue ? '<div class="overdue">⚠️ Overdue by ' + (book.days_overdue || 0) + ' days</div>' : '') +
                                    (book.fine_amount > 0 ? '<div class="overdue">Fine: $' + parseFloat(book.fine_amount).toFixed(2) + '</div>' : '') +
                                '</div>' +
                            '</div>';
                }
                list.innerHTML = html;
            } else { 
                list.innerHTML = '<p style="text-align:center; padding:20px;">📭 You have no borrowed books.</p>'; 
            }
        })
        .catch(function(error) { 
            console.error('Error:', error);
            document.getElementById('bookList').innerHTML = '<p style="text-align:center; padding:20px;">❌ Error loading borrowed books.</p>'; 
        });
</script>
</body>
</html>