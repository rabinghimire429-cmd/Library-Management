<?php
/**
 * admin-management.php - Admin Management (Middle Layer + Presentation Layer)
 * Author: Rabin Ghimire
 * Module: Authentication & Dashboard
 * 
 * FUNCTIONALITY: Add, Edit, Delete, List, Find, Filter, Validate
 * 
 * Complete CRUD operations for managing admin users.
 */

session_start();

// FUNCTIONALITY: ACCESS CONTROL - Only Librarian can access
if(!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'Librarian') {
    header('Location: index.php');
    exit();
}

// Connect to database (Data Layer)
$conn = new mysqli('localhost', 'root', '', 'libtech_db');

// =============================================
// FUNCTIONALITY: DELETE - Remove admin user
// =============================================
if(isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $conn->query("DELETE FROM admin WHERE admin_id = $delete_id");
    header('Location: admin-management.php?msg=deleted');
    exit();
}

// =============================================
// FUNCTIONALITY: TOGGLE STATUS (Block/Activate)
// =============================================
if(isset($_GET['toggle'])) {
    $toggle_id = $_GET['toggle'];
    $conn->query("UPDATE admin SET is_active = NOT is_active WHERE admin_id = $toggle_id");
    header('Location: admin-management.php?msg=toggled');
    exit();
}

// =============================================
// FUNCTIONALITY: FIND & FILTER
// =============================================
$search = $_GET['search'] ?? '';
$filter_role = $_GET['filter_role'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';

// Build dynamic query based on search and filters
$query = "SELECT * FROM admin WHERE 1=1";
if($search) {
    $query .= " AND (email LIKE '%$search%' OR role LIKE '%$search%')";
}
if($filter_role && $filter_role !== 'all') {
    $query .= " AND role = '$filter_role'";
}
if($filter_status !== '' && $filter_status !== 'all') {
    $query .= " AND is_active = " . ($filter_status == 'active' ? 1 : 0);
}
$query .= " ORDER BY admin_id DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Management - LibTech Solutions</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); min-height: 100vh; }
        .header { background: rgba(255,255,255,0.95); padding: 15px 40px; display: flex; justify-content: space-between; }
        .logo-text { font-size: 22px; font-weight: bold; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .main-container { max-width: 1200px; margin: 40px auto; padding: 20px; }
        .back-link { display: inline-block; margin-bottom: 20px; color: white; background: rgba(255,255,255,0.2); padding: 8px 20px; border-radius: 25px; text-decoration: none; }
        .card { background: white; border-radius: 20px; padding: 30px; }
        .card h2 { color: #764ba2; margin-bottom: 10px; }
        .action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .btn-add { background: #10b981; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; }
        .search-box { display: flex; gap: 10px; flex: 1; max-width: 400px; }
        .search-box input { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        .search-box button { padding: 10px 20px; background: #764ba2; color: white; border: none; border-radius: 8px; cursor: pointer; }
        .filter-box { display: flex; gap: 10px; }
        .filter-box select { padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e0e0e0; }
        th { background: #f8f9fa; color: #764ba2; }
        .status-active { color: #10b981; font-weight: 600; }
        .status-inactive { color: #dc2626; font-weight: 600; }
        .btn-edit { background: #764ba2; color: white; padding: 5px 12px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 12px; }
        .btn-delete { background: #dc2626; color: white; padding: 5px 12px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 12px; }
        .btn-toggle { background: #f59e0b; color: white; padding: 5px 12px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 12px; }
        .success-msg { background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .footer { text-align: center; padding: 20px; color: rgba(255,255,255,0.7); background: rgba(0,0,0,0.3); margin-top: 40px; }
    </style>
</head>
<body>
    <div class="header"><div class="logo-text">LibTech Solutions</div></div>
    <div class="main-container">
        <a href="librarian-dashboard.php" class="back-link">← Back to Dashboard</a>
        <div class="card">
            <h2>👥 Admin Management</h2>
            <p>Manage system users - Add, Edit, Delete, List, Find, Filter</p>

            <?php if(isset($_GET['msg'])): ?>
                <div class="success-msg">✅ Operation completed successfully!</div>
            <?php endif; ?>

            <div class="action-bar">
                <button class="btn-add" onclick="openAddModal()">+ Add New Admin</button>
                <div class="search-box">
                    <form method="GET" style="display: flex; gap: 10px; width: 100%;">
                        <input type="text" name="search" placeholder="Find by email or role..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit">🔍 Find</button>
                    </form>
                </div>
                <div class="filter-box">
                    <form method="GET" style="display: flex; gap: 10px;">
                        <select name="filter_role" onchange="this.form.submit()">
                            <option value="all">All Roles</option>
                            <option value="Member" <?php echo $filter_role == 'Member' ? 'selected' : ''; ?>>Member</option>
                            <option value="Librarian" <?php echo $filter_role == 'Librarian' ? 'selected' : ''; ?>>Librarian</option>
                        </select>
                        <select name="filter_status" onchange="this.form.submit()">
                            <option value="all">All Status</option>
                            <option value="active" <?php echo $filter_status == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $filter_status == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </form>
                </div>
            </div>

            <!-- FUNCTIONALITY: LIST - Display all admins in table -->
            <table>
                <thead><tr><th>ID</th><th>Email</th><th>Role</th><th>Last Login</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['admin_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo $row['role']; ?></td>
                        <td><?php echo $row['last_login'] ?? 'Never'; ?></td>
                        <td class="<?php echo $row['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                            <?php echo $row['is_active'] ? 'Active' : 'Blocked'; ?>
                        </td>
                        <td>
                            <button class="btn-edit" onclick="openEditModal(<?php echo $row['admin_id']; ?>, '<?php echo $row['email']; ?>', '<?php echo $row['role']; ?>')">Edit</button>
                            <a href="?toggle=<?php echo $row['admin_id']; ?>" class="btn-toggle" onclick="return confirm('Toggle status?')"><?php echo $row['is_active'] ? 'Block' : 'Activate'; ?></a>
                            <a href="?delete=<?php echo $row['admin_id']; ?>" class="btn-delete" onclick="return confirm('Delete this admin?')">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ADD MODAL -->
    <div id="addModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; align-items:center; justify-content:center;">
        <div style="background:white; padding:30px; border-radius:20px; width:500px;">
            <h3>Add New Admin</h3>
            <form method="POST" action="admin-process.php">
                <input type="email" name="email" placeholder="Email" required style="width:100%; padding:10px; margin:10px 0;">
                <input type="password" name="password" placeholder="Password" required style="width:100%; padding:10px; margin:10px 0;">
                <select name="role" style="width:100%; padding:10px; margin:10px 0;">
                    <option value="Member">Member</option>
                    <option value="Librarian">Librarian</option>
                </select>
                <button type="submit" name="add" style="background:#764ba2; color:white; padding:10px; width:100%; border:none; cursor:pointer;">Add Admin</button>
            </form>
            <button onclick="closeAddModal()" style="margin-top:10px; width:100%; padding:10px;">Cancel</button>
        </div>
    </div>

    <div class="footer"><p>© 2026 LibTech Solutions | Secure Library Management System</p></div>

    <script>
        function openAddModal() { document.getElementById('addModal').style.display = 'flex'; }
        function closeAddModal() { document.getElementById('addModal').style.display = 'none'; }
        function openEditModal(id, email, role) {
            // Implement edit functionality
            window.location.href = 'admin-process.php?edit=' + id;
        }
    </script>
</body>
</html>