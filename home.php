<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$isAdmin = ($_SESSION['role'] === 'admin');
?>

<!DOCTYPE html>
<html>
<head>
    <title>Home Page</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body.home-page {
            background: #f2f2f2;
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
        }

        .home-container {
            display: inline-block;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
        }

        .home-container h2 {
            margin-bottom: 30px;
        }

        .home-container a.button {
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

        .home-container .button:hover {
            background-color: #0077ff;
        }
    </style> 
    <link rel="icon" href="images/logo.jpg" type="image/png">
</head>
<body class="home-page">
    <div class="home-container">
        <h2>🎉 Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h2>
        <div style="margin: 20px 0;">
            <a href="index.php" class="button">🗓️ View Calendar</a>
            <?php if ($isAdmin): ?>
                <a href="admin/index.php" class="button">🔐 Admin Panel</a>
            <?php endif; ?>
        </div>
        <br>
        <a href="logout.php" class="button logout-button">🚪 Logout</a>
    </div>
</body>
</html>