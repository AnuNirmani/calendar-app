<?php
include dirname(__DIR__) . '/../db.php';
include dirname(__DIR__) . '/../auth.php';

// Check authentication
checkAuth();

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
    <link rel="icon" href="../../images/logo.jpg" type="image/png">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .main-content {
            min-height: calc(100vh - 64px);
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex min-h-screen">
        <?php 
        // For sidebar path adjustments - we're in admin/posts/, sidebar links are relative to admin/
        $base_path = '../';
        include dirname(__DIR__) . '/includes/slidebar2.php'; 
        ?>

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
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        // Fix sidebar paths since we're in posts/ subdirectory
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                const links = sidebar.querySelectorAll('a[href]');
                links.forEach(link => {
                    const href = link.getAttribute('href');
                    // Don't modify if it's already relative to posts/ or an absolute path
                    if (!href.startsWith('posts/') && !href.startsWith('../') && !href.startsWith('http') && !href.startsWith('/')) {
                        // Add ../ prefix for links that need to go up to admin/ directory
                        link.setAttribute('href', '../' + href);
                    } else if (href.startsWith('posts/')) {
                        // Remove posts/ prefix since we're already in posts/
                        link.setAttribute('href', href.replace('posts/', ''));
                    }
                });
            }
        });

        // Mobile sidebar toggle
        const sidebar = document.getElementById('sidebar');
        const menuToggle = document.getElementById('menu-toggle');
        const closeSidebar = document.getElementById('close-sidebar');

        if (menuToggle) {
            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('-translate-x-full');
            });
        }

        if (closeSidebar) {
            closeSidebar.addEventListener('click', () => {
                sidebar.classList.add('-translate-x-full');
            });
        }

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
<?php $conn->close(); ?>