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

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $new_role = $_POST['role'];
    $edited_by = getCurrentUserId();
    $edited_at = date('Y-m-d H:i:s');

    if ($new_password !== $confirm_password) {
        $error = "❌ Passwords do not match!";
    } elseif (strlen($new_password) < 8) {
        $error = "❌ Password must be at least 8 characters!";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users SET password = ?, role = ?, edited_by = ?, edited_at = ? WHERE id = ?");
        $stmt->bind_param("ssisi", $hashed_password, $new_role, $edited_by, $edited_at, $id);
        $stmt->execute();

        header("Location: manage_users.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit User - Super Admin</title>
    <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../images/logo.jpg" type="image/png">
</head>
<body class="admin-page">
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="font-size: 28px;">✏️ Edit User</h1>
    </div>

    <?php if ($error): ?>
        <div style="background: #ffebee; color: #c62828; padding: 15px; border-radius: 8px; margin: auto; max-width: 500px; margin-bottom: 20px;">
            <strong><?= $error ?></strong>
        </div>
    <?php endif; ?>

    <form method="POST" style="max-width: 500px; margin: auto;">
        <label>Username:</label>
        <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled style="background-color: #f3f3f3;">

        <label>New Password:</label>
<div style="position: relative; margin-bottom: 15px;">
    <input type="password" name="new_password" id="new_password"
           required placeholder="Enter new password"
           style="width: 100%; padding: 16px 45px 16px 15px; font-size: 16px; border-radius: 8px;">
    <span onclick="togglePassword('new_password', 'eye1')" 
          style="position: absolute; top: 50%; right: 15px; transform: translateY(-50%); cursor: pointer;">
        <i class="fa-solid fa-eye" id="eye1"></i>
    </span>
</div>

        <label>Confirm Password:</label>
<div style="position: relative;">
    <input type="password" name="confirm_password" id="confirm_password"
           required placeholder="Confirm new password"
           style="width: 100%; padding: 16px 45px 16px 15px; font-size: 16px; border-radius: 8px;">
    <span onclick="togglePassword('confirm_password', 'eye2')" 
          style="position: absolute; top: 50%; right: 15px; transform: translateY(-50%); cursor: pointer;">
        <i class="fa-solid fa-eye" id="eye2"></i>
    </span>
</div>

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

<script>
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);

    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("fa-eye");
        icon.classList.add("fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.remove("fa-eye-slash");
        icon.classList.add("fa-eye");
    }
}
</script>

</html>
