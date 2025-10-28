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

// Handle post deletion
if (isset($_GET['delete'])) {
    $post_id = intval($_GET['delete']);
    $sql = "SELECT featured_image FROM posts WHERE id = $post_id";
    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
        $post = mysqli_fetch_assoc($result);
        if ($post['featured_image']) {
            $image_path = '../Uploads/posts/' . $post['featured_image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        $sql = "DELETE FROM posts WHERE id = $post_id";
        if (mysqli_query($conn, $sql)) {
            $_SESSION['success'] = "Post deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete post: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = "Post not found.";
    }
    header("Location: list_posts.php");
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id']) && isset($_POST['status'])) {
    $post_id = intval($_POST['post_id']);
    $status = $_POST['status'] === 'published' ? 'published' : 'draft';
    $sql = "UPDATE posts SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $status, $post_id);
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = "Post status updated successfully!";
    } else {
        $_SESSION['error'] = "Failed to update post status: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
    header("Location: list_posts.php");
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

// Fetch posts with category names
$sql = "SELECT p.*, c.name AS category_name FROM posts p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC";
$result = mysqli_query($conn, $sql);
$posts = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $posts[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List Posts</title>
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
        td img {
            max-width: 100px;
            border-radius: 0.375rem;
        }
        .status-form button {
            padding: 2px 8px;
            font-size: 0.75rem;
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
            <div class="max-w-4xl mx-auto">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">List of Posts</h1>

                <?php if ($successMessage): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded"><?php echo htmlspecialchars($successMessage); ?></div>
                <?php endif; ?>

                <?php if ($errorMessage): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded"><?php echo $errorMessage; ?></div>
                <?php endif; ?>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">All Posts</h2>
                    <?php if (empty($posts)): ?>
                        <p class="text-gray-600 flex items-center justify-center">
                            <span class="text-2xl mr-2">📄</span> No posts found.
                        </p>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="bg-gray-200">
                                        <th class="p-3">Title</th>
                                        <th class="p-3">Category</th>
                                        <th class="p-3">Author</th>
                                        <th class="p-3">Image</th>
                                        <th class="p-3">Status</th>
                                        <th class="p-3">Date</th>
                                        <th class="p-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($posts as $post): ?>
                                        <tr>
                                            <td class="p-3"><?php echo htmlspecialchars($post['title']); ?></td>
                                            <td class="p-3"><?php echo htmlspecialchars($post['category_name'] ?: 'Uncategorized'); ?></td>
                                            <td class="p-3"><?php echo htmlspecialchars($post['author']); ?></td>
                                            <td class="p-3">
                                                <?php if ($post['featured_image']): ?>
                                                    <img src="../Uploads/posts/<?php echo htmlspecialchars($post['featured_image']); ?>" alt="Featured Image">
                                                <?php else: ?>
                                                    No Image
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-3">
                                                <form method="post" action="" class="status-form inline-block">
                                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                    <input type="checkbox" name="status" value="published" <?php echo $post['status'] == 'published' ? 'checked' : ''; ?> class="h-5 w-5 text-green-600 rounded focus:ring-green-500">
                                                    <button type="submit" class="ml-2 bg-blue-500 text-white rounded hover:bg-blue-600">Update</button>
                                                </form>
                                                <span class="ml-2 text-xs font-semibold <?php echo $post['status'] == 'published' ? 'text-green-800' : 'text-gray-800'; ?>">
                                                    <?php echo htmlspecialchars(ucfirst($post['status'])); ?>
                                                </span>
                                            </td>
                                            <td class="p-3"><?php echo date('M d, Y', strtotime($post['publish_date'])); ?></td>
                                            <td class="p-3 flex space-x-2">
                                                <a href="edit_post.php?id=<?php echo $post['id']; ?>" class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600">Edit</a>
                                                <a href="?delete=<?php echo $post['id']; ?>" class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-700" onclick="return confirm('Are you sure you want to delete this post?')">Delete</a>
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
</body>
</html>