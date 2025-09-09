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
        $error = "❌ Password must be at least 8 characters long!";
    } elseif (!preg_match("/[A-Za-z].*[0-9]|[0-9].*[A-Za-z]/", $new_password)) {
        $error = "❌ Password must contain both letters and numbers!";
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
    <!-- <link rel="stylesheet" href="../style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"> -->
    <link rel="stylesheet" href="../css/fonts/fonts.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../images/logo.jpg" type="image/png">
    <style>
        /* Password validation styles */
        .password-validation {
            margin-top: 5px;
            font-size: 11px;
            color: #f44336;
            display: none;
        }

        .password-validation.show {
            display: block;
        }

        .password-input-container {
            position: relative;
        }

        .password-input {
            transition: border-color 0.3s ease;
        }

        .password-input.valid {
            border-color: #4caf50;
        }

        .password-input.invalid {
            border-color: #f44336;
        }
    </style>
</head>
<body class="admin-page">
    <div style="display: flex; align-items: center; justify-content: center; gap: 20px; margin-bottom: 30px; position: relative;">
        <a href="manage_users.php" style="background: #1976d2; color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; position: absolute; left: 0; font-weight: 600;">
            ← Back
        </a>
        <h2>✨ Manage Users</h2>
    </div>

    <?php if ($error): ?>
        <div style="background: #ffebee; color: #c62828; padding: 15px; border-radius: 8px; margin: auto; max-width: 500px; margin-bottom: 20px;">
            <strong><?= $error ?></strong>
        </div>
    <?php endif; ?>

    <form method="POST" id="editUserForm" style="max-width: 500px; margin: auto;">
        <label>Username:</label>
        <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled style="background-color: #f3f3f3;">

        <label>New Password:</label>
<div class="password-input-container" style="margin-bottom: 15px;">
    <input type="password" name="new_password" id="new_password"
           required class="password-input"
           placeholder="At least 8 characters, Contains letters, Contains numbers"
           style="width: 100%; padding: 16px 45px 16px 15px; font-size: 16px; border-radius: 8px;"
           oninput="validatePassword()">
    <span onclick="togglePassword('new_password', 'eye1')" 
          style="position: absolute; top: 50%; right: 15px; transform: translateY(-50%); cursor: pointer;">
        <i class="fa-solid fa-eye" id="eye1"></i>
    </span>
    <div class="password-validation" id="passwordValidation">
        ❌ At least 8 characters, Contains letters, Contains numbers
    </div>
</div>

        <label>Confirm Password:</label>
<div style="position: relative; margin-bottom: 15px;">
    <input type="password" name="confirm_password" id="confirm_password"
           required placeholder="Confirm new password"
           style="width: 100%; padding: 16px 45px 16px 15px; font-size: 16px; border-radius: 8px;"
           oninput="validatePasswordMatch()">
    <span onclick="togglePassword('confirm_password', 'eye2')" 
          style="position: absolute; top: 50%; right: 15px; transform: translateY(-50%); cursor: pointer;">
        <i class="fa-solid fa-eye" id="eye2"></i>
    </span>
    <div class="password-validation" id="confirmPasswordValidation" style="display: none;">
        ❌ Passwords do not match
    </div>
</div>

        <label>Role:</label>
        <select name="role" required>
            <option value="">Select Role</option>
            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
            <option value="super_admin" <?= $user['role'] === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
        </select>

        <button type="submit" id="saveButton" style="margin-top: 20px;" disabled>💾 Save Changes</button>
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

// Password validation function
function validatePassword() {
    const password = document.getElementById('new_password').value;
    const passwordInput = document.getElementById('new_password');
    const passwordValidation = document.getElementById('passwordValidation');
    const saveButton = document.getElementById('saveButton');
    
    // Check if password meets all requirements
    const hasMinLength = password.length >= 8;
    const hasLetters = /[A-Za-z]/.test(password);
    const hasNumbers = /[0-9]/.test(password);
    const isValid = hasMinLength && hasLetters && hasNumbers;
    
    // Show/hide validation message and update styling
    if (password.length > 0 && !isValid) {
        passwordValidation.classList.add('show');
        passwordInput.classList.add('invalid');
        passwordInput.classList.remove('valid');
    } else if (password.length > 0 && isValid) {
        passwordValidation.classList.remove('show');
        passwordInput.classList.add('valid');
        passwordInput.classList.remove('invalid');
    } else {
        passwordValidation.classList.remove('show');
        passwordInput.classList.remove('valid', 'invalid');
    }
    
    // Also validate password match when password changes
    validatePasswordMatch();
    
    // Enable/disable submit button
    updateSaveButton();
}

// Password match validation function
function validatePasswordMatch() {
    const password = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const confirmPasswordValidation = document.getElementById('confirmPasswordValidation');
    
    if (confirmPassword.length > 0 && password !== confirmPassword) {
        confirmPasswordValidation.style.display = 'block';
    } else {
        confirmPasswordValidation.style.display = 'none';
    }
    
    // Update save button state
    updateSaveButton();
}

// Update save button state
function updateSaveButton() {
    const password = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const saveButton = document.getElementById('saveButton');
    
    // Check if password meets all requirements
    const hasMinLength = password.length >= 8;
    const hasLetters = /[A-Za-z]/.test(password);
    const hasNumbers = /[0-9]/.test(password);
    const isValid = hasMinLength && hasLetters && hasNumbers;
    const passwordsMatch = password === confirmPassword && password.length > 0;
    
    // Enable button only if password is valid and passwords match
    saveButton.disabled = !isValid || !passwordsMatch;
}

// Prevent form submission if password is invalid
document.getElementById('editUserForm').addEventListener('submit', function(e) {
    const password = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    // Double check validation on submit
    if (password.length < 8 || !/[A-Za-z]/.test(password) || !/[0-9]/.test(password)) {
        e.preventDefault();
        alert('❌ Please ensure the password meets all requirements before submitting.');
        return false;
    }
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('❌ Passwords do not match!');
        return false;
    }
});

// Initialize validation on page load
document.addEventListener('DOMContentLoaded', function() {
    validatePassword();
});
</script>

</html>
