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
        
        $delete_stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $delete_stmt->bind_param("i", $category_id);
        
        if ($delete_stmt->execute()) {
            $_SESSION['success'] = "Category '$category_name' deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete category: " . $conn->error;
        }
        $delete_stmt->close();
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

// Fetch categories with error handling
$categories = [];
$sql = "SELECT * FROM categories ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);

if ($result === false) {
    $errorMessage = "Database error: " . mysqli_error($conn) . "<br>Please make sure the 'categories' table exists in your database.";
} else {
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
}

// Get category statistics
$total_categories = count($categories);
$active_categories = 0;
$inactive_categories = 0;

foreach ($categories as $category) {
    if ($category['status'] == 'active') {
        $active_categories++;
    } else {
        $inactive_categories++;
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
                            Showing <?php echo $total_categories; ?> categor<?php echo $total_categories == 1 ? 'y' : 'ies'; ?>
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
                                        <th class="p-4 border border-gray-300 font-semibold">Status</th>
                                        <th class="p-4 border border-gray-300 font-semibold">Created Date</th>
                                        <th class="p-4 border border-gray-300 font-semibold text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                        <tr class="table-row border-b">
                                            <td class="p-4 border border-gray-300 font-mono">#<?php echo htmlspecialchars($category['id']); ?></td>
                                            <td class="p-4 border border-gray-300 font-medium">
                                                <div class="flex items-center">
                                                    <i class="fas fa-folder text-indigo-500 mr-3"></i>
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </div>
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
                                                    <a href="?delete=<?php echo $category['id']; ?>" 
                                                       class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-700 transition-colors flex items-center"
                                                       onclick="return confirm('Are you sure you want to delete the category \"<?php echo addslashes($category['name']); ?>\"?')"
                                                       title="Delete Category">
                                                        <i class="fas fa-trash mr-1"></i> Delete
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-hide messages after 5 seconds
        setTimeout(function() {
            const successMsg = document.querySelector('.bg-green-100');
            const errorMsg = document.querySelector('.bg-red-100');
            
            if (successMsg) successMsg.style.display = 'none';
            if (errorMsg) errorMsg.style.display = 'none';
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