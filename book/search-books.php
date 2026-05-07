<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'libtech_db');
$search = $_GET['search'] ?? '';
$genre = $_GET['genre'] ?? 'all';
$query = "SELECT * FROM book WHERE 1=1";
if($search) $query .= " AND (title LIKE '%$search%' OR author LIKE '%$search%')";
if($genre != 'all') $query .= " AND genre = '$genre'";
$result = $conn->query($query);
$is_librarian = $_SESSION['admin_role'] == 'Librarian';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Books</title>
    <style>
        body { font-family: Arial; background: #0a0a2a; color: white; padding: 20px; }
        .container { max-width: 1200px; margin: auto; background: rgba(255,255,255,0.1); padding: 20px; border-radius: 20px; }
        .search-box { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-box input, select { padding: 10px; background: rgba(255,255,255,0.2); border: none; border-radius: 12px; color: white; }
        .search-box button { padding: 10px 20px; background: #6366f1; border: none; border-radius: 12px; color: white; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.2); }
        th { background: #6366f1; }
        .available { color: #34d399; }
        a { color: #818cf8; }
    </style>
</head>
<body>
    <div class="container">
        <a href="<?php echo $is_librarian ? '../librarian-dashboard.php' : '../member-dashboard.php'; ?>">← Back</a>
        <h2> Search Books</h2>
        <div class="search-box">
            <form method="GET">
                <input type="text" name="search" placeholder="Search..." value="<?php echo $search; ?>">
                <select name="genre"><option value="all">All</option><option>Fiction</option><option>Non-Fiction</option></select>
                <button type="submit">Search</button>
            </form>
        </div>
        <table><thead><tr><th>Title</th><th>Author</th><th>Genre</th><th>Copies</th><th>Available</th><th>Status</th><?php if($is_librarian): ?><th>Actions</th><?php endif; ?></tr></thead>
        <tbody><?php while($row = $result->fetch_assoc()): ?>
        <tr><td><?php echo $row['title']; ?></td><td><?php echo $row['author']; ?></td><td><?php echo $row['genre']; ?></td><td><?php echo $row['total_copies']; ?></td><td><?php echo $row['available_copies']; ?></td><td class="<?php echo $row['available_copies'] > 0 ? 'available' : ''; ?>"><?php echo $row['available_copies'] > 0 ? 'Available' : 'Not Available'; ?></td>
        <?php if($is_librarian): ?><td><a href="edit-book.php?id=<?php echo $row['book_id']; ?>">Edit</a> | <a href="delete-book.php?id=<?php echo $row['book_id']; ?>" onclick="return confirm('Delete?')">Delete</a></td><?php endif; ?>
        </tr><?php endwhile; ?></tbody>
        </table>
    </div>
</body>
</html>