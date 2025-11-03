<?php
include dirname(__DIR__) . '/../db.php';
require 'add_category_type_functions.php';

// Start session for messages and authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management Dashboard</title>
    <link rel="icon" href="../../images/logo.jpg" type="image/png">
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
        <!-- Sidebar
<div class="sidebar w-64 bg-white shadow-lg p-6 flex flex-col justify-between">
    <div>
        <div class="mb-8">
            <img src="../images/logo.jpg" alt="Logo" class="w-16 mx-auto">
            <h2 class="text-xl font-bold text-center text-gray-800 mt-2">Category Management</h2>
        </div>
        <nav class="space-y-4">
            <a href="create_category.php" class="btn-nav block w-full text-left py-3 px-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                Create Category
            </a>
            <a href="list_categories.php" class="btn-nav block w-full text-left py-3 px-4 bg-teal-600 text-white rounded-lg hover:bg-teal-700">
                List Categories
            </a>
            <a href="add_post.php" class="btn-nav block w-full text-left py-3 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700">
                Add New Post
            </a>
            <a href="list_posts.php" class="btn-nav block w-full text-left py-3 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                List Posts
            </a>
            <a href="add_telephone_directory.php" class="btn-nav block w-full text-left py-3 px-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                Add Telephone Directory
            </a>
            <a href="list_telephone_directory.php" class="btn-nav block w-full text-left py-3 px-4 bg-teal-600 text-white rounded-lg hover:bg-teal-700">
                List Telephone Directory
            </a>
            <a href="dashboard.php" class="btn-nav block w-full text-left py-3 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700">
                Admin Dashboard
            </a>
        </nav>
    </div>
    <div class="mt-auto">
        <a href="?logout=true" class="logout-btn flex items-center justify-center py-3 px-4 bg-red-600 text-white rounded-lg hover:bg-red-700" onclick="return confirm('Are you sure you want to logout?')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mr-2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <polyline points="16 17 21 12 16 7"></polyline>
                <line x1="21" y1="12" x2="9" y2="12"></line>
            </svg>
            Logout
        </a>
    </div>
</div> -->

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