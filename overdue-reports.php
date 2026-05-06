<?php
session_start();
if(!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'Librarian') {
    header('Location: index.php');
    exit();
}

require_once 'config.php';

$search = $_GET['search'] ?? '';
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

if($search) {
    $query .= " AND (m.full_name LIKE '%$search%' OR m.email LIKE '%$search%' OR b.title LIKE '%$search%')";
}
$query .= " ORDER BY t.due_date ASC";

$result = $conn->query($query);

$total_fines = 0;
$temp_result = $conn->query($query);
while($row = $temp_result->fetch_assoc()) {
    $total_fines += $row['fine_amount'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Overdue Reports - LibTech Solutions</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#0f0c29,#302b63,#24243e);color:white}.navbar{background:rgba(15,23,42,0.95);padding:16px 40px;display:flex;justify-content:space-between;align-items:center}.logo{font-size:24px;font-weight:800;background:linear-gradient(135deg,#818cf8,#ec4899);-webkit-background-clip:text;-webkit-text-fill-color:transparent}.logout-btn{background:rgba(239,68,68,0.2);border:1px solid rgba(239,68,68,0.3);color:#f87171;padding:8px 20px;border-radius:30px;cursor:pointer}.container{max-width:1400px;margin:40px auto;padding:20px}.stats-cards{display:flex;gap:20px;margin-bottom:30px;flex-wrap:wrap}.stat-card{background:rgba(255,255,255,0.05);border-radius:20px;padding:20px;flex:1;min-width:180px;text-align:center}.stat-number{font-size:32px;font-weight:800;margin-bottom:5px}.stat-card.overdue .stat-number{color:#f87171}.filter-bar{display:flex;gap:15px;margin-bottom:25px;flex-wrap:wrap}.search-box{flex:1}.search-box input{width:100%;padding:12px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);border-radius:40px;color:white}.table-container{overflow-x:auto;background:rgba(255,255,255,0.03);border-radius:24px;border:1px solid rgba(255,255,255,0.1)}table{width:100%;border-collapse:collapse}th,td{padding:16px;text-align:left;border-bottom:1px solid rgba(255,255,255,0.05)}th{background:rgba(99,102,241,0.2);color:#818cf8}.overdue-badge{color:#f87171;font-weight:600}.no-data{text-align:center;padding:60px;color:rgba(255,255,255,0.5)}.footer{text-align:center;padding:20px;color:rgba(255,255,255,0.4)}@media(max-width:768px){.navbar{flex-direction:column;gap:15px;padding:15px 20px}th,td{padding:12px;font-size:12px}}
    </style>
</head>
<body>
    <div class="navbar"><div class="logo"><i class="fas fa-book"></i> LibTech Solutions</div><button class="logout-btn" onclick="window.location.href='auth/logout.php'">Logout</button></div>
    <div class="container">
        <div class="stats-cards"><div class="stat-card overdue"><div class="stat-number" id="totalOverdue">0</div><div>Overdue Books</div></div><div class="stat-card"><div class="stat-number" id="totalFines">$0</div><div>Total Fines</div></div></div>
        <div class="filter-bar"><div class="search-box"><form method="GET"><input type="text" name="search" placeholder="Search by member name, email, or book title..." value="<?php echo htmlspecialchars($search); ?>"><button type="submit" style="margin-left:10px;padding:12px 20px;background:#6366f1;border:none;border-radius:40px;color:white;cursor:pointer;">Search</button></form></div></div>
        <div class="table-container"><table><thead><tr><th>Member</th><th>Contact</th><th>Book</th><th>Borrow Date</th><th>Due Date</th><th>Days Overdue</th><th>Fine</th></tr></thead><tbody id="overdueTable"><?php if($result->num_rows>0): ?><?php while($row=$result->fetch_assoc()): ?><tr><td><strong><?php echo htmlspecialchars($row['member_name']); ?></strong><br><small><?php echo $row['member_id']; ?></small></td><td><?php echo htmlspecialchars($row['member_email']); ?><br><small><?php echo htmlspecialchars($row['member_phone']); ?></small></td><td><strong><?php echo htmlspecialchars($row['book_title']); ?></strong><br><small><?php echo htmlspecialchars($row['book_author']); ?></small></td><td><?php echo $row['borrow_date']; ?></td><td class="overdue-badge"><?php echo $row['due_date']; ?></td><td><?php echo $row['days_overdue']; ?> days</td><td class="overdue-badge">$<?php echo number_format($row['fine_amount'],2); ?></td></tr><?php endwhile; ?><?php else: ?><tr><td colspan="7" class="no-data">✅ No overdue books! Great job members!</td></tr><?php endif; ?></tbody></table></div>
    </div>
    <div class="footer"><p>© 2026 LibTech Solutions</p></div>
    <script>
        <?php
        $result->data_seek(0);
        $total_overdue = $result->num_rows;
        $total_fines_sum = 0;
        while($row = $result->fetch_assoc()) { $total_fines_sum += $row['fine_amount']; }
        ?>
        document.getElementById('totalOverdue').innerText = '<?php echo $total_overdue; ?>';
        document.getElementById('totalFines').innerText = '$<?php echo number_format($total_fines_sum,2); ?>';
    </script>
</body>
</html>