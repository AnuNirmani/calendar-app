<?php
include '../db.php';
require 'add_category_type_functions.php';

$successMessage = "";
$errorMessage = "";

// Handle deletion
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    
    // Add confirmation and CSRF protection
    if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
        $result = deleteCategory($deleteId);
        
        if ($result === true) {
            $successMessage = "Category deleted successfully!";
        } else {
            // Handle different types of return values
            if ($result === false) {
                $errorMessage = "Cannot delete category: It is being used in posts!";
            } else {
                $errorMessage = "Failed to delete category: " . $result;
            }
        }
        
        // Redirect to avoid resubmission
        header("Location: add_category_type.php?success=" . urlencode($successMessage) . "&error=" . urlencode($errorMessage));
        exit();
    } else {
        // Show confirmation modal instead of immediate deletion
        $categories = getCategories();
        $categoryToDelete = null;
        foreach ($categories as $cat) {
            if ($cat['id'] == $deleteId) {
                $categoryToDelete = $cat;
                break;
            }
        }
    }
}

// Handle edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['categoryId']) && !empty($_POST['categoryId'])) {
        // Update existing category
        $categoryId = intval($_POST['categoryId']);
        $categoryName = trim($_POST['categoryName']);
        
        if (empty($categoryName)) {
            $errorMessage = "Please enter a category name";
        } else {
            $result = updateCategory($categoryId, $categoryName);
            if ($result === true) {
                $successMessage = "Category updated successfully!";
            } else {
                $errorMessage = $result;
            }
        }
    } else {
        // Create new category
        $categoryName = trim($_POST['categoryName']);
        if (empty($categoryName)) {
            $errorMessage = "Please enter a category name";
        } else {
            $result = createCategory($categoryName);
            if ($result === true) {
                $successMessage = "Category created successfully!";
            } else {
                $errorMessage = $result;
            }
        }
    }
}

// Check for success/error messages from redirect
if (isset($_GET['success'])) {
    $successMessage = $_GET['success'];
}
if (isset($_GET['error'])) {
    $errorMessage = $_GET['error'];
}

// Fetch all categories
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
    <title>Add Category Type</title>
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
                <div class="success-message"><?= htmlspecialchars($successMessage) ?></div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="error-message"><?= htmlspecialchars($errorMessage) ?></div>
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
                                            <button class="edit-btn" onclick="editCategory(<?= $category['id'] ?>, '<?= htmlspecialchars($category['name']) ?>')">
                                                Edit
                                            </button>
                                            <button class="delete-btn" onclick="showDeleteModal(<?= $category['id'] ?>, '<?= htmlspecialchars($category['name']) ?>')">
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
    <div id="deleteModal" class="modal" style="display: none;">
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
            document.getElementById('submitButton').querySelector('#buttonText').textContent = 'Update Category';
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
            document.getElementById('submitButton').querySelector('#buttonText').textContent = 'Create Category';
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
    </script>
</body>
</html>