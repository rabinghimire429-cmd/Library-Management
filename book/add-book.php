<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'libtech_db');
$msg = '';
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $isbn = $_POST['isbn'];
    $genre = $_POST['genre'];
    $total_copies = $_POST['total_copies'];
    $conn->query("INSERT INTO book (title, author, isbn, genre, total_copies, available_copies) VALUES ('$title', '$author', '$isbn', '$genre', $total_copies, $total_copies)");
    $msg = "<p style='color:#34d399'>✅ Book added!</p>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Book</title>
    <style>
        body { font-family: Arial; background: #0a0a2a; color: white; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .form-container { background: rgba(255,255,255,0.1); border-radius: 30px; padding: 40px; width: 450px; }
        input, select { width: 100%; padding: 12px; margin: 10px 0; background: rgba(255,255,255,0.2); border-radius: 12px; color: white; }
        button { width: 100%; padding: 12px; background: #6366f1; border: none; border-radius: 12px; color: white; cursor: pointer; }
        a { color: #818cf8; display: block; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>📚 Add Book</h2>
        <?php echo $msg; ?>
        <form method="POST">
            <input type="text" name="title" placeholder="Title" required>
            <input type="text" name="author" placeholder="Author" required>
            <input type="text" name="isbn" placeholder="ISBN" required>
            <select name="genre"><option>Fiction</option><option>Non-Fiction</option></select>
            <input type="number" name="total_copies" placeholder="Copies" value="1">
            <button type="submit">Add Book</button>
        </form>
        <a href="../librarian-dashboard.php">← Back</a>
    </div>
</body>
</html>