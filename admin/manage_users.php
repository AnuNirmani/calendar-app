<?php
include '../db.php';
include '../auth.php';

checkAuth('super_admin');

// Auto logout after inactivity
$timeout = 900;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout) {
    session_unset();
    session_destroy();
    header("Location: ../login.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// Handle status toggle
if (isset($_GET['toggle_status'])) {
    $id = (int)$_GET['toggle_status'];
    if ($id == $_SESSION['user_id']) {
        $error = "You cannot deactivate your own account!";
    } else {
        // Get current status and toggle it
        $stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        $new_status = $user['status'] == 1 ? 0 : 1;
        
        $stmt = $conn->prepare("UPDATE users SET status = ?, edited_by = ?, edited_at = NOW() WHERE id = ?");
        $stmt->bind_param("iii", $new_status, $_SESSION['user_id'], $id);
        $stmt->execute();
        
        $status_text = $new_status == 1 ? "activated" : "deactivated";
        $success = "User " . $status_text . " successfully!";
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id == $_SESSION['user_id']) {
        $error = "You cannot delete your own account!";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $success = "User deleted successfully!";
    }
}

// Handle add user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    $created_by = getCurrentUserId();
    $status = 1; // New users are active by default

    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long!";
    } elseif (!preg_match("/[A-Za-z].*[0-9]|[0-9].*[A-Za-z]/", $password)) {
        $error = "Password must contain both letters and numbers!";
    } else {
        $password = password_hash($password, PASSWORD_DEFAULT);
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $checkStmt->bind_param("s", $username);
        $checkStmt->execute();

        if ($checkStmt->get_result()->num_rows > 0) {
            $error = "Username already exists!";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, password, role, created_by, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssii", $username, $password, $role, $created_by, $status);
            if ($stmt->execute()) {
                $success = "User added successfully!";
            } else {
                $error = "Error adding user!";
            }
        }
    }
}

// Fetch users
$result = $conn->query("
    SELECT u.id, u.username, u.role, u.status, u.created_at,
       creator.username as created_by_username,
       editor.username as edited_by_username,
       u.edited_at
FROM users u
LEFT JOIN users creator ON u.created_by = creator.id
LEFT JOIN users editor ON u.edited_by = editor.id
ORDER BY u.created_at DESC

");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Users - Super Admin</title>
    <link rel="stylesheet" href="../css/fonts.css">
    <link rel="stylesheet" href="../style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../images/logo.jpg" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .status-button {
            padding: 5px 12px;
            border: none;
            border-radius: 20px;
            color: white;
            font-weight: 600;
            text-decoration: none;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .status-active {
            background: linear-gradient(135deg, #4caf50, #45a049);
        }
        
        .status-active:hover {
            background: linear-gradient(135deg, #f44336, #f44336);
            transform: translateY(-1px);
        }
        
        .status-inactive {
            background: linear-gradient(135deg, #f44336, #e53935);
        }
        
        .status-inactive:hover {
            background: linear-gradient(135deg, #4caf50, #45a049);
            transform: translateY(-1px);
        }
    </style>
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

    <!-- Add User Form -->
    <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h3 style="margin-top: 0; color: #333;">➕ Add New User</h3>
        <form method="POST" style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
            <div>
                <label style="font-weight: 600;">Username:</label>
                <input type="text" name="username" required style="width: 100%; padding: 10px;">
            </div>
            <div>
                <label style="font-weight: 600;">Password:</label>
                <div style="position: relative;">
                    <input type="password" name="password" id="passwordInput" required style="width: 100%; padding: 10px 40px 10px 10px;">
                    <span onclick="togglePassword()" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                        <i class="fa-solid fa-eye" id="eyeIcon"></i>
                    </span>
                </div>
            </div>
            <div>
                <label style="font-weight: 600;">Role:</label>
                <select name="role" required style="width: 100%; padding: 10px;">
                    <option value="">Select Role</option>
                    <option value="admin">Admin</option>
                    <option value="super_admin">Super Admin</option>
                </select>
            </div>
            <button type="submit" name="add_user" style="background: #2196f3; color: white; padding: 10px 20px; border-radius: 5px;">Add User</button>
        </form>
    </div>

    <!-- User Table -->
    <div class="special-dates-table">
        <table>
            <thead>
                <tr style="background: linear-gradient(90deg, #6A5ACD, #7B68EE); color: white;">
                    <th class="col-username">👤 USERNAME</th>
                    <th class="col-role">🏷️ ROLE</th>
                    <th class="col-status">🔄 STATUS</th>
                    <th class="col-created-at">📅 CREATED AT</th>
                    <th class="col-created-by">👨‍💼 CREATED BY</th>
                    <th class="col-edited-at">✏️ EDITED AT</th>
                    <th class="col-edited-by">🧑 EDITED BY</th>
                    <th class="col-actions">⚡ ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td style="font-weight: 600;"><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= $row['role'] === 'super_admin' ? '👑 Super Admin' : '👤 Admin' ?></td>
                        <td>
                            <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                <a href="?toggle_status=<?= $row['id'] ?>" 
                                   class="status-button <?= $row['status'] == 1 ? 'status-active' : 'status-inactive' ?>"
                                   onclick="return confirm('Are you sure you want to <?= $row['status'] == 1 ? 'deactivate' : 'activate' ?> this user?')">
                                    <?= $row['status'] == 1 ? '✅ Active' : '❌ Inactive' ?>
                                </a>
                            <?php else: ?>
                                <span class="status-button status-active" style="cursor: not-allowed; opacity: 0.7;">
                                    ✅ Active
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('M j, Y', strtotime($row['created_at'])) ?></td>
                        <td><?= htmlspecialchars($row['created_by_username'] ?? 'System') ?></td>
                        <td><?= $row['edited_at'] ? date('M j, Y', strtotime($row['edited_at'])) : '-' ?></td>
                        <td><?= $row['edited_by_username'] ?? '-' ?></td>
                        <td>
                            <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                <a href="edit_user.php?id=<?= $row['id'] ?>" class="edit-button">✏️ Edit</a>
                                <a href="?delete=<?= $row['id'] ?>" class="delete-button" onclick="return confirm('⚠️ Are you sure you want to delete this user?')">🗑️ Delete</a>
                            <?php else: ?>
                                <span style="color: #999; font-size: 12px;">Current User</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- User Info + Logout -->
    <div style="margin-top: 10px;">
        <span style="color: white; padding: 8px 16px; border-radius: 20px; font-size: 18px; font-weight: 600;;">
            <?= isSuperAdmin() ? '👑 Super Admin' : '👤 Admin' ?>: <?= htmlspecialchars($_SESSION['username']) ?>
        </span>
        <a href="../logout.php" style="background: #f44336; color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; margin-left: 10px;">
            🚪 Logout
        </a>
    </div>

    <footer class="footer" style="margin-top: 30px; text-align: center;">
        &copy; <?= date('Y') ?> Developed by Web Publishing Dept. in collaboration with WNL Time Office<br>
        © All rights reserved, 2008 - Wijeya Newspapers Ltd.
    </footer>

    <script>
        setTimeout(() => {
            document.querySelectorAll('div[style*="border-left"]').forEach(el => el.style.display = 'none');
        }, 2000);

        function togglePassword() {
            const passwordInput = document.getElementById("passwordInput");
            const eyeIcon = document.getElementById("eyeIcon");

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                eyeIcon.classList.remove("fa-eye");
                eyeIcon.classList.add("fa-eye-slash");
            } else {
                passwordInput.type = "password";
                eyeIcon.classList.remove("fa-eye-slash");
                eyeIcon.classList.add("fa-eye");
            }
        }
    </script>
</body>
</html>