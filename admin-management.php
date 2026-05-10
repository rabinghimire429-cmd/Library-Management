<?php
session_start();
if(!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'Librarian') {
    header('Location: index.php');
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'libtech_db');

// Delete admin
if(isset($_GET['delete'])) {
    $delete_id = $_GET['delete'];
    $conn->query("DELETE FROM admin WHERE User_id = $delete_id");
    header('Location: admin-management.php?msg=deleted');
    exit();
}

// Toggle status (Block/Activate)
if(isset($_GET['toggle'])) {
    $toggle_id = $_GET['toggle'];
    $conn->query("UPDATE admin SET Is_active = NOT Is_active WHERE User_id = $toggle_id");
    header('Location: admin-management.php?msg=toggled');
    exit();
}

// Search and Filter
$search = $_GET['search'] ?? '';
$filter_role = $_GET['filter_role'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';

$query = "SELECT * FROM admin WHERE 1=1";
if($search) {
    $query .= " AND (Email LIKE '%$search%' OR Role LIKE '%$search%')";
}
if($filter_role && $filter_role !== 'all') {
    $query .= " AND Role = '$filter_role'";
}
if($filter_status !== '' && $filter_status !== 'all') {
    $query .= " AND Is_active = " . ($filter_status == 'active' ? 1 : 0);
}
$query .= " ORDER BY User_id DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management - LibTech Solutions</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
        }
        .header {
            background: rgba(255,255,255,0.95);
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .logo-area { display: flex; align-items: center; gap: 15px; }
        .logo-area img { height: 50px; width: auto; border-radius: 10px; }
        .logo-text { font-size: 22px; font-weight: bold; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .main-container { max-width: 1400px; margin: 40px auto; padding: 20px; }
        .back-link { display: inline-block; margin-bottom: 20px; color: white; background: rgba(255,255,255,0.2); padding: 8px 20px; border-radius: 25px; text-decoration: none; }
        .card { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .card h2 { color: #764ba2; margin-bottom: 10px; }
        .action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; gap: 15px; }
        .btn-add { background: #10b981; color: white; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; text-decoration: none; }
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
        .btn-edit { background: #764ba2; color: white; padding: 5px 12px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; font-size: 12px; }
        .btn-delete { background: #dc2626; color: white; padding: 5px 12px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; font-size: 12px; }
        .btn-toggle { background: #f59e0b; color: white; padding: 5px 12px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; font-size: 12px; }
        .success-msg { background: #d4edda; color: #155724; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 30px; border-radius: 20px; width: 500px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .close-btn { background: none; border: none; font-size: 24px; cursor: pointer; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        .error-msg { color: #dc2626; font-size: 12px; margin-top: 5px; display: none; }
        .footer { text-align: center; padding: 20px; color: rgba(255,255,255,0.7); background: rgba(0,0,0,0.3); margin-top: 40px; }
        @media (max-width: 700px) {
            .header { padding: 12px 20px; }
            .main-container { padding: 20px; }
            .action-bar { flex-direction: column; align-items: stretch; }
            .search-box { max-width: 100%; }
            table { font-size: 12px; }
            th, td { padding: 8px; }
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="logo-area">
            <img src="Picture2.jpg" alt="Logo" onerror="this.style.display='none'">
            <span class="logo-text">LibTech Solutions</span>
        </div>
    </div>

    <div class="main-container">
        <a href="librarian-dashboard.php" class="back-link">← Back to Dashboard</a>

        <div class="card">
            <h2>👥 Admin Management</h2>
            

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

            <!-- TABLE WITH CREATED_AT COLUMN -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created At</th>
                            <th>Last Login</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['User_id']; ?></td>
                            <td><?php echo htmlspecialchars($row['Email']); ?></td>
                            <td><?php echo $row['Role']; ?></td>
                            <td><?php echo $row['Created_at']; ?></td>
                            <td><?php echo $row['Last_login'] ?? 'Never'; ?></td>
                            <td class="<?php echo $row['Is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                <?php echo $row['Is_active'] ? 'Active' : 'Blocked'; ?>
                            </td>
                            <td>
                                <button class="btn-edit" onclick="openEditModal(<?php echo $row['User_id']; ?>, '<?php echo $row['Email']; ?>', '<?php echo $row['Role']; ?>')">Edit</button>
                                <a href="?toggle=<?php echo $row['User_id']; ?>" class="btn-toggle" onclick="return confirm('Toggle status?')"><?php echo $row['Is_active'] ? 'Block' : 'Activate'; ?></a>
                                <a href="?delete=<?php echo $row['User_id']; ?>" class="btn-delete" onclick="return confirm('Delete this admin?')">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ADD MODAL -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Admin</h3>
                <button class="close-btn" onclick="closeModal('addModal')">&times;</button>
            </div>
            <form method="POST" action="admin-process.php" onsubmit="return validateForm()">
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" id="add_email" name="email" required>
                    <div class="error-msg" id="add_email_error">Valid email required</div>
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" id="add_password" name="password" required>
                    <div class="error-msg" id="add_password_error">Password must be at least 4 characters</div>
                </div>
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" id="add_confirm" name="confirm_password" required>
                </div>
                <div class="form-group">
                    <label>Role *</label>
                    <select name="role">
                        <option value="Member">Member</option>
                        <option value="Librarian">Librarian</option>
                    </select>
                </div>
                <button type="submit" name="add" class="btn-add" style="width:100%;">Add Admin</button>
            </form>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Admin</h3>
                <button class="close-btn" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST" action="admin-process.php">
                <input type="hidden" id="edit_id" name="admin_id">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select id="edit_role" name="role">
                        <option value="Member">Member</option>
                        <option value="Librarian">Librarian</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>New Password (leave blank to keep current)</label>
                    <input type="password" name="new_password">
                </div>
                <button type="submit" name="edit" class="btn-add" style="width:100%;">Update Admin</button>
            </form>
        </div>
    </div>

    <div class="footer">
        <p>© 2026 LibTech Solutions | Secure Library Management System</p>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }

        function openEditModal(id, email, role) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_email').value = email;
            document.getElementById('edit_role').value = role;
            document.getElementById('editModal').classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function validateForm() {
            const email = document.getElementById('add_email').value;
            const password = document.getElementById('add_password').value;
            const confirm = document.getElementById('add_confirm').value;
            let isValid = true;

            if(!email.includes('@')) {
                document.getElementById('add_email_error').style.display = 'block';
                document.getElementById('add_email').classList.add('error');
                isValid = false;
            } else {
                document.getElementById('add_email_error').style.display = 'none';
                document.getElementById('add_email').classList.remove('error');
            }

            if(password.length < 4) {
                document.getElementById('add_password_error').style.display = 'block';
                document.getElementById('add_password').classList.add('error');
                isValid = false;
            } else {
                document.getElementById('add_password_error').style.display = 'none';
                document.getElementById('add_password').classList.remove('error');
            }

            if(password !== confirm) {
                alert('Passwords do not match!');
                isValid = false;
            }

            return isValid;
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>
</html>