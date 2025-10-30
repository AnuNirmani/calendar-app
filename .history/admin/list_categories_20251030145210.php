<?php
include '../db.php';

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

// Handle category deletion
if (isset($_GET['delete'])) {
    $category_id = intval($_GET['delete']);
    
    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $category = $result->fetch_assoc();
        $category_name = $category['name'];
        
        // Check if there are posts associated with this category
        $check_stmt = $conn->prepare("SELECT COUNT(*) as post_count FROM posts WHERE category_id = ?");
        $check_stmt->bind_param("i", $category_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $post_data = $check_result->fetch_assoc();
        $post_count = $post_data['post_count'];
        $check_stmt->close();
        
        if ($post_count > 0) {
            // Option 1: Prevent deletion and show error
            $_SESSION['error'] = "Cannot delete category '$category_name' because it has $post_count post(s) associated with it. Please delete or reassign the posts first.";
            
            // Option 2: Delete associated posts first (uncomment the lines below to enable this)
            /*
            $delete_posts_stmt = $conn->prepare("DELETE FROM posts WHERE category_id = ?");
            $delete_posts_stmt->bind_param("i", $category_id);
            $delete_posts_stmt->execute();
            $delete_posts_stmt->close();
            
            // Then delete the category
            $delete_stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
            $delete_stmt->bind_param("i", $category_id);
            
            if ($delete_stmt->execute()) {
                $_SESSION['success'] = "Category '$category_name' and its $post_count post(s) deleted successfully!";
            } else {
                $_SESSION['error'] = "Failed to delete category: " . $conn->error;
            }
            $delete_stmt->close();
            */
        } else {
            // No posts associated, safe to delete
            $delete_stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
            $delete_stmt->bind_param("i", $category_id);
            
            if ($delete_stmt->execute()) {
                $_SESSION['success'] = "Category '$category_name' deleted successfully!";
            } else {
                $_SESSION['error'] = "Failed to delete category: " . $conn->error;
            }
            $delete_stmt->close();
        }
    } else {
        $_SESSION['error'] = "Category not found.";
    }
    $stmt->close();
    
    header("Location: list_categories.php");
    exit();
}

// Handle status toggle
if (isset($_GET['toggle_status'])) {
    $category_id = intval($_GET['toggle_status']);
    
    $stmt = $conn->prepare("SELECT id, name, status FROM categories WHERE id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $category = $result->fetch_assoc();
        $new_status = $category['status'] == 'active' ? 'inactive' : 'active';
        
        $update_stmt = $conn->prepare("UPDATE categories SET status = ? WHERE id = ?");
        $update_stmt->bind_param("si", $new_status, $category_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Category '{$category['name']}' status changed to $new_status!";
        } else {
            $_SESSION['error'] = "Failed to update category status: " . $conn->error;
        }
        $update_stmt->close();
    } else {
        $_SESSION['error'] = "Category not found.";
    }
    $stmt->close();
    
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

// Pagination configuration
$records_per_page = 10;

// Get current page
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

// Calculate offset
$offset = ($current_page - 1) * $records_per_page;

// Build count query for total records
$count_sql = "SELECT COUNT(*) as total FROM categories";
$count_result = mysqli_query($conn, $count_sql);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Ensure current page is within valid range
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
}

// Fetch categories with post count and pagination
$sql = "SELECT c.*, COUNT(p.id) as post_count 
        FROM categories c 
        LEFT JOIN posts p ON c.id = p.category_id 
        GROUP BY c.id 
        ORDER BY c.created_at DESC
        LIMIT $offset, $records_per_page";
$result = mysqli_query($conn, $sql);

$categories = [];
if ($result === false) {
    $errorMessage = "Database error: " . mysqli_error($conn) . "<br>Please make sure the 'categories' table exists in your database.";
} else {
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
}

// Get category statistics (for all categories, not just current page)
$stats_sql = "SELECT 
                COUNT(*) as total_categories,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_categories,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_categories
              FROM categories";
$stats_result = mysqli_query($conn, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

$total_categories = $stats['total_categories'];
$active_categories = $stats['active_categories'];
$inactive_categories = $stats['inactive_categories'];
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
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        .status-active {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .status-inactive {
            background-color: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        .table-row:hover {
            background-color: #f9fafb;
        }
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
            gap: 8px;
        }
        .pagination a, .pagination span {
            padding: 6px 12px;
            border: 1px solid #ddd;
            text-decoration: none;
            color: #333;
            border-radius: 4px;
            transition: all 0.3s;
            font-size: 0.875rem;
        }
        .pagination a:hover {
            background-color: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        .pagination .current {
            background-color: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        .pagination .disabled {
            color: #9ca3af;
            pointer-events: none;
            background-color: #f3f4f6;
            border-color: #d1d5db;
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
                        <i class="fas fa-plus-circle mr-2"></i>Create Category
                    </a>
                    <a href="list_categories.php" class="btn-nav block w-full text-left py-3 px-4 bg-teal-600 text-white rounded-lg hover:bg-teal-700">
                        <i class="fas fa-list mr-2"></i>List Categories
                    </a>
                    <a href="add_post.php" class="btn-nav block w-full text-left py-3 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        <i class="fas fa-edit mr-2"></i>Add New Post
                    </a>
                    <a href="list_posts.php" class="btn-nav block w-full text-left py-3 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-newspaper mr-2"></i>List Posts
                    </a>
                    <a href="add_telephone_directory.php" class="btn-nav block w-full text-left py-3 px-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        <i class="fas fa-phone mr-2"></i>Add Telephone Directory
                    </a>
                    <a href="list_telephone_directory.php" class="btn-nav block w-full text-left py-3 px-4 bg-teal-600 text-white rounded-lg hover:bg-teal-700">
                        <i class="fas fa-address-book mr-2"></i>List Telephone Directory
                    </a>
                    <a href="dashboard.php" class="btn-nav block w-full text-left py-3 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        <i class="fas fa-tachometer-alt mr-2"></i>Admin Dashboard
                    </a>
                </nav>
            </div>
            <div class="mt-auto">
                <a href="?logout=true" class="logout-btn flex items-center justify-center py-3 px-4 bg-red-600 text-white rounded-lg hover:bg-red-700" onclick="return confirm('Are you sure you want to logout?')">
                    <i class="fas fa-sign-out-alt mr-2"></i>
                    Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content flex-1 p-8 overflow-y-auto">
            <div class="max-w-6xl mx-auto">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold text-gray-800">List of Categories</h1>
                    <a href="create_category.php" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                        <i class="fas fa-plus-circle mr-2"></i>Create New Category
                    </a>
                </div>

                <?php if ($successMessage): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                <?php endif; ?>

                <?php if ($errorMessage): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo $errorMessage; ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-indigo-500">
                        <div class="flex items-center">
                            <div class="bg-indigo-100 p-3 rounded-full">
                                <i class="fas fa-folder text-indigo-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-2xl font-bold text-gray-800"><?php echo $total_categories; ?></h3>
                                <p class="text-gray-600">Total Categories</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-green-500">
                        <div class="flex items-center">
                            <div class="bg-green-100 p-3 rounded-full">
                                <i class="fas fa-check-circle text-green-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-2xl font-bold text-gray-800"><?php echo $active_categories; ?></h3>
                                <p class="text-gray-600">Active Categories</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white p-6 rounded-lg shadow-md border-l-4 border-red-500">
                        <div class="flex items-center">
                            <div class="bg-red-100 p-3 rounded-full">
                                <i class="fas fa-pause-circle text-red-600 text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-2xl font-bold text-gray-800"><?php echo $inactive_categories; ?></h3>
                                <p class="text-gray-600">Inactive Categories</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-gray-800">All Categories</h2>
                        <div class="text-sm text-gray-600">
                            <?php if ($total_records > 0): ?>
                                Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> categor<?php echo $total_records == 1 ? 'y' : 'ies'; ?>
                            <?php else: ?>
                                No categories found
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (empty($categories)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-folder-open text-6xl text-gray-300 mb-4"></i>
                            <h3 class="text-xl font-semibold text-gray-600 mb-2">No Categories Found</h3>
                            <p class="text-gray-500 mb-6">Get started by creating your first category.</p>
                            <a href="create_category.php" class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition-colors">
                                <i class="fas fa-plus-circle mr-2"></i>Create Your First Category
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-200">
                                        <th class="p-4 border border-gray-300 font-semibold">ID</th>
                                        <th class="p-4 border border-gray-300 font-semibold">Category Name</th>
                                        <th class="p-4 border border-gray-300 font-semibold">Posts</th>
                                        <th class="p-4 border border-gray-300 font-semibold">Status</th>
                                        <th class="p-4 border border-gray-300 font-semibold">Created Date</th>
                                        <th class="p-4 border border-gray-300 font-semibold text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                        <tr class="table-row border-b" id="category-row-<?php echo $category['id']; ?>">
                                            <td class="p-4 border border-gray-300 font-mono">#<?php echo htmlspecialchars($category['id']); ?></td>
                                            <td class="p-4 border border-gray-300 font-medium">
                                                <div class="flex items-center">
                                                    <i class="fas fa-folder text-indigo-500 mr-3"></i>
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </div>
                                            </td>
                                            <td class="p-4 border border-gray-300 text-center">
                                                <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-indigo-100 bg-indigo-600 rounded-full">
                                                    <?php echo $category['post_count']; ?>
                                                </span>
                                            </td>
                                            <td class="p-4 border border-gray-300">
                                                <div class="flex items-center space-x-2">
                                                    <span class="status-badge <?php echo $category['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>">
                                                        <i class="fas <?php echo $category['status'] == 'active' ? 'fa-check-circle' : 'fa-pause-circle'; ?> mr-1"></i>
                                                        <?php echo htmlspecialchars(ucfirst($category['status'])); ?>
                                                    </span>
                                                    <a href="?toggle_status=<?php echo $category['id']; ?>" 
                                                       class="text-gray-500 hover:text-indigo-600 transition-colors"
                                                       title="Toggle Status">
                                                        <i class="fas fa-sync-alt text-sm"></i>
                                                    </a>
                                                </div>
                                            </td>
                                            <td class="p-4 border border-gray-300">
                                                <div class="flex items-center text-gray-600">
                                                    <i class="far fa-calendar mr-2"></i>
                                                    <?php echo date('M d, Y', strtotime($category['created_at'])); ?>
                                                </div>
                                            </td>
                                            <td class="p-4 border border-gray-300">
                                                <div class="flex justify-center space-x-2">
                                                    <a href="edit_category.php?id=<?php echo $category['id']; ?>" 
                                                       class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition-colors flex items-center"
                                                       title="Edit Category">
                                                        <i class="fas fa-edit mr-1"></i> Edit
                                                    </a>
                                                    <button 
                                                       onclick="confirmDelete(<?php echo $category['id']; ?>, '<?php echo addslashes($category['name']); ?>', <?php echo $category['post_count']; ?>)"
                                                       class="delete-btn px-4 py-2 bg-red-500 text-white rounded hover:bg-red-700 transition-colors flex items-center <?php echo $category['post_count'] > 0 ? 'opacity-50' : ''; ?>"
                                                       title="<?php echo $category['post_count'] > 0 ? 'Category has posts' : 'Delete Category'; ?>"
                                                       data-category-id="<?php echo $category['id']; ?>">
                                                        <i class="fas fa-trash mr-1"></i> Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination mt-6">
                                <!-- First page -->
                                <?php if ($current_page > 1): ?>
                                    <a href="?page=1" class="pagination-link">
                                        <i class="fas fa-angle-double-left mr-1"></i> First
                                    </a>
                                <?php else: ?>
                                    <span class="disabled">
                                        <i class="fas fa-angle-double-left mr-1"></i> First
                                    </span>
                                <?php endif; ?>

                                <!-- Previous page -->
                                <?php if ($current_page > 1): ?>
                                    <a href="?page=<?php echo $current_page - 1; ?>" class="pagination-link">
                                        <i class="fas fa-angle-left mr-1"></i> Previous
                                    </a>
                                <?php else: ?>
                                    <span class="disabled">
                                        <i class="fas fa-angle-left mr-1"></i> Previous
                                    </span>
                                <?php endif; ?>

                                <!-- Page numbers -->
                                <?php
                                $start_page = max(1, $current_page - 2);
                                $end_page = min($total_pages, $current_page + 2);
                                
                                for ($i = $start_page; $i <= $end_page; $i++):
                                ?>
                                    <?php if ($i == $current_page): ?>
                                        <span class="current"><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="?page=<?php echo $i; ?>" class="pagination-link"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <!-- Next page -->
                                <?php if ($current_page < $total_pages): ?>
                                    <a href="?page=<?php echo $current_page + 1; ?>" class="pagination-link">
                                        Next <i class="fas fa-angle-right ml-1"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="disabled">
                                        Next <i class="fas fa-angle-right ml-1"></i>
                                    </span>
                                <?php endif; ?>

                                <!-- Last page -->
                                <?php if ($current_page < $total_pages): ?>
                                    <a href="?page=<?php echo $total_pages; ?>" class="pagination-link">
                                        Last <i class="fas fa-angle-double-right ml-1"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="disabled">
                                        Last <i class="fas fa-angle-double-right ml-1"></i>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div id="deleteModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 transform transition-all">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 text-center mb-2">Delete Category</h3>
                <p class="text-gray-600 text-center mb-4" id="deleteMessage"></p>
                <div id="warningMessage" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4 hidden">
                    <div class="flex">
                        <i class="fas fa-exclamation-circle text-yellow-400 mr-2 mt-0.5"></i>
                        <p class="text-sm text-yellow-700" id="warningText"></p>
                    </div>
                </div>
                <div class="flex gap-3 mt-6">
                    <button onclick="closeDeleteModal()" class="flex-1 px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </button>
                    <button onclick="proceedDelete()" id="confirmDeleteBtn" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-trash mr-1"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let deleteUrl = '';
        let currentCategoryId = null;

        function confirmDelete(categoryId, categoryName, postCount) {
            const modal = document.getElementById('deleteModal');
            const deleteMessage = document.getElementById('deleteMessage');
            const warningMessage = document.getElementById('warningMessage');
            const warningText = document.getElementById('warningText');
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            
            // Store category ID for later use
            currentCategoryId = categoryId;
            
            // Set the delete URL
            deleteUrl = '?delete=' + categoryId;
            
            // Set the main message
            deleteMessage.innerHTML = 'Are you sure you want to delete the category <strong>"' + categoryName + '"</strong>?';
            
            // Show warning if category has posts
            if (postCount > 0) {
                warningMessage.classList.remove('hidden');
                warningText.innerHTML = '<strong>Warning:</strong> This category has <strong>' + postCount + ' post(s)</strong> associated with it. You need to delete or reassign these posts before deleting this category.';
                confirmBtn.classList.add('opacity-50', 'cursor-not-allowed');
                confirmBtn.disabled = true;
            } else {
                warningMessage.classList.add('hidden');
                confirmBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                confirmBtn.disabled = false;
            }
            
            // Show modal
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeDeleteModal() {
            const modal = document.getElementById('deleteModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            
            // Reset button state
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            confirmBtn.innerHTML = '<i class="fas fa-trash mr-1"></i> Delete';
            confirmBtn.disabled = false;
        }

        function proceedDelete() {
            // Show loading state
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Deleting...';
            confirmBtn.disabled = true;
            
            // Use AJAX to delete the category
            fetch(deleteUrl)
                .then(response => {
                    // Remove the row from the table with animation
                    const row = document.getElementById('category-row-' + currentCategoryId);
                    if (row) {
                        row.style.transition = 'all 0.3s ease';
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(-20px)';
                        
                        setTimeout(() => {
                            row.remove();
                            
                            // Check if table is empty
                            const tbody = document.querySelector('tbody');
                            if (tbody && tbody.children.length === 0) {
                                location.reload();
                            } else {
                                // Update statistics and pagination info
                                updatePageInfo();
                            }
                        }, 300);
                    }
                    
                    // Close modal
                    closeDeleteModal();
                    
                    // Show success message
                    showSuccessMessage('Category deleted successfully!');
                })
                .catch(error => {
                    console.error('Error:', error);
                    closeDeleteModal();
                    showErrorMessage('Failed to delete category. Please try again.');
                });
        }

        function updatePageInfo() {
            // Update the "Showing X categories" text
            const showingText = document.querySelector('.text-sm.text-gray-600');
            if (showingText) {
                const tbody = document.querySelector('tbody');
                const count = tbody ? tbody.children.length : 0;
                // We need to reload to get accurate count since we don't know the total after deletion
                setTimeout(() => {
                    location.reload();
                }, 1000);
            }
        }

        function showSuccessMessage(message) {
            const existingMsg = document.querySelector('.bg-green-100');
            if (existingMsg) {
                existingMsg.remove();
            }
            
            const successDiv = document.createElement('div');
            successDiv.className = 'bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded flex items-center';
            successDiv.innerHTML = '<i class="fas fa-check-circle mr-2"></i>' + message;
            
            const mainContent = document.querySelector('.max-w-6xl');
            const firstChild = mainContent.children[1];
            mainContent.insertBefore(successDiv, firstChild);
            
            setTimeout(() => {
                successDiv.style.transition = 'all 0.3s ease';
                successDiv.style.opacity = '0';
                setTimeout(() => successDiv.remove(), 300);
            }, 5000);
        }

        function showErrorMessage(message) {
            const existingMsg = document.querySelector('.bg-red-100');
            if (existingMsg) {
                existingMsg.remove();
            }
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded flex items-center';
            errorDiv.innerHTML = '<i class="fas fa-exclamation-circle mr-2"></i>' + message;
            
            const mainContent = document.querySelector('.max-w-6xl');
            const firstChild = mainContent.children[1];
            mainContent.insertBefore(errorDiv, firstChild);
            
            setTimeout(() => {
                errorDiv.style.transition = 'all 0.3s ease';
                errorDiv.style.opacity = '0';
                setTimeout(() => errorDiv.remove(), 300);
            }, 5000);
        }

        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeDeleteModal();
            }
        });

        // Auto-hide messages after 5 seconds
        setTimeout(function() {
            const successMsg = document.querySelector('.bg-green-100');
            const errorMsg = document.querySelector('.bg-red-100');
            
            if (successMsg) {
                successMsg.style.transition = 'all 0.3s ease';
                successMsg.style.opacity = '0';
                setTimeout(() => successMsg.remove(), 300);
            }
            if (errorMsg) {
                errorMsg.style.transition = 'all 0.3s ease';
                errorMsg.style.opacity = '0';
                setTimeout(() => errorMsg.remove(), 300);
            }
        }, 5000);

        // Add confirmation for status toggle
        document.addEventListener('DOMContentLoaded', function() {
            const toggleLinks = document.querySelectorAll('a[href*="toggle_status"]');
            toggleLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to change the status of this category?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>