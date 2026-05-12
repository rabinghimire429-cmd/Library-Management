<?php
/**
 * overdue-reports.php - Overdue Reports Page
 * Author: Rabin Ghimire
 * Module: Authentication & Dashboard
 * 
 * FUNCTIONALITY: List, Find (Search), Filter
 * Shows all overdue books with member details and calculated fines
 */

// =============================================
// SESSION CHECK - Only Librarian can access
// =============================================
session_start();
if(!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'Librarian') {
    header('Location: index.php');
    exit();
}

// =============================================
// DATABASE CONNECTION
// =============================================
require_once 'config.php';

// =============================================
// FUNCTIONALITY: FIND (SEARCH)
// =============================================
$search = $_GET['search'] ?? '';

// =============================================
// FUNCTIONALITY: LIST - Query for overdue books
// =============================================
$query = "SELECT 
            m.member_id,
            m.full_name as member_name,
            m.email as member_email,
            m.phone as member_phone,
            b.title as book_title,
            b.author as book_author,
            t.borrow_date,
            t.due_date,
            DATEDIFF(CURDATE(), t.due_date) as days_overdue,
            DATEDIFF(CURDATE(), t.due_date) * 0.50 as fine_amount
          FROM transaction t
          JOIN member m ON t.member_id = m.member_id
          JOIN book b ON t.book_id = b.book_id
          WHERE t.return_date IS NULL AND t.due_date < CURDATE()";

// Add search condition if search term exists
if($search) {
    $query .= " AND (m.full_name LIKE '%$search%' OR m.email LIKE '%$search%' OR b.title LIKE '%$search%')";
}

// Order by most overdue first
$query .= " ORDER BY t.due_date ASC";

$result = $conn->query($query);

// Calculate total statistics
$total_overdue = 0;
$total_fines = 0;
if($result && $result->num_rows > 0) {
    $total_overdue = $result->num_rows;
    $result->data_seek(0);
    while($row = $result->fetch_assoc()) {
        $total_fines += $row['fine_amount'];
    }
    $result->data_seek(0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overdue Reports - LibTech Solutions</title>
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
        
        /* Navigation Bar */
        .navbar {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(12px);
            padding: 16px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .logo {
            font-size: 24px;
            font-weight: 800;
            background: linear-gradient(135deg, #818cf8, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .logout-btn {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
            padding: 8px 20px;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.3);
            transform: translateY(-2px);
        }
        
        /* Main Container */
        .container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 40px;
        }
        
        /* Back Link */
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #818cf8;
            text-decoration: none;
            transition: all 0.3s;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        
        /* Page Title */
        .page-title {
            margin-bottom: 30px;
        }
        .page-title h1 {
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, #fff, #f87171);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }
        .page-title p {
            color: #8b8d94;
            font-size: 14px;
        }
        
        /* Statistics Cards */
        .stats-cards {
            display: flex;
            gap: 25px;
            margin-bottom: 35px;
            flex-wrap: wrap;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 25px 30px;
            flex: 1;
            min-width: 200px;
            text-align: center;
            transition: all 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            background: rgba(255, 255, 255, 0.08);
            border-color: #6366f1;
        }
        .stat-number {
            font-size: 42px;
            font-weight: 800;
            background: linear-gradient(135deg, #818cf8, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }
        .stat-card.overdue .stat-number {
            background: linear-gradient(135deg, #f87171, #dc2626);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stat-label {
            color: #b9bbbe;
            font-size: 14px;
        }
        
        /* Search Bar */
        .search-bar {
            margin-bottom: 30px;
        }
        .search-form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .search-input {
            flex: 1;
            padding: 14px 20px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 40px;
            color: #e4e6eb;
            font-size: 14px;
            transition: all 0.3s;
        }
        .search-input:focus {
            outline: none;
            border-color: #6366f1;
            background: rgba(255, 255, 255, 0.12);
        }
        .search-btn {
            padding: 14px 30px;
            background: linear-gradient(135deg, #6366f1, #ec4899);
            border: none;
            border-radius: 40px;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.4);
        }
        
        /* Table */
        .table-container {
            overflow-x: auto;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 16px 20px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        th {
            background: rgba(99, 102, 241, 0.2);
            color: #818cf8;
            font-weight: 600;
            font-size: 14px;
        }
        tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }
        .overdue-badge {
            color: #f87171;
            font-weight: 600;
        }
        .fine-amount {
            color: #34d399;
            font-weight: 600;
        }
        .no-data {
            text-align: center;
            padding: 60px;
            color: #8b8d94;
            font-size: 16px;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            padding: 30px;
            color: #8b8d94;
            font-size: 12px;
            margin-top: 40px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 15px;
                padding: 15px 20px;
            }
            .container {
                padding: 0 20px;
            }
            .stats-cards {
                flex-direction: column;
            }
            th, td {
                padding: 12px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="logo"><i class="fas fa-book-open"></i> LibTech Solutions</div>
        <button class="logout-btn" onclick="window.location.href='auth/logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</button>
    </div>

    <div class="container">
        <!-- Back Link -->
        <a href="librarian-dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

        <!-- Page Title -->
        <div class="page-title">
            <h1><i class="fas fa-exclamation-triangle"></i> Overdue Reports</h1>
            
        </div>

        <!-- Statistics Cards -->
        <div class="stats-cards">
            <div class="stat-card overdue">
                <div class="stat-number"><?php echo $total_overdue; ?></div>
                <div class="stat-label"><i class="fas fa-book"></i> Overdue Books</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">$<?php echo number_format($total_fines, 2); ?></div>
                <div class="stat-label"><i class="fas fa-coins"></i> Total Fines</div>
            </div>
        </div>

        <!-- FUNCTIONALITY: FIND (SEARCH) - Search Bar -->
        <div class="search-bar">
            <form method="GET" class="search-form">
                <input type="text" name="search" class="search-input" placeholder="Search by member name, email, or book title..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
                <?php if($search): ?>
                    <a href="overdue-reports.php" class="search-btn" style="background: #2d3139;">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- FUNCTIONALITY: LIST - Table of Overdue Books -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Contact</th>
                        <th>Book</th>
                        <th>Borrow Date</th>
                        <th>Due Date</th>
                        <th>Days Overdue</th>
                        <th>Fine</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['member_name']); ?></strong><br>
                                    <small style="color:#8b8d94;">ID: <?php echo $row['member_id']; ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($row['member_email']); ?><br>
                                    <small style="color:#8b8d94;"><?php echo htmlspecialchars($row['member_phone']); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['book_title']); ?></strong><br>
                                    <small style="color:#8b8d94;">by <?php echo htmlspecialchars($row['book_author']); ?></small>
                                </td>
                                <td><?php echo $row['borrow_date']; ?></td>
                                <td class="overdue-badge"><?php echo $row['due_date']; ?></td>
                                <td><?php echo $row['days_overdue']; ?> days</td>
                                <td class="fine-amount">$<?php echo number_format($row['fine_amount'], 2); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="no-data">
                                <i class="fas fa-check-circle" style="color: #34d399; font-size: 48px; margin-bottom: 15px; display: block;"></i>
                                ✅ No overdue books! Great job members!
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="footer">
        <p>© 2026 LibTech Solutions | Secure Library Management System</p>
    </div>
</body>
</html>