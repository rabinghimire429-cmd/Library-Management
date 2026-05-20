<?php
session_start();

// Check if the user is logged in
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'libtech_db');

// Check database connection
if($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get logged-in admin ID
$admin_id = intval($_SESSION['admin_id']);

// Get member ID connected to the logged-in admin account
$member_result = $conn->query("
    SELECT member_id
    FROM member
    WHERE admin_id = $admin_id
");

$member = $member_result->fetch_assoc();
$member_id = $member['member_id'] ?? 0;

// Validate member account
if($member_id <= 0) {
    die("Invalid member account.");
}

// Get transaction ID from URL
$id = intval($_GET['id'] ?? 0);

// Validate transaction ID
if($id <= 0) {
    die("Invalid transaction ID.");
}

// Get borrowing transaction details
$result = $conn->query("
    SELECT t.*, b.title
    FROM transaction t
    JOIN book b ON t.book_id = b.book_id
    WHERE t.transaction_id = $id
    AND t.member_id = $member_id
");

// Check if transaction exists
if($result->num_rows == 0) {
    die("Transaction not found.");
}

$transaction = $result->fetch_assoc();

// Returned books should not be edited
if($transaction['status'] == 'Returned') {
    die("Returned transactions cannot be edited.");
}

$msg = '';

// Update due date when form is submitted
if($_SERVER['REQUEST_METHOD'] == 'POST') {

    $due_date = $_POST['due_date'] ?? '';

    // Validate due date
    if(empty($due_date)) {
        $msg = "Due date is required.";
    } elseif($due_date < $transaction['borrow_date']) {
        $msg = "Due date cannot be before borrow date.";
    } else {

        // Edit functionality - update due date
        $conn->query("
            UPDATE transaction
            SET due_date = '$due_date'
            WHERE transaction_id = $id
            AND member_id = $member_id
            AND status != 'Returned'
        ");

        header('Location: my-borrowings.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Borrowing</title>

<style>
body {
    font-family: 'Segoe UI', sans-serif;
    background: #0a0a2a;
    color: white;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    margin: 0;
}

.container {
    width: 450px;
    background: #1c1e26;
    padding: 35px;
    border-radius: 25px;
}

a {
    color: #818cf8;
    text-decoration: none;
}

input {
    width: 100%;
    padding: 13px;
    margin-top: 10px;
    border-radius: 10px;
    border: none;
    background: #0f1419;
    color: white;
}

button {
    width: 100%;
    padding: 14px;
    margin-top: 20px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #6366f1, #ec4899);
    color: white;
    font-weight: bold;
    cursor: pointer;
}

.msg {
    background: rgba(239,68,68,0.2);
    border: 1px solid #ef4444;
    color: #f87171;
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 15px;
}
</style>
</head>

<body>

<div class="container">

    <a href="my-borrowings.php">← Back</a>

    <h2>✏️ Edit Borrowing</h2>

    <!-- Display validation message -->
    <?php if($msg): ?>
        <div class="msg"><?php echo $msg; ?></div>
    <?php endif; ?>

    <!-- Show current borrowing details -->
    <p><strong>Book:</strong> <?php echo htmlspecialchars($transaction['title']); ?></p>
    <p><strong>Borrow Date:</strong> <?php echo $transaction['borrow_date']; ?></p>

    <!-- Edit due date form -->
    <form method="POST">

        <label>Due Date</label>

        <input type="date"
               name="due_date"
               value="<?php echo $transaction['due_date']; ?>"
               required>

        <button type="submit">Update Due Date</button>

    </form>

</div>

</body>
</html>