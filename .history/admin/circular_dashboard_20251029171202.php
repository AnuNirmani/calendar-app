<?php
include '../db.php';

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

// Fetch statistics for the dashboard
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
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                        },
                        success: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            500: '#22c55e',
                            600: '#16a34a',
                        },
                        warning: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            500: '#f59e0b',
                            600: '#d97706',
                        },
                        danger: {
                            50: '#fef2f2',
                            100: '#fee2e2',
                            500: '#ef4444',
                            600: '#dc2626',
                        }
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-slow': 'pulse 3s ease-in-out infinite',
                        'bounce-slow': 'bounce 2s infinite'
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-10px)' }
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .glass-effect {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar {
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 0 40px rgba(0, 0, 0, 0.1);
        }
        .sidebar:hover {
            box-shadow: 0 0 60px rgba(0, 0, 0, 0.2);
        }
        .nav-item {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            border-radius: 12px;
        }
        .nav-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s;
        }
        .nav-item:hover::before {
            left: 100%;
        }
        .nav-item.active {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
        }
        .stat-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
            background: linear-gradient(90deg, var(--gradient-color), transparent);
        }
        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        .quick-action-card {
            background: white;
            transition: all 0.3s ease;
            border-radius: 16px;
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
            background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.05), transparent);
            transition: left 0.6s;
        }
        .quick-action-card:hover::before {
            left: 100%;
        }
        .quick-action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        .pulse-dot {
            animation: pulse-dot 2s infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            overflow: hidden;
            z-index: 0;
        }
        .shape {
            position: absolute;
            opacity: 0.1;
            animation: float 20s infinite linear;
        }
        .shape:nth-child(1) { top: 10%; left: 10%; animation-delay: 0s; }
        .shape:nth-child(2) { top: 60%; left: 80%; animation-delay: -5s; }
        .shape:nth-child(3) { top: 80%; left: 20%; animation-delay: -10s; }
        .shape:nth-child(4) { top: 30%; left: 90%; animation-delay: -15s; }
    </style>
</head>
<body class="bg-gray-50 font-sans overflow-x-hidden">
    <div class="flex min-h-screen">
        <!-- Enhanced Sidebar -->
        <div class="sidebar w-80 p-6 flex flex-col justify-between text-white relative z-10">
            <!-- Floating shapes background -->
            <div class="floating-shapes">
                <div class="shape w-20 h-20 bg-blue-300 rounded-full"></div>
                <div class="shape w-16 h-16 bg-purple-300 rounded-full"></div>
                <div class="shape w-24 h-24 bg-cyan-300 rounded-full"></div>
                <div class="shape w-12 h-12 bg-pink-300 rounded-full"></div>
            </div>
            
            <div class="relative z-10">
                <div class="mb-8 text-center animate__animated animate__fadeInDown">
                    <div class="w-20 h-20 mx-auto mb-4 rounded-2xl glass-effect flex items-center justify-center">
                        <img src="../images/logo.jpg" alt="Logo" class="w-16 h-16 rounded-xl">
                    </div>
                    <h2 class="text-2xl font-bold bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent">
                        Category Manager
                    </h2
                </div>
                
                <nav class="space-y-3">
                    <?php
                    $navItems = [
                        ['icon' => 'fas fa-plus-circle', 'text' => 'Create Category', 'url' => 'create_category.php', 'color' => 'from-blue-500 to-cyan-500'],
                        ['icon' => 'fas fa-list', 'text' => 'List Categories', 'url' => 'list_categories.php', 'color' => 'from-teal-500 to-green-500'],
                        ['icon' => 'fas fa-edit', 'text' => 'Add New Post', 'url' => 'add_post.php', 'color' => 'from-green-500 to-emerald-500'],
                        ['icon' => 'fas fa-newspaper', 'text' => 'List Posts', 'url' => 'list_posts.php', 'color' => 'from-blue-500 to-indigo-500'],
                        ['icon' => 'fas fa-phone', 'text' => 'Add Telephone Directory', 'url' => 'add_telephone_directory.php', 'color' => 'from-indigo-500 to-purple-500'],
                        ['icon' => 'fas fa-address-book', 'text' => 'List Telephone Directory', 'url' => 'list_telephone_directory.php', 'color' => 'from-teal-500 to-cyan-500'],
                        ['icon' => 'fas fa-tachometer-alt', 'text' => 'Admin Dashboard', 'url' => 'dashboard.php', 'color' => 'from-green-500 to-teal-500', 'active' => true]
                    ];
                    
                    foreach ($navItems as $item) {
                        $activeClass = isset($item['active']) ? 'active' : '';
                        echo "
                        <a href='{$item['url']}' class='nav-item {$activeClass} flex items-center p-4 text-white hover:bg-white/10 rounded-xl transition-all duration-300 group'>
                            <div class='w-10 h-10 rounded-lg bg-gradient-to-r {$item['color']} flex items-center justify-center mr-4 group-hover:scale-110 transition-transform duration-300'>
                                <i class='{$item['icon']} text-white text-sm'></i>
                            </div>
                            <span class='font-medium flex-1'>{$item['text']}</span>
                            <i class='fas fa-chevron-right text-blue-200 text-xs opacity-0 group-hover:opacity-100 transition-opacity duration-300'></i>
                        </a>";
                    }
                    ?>
                </nav>
            </div>
            
            <div class="relative z-10">
                <div class="bg-white/10 rounded-2xl p-4 mb-4 backdrop-blur-sm">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-400 to-purple-400 flex items-center justify-center">
                                <i class="fas fa-user text-white text-sm"></i>
                            </div>
                        </div>
                        <div class="pulse-dot w-3 h-3 bg-green-400 rounded-full"></div>
                    </div>
                </div>
                
                <a href="?logout=true" class="logout-btn flex items-center justify-center p-4 bg-gradient-to-r from-red-500 to-pink-500 text-white rounded-xl hover:from-red-600 hover:to-pink-600 transition-all duration-300 group shadow-lg" onclick="return confirm('Are you sure you want to logout?')">
                    <i class="fas fa-sign-out-alt mr-3 group-hover:rotate-180 transition-transform duration-300"></i>
                    <span class="font-medium">Logout</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 min-h-screen gradient-bg relative overflow-hidden">
            <!-- Animated background elements -->
            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute -top-40 -right-32 w-80 h-80 bg-blue-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-float"></div>
                <div class="absolute -bottom-40 -left-32 w-80 h-80 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-float" style="animation-delay: -2s;"></div>
                <div class="absolute top-40 left-1/2 w-80 h-80 bg-cyan-300 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-float" style="animation-delay: -4s;"></div>
            </div>

            <div class="relative z-10 p-6">
                <!-- Header -->
                <div class="mb-8 animate__animated animate__fadeInDown">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-4xl md:text-5xl font-bold text-white mb-2">Dashboard Overview</h1>
                            <p class="text-blue-100 text-lg">Welcome back! Here's what's happening with your content.</p>
                        </div>
                        <div class="hidden md:flex items-center space-x-4">
                            <div class="glass-effect rounded-2xl px-4 py-2">
                                <p class="text-white text-sm">Last login: Today</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <?php
                    $statCards = [
                        ['icon' => 'fas fa-folder', 'number' => $stats['total_categories'], 'label' => 'Total Categories', 'color' => 'from-blue-500 to-cyan-500', 'gradient' => '#0ea5e9, #06b6d4'],
                        ['icon' => 'fas fa-check-circle', 'number' => $stats['active_categories'], 'label' => 'Active Categories', 'color' => 'from-green-500 to-emerald-500', 'gradient' => '#22c55e, #10b981'],
                        ['icon' => 'fas fa-newspaper', 'number' => $stats['total_posts'], 'label' => 'Total Posts', 'color' => 'from-purple-500 to-pink-500', 'gradient' => '#a855f7, #ec4899'],
                        ['icon' => 'fas fa-address-book', 'number' => $stats['total_contacts'], 'label' => 'Contacts', 'color' => 'from-orange-500 to-red-500', 'gradient' => '#f97316, #ef4444']
                    ];
                    
                    foreach ($statCards as $card) {
                        echo "
                        <div class='stat-card rounded-2xl p-6 shadow-xl cursor-pointer transform transition-all duration-300 hover:scale-105' style='--gradient-color: {$card['gradient']}'>
                            <div class='flex items-center justify-between mb-4'>
                                <div class='w-12 h-12 rounded-xl bg-gradient-to-r {$card['color']} flex items-center justify-center shadow-lg'>
                                    <i class='{$card['icon']} text-white text-lg'></i>
                                </div>
                                <div class='text-right'>
                                    <div class='text-2xl font-bold text-gray-800'>{$card['number']}</div>
                                    <div class='text-gray-500 text-sm'>{$card['label']}</div>
                                </div>
                            </div>
                            <div class='w-full bg-gray-200 rounded-full h-2'>
                                <div class='bg-gradient-to-r {$card['color']} h-2 rounded-full' style='width: " . min($card['number'] * 10, 100) . "%'></div>
                            </div>
                        </div>";
                    }
                    ?>
                </div>

                <!-- Quick Actions Grid -->
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-white mb-6 flex items-center">
                        <i class="fas fa-bolt text-yellow-400 mr-3"></i>
                        Quick Actions
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php
                        $quickActions = [
                            ['icon' => 'fas fa-plus-circle', 'title' => 'Create Category', 'description' => 'Add a new content category', 'url' => 'create_category.php', 'color' => 'border-l-4 border-blue-500'],
                            ['icon' => 'fas fa-list', 'title' => 'List Categories', 'description' => 'View and manage categories', 'url' => 'list_categories.php', 'color' => 'border-l-4 border-teal-500'],
                            ['icon' => 'fas fa-edit', 'title' => 'Add New Post', 'description' => 'Create a new blog post', 'url' => 'add_post.php', 'color' => 'border-l-4 border-green-500'],
                            ['icon' => 'fas fa-newspaper', 'title' => 'List Posts', 'description' => 'Browse all posts', 'url' => 'list_posts.php', 'color' => 'border-l-4 border-purple-500'],
                            ['icon' => 'fas fa-phone', 'title' => 'Add Contact', 'description' => 'Add telephone directory', 'url' => 'add_telephone_directory.php', 'color' => 'border-l-4 border-indigo-500'],
                            ['icon' => 'fas fa-address-book', 'title' => 'List Contacts', 'description' => 'View all contacts', 'url' => 'list_telephone_directory.php', 'color' => 'border-l-4 border-cyan-500']
                        ];
                        
                        foreach ($quickActions as $action) {
                            echo "
                            <a href='{$action['url']}' class='quick-action-card p-6 shadow-lg {$action['color']} group'>
                                <div class='flex items-start mb-4'>
                                    <div class='w-12 h-12 rounded-xl bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center shadow-sm group-hover:shadow-md transition-shadow duration-300 mr-4'>
                                        <i class='{$action['icon']} text-gray-600 text-lg'></i>
                                    </div>
                                    <div class='flex-1'>
                                        <h3 class='font-semibold text-gray-800 text-lg mb-1 group-hover:text-gray-900 transition-colors duration-300'>{$action['title']}</h3>
                                        <p class='text-gray-600 text-sm'>{$action['description']}</p>
                                    </div>
                                    <i class='fas fa-arrow-right text-gray-400 group-hover:text-gray-600 transform group-hover:translate-x-1 transition-all duration-300'></i>
                                </div>
                                <div class='w-8 h-1 bg-gradient-to-r from-transparent via-current to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300'></div>
                            </a>";
                        }
                        ?>
                    </div>
                </div>

                <!-- Recent Activity Section -->
                <div class="glass-effect rounded-2xl p-6 shadow-xl">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-white flex items-center">
                            <i class="fas fa-chart-line text-green-400 mr-3"></i>
                            System Overview
                        </h2>
                        <span class="text-blue-200 text-sm">Real-time</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center p-4">
                            <div class="w-16 h-16 mx-auto mb-3 rounded-full bg-green-100 flex items-center justify-center">
                                <i class="fas fa-server text-green-600 text-xl"></i>
                            </div>
                            <h3 class="text-white font-semibold mb-1">System Status</h3>
                            <p class="text-green-300 text-sm">All Systems Operational</p>
                        </div>
                        <div class="text-center p-4">
                            <div class="w-16 h-16 mx-auto mb-3 rounded-full bg-blue-100 flex items-center justify-center">
                                <i class="fas fa-database text-blue-600 text-xl"></i>
                            </div>
                            <h3 class="text-white font-semibold mb-1">Database</h3>
                            <p class="text-blue-300 text-sm">Connected & Healthy</p>
                        </div>
                        <div class="text-center p-4">
                            <div class="w-16 h-16 mx-auto mb-3 rounded-full bg-purple-100 flex items-center justify-center">
                                <i class="fas fa-shield-alt text-purple-600 text-xl"></i>
                            </div>
                            <h3 class="text-white font-semibold mb-1">Security</h3>
                            <p class="text-purple-300 text-sm">Protected</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add interactive animations
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // Add loading animation
            const elements = document.querySelectorAll('.animate__animated');
            elements.forEach((element, index) => {
                element.style.animationDelay = `${index * 0.1}s`;
            });

            // Pulse animation for active elements
            setInterval(() => {
                const pulseDot = document.querySelector('.pulse-dot');
                pulseDot.style.animation = 'none';
                setTimeout(() => {
                    pulseDot.style.animation = 'pulse-dot 2s infinite';
                }, 10);
            }, 4000);
        });

        // Add parallax effect to background shapes
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const shapes = document.querySelectorAll('.shape');
            shapes.forEach((shape, index) => {
                const speed = 0.5 + (index * 0.1);
                shape.style.transform = `translateY(${scrolled * speed}px)`;
            });
        });
    </script>
</body>
</html>