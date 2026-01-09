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
        $password_hashed = password_hash($password, PASSWORD_DEFAULT);

        $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $checkStmt->bind_param("s", $username);
        $checkStmt->execute();

        if ($checkStmt->get_result()->num_rows > 0) {
            $error = "Username already exists!";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (username, password, role, created_by, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssii", $username, $password_hashed, $role, $created_by, $status);

            if ($stmt->execute()) {
                $success = "User added successfully!";
                header("refresh:2;url=manage_users.php");
            } else {
                $error = "Error adding user!";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add User - Super Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../images/logo.jpg" type="image/png">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>

    <style>
        /* jQuery Validation Styles (Tailwind friendly) */
        input.error, select.error {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15) !important;
        }
        input.valid, select.valid {
            border-color: #22c55e !important;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15) !important;
        }
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 10px 12px;
            border-radius: 8px;
            margin-top: 8px;
            border-left: 4px solid #ef4444;
            font-size: 13px;
        }

        .password-validation {
            margin-top: 6px;
            font-size: 12px;
            color: #b91c1c;
            display: none;
        }
        .password-validation.show { display: block; }
    </style>
</head>

<body class="bg-gray-100 font-sans">
<div class="flex min-h-screen">
    <?php
    $base_path = '../';
    include __DIR__ . '/includes/slidebar2.php';
    ?>

    <div class="flex-1 p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">➕ Add New User</h1>

        <?php if (isset($error)): ?>
            <div class="max-w-xl mx-auto bg-red-100 border-l-4 border-red-500 text-red-700 p-4 my-4 rounded">
                <strong>⚠️ Error:</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="max-w-xl mx-auto bg-green-100 border-l-4 border-green-500 text-green-800 p-4 my-4 rounded">
                <strong>✅ Success:</strong> <?= htmlspecialchars($success) ?>
                <div class="text-xs mt-1">Redirecting to Manage Users...</div>
            </div>
        <?php endif; ?>

        <div class="max-w-xl mx-auto bg-white rounded-lg shadow-md p-6">
            <form method="POST" id="addUserForm" class="space-y-5">
                <input type="hidden" name="add_user" value="1">

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">👤 Username</label>
                    <input type="text" id="username" name="username"
                           value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                           placeholder="Enter username"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">🔒 Password</label>

                    <div class="relative">
                        <input type="password" name="password" id="passwordInput"
                               placeholder="At least 8 characters, letters + numbers"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 pr-10 focus:ring-2 focus:ring-indigo-500 focus:outline-none">

                        <button type="button"
                                onclick="togglePassword()"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <i class="fa-solid fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>

                    <div class="password-validation" id="passwordValidation">
                        ❌ Password must be at least 8 characters and contain letters and numbers
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">🏷️ Role</label>
                    <select name="role" id="role"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">Select Role</option>
                        <option value="admin" <?= (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : '' ?>>Admin</option>
                        <option value="super_admin" <?= (isset($_POST['role']) && $_POST['role'] == 'super_admin') ? 'selected' : '' ?>>Super Admin</option>
                    </select>
                </div>

                <button type="submit"
                        class="w-full bg-sky-500 text-white px-4 py-3 rounded-md font-semibold hover:bg-sky-600 transition flex items-center justify-center gap-2">
                    <i class="fas fa-user-plus"></i>
                    Add User
                </button>
            </form>
        </div>

    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
    // Toggle password visibility
    function togglePassword() {
        const passwordInput = $("#passwordInput");
        const eyeIcon = $("#eyeIcon");

        if (passwordInput.attr("type") === "password") {
            passwordInput.attr("type", "text");
            eyeIcon.removeClass("fa-eye").addClass("fa-eye-slash");
        } else {
            passwordInput.attr("type", "password");
            eyeIcon.removeClass("fa-eye-slash").addClass("fa-eye");
        }
    }

    $(document).ready(function () {
        // Auto-hide messages
        setTimeout(() => {
            $('.bg-red-100, .bg-green-100').hide();
        }, 2000);

        // Custom validation methods
        $.validator.addMethod("hasLetters", function(value, element) {
            return this.optional(element) || /[A-Za-z]/.test(value);
        }, "Password must contain at least one letter");

        $.validator.addMethod("hasNumbers", function(value, element) {
            return this.optional(element) || /[0-9]/.test(value);
        }, "Password must contain at least one number");

        // Validation
        $("#addUserForm").validate({
            rules: {
                username: { required: true, minlength: 3, maxlength: 50 },
                password: { required: true, minlength: 8, hasLetters: true, hasNumbers: true },
                role: { required: true }
            },
            messages: {
                username: {
                    required: "👤 Username is required",
                    minlength: "Username must be at least 3 characters",
                    maxlength: "Username cannot exceed 50 characters"
                },
                password: {
                    required: "🔒 Password is required",
                    minlength: "Password must be at least 8 characters",
                    hasLetters: "Password must contain letters",
                    hasNumbers: "Password must contain numbers"
                },
                role: { required: "🏷️ Please select a role" }
            },
            errorElement: "div",
            errorClass: "error-message",
            validClass: "valid",
            errorPlacement: function(error, element) {
                if (element.attr("name") === "password") {
                    error.insertAfter(element.closest(".relative"));
                } else {
                    error.insertAfter(element);
                }
            },
            success: function(label, element) {
                $(element).removeClass("error").addClass("valid");
                label.remove();
            }
        });

        // Real-time password feedback
        $("#passwordInput").on("input", function() {
            const password = $(this).val();
            const ok = password.length >= 8 && /[A-Za-z]/.test(password) && /[0-9]/.test(password);

            if (password.length === 0) {
                $("#passwordValidation").removeClass("show");
                return;
            }

            if (ok) {
                $("#passwordValidation").removeClass("show");
            } else {
                $("#passwordValidation").addClass("show");
            }
        });
    });
</script>
</body>
</html>
