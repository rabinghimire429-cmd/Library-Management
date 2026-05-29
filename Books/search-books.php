<?php
// Start session to check if user is logged in
session_start();

// Check if admin is logged in, if not redirect to login page
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

// Include database configuration file
require_once '../config.php';

// Get search and filter values from URL parameters
$search = $_GET['search'] ?? '';
$genre = $_GET['genre'] ?? 'all';

// Build SQL query to search books
$query = "SELECT * FROM book WHERE 1=1";
$params = [];
$types = "";

// If search term is entered, add title and author search
if(!empty($search)) {
    $query .= " AND (title LIKE ? OR author LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

// If genre filter is selected, add genre filter
if($genre != 'all') {
    $query .= " AND genre = ?";
    $params[] = $genre;
    $types .= "s";
}

// Order results by title in ascending order
$query .= " ORDER BY title ASC";

// Prepare SQL statement to prevent SQL injection
$stmt = $conn->prepare($query);

// Bind parameters if they exist
if(!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

// Execute the query
$stmt->execute();

// Get the result set
$result = $stmt->get_result();

// Check if logged in user is librarian to show edit/delete buttons
$is_librarian = $_SESSION['admin_role'] == 'Librarian';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Books - LibTech Solutions</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Reset default styles */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        /* Body background gradient */
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #0f0c29, #302b63, #24243e); min-height: 100vh; color: #e4e6eb; padding: 20px; }
        
        /* Main container with glassmorphism effect */
        .container { max-width: 1200px; margin: auto; background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); border-radius: 30px; padding: 30px; }
        
        /* Heading gradient text */
        h2 { margin-bottom: 25px; background: linear-gradient(135deg, #fff, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        
        /* Search box styling */
        .search-box { display: flex; gap: 15px; margin-bottom: 30px; flex-wrap: wrap; }
        .search-box input, .search-box select { padding: 12px 20px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 40px; color: white; }
        .search-box input { flex: 1; min-width: 200px; }
        .search-box button { padding: 12px 30px; background: linear-gradient(135deg, #6366f1, #ec4899); border: none; border-radius: 40px; color: white; cursor: pointer; }
        
        /* Table styling */
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); }
        th { background: rgba(99,102,241,0.2); color: #818cf8; }
        
        /* Availability status colors */
        .available { color: #34d399; font-weight: 600; }
        .not-available { color: #f87171; font-weight: 600; }
        
        /* Action buttons styling */
        .actions a { color: #818cf8; text-decoration: none; margin-right: 10px; }
        .actions a:hover { text-decoration: underline; }
        
        /* Back link styling */
        .back-link { color: #818cf8; text-decoration: none; display: inline-block; margin-bottom: 20px; }
        
        /* Success message styling */
        .success-msg { background: rgba(16,185,129,0.2); color: #34d399; padding: 12px; border-radius: 12px; margin-bottom: 20px; text-align: center; }
        
        /* Responsive design for mobile */
        @media (max-width: 768px) { th, td { padding: 10px; font-size: 12px; } .container { padding: 20px; } }
    </style>
</head>
<body>
    <div class="container">
        <!-- Back button to dashboard -->
        <a href="<?php echo $is_librarian ? '../librarian-dashboard.php' : '../member-dashboard.php'; ?>" class="back-link">← Back to Dashboard</a>
        
        <!-- Page heading -->
        <h2><i class="fas fa-search"></i> Search Books</h2>
        
        <!-- Success message after delete -->
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <div class="success-msg">✓ Book deleted successfully!</div>
        <?php endif; ?>
        
        <!-- Search and filter form -->
        <div class="search-box">
            <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; width: 100%;">
                <!-- Search input field -->
                <input type="text" name="search" placeholder="Search by title or author..." value="<?php echo htmlspecialchars($search); ?>">
                
                <!-- Genre filter dropdown -->
                <select name="genre">
                    <option value="all" <?php echo $genre == 'all' ? 'selected' : ''; ?>>All Genres</option>
                    <option value="Fiction" <?php echo $genre == 'Fiction' ? 'selected' : ''; ?>>Fiction</option>
                    <option value="Non-Fiction" <?php echo $genre == 'Non-Fiction' ? 'selected' : ''; ?>>Non-Fiction</option>
                    <option value="Science" <?php echo $genre == 'Science' ? 'selected' : ''; ?>>Science</option>
                    <option value="History" <?php echo $genre == 'History' ? 'selected' : ''; ?>>History</option>
                    <option value="Technology" <?php echo $genre == 'Technology' ? 'selected' : ''; ?>>Technology</option>
                </select>
                
                <!-- Search button -->
                <button type="submit"><i class="fas fa-search"></i> Search</button>
                
                <!-- Clear button to reset filters (only shows when filter is active) -->
                <?php if($search || $genre != 'all'): ?>
                    <a href="search-books.php" style="padding: 12px 20px; background: rgba(255,255,255,0.1); border-radius: 40px; text-decoration: none; color: white;">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Books table -->
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Genre</th>
                    <th>Copies</th>
                    <th>Available</th>
                    <th>Status</th>
                    <?php if($is_librarian): ?>
                        <th>Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <!-- Check if books found -->
                <?php if($result->num_rows > 0): ?>
                    <!-- Loop through each book and display -->
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['author']); ?></td>
                            <td><?php echo $row['genre']; ?></td>
                            <td><?php echo $row['total_copies']; ?></td>
                            <td><?php echo $row['available_copies']; ?></td>
                            <!-- Show available or not available status -->
                            <td class="<?php echo $row['available_copies'] > 0 ? 'available' : 'not-available'; ?>">
                                <?php echo $row['available_copies'] > 0 ? '✓ Available' : '✗ Not Available'; ?>
                            </td>
                            <!-- Show edit/delete buttons only for librarian -->
                            <?php if($is_librarian): ?>
                                <td class="actions">
                                    <a href="edit-book.php?id=<?php echo $row['book_id']; ?>"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="delete-book.php?id=<?php echo $row['book_id']; ?>" onclick="return confirm('Delete this book?')"><i class="fas fa-trash"></i> Delete</a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <!-- No books found message -->
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px;">
                            <i class="fas fa-book-open" style="font-size: 48px; color: #4b5563;"></i>
                            <p>No books found matching your search.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>