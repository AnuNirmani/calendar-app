<?php
include '../db.php';
include '../auth.php';
checkAuth('super_admin');

// Auto logout
$timeout = 900;
if (isset($_SESSION['LAST_ACTIVITY']) && time() - $_SESSION['LAST_ACTIVITY'] > $timeout) {
    session_unset();
    session_destroy();
    header("Location: ../login.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

if (!isset($_GET['id'])) {
    header("Location: manage_users.php");
    exit;
}

$id = (int)$_GET['id'];

// Get current user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: manage_users.php");
    exit;
}

// Update logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = $_POST['username'];
    $new_role = $_POST['role'];
    $edited_by = getCurrentUserId();
    $edited_at = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("UPDATE users SET username = ?, role = ?, edited_by = ?, edited_at = ? WHERE id = ?");
    $stmt->bind_param("ssisi", $new_username, $new_role, $edited_by, $edited_at, $id);
    $stmt->execute();

    header("Location: manage_users.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Edit User</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../images/logo.jpg" type="image/png">
</head>
<body class="admin-page">
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="font-size: 28px;">✏️ Edit User</h1>
    </div>

    <form method="POST" style="max-width: 500px; margin: auto;">
        <label>Username:</label>
        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>

        <label>Role:</label>
        <select name="role" required>
            <option value="">Select Role</option>
            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
            <option value="super_admin" <?= $user['role'] === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
        </select>

        <button type="submit" style="margin-top: 20px;">💾 Save Changes</button>
    </form>

    <div style="margin-top: 20px; text-align: center;">
        <span style="color: white; padding: 8px 16px; border-radius: 20px; font-size: 18px; font-weight: 600;">
            <?= isSuperAdmin() ? '👑 Super Admin' : '👤 Admin' ?>: <?= htmlspecialchars($_SESSION['username']) ?>
        </span>
        <a href="../logout.php" style="background: #f44336; color: white; padding: 8px 16px; border-radius: 20px; font-size: 16px; font-weight: 600; text-decoration: none; margin-left: 10px;">
            🚪 Logout
        </a>
    </div>

    <footer class="footer">
        © <?= date('Y'); ?> Developed and Maintained by Web Publishing Department in collaboration with WNL Time Office<br>
        © All rights reserved, 2008 - Wijeya Newspapers Ltd.
    </footer>
    
</body>
</html>
