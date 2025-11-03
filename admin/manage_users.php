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
    <link rel="stylesheet" href="../css/fonts/fonts.css">
    <link rel="stylesheet" href="../css/style.css">
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
    <!-- Header with Back Button and Title -->
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="font-size: 28px;">✨ Admin Panel - Manage Users</h1>
        <a href="dashboard.php" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; 
        color: white !important; 
        padding: 10px 20px !important; 
        border-radius: 20px !important; 
        font-weight: 600 !important; 
        text-transform: uppercase !important; 
        letter-spacing: 0.5px !important; 
        margin: 10px !important; 
        display: inline-block !important; 
        transition: all 0.3s ease !important;
        font-size: 14px !important;">
        <i class="fas fa-home"></i> Back to Dashboard
    </a>
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
        <span style="color: navy; padding: 8px 16px; border-radius: 20px; font-size: 18px; font-weight: 600;;">
            <?= isSuperAdmin() ? '👑 Super Admin' : '👤 Admin' ?>: <?= htmlspecialchars($_SESSION['username']) ?>
        </span>
        <a href="../logout.php" style="background: #f44336; color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; margin-left: 10px;">
            🚪 Logout
        </a>
    </div>

    <!-- <div class="footer-divider"></div>
    <footer class="footer" style="margin-top: 0; text-align: center;">
        &copy; <?= date('Y') ?> Developed and Maintained by WNL in collaboration with Web Publishing Department <br>
        © All rights reserved, 2008 - Wijeya Newspapers Ltd.
    </footer> -->

    <script>
        // Auto-hide success/error messages
        setTimeout(() => {
            document.querySelectorAll('div[style*="border-left"]').forEach(el => el.style.display = 'none');
        }, 2000);
    </script>

<div class="footer-divider"></div>
<?php include 'includes/footer.php'; ?>