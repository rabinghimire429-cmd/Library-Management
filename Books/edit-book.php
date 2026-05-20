<?php
session_start();
if(!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'Librarian') {
    header('Location: ../index.php');
    exit();
}

require_once '../config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if($id <= 0) {
    header('Location: search-books.php');
    exit();
}

// Fetch book data using prepared statement
$stmt = $conn->prepare("SELECT * FROM book WHERE book_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$book) {
    header('Location: search-books.php');
    exit();
}

$msg = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Security validation failed.";
    } else {
        $title = sanitizeInput($_POST['title']);
        $author = sanitizeInput($_POST['author']);
        $genre = $_POST['genre'];
        $total_copies = (int)$_POST['total_copies'];
        
        // Calculate new available_copies adjustment
        $old_copies = $book['total_copies'];
        $diff = $total_copies - $old_copies;
        
        // Update using prepared statement
        $update_stmt = $conn->prepare("UPDATE book SET title = ?, author = ?, genre = ?, total_copies = ?, available_copies = available_copies + ? WHERE book_id = ?");
        $update_stmt->bind_param("sssiii", $title, $author, $genre, $total_copies, $diff, $id);
        
        if($update_stmt->execute()) {
            $msg = " Book updated successfully!";
            logAdminAction($conn, $_SESSION['admin_id'], 'EDIT_BOOK', "Edited book ID $id: $title");
            // Refresh book data
            $book['title'] = $title;
            $book['author'] = $author;
            $book['genre'] = $genre;
            $book['total_copies'] = $total_copies;
        } else {
            $error = " Error updating book";
        }
        $update_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Book - LibTech Solutions</title>
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
    </style>
</head>
<body>
    <div class="form-container">
        <h2> Edit Book</h2>
        
        <?php if($msg): ?>
            <div class="success"><?php echo $msg; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <input type="text" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required>
            
            <input type="text" name="author" value="<?php echo htmlspecialchars($book['author']); ?>" required>
            
            <select name="genre">
                <option value="Fiction" <?php echo $book['genre'] == 'Fiction' ? 'selected' : ''; ?>>Fiction</option>
                <option value="Non-Fiction" <?php echo $book['genre'] == 'Non-Fiction' ? 'selected' : ''; ?>>Non-Fiction</option>
                <option value="Science" <?php echo $book['genre'] == 'Science' ? 'selected' : ''; ?>>Science</option>
                <option value="History" <?php echo $book['genre'] == 'History' ? 'selected' : ''; ?>>History</option>
                <option value="Technology" <?php echo $book['genre'] == 'Technology' ? 'selected' : ''; ?>>Technology</option>
            </select>
            
            <input type="number" name="total_copies" value="<?php echo $book['total_copies']; ?>" min="1" required>
            
            <button type="submit"> Update Book</button>
        </form>
        
        <a href="search-books.php">← Back to Search</a>
    </div>
</body>
</html>