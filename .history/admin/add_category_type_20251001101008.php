<?php
include '../db.php';
require 'add_category_type_functions.php';

// Start session for messages and authentication
session_start();

// Check if user is logged in (optional - add your authentication logic)
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not authenticated
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
    
    <!-- jQuery and Validation CDN -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/jquery.validation/1.19.3/jquery.validate.min.js"></script>
    
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
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-right: 5px;
            font-size: 0.8rem;
        }
        
        .status-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-right: 5px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-active {
            background: #10b981;
            color: white;
        }
        
        .status-inactive {
            background: #6b7280;
            color: white;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .badge-inactive {
            background: #f3f4f6;
            color: #6b7280;
        }
        
        .actions {
            display: flex;
            gap: 5px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        /* Logout Button Styles */
        .logout-container {
            position: absolute;
            top: 20px;
            right: 20px;
        }
        
        .logout-btn {
            background: #ef4444;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .logout-btn:hover {
            background: #dc2626;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        
        .header-section {
            position: relative;
        }
        
        /* Validation Styles */
        .validation-error {
            color: #dc2626;
            font-size: 0.875rem;
            margin-top: 5px;
            display: block;
        }
        
        .error-border {
            border-color: #dc2626 !important;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1) !important;
        }
        
        .input-field {
            position: relative;
            flex: 1;
        }
        
        .input-field input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .input-field input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            outline: none;
        }
        
        .input-field input.error-border:focus {
            border-color: #dc2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }
        
        .input-group {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }
        
        @media (max-width: 768px) {
            .logout-container {
                position: static;
                text-align: center;
                margin-top: 15px;
            }
            
            .logout-btn {
                display: inline-flex;
            }
            
            .input-group {
                flex-direction: column;
            }
            
            .input-field {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <button class="floating-btn" onclick="addNewPost()">
        <span>+</span> Add New Post
    </button>

    <div class="main-container">
        <div class="header-section">
            <!-- Logout Button -->
            <div class="logout-container">
                <a href="?logout=true" class="logout-btn" onclick="return confirm('Are you sure you want to logout?')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
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
                                <input type="text" id="categoryName" name="categoryName" placeholder="Enter category name" required minlength="2" maxlength="50">
                                <!-- Validation errors will appear here -->
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
                                            <button class="edit-btn" onclick="editCategory(<?= $category['id'] ?>, '<?= htmlspecialchars(addslashes($category['name'])) ?>')">
                                                Edit
                                            </button>
                                            <button class="status-btn <?= $category['status'] == 'active' ? 'status-inactive' : 'status-active' ?>" 
                                                    onclick="toggleStatus(<?= $category['id'] ?>, '<?= $category['status'] ?>')">
                                                <?= $category['status'] == 'active' ? 'Deactivate' : 'Activate' ?>
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

        // Initialize jQuery Validation
        $(document).ready(function() {
            // Initialize form validation
            $("#categoryForm").validate({
                rules: {
                    categoryName: {
                        required: true,
                        minlength: 2,
                        maxlength: 50,
                        noSpecialChars: true,
                        noLeadingTrailingSpaces: true,
                        noMultipleSpaces: true,
                        checkDuplicate: true
                    }
                },
                messages: {
                    categoryName: {
                        required: "Please enter a category name",
                        minlength: "Category name must be at least 2 characters long",
                        maxlength: "Category name cannot exceed 50 characters"
                    }
                },
                errorElement: 'span',
                errorClass: 'validation-error',
                errorPlacement: function(error, element) {
                    error.addClass('validation-error');
                    error.insertAfter(element);
                },
                highlight: function(element) {
                    $(element).addClass('error-border');
                },
                unhighlight: function(element) {
                    $(element).removeClass('error-border');
                },
                submitHandler: function(form) {
                    // If validation passes, submit the form
                    form.submit();
                }
            });

            // Custom validation method: No special characters except spaces and hyphens
            $.validator.addMethod("noSpecialChars", function(value, element) {
                return this.optional(element) || /^[a-zA-Z0-9\s\-]+$/.test(value);
            }, "Category name can only contain letters, numbers, spaces, and hyphens");

            // Custom validation method: No leading or trailing spaces
            $.validator.addMethod("noLeadingTrailingSpaces", function(value, element) {
                return this.optional(element) || value.trim() === value;
            }, "Category name cannot have leading or trailing spaces");

            // Custom validation method: No multiple consecutive spaces
            $.validator.addMethod("noMultipleSpaces", function(value, element) {
                return this.optional(element) || !/\s{2,}/.test(value);
            }, "Category name cannot have multiple consecutive spaces");

            // Custom validation method: Check for duplicate category names
            $.validator.addMethod("checkDuplicate", function(value, element) {
                let isDuplicate = false;
                let currentId = $('#categoryId').val();
                
                // Check against existing categories in the table
                $('#categoriesTable tbody tr').each(function() {
                    let rowId = $(this).attr('id') ? $(this).attr('id').replace('category-', '') : '';
                    let categoryName = $(this).find('td:nth-child(2)').text().trim();
                    
                    // Skip the current row if we're editing
                    if (currentId && rowId === currentId) return true;
                    
                    if (categoryName.toLowerCase() === value.toLowerCase().trim()) {
                        isDuplicate = true;
                        return false; // break the loop
                    }
                });
                
                return !isDuplicate;
            }, "This category name already exists");

            // Real-time validation on input change
            $('#categoryName').on('input', function() {
                $(this).valid();
            });

            // Reset validation when cancel button is clicked
            $('#cancelButton').on('click', function() {
                resetForm();
                $("#categoryForm").validate().resetForm();
            });
        });

        function editCategory(id, name) {
            currentEditId = id;
            document.getElementById('categoryId').value = id;
            document.getElementById('categoryName').value = name;
            document.getElementById('buttonText').textContent = 'Update Category';
            document.getElementById('cancelButton').style.display = 'inline-block';
            document.getElementById('categoryName').focus();
            
            // Trigger validation to clear any previous errors
            $("#categoryForm").validate().resetForm();
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