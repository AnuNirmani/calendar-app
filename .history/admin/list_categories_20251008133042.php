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

// Handle status toggle
if (isset($_GET['toggle_status'])) {
    $categoryId = intval($_GET['toggle_status']);
    $currentStatus = getCategoryStatus($categoryId);
    
    if ($currentStatus) {
        $newStatus = ($currentStatus == 'active') ? 'inactive' : 'active';
        $result = toggleCategoryStatus($categoryId, $newStatus);
        
        if ($result === true) {
            $_SESSION['success'] = "Category status updated successfully!";
        } else {
            $_SESSION['error'] = $result;
        }
    } else {
        $_SESSION['error'] = "Category not found!";
    }
    
    header("Location: list_categories.php");
    exit();
}

// Handle deletion
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    
    if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
        $result = deleteCategory($deleteId);
        
        if ($result === true) {
            $_SESSION['success'] = "Category deleted successfully!";
        } else {
            if ($result === false) {
                $_SESSION['error'] = "Cannot delete category: It is being used in posts!";
            } else {
                $_SESSION['error'] = "Failed to delete category: " . $result;
            }
        }
        
        header("Location: list_categories.php");
        exit();
    }
}

// Handle form submission for updating categories
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryId = intval($_POST['categoryId']);
    $categoryName = trim($_POST['categoryName']);
    
    if (empty($categoryName)) {
        $_SESSION['error'] = "Please enter a category name";
    } else {
        $result = updateCategory($categoryId, $categoryName);
        if ($result === true) {
            $_SESSION['success'] = "Category updated successfully!";
        } else {
            $_SESSION['error'] = $result;
        }
    }
    
    header("Location: list_categories.php");
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

// Fetch categories
$categories = getCategories();
if (isset($categories['error'])) {
    $errorMessage = $categories['error'];
    $categories = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List Categories</title>
    <link rel="icon" href="../images/logo.jpg" type="image/png">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- jQuery and jQuery Validation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
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
    </style>
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
                </nav>
            </div>
            <div class="mt-auto">
                <a href="add_category_type.php?logout=true" class="logout-btn flex items-center justify-center py-3 px-4 bg-red-600 text-white rounded-lg hover:bg-red-700" onclick="return confirm('Are you sure you want to logout?')">
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
                <h1 class="text-3xl font-bold text-gray-800 mb-6">All Categories</h1>

                <?php if ($successMessage): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded"><?php echo htmlspecialchars($successMessage); ?></div>
                <?php endif; ?>

                <?php if ($errorMessage): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded"><?php echo htmlspecialchars($errorMessage); ?></div>
                <?php endif; ?>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <form id="categoryForm" method="POST" action="" class="mb-6">
                        <input type="hidden" id="categoryId" name="categoryId" value="">
                        <div class="mb-4">
                            <label for="categoryName" class="block text-gray-700 font-semibold mb-2">
                                Category Name <span class="text-red-500">*</span>
                            </label>
                            <div class="flex space-x-4">
                                <input type="text" id="categoryName" name="categoryName" class="flex-1 p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Enter category name">
                                <button type="submit" class="py-3 px-6 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700" id="submitButton">Update Category</button>
                                <button type="button" class="py-3 px-6 bg-gray-500 text-white rounded-lg hover:bg-gray-600" id="cancelButton" style="display: none;">Cancel</button>
                            </div>
                        </div>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-gray-200">
                                    <th class="p-3">#</th>
                                    <th class="p-3">Category Name</th>
                                    <th class="p-3">Status</th>
                                    <th class="p-3">Created Date</th>
                                    <th class="p-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($categories)): ?>
                                    <tr>
                                        <td colspan="5" class="p-3 text-center text-gray-600">
                                            <div class="flex justify-center items-center">
                                                <span class="text-2xl mr-2">📁</span> No categories added yet.
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($categories as $index => $category): ?>
                                        <tr id="category-<?php echo $category['id']; ?>">
                                            <td class="p-3"><?php echo $index + 1; ?></td>
                                            <td class="p-3"><?php echo htmlspecialchars($category['name']); ?></td>
                                            <td class="p-3">
                                                <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $category['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                                    <?php echo ucfirst($category['status']); ?>
                                                </span>
                                            </td>
                                            <td class="p-3"><?php echo htmlspecialchars($category['created_at']); ?></td>
                                            <td class="p-3 flex space-x-2">
                                                <button class="edit-btn px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600" onclick="editCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars(addslashes($category['name'])); ?>')">
                                                    Edit
                                                </button>
                                                <button class="px-3 py-1 rounded text-white <?php echo $category['status'] == 'active' ? 'bg-gray-500 hover:bg-gray-600' : 'bg-green-500 hover:bg-green-600'; ?>" 
                                                        onclick="toggleStatus(<?php echo $category['id']; ?>, '<?php echo $category['status']; ?>')">
                                                    <?php echo $category['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                                <button class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600" onclick="showDeleteModal(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars(addslashes($category['name'])); ?>')">
                                                    Delete
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3 class="text-lg font-semibold mb-4">Confirm Delete</h3>
            <p>Are you sure you want to delete the category "<span id="deleteCategoryName"></span>"?</p>
            <div class="flex justify-end space-x-4 mt-6">
                <button class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600" onclick="closeDeleteModal()">Cancel</button>
                <button class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>

    <script>
        let currentEditId = null;
        let currentDeleteId = null;

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

            // Auto-hide messages after 5 seconds
            setTimeout(() => {
                $('.bg-green-100, .bg-red-100').fadeOut();
            }, 5000);
        });

        function editCategory(id, name) {
            currentEditId = id;
            document.getElementById('categoryId').value = id;
            document.getElementById('categoryName').value = name;
            document.getElementById('submitButton').textContent = 'Update Category';
            document.getElementById('cancelButton').style.display = 'inline-block';
            document.getElementById('categoryName').focus();
            $("#categoryForm").validate().resetForm();
            $("#categoryName").removeClass('error valid');
        }

        function toggleStatus(categoryId, currentStatus) {
            if (confirm('Are you sure you want to ' + (currentStatus === 'active' ? 'deactivate' : 'activate') + ' this category?')) {
                window.location.href = '?toggle_status=' + categoryId;
            }
        }

        function showDeleteModal(id, name) {
            currentDeleteId = id;
            document.getElementById('deleteCategoryName').textContent = name;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function confirmDelete() {
            if (currentDeleteId) {
                window.location.href = '?delete=' + currentDeleteId + '&confirm=yes';
            }
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            currentDeleteId = null;
        }

        document.getElementById('cancelButton').addEventListener('click', function() {
            resetForm();
        });

        document.getElementById('confirmDeleteBtn').addEventListener('click', confirmDelete);

        function resetForm() {
            currentEditId = null;
            document.getElementById('categoryId').value = '';
            document.getElementById('categoryName').value = '';
            document.getElementById('submitButton').textContent = 'Update Category';
            document.getElementById('cancelButton').style.display = 'none';
            $("#categoryForm").validate().resetForm();
            $("#categoryName").removeClass('error valid');
        }

        window.addEventListener('click', function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                closeDeleteModal();
            }
        });
    </script>
</body>
</html>