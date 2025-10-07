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
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../images/logo.jpg" type="image/png">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-calendar-alt"></i>  Manage WNL Attendance Calendar</h2>
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
                <li><a href="add.php"><i class="fas fa-plus-circle"></i> Add Special Dates</a></li>
                <?php if (isSuperAdmin()): ?>
                    <li><a href="manage_users.php"><i class="fas fa-users-cog"></i> Manage Users</a></li>
                <?php endif; ?>
                <li><a href="../index.php"><i class="fas fa-calendar"></i> View Calendar</a></li>
            </ul>

            <a href="../logout.php" style="background: #f44336; color: white; padding: 12px 20px; border-radius: 20px; font-size: 16px; font-weight: 600; text-decoration: none; display: block; margin: 30px auto 20px; width: calc(100% - 40px); max-width: 240px; text-align: center; border: 2px solid #f44336; transition: all 0.3s ease;">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1>Welcome to WNL</h1>
                <p>Manage special dates and calendar events</p>
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
                    <div class="stat-label">This Year's Special Dates</div>
                </div>

                <!-- <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-tags"></i></div>
                    <div class="stat-value"><?= $totalTypes ?></div>
                    <div class="stat-label">Date Types</div>
                </div> -->

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

