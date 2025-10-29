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

// Handle category creation
$category_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category_name'])) {
    $result = createCategory($_POST['category_name']);
    if ($result === true) {
        $category_message = "<p class='text-green-600 mt-2'>Category created successfully!</p>";
    } else {
        $category_message = "<p class='text-red-600 mt-2'>$result</p>";
    }
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
        .card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .gradient-bg {
            background: linear-gradient(135deg, #e0e7ff 0%, #f9fafb 100%);
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <div class="flex h-screen">
        <!-- Sidebar (Unchanged) -->
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
        </div>

        <!-- Main Content -->
        <div class="main-content flex-1 p-4 md:p-8 md:ml-[250px] gradient-bg">
            <div class="hamburger md:hidden p-4">
                <button id="hamburgerBtn" class="text-gray-800 focus:outline-none">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
            <div class="max-w-full md:max-w-5xl mx-auto">
                <h1 class="text-4xl font-extrabold text-gray-900 mb-4">Category Management Dashboard</h1>
                <p class="

                <!-- Quick Actions -->
                <div class="bg-white p-8 rounded-xl shadow-lg mb-6">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-6">Quick Actions</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <a href="create_category.php" class="card bg-indigo-50 p-6 rounded-lg hover:bg-indigo-100">
                            <h3 class="text-lg font-medium text-indigo-600">Create Category</h3>
                            <p class="text-gray-500 mt-2">Add a new category to organize your content.</p>
                        </a>
                        <a href="list_categories.php" class="card bg-teal-50 p-6 rounded-lg hover:bg-teal-100">
                            <h3 class="text-lg font-medium text-teal-600">List Categories</h3>
                            <p class="text-gray-500 mt-2">View and manage all existing categories.</p>
                        </a>
                        <a href="add_post.php" class="card bg-green-50 p-6 rounded-lg hover:bg-green-100">
                            <h3 class="text-lg font-medium text-green-600">Add New Post</h3>
                            <p class="text-gray-500 mt-2">Create a new post to share your content.</p>
                        </a>
                        <a href="list_posts.php" class="card bg-blue-50 p-6 rounded-lg hover:bg-blue-100">
                            <h3 class="text-lg font-medium text-blue-600">List Posts</h3>
                            <p class="text-gray-500 mt-2">Browse and edit your existing posts.</p>
                        </a>
                        <a href="add_telephone_directory.php" class="card bg-indigo-50 p-6 rounded-lg hover:bg-indigo-100">
                            <h3 class="text-lg font-medium text-indigo-600">Add Telephone Directory</h3>
                            <p class="text-gray-500 mt-2">Add a new telephone directory to the company records.</p>
                        </a>
                        <a href="list_telephone_directory.php" class="card bg-teal-50 p-6 rounded-lg hover:bg-teal-100">
                            <h3 class="text-lg font-medium text-teal-600">List Telephone Directory</h3>
                            <p class="text-gray-500 mt-2">View and manage all existing telephone directories.</p>
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