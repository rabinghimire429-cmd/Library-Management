<?php

// Start session
session_start();

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

// Connect to database
require_once '../config.php';

// Message variables
$msg = '';
$error = '';

// Check if form is submitted
if($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Security check
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Security validation failed. Please refresh the page and try again.";
    } else {

        // Get book details from form
        $title = sanitizeInput($_POST['title']);
        $author = sanitizeInput($_POST['author']);
        $isbn = sanitizeInput($_POST['isbn']);
        $genre = $_POST['genre'];

        // Get number of copies
        $total_copies = (int)$_POST['total_copies'];

        // Validation checks
        $errors = [];

        // Check title
        if(empty($title)) $errors[] = "Title is required";

        // Check author
        if(empty($author)) $errors[] = "Author is required";

        // Check ISBN format
        if(!validateISBN($isbn)) $errors[] = "Invalid ISBN format";

        // Check copies
        if($total_copies < 1) $errors[] = "Total copies must be at least 1";

        // If there are no errors
        if(empty($errors)) {

            // Add book to database
            $stmt = $conn->prepare("INSERT INTO book (title, author, isbn, genre, total_copies, available_copies) VALUES (?, ?, ?, ?, ?, ?)");

            // Bind values
            $stmt->bind_param("ssssii", $title, $author, $isbn, $genre, $total_copies, $total_copies);

            // Execute query
            if($stmt->execute()) {

                // Success message
                $msg = " Book added successfully!";

                // Save activity log
                logAdminAction($conn, $_SESSION['admin_id'], 'ADD_BOOK', "Added book: $title (ISBN: $isbn)");

            } else {

                // Error message
                $error = " Error adding book: " . $conn->error;
            }

            // Close query
            $stmt->close();

        } else {

            // Show validation errors
            $error = implode("<br>", $errors);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Book - LibTech Solutions</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #0f0c29, #302b63, #24243e); min-height: 100vh; color: #e4e6eb; display: flex; justify-content: center; align-items: center; }
        .form-container { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 30px; padding: 40px; width: 500px; border: 1px solid rgba(255,255,255,0.2); }
        h2 { margin-bottom: 20px; text-align: center; background: linear-gradient(135deg, #fff, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        input, select { width: 100%; padding: 12px; margin: 10px 0; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 12px; color: white; }
        button { width: 100%; padding: 12px; background: linear-gradient(135deg, #6366f1, #ec4899); border: none; border-radius: 12px; color: white; cursor: pointer; font-weight: 600; }
        a { color: #818cf8; display: block; margin-top: 15px; text-align: center; }
        .success { background: rgba(16,185,129,0.2); color: #34d399; padding: 12px; border-radius: 12px; margin-bottom: 15px; text-align: center; }
        .error { background: rgba(239,68,68,0.2); color: #f87171; padding: 12px; border-radius: 12px; margin-bottom: 15px; text-align: center; }
        .hint { font-size: 12px; color: #8b8d94; margin-top: -5px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2> Add New Book</h2>
        
        <?php if($msg): ?>
            <div class="success"><?php echo $msg; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <input type="text" name="title" placeholder="Book Title" required>
            
            <input type="text" name="author" placeholder="Author" required>
            
            <input type="text" name="isbn" placeholder="ISBN (13 digits)" required>
            <div class="hint"> Format: 9780743273565</div>
            
            <select name="genre">
                <option value="Fiction">Fiction</option>
                <option value="Non-Fiction">Non-Fiction</option>
                <option value="Science">Science</option>
                <option value="History">History</option>
                <option value="Technology">Technology</option>
            </select>
            
            <input type="number" name="total_copies" placeholder="Total Copies" value="1" min="1" required>
            
            <button type="submit"> Add Book</button>
        </form>
        
        <a href="search-books.php">← Back to Search</a>
    </div>
</body>
</html>