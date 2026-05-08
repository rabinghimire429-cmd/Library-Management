<?php

require_once 'config.php';

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
        *{
            margin:0;
            padding:0;
            box-sizing:border-box
        }

        body{
            font-family:'Inter',sans-serif;
            background:linear-gradient(135deg,#0f0c29,#302b63,#24243e);
            min-height:100vh
        }

        .navbar{
            background:rgba(15,23,42,0.95);
            backdrop-filter:blur(12px);
            padding:16px 40px;
            display:flex;
            justify-content:space-between;
            align-items:center;
            position:sticky;
            top:0;
            z-index:100;
            border-bottom:1px solid rgba(255,255,255,0.1)
        }

        .logo{
            font-size:24px;
            font-weight:800;
            background:linear-gradient(135deg,#818cf8,#ec4899);
            -webkit-background-clip:text;
            -webkit-text-fill-color:transparent
        }

        .logout-btn{
            background:rgba(239,68,68,0.2);
            border:1px solid rgba(239,68,68,0.3);
            color:#f87171;
            padding:8px 20px;
            border-radius:30px;
            cursor:pointer
        }

        .container{
            max-width:1400px;
            margin:40px auto;
            padding:0 40px
        }

        .welcome-section{
            background:linear-gradient(135deg,rgba(99,102,241,0.15),rgba(236,72,153,0.15));
            border-radius:40px;
            padding:45px;
            margin-bottom:40px;
            text-align:center;
            border:1px solid rgba(255,255,255,0.1)
        }

        .welcome-section h1{
            font-size:36px;
            font-weight:800;
            background:linear-gradient(135deg,#fff,#818cf8);
            -webkit-background-clip:text;
            -webkit-text-fill-color:transparent;
            margin-bottom:10px
        }

        .stats-grid{
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
            gap:25px;
            margin-bottom:50px
        }

        .stat-card{
            background:rgba(255,255,255,0.05);
            backdrop-filter:blur(10px);
            border:1px solid rgba(255,255,255,0.1);
            border-radius:30px;
            padding:30px;
            text-align:center
        }

        .stat-number{
            font-size:48px;
            font-weight:800;
            background:linear-gradient(135deg,#818cf8,#ec4899);
            -webkit-background-clip:text;
            -webkit-text-fill-color:transparent;
            margin-bottom:10px
        }

        .stat-label{
            color:rgba(255,255,255,0.6);
            font-size:14px
        }

        .menu-grid{
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
            gap:25px;
            margin-bottom:50px
        }

        .menu-card{
            background:rgba(255,255,255,0.05);
            backdrop-filter:blur(10px);
            border:1px solid rgba(255,255,255,0.1);
            border-radius:30px;
            padding:30px;
            text-align:center;
            text-decoration:none;
            transition:all 0.3s;
            display:block
        }

        .menu-card:hover{
            transform:translateY(-8px);
            background:rgba(255,255,255,0.1)
        }

        .menu-icon{
            font-size:48px;
            margin-bottom:15px
        }

        .menu-card h3{
            font-size:18px;
            font-weight:600;
            color:white;
            margin-bottom:8px
        }

        .menu-card p{
            font-size:12px;
            color:rgba(255,255,255,0.5)
        }

        .footer{
            text-align:center;
            padding:30px;
            color:rgba(255,255,255,0.4);
            font-size:12px;
            margin-top:40px
        }
    </style>
</head>

<body>

<div class="navbar">
    <div class="logo">
        <i class="fas fa-book-open"></i> LibTech Solutions
    </div>

    <button class="logout-btn" onclick="window.location.href='auth/logout.php'">
        <i class="fas fa-sign-out-alt"></i> Logout
    </button>
</div>

<div class="container">

    <div class="welcome-section">
        <h1>Welcome, Librarian! 👩‍💼</h1>
        <p>Manage your library efficiently from one dashboard</p>
    </div>

    <div class="stats-grid">

        <div class="stat-card">
            <div class="stat-number"><?php echo $total_books; ?></div>
            <div class="stat-label">Total Books</div>
        </div>

        <div class="stat-card">
            <div class="stat-number"><?php echo $total_members; ?></div>
            <div class="stat-label">Total Members</div>
        </div>

        <div class="stat-card">
            <div class="stat-number"><?php echo $active_borrowings; ?></div>
            <div class="stat-label">Active Borrowings</div>
        </div>

        <div class="stat-card">
            <div class="stat-number"><?php echo $overdue_count; ?></div>
            <div class="stat-label">Overdue Books</div>
        </div>

    </div>

    <div class="menu-grid">

        <a href="book/add-book.php" class="menu-card">
            <div class="menu-icon">📚</div>
            <h3>Add New Book</h3>
            <p>Add books to catalog</p>
        </a>

        <a href="book/search-books.php" class="menu-card">
            <div class="menu-icon">🔍</div>
            <h3>Search Books</h3>
            <p>Find books in catalog</p>
        </a>

        <a href="Member/member-registration.php" class="menu-card">
            <div class="menu-icon">👥</div>
            <h3>Register Member</h3>
            <p>Add new library members</p>
        </a>

        <a href="Member/member-management.php" class="menu-card">
            <div class="menu-icon">📊</div>
            <h3>Manage Members</h3>
            <p>View and manage members</p>
        </a>

        <a href="overdue-reports.php" class="menu-card">
            <div class="menu-icon">⚠️</div>
            <h3>Overdue Reports</h3>
            <p>View overdue books and fines</p>
        </a>

        <a href="Notification/notifications.php" class="menu-card">
            <div class="menu-icon">🔔</div>
            <h3>Notifications</h3>
            <p>Send and view notifications</p>
        </a>

    </div>

</div>

<div class="footer">
    <p>© 2026 LibTech Solutions | Secure Library Management System</p>
</div>

</body>
</html>