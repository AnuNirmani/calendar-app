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

// Include header
include 'includes/header.php';

// Include sidebar
include 'includes/sidebar.php';
?>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6 mb-8 lg:mb-10">
                <div class="bg-white p-6 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border-l-4 border-blue-900">
                    <div class="text-4xl text-blue-900 mb-3">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="text-3xl font-bold text-blue-900 mb-1"><?= $totalDates ?></div>
                    <div class="text-gray-600 text-sm uppercase tracking-wide">Total Special Dates</div>
                </div>

                <div class="bg-white p-6 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border-l-4 border-blue-900">
                    <div class="text-4xl text-blue-900 mb-3">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="text-3xl font-bold text-blue-900 mb-1"><?= $currentYearDates ?></div>
                    <div class="text-gray-600 text-sm uppercase tracking-wide">This Year's Special Dates</div>
                </div>

                <?php if (isSuperAdmin()): ?>
                <div class="bg-white p-6 rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border-l-4 border-blue-900">
                    <div class="text-4xl text-blue-900 mb-3">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="text-3xl font-bold text-blue-900 mb-1"><?= $totalUsers ?></div>
                    <div class="text-gray-600 text-sm uppercase tracking-wide">Total Users</div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white p-6 lg:p-8 rounded-2xl shadow-md">
                <h2 class="text-xl lg:text-2xl font-semibold text-blue-900 mb-6">
                    <i class="fas fa-bolt"></i> Quick Actions
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-5">
                    <a href="index.php" class="flex flex-col items-center justify-center p-6 lg:p-8 bg-gradient-to-br from-blue-500 to-blue-700 text-white rounded-2xl hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 min-h-[140px] text-center">
                        <i class="fas fa-list text-4xl lg:text-5xl mb-3"></i>
                        <span class="text-sm lg:text-base font-semibold uppercase tracking-wide">See Special Dates</span>
                    </a>

                    <a href="add.php" class="flex flex-col items-center justify-center p-6 lg:p-8 bg-gradient-to-br from-green-500 to-green-700 text-white rounded-2xl hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 min-h-[140px] text-center">
                        <i class="fas fa-plus-circle text-4xl lg:text-5xl mb-3"></i>
                        <span class="text-sm lg:text-base font-semibold uppercase tracking-wide">Add Special Date</span>
                    </a>

                    <?php if (isSuperAdmin()): ?>

                    <a href="manage_users.php" class="flex flex-col items-center justify-center p-6 lg:p-8 bg-gradient-to-br from-purple-500 to-purple-700 text-white rounded-2xl hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 min-h-[140px] text-center">
                        <i class="fas fa-users-cog text-4xl lg:text-5xl mb-3"></i>
                        <span class="text-sm lg:text-base font-semibold uppercase tracking-wide">Manage Users</span>
                    </a>

                    <a href="add_user.php" class="flex flex-col items-center justify-center p-6 lg:p-8 bg-gradient-to-br from-cyan-500 to-cyan-700 text-white rounded-2xl hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 min-h-[140px] text-center">
                        <i class="fas fa-user-plus text-4xl lg:text-5xl mb-3"></i>
                        <span class="text-sm lg:text-base font-semibold uppercase tracking-wide">Add User</span>
                    </a>

                    <?php endif; ?>

                    <a href="../index.php" 
                    target="_blank" rel="noopener noreferrer" 
                    class="flex flex-col items-center justify-center p-6 lg:p-8 bg-gradient-to-br from-orange-500 to-orange-700 text-white rounded-2xl hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 min-h-[140px] text-center">
                        <i class="fas fa-calendar text-4xl lg:text-5xl mb-3"></i>
                        <span class="text-sm lg:text-base font-semibold uppercase tracking-wide">View Calendar</span>
                    </a>
                </div>
            </div>

<?php
// Include footer
include 'includes/footer.php';
?>