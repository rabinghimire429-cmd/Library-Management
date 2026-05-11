<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'libtech_db');
$admin_id = $_SESSION['admin_id'];
$member_result = $conn->query("SELECT member_id FROM member WHERE admin_id = $admin_id");
$member = $member_result->fetch_assoc();
$member_id = $member['member_id'] ?? 0;

if(isset($_GET['return'])) {
    $trans_id = $_GET['return'];
    $return_date = date('Y-m-d');
    $trans = $conn->query("SELECT * FROM transaction WHERE transaction_id = $trans_id")->fetch_assoc();
    $fine = 0;
    if($return_date > $trans['due_date']) {
        $days = (strtotime($return_date) - strtotime($trans['due_date'])) / (60*60*24);
        $fine = $days * 0.50;
    }
    $conn->query("UPDATE transaction SET return_date = '$return_date', fine_amount = $fine, status = 'Returned' WHERE transaction_id = $trans_id");
    $conn->query("UPDATE book SET available_copies = available_copies + 1 WHERE book_id = " . $trans['book_id']);
    header('Location: my-borrowings.php');
    exit();
}

$transactions = $conn->query("SELECT t.*, b.title FROM transaction t JOIN book b ON t.book_id = b.book_id WHERE t.member_id = $member_id ORDER BY t.borrow_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Borrowings - LibTech Solutions</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #0a0a2a; color: #e4e6eb; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 40px auto; padding: 20px; background: #1c1e26; border-radius: 20px; }
        a { color: #818cf8; text-decoration: none; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #2d3139; }
        th { background: rgba(99,102,241,0.3); color: #818cf8; }
        .overdue { color: #f87171; }
        .btn-return { background: #10b981; color: white; padding: 5px 12px; border-radius: 8px; text-decoration: none; }
        .back-link { display: inline-block; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <!-- FIXED: Added ../ to go back to root folder -->
        <a href="../member-dashboard.php" class="back-link">← Back to Dashboard</a>
        <h2>📋 My Borrowings</h2>
        <table>
            <thead>
                <tr><th>Book</th><th>Borrow Date</th><th>Due Date</th><th>Return Date</th><th>Fine</th><th>Status</th><th>Action</th>
            </thead>
            <tbody>
                <?php while($t = $transactions->fetch_assoc()): 
                    $overdue = ($t['return_date'] == null && $t['due_date'] < date('Y-m-d'));
                ?>
                <tr>
                    <td><?php echo $t['title']; ?></td>
                    <td><?php echo $t['borrow_date']; ?></td>
                    <td class="<?php echo $overdue ? 'overdue' : ''; ?>"><?php echo $t['due_date']; ?></td>
                    <td><?php echo $t['return_date'] ?? 'Not returned'; ?><tr>
                    <td>$<?php echo number_format($t['fine_amount'], 2); ?></td>
                    <td><?php echo $t['status']; ?></td>
                    <td>
                        <?php if(!$t['return_date']): ?>
                            <a href="?return=<?php echo $t['transaction_id']; ?>" class="btn-return" onclick="return confirm('Return this book?')">Return</a>
                        <?php else: ?>
                            Returned
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>