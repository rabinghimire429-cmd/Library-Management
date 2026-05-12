<?php
session_start();

if(!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'libtech_db');

if($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = intval($_GET['id'] ?? 0);

if($id <= 0) {
    die("Invalid transaction ID");
}

/* =========================================
   GET TRANSACTION
========================================= */

$result = $conn->query("
    SELECT t.*, b.title
    FROM transaction t
    JOIN book b ON t.book_id = b.book_id
    WHERE t.transaction_id = $id
");

if($result->num_rows == 0) {
    die("Transaction not found.");
}

$transaction = $result->fetch_assoc();

/* =========================================
   UPDATE TRANSACTION
========================================= */

$msg = "";

if($_SERVER['REQUEST_METHOD'] == 'POST') {

    $due_date = $_POST['due_date'];

    if(empty($due_date)) {

        $msg = "Due date is required.";

    } else {

        $conn->query("
            UPDATE transaction
            SET due_date = '$due_date'
            WHERE transaction_id = $id
        ");

        $msg = "✅ Transaction updated successfully!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>Edit Transaction</title>

<style>

body {
    font-family: 'Segoe UI';
    background: #0a0a2a;
    color: white;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}

.container {
    width: 450px;
    background: #1c1e26;
    padding: 35px;
    border-radius: 25px;
}

input {
    width: 100%;
    padding: 12px;
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
    cursor: pointer;
    font-weight: bold;
}

a {
    color: #818cf8;
    text-decoration: none;
}

.msg {
    background: rgba(16,185,129,0.2);
    border: 1px solid #10b981;
    padding: 12px;
    border-radius: 10px;
    margin-bottom: 20px;
    color: #34d399;
}

</style>

</head>

<body>

<div class="container">

    <a href="my-borrowings.php">← Back</a>

    <h2>✏️ Edit Borrowing</h2>

    <?php if($msg): ?>

        <div class="msg">
            <?php echo $msg; ?>
        </div>

    <?php endif; ?>

    <p>
        <strong>Book:</strong>
        <?php echo htmlspecialchars($transaction['title']); ?>
    </p>

    <p>
        <strong>Borrow Date:</strong>
        <?php echo $transaction['borrow_date']; ?>
    </p>

    <form method="POST">

        <label>Due Date</label>

        <input type="date"
               name="due_date"
               value="<?php echo $transaction['due_date']; ?>"
               required>

        <button type="submit">
            Update Transaction
        </button>

    </form>

</div>

</body>
</html>