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
        $delete_stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $delete_stmt->bind_param("i", $category_id);
        
        if ($delete_stmt->execute()) {
            $_SESSION['success'] = "Category deleted successfully!";
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

// Get messages from session
if (isset($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $errorMessage = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Fetch categories with error handling and check for status column
$categories = [];
$sql = "SELECT * FROM categories ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);

if ($result === false) {
    $errorMessage = "Database error: " . mysqli_error($conn) . "<br>Please make sure the 'categories' table exists in your database.";
} else {
    // Check if status column exists in the table
    $columns = [];
    $check_columns = mysqli_query($conn, "SHOW COLUMNS FROM categories");
    while ($col = mysqli_fetch_assoc($check_columns)) {
        $columns[] = $col['Field'];
    }
    
    $hasStatusColumn = in_array('status', $columns);
    
    if (!$hasStatusColumn) {
        $errorMessage = "Warning: The 'status' column is missing from the categories table. Please run the database setup.";
    }
    
    while ($row = mysqli_fetch_assoc($result)) {
        // If status column doesn't exist, set a default value
        if (!$hasStatusColumn) {
            $row['status'] = 'active'; // Default value
        }
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
            display: inline-block;
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
            background-color: #f3f4f6;
            color: #374151;
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
        <div class="main-content flex-1 p-8 overflow-y-auto">
            <div class="max-w-4xl mx-auto">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">List of Categories</h1>

                <?php if ($successMessage): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                        <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                <?php endif; ?>

                <?php if ($errorMessage): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                        <?php echo $errorMessage; ?>
                        <?php if (strpos($errorMessage, 'status column is missing') !== false): ?>
                            <div class="mt-2">
                                <button onclick="addStatusColumn()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                                    Add Status Column Automatically
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">All Categories</h2>
                    <?php if (empty($categories)): ?>
                        <p class="text-gray-600 flex items-center justify-center py-8">
                            <span class="text-2xl mr-2">📁</span> No categories found.
                        </p>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-gray-200">
                                        <th class="p-3 border border-gray-300">ID</th>
                                        <th class="p-3 border border-gray-300">Category Name</th>
                                        <th class="p-3 border border-gray-300">Status</th>
                                        <th class="p-3 border border-gray-300">Created Date</th>
                                        <th class="p-3 border border-gray-300">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                        <tr class="border-b hover:bg-gray-50">
                                            <td class="p-3 border border-gray-300"><?php echo htmlspecialchars($category['id']); ?></td>
                                            <td class="p-3 border border-gray-300 font-medium"><?php echo htmlspecialchars($category['name']); ?></td>
                                            <td class="p-3 border border-gray-300">
                                                <?php 
                                                $status = isset($category['status']) ? $category['status'] : 'active';
                                                $statusClass = $status == 'active' ? 'status-active' : 'status-inactive';
                                                ?>
                                                <span class="status-badge <?php echo $statusClass; ?>">
                                                    <?php echo htmlspecialchars(ucfirst($status)); ?>
                                                </span>
                                            </td>
                                            <td class="p-3 border border-gray-300"><?php echo date('M d, Y', strtotime($category['created_at'])); ?></td>
                                            <td class="p-3 border border-gray-300">
                                                <div class="flex space-x-2">
                                                    <a href="edit_category.php?id=<?php echo $category['id']; ?>" class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600 transition">Edit</a>
                                                    <a href="?delete=<?php echo $category['id']; ?>" class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-700 transition" onclick="return confirm('Are you sure you want to delete the category \"<?php echo addslashes($category['name']); ?>\"?')">Delete</a>
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
        function addStatusColumn() {
            if (confirm('This will add the status column to your categories table. Continue?')) {
                fetch('add_status_column.php')
                    .then(response => response.text())
                    .then(data => {
                        alert(data);
                        location.reload();
                    })
                    .catch(error => {
                        alert('Error: ' + error);
                    });
            }
        }

        // Auto-hide messages after 5 seconds
        $(document).ready(function() {
            setTimeout(function() {
                $('.bg-green-100, .bg-red-100').fadeOut('slow');
            }, 5000);
        });
    </script>
</body>
</html>