<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../config.php';

$search = $_GET['search'] ?? '';
$genre = $_GET['genre'] ?? 'all';

// Secure query with prepared statement
$query = "SELECT * FROM book WHERE 1=1";
$params = [];
$types = "";

if(!empty($search)) {
    $query .= " AND (title LIKE ? OR author LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

if($genre != 'all') {
    $query .= " AND genre = ?";
    $params[] = $genre;
    $types .= "s";
}

$query .= " ORDER BY title ASC";

$stmt = $conn->prepare($query);
if(!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$is_librarian = $_SESSION['admin_role'] == 'Librarian';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Books - LibTech Solutions</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #0f0c29, #302b63, #24243e); min-height: 100vh; color: #e4e6eb; padding: 20px; }
        .container { max-width: 1200px; margin: auto; background: rgba(255,255,255,0.05); backdrop-filter: blur(10px); border-radius: 30px; padding: 30px; }
        h2 { margin-bottom: 25px; background: linear-gradient(135deg, #fff, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .search-box { display: flex; gap: 15px; margin-bottom: 30px; flex-wrap: wrap; }
        .search-box input, .search-box select { padding: 12px 20px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); border-radius: 40px; color: white; }
        .search-box input { flex: 1; min-width: 200px; }
        .search-box button { padding: 12px 30px; background: linear-gradient(135deg, #6366f1, #ec4899); border: none; border-radius: 40px; color: white; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); }
        th { background: rgba(99,102,241,0.2); color: #818cf8; }
        .available { color: #34d399; font-weight: 600; }
        .not-available { color: #f87171; font-weight: 600; }
        .actions a { color: #818cf8; text-decoration: none; margin-right: 10px; }
        .actions a:hover { text-decoration: underline; }
        .back-link { color: #818cf8; text-decoration: none; display: inline-block; margin-bottom: 20px; }
        .success-msg { background: rgba(16,185,129,0.2); color: #34d399; padding: 12px; border-radius: 12px; margin-bottom: 20px; text-align: center; }
        @media (max-width: 768px) { th, td { padding: 10px; font-size: 12px; } .container { padding: 20px; } }
    </style>
</head>
<body>
    <div class="container">
        <a href="<?php echo $is_librarian ? '../librarian-dashboard.php' : '../member-dashboard.php'; ?>" class="back-link">← Back to Dashboard</a>
        
        <h2><i class="fas fa-search"></i> Search Books</h2>
        
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <div class="success-msg"> Book deleted successfully!</div>
        <?php endif; ?>
        
        <div class="search-box">
            <form method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; width: 100%;">
                <input type="text" name="search" placeholder="Search by title or author..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="genre">
                    <option value="all" <?php echo $genre == 'all' ? 'selected' : ''; ?>>All Genres</option>
                    <option value="Fiction" <?php echo $genre == 'Fiction' ? 'selected' : ''; ?>>Fiction</option>
                    <option value="Non-Fiction" <?php echo $genre == 'Non-Fiction' ? 'selected' : ''; ?>>Non-Fiction</option>
                    <option value="Science" <?php echo $genre == 'Science' ? 'selected' : ''; ?>>Science</option>
                    <option value="History" <?php echo $genre == 'History' ? 'selected' : ''; ?>>History</option>
                    <option value="Technology" <?php echo $genre == 'Technology' ? 'selected' : ''; ?>>Technology</option>
                </select>
                <button type="submit"><i class="fas fa-search"></i> Search</button>
                <?php if($search || $genre != 'all'): ?>
                    <a href="search-books.php" style="padding: 12px 20px; background: rgba(255,255,255,0.1); border-radius: 40px; text-decoration: none; color: white;">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        
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
                <?php if($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                            <td><?php echo htmlspecialchars($row['author']); ?></td>
                            <td><?php echo $row['genre']; ?></td>
                            <td><?php echo $row['total_copies']; ?></td>
                            <td><?php echo $row['available_copies']; ?></td>
                            <td class="<?php echo $row['available_copies'] > 0 ? 'available' : 'not-available'; ?>">
                                <?php echo $row['available_copies'] > 0 ? '✅ Available' : '❌ Not Available'; ?>
                            </td>
                            <?php if($is_librarian): ?>
                                <td class="actions">
                                    <a href="edit-book.php?id=<?php echo $row['book_id']; ?>"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="delete-book.php?id=<?php echo $row['book_id']; ?>" onclick="return confirm('Delete this book?')"><i class="fas fa-trash"></i> Delete</a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
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