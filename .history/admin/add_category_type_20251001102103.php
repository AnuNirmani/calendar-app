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

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

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
    
    header("Location: add_category_type.php");
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
        
        header("Location: add_category_type.php");
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['categoryId']) && !empty($_POST['categoryId'])) {
        // Update category
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
    } else {
        // Create new category
        $categoryName = trim($_POST['categoryName']);
        if (empty($categoryName)) {
            $_SESSION['error'] = "Please enter a category name";
        } else {
            $result = createCategory($categoryName);
            if ($result === true) {
                $_SESSION['success'] = "Category created successfully!";
            } else {
                $_SESSION['error'] = $result;
            }
        }
    }
    
    header("Location: add_category_type.php");
    exit();
}

// Get messages
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
    <link rel="stylesheet" href="../css/add_category_type.css">
    <link rel="icon" href="../images/logo.jpg" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories</title>
    <style>
        /* Modal and button styles remain same ... */

        /* Validation Error Styles */
        label.error {
            color: #dc2626;
            font-size: 0.85rem;
            margin-top: 5px;
            display: block;
        }
        input.error {
            border: 1px solid #dc2626 !important;
            background: #fef2f2;
        }
    </style>
</head>
<body>
    <button class="floating-btn" onclick="addNewPost()">
        <span>+</span> Add New Post
    </button>

    <div class="main-container">
        <div class="header-section">
            <div class="logout-container">
                <a href="?logout=true" class="logout-btn" onclick="return confirm('Are you sure you want to logout?')">
                    Logout
                </a>
            </div>

            <div class="logo-container">
                <img src="../images/logo.jpg" alt="Logo">
            </div>
            <div class="page-title">
                <h1>Manage Categories</h1>
            </div>
        </div>

        <div class="content-section">
            <?php if ($successMessage): ?>
                <div class="success-message show"><?= htmlspecialchars($successMessage) ?></div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="error-message show"><?= htmlspecialchars($errorMessage) ?></div>
            <?php endif; ?>

            <div class="form-container">
                <form id="categoryForm" method="POST" action="">
                    <input type="hidden" id="categoryId" name="categoryId" value="">
                    <div class="form-group">
                        <label for="categoryName">Category Name</label>
                        <div class="input-group">
                            <div class="input-field">
                                <input type="text" id="categoryName" name="categoryName" placeholder="Enter category name">
                            </div>
                            <button type="submit" class="btn-primary" id="submitButton">
                                <span id="buttonText">Create Category</span>
                            </button>
                            <button type="button" class="btn-secondary" id="cancelButton" style="display: none;">
                                Cancel
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- categories table remains same -->
            <div class="categories-section">
                <h2 class="section-title">All Categories</h2>
                <div class="table-container">
                    <table id="categoriesTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Category Name</th>
                                <th>Status</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        <div class="empty-icon">📁</div>
                                        <div>No categories added yet.</div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $index => $category): ?>
                                    <tr id="category-<?= $category['id'] ?>">
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($category['name']) ?></td>
                                        <td>
                                            <span class="status-badge <?= $category['status'] == 'active' ? 'badge-active' : 'badge-inactive' ?>">
                                                <?= ucfirst($category['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($category['created_at']) ?></td>
                                        <td class="actions">
                                            <button class="edit-btn" onclick="editCategory(<?= $category['id'] ?>, '<?= htmlspecialchars(addslashes($category['name'])) ?>')">Edit</button>
                                            <button class="status-btn <?= $category['status'] == 'active' ? 'status-inactive' : 'status-active' ?>" onclick="toggleStatus(<?= $category['id'] ?>, '<?= $category['status'] ?>')">
                                                <?= $category['status'] == 'active' ? 'Deactivate' : 'Activate' ?>
                                            </button>
                                            <button class="delete-btn" onclick="showDeleteModal(<?= $category['id'] ?>, '<?= htmlspecialchars(addslashes($category['name'])) ?>')">Delete</button>
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

    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3>Confirm Delete</h3>
            <p>Are you sure you want to delete the category "<span id="deleteCategoryName"></span>"?</p>
            <div class="modal-actions">
                <button class="btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <button class="btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>

    <!-- jQuery + jQuery Validation -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/jquery.validation/1.19.5/jquery.validate.min.js"></script>

    <script>
        // Existing JS functions (editCategory, toggleStatus, etc.) remain same...

        // jQuery Validation
        $(document).ready(function() {
            $("#categoryForm").validate({
                rules: {
                    categoryName: {
                        required: true,
                        minlength: 2,
                        maxlength: 50,
                        pattern: /^[a-zA-Z0-9 _-]+$/
                    }
                },
                messages: {
                    categoryName: {
                        required: "Please enter a category name",
                        minlength: "Category name must be at least 2 characters",
                        maxlength: "Category name cannot exceed 50 characters",
                        pattern: "Category name can only contain letters, numbers, spaces, underscores, and hyphens"
                    }
                },
                errorPlacement: function(error, element) {
                    error.insertAfter(element);
                },
                highlight: function(element) {
                    $(element).addClass('error');
                },
                unhighlight: function(element) {
                    $(element).removeClass('error');
                }
            });
        });
    </script>
</body>
</html>
