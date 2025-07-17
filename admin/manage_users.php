<?php
include '../db.php';
include '../auth.php';

// Only super admin can access this page
checkAuth('super_admin');

// Handle user deletion
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Prevent super admin from deleting themselves
    if ($id == $_SESSION['user_id']) {
        $error = "You cannot delete your own account!";
    } else {
        $conn->query("DELETE FROM users WHERE id = $id");
        $success = "User deleted successfully!";
    }
}

// Handle adding new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    $created_by = getCurrentUserId();

    // Check if username already exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $checkStmt->bind_param("s", $username);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows > 0) {
        $error = "Username already exists!";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, password, role, created_by) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $username, $password, $role, $created_by);
        
        if ($stmt->execute()) {
            $success = "User added successfully!";
        } else {
            $error = "Error adding user!";
        }
    }
}

// Fetch all users
$result = $conn->query("
    SELECT u.id, u.username, u.role, u.created_at, 
           creator.username as created_by_username 
    FROM users u 
    LEFT JOIN users creator ON u.created_by = creator.id 
    ORDER BY u.created_at DESC
");

?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Users - Super Admin</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../images/logo.jpg" type="image/png">
</head>
<body class="admin-page">

    <div style="text-align: center; margin-bottom: 30px;">
        <h2>✨ Manage Users</h2>
        
    </div>

    <?php if (isset($error)): ?>
        <div style="background: #ffebee; color: #c62828; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #f44336;">
            <strong>⚠️ Error:</strong> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($success)): ?>
        <div style="background: #e8f5e8; color: #2e7d32; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #4caf50;">
            <strong>✅ Success:</strong> <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <!-- Add New User Form -->
    <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h3 style="margin-top: 0; color: #333;">➕ Add New User</h3>
        
        <form method="POST" style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Username:</label>
                <input type="text" name="username" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Password:</label>
                <input type="password" name="password" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
            </div>
            
            <div>
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Role:</label>
                <select name="role" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
                    <option value="">Select Role</option>
                    <option value="admin">Admin</option>
                    <option value="super_admin">Super Admin</option>
                </select>
            </div>
            
            <button type="submit" name="add_user" style="background: #2196f3; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600;">
                Add User
            </button>
        </form>
    </div>

    <!-- Users Table -->
    <div class="special-dates-table">
        <table>
            <thead>
                <tr>
                    <th>👤 Username</th>
                    <th>🏷️ Role</th>
                    <th>📅 Created</th>
                    <th>👨‍💼 Created By</th>
                    <th>⚡ Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td style="font-weight: 600;"><?= htmlspecialchars($row['username']) ?></td>
                    <td>
                        <span style="background: <?= $row['role'] === 'super_admin' ?>; color: black; padding: 4px 12px; border-radius: 15px; font-size: 14px; font-weight: 600;">
                            <?= $row['role'] === 'super_admin' ? '👑 Super Admin' : '👤 Admin' ?>
                        </span>
                    </td>
                    <td><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                    <td><?= htmlspecialchars($row['created_by_username'] ?? 'System') ?></td>
                    <td>
                        <?php if ($row['id'] != $_SESSION['user_id']): ?>
                            <a href="?delete=<?= $row['id'] ?>" 
                               onclick="return confirm('⚠️ Are you sure you want to delete user: <?= htmlspecialchars($row['username']) ?>?')"
                               style="background: #f44336; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px;">
                                🗑️ Delete
                            </a>
                        <?php else: ?>
                            <span style="color: #999; font-size: 12px;">Current User</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>


            <div style="margin-top: 10px;">
        <span style="background: <?= isSuperAdmin() ?>; color: white; padding: 8px 16px; border-radius: 20px; font-size: 18px; font-weight: 600;">
            <?= isSuperAdmin() ? '👑 Super Admin' : '👤 Admin' ?>: <?= htmlspecialchars($_SESSION['username']) ?>
        </span>
        <a href="../logout.php" style="background: #f44336; color: white; padding: 8px 16px; border-radius: 20px; font-size: 16px; font-weight: 600; text-decoration: none; margin-left: 10px;">
            🚪 Logout
        </a>
    </div>

    <footer class="footer">
        &copy; <?php echo date('Y'); ?> Developed and Maintained by Web Publishing Department in collaboration with WNL Time Office<br>
        © All rights reserved, 2008 - Wijeya Newspapers Ltd.
    </footer>

</body>
</html>