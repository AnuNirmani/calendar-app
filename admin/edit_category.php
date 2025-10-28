<?php
require_once '../db.php';
require_once 'categories.php';

// Start session for authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$categoryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$category = getCategoryById($categoryId);

if (!$category) {
    die("Category not found.");
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryName = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $status = trim($_POST['status'] ?? '');

    if (empty($categoryName)) {
        $errors[] = "Category name is required.";
    }
    if (empty($slug)) {
        $errors[] = "Slug is required.";
    }
    if (!in_array($status, ['published', 'unpublished'])) {
        $errors[] = "Invalid status selected.";
    }

    if (empty($errors)) {
        $result = updateCategory($categoryId, $categoryName, $slug, $status);
        if ($result === true) {
            $success = "Category updated successfully!";
            $category = getCategoryById($categoryId);
        } else {
            $errors[] = $result;
        }
    }
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
    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
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
    <script>
        $(document).ready(function() {
            // Function to generate slug from category name
            function generateSlug(text) {
                return text
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9\s-]/g, '') // Remove special characters
                    .replace(/\s+/g, '-')         // Replace spaces with hyphens
                    .replace(/-+/g, '-');         // Replace multiple hyphens with single hyphen
            }

            // Auto-fill slug when category name changes
            $('#name').on('input', function() {
                const categoryName = $(this).val();
                const slug = generateSlug(categoryName);
                $('#slug').val(slug);
            });

            // Initialize slug on page load
            const initialCategoryName = $('#name').val();
            if (initialCategoryName) {
                $('#slug').val(generateSlug(initialCategoryName));
            }
        });
    </script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen">
        <!-- Sidebar -->
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
        </div>

        <!-- Main Content -->
        <div class="main-content flex-1 p-8">
            <div class="max-w-4xl mx-auto">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Edit Category</h1>

                <?php if ($success): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Edit Category Details</h2>
                    <form method="POST" action="">
                        <div class="mb-4">
                            <label for="name" class="block text-gray-700 font-medium mb-2">Category Name</label>
                            <input type="text" class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($category['name'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-4">
                            <label for="slug" class="block text-gray-700 font-medium mb-2">Slug</label>
                            <input type="text" class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" id="slug" name="slug" 
                                   value="<?php echo htmlspecialchars($category['slug'] ?? ''); ?>" required>
                            <p class="text-sm text-gray-600 mt-1">Use lowercase letters, numbers, and hyphens only. Auto-generated from category name.</p>
                        </div>
                        <div class="mb-4">
                            <label for="status" class="block text-gray-700 font-medium mb-2">Status</label>
                            <select class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" id="status" name="status" required>
                                <option value="published" <?php echo $category['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                                <option value="unpublished" <?php echo $category['status'] === 'unpublished' ? 'selected' : ''; ?>>Unpublished</option>
                            </select>
                        </div>
                        <div class="flex space-x-4">
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Update Category</button>
                            <a href="list_categories.php" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">Back to Categories</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>