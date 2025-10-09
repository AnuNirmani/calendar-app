```php
<?php
include '../db.php';
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

// Fetch posts with view counts
$sql = "SELECT p.id, p.title, c.name AS category_name, p.publish_date, 
        (SELECT COUNT(*) FROM post_views pv WHERE pv.post_id = p.id) AS view_count 
        FROM posts p 
        LEFT JOIN categories c ON p.category_id = c.id 
        ORDER BY view_count DESC";
$result = mysqli_query($conn, $sql);
$posts = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $posts[] = $row;
    }
}

// Get messages from session
$successMessage = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$errorMessage = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['success'], $_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../images/logo.jpg" type="image/png">
    <title>Post Views</title>
    
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

        .content-section {
            flex: 1;
            padding: 0 40px 40px;
            display: flex;
            justify-content: center;
        }

        .table-container {
            background: #ffffff;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            width: 100%;
            max-width: 1200px;
            transition: box-shadow 0.3s ease;
        }

        .table-container:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 1rem;
        }

        th, td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: #f8fafc;
            color: #1e293b;
            font-weight: 600;
            font-size: 1.1em;
        }

        td {
            color: #374151;
        }

        tr:hover {
            background: #f0f9ff;
        }

        .view-count {
            color: #3b82f6;
            font-weight: 600;
        }

        .success-message, .error-message {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(-30px);
            width: calc(100% - 40px);
            max-width: 500px;
            background: #ffffff;
            padding: 16px 20px;
            border-radius: 12px;
            text-align: center;
            font-weight: 600;
            font-size: 1rem;
            opacity: 0;
            z-index: 10000;
            transition: all 0.3s ease;
            display: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .success-message {
            color: #166534;
            border-left: 4px solid #10b981;
        }

        .error-message {
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }

        .success-message.show, .error-message.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
            display: block;
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

            .content-section {
                padding: 0 20px 20px;
            }

            .table-container {
                padding: 20px;
            }

            .success-message, .error-message {
                width: calc(100% - 20px);
                padding: 12px 16px;
                font-size: 0.9rem;
            }

            .page-title h1 {
                font-size: 2rem;
            }
        }

        @media (max-width: 480px) {
            .logo-container img {
                width: 140px;
            }

            .page-title h1 {
                font-size: 1.75rem;
            }

            th, td {
                padding: 10px;
                font-size: 0.85rem;
            }
        }

        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
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
                    <li><a href="list_posts.php"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h18M3 12h18M3 17h18"/></svg><span>List Posts</span></a></li>
                    <li><a href="post_views.php" class="active"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg><span>Post Views</span></a></li>
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
                    <h1>Post Views</h1>
                    <p>Track the performance of your blog posts</p>
                </div>
            </div>
            <div class="content-section">
                <div class="table-container">
                    <?php if ($successMessage): ?>
                        <div class="success-message show" id="successMessage">
                            <?= htmlspecialchars($successMessage) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($errorMessage): ?>
                        <div class="error-message show" id="errorMessage">
                            <?= htmlspecialchars($errorMessage) ?>
                        </div>
                    <?php endif; ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Views</th>
                                <th>Publish Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($posts)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #64748b;">
                                        No posts found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($posts as $post): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($post['title']) ?></td>
                                        <td><?= htmlspecialchars($post['category_name'] ?? 'Uncategorized') ?></td>
                                        <td class="view-count"><?= number_format($post['view_count']) ?></td>
                                        <td><?= $post['publish_date'] ? date('M d, Y H:i', strtotime($post['publish_date'])) : '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-hide messages
        $(document).ready(function() {
            setTimeout(() => {
                document.querySelectorAll('.success-message, .error-message').forEach(msg => {
                    msg.classList.remove('show');
                });
            }, 5000);
        });

        // Sidebar toggle for mobile
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>
```