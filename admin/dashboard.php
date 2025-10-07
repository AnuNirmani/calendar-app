<?php
include '../db.php';
include '../auth.php';

// Check if user is authenticated
checkAuth();

// Auto logout after inactivity
$timeout = 900; // 15 minutes = 900 seconds
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout) {
    session_unset();
    session_destroy();
    header("Location: ../login.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// Get some statistics for the dashboard
$stmt = $conn->query("SELECT COUNT(*) as total_dates FROM special_dates");
$totalDates = $stmt->fetch_assoc()['total_dates'];

$stmt = $conn->query("SELECT COUNT(*) as total_types FROM special_types");
$totalTypes = $stmt->fetch_assoc()['total_types'];

$stmt = $conn->query("SELECT COUNT(*) as current_year_dates FROM special_dates WHERE YEAR(date) = YEAR(CURDATE())");
$currentYearDates = $stmt->fetch_assoc()['current_year_dates'];

if (isSuperAdmin()) {
    $stmt = $conn->query("SELECT COUNT(*) as total_users FROM users");
    $totalUsers = $stmt->fetch_assoc()['total_users'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../css/fonts/fonts.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../images/logo.jpg" type="image/png">
    <style>
        body {
            margin: 0;
            font-family: 'Inter', 'Segoe UI', Tahoma, sans-serif;
            background: #f5f7fa;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 30px 0;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-header {
            text-align: center;
            padding: 0 20px 30px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 30px;
        }

        .sidebar-header h2 {
            color: white;
            font-size: 24px;
            margin: 0 0 10px 0;
            text-shadow: none;
        }

        .user-info {
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 12px;
            margin: 20px;
            text-align: center;
        }

        .user-info .username {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .user-info .role-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 5px;
        }

        .role-badge.super-admin {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .role-badge.admin {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin: 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 15px 30px;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            background: transparent;
            border-radius: 0;
            margin: 0;
        }

        .sidebar-menu a:hover {
            background: rgba(255,255,255,0.1);
            border-left-color: #fff;
            transform: none;
            box-shadow: none;
        }

        .sidebar-menu a.active {
            background: rgba(255,255,255,0.15);
            border-left-color: #fff;
            font-weight: 600;
        }

        .sidebar-menu a i {
            margin-right: 15px;
            font-size: 18px;
            width: 25px;
            text-align: center;
        }

        .logout-btn {
            margin: 30px 20px 20px;
            display: block;
            text-align: center;
            padding: 12px 20px;
            background: rgba(244, 67, 54, 0.2) !important;
            color: white !important;
            border: 2px solid rgba(244, 67, 54, 0.5);
            border-radius: 10px !important;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .logout-btn:hover {
            background: #f44336 !important;
            border-color: #f44336;
            transform: translateY(-2px) !important;
            box-shadow: 0 5px 15px rgba(244, 67, 54, 0.3) !important;
        }

        /* Main Content Area */
        .main-content {
            margin-left: 280px;
            padding: 40px;
            flex: 1;
            background: #f5f7fa;
        }

        .page-header {
            margin-bottom: 40px;
        }

        .page-header h1 {
            color: #1e3c72;
            font-size: 32px;
            margin-bottom: 10px;
            text-shadow: none;
        }

        .page-header p {
            color: #666;
            font-size: 16px;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border-left: 4px solid #1e3c72;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.12);
        }

        .stat-card .stat-icon {
            font-size: 36px;
            margin-bottom: 15px;
            color: #1e3c72;
        }

        .stat-card .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #1e3c72;
            margin-bottom: 5px;
        }

        .stat-card .stat-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Quick Actions Section */
        .quick-actions {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .quick-actions h2 {
            color: #1e3c72;
            font-size: 24px;
            margin-bottom: 25px;
            text-shadow: none;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px 20px !important;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            color: white !important;
            text-decoration: none;
            border-radius: 15px !important;
            transition: all 0.3s ease;
            min-height: 150px;
            text-align: center;
            margin: 0 !important;
            border: none;
        }

        .action-btn:hover {
            transform: translateY(-5px) !important;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3) !important;
        }

        .action-btn.primary {
            background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%) !important;
        }

        .action-btn.success {
            background: linear-gradient(135deg, #4caf50 0%, #45a049 100%) !important;
        }

        .action-btn.warning {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%) !important;
        }

        .action-btn.purple {
            background: linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%) !important;
        }

        .action-btn i {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .action-btn span {
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .dashboard-container {
                flex-direction: column;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                grid-template-columns: 1fr;
            }
        }

        .footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 13px;
            margin-top: 40px;
            border-top: 1px solid rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-calendar-alt"></i> Calendar App</h2>
            </div>

            <div class="user-info">
                <div class="username">👤 <?= htmlspecialchars($_SESSION['username']) ?></div>
                <span class="role-badge <?= isSuperAdmin() ? 'super-admin' : 'admin' ?>">
                    <?= isSuperAdmin() ? '👑 Super Admin' : '🔰 Admin' ?>
                </span>
            </div>

            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="index.php"><i class="fas fa-calendar-day"></i> See Special Dates</a></li>
                <li><a href="add.php"><i class="fas fa-plus-circle"></i> Add Special Date</a></li>
                <?php if (isSuperAdmin()): ?>
                    <li><a href="manage_users.php"><i class="fas fa-users-cog"></i> Manage Users</a></li>
                <?php endif; ?>
                <li><a href="../index.php"><i class="fas fa-calendar"></i> View Calendar</a></li>
            </ul>

            <a href="../logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1>Welcome to Dashboard</h1>
                <p>Manage your special dates and calendar events</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-value"><?= $totalDates ?></div>
                    <div class="stat-label">Total Special Dates</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="stat-value"><?= $currentYearDates ?></div>
                    <div class="stat-label">This Year's Dates</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-tags"></i></div>
                    <div class="stat-value"><?= $totalTypes ?></div>
                    <div class="stat-label">Date Types</div>
                </div>

                <?php if (isSuperAdmin()): ?>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-value"><?= $totalUsers ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
                <div class="action-buttons">
                    <a href="index.php" class="action-btn primary">
                        <i class="fas fa-list"></i>
                        <span>See Special Dates</span>
                    </a>

                    <a href="add.php" class="action-btn success">
                        <i class="fas fa-plus-circle"></i>
                        <span>Add Special Date</span>
                    </a>

                    <?php if (isSuperAdmin()): ?>
                    <a href="manage_users.php" class="action-btn purple">
                        <i class="fas fa-users-cog"></i>
                        <span>Manage Users</span>
                    </a>
                    <?php endif; ?>

                    <a href="../index.php" class="action-btn warning">
                        <i class="fas fa-calendar"></i>
                        <span>View Calendar</span>
                    </a>
                </div>
            </div>

            <footer class="footer">
                &copy; <?php echo date('Y'); ?> Developed and Maintained by Web Publishing Department in collaboration with WNL Time Office<br>
                © All rights reserved, 2008 - Wijeya Newspapers Ltd.
            </footer>
        </main>
    </div>
</body>
</html>

