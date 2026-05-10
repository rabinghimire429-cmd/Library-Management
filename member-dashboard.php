<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

require_once 'config.php';

$admin_id = $_SESSION['admin_id'];
$admin_email = $_SESSION['admin_email'];

// Get member details
$member_query = $conn->query("SELECT * FROM member WHERE admin_id = $admin_id");
$member = $member_query->fetch_assoc();

// Fix name: if full_name is empty, use email username
if($member && !empty($member['full_name']) && $member['full_name'] != $member['email']) {
    $name = $member['full_name'];
} else {
    $email_parts = explode('@', $admin_email);
    $name = ucfirst($email_parts[0]);
}

$member_id = $member['member_id'] ?? 0;
$member_phone = $member['phone'] ?? '';
$member_email = $member['email'] ?? $admin_email;

// Get borrowed books count
$borrowed_count = $conn->query("SELECT COUNT(*) as count FROM transaction WHERE member_id = $member_id AND return_date IS NULL")->fetch_assoc()['count'];

// Get total fines
$total_fines = $conn->query("SELECT SUM(fine_amount) as total FROM transaction WHERE member_id = $member_id AND fine_paid = 0")->fetch_assoc()['total'] ?? 0;

// Get unread notifications count
$notif_count = $conn->query("SELECT COUNT(*) as count FROM notification WHERE member_id = $member_id AND read_status = 0")->fetch_assoc()['count'];

// Update profile
$update_msg = '';
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = $_POST['full_name'];
    $phone = $_POST['phone'];
    $conn->query("UPDATE member SET full_name = '$full_name', phone = '$phone' WHERE member_id = $member_id");
    $update_msg = "<div class='success-msg'>✅ Profile updated successfully!</div>";
    // Refresh name
    $name = $full_name;
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
        }
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
            color: white;
            text-decoration: none;
            transition: all 0.3s;
        }
        .dropdown-content a:hover {
            background: rgba(99,102,241,0.3);
        }
        .logout-btn {
            color: #f87171 !important;
        }
        
        .container { max-width: 1400px; margin: 40px auto; padding: 0 40px; }
        .welcome-section {
            background: linear-gradient(135deg, rgba(99,102,241,0.15), rgba(236,72,153,0.15));
            border-radius: 40px;
            padding: 50px;
            margin-bottom: 40px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .welcome-section h1 { font-size: 42px; font-weight: 800; background: linear-gradient(135deg, #fff, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 10px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; margin-bottom: 50px; }
        .stat-card { background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 30px; padding: 30px; text-align: center; transition: all 0.3s; }
        .stat-card:hover { transform: translateY(-5px); background: rgba(255,255,255,0.08); border-color: #6366f1; }
        .stat-number { font-size: 48px; font-weight: 800; background: linear-gradient(135deg, #818cf8, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 10px; }
        .menu-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 25px; margin-bottom: 40px; }
        .menu-card { background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 30px; padding: 30px; text-align: center; text-decoration: none; transition: all 0.3s; display: block; }
        .menu-card:hover { transform: translateY(-8px); background: rgba(255,255,255,0.1); border-color: #6366f1; }
        .menu-icon { font-size: 50px; margin-bottom: 15px; }
        .menu-card h3 { font-size: 18px; font-weight: 600; color: white; margin-bottom: 8px; }
        .borrowed-section { background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1); border-radius: 30px; padding: 30px; margin-top: 40px; }
        .borrowed-section h3 { font-size: 20px; margin-bottom: 20px; color: #818cf8; }
        .book-list { display: flex; flex-direction: column; gap: 15px; }
        .book-item { background: rgba(255,255,255,0.03); border-radius: 20px; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .book-title { font-weight: 600; color: white; }
        .due-date { font-size: 13px; color: rgba(255,255,255,0.6); }
        .overdue { color: #f87171; }
        .btn-pay { background: #10b981; color: white; padding: 8px 16px; border: none; border-radius: 8px; cursor: pointer; }
        .footer { text-align: center; padding: 30px; color: rgba(255,255,255,0.4); font-size: 12px; margin-top: 40px; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: rgba(20,20,50,0.98); border-radius: 30px; padding: 30px; width: 450px; max-width: 90%; border: 1px solid rgba(255,255,255,0.2); }
        .modal-content h3 { margin-bottom: 20px; color: #818cf8; }
        .modal-content input { width: 100%; padding: 12px; margin: 10px 0; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 12px; color: white; }
        .modal-content button { width: 100%; padding: 12px; background: linear-gradient(135deg, #6366f1, #ec4899); border: none; border-radius: 12px; color: white; cursor: pointer; }
        .close-btn { float: right; font-size: 24px; cursor: pointer; color: #888; }
        .success-msg { background: rgba(16,185,129,0.2); color: #34d399; padding: 12px; border-radius: 12px; margin-bottom: 20px; text-align: center; }
        @media (max-width: 768px) { .navbar { flex-direction: column; gap: 15px; padding: 15px 20px; } .container { padding: 0 20px; } .welcome-section h1 { font-size: 28px; } }
    </style>
</head>
<body>
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
                <a href="my-fines.php"><i class="fas fa-coins"></i> My Fines</a>
                <a href="my-borrowings.php"><i class="fas fa-book"></i> My Borrowings</a>
                <a href="auth/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="welcome-section">
            <h1>Welcome back, <?php echo htmlspecialchars($name); ?>! 👋</h1>
            <p>Your library journey continues here.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card"><div class="stat-number"><?php echo $borrowed_count; ?></div><div class="stat-label"><i class="fas fa-book"></i> Books Borrowed</div></div>
            <div class="stat-card"><div class="stat-number">$<?php echo number_format($total_fines, 2); ?></div><div class="stat-label"><i class="fas fa-coins"></i> Pending Fines</div></div>
            <div class="stat-card"><div class="stat-number"><?php echo $notif_count; ?></div><div class="stat-label"><i class="fas fa-bell"></i> Unread Notifications</div></div>
        </div>

        <div class="menu-grid">
            <a href="Books/search-books.php" class="menu-card"><div class="menu-icon">🔍</div><h3>Search Books</h3><p>Find your next read</p></a>
            <a href="B/borrow-book.php" class="menu-card"><div class="menu-icon">📚</div><h3>Borrow a Book</h3><p>Checkout books</p></a>
            <a href="B/my-borrowings.php" class="menu-card"><div class="menu-icon">📋</div><h3>My Borrowings</h3><p>Track your books</p></a>
            <a href="B/my-fines.php" class="menu-card"><div class="menu-icon">💰</div><h3>My Fines</h3><p>View & pay fines</p></a>
            <a href="Notification/notifications.php" class="menu-card"><div class="menu-icon">🔔</div><h3>Notifications</h3><p>Stay updated</p></a>
        </div>

        <div class="borrowed-section" id="borrowedBooks">
            <h3><i class="fas fa-book-open"></i> Currently Borrowed Books</h3>
            <div class="book-list" id="bookList"><p style="text-align:center; padding:20px;">Loading your borrowed books...</p></div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeEditProfileModal()">&times;</span>
            <h3>✏️ Edit Profile</h3>
            <form method="POST">
                <input type="text" name="full_name" placeholder="Full Name" value="<?php echo htmlspecialchars($name); ?>" required>
                <input type="tel" name="phone" placeholder="Phone Number" value="<?php echo htmlspecialchars($member_phone); ?>" required>
                <button type="submit" name="update_profile">Save Changes</button>
            </form>
        </div>
    </div>

    <div class="footer"><p>© 2026 LibTech Solutions | Secure Library Management System</p></div>

    <script>
        function openEditProfileModal() { document.getElementById('editProfileModal').classList.add('active'); }
        function closeEditProfileModal() { document.getElementById('editProfileModal').classList.remove('active'); }
        window.onclick = function(e) { if(e.target.classList.contains('modal')) e.target.classList.remove('active'); }
        
        fetch('api/get-borrowed-books.php').then(r=>r.json()).then(data=>{
            const list = document.getElementById('bookList');
            if(data.success && data.books.length > 0) {
                let html = '';
                data.books.forEach(book => {
                    const isOverdue = book.status === 'Overdue';
                    html += `<div class="book-item"><div><div class="book-title">${book.title}</div><div class="due-date">by ${book.author}</div></div><div><div class="due-date">Borrowed: ${book.borrow_date}</div><div class="due-date ${isOverdue ? 'overdue' : ''}">Due: ${book.due_date}</div>${isOverdue ? `<div class="overdue">⚠️ Overdue by ${book.days_overdue} days</div>` : ''}${book.fine_amount > 0 ? `<div class="overdue">Fine: $${parseFloat(book.fine_amount).toFixed(2)}</div>` : ''}</div></div>`;
                });
                list.innerHTML = html;
            } else { list.innerHTML = '<p style="text-align:center; padding:20px;">📭 You have no borrowed books.</p>'; }
        }).catch(()=>{ document.getElementById('bookList').innerHTML = '<p style="text-align:center; padding:20px;">❌ Error loading borrowed books.</p>'; });
    </script>
</body>
</html>