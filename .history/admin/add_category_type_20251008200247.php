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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management Dashboard</title>
    <link rel="icon" href="../images/logo.jpg" type="image/png">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom styles for sidebar and main content */
        .sidebar {
            transition: all 0.3s ease;
        }
        .sidebar:hover {
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        .btn-nav {
            transition: all 0.2s ease;
        }
        .btn-nav:hover {
            transform: translateX(5px);
        }
        .main-content {
            min-height: calc(100vh - 64px);
        }
        .logout-btn svg {
            transition: transform 0.2s ease;
        }
        .logout-btn:hover svg {
            transform: translateX(4px);
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen">
        

        <!-- Main Content -->
        <div class="main-content flex-1 p-8">
            <div class="max-w-4xl mx-auto">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Welcome to Category Management</h1>
                <p class="text-gray-600 mb-4">Use the sidebar to create a new category, view the list of categories, or add a new post.</p>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Quick Actions</h2>
                    <p class="text-gray-600">Select an option from the sidebar to get started.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>