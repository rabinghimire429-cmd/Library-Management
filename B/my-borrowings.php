<?php
session_start();

// Check if user is logged in
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

// Get logged in admin ID
$admin_id = intval($_SESSION['admin_id']);

// Get member ID related to logged in user
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

/* Return functionality */
if(isset($_GET['return'])) {

    $trans_id = intval($_GET['return']);

    // Get borrowing transaction
    $trans_result = $conn->query("
        SELECT * 
        FROM transaction 
        WHERE transaction_id = $trans_id 
        AND member_id = $member_id
    ");

    if($trans_result->num_rows > 0) {

        $trans = $trans_result->fetch_assoc();

        // Prevent double return
        if($trans['status'] != 'Returned') {

            $return_date = date('Y-m-d');
            $fine = 0;

            // Fine calculation if overdue
            if($return_date > $trans['due_date']) {

                $days = (
                    strtotime($return_date) -
                    strtotime($trans['due_date'])
                ) / 86400;

                $fine = $days * 0.50;
            }

            // Update borrowing status
            $conn->query("
                UPDATE transaction 
                SET return_date = '$return_date',
                    fine_amount = $fine,
                    status = 'Returned'
                WHERE transaction_id = $trans_id
            ");

            // Increase available book copies
            $conn->query("
                UPDATE book 
                SET available_copies = available_copies + 1 
                WHERE book_id = {$trans['book_id']}
            ");
        }
    }

    header('Location: my-borrowings.php');
    exit();
}

/* Delete functionality */
if(isset($_GET['delete'])) {

    $delete_id = intval($_GET['delete']);

    // Only returned history can be deleted
    $check = $conn->query("
        SELECT * 
        FROM transaction 
        WHERE transaction_id = $delete_id
        AND member_id = $member_id
        AND status = 'Returned'
    ");

    if($check->num_rows > 0) {

        $conn->query("
            DELETE FROM transaction
            WHERE transaction_id = $delete_id
            AND member_id = $member_id
            AND status = 'Returned'
        ");

    } else {

        die("You can only delete returned borrowing history.");
    }

    header('Location: my-borrowings.php');
    exit();
}

/* Search and filter functionality */
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

// List functionality - get borrowing records
$sql = "
SELECT t.*, b.title
FROM transaction t
JOIN book b ON t.book_id = b.book_id
WHERE t.member_id = $member_id
";

// Search by book title
if(!empty($search)) {

    $safe_search = $conn->real_escape_string($search);

    $sql .= " AND b.title LIKE '%$safe_search%'";
}

// Filter by borrowing status
if(!empty($status)) {

    $safe_status = $conn->real_escape_string($status);

    $sql .= " AND t.status = '$safe_status'";
}

$sql .= " ORDER BY t.borrow_date DESC";

// Execute query
$transactions = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">

<title>My Borrowings - LibTech Solutions</title>

<style>

body {
    font-family: 'Segoe UI', sans-serif;
    background: #0a0a2a;
    color: #e4e6eb;
    margin: 0;
    padding: 20px;
}

.container {
    max-width: 1100px;
    margin: 40px auto;
    padding: 35px;
    background: #1c1e26;
    border-radius: 25px;
}

a {
    color: #818cf8;
    text-decoration: none;
}

h2 {
    margin-top: 40px;
    font-size: 32px;
}

form {
    margin-bottom: 25px;
}

input, select {
    padding: 14px;
    border-radius: 10px;
    border: none;
    margin-right: 12px;
    font-size: 15px;
}

button {
    padding: 14px 22px;
    border: none;
    border-radius: 10px;
    background: #6366f1;
    color: white;
    cursor: pointer;
    font-size: 15px;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 25px;
}

th, td {
    padding: 16px;
    border-bottom: 1px solid #2d3139;
    text-align: left;
}

th {
    background: rgba(99,102,241,0.2);
    color: #818cf8;
}

.btn {
    padding: 10px 16px;
    border-radius: 9px;
    color: white;
    text-decoration: none;
    margin-right: 6px;
    display: inline-block;
}

.btn-return {
    background: #10b981;
}

.btn-edit {
    background: #f59e0b;
}

.btn-delete {
    background: #ef4444;
}

.overdue {
    color: #f87171;
    font-weight: bold;
}

.status-borrowed {
    color: #fbbf24;
}

.status-returned {
    color: #34d399;
}

</style>
</head>

<body>

<div class="container">

    <a href="../member-dashboard.php">
        ← Back to Dashboard
    </a>

    <h2>📚 My Borrowings</h2>

    <!-- Search and filter form -->

    <form method="GET">

        <input type="text"
               name="search"
               placeholder="Search by book title"
               value="<?php echo htmlspecialchars($search); ?>">

        <select name="status">

            <option value="">All Status</option>

            <option value="Borrowed"
                <?php if($status == 'Borrowed') echo 'selected'; ?>>

                Borrowed

            </option>

            <option value="Returned"
                <?php if($status == 'Returned') echo 'selected'; ?>>

                Returned

            </option>

        </select>

        <button type="submit">Search</button>

    </form>

    <!-- List borrowing records -->

    <table>

        <thead>

            <tr>
                <th>Book</th>
                <th>Borrow Date</th>
                <th>Due Date</th>
                <th>Return Date</th>
                <th>Fine</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>

        </thead>

        <tbody>

        <?php if($transactions->num_rows > 0): ?>

            <!-- Loop through borrowing records -->

            <?php while($t = $transactions->fetch_assoc()): 

                // Check overdue books
                $overdue = (
                    $t['return_date'] == null &&
                    $t['due_date'] < date('Y-m-d')
                );
            ?>

            <tr>

                <td>
                    <?php echo htmlspecialchars($t['title']); ?>
                </td>

                <td>
                    <?php echo $t['borrow_date']; ?>
                </td>

                <td class="<?php echo $overdue ? 'overdue' : ''; ?>">

                    <?php echo $t['due_date']; ?>

                    <?php if($overdue): ?>
                        ⚠️ Overdue
                    <?php endif; ?>

                </td>

                <td>

                    <?php
                    echo $t['return_date']
                        ? $t['return_date']
                        : 'Not Returned';
                    ?>

                </td>

                <td>
                    $<?php echo number_format($t['fine_amount'], 2); ?>
                </td>

                <td>

                    <span class="<?php echo $t['status'] == 'Returned'
                        ? 'status-returned'
                        : 'status-borrowed'; ?>">

                        <?php echo $t['status']; ?>

                    </span>

                </td>

                <td>

                    <?php if($t['status'] != 'Returned'): ?>

                        <!-- Return button -->

                        <a class="btn btn-return"
                           href="?return=<?php echo $t['transaction_id']; ?>"
                           onclick="return confirm('Return this book?')">

                           Return

                        </a>

                        <!-- Edit button -->

                        <a class="btn btn-edit"
                           href="edit-transaction.php?id=<?php echo $t['transaction_id']; ?>">

                           Edit

                        </a>

                    <?php else: ?>

                        <!-- Delete returned history -->

                        <a class="btn btn-delete"
                           href="?delete=<?php echo $t['transaction_id']; ?>"
                           onclick="return confirm('Delete this returned borrowing history?')">

                           Delete

                        </a>

                    <?php endif; ?>

                </td>

            </tr>

            <?php endwhile; ?>

        <?php else: ?>

            <tr>

                <td colspan="7"
                    style="text-align:center; padding:40px;">

                    No borrowing records found.

                </td>

            </tr>

        <?php endif; ?>

        </tbody>

    </table>

</div>

</body>
</html>