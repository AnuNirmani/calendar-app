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

$successMessage = "";
$errorMessage = "";

// Check if category ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid category ID.";
    header("Location: list_categories.php");
    exit();
}

$categoryId = intval($_GET['id']);

// Fetch category data
$category = getCategoryById($categoryId);
if (!$category) {
    $_SESSION['error'] = "Category not found.";
    header("Location: list_categories.php");
    exit();
}

// Handle form submission for updating category
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryName = trim($_POST['categoryName']);
    
    if (empty($categoryName)) {
        $errorMessage = "Please enter a category name";
    } else {
        $result = updateCategory($categoryId, $categoryName);
        if ($result === true) {
            $_SESSION['success'] = "Category updated successfully!";
            header("Location: list_categories.php");
            exit();
        } else {
            $errorMessage = $result;
        }
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Get messages from session
if (isset($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    $errorMessage = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Category</title>
    <link rel="icon" href="../images/logo.jpg" type="image/png">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- jQuery and jQuery Validation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
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
        .error {
            color: #dc2626;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        input.error {
            border-color: #dc2626 !important;
            background-color: #fef2f2;
        }
        input.valid {
            border-color: #10b981;
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
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen">
        <!-- Sidebar -->
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

        <!-- Main Content -->
        <div class="main-content flex-1 p-4 md:p-8 md:ml-[250px]">
            <div class="hamburger md:hidden p-4">
                <button id="hamburgerBtn" class="text-gray-800 focus:outline-none">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
            <div class="max-w-full md:max-w-4xl mx-auto">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-6">Edit Category</h1>

                <?php if ($successMessage): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded"><?php echo htmlspecialchars($successMessage); ?></div>
                <?php endif; ?>

                <?php if ($errorMessage): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded"><?php echo htmlspecialchars($errorMessage); ?></div>
                <?php endif; ?>

                <div class="bg-white p-4 md:p-6 rounded-lg shadow-md">
                    <form id="categoryForm" method="POST" action="">
                        <input type="hidden" id="categoryId" name="categoryId" value="<?php echo $categoryId; ?>">
                        <div class="mb-6">
                            <label for="categoryName" class="block text-gray-700 font-semibold mb-2">
                                Category Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="categoryName" name="categoryName" value="<?php echo htmlspecialchars($category['name']); ?>" 
                                   class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" 
                                   placeholder="Enter category name">
                        </div>
                        <div class="flex flex-col space-y-2 md:flex-row md:space-x-4 md:space-y-0 justify-center">
                            <button type="submit" class="py-3 px-6 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-base md:text-lg">Update Category</button>
                            <button type="button" class="py-3 px-6 bg-gray-500 text-white rounded-lg hover:bg-gray-600 text-base md:text-lg" 
                                    onclick="window.location.href='list_categories.php'">Back to Categories</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $("#categoryForm").validate({
                rules: {
                    categoryName: {
                        required: true,
                        minlength: 2,
                        maxlength: 50
                    }
                },
                messages: {
                    categoryName: {
                        required: "This field is required.",
                        minlength: "Category name must be at least 2 characters long.",
                        maxlength: "Category name cannot exceed 50 characters."
                    }
                },
                errorElement: "label",
                errorClass: "error",
                validClass: "valid",
                errorPlacement: function(error, element) {
                    error.insertAfter(element);
                },
                highlight: function(element) {
                    $(element).addClass('error').removeClass('valid');
                },
                unhighlight: function(element) {
                    $(element).removeClass('error').addClass('valid');
                }
            });

            // Auto-hide messages
            setTimeout(() => {
                $('.bg-green-100, .bg-red-100').fadeOut();
            }, 5000);

            // Hamburger menu toggle
            $('#hamburgerBtn').click(function() {
                $('.sidebar').toggleClass('hidden');
            });
        });
    </script>
</body>
</html>