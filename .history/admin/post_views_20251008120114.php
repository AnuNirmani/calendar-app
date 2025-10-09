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

// Fetch post views
$sql = "SELECT p.title, COUNT(pv.id) as view_count 
        FROM posts p 
        LEFT JOIN post_views pv ON p.id = pv.post_id 
        GROUP BY p.id, p.title 
        ORDER BY view_count DESC";
$result = mysqli_query($conn, $sql);
$views = [];
$labels = [];
$data = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $views[] = $row;
        $labels[] = $row['title'];
        $data[] = $row['view_count'];
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
    <link rel="icon" href="../images/logo.jpg" type="image/png">
    <title>Post Views</title>
    
    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
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

        .chart-container {
            background: #ffffff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            max-width: 900px;
            margin: 0 auto 30px;
        }

        .table-container {
            background: #ffffff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            max-width: 900px;
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
                <?php if ($successMessage): ?>
                    <div class="success-message show"><?= htmlspecialchars($successMessage) ?></div>
                <?php endif; ?>
                <?php if ($errorMessage): ?>
                    <div class="error-message show"><?= $errorMessage ?></div>
                <?php endif; ?>
                <div class="chart-container">
                    <div class="section-title">View Statistics</div>
                    <canvas id="viewsChart"></canvas>
                </div>
                <div class="table-container">
                    <div class="section-title">Post View Counts</div>
                    <?php if (empty($views)): ?>
                        <p>No views recorded.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Post Title</th>
                                    <th>View Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($views as $view): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($view['title']) ?></td>
                                        <td><?= $view['view_count'] ?></td>
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
        // Initialize Chart.js
        const ctx = document.getElementById('viewsChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'Views',
                    data: <?php echo json_encode($data); ?>,
                    backgroundColor: '#3b82f6',
                    borderRadius: 8
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });

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
