<?php
include '../db.php';
require 'add_category_type_functions.php';

// Start session for messages
session_start();

$successMessage = "";
$errorMessage = "";

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
    <link rel="stylesheet" href="../css/add_category_type.css">
    <link rel="icon" href="../images/logo.jpg" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories</title>
    <style>
        /* Modal Styles */
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
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            max-width: 400px;
            width: 90%;
        }
        
        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .btn-danger {
            background: #dc2626;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        
        .edit-btn {
            background: #10b981;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <button class="floating-btn" onclick="addNewPost()">
        <span>+</span> Add New Post
    </button>

    <div class="main-container">
        <div class="header-section">
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
                                <input type="text" id="categoryName" name="categoryName" placeholder="Enter category name" required minlength="2" maxlength="50">
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

            <div class="categories-section">
                <h2 class="section-title">All Categories</h2>
                <div class="table-container">
                    <table id="categoriesTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Category Name</th>
                                <th>Created Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="4" class="empty-state">
                                        <div class="empty-icon">📁</div>
                                        <div>No categories added yet.</div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $index => $category): ?>
                                    <tr id="category-<?= $category['id'] ?>">
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($category['name']) ?></td>
                                        <td><?= htmlspecialchars($category['created_at']) ?></td>
                                        <td class="actions">
                                            <button class="edit-btn" onclick="editCategory(<?= $category['id'] ?>, '<?= htmlspecialchars(addslashes($category['name'])) ?>')">
                                                Edit
                                            </button>
                                            <button class="delete-btn" onclick="showDeleteModal(<?= $category['id'] ?>, '<?= htmlspecialchars(addslashes($category['name'])) ?>')">
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

    <!-- Delete Confirmation Modal -->
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

    <script>
        let currentEditId = null;
        let currentDeleteId = null;

        function editCategory(id, name) {
            currentEditId = id;
            document.getElementById('categoryId').value = id;
            document.getElementById('categoryName').value = name;
            document.getElementById('buttonText').textContent = 'Update Category';
            document.getElementById('cancelButton').style.display = 'inline-block';
            document.getElementById('categoryName').focus();
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
            document.getElementById('buttonText').textContent = 'Create Category';
            document.getElementById('cancelButton').style.display = 'none';
        }

        function addNewPost() {
            window.location.href = 'add_post.html';
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('deleteModal');
            if (event.target === modal) {
                closeDeleteModal();
            }
        });

        // Auto-hide messages after 5 seconds
        setTimeout(() => {
            const messages = document.querySelectorAll('.success-message, .error-message');
            messages.forEach(msg => {
                msg.classList.remove('show');
            });
        }, 5000);
    </script>
</body>
</html>