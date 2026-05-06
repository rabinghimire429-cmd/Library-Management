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

$fines = $conn->query("SELECT t.*, b.title FROM transaction t JOIN book b ON t.book_id = b.book_id WHERE t.member_id = $member_id AND t.fine_amount > 0 AND t.fine_paid = 0");
$total = 0;
while($row = $fines->fetch_assoc()) $total += $row['fine_amount'];
$fines->data_seek(0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Fines</title>
    <style>
        body { font-family: Arial; background: #0a0a2a; color: white; }
        .container { max-width: 800px; margin: 40px auto; padding: 20px; }
        a { color: #818cf8; }
        .total { background: rgba(239,68,68,0.2); padding: 20px; border-radius: 16px; text-align: center; margin-bottom: 20px; }
        .total h2 { color: #f87171; }
        table { width: 100%; background: rgba(255,255,255,0.1); border-radius: 16px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.2); }
        th { background: #6366f1; }
        .btn-pay { background: #10b981; color: white; padding: 5px 12px; border-radius: 8px; cursor: pointer; border: none; }
    </style>
</head>
<body>
    <div class="container">
        <a href="../member-dashboard.php">← Back</a>
        <h2>💰 My Fines</h2>
        <div class="total"><p>Total Outstanding Fines</p><h2>$<?php echo number_format($total, 2); ?></h2></div>
        <?php if($fines->num_rows > 0): ?>
        </table>
            <thead><tr><th>Book</th><th>Due Date</th><th>Fine Amount</th><th>Action</th></tr></thead>
            <tbody>
                <?php while($fine = $fines->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $fine['title']; ?></td>
                    <td><?php echo $fine['due_date']; ?></td>
                    <td>$<?php echo number_format($fine['fine_amount'], 2); ?></td>
                    <td><button class="btn-pay" onclick="alert('✅ Payment received!')">Pay Now</button></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p style="text-align:center;">🎉 No fines!</p>
        <?php endif; ?>
    </div>
</body>
</html>