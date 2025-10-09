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
$category = null;

// Check if category ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "No category ID provided.";
    header("Location: add_category_type.php");
    exit();
}

$categoryId = intval($_GET['id']);
$category = getCategoryById($categoryId); // Assume this function exists in add_category_type_functions.php

if (!$category) {
    $_SESSION['error'] = "Category not found.";
    header("Location: add_category_type.php");
    exit();
}

// Handle form submission for updating category
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryName = trim($_POST['categoryName']);
    
    if (empty($categoryName)) {
        $_SESSION['error'] = "Please enter a category name";
    } else {
        $result = updateCategory($categoryId, $categoryName);
        if ($result === true) {
            $_SESSION['success'] = "Category updated successfully!";
            header("Location: add_category_type.php");
            exit();
        } else {
            $_SESSION['error'] = $result;
        }
    }
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
    <link rel="stylesheet" href="../css/add_category_type.css">
    <link rel="icon" href="../images/logo.jpg" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Category</title>
    
    <!-- jQuery and jQuery Validation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
    
    <style>
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
        
        /* jQuery Validation Styles */
        .required-indicator {
            color: #dc2626;
            margin-left: 3px;
            font-weight: bold;
        }
        
        label.error {
            color: #dc2626;
            font-size: 0.875rem;
            margin-top: 5px;
            display: block;
            font-weight: 500;
        }
        
        input.error {
            border-color: #dc2626 !important;
            background-color: #fef2f2;
        }
        
        input.valid {
            border-color: #10b981;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }
        
        .btn-secondary {
            background: #6b7280;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin-left: 10px;
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
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="header-section">
            <!-- Logout Button -->
            <div class="logout-container">
                <a href="add_category_type.php?logout=true" class="logout-btn" onclick="return confirm('Are you sure you want to logout?')">
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
                <h1>Edit Category</h1>
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
                    <input type="hidden" id="categoryId" name="categoryId" value="<?= $categoryId ?>">
                    <div class="form-group">
                        <label for="categoryName">
                            Category Name
                            <span class="required-indicator">*</span>
                        </label>
                        <div class="input-group">
                            <div class="input-field">
                                <input type="text" id="categoryName" name="categoryName" value="<?= htmlspecialchars($category['name']) ?>" placeholder="Enter category name">
                            </div>
                            <button type="submit" class="btn-primary">Update Category</button>
                            <a href="add_category_type.php" class="btn-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Initialize form validation
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
                highlight: function(element, errorClass, validClass) {
                    $(element).addClass(errorClass).removeClass(validClass);
                },
                unhighlight: function(element, errorClass, validClass) {
                    $(element).removeClass(errorClass).addClass(validClass);
                },
                submitHandler: function(form) {
                    form.submit();
                }
            });
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