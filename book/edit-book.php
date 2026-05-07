<?php

// Start session
session_start();

// Check admin login
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'libtech_db');

// Get book id
$id = $_GET['id'];

// Check form submit
if($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Get updated values
    $title = $_POST['title'];
    $author = $_POST['author'];
    $genre = $_POST['genre'];
    $total_copies = $_POST['total_copies'];

    // Update book data
    $conn->query("UPDATE book SET title='$title', author='$author', genre='$genre', total_copies=$total_copies WHERE book_id=$id");

    // Redirect to search page
    header('Location: search-books.php');
    exit();
}

// Fetch book data
$book = $conn->query("SELECT * FROM book WHERE book_id=$id")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <!-- Page title -->
    <title>Edit Book</title>

    <style>

        /* Page design */
        body { font-family: Arial; background: #0a0a2a; color: white; display: flex; justify-content: center; align-items: center; height: 100vh; }

        /* Form container */
        .form-container { background: rgba(255,255,255,0.1); border-radius: 30px; padding: 40px; width: 450px; }

        /* Input style */
        input, select { width: 100%; padding: 12px; margin: 10px 0; background: rgba(255,255,255,0.2); border-radius: 12px; color: white; }

        /* Button style */
        button { width: 100%; padding: 12px; background: #6366f1; border: none; border-radius: 12px; color: white; cursor: pointer; }

        /* Link style */
        a { color: #818cf8; display: block; margin-top: 15px; }
    </style>
</head>
<body>

    <!-- Main container -->
    <div class="form-container">

        <!-- Heading -->
        <h2>Edit Book</h2>

        <!-- Edit form -->
        <form method="POST">

            <!-- Title input -->
            <input type="text" name="title" value="<?php echo $book['title']; ?>" required>

            <!-- Author input -->
            <input type="text" name="author" value="<?php echo $book['author']; ?>" required>

            <!-- Genre selection -->
            <select name="genre"><option <?php echo $book['genre']=='Fiction'?'selected':''; ?>>Fiction</option><option <?php echo $book['genre']=='Non-Fiction'?'selected':''; ?>>Non-Fiction</option></select>

            <!-- Total copies input -->
            <input type="number" name="total_copies" value="<?php echo $book['total_copies']; ?>">

            <!-- Update button -->
            <button type="submit">Update</button>
        </form>

        <!-- Back link -->
        <a href="search-books.php">← Back</a>
    </div>
</body>
</html>