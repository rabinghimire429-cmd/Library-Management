<?php
// Start session to check if user is logged in
session_start();

// Check if admin is logged in AND has Librarian role (only librarians can edit)
if(!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'Librarian') {
    header('Location: ../index.php');
    exit();
}

// Include database configuration file
require_once '../config.php';

// Get book ID from URL parameter
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If ID is invalid, redirect back to search page
if($id <= 0) {
    header('Location: search-books.php');
    exit();
}

// Fetch current book data using prepared statement (prevents SQL injection)
$stmt = $conn->prepare("SELECT * FROM book WHERE book_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

// If book not found, redirect back to search page
if(!$book) {
    header('Location: search-books.php');
    exit();
}

// Variables for success and error messages
$msg = '';
$error = '';

// Check if form was submitted
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // CSRF Protection - security check to prevent fake requests
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Security validation failed.";
    } else {
        // Get and sanitize form input values
        $title = sanitizeInput($_POST['title']);
        $author = sanitizeInput($_POST['author']);
        $genre = $_POST['genre'];
        $total_copies = (int)$_POST['total_copies'];
        
        // Calculate how available copies should change
        $old_copies = $book['total_copies'];
        $diff = $total_copies - $old_copies;  // Positive = add copies, Negative = remove copies
        
        // Update book in database using prepared statement
        $update_stmt = $conn->prepare("UPDATE book SET title = ?, author = ?, genre = ?, total_copies = ?, available_copies = available_copies + ? WHERE book_id = ?");
        $update_stmt->bind_param("sssiii", $title, $author, $genre, $total_copies, $diff, $id);
        
        // Execute update and check if successful
        if($update_stmt->execute()) {
            $msg = "✓ Book updated successfully!";
            // Log the action for audit trail
            logAdminAction($conn, $_SESSION['admin_id'], 'EDIT_BOOK', "Edited book ID $id: $title");
            // Refresh book data in current page
            $book['title'] = $title;
            $book['author'] = $author;
            $book['genre'] = $genre;
            $book['total_copies'] = $total_copies;
        } else {
            $error = "✗ Error updating book";
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
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Reset default styles */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        /* Body with gradient background */
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #0f0c29, #302b63, #24243e); min-height: 100vh; color: #e4e6eb; display: flex; justify-content: center; align-items: center; }
        
        /* Form container with glassmorphism effect */
        .form-container { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 30px; padding: 40px; width: 500px; border: 1px solid rgba(255,255,255,0.2); }
        
        /* Heading gradient text */
        h2 { margin-bottom: 20px; text-align: center; background: linear-gradient(135deg, #fff, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        /* Input and select styling */
        input, select { width: 100%; padding: 12px; margin: 10px 0; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 12px; color: white; }
        
        /* Button styling */
        button { width: 100%; padding: 12px; background: linear-gradient(135deg, #6366f1, #ec4899); border: none; border-radius: 12px; color: white; cursor: pointer; font-weight: 600; }
        
        /* Link styling */
        a { color: #818cf8; display: block; margin-top: 15px; text-align: center; }
        
        /* Success message styling */
        .success { background: rgba(16,185,129,0.2); color: #34d399; padding: 12px; border-radius: 12px; margin-bottom: 15px; text-align: center; }
        
        /* Error message styling */
        .error { background: rgba(239,68,68,0.2); color: #f87171; padding: 12px; border-radius: 12px; margin-bottom: 15px; text-align: center; }
    </style>
</head>
<body>
    <div class="form-container">
        <!-- Page heading -->
        <h2>✏️ Edit Book</h2>
        
        <!-- Display success message if any -->
        <?php if($msg): ?>
            <div class="success"><?php echo $msg; ?></div>
        <?php endif; ?>
        
        <!-- Display error message if any -->
        <?php if($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Edit book form -->
        <form method="POST">
            <!-- CSRF token for security -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <!-- Book title input (pre-filled with current value) -->
            <input type="text" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required>
            
            <!-- Book author input (pre-filled with current value) -->
            <input type="text" name="author" value="<?php echo htmlspecialchars($book['author']); ?>" required>
            
            <!-- Genre dropdown (pre-selected with current genre) -->
            <select name="genre">
                <option value="Fiction" <?php echo $book['genre'] == 'Fiction' ? 'selected' : ''; ?>>Fiction</option>
                <option value="Non-Fiction" <?php echo $book['genre'] == 'Non-Fiction' ? 'selected' : ''; ?>>Non-Fiction</option>
                <option value="Science" <?php echo $book['genre'] == 'Science' ? 'selected' : ''; ?>>Science</option>
                <option value="History" <?php echo $book['genre'] == 'History' ? 'selected' : ''; ?>>History</option>
                <option value="Technology" <?php echo $book['genre'] == 'Technology' ? 'selected' : ''; ?>>Technology</option>
            </select>
            
            <!-- Total copies input (pre-filled with current value) -->
            <input type="number" name="total_copies" value="<?php echo $book['total_copies']; ?>" min="1" required>
            
            <!-- Submit button -->
            <button type="submit">💾 Update Book</button>
        </form>
        
        <!-- Back link to search page -->
        <a href="search-books.php">← Back to Search</a>
    </div>
</body>
</html>