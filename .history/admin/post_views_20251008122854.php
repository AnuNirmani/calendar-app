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

// Fetch posts with view counts (assuming a post_views table or simulating views)
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
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 50%, #f1f5f9 100%);
            min-height: 100vh;
            color: #334155;
            line-height: 1.6;
        }

        .main-container {
            min-height: 100vh;
            width: 100%;
            padding: 0;
            display: flex;
        }

        .sidebar {
            width: 250px;
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .sidebar button {
            padding: 12px 16px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f9fafb;
            color: #374151;
        }

        .sidebar button:hover {
            background: #3b82f6;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .header-section {
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid #e2e8f0;
            padding: 30px 40px;
            margin-bottom: 30px;
            position: relative;
        }

        .logout-container {
            position: absolute;
            top: 20px;
            right: 20px;
        }

        .logout-btn {
            background: #ef4444;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 0.9rem;
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.2);
        }

        .logout-btn:hover {
            background: #dc2626;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .logo-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 25px;
        }

        .logo-container img {
            width: 200px;
            height: auto;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .logo-container img:hover {
            transform: scale(1.02);
        }

        .page-title {
            text-align: center;
        }

        .page-title h1 {
            color: #1e293b;
            font-size: 2.5em;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.025em;
        }

        .page-title p {
            color: #64748b;
            font-size: 1.1em;
            font-weight: 400;
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

        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                flex-direction: row;
                justify-content: center;
                padding: 10px;
                border-right: none;
                border-bottom: 1px solid #e2e8f0;
            }

            .sidebar button {
                flex: 1;
                text-align: center;
                font-size: 0.9rem;
                padding: 10px;
            }

            .header-section {
                padding: 20px;
                margin-bottom: 20px;
            }

            .logout-container {
                position: static;
                text-align: center;
                margin-top: 15px;
                margin-bottom: 10px;
            }

            .logout-btn {
                padding: 8px 16px;
                font-size: 0.85rem;
            }

            .content-section {
                padding: 0 20px 20px;
            }

            .table-container {
                padding: 20px;
            }

            table {
                font-size: 0.9rem;
            }

            th, td {
                padding: 12px;
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
            .sidebar {
                flex-direction: column;
                align-items: center;
            }

            .sidebar button {
                width: 100%;
                max-width: 300px;
            }

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
                    <h1>Post Views</h1>
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
        // Auto-hide messages after 5 seconds
        $(document).ready(function() {
            setTimeout(() => {
                const messages = document.querySelectorAll('.success-message, .error-message');
                messages.forEach(msg => {
                    msg.classList.remove('show');
                });
            }, 5000);
        });
    </script>
</body>
</html>            