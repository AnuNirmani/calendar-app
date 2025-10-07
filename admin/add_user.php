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

// Handle add user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $created_by = getCurrentUserId();
    $status = 1; // New users are active by default

    if (empty($username)) {
        $error = "Username is required!";
    } elseif (empty($role)) {
        $error = "Role is required!";
    } elseif (strlen($password) < 8) {
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
                // Redirect after 2 seconds
                header("refresh:2;url=manage_users.php");
            } else {
                $error = "Error adding user!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add User - Super Admin</title>
    <link rel="stylesheet" href="../css/fonts/fonts.css">
    <link rel="stylesheet" href="../css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../images/logo.jpg" type="image/png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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

        .form-container {
            background: transparent;
            padding: 30px;
            border-radius: 10px;
            box-shadow: none;
            max-width: 600px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #2196f3;
            outline: none;
        }
    </style>
</head>

<body class="admin-page">
    <!-- Header with Back Button and Title -->
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="font-size: 28px;">➕ Add New User</h1>

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
        <div style="background: #ffebee; color: #c62828; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #f44336; max-width: 600px; margin: 0 auto 20px auto;">
            <strong>⚠️ Error:</strong> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($success)): ?>
        <div style="background: #e8f5e8; color: #2e7d32; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #4caf50; max-width: 600px; margin: 0 auto 20px auto;">
            <strong>✅ Success:</strong> <?= htmlspecialchars($success) ?>
            <br><small>Redirecting to Manage Users...</small>
        </div>
    <?php endif; ?>

    <!-- Add User Form -->
    <div class="form-container">
        <form method="POST" id="addUserForm">
            <div class="form-group">
                <label for="username">👤 Username:</label>
                <input type="text" id="username" name="username" required 
                       placeholder="Enter username"
                       value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
            </div>

            <div class="form-group">
                <label for="password">🔒 Password:</label>
                <div class="password-input-container">
                    <input type="password" name="password" id="passwordInput" required 
                           class="password-input"
                           placeholder="At least 8 characters, Contains letters, Contains numbers"
                           oninput="validatePassword()">
                    <span onclick="togglePassword()" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer;">
                        <i class="fa-solid fa-eye" id="eyeIcon"></i>
                    </span>
                </div>
                <div class="password-validation" id="passwordValidation">
                    ❌ At least 8 characters, Contains letters and numbers
                </div>
            </div>

            <div class="form-group">
                <label for="role">🏷️ Role:</label>
                <select name="role" id="role" required>
                    <option value="">Select Role</option>
                    <option value="admin" <?= (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : '' ?>>Admin</option>
                    <option value="super_admin" <?= (isset($_POST['role']) && $_POST['role'] == 'super_admin') ? 'selected' : '' ?>>Super Admin</option>
                </select>
            </div>

            <button type="submit" name="add_user" id="addUserButton">
            <i class="fas fa-user-plus "></i>
               Add User
            </button>
        </form>
    </div>

    <!-- User Info + Logout -->
    <div style="margin-top: 30px; text-align: center;">
        <span style="color: navy; padding: 8px 16px; border-radius: 20px; font-size: 18px; font-weight: 600;">
            <?= isSuperAdmin() ? '👑 Super Admin' : '👤 Admin' ?>: <?= htmlspecialchars($_SESSION['username']) ?>
        </span>
        <a href="../logout.php" style="background: #f44336; color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; margin-left: 10px;">
            🚪 Logout
        </a>
    </div>

    <div class="footer-divider"></div>
    <footer class="footer" style="margin-top: 0; text-align: center;">
        &copy; <?= date('Y') ?> Developed and Maintained by WNL in collaboration with Web Publishing Department <br>
        © All rights reserved, 2008 - Wijeya Newspapers Ltd.
    </footer>

    <script>
        // Auto-hide success/error messages
        setTimeout(() => {
            document.querySelectorAll('div[style*="border-left"]').forEach(el => {
                if (el.textContent.includes('Success')) {
                    el.style.display = 'none';
                }
            });
        }, 2000);

        // Toggle password visibility
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

        // Password validation function
        function validatePassword() {
            const password = document.getElementById('passwordInput').value;
            const passwordInput = document.getElementById('passwordInput');
            const passwordValidation = document.getElementById('passwordValidation');
            const addUserButton = document.getElementById('addUserButton');
            const username = document.getElementById('username').value;
            const role = document.getElementById('role').value;
            
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
            
            // Always enable submit button to allow server-side validation
            addUserButton.disabled = false;
        }

        // Add event listeners for all form fields
        document.getElementById('username').addEventListener('input', validatePassword);
        document.getElementById('role').addEventListener('change', validatePassword);

        // Allow form submission to show server-side error messages
        // The server-side validation will handle the error display

        // Initialize validation on page load
        document.addEventListener('DOMContentLoaded', function() {
            validatePassword();
        });
    </script>
</body>
</html>

