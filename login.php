<?php
session_start();
include 'db.php';
include 'auth.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $user = $res->fetch_assoc();

        // Check if user is active
        if ($user['status'] == 0) {
            $error = "Your account has been deactivated. Please contact the superuser.";
        } elseif (password_verify($password, $user['password'])) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['user_id'] = $user['id'];
            header("Location: admin/dashboard.php");
            exit;
        } else {
            $error = "Invalid password";
        }
    } else {
        $error = "User not found";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login Page</title>
    <link rel="stylesheet" href="css/fonts/fonts.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body.login-page {
            background: #f2f2f2;
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
        }

        .login-container {
            display: inline-block;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
        }

        .login-container h2 {
            margin-bottom: 30px;
        }

        .login-container a.button {
            display: inline-block;
            padding: 12px 24px;
            margin: 10px;
            background-color: navy;
            color: white;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .login-container a.button:hover {
            background-color: darkblue;
        }

        .role-info {
            margin-top: 20px;
            padding: 15px;
            background: #e3f2fd;
            border-radius: 8px;
            font-size: 14px;
            color: #1976d2;
        }

    </style>
    <link rel="icon" href="images/logo.jpg" type="image/png">
</head>
<body class="login-page">
    <div class="login-container">
        <h2>🔐 Login</h2>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required onfocus="this.value=''">
            <div style="position: relative;">
                <input type="password" name="password" id="passwordInput" placeholder="Password" required 
                    onfocus="this.value=''" 
                        style="width: 100%; padding: 10px 40px 10px 10px; box-sizing: border-box;">
                    <span id="togglePassword" onclick="togglePassword()"
                        style="position: absolute; top: 50%; right: 10px; transform: translateY(-50%);
                            cursor: pointer; font-size: 20px; color: #666;">
                        <i class="fa-solid fa-eye" id="eyeIcon"></i>
                    </span>

                </div>

            <button type="submit">Login</button>
        </form>
        <?php if ($error): ?>
            <p style="color:#d32f2f; margin-top: 20px; padding: 10px; background: rgba(255,0,0,0.1); border-radius: 8px; font-weight: 500;"><?= $error ?></p>
        <?php endif; ?>

        <!-- <div class="role-info">
            <strong>Role System:</strong><br>
            • Super Admin: Can manage all users and access all features<br>
            • Admin: Can access calendar management features
        </div> -->
        
    </div>
</body>

<script>
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

</html>