<?php
include '../db.php';

// Start session for messages and authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$successMessage = "";
$errorMessage = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $status = $_POST['status'];
    
    // Validate inputs
    if (empty($name)) {
        $errorMessage = "Category name is required.";
    } else {
        // Check if category already exists
        $check_sql = "SELECT id FROM categories WHERE name = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errorMessage = "Category '$name' already exists.";
        } else {
            // Insert new category
            $insert_sql = "INSERT INTO categories (name, status) VALUES (?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ss", $name, $status);
            
            if ($insert_stmt->execute()) {
                $_SESSION['success'] = "Category '$name' created successfully!";
                header("Location: list_categories.php");
                exit();
            } else {
                $errorMessage = "Error creating category: " . $conn->error;
            }
            $insert_stmt->close();
        }
        $stmt->close();
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Category</title>
    <link rel="icon" href="../images/logo.jpg" type="image/png">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
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
            <div class="max-w-2xl mx-auto">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Create New Category</h1>

                <?php if ($successMessage): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                        <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                <?php endif; ?>

                <?php if ($errorMessage): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                        <?php echo htmlspecialchars($errorMessage); ?>
                    </div>
                <?php endif; ?>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <form method="POST" action="" class="space-y-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Category Name <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="name" 
                                name="name" 
                                value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Enter category name"
                                required
                            >
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                Status <span class="text-red-500">*</span>
                            </label>
                            <select 
                                id="status" 
                                name="status" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                required
                            >
                                <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] == 'active') ? 'selected' : 'selected'; ?>>Active</option>
                                <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                            <p class="text-sm text-gray-500 mt-1">
                                Active categories will be available for use in posts.
                            </p>
                        </div>

                        <div class="flex space-x-4 pt-4">
                            <button 
                                type="submit" 
                                class="flex-1 bg-indigo-600 text-white py-3 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors"
                            >
                                Create Category
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Quick Stats -->
                <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <?php
                    // Get category stats
                    $total_categories = 0;
                    $active_categories = 0;
                    $inactive_categories = 0;
                    
                    $stats_sql = "SELECT status, COUNT(*) as count FROM categories GROUP BY status";
                    $stats_result = mysqli_query($conn, $stats_sql);
                    
                    if ($stats_result) {
                        while ($row = mysqli_fetch_assoc($stats_result)) {
                            $total_categories += $row['count'];
                            if ($row['status'] == 'active') {
                                $active_categories = $row['count'];
                            } elseif ($row['status'] == 'inactive') {
                                $inactive_categories = $row['count'];
                            }
                        }
                    }
                    ?>
                    
                    <div class="bg-white p-4 rounded-lg shadow-md text-center">
                        <div class="text-2xl font-bold text-indigo-600"><?php echo $total_categories; ?></div>
                        <div class="text-gray-600">Total Categories</div>
                    </div>
                    
                    <div class="bg-white p-4 rounded-lg shadow-md text-center">
                        <div class="text-2xl font-bold text-green-600"><?php echo $active_categories; ?></div>
                        <div class="text-gray-600">Active Categories</div>
                    </div>
                    
                    <div class="bg-white p-4 rounded-lg shadow-md text-center">
                        <div class="text-2xl font-bold text-gray-600"><?php echo $inactive_categories; ?></div>
                        <div class="text-gray-600">Inactive Categories</div>
                    </div>
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
    </script>
</body>
</html>