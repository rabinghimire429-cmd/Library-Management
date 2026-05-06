<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'libtech_db');
$id = $_GET['id'];
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $genre = $_POST['genre'];
    $total_copies = $_POST['total_copies'];
    $conn->query("UPDATE book SET title='$title', author='$author', genre='$genre', total_copies=$total_copies WHERE book_id=$id");
    header('Location: search-books.php');
    exit();
}
$book = $conn->query("SELECT * FROM book WHERE book_id=$id")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Book</title>
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
        <h2>Edit Book</h2>
        <form method="POST">
            <input type="text" name="title" value="<?php echo $book['title']; ?>" required>
            <input type="text" name="author" value="<?php echo $book['author']; ?>" required>
            <select name="genre"><option <?php echo $book['genre']=='Fiction'?'selected':''; ?>>Fiction</option><option <?php echo $book['genre']=='Non-Fiction'?'selected':''; ?>>Non-Fiction</option></select>
            <input type="number" name="total_copies" value="<?php echo $book['total_copies']; ?>">
            <button type="submit">Update</button>
        </form>
        <a href="search-books.php">← Back</a>
    </div>
</body>
</html>