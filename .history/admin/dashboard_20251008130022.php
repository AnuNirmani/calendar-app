<?php
include '../db.php';
require 'add_category_type_functions.php';

// Start session for messages and authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="../css/add_category_type.css">
    <link rel="icon" href="../images/logo.jpg" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    
    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    
    <style>
        body {
            display: flex;
            min-height: 100vh;
            margin: 0;
            background: #f9fafb;
        }
        
        .sidebar {
            width: 250px;
            background: #1f2937;
            color: white;
            padding: 20px;
            position: fixed;
            height: 100%;
            overflow-y: auto;
        }
        
        .sidebar .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .sidebar .logo-container img {
            width: 120px;
            border-radius: 8px;
        }
        
        .sidebar nav ul {
            list-style: none;
            padding: 0;
        }
        
        .sidebar nav ul li {
            margin: 10px 0;
        }
        
        .sidebar nav ul li a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 12px 20px;
            border-radius: 8px;
            transition: background 0.3s;
        }
        
        .sidebar nav ul li a:hover {
            background: #374151;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 40px;
            width: calc(100% - 250px);
        }
        
        .dashboard-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            margin: 0 0 10px;
            color: #374151;
        }
        
        .stat-card p {
            font-size: 2rem;
            color: #10b981;
            margin: 0;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo-container">
            <img src="../images/logo.jpg" alt="Logo">
        </div>
        <nav>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="category_list.php">Manage Categories</a></li>
                <li><a href="add_post.php">Add New Post</a></li>
                <li><a href="?logout=true" onclick="return confirm('Are you sure you want to logout?')">Logout</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="dashboard-header">
            <h1>Welcome to the Dashboard</h1>
            <p>Manage your content efficiently.</p>
        </div>
        
        <div class="dashboard-stats">
            <div class="stat-card">
                <h3>Total Categories</h3>
                <p><?php echo count(getCategories()); ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Posts</h3>
                <p>0</p> <!-- Replace with actual query if available -->
            </div>
            <div class="stat-card">
                <h3>Active Users</h3>
                <p>1</p> <!-- Static for now -->
            </div>
        </div>
    </main>
</body>
</html>