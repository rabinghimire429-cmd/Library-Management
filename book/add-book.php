<?php

// Start session
session_start();

// Check if admin logged in
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'libtech_db');

// Message variable
$msg = '';

// Check form submit
if($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Get input values
    $title = $_POST['title'];
    $author = $_POST['author'];
    $isbn = $_POST['isbn'];
    $genre = $_POST['genre'];
    $total_copies = $_POST['total_copies'];

    // Add book into database
    $conn->query("INSERT INTO book (title, author, isbn, genre, total_copies, available_copies) VALUES ('$title', '$author', '$isbn', '$genre', $total_copies, $total_copies)");

    // Show success message
    $msg = "<p style='color:#34d399'>  Book added!</p>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <!-- Page name -->
    <title>Add Book</title>

    <style>

        /* Page design */
        body { font-family: Arial; background: #0a0a2a; color: white; display: flex; justify-content: center; align-items: center; height: 100vh; }

        /* Form box */
        .form-container { background: rgba(255,255,255,0.1); border-radius: 30px; padding: 40px; width: 450px; }

        /* Input design */
        input, select { width: 100%; padding: 12px; margin: 10px 0; background: rgba(255,255,255,0.2); border-radius: 12px; color: white; }

        /* Button design */
        button { width: 100%; padding: 12px; background: #6366f1; border: none; border-radius: 12px; color: white; cursor: pointer; }

        /* Link design */
        a { color: #818cf8; display: block; margin-top: 15px; }
    </style>
</head>
<body>

    <!-- Form container -->
    <div class="form-container">

        <!-- Heading -->
        <h2>  Add Book</h2>

        <!-- Print message -->
        <?php echo $msg; ?>

        <!-- Form start -->
        <form method="POST">

            <!-- Title input -->
            <input type="text" name="title" placeholder="Title" required>

            <!-- Author input -->
            <input type="text" name="author" placeholder="Author" required>

            <!-- ISBN input -->
            <input type="text" name="isbn" placeholder="ISBN" required>

            <!-- Genre option -->
            <select name="genre"><option>Fiction</option><option>Non-Fiction</option></select>

            <!-- Copies input -->
            <input type="number" name="total_copies" placeholder="Copies" value="1">

            <!-- Add button -->
            <button type="submit">Add Book</button>
        </form>

        <!-- Back button -->
        <a href="../librarian-dashboard.php">← Back</a>
    </div>
</body>
</html>