<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'libtech_db');

// Check connection
if($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get logged in admin ID
$admin_id = intval($_SESSION['admin_id']);

// Get member details
$member = $conn->query("
    SELECT member_id, full_name, email
    FROM member
    WHERE admin_id = $admin_id
")->fetch_assoc();

$member_id = $member['member_id'] ?? 0;
$member_name = $member['full_name'] ?? 'Member';
$member_email = $member['email'] ?? '';

if($member_id <= 0) {
    die("Invalid member.");
}

// Step control for page actions
$step = $_GET['step'] ?? 'list';

$fine_id = intval($_GET['fine_id'] ?? 0);

$payment_msg = '';

/* Search and filter functionality */

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

/* Get fine details */

if($step == 'pay' && $fine_id > 0) {

    $fine_query = "
        SELECT t.*, b.title
        FROM transaction t
        JOIN book b ON t.book_id = b.book_id
        WHERE t.transaction_id = $fine_id
        AND t.member_id = $member_id
    ";

    // Get selected fine information
    $fine = $conn->query($fine_query)->fetch_assoc();

    if(!$fine) {
        die("Fine not found.");
    }
}

/* Payment functionality */

if($step == 'process' && $_SERVER['REQUEST_METHOD'] == 'POST') {

    $fine_id = intval($_POST['fine_id']);

    $card_number = trim($_POST['card_number']);
    $card_name = trim($_POST['card_name']);
    $expiry = trim($_POST['expiry']);
    $cvv = trim($_POST['cvv']);

    /* Validation */

    // Validate cardholder name
    if(empty($card_name)) {
        die("Cardholder name required.");
    }

    // Validate card number
    if(!preg_match('/^[0-9 ]{16,19}$/', $card_number)) {
        die("Invalid card number.");
    }

    // Validate expiry date
    if(!preg_match('/^(0[1-9]|1[0-2])\/[0-9]{2}$/', $expiry)) {
        die("Invalid expiry date.");
    }

    // Validate CVV
    if(!preg_match('/^[0-9]{3,4}$/', $cvv)) {
        die("Invalid CVV.");
    }

    // Verify ownership of fine
    $verify = $conn->query("
        SELECT *
        FROM transaction
        WHERE transaction_id = $fine_id
        AND member_id = $member_id
    ");

    if($verify->num_rows == 0) {
        die("Unauthorized payment.");
    }

    // Update payment status
    $conn->query("
        UPDATE transaction
        SET fine_paid = 1
        WHERE transaction_id = $fine_id
    ");

    // Get fine details for notification
    $fine_details = $conn->query("
        SELECT t.*, b.title
        FROM transaction t
        JOIN book b ON t.book_id = b.book_id
        WHERE t.transaction_id = $fine_id
    ")->fetch_assoc();

    // Create payment confirmation message
    $subject = "💰 Fine Payment Confirmation - LibTech Solutions";

    $message = "
Dear $member_name,

Your fine payment of \$" . number_format($fine_details['fine_amount'], 2) . "
for \"{$fine_details['title']}\" has been received successfully.

Thank you for your payment.

Best regards,
LibTech Team
";

    // Save notification
    $conn->query("
        INSERT INTO notification
        (member_id, notification_type, subject, message, status)

        VALUES
        ($member_id, 'fine', '$subject', '$message', 'sent')
    ");

    $payment_msg = "
    <div class='success-msg'>
        ✅ Payment successful! Confirmation email sent to $member_email
    </div>
    ";

    // Redirect after payment
    echo "
    <script>
        setTimeout(function() {
            window.location.href='my-fines.php';
        }, 2000);
    </script>
    ";
}

/* Delete functionality */

if(isset($_GET['delete'])) {

    $delete_id = intval($_GET['delete']);

    if($delete_id > 0) {

        // Delete fine record
        $conn->query("
            DELETE FROM transaction
            WHERE transaction_id = $delete_id
            AND member_id = $member_id
        ");
    }

    header('Location: my-fines.php');
    exit();
}

/* List functionality */

$sql = "
SELECT t.*, b.title
FROM transaction t
JOIN book b ON t.book_id = b.book_id
WHERE t.member_id = $member_id
AND t.fine_amount > 0
";

// Search fines by book title
if(!empty($search)) {

    $search = $conn->real_escape_string($search);

    $sql .= " AND b.title LIKE '%$search%'";
}

// Filter paid and unpaid fines
if($status !== '') {

    $status = intval($status);

    $sql .= " AND t.fine_paid = $status";
}

$sql .= " ORDER BY t.due_date DESC";

// Execute query
$fines = $conn->query($sql);

/* Calculate total fines */

$total = 0;

$temp = $conn->query($sql);

while($row = $temp->fetch_assoc()) {
    $total += $row['fine_amount'];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>My Fines - LibTech Solutions</title>

<style>

body {
    font-family: 'Segoe UI';
    background: #0a0a2a;
    color: #e4e6eb;
    margin: 0;
    padding: 20px;
}

.container {
    max-width: 1000px;
    margin: auto;
    background: #1c1e26;
    border-radius: 25px;
    padding: 30px;
}

a {
    color: #818cf8;
    text-decoration: none;
}

.total {
    background: rgba(239,68,68,0.15);
    border: 1px solid #ef4444;
    padding: 20px;
    border-radius: 16px;
    text-align: center;
    margin-bottom: 25px;
}

.total h2 {
    color: #f87171;
    font-size: 36px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 12px;
    border-bottom: 1px solid #2d3139;
}

th {
    background: rgba(99,102,241,0.2);
    color: #818cf8;
}

.btn {
    padding: 7px 14px;
    border-radius: 8px;
    color: white;
    text-decoration: none;
    display: inline-block;
    margin-right: 5px;
}

.btn-pay {
    background: #10b981;
}

.btn-delete {
    background: #ef4444;
}

.payment-box {
    background: #0f1419;
    border-radius: 20px;
    padding: 25px;
    margin-top: 20px;
}

.payment-box input {
    width: 100%;
    padding: 12px;
    margin: 10px 0;
    background: #1c1e26;
    border: 1px solid #2d3139;
    border-radius: 10px;
    color: white;
}

.btn-submit {
    background: linear-gradient(135deg, #6366f1, #ec4899);
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 12px;
    color: white;
    font-weight: 600;
    cursor: pointer;
}

.success-msg {
    background: rgba(16,185,129,0.2);
    border: 1px solid #10b981;
    color: #34d399;
    padding: 12px;
    border-radius: 12px;
    text-align: center;
}

input, select {
    padding: 10px;
    border-radius: 8px;
    border: none;
    margin-right: 10px;
}

button {
    padding: 10px 15px;
    border: none;
    border-radius: 8px;
    background: #6366f1;
    color: white;
    cursor: pointer;
}

</style>

</head>

<body>

<div class="container">

<?php if($step == 'list'): ?>

    <a href="../member-dashboard.php">← Back to Dashboard</a>

    <h2>💰 My Fines</h2>

    <!-- Display total outstanding fines -->

    <div class="total">
        <p>Total Outstanding Fines</p>
        <h2>$<?php echo number_format($total, 2); ?></h2>
    </div>

    <!-- Search and filter form -->

    <form method="GET" style="margin-bottom:20px;">

        <input type="text"
               name="search"
               placeholder="Search fines"
               value="<?php echo htmlspecialchars($search); ?>">

        <select name="status">

            <option value="">All</option>
            <option value="0">Unpaid</option>
            <option value="1">Paid</option>

        </select>

        <button type="submit">Filter</button>

    </form>

    <?php if($fines->num_rows > 0): ?>

        <!-- List fine records -->

        <table>

            <thead>
                <tr>
                    <th>Book</th>
                    <th>Due Date</th>
                    <th>Fine Amount</th>
                    <th>Payment Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

            <!-- Loop through fines -->

            <?php while($fine = $fines->fetch_assoc()): ?>

                <tr>

                    <td>
                        <?php echo htmlspecialchars($fine['title']); ?>
                    </td>

                    <td>
                        <?php echo $fine['due_date']; ?>
                    </td>

                    <td>
                        $<?php echo number_format($fine['fine_amount'], 2); ?>
                    </td>

                    <td>
                        <?php echo $fine['fine_paid'] ? 'Paid' : 'Unpaid'; ?>
                    </td>

                    <td>

                        <!-- Pay button -->

                        <?php if(!$fine['fine_paid']): ?>

                            <a class="btn btn-pay"
                               href="?step=pay&fine_id=<?php echo $fine['transaction_id']; ?>">

                               Pay Now

                            </a>

                        <?php endif; ?>

                        <!-- Delete button -->

                        <a class="btn btn-delete"
                           href="?delete=<?php echo $fine['transaction_id']; ?>"
                           onclick="return confirm('Delete this fine record?')">

                           Delete

                        </a>

                    </td>

                </tr>

            <?php endwhile; ?>

            </tbody>

        </table>

    <?php else: ?>

        <p style="text-align:center; padding:40px;">
            🎉 No fines found!
        </p>

    <?php endif; ?>

<?php elseif($step == 'pay' && $fine): ?>

    <a href="my-fines.php">← Back to Fines</a>

    <h2>💳 Pay Fine</h2>

    <!-- Fine details -->

    <div class="payment-box">

        <p>
            <strong>📖 Book:</strong>
            <?php echo htmlspecialchars($fine['title']); ?>
        </p>

        <p>
            <strong>📅 Due Date:</strong>
            <?php echo $fine['due_date']; ?>
        </p>

        <p>
            <strong>💰 Fine Amount:</strong>
            $<?php echo number_format($fine['fine_amount'], 2); ?>
        </p>

    </div>

    <!-- Payment form -->

    <form method="POST" action="?step=process">

        <input type="hidden"
               name="fine_id"
               value="<?php echo $fine_id; ?>">

        <div class="payment-box">

            <h3>💳 Payment Details</h3>

            <input type="text"
                   name="card_number"
                   placeholder="4242 4242 4242 4242"
                   required>

            <input type="text"
                   name="card_name"
                   placeholder="Cardholder Name"
                   required>

            <div style="display:flex; gap:15px;">

                <input type="text"
                       name="expiry"
                       placeholder="MM/YY"
                       required>

                <input type="text"
                       name="cvv"
                       placeholder="CVV"
                       required>

            </div>

            <button type="submit" class="btn-submit">
                Confirm Payment
            </button>

        </div>

    </form>

<?php elseif($step == 'process'): ?>

    <!-- Payment success message -->

    <div class="payment-box">

        <h2>Payment Status</h2>

        <?php echo $payment_msg; ?>

    </div>

<?php endif; ?>

</div>

</body>
</html>