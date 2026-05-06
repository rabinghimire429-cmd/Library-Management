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
$msg = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $book_id = $_POST['book_id'];
    $borrow_date = date('Y-m-d');
    $due_date = date('Y-m-d', strtotime('+14 days'));
    $book_check = $conn->query("SELECT * FROM book WHERE book_id = $book_id AND available_copies > 0");
    if($book_check->num_rows > 0) {
        $conn->query("UPDATE book SET available_copies = available_copies - 1 WHERE book_id = $book_id");
        $conn->query("INSERT INTO transaction (member_id, book_id, borrow_date, due_date, status) VALUES ($member_id, $book_id, '$borrow_date', '$due_date', 'Borrowed')");
        $msg = "<p style='color:#34d399'>✅ Book borrowed successfully! Due date: $due_date</p>";
    } else {
        $msg = "<p style='color:#f87171'>❌ Book not available!</p>";
    }
}

$books = $conn->query("SELECT * FROM book WHERE available_copies > 0 ORDER BY title");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Borrow Book</title>
    <style>
        body { font-family: Arial; background: #0a0a2a; color: white; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .form-container { background: rgba(255,255,255,0.1); border-radius: 30px; padding: 40px; width: 450px; }
        select, button { width: 100%; padding: 12px; margin: 10px 0; background: rgba(255,255,255,0.2); border-radius: 12px; color: white; }
        button { background: #6366f1; cursor: pointer; }
        a { color: #818cf8; display: block; text-align: center; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>📚 Borrow a Book</h2>
        <?php echo $msg; ?>
        <form method="POST">
            <select name="book_id" required>
                <option value="">Select a book</option>
                <?php while($book = $books->fetch_assoc()): ?>
                    <option value="<?php echo $book['book_id']; ?>"><?php echo $book['title']; ?> (Available: <?php echo $book['available_copies']; ?>)</option>
                <?php endwhile; ?>
            </select>
            <button type="submit">Borrow Book</button>
        </form>
        <a href="../member-dashboard.php">← Back</a>
    </div>
</body>
</html>