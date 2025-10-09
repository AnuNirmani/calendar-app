<?php
// category_list.php
include '../db.php';
require 'add_category_type_functions.php';

session_start();

if (!isset($_SESSION['user_id'])) {
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
    
    header("Location: category_list.php");
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
        
        header("Location: category_list.php");
        exit();
    }
}

// Get messages from session
$successMessage = isset($_SESSION['success']) ? $_SESSION['success'] : '';
unset($_SESSION['success']);

$errorMessage = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);

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
    <title>Category List</title>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    
    <style>
        /* Reuse dashboard styles for sidebar and main-content */
        body {
            display: flex;
            min-height: 100vh;
            margin: 0;
            background: #f9fafb;
        }
        
        .sidebar {
            width: 250px;
            background: #1f2937;
            color: white;
            padding: 20px;
            position: fixed;
            height: 100%;
            overflow-y: auto;
        }
        
        .sidebar .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .sidebar .logo-container img {
            width: 120px;
            border-radius: 8px;
        }
        
        .sidebar nav ul {
            list-style: none;
            padding: 0;
        }
        
        .sidebar nav ul li {
            margin: 10px 0;
        }
        
        .sidebar nav ul li a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 12px 20px;
            border-radius: 8px;
            transition: background 0.3s;
        }
        
        .sidebar nav ul li a:hover {
            background: #374151;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 40px;
            width: calc(100% - 250px);
        }
        
        /* Modal and other styles from original */
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
        
        .add-new-btn {
            background: #3b82f6;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            margin-bottom: 20px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo-container">
            <img src="../images/logo.jpg" alt="Logo">
        </div>
        <nav>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="category_list.php">Manage Categories</a></li>
                <li><a href="add_post.php">Add New Post</a></li>
                <li><a href="dashboard.php?logout=true" onclick="return confirm('Are you sure you want to logout?')">Logout</a></li>
            </ul>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <h1>Category List</h1>
        
        <?php if ($successMessage): ?>
            <div class="success-message show"><?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="error-message show"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>
        
        <a href="category_form.php" class="add-new-btn">Create New Category</a>
        
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
                                    <a href="category_form.php?id=<?= $category['id'] ?>" class="edit-btn">Edit</a>
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
    </main>

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
        let currentDeleteId = null;

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