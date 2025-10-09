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
    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
        .sidebar {
            transition: all 0.3s ease;
            transform: translateX(0);
        }
        .sidebar.hidden {
            transform: translateX(-100%);
        }
        .sidebar:hover {
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        .btn-nav {
            transition: all 0.2s ease;
            padding: 0.75rem 1rem;
        }
        .btn-nav:hover {
            transform: translateX(5px);
        }
        .main-content {
            min-height: calc(100vh - 64px);
            transition: margin-left 0.3s ease;
        }
        .logout-btn svg {
            transition: transform 0.2s ease;
        }
        .logout-btn:hover svg {
            transform: translateX(4px);
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            max-width: 400px;
            width: 90%;
        }
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100%;
                z-index: 50;
                width: 75%;
                max-width: 250px;
            }
            .main-content {
                margin-left: 0;
            }
            .hamburger {
                display: flex;
            }
            .table-container {
                display: none;
            }
            .card-container {
                display: block;
            }
        }
        @media (min-width: 769px) {
            .hamburger {
                display: none;
            }
            .sidebar {
                width: 20%;
                min-width: 200px;
                max-width: 250px;
            }
            .table-container {
                display: block;
            }
            .card-container {
                display: none;
            }
        }
        .quick-action-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .quick-action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-200 font-sans">
    <div class="flex h-screen">
        <!-- Sidebar (Unchanged) -->
        <div class="sidebar fixed inset-y-0 left-0 w-64 bg-white shadow-lg p-6 flex flex-col justify-between z-50 md:w-1/5 md:max-w-[250px] md:relative hidden md:flex">
            <div>
                <div class="mb-8">
                    <img src="../images/logo.jpg" alt="Logo" class="w-16 mx-auto">
                    <h2 class="text-xl font-bold text-center text-gray-800 mt-2">Category Management</h2>
                </div>
                <nav class="space-y-4">
                    <a href="create_category.php" class="btn-nav block w-full text-left py-3 px-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Create Category</a>
                    <a href="list_categories.php" class="btn-nav block w-full text-left py-3 px-4 bg-teal-600 text-white rounded-lg hover:bg-teal-700">List Categories</a>
                    <a href="add_post.php" class="btn-nav block w-full text-left py-3 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700">Add New Post</a>
                    <a href="list_posts.php" class="btn-nav block w-full text-left py-3 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700">List Posts</a>
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
        </div>

        <!-- Main Content (Improved UI) -->
        <div class="main-content flex-1 p-4 md:p-8 md:ml-[250px] overflow-auto">
            <div class="hamburger md:hidden p-4">
                <button id="hamburgerBtn" class="text-gray-800 focus:outline-none">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
            <div class="max-w-full md:max-w-5xl mx-auto">
                <h1 class="text-4xl font-extrabold text-gray-900 mb-6 bg-clip-text text-transparent bg-gradient-to-r from-indigo-600 to-teal-600">Category Management Dashboard</h1>
                <p class="text-lg text-gray-700 mb-8 max-w-2xl">Manage your categories and posts efficiently. Use the sidebar to create new categories, view existing ones, or add and manage posts with ease.</p>
                <div class="bg-white rounded-xl shadow-lg p-8">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Quick Actions</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <a href="create_category.php" class="quick-action-card bg-indigo-50 p-6 rounded-lg text-center hover:bg-indigo-100">
                            <svg class="w-12 h-12 mx-auto mb-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-800">Create Category</h3>
                            <p class="text-sm text-gray-600 mt-2">Add a new category to organize your content.</p>
                        </a>
                        <a href="list_categories.php" class="quick-action-card bg-teal-50 p-6 rounded-lg text-center hover:bg-teal-100">
                            <svg class="w-12 h-12 mx-auto mb-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-800">List Categories</h3>
                            <p class="text-sm text-gray-600 mt-2">View and manage all your categories.</p>
                        </a>
                        <a href="add_post.php" class="quick-action-card bg-green-50 p-6 rounded-lg text-center hover:bg-green-100">
                            <svg class="w-12 h-12 mx-auto mb-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-800">Add New Post</h3>
                            <p class="text-sm text-gray-600 mt-2">Create a new post for your audience.</p>
                        </a>
                        <a href="list_posts.php" class="quick-action-card bg-blue-50 p-6 rounded-lg text-center hover:bg-blue-100">
                            <svg class="w-12 h-12 mx-auto mb-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-800">List Posts</h3>
                            <p class="text-sm text-gray-600 mt-2">Review and edit your existing posts.</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Hamburger menu toggle
            $('#hamburgerBtn').click(function() {
                $('.sidebar').toggleClass('hidden');
            });
        });
    </script>
</body>
</html>