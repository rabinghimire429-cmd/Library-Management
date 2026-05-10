<?php
session_start();
if(!isset($_SESSION['admin_id'])) { header('Location: ../index.php'); exit(); }

$conn = new mysqli('localhost', 'root', '', 'libtech_db');
$admin_id = $_SESSION['admin_id'];
$member = $conn->query("SELECT member_id, full_name, email FROM member WHERE admin_id = $admin_id")->fetch_assoc();
$member_id = $member['member_id'] ?? 0;
$member_name = $member['full_name'] ?? 'Member';
$member_email = $member['email'] ?? '';

$step = $_GET['step'] ?? 'list';
$fine_id = $_GET['fine_id'] ?? 0;
$payment_msg = '';

// Get specific fine details for payment
if($step == 'pay' && $fine_id) {
    $fine = $conn->query("SELECT t.*, b.title FROM transaction t JOIN book b ON t.book_id = b.book_id WHERE t.transaction_id = $fine_id AND t.member_id = $member_id")->fetch_assoc();
}

// Process payment
if($step == 'process' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $fine_id = $_POST['fine_id'];
    $card_number = $_POST['card_number'];
    $card_name = $_POST['card_name'];
    $expiry = $_POST['expiry'];
    $cvv = $_POST['cvv'];
    
    // Simulate payment processing
    $conn->query("UPDATE transaction SET fine_paid = 1 WHERE transaction_id = $fine_id");
    
    // Send confirmation notification
    $fine_details = $conn->query("SELECT t.*, b.title FROM transaction t JOIN book b ON t.book_id = b.book_id WHERE t.transaction_id = $fine_id")->fetch_assoc();
    $subject = "💰 Fine Payment Confirmation - LibTech Solutions";
    $message = "Dear $member_name,\n\nYour fine payment of \$${fine_details['fine_amount']} for \"{$fine_details['title']}\" has been received successfully.\n\nThank you for your prompt payment.\n\nBest regards,\nLibTech Team";
    $conn->query("INSERT INTO notification (member_id, notification_type, subject, message, status) VALUES ($member_id, 'fine', '$subject', '$message', 'sent')");
    
    $payment_msg = "<div class='success-msg'>✅ Payment successful! Fine paid. A confirmation email has been sent to $member_email</div>";
    echo "<script>setTimeout(function(){ window.location.href = 'my-fines.php'; }, 2000);</script>";
}

// Get all outstanding fines
$fines = $conn->query("SELECT t.*, b.title FROM transaction t JOIN book b ON t.book_id = b.book_id WHERE t.member_id = $member_id AND t.fine_amount > 0 AND t.fine_paid = 0");
$total = 0;
while($row = $fines->fetch_assoc()) $total += $row['fine_amount'];
$fines->data_seek(0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Fines - LibTech Solutions</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #0a0a2a; color: #e4e6eb; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 40px auto; padding: 30px; background: #1c1e26; border-radius: 30px; border: 1px solid #2d3139; }
        a { color: #818cf8; text-decoration: none; }
        .total { background: rgba(239,68,68,0.15); border: 1px solid #ef4444; padding: 20px; border-radius: 16px; text-align: center; margin-bottom: 25px; }
        .total h2 { color: #f87171; font-size: 36px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #2d3139; }
        th { background: rgba(99,102,241,0.2); color: #818cf8; }
        .btn-pay { background: #10b981; color: white; padding: 8px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        .btn-pay:hover { transform: scale(1.02); }
        .back-link { display: inline-block; margin-bottom: 20px; }
        .payment-box { background: #0f1419; border-radius: 20px; padding: 25px; margin-top: 20px; border: 1px solid #2d3139; }
        .payment-box input { width: 100%; padding: 12px; margin: 10px 0; background: #1c1e26; border: 1px solid #2d3139; border-radius: 10px; color: #e4e6eb; }
        .payment-box input:focus { outline: none; border-color: #6366f1; }
        .btn-submit { background: linear-gradient(135deg, #6366f1, #ec4899); width: 100%; padding: 14px; border: none; border-radius: 12px; color: white; font-weight: 600; cursor: pointer; }
        .success-msg { background: rgba(16,185,129,0.2); border: 1px solid #10b981; color: #34d399; padding: 12px; border-radius: 12px; margin-bottom: 20px; text-align: center; }
        .fine-detail { background: #0f1419; border-radius: 16px; padding: 15px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <?php if($step == 'list'): ?>
            <a href="../member-dashboard.php" class="back-link">← Back to Dashboard</a>
            <h2>💰 My Fines</h2>
            <div class="total"><p>Total Outstanding Fines</p><h2>$<?php echo number_format($total, 2); ?></h2></div>
            
            <?php if($fines->num_rows > 0): ?>
                <table>
                    <thead><tr><th>Book</th><th>Due Date</th><th>Fine Amount</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php while($fine = $fines->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $fine['title']; ?></td>
                            <td><?php echo $fine['due_date']; ?></td>
                            <td>$<?php echo number_format($fine['fine_amount'], 2); ?></td>
                            <td><a href="?step=pay&fine_id=<?php echo $fine['transaction_id']; ?>" class="btn-pay">💳 Pay Now</a></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align:center; padding:40px;">🎉 No outstanding fines! Great job!</p>
            <?php endif; ?>
            
        <?php elseif($step == 'pay' && $fine): ?>
            <a href="my-fines.php" class="back-link">← Back to Fines</a>
            <h2>💳 Pay Fine</h2>
            
            <div class="fine-detail">
                <p><strong>📖 Book:</strong> <?php echo $fine['title']; ?></p>
                <p><strong>📅 Due Date:</strong> <?php echo $fine['due_date']; ?></p>
                <p><strong>💰 Fine Amount:</strong> <span style="color:#f87171; font-size:20px;">$<?php echo number_format($fine['fine_amount'], 2); ?></span></p>
            </div>
            
            <form method="POST" action="?step=process">
                <input type="hidden" name="fine_id" value="<?php echo $fine_id; ?>">
                <div class="payment-box">
                    <h3>💳 Payment Details</h3>
                    <input type="text" name="card_number" placeholder="Card Number (4242 4242 4242 4242)" required>
                    <input type="text" name="card_name" placeholder="Cardholder Name" required>
                    <div style="display: flex; gap: 15px;">
                        <input type="text" name="expiry" placeholder="MM/YY" style="flex:1;" required>
                        <input type="text" name="cvv" placeholder="CVV" style="flex:1;" required>
                    </div>
                    <p style="font-size: 12px; color: #8b8d94; margin-top: 15px;"><i class="fas fa-lock"></i> Secure payment simulation. No real transaction will be processed.</p>
                    <button type="submit" class="btn-submit">Confirm Payment →</button>
                </div>
            </form>
            
        <?php elseif($step == 'process'): ?>
            <div class="payment-box">
                <h2>💰 Payment Status</h2>
                <?php echo $payment_msg; ?>
                <p style="text-align:center; margin-top:20px;"><a href="my-fines.php">← Return to Fines</a></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>