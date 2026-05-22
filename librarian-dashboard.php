<?php

// Start session
session_start();

// =============================================
// SECURITY: Role-based access control
// Only Librarian role can access this page
// =============================================
if(!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'Librarian') {
    header('Location: index.php');
    exit();
}

// Include database configuration and helper functions
require_once 'config.php';
require_once 'includes/2fa.php';

$admin_id = $_SESSION['admin_id'];
$admin_email = $_SESSION['admin_email'];

// =============================================
// FUNCTIONALITY: Get librarian details
// Using prepared statement to prevent SQL injection
// =============================================
$stmt = $conn->prepare("SELECT * FROM member WHERE admin_id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle display name
if($member && !empty($member['full_name'])) {
    $name = $member['full_name'];
} else {
    $email_parts = explode('@', $admin_email);
    $name = ucfirst($email_parts[0]) . ' (Librarian)';
}
$librarian_id = $member['member_id'] ?? 0;
$librarian_phone = $member['phone'] ?? '';

// =============================================
// FUNCTIONALITY: Get library statistics
// Total books, members, active borrowings, overdue count
// =============================================
$total_books = $conn->query("SELECT COUNT(*) as count FROM book")->fetch_assoc()['count'];
$total_members = $conn->query("SELECT COUNT(*) as count FROM member")->fetch_assoc()['count'];
$active_borrowings = $conn->query("SELECT COUNT(*) as count FROM transaction WHERE return_date IS NULL")->fetch_assoc()['count'];
$overdue_count = $conn->query("SELECT COUNT(*) as count FROM transaction WHERE return_date IS NULL AND due_date < CURDATE()")->fetch_assoc()['count'];

// =============================================
// FUNCTIONALITY: Get recent overdue books preview
// Shows top 5 overdue books with member and book details
// =============================================
$overdue_stmt = $conn->prepare("SELECT t.*, m.full_name as member_name, b.title as book_title 
                              FROM transaction t 
                              JOIN member m ON t.member_id = m.member_id 
                              JOIN book b ON t.book_id = b.book_id 
                              WHERE t.return_date IS NULL AND t.due_date < CURDATE() 
                              LIMIT 5");
$overdue_stmt->execute();
$overdue_preview = $overdue_stmt->get_result();
$overdue_stmt->close();

// =============================================
// SCRUM-25: FUNCTIONALITY - Get Popular Books
// Shows top 5 most borrowed books (by transaction count)
// This addresses the user story: "As a librarian, I want to see popular books on my dashboard"
// =============================================
$popular_books_query = "
    SELECT 
        b.book_id, 
        b.title, 
        b.author, 
        b.genre,
        COUNT(t.transaction_id) as borrow_count
    FROM book b
    LEFT JOIN transaction t ON b.book_id = t.book_id
    GROUP BY b.book_id
    ORDER BY borrow_count DESC
    LIMIT 5
";
$popular_books = $conn->query($popular_books_query);

// =============================================
// Get 2FA status for this librarian
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
        $update_stmt->bind_param("ssi", $full_name, $phone, $librarian_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        $update_msg = "<div class='success-msg'>✅ Profile updated successfully!</div>";
        $name = $full_name;
    }
}

// =============================================
// FUNCTIONALITY: Enable/Disable 2FA for Librarian
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
    <title>Librarian Dashboard - LibTech Solutions</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* =============================================
           PRESENTATION LAYER - CSS STYLES
           Modern glassmorphism design for librarian dashboard
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
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .logo-img { height: 45px; width: auto; border-radius: 10px; }
        .logo-text { font-size: 22px; font-weight: 800; background: linear-gradient(135deg, #fff, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        /* Profile Dropdown */
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
            border: 1px solid rgba(255,255,255,0.1);
            z-index: 200;
        }
        .profile-dropdown:hover .dropdown-content { display: block; }
        .dropdown-content a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: #e4e6eb;
            text-decoration: none;
        }
        .dropdown-content a:hover { background: rgba(99,102,241,0.3); }
        .logout-btn { color: #f87171 !important; }
        
        /* Main Container */
        .container { max-width: 1400px; margin: 40px auto; padding: 0 40px; }
        
        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, rgba(99,102,241,0.15), rgba(236,72,153,0.15));
            border-radius: 40px;
            padding: 45px;
            margin-bottom: 40px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .welcome-section h1 { font-size: 36px; font-weight: 800; background: linear-gradient(135deg, #fff, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .welcome-section p { color: #b9bbbe; }
        
        /* Statistics Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; margin-bottom: 50px; }
        .stat-card { background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 30px; padding: 30px; text-align: center; transition: all 0.3s; position: relative; }
        .stat-card:hover { transform: translateY(-5px); background: rgba(255,255,255,0.08); border-color: #6366f1; }
        .stat-number { font-size: 48px; font-weight: 800; background: linear-gradient(135deg, #818cf8, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 10px; }
        .stat-label { color: #b9bbbe; font-size: 14px; }
        .warning-badge { position: absolute; top: 15px; right: 15px; background: #ef4444; color: white; font-size: 11px; padding: 4px 10px; border-radius: 20px; }
        
        /* Menu Grid - Navigation Cards */
        .menu-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 25px; margin-bottom: 50px; }
        .menu-card { background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 30px; padding: 30px; text-align: center; text-decoration: none; transition: all 0.3s; display: block; }
        .menu-card:hover { transform: translateY(-8px); background: rgba(255,255,255,0.1); border-color: #6366f1; }
        .menu-icon { font-size: 48px; margin-bottom: 15px; }
        .menu-card h3 { font-size: 18px; font-weight: 600; color: white; margin-bottom: 8px; }
        .menu-card p { color: #8b8d94; font-size: 12px; }
        
        /* Overdue Books Preview Section */
        .overdue-section { background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 30px; padding: 30px; margin-top: 20px; }
        .overdue-section h3 { font-size: 20px; margin-bottom: 20px; color: #f87171; }
        .overdue-list { display: flex; flex-direction: column; gap: 12px; }
        .overdue-item { background: rgba(255,255,255,0.03); border-radius: 16px; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .overdue-member { font-weight: 600; }
        .overdue-book { font-size: 14px; color: rgba(255,255,255,0.8); }
        .overdue-days { color: #f87171; font-weight: 600; }
        .view-all { text-align: center; margin-top: 20px; }
        .view-all a { color: #818cf8; text-decoration: none; }
        
        /* ============================================= */
        /* SCRUM-25: Popular Books Section Styling       */
        /* Displays top 5 most borrowed books           */
        /* ============================================= */
        .popular-section {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 30px;
            padding: 30px;
            margin-top: 30px;
        }
        .popular-section h3 {
            font-size: 20px;
            margin-bottom: 20px;
            color: #fbbf24;
        }
        .popular-section h3 i {
            margin-right: 10px;
        }
        .popular-table {
            width: 100%;
            border-collapse: collapse;
        }
        .popular-table th,
        .popular-table td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .popular-table th {
            color: #818cf8;
            font-weight: 600;
            font-size: 14px;
        }
        .popular-table tr:hover td {
            background: rgba(255,255,255,0.03);
        }
        .rank-1 {
            color: #fbbf24;
            font-weight: bold;
        }
        .rank-2 {
            color: #a0aec0;
            font-weight: bold;
        }
        .rank-3 {
            color: #cd7f32;
            font-weight: bold;
        }
        .borrow-count {
            background: rgba(99,102,241,0.3);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 13px;
            display: inline-block;
        }
        
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
        .help-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .help-card { background: rgba(255,255,255,0.03); border-radius: 16px; padding: 15px; }
        .help-card strong { display: block; margin-bottom: 8px; }
        .help-card p { font-size: 13px; color: #b9bbbe; line-height: 1.5; }
        
        /* Modal */
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
        
        @media (max-width: 768px) {
            .navbar { flex-direction: column; gap: 15px; padding: 15px 20px; }
            .container { padding: 0 20px; }
            .welcome-section h1 { font-size: 28px; }
            .stats-grid { grid-template-columns: 1fr; }
            .popular-table th, .popular-table td { padding: 10px; font-size: 12px; }
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
                <a href="admin-management.php"><i class="fas fa-users-cog"></i> Admin Users</a>
                <a href="auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1>Welcome, <?php echo htmlspecialchars($name); ?>! 👩‍💼</h1>
            <p>Manage your library efficiently</p>
            <?php if($is_2fa_enabled): ?>
                <p style="margin-top: 10px; color: #34d399;"><i class="fas fa-shield-alt"></i> 2FA Protected Account</p>
            <?php endif; ?>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-number"><?php echo $total_books; ?></div><div class="stat-label"><i class="fas fa-book"></i> Total Books</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $total_members; ?></div><div class="stat-label"><i class="fas fa-users"></i> Total Members</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $active_borrowings; ?></div><div class="stat-label"><i class="fas fa-exchange-alt"></i> Active Borrowings</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $overdue_count; ?></div><div class="stat-label"><i class="fas fa-exclamation-triangle"></i> Overdue Books</div>
            <?php if($overdue_count > 0): ?><div class="warning-badge">⚠️ Action Needed</div><?php endif; ?>
            </div>
        </div>

        <!-- Menu Grid - Navigation Cards -->
        <div class="menu-grid">
            <a href="Books/add-book.php" class="menu-card"><div class="menu-icon">📚</div><h3>Add New Book</h3><p>Add books to catalog</p></a>
            <a href="Books/search-books.php" class="menu-card"><div class="menu-icon">🔍</div><h3>Search Books</h3><p>Find books in catalog</p></a>
            <a href="Member/member-registration.php" class="menu-card"><div class="menu-icon">👥</div><h3>Register Member</h3><p>Add new library members</p></a>
            <a href="Member/member-management.php" class="menu-card"><div class="menu-icon">📊</div><h3>Manage Members</h3><p>View, edit, block members</p></a>
            <a href="overdue-reports.php" class="menu-card"><div class="menu-icon">⚠️</div><h3>Overdue Reports</h3><p>View overdue books & fines</p></a>
            <a href="admin-management.php" class="menu-card"><div class="menu-icon">⚙️</div><h3>Admin Users</h3><p>Manage system admins</p></a>
            <a href="Notification/notifications.php" class="menu-card"><div class="menu-icon">🔔</div><h3>Notifications</h3><p>Send & view notifications</p></a>
        </div>

        <!-- Recent Overdue Books Preview -->
        <div class="overdue-section">
            <h3><i class="fas fa-exclamation-triangle"></i> Recent Overdue Books</h3>
            <div class="overdue-list">
                <?php if($overdue_preview && $overdue_preview->num_rows > 0): ?>
                    <?php while($overdue = $overdue_preview->fetch_assoc()): 
                        $days = (strtotime(date('Y-m-d')) - strtotime($overdue['due_date'])) / (60 * 60 * 24);
                    ?>
                    <div class="overdue-item">
                        <div>
                            <div class="overdue-member"><?php echo htmlspecialchars($overdue['member_name']); ?></div>
                            <div class="overdue-book">"<?php echo htmlspecialchars($overdue['book_title']); ?>"</div>
                        </div>
                        <div>
                            <div class="overdue-days"><?php echo round($days); ?> days overdue</div>
                            <div style="font-size: 12px;">Fine: $<?php echo number_format($days * 0.50, 2); ?></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align:center; padding:20px;">✅ No overdue books! Great job members!</p>
                <?php endif; ?>
            </div>
            <div class="view-all">
                <a href="overdue-reports.php">View All Overdue Reports →</a>
            </div>
        </div>

        <!-- ============================================= -->
        <!-- SCRUM-25: POPULAR BOOKS SECTION               -->
        <!-- Displays top 5 most borrowed books            -->
        <!-- User Story: As a librarian, I want to see     -->
        <!-- popular books on my dashboard                 -->
        <!-- ============================================= -->
        <div class="popular-section">
            <h3><i class="fas fa-chart-line"></i> Popular Books (Most Borrowed)</h3>
            <?php if($popular_books && $popular_books->num_rows > 0): ?>
                <table class="popular-table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Book Title</th>
                            <th>Author</th>
                            <th>Genre</th>
                            <th>Times Borrowed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rank = 1; while($book = $popular_books->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php 
                                if($rank == 1) echo '<span class="rank-1">🥇 ' . $rank . '</span>';
                                elseif($rank == 2) echo '<span class="rank-2">🥈 ' . $rank . '</span>';
                                elseif($rank == 3) echo '<span class="rank-3">🥉 ' . $rank . '</span>';
                                else echo $rank . '.';
                                ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($book['title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($book['author']); ?></td>
                            <td><?php echo htmlspecialchars($book['genre']); ?></td>
                            <td><span class="borrow-count">📖 <?php echo $book['borrow_count']; ?> times</span></td>
                        </tr>
                        <?php $rank++; endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align:center; padding:30px;">
                    <i class="fas fa-chart-simple" style="font-size: 48px; color: #4b5563; margin-bottom: 15px; display: block;"></i>
                    📊 No borrowing data available yet.<br>
                    <small style="color: #8b8d94;">Popular books will appear here once members start borrowing books.</small>
                </p>
            <?php endif; ?>
        </div>

        <!-- ============================================= -->
        <!-- SECURITY: Two-Factor Authentication Settings  -->
        <!-- Allows librarians to enable/disable 2FA      -->
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
                <i class="fas fa-info-circle"></i> When enabled, you'll receive a 6-digit code during login. Check your browser console (F12) for the demo code.
            </p>
        </div>

        <!-- ============================================= -->
        <!-- ETHICS: Transparency - Help Section          -->
        <!-- Explains librarian responsibilities          -->
        <!-- This addresses the Ethics Checklist requirement -->
        <!-- ============================================= -->
        <div class="help-section">
            <h3><i class="fas fa-shield-alt"></i> Librarian Responsibilities</h3>
            <div class="help-grid">
                <div class="help-card">
                    <strong style="color: #34d399;">📚 Managing Books</strong>
                    <p>Librarians can add, edit, and delete books from the catalog. You can also search and filter books.</p>
                </div>
                <div class="help-card">
                    <strong style="color: #f87171;">👥 Member Management</strong>
                    <p>Register new members, block/unblock accounts, and manage member information.</p>
                </div>
                <div class="help-card">
                    <strong style="color: #fbbf24;">📊 Overdue Reports</strong>
                    <p>View all overdue books with member details and calculated fines. You can override fines if needed.</p>
                </div>
                <div class="help-card">
                    <strong style="color: #818cf8;">⚖️ Accountability</strong>
                    <p>All fines are calculated automatically at $0.50/day. Librarians can review and address member concerns.</p>
                </div>
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
                <input type="tel" name="phone" placeholder="Phone Number" value="<?php echo htmlspecialchars($librarian_phone); ?>" required>
                <button type="submit" name="update_profile">Save Changes</button>
            </form>
        </div>
    </div>

    <div class="footer">
        <p>© 2026 LibTech Solutions | Secure Library Management System | Built with <i class="fas fa-heart" style="color:#ec4899;"></i> by DMU Students</p>
    </div>

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
    </script>
</body>
</html>