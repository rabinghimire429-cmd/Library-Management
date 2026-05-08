 <?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

require_once 'config.php';

// Only Librarian can access this page
if($_SESSION['admin_role'] !== 'Librarian') {
    header('Location: member-dashboard.php');
    exit();
}

// Get statistics
$total_books = $conn->query("SELECT COUNT(*) as count FROM book")->fetch_assoc()['count'];
$total_members = $conn->query("SELECT COUNT(*) as count FROM member")->fetch_assoc()['count'];
$active_borrowings = $conn->query("SELECT COUNT(*) as count FROM transaction WHERE return_date IS NULL")->fetch_assoc()['count'];
$overdue_count = $conn->query("SELECT COUNT(*) as count FROM transaction WHERE return_date IS NULL AND due_date < CURDATE()")->fetch_assoc()['count'];
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
        *{margin:0;padding:0;box-sizing:border-box}body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#0f0c29,#302b63,#24243e);min-height:100vh}.navbar{background:rgba(15,23,42,0.95);backdrop-filter:blur(12px);padding:16px 40px;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:100;border-bottom:1px solid rgba(255,255,255,0.1)}.logo{font-size:24px;font-weight:800;background:linear-gradient(135deg,#818cf8,#ec4899);-webkit-background-clip:text;-webkit-text-fill-color:transparent}.logout-btn{background:rgba(239,68,68,0.2);border:1px solid rgba(239,68,68,0.3);color:#f87171;padding:8px 20px;border-radius:30px;cursor:pointer}.logout-btn:hover{background:rgba(239,68,68,0.3);transform:translateY(-2px)}.container{max-width:1400px;margin:40px auto;padding:0 40px}.welcome-section{background:linear-gradient(135deg,rgba(99,102,241,0.15),rgba(236,72,153,0.15));border-radius:40px;padding:45px;margin-bottom:40px;text-align:center;border:1px solid rgba(255,255,255,0.1)}.welcome-section h1{font-size:36px;font-weight:800;background:linear-gradient(135deg,#fff,#818cf8);-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:10px}.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:25px;margin-bottom:50px}.stat-card{background:rgba(255,255,255,0.05);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.1);border-radius:30px;padding:30px;text-align:center;transition:all 0.3s;position:relative}.stat-card:hover{transform:translateY(-5px);background:rgba(255,255,255,0.08);border-color:#6366f1}.stat-number{font-size:48px;font-weight:800;background:linear-gradient(135deg,#818cf8,#ec4899);-webkit-background-clip:text;-webkit-text-fill-color:transparent;margin-bottom:10px}.stat-label{color:rgba(255,255,255,0.6);font-size:14px}.warning-badge{position:absolute;top:15px;right:15px;background:#ef4444;color:white;font-size:11px;padding:4px 10px;border-radius:20px}.menu-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:25px;margin-bottom:50px}.menu-card{background:rgba(255,255,255,0.05);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.1);border-radius:30px;padding:30px;text-align:center;text-decoration:none;transition:all 0.3s;display:block}.menu-card:hover{transform:translateY(-8px);background:rgba(255,255,255,0.1);border-color:#6366f1}.menu-icon{font-size:48px;margin-bottom:15px}.menu-card h3{font-size:18px;font-weight:600;color:white;margin-bottom:8px}.menu-card p{font-size:12px;color:rgba(255,255,255,0.5)}.overdue-section{background:rgba(255,255,255,0.05);backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,0.1);border-radius:30px;padding:30px;margin-top:20px}.overdue-section h3{font-size:20px;margin-bottom:20px;color:#f87171}.overdue-list{display:flex;flex-direction:column;gap:12px}.overdue-item{background:rgba(255,255,255,0.03);border-radius:16px;padding:15px 20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px}.overdue-member{font-weight:600}.overdue-book{font-size:14px;color:rgba(255,255,255,0.8)}.overdue-days{color:#f87171;font-weight:600}.view-all{text-align:center;margin-top:20px}.view-all a{color:#818cf8;text-decoration:none}.footer{text-align:center;padding:30px;color:rgba(255,255,255,0.4);font-size:12px;margin-top:40px}@media(max-width:768px){.navbar{flex-direction:column;gap:15px;padding:15px 20px}.container{padding:0 20px}.welcome-section{padding:30px}.welcome-section h1{font-size:28px}.stats-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
    <div class="navbar"><div class="logo"><i class="fas fa-book-open"></i> LibTech Solutions</div><button class="logout-btn" onclick="window.location.href='auth/logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</button></div>
    <div class="container"><div class="welcome-section"><h1>Welcome, Librarian! 👩‍💼</h1><p>Manage your library efficiently from one dashboard</p></div>
    <div class="stats-grid"><div class="stat-card"><div class="stat-number"><?php echo $total_books; ?></div><div class="stat-label"><i class="fas fa-book"></i> Total Books</div></div><div class="stat-card"><div class="stat-number"><?php echo $total_members; ?></div><div class="stat-label"><i class="fas fa-users"></i> Total Members</div></div><div class="stat-card"><div class="stat-number"><?php echo $active_borrowings; ?></div><div class="stat-label"><i class="fas fa-exchange-alt"></i> Active Borrowings</div></div><div class="stat-card"><div class="stat-number"><?php echo $overdue_count; ?></div><div class="stat-label"><i class="fas fa-exclamation-triangle"></i> Overdue Books</div><?php if($overdue_count>0): ?><div class="warning-badge">⚠️ Action Needed</div><?php endif; ?></div></div>
<div class="menu-grid">

    <!-- Add New Book -->
    <a href="book/add-book.php" class="menu-card">
        <div class="menu-icon">📚</div>
        <h3>Add New Book</h3>
        <p>Add books to catalog</p>
    </a>

    <!-- Search Books -->
    <a href="book/search-books.php" class="menu-card">
        <div class="menu-icon">🔍</div>
        <h3>Search Books</h3>
        <p>Find books in catalog</p>
    </a>

    <!-- Register Member -->
    <a href="Member/member-registration.php" class="menu-card">
        <div class="menu-icon">👥</div>
        <h3>Register Member</h3>
        <p>Add new library members</p>
    </a>

    <!-- Manage Members -->
    <a href="Member/member-management.php" class="menu-card">
        <div class="menu-icon">📊</div>
        <h3>Manage Members</h3>
        <p>View, edit, block members</p>
    </a>

    <!-- Overdue Reports -->
    <a href="overdue-reports.php" class="menu-card">
        <div class="menu-icon">⚠️</div>
        <h3>Overdue Reports</h3>
        <p>View overdue books & fines</p>
    </a>

    <!-- Notifications -->
    <a href="Notification/notifications.php" class="menu-card">
        <div class="menu-icon">🔔</div>
        <h3>Notifications</h3>
        <p>Send & view notifications</p>
    </a>

</div>
    <div class="overdue-section" id="overduePreview"><h3><i class="fas fa-exclamation-triangle"></i> Recent Overdue Books</h3><div class="overdue-list" id="overdueList"><p style="text-align:center; padding:20px;">Loading overdue books...</p></div><div class="view-all"><a href="overdue-reports.php">View All Overdue Reports →</a></div></div></div>
    <div class="footer"><p>© 2026 LibTech Solutions | Secure Library Management System</p></div>
    <script>
        fetch('api/get-overdue-reports.php').then(r=>r.json()).then(data=>{const list=document.getElementById('overdueList');if(data.success&&data.overdue_books.length>0){let html='';const preview=data.overdue_books.slice(0,5);preview.forEach(book=>{html+=`<div class="overdue-item"><div><div class="overdue-member">${book.member_name}</div><div class="overdue-book">"${book.book_title}" by ${book.book_author}</div></div><div><div class="overdue-days">${book.days_overdue} days overdue</div><div style="font-size:12px;">Fine: $${parseFloat(book.fine_amount).toFixed(2)}</div></div></div>`;});list.innerHTML=html;}else{list.innerHTML='<p style="text-align:center; padding:20px;">✅ No overdue books! Great job members!</p>';}}).catch(error=>{document.getElementById('overdueList').innerHTML='<p style="text-align:center; padding:20px;">❌ Error loading overdue books.</p>';});
    </script>
</body>
</html>