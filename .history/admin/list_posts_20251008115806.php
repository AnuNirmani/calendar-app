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
    <link rel="icon" href="../images/logo.jpg" type="image/png">
    <title>List Posts</title>
    
    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            color: #1e293b;
            line-height: 1.6;
        }

        .main-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1e293b 0%, #2d3748 100%);
            color: #f8fafc;
            padding: 30px 20px;
            position: fixed;
            height: 100%;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            z-index: 1000;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header img {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            margin-right: 10px;
        }

        .sidebar-header span {
            font-size: 1.5rem;
            font-weight: 700;
            color: #f8fafc;
        }

        .sidebar nav ul {
            list-style: none;
        }

        .sidebar nav ul li {
            margin-bottom: 10px;
        }

        .sidebar nav ul li a {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: #d1d5db;
            text-decoration: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .sidebar nav ul li a:hover,
        .sidebar nav ul li a.active {
            background: #3b82f6;
            color: #ffffff;
            transform: translateX(5px);
        }

        .sidebar nav ul li a svg {
            margin-right: 10px;
        }

        .sidebar .logout {
            margin-top: auto;
            padding: 12px 16px;
            background: #ef4444;
            color: #ffffff;
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .sidebar .logout:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }

        /* Content Styling */
        .content-wrapper {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
            background: #f8fafc;
        }

        .header-section {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            text-align: center;
            position: relative;
        }

        .hamburger {
            display: none;
            position: absolute;
            top: 20px;
            left: 20px;
            cursor: pointer;
        }

        .logo-container img {
            width: 150px;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .page-title h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
        }

        .page-title p {
            font-size: 1.1rem;
            color: #64748b;
        }

        .table-container {
            background: #ffffff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            background: #f8fafc;
            font-weight: 600;
            color: #1e293b;
        }

        td img {
            max-width: 100px;
            border-radius: 8px;
        }

        .action-buttons a {
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            margin-right: 10px;
            transition: all 0.3s ease;
        }

        .edit-btn {
            background: #3b82f6;
            color: #ffffff;
        }

        .edit-btn:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }

        .delete-btn {
            background: #ef4444;
            color: #ffffff;
        }

        .delete-btn:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }

        .success-message, .error-message {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 15px;
            border-radius: 8px;
            font-weight: 600;
            z-index: 1000;
            display: none;
        }

        .success-message.show, .error-message.show {
            display: block;
        }

        .success-message {
            background: #d1fae5;
            color: #166534;
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
        }

        @media (max-width: 1024px) {
            .sidebar {
                width: 60px;
                padding: 20px 10px;
            }

            .sidebar-header span,
            .sidebar nav ul li a span {
                display: none;
            }

            .sidebar nav ul li a {
                justify-content: center;
                padding: 10px;
            }

            .content-wrapper {
                margin-left: 60px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 250px;
                z-index: 1001;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .content-wrapper {
                margin-left: 0;
            }

            .hamburger {
                display: block;
            }

            table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="../images/logo.jpg" alt="Logo">
                <span>Blog Admin</span>
            </div>
            <nav>
                <ul>
                    <li><a href="list_posts.php" class="active"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h18M3 12h18M3 17h18"/></svg><span>List Posts</span></a></li>
                    <li><a href="post_views.php"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg><span>Post Views</span></a></li>
                    <li><a href="add_post.php"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg><span>Create Post</span></a></li>
                </ul>
            </nav>
            <a href="?logout=true" class="logout" onclick="return confirm('Are you sure you want to logout?')">Logout</a>
        </div>
        <div class="content-wrapper">
            <div class="header-section">
                <div class="hamburger" onclick="toggleSidebar()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 12h18M3 6h18M3 18h18"/>
                    </svg>
                </div>
                <div class="logo-container">
                    <img src="../images/logo.jpg" alt="Logo">
                </div>
                <div class="page-title">
                    <h1>List of Posts</h1>
                    <p>View and manage all blog posts</p>
                </div>
            </div>
            <div class="content-section">
                <div class="table-container">
                    <?php if ($successMessage): ?>
                        <div class="success-message show"><?= htmlspecialchars($successMessage) ?></div>
                    <?php endif; ?>
                    <?php if ($errorMessage): ?>
                        <div class="error-message show"><?= $errorMessage ?></div>
                    <?php endif; ?>
                    <div class="section-title">All Posts</div>
                    <?php if (empty($posts)): ?>
                        <p>No posts found.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Author</th>
                                    <th>Image</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($posts as $post): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($post['title']) ?></td>
                                        <td><?= htmlspecialchars($post['category_name'] ?: 'Uncategorized') ?></td>
                                        <td><?= htmlspecialchars($post['author']) ?></td>
                                        <td>
                                            <?php if ($post['featured_image']): ?>
                                                <img src="../Uploads/posts/<?= htmlspecialchars($post['featured_image']) ?>" alt="Featured Image">
                                            <?php else: ?>
                                                No Image
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars(ucfirst($post['status'])) ?></td>
                                        <td><?= date('M d, Y', strtotime($post['publish_date'])) ?></td>
                                        <td class="action-buttons">
                                            <a href="edit_post.php?id=<?= $post['id'] ?>" class="edit-btn">Edit</a>
                                            <a href="?delete=<?= $post['id'] ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this post?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-hide messages
        setTimeout(() => {
            document.querySelectorAll('.success-message, .error-message').forEach(msg => {
                msg.classList.remove('show');
            });
        }, 5000);

        // Sidebar toggle for mobile
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>
