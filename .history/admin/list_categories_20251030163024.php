<?php
include '../db.php';
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
            $_SESSION['error'] = "Cannot delete category '$category_name' because it has $post_count post(s) associated with it. Please delete or reassign the posts first.";
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
        }
        .status-inactive {
            background-color: #fef2f2;
            color: #dc2626;
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
            <div class="max-w-6xl mx-auto">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">List of Categories</h1>

                <?php if ($successMessage): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded"><?php echo htmlspecialchars($successMessage); ?></div>
                <?php endif; ?>

                <?php if ($errorMessage): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded"><?php echo $errorMessage; ?></div>
                <?php endif; ?>

                <!-- Search and Add Button -->
                <div class="flex justify-between mb-3">
                    <form method="GET" action="" class="flex w-full max-w-md">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, phone, email, extension, or department" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <button type="submit" class="ml-2 py-2 px-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Search</button>
                        <?php if (!empty($search)): ?>
                            <a href="list_c.php" class="ml-2 py-2 px-4 bg-gray-600 text-white rounded-lg hover:bg-gray-700">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Results Summary -->
                <div class="mb-4 text-sm text-gray-600 bg-white p-3 rounded-lg shadow">
                    <?php if ($total_records > 0): ?>
                        Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> categories
                    <?php else: ?>
                        No categories found
                    <?php endif; ?>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">All Categories</h2>
                    <?php if (empty($categories)): ?>
                        <p class="text-gray-600 flex items-center justify-center py-8">
                            <span class="text-2xl mr-2">📁</span> No categories found.
                        </p>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left table-auto">
                                <thead>
                                    <tr class="bg-gray-200">
                                        <th class="p-3 font-semibold">ID</th>
                                        <th class="p-3 font-semibold">Category Name</th>
                                        <th class="p-3 font-semibold">Posts</th>
                                        <th class="p-3 font-semibold">Status</th>
                                        <th class="p-3 font-semibold">Created Date</th>
                                        <th class="p-3 font-semibold">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                                            <td class="p-3">#<?php echo htmlspecialchars($category['id']); ?></td>
                                            <td class="p-3 font-medium"><?php echo htmlspecialchars($category['name']); ?></td>
                                            <td class="p-3 text-center">
                                                <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-indigo-100 bg-indigo-600 rounded-full">
                                                    <?php echo $category['post_count']; ?>
                                                </span>
                                            </td>
                                            <td class="p-3">
                                                <div class="flex items-center space-x-2">
                                                    <span class="status-badge <?php echo $category['status'] == 'active' ? 'status-active' : 'status-inactive'; ?>">
                                                        <?php echo htmlspecialchars(ucfirst($category['status'])); ?>
                                                    </span>
                                                    <a href="?toggle_status=<?php echo $category['id']; ?>" 
                                                       class="text-gray-500 hover:text-indigo-600 transition-colors text-sm"
                                                       onclick="return confirm('Are you sure you want to change the status of this category?')">
                                                        Toggle
                                                    </a>
                                                </div>
                                            </td>
                                            <td class="p-3 text-sm"><?php echo date('M d, Y', strtotime($category['created_at'])); ?></td>
                                            <td class="p-3">
                                                <div class="flex space-x-2">
                                                    <a href="edit_category.php?id=<?php echo $category['id']; ?>" 
                                                       class="px-3 py-1 bg-green-500 text-white text-sm rounded hover:bg-green-600 transition">
                                                        Edit
                                                    </a>
                                                    <a href="?delete=<?php echo $category['id']; ?>" 
                                                       class="px-3 py-1 bg-red-500 text-white text-sm rounded hover:bg-red-700 transition <?php echo $category['post_count'] > 0 ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                                                       onclick="<?php echo $category['post_count'] > 0 ? 'alert(\'Cannot delete category with posts associated.\'); return false;' : 'return confirm(\'Are you sure you want to delete this category?\')'; ?>">
                                                        Delete
                                                    </a>
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
                                    <a href="?page=1" class="pagination-link">First</a>
                                <?php else: ?>
                                    <span class="disabled">First</span>
                                <?php endif; ?>

                                <!-- Previous page -->
                                <?php if ($current_page > 1): ?>
                                    <a href="?page=<?php echo $current_page - 1; ?>" class="pagination-link">Previous</a>
                                <?php else: ?>
                                    <span class="disabled">Previous</span>
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
                                    <a href="?page=<?php echo $current_page + 1; ?>" class="pagination-link">Next</a>
                                <?php else: ?>
                                    <span class="disabled">Next</span>
                                <?php endif; ?>

                                <!-- Last page -->
                                <?php if ($current_page < $total_pages): ?>
                                    <a href="?page=<?php echo $total_pages; ?>" class="pagination-link">Last</a>
                                <?php else: ?>
                                    <span class="disabled">Last</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-hide success/error messages after 5 seconds
        $(document).ready(function() {
            setTimeout(function() {
                $('.bg-green-100, .bg-red-100').fadeOut('slow');
            }, 5000);
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>