<?php
include '../db.php';
include '../auth.php';

checkAuth('super_admin');

/* Auto logout */
$timeout = 900;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout) {
    session_unset();
    session_destroy();
    header("Location: ../login.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

/* Validate ID */
if (!isset($_GET['id'])) {
    header("Location: manage_users.php");
    exit;
}

$id = (int)$_GET['id'];

/* Fetch user */
$stmt = $conn->prepare("SELECT id, username, role FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: manage_users.php");
    exit;
}

$error = '';
$success = '';

/* Handle update */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $role = $_POST['role'];
    $edited_by = getCurrentUserId();
    $edited_at = date('Y-m-d H:i:s');

    if (empty($role)) {
        $error = "Role is required!";
    }

    // If password entered → validate
    if (!empty($new_password)) {
        if ($new_password !== $confirm_password) {
            $error = "Passwords do not match!";
        } elseif (strlen($new_password) < 8) {
            $error = "Password must be at least 8 characters!";
        } elseif (!preg_match("/[A-Za-z]/", $new_password) || !preg_match("/[0-9]/", $new_password)) {
            $error = "Password must contain letters and numbers!";
        }
    }

    if (!$error) {
        if (!empty($new_password)) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("
                UPDATE users 
                SET password=?, role=?, edited_by=?, edited_at=? 
                WHERE id=?
            ");
            $stmt->bind_param("ssisi", $hashed, $role, $edited_by, $edited_at, $id);
        } else {
            // Password unchanged
            $stmt = $conn->prepare("
                UPDATE users 
                SET role=?, edited_by=?, edited_at=? 
                WHERE id=?
            ");
            $stmt->bind_param("sisi", $role, $edited_by, $edited_at, $id);
        }

        if ($stmt->execute()) {
            $success = "User updated successfully!";
            header("refresh:2;url=manage_users.php");
        } else {
            $error = "Failed to update user!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit User - Super Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" href="../images/logo.jpg">

<link rel="stylesheet" href="../assets/css/fontawesome.min.css">
<script src="../assets/js/tailwind.js"></script>
<script src="../assets/js/jquery.min.js"></script>
<script src="../assets/js/jquery.validate.min.js"></script>

<style>
input.error, select.error {
    border-color:#ef4444 !important;
}
.error-message {
    background:#fee2e2;
    color:#991b1b;
    padding:8px;
    border-radius:6px;
    margin-top:6px;
    font-size:13px;
}
</style>
</head>

<body class="bg-gray-100 font-sans">
<div class="flex min-h-screen">

<?php include __DIR__.'/includes/slidebar2.php'; ?>

<div class="flex-1 p-8">
<h1 class="text-3xl font-bold text-center mb-6">✏️ Edit User</h1>

<?php if ($error): ?>
<div class="max-w-xl mx-auto bg-red-100 p-4 rounded mb-4"><?= $error ?></div>
<?php endif; ?>

<?php if ($success): ?>
<div class="max-w-xl mx-auto bg-green-100 p-4 rounded mb-4"><?= $success ?></div>
<?php endif; ?>

<div class="max-w-xl mx-auto bg-white shadow rounded-lg p-6">
<form method="POST" id="editUserForm" class="space-y-5">

<!-- Username -->
<div>
<label class="text-sm font-semibold">Username</label>
<input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled
class="w-full border rounded px-3 py-2 bg-gray-100">
</div>

<!-- Current Password (masked) -->
<div>
<label class="text-sm font-semibold">Current Password</label>
<input type="text" value="******** (hidden)" disabled
class="w-full border rounded px-3 py-2 bg-gray-100 text-gray-500">
</div>

<!-- New Password -->
<div>
<label class="text-sm font-semibold">New Password (optional)</label>
<input type="password" name="new_password" id="new_password"
placeholder="Leave blank to keep current password"
class="w-full border rounded px-3 py-2">
</div>

<!-- Confirm -->
<div>
<label class="text-sm font-semibold">Confirm Password</label>
<input type="password" name="confirm_password"
class="w-full border rounded px-3 py-2">
</div>

<!-- Role -->
<div>
<label class="text-sm font-semibold">Role</label>
<select name="role" class="w-full border rounded px-3 py-2">
<option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Admin</option>
<option value="super_admin" <?= $user['role']=='super_admin'?'selected':'' ?>>Super Admin</option>
</select>
</div>

<button class="w-full bg-sky-500 text-white py-3 rounded font-semibold">
<i class="fas fa-save"></i> Save Changes
</button>

</form>
</div>
</div>
</div>

<?php include __DIR__.'/includes/footer.php'; ?>

<script>
$("#editUserForm").validate({
rules:{
new_password:{ minlength:8 },
confirm_password:{ equalTo:"#new_password" },
role:{ required:true }
},
messages:{
confirm_password:"Passwords do not match"
},
errorElement:"div",
errorClass:"error-message"
});
</script>
</body>
</html>
