<?php
include '../db.php';
require 'add_category_type_functions.php';

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

// Fetch dashboard statistics
$stats = [];
$stats['total_categories'] = 0;
$stats['active_categories'] = 0;
$stats['total_posts'] = 0;
$stats['total_contacts'] = 0;

// Get category statistics
$category_query = "SELECT status, COUNT(*) as count FROM categories GROUP BY status";
$category_result = mysqli_query($conn, $category_query);
if ($category_result) {
    while ($row = mysqli_fetch_assoc($category_result)) {
        $stats['total_categories'] += $row['count'];
        if ($row['status'] == 'active') {
            $stats['active_categories'] = $row['count'];
        }
    }
}

// Get post statistics
$post_query = "SELECT COUNT(*) as count FROM posts";
$post_result = mysqli_query($conn, $post_query);
if ($post_result) {
    $row = mysqli_fetch_assoc($post_result);
    $stats['total_posts'] = $row['count'];
}

// Get telephone directory statistics
$contact_query = "SELECT COUNT(*) as count FROM telephone_directory";
$contact_result = mysqli_query($conn, $contact_query);
if ($contact_result) {
    $row = mysqli_fetch_assoc($contact_result);
    $stats['total_contacts'] = $row['count'];
}

// Handle category creation
$category_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category_name'])) {
    $result = createCategory($_POST['category_name']);
    if ($result === true) {
        $category_message = "<p class='text-green-600 mt-2'>Category created successfully!</p>";
    } else {
        $category_message = "<p class='text-red-600 mt-2'>$result</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management Dashboard</title>
    <link rel="icon" href="../images/logo.jpg" type="image/png">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
        .sidebar {
            transition: all 0.3s ease;
            transform: translateX(0);
        }
        .sidebar.hidden {
            transform: translateX(-100%);
        }
        .sidebar:hover {
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        .btn-nav {
            transition: all 0.2s ease;
            padding: 0.75rem 1rem;
        }
        .btn-nav:hover {
            transform: translateX(5px);
        }
        .main-content {
            min-height: calc(100vh - 64px);
            transition: margin-left 0.3s ease;
        }
        .logout-btn svg {
            transition: transform 0.2s ease;
        }
        .logout-btn:hover svg {
            transform: translateX(4px);
        }
        .gradient-bg {
            background: linear-gradient(135deg, #e0e7ff 0%, #f9fafb 100%);
        }
        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .quick-action-card {
            background: white;
            transition: all 0.3s ease;
            border-radius: 12px;
            position: relative;
            overflow: hidden;
        }
        .quick-action-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(99, 102, 241, 0.05), transparent);
            transition: left 0.6s;
        }
        .quick-action-card:hover::before {
            left: 100%;
        }
        .quick-action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .pulse-dot {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .floating {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .glow {
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.1);
        }
        .gradient-text {
            background: linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .feature-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 1.25rem;
            transition: all 0.3s ease;
        }
        .quick-action-card:hover .feature-icon {
            transform: scale(1.1);
        }
        .dashboard-header {
            background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(248,250,252,0.9) 100%);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.8);
        }
        .progress-bar {
            transition: width 1.5s ease-in-out;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <div class="flex h-screen">
        <!-- Sidebar (Unchanged) -->
        <div class="sidebar w-64 bg-white shadow-lg p-6 flex flex-col justify-between">
            <div>
                <div class="mb-8">
                    <img src="../images/logo.jpg" alt="Logo" class="w-16 mx-auto rounded-lg shadow-md">
                    <h2 class="text-xl font-bold text-center text-gray-800 mt-2">Category Management</h2>
                </div>
                <nav class="space-y-4">
                    <a href="create_category.php" class="btn-nav block w-full text-left py-3 px-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 shadow-md">
                        Create Category
                    </a>
                    <a href="list_categories.php" class="btn-nav block w-full text-left py-3 px-4 bg-teal-600 text-white rounded-lg hover:bg-teal-700 shadow-md">
                        List Categories
                    </a>
                    <a href="add_post.php" class="btn-nav block w-full text-left py-3 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700 shadow-md">
                        Add New Post
                    </a>
                    <a href="list_posts.php" class="btn-nav block w-full text-left py-3 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700 shadow-md">
                        List Posts
                    </a>
                    <a href="add_telephone_directory.php" class="btn-nav block w-full text-left py-3 px-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 shadow-md">
                        Add Telephone Directory
                    </a>
                    <a href="list_telephone_directory.php" class="btn-nav block w-full text-left py-3 px-4 bg-teal-600 text-white rounded-lg hover:bg-teal-700 shadow-md">
                        List Telephone Directory
                    </a>
                    <a href="dashboard.php" class="btn-nav block w-full text-left py-3 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700 shadow-md">
                        Admin Dashboard
                    </a>
                </nav>
            </div>
            <div class="mt-auto">
                <a href="?logout=true" class="logout-btn flex items-center justify-center py-3 px-4 bg-red-600 text-white rounded-lg hover:bg-red-700 shadow-md" onclick="return confirm('Are you sure you want to logout?')">
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
        <div class="main-content flex-1 p-4 md:p-8 md:ml-[250px] gradient-bg overflow-y-auto">
            <!-- Mobile Hamburger -->
            <div class="hamburger md:hidden p-4">
                <button id="hamburgerBtn" class="text-gray-800 focus:outline-none bg-white p-2 rounded-lg shadow-md">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>

            <div class="max-w-full md:max-w-7xl mx-auto">
                <!-- Header Section -->
                <div class="dashboard-header rounded-2xl p-6 mb-8 shadow-lg">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <div class="mb-4 md:mb-0">
                            <h1 class="text-3xl md:text-4xl font-extrabold gradient-text mb-2">Dashboard Overview</h1>
                            <p class="text-gray-600 text-lg">Welcome back! Manage your content efficiently with our tools.</p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="bg-white rounded-lg px-4 py-2 shadow-md">
                                <p class="text-gray-600 text-sm"><i class="fas fa-calendar-day text-indigo-500 mr-2"></i><?php echo date('F j, Y'); ?></p>
                            </div>
                            <div class="bg-white rounded-lg px-4 py-2 shadow-md">
                                <p class="text-gray-600 text-sm"><i class="fas fa-clock text-green-500 mr-2"></i><?php echo date('h:i A'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Categories Card -->
                    <div class="stat-card rounded-xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="feature-icon bg-indigo-100 text-indigo-600">
                                <i class="fas fa-folder"></i>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-gray-800 stat-number" data-target="<?php echo $stats['total_categories']; ?>">0</div>
                                <div class="text-gray-500 text-sm">Total Categories</div>
                            </div>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-indigo-500 h-2 rounded-full progress-bar" style="width: 0%" data-target="<?php echo min(($stats['total_categories'] / max($stats['total_categories'], 1)) * 100, 100); ?>"></div>
                        </div>
                    </div>

                    <!-- Active Categories Card -->
                    <div class="stat-card rounded-xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="feature-icon bg-green-100 text-green-600">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-gray-800 stat-number" data-target="<?php echo $stats['active_categories']; ?>">0</div>
                                <div class="text-gray-500 text-sm">Active Categories</div>
                            </div>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full progress-bar" style="width: 0%" data-target="<?php echo min(($stats['active_categories'] / max($stats['total_categories'], 1)) * 100, 100); ?>"></div>
                        </div>
                    </div>

                    <!-- Total Posts Card -->
                    <div class="stat-card rounded-xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="feature-icon bg-blue-100 text-blue-600">
                                <i class="fas fa-newspaper"></i>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-gray-800 stat-number" data-target="<?php echo $stats['total_posts']; ?>">0</div>
                                <div class="text-gray-500 text-sm">Total Posts</div>
                            </div>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full progress-bar" style="width: 0%" data-target="<?php echo min(($stats['total_posts'] / max($stats['total_posts'] + 1, 1)) * 100, 100); ?>"></div>
                        </div>
                    </div>

                    <!-- Total Contacts Card -->
                    <div class="stat-card rounded-xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="feature-icon bg-teal-100 text-teal-600">
                                <i class="fas fa-address-book"></i>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-gray-800 stat-number" data-target="<?php echo $stats['total_contacts']; ?>">0</div>
                                <div class="text-gray-500 text-sm">Total Contacts</div>
                            </div>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-teal-500 h-2 rounded-full progress-bar" style="width: 0%" data-target="<?php echo min(($stats['total_contacts'] / max($stats['total_contacts'] + 1, 1)) * 100, 100); ?>"></div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white p-8 rounded-2xl shadow-xl mb-8">
                    <div class="flex items-center justify-between mb-8">
                        <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                            <i class="fas fa-bolt text-yellow-500 mr-3 floating"></i>
                            Quick Actions
                        </h2>
                        <span class="text-gray-500 text-sm">Get things done faster</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <a href="create_category.php" class="quick-action-card p-6 border-l-4 border-indigo-500 group">
                            <div class="flex items-start mb-4">
                                <div class="feature-icon bg-indigo-100 text-indigo-600 mr-4">
                                    <i class="fas fa-plus-circle"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-800 text-lg mb-2 group-hover:text-indigo-600 transition-colors">Create Category</h3>
                                    <p class="text-gray-600 text-sm">Add a new category to organize your content efficiently.</p>
                                </div>
                                <i class="fas fa-arrow-right text-gray-400 group-hover:text-indigo-500 transform group-hover:translate-x-1 transition-all"></i>
                            </div>
                        </a>

                        <a href="list_categories.php" class="quick-action-card p-6 border-l-4 border-teal-500 group">
                            <div class="flex items-start mb-4">
                                <div class="feature-icon bg-teal-100 text-teal-600 mr-4">
                                    <i class="fas fa-list"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-800 text-lg mb-2 group-hover:text-teal-600 transition-colors">List Categories</h3>
                                    <p class="text-gray-600 text-sm">View and manage all existing categories with ease.</p>
                                </div>
                                <i class="fas fa-arrow-right text-gray-400 group-hover:text-teal-500 transform group-hover:translate-x-1 transition-all"></i>
                            </div>
                        </a>

                        <a href="add_post.php" class="quick-action-card p-6 border-l-4 border-green-500 group">
                            <div class="flex items-start mb-4">
                                <div class="feature-icon bg-green-100 text-green-600 mr-4">
                                    <i class="fas fa-edit"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-800 text-lg mb-2 group-hover:text-green-600 transition-colors">Add New Post</h3>
                                    <p class="text-gray-600 text-sm">Create engaging content and share it with your audience.</p>
                                </div>
                                <i class="fas fa-arrow-right text-gray-400 group-hover:text-green-500 transform group-hover:translate-x-1 transition-all"></i>
                            </div>
                        </a>

                        <a href="list_posts.php" class="quick-action-card p-6 border-l-4 border-blue-500 group">
                            <div class="flex items-start mb-4">
                                <div class="feature-icon bg-blue-100 text-blue-600 mr-4">
                                    <i class="fas fa-newspaper"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-800 text-lg mb-2 group-hover:text-blue-600 transition-colors">List Posts</h3>
                                    <p class="text-gray-600 text-sm">Browse and manage all your published content.</p>
                                </div>
                                <i class="fas fa-arrow-right text-gray-400 group-hover:text-blue-500 transform group-hover:translate-x-1 transition-all"></i>
                            </div>
                        </a>

                        <a href="add_telephone_directory.php" class="quick-action-card p-6 border-l-4 border-indigo-500 group">
                            <div class="flex items-start mb-4">
                                <div class="feature-icon bg-indigo-100 text-indigo-600 mr-4">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-800 text-lg mb-2 group-hover:text-indigo-600 transition-colors">Add Telephone Directory</h3>
                                    <p class="text-gray-600 text-sm">Add new contacts to the company directory.</p>
                                </div>
                                <i class="fas fa-arrow-right text-gray-400 group-hover:text-indigo-500 transform group-hover:translate-x-1 transition-all"></i>
                            </div>
                        </a>

                        <a href="list_telephone_directory.php" class="quick-action-card p-6 border-l-4 border-teal-500 group">
                            <div class="flex items-start mb-4">
                                <div class="feature-icon bg-teal-100 text-teal-600 mr-4">
                                    <i class="fas fa-address-book"></i>
                                </div>
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-800 text-lg mb-2 group-hover:text-teal-600 transition-colors">List Telephone Directory</h3>
                                    <p class="text-gray-600 text-sm">View and manage all contact information.</p>
                                </div>
                                <i class="fas fa-arrow-right text-gray-400 group-hover:text-teal-500 transform group-hover:translate-x-1 transition-all"></i>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- System Status & Tips -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- System Status -->
                    <div class="bg-white rounded-2xl p-6 shadow-xl">
                        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-chart-line text-green-500 mr-3"></i>
                            System Status
                        </h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-green-500 rounded-full pulse-dot mr-3"></div>
                                    <span class="text-gray-700">Database Connection</span>
                                </div>
                                <span class="text-green-600 font-medium">Active</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-blue-500 rounded-full pulse-dot mr-3"></div>
                                    <span class="text-gray-700">Application Server</span>
                                </div>
                                <span class="text-blue-600 font-medium">Running</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-indigo-50 rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-indigo-500 rounded-full pulse-dot mr-3"></div>
                                    <span class="text-gray-700">User Session</span>
                                </div>
                                <span class="text-indigo-600 font-medium">Valid</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-teal-50 rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 bg-teal-500 rounded-full pulse-dot mr-3"></div>
                                    <span class="text-gray-700">Security</span>
                                </div>
                                <span class="text-teal-600 font-medium">Protected</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Tips -->
                    <div class="bg-white rounded-2xl p-6 shadow-xl">
                        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-lightbulb text-yellow-500 mr-3"></i>
                            Quick Tips
                        </h3>
                        <div class="space-y-3">
                            <div class="flex items-start p-3 bg-yellow-50 rounded-lg">
                                <i class="fas fa-info-circle text-yellow-500 mt-1 mr-3"></i>
                                <p class="text-gray-700 text-sm">Use categories to organize your content for better management and navigation.</p>
                            </div>
                            <div class="flex items-start p-3 bg-blue-50 rounded-lg">
                                <i class="fas fa-clock text-blue-500 mt-1 mr-3"></i>
                                <p class="text-gray-700 text-sm">Regularly update your telephone directory to keep contact information current.</p>
                            </div>
                            <div class="flex items-start p-3 bg-green-50 rounded-lg">
                                <i class="fas fa-shield-alt text-green-500 mt-1 mr-3"></i>
                                <p class="text-gray-700 text-sm">Always logout when you're done to keep your account secure and protected.</p>
                            </div>
                            <div class="flex items-start p-3 bg-indigo-50 rounded-lg">
                                <i class="fas fa-sync-alt text-indigo-500 mt-1 mr-3"></i>
                                <p class="text-gray-700 text-sm">Keep your content fresh by regularly updating and maintaining your posts.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Preview -->
                <div class="bg-white rounded-2xl p-6 shadow-xl mt-8">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-history text-purple-500 mr-3"></i>
                        Recent Activity
                    </h3>
                    <div class="text-center py-8">
                        <i class="fas fa-chart-bar text-gray-300 text-4xl mb-3"></i>
                        <p class="text-gray-500">Activity tracking will appear here as you use the system.</p>
                        <p class="text-gray-400 text-sm mt-2">Create categories, add posts, or update directories to see activity.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Hamburger menu toggle
            $('#hamburgerBtn').click(function() {
                $('.sidebar').toggleClass('hidden');
            });

            // Add hover effects to stat cards
            $('.stat-card').hover(
                function() {
                    $(this).addClass('glow');
                },
                function() {
                    $(this).removeClass('glow');
                }
            );

            // Animate statistics numbers
            function animateNumbers() {
                $('.stat-number').each(function() {
                    const $this = $(this);
                    const target = parseInt($this.data('target'));
                    let count = 0;
                    const duration = 1500;
                    const steps = 60;
                    const increment = target / steps;
                    const stepTime = duration / steps;
                    
                    const timer = setInterval(() => {
                        if (count >= target) {
                            $this.text(target);
                            clearInterval(timer);
                        } else {
                            count += increment;
                            $this.text(Math.ceil(count));
                        }
                    }, stepTime);
                });
            }

            // Animate progress bars
            function animateProgressBars() {
                $('.progress-bar').each(function() {
                    const $this = $(this);
                    const targetWidth = $this.data('target');
                    $this.css('width', targetWidth + '%');
                });
            }

            // Initialize animations when page loads
            setTimeout(() => {