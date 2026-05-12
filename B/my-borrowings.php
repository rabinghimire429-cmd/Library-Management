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

$admin_id = intval($_SESSION['admin_id']);

$member_result = $conn->query("
    SELECT member_id
    FROM member
    WHERE admin_id = $admin_id
");

$member = $member_result->fetch_assoc();

$member_id = $member['member_id'] ?? 0;

if($member_id <= 0) {
    die("Invalid member account.");
}

/* =========================================
   RETURN BOOK
========================================= */

if(isset($_GET['return'])) {

    $trans_id = intval($_GET['return']);

    if($trans_id <= 0) {
        die("Invalid transaction.");
    }

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

            // Fine calculation

            if($return_date > $trans['due_date']) {

                $days = (
                    strtotime($return_date) -
                    strtotime($trans['due_date'])
                ) / (60 * 60 * 24);

                $fine = $days * 0.50;
            }

            // Update transaction

            $conn->query("
                UPDATE transaction
                SET return_date = '$return_date',
                    fine_amount = $fine,
                    status = 'Returned'
                WHERE transaction_id = $trans_id
            ");

            // Increase available copies

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

/* =========================================
   DELETE TRANSACTION
========================================= */

if(isset($_GET['delete'])) {

    $delete_id = intval($_GET['delete']);

    if($delete_id > 0) {

        $conn->query("
            DELETE FROM transaction
            WHERE transaction_id = $delete_id
            AND member_id = $member_id
        ");
    }

    header('Location: my-borrowings.php');
    exit();
}

/* =========================================
   SEARCH + FILTER
========================================= */

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$sql = "
SELECT t.*, b.title
FROM transaction t
JOIN book b ON t.book_id = b.book_id
WHERE t.member_id = $member_id
";

if(!empty($search)) {

    $search = $conn->real_escape_string($search);

    $sql .= " AND b.title LIKE '%$search%'";
}

if(!empty($status)) {

    $status = $conn->real_escape_string($status);

    $sql .= " AND t.status = '$status'";
}

$sql .= " ORDER BY t.borrow_date DESC";

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
    padding: 25px;
    background: #1c1e26;
    border-radius: 25px;
}

a {
    color: #818cf8;
    text-decoration: none;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th, td {
    padding: 12px;
    border-bottom: 1px solid #2d3139;
    text-align: left;
}

th {
    background: rgba(99,102,241,0.2);
    color: #818cf8;
}

.overdue {
    color: #f87171;
    font-weight: bold;
}

.btn {
    padding: 7px 14px;
    border-radius: 8px;
    text-decoration: none;
    color: white;
    margin-right: 5px;
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

.status-returned {
    color: #34d399;
}

.status-borrowed {
    color: #fbbf24;
}

.back-link {
    display: inline-block;
    margin-bottom: 20px;
}

</style>

</head>

<body>

<div class="container">

    <a href="../member-dashboard.php" class="back-link">
        ← Back to Dashboard
    </a>

    <h2>📚 My Borrowings</h2>

    <!-- SEARCH + FILTER -->

    <form method="GET" style="margin-bottom:20px;">

        <input type="text"
               name="search"
               placeholder="Search by book title"
               value="<?php echo htmlspecialchars($search); ?>">

        <select name="status">

            <option value="">All Status</option>

            <option value="Borrowed">
                Borrowed
            </option>

            <option value="Returned">
                Returned
            </option>

        </select>

        <button type="submit">Search</button>

    </form>

    <!-- BORROWINGS TABLE -->

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

        <?php while($t = $transactions->fetch_assoc()):

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

                        <a class="btn btn-return"
                           href="?return=<?php echo $t['transaction_id']; ?>"
                           onclick="return confirm('Return this book?')">

                           Return

                        </a>

                    <?php endif; ?>

                    <!-- EDIT -->

                    <a class="btn btn-edit"
                       href="edit-transaction.php?id=<?php echo $t['transaction_id']; ?>">

                       Edit

                    </a>

                    <!-- DELETE -->

                    <a class="btn btn-delete"
                       href="?delete=<?php echo $t['transaction_id']; ?>"
                       onclick="return confirm('Delete this transaction?')">

                       Delete

                    </a>

                </td>

            </tr>

        <?php endwhile; ?>

        </tbody>

    </table>

</div>

</body>
</html>