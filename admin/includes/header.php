<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../images/logo.jpg" type="image/png">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Mobile Header with Menu Button -->
    <div id="mobile-header" class="lg:hidden fixed top-0 left-0 right-0 bg-white shadow-md border-b border-gray-200 z-50 transition-transform duration-300">
        <div class="flex items-center justify-between px-4 py-3">
            <button id="mobile-menu-btn" class="bg-blue-900 text-white p-2 rounded-lg shadow-lg">
                <i class="fas fa-bars text-lg"></i>
            </button>
            <div class="flex items-center gap-3">
                <img src="../images/logo.png" alt="WNL Logo" class="w-10 h-10 object-contain rounded-lg shadow-md">
                <div class="text-center">
                    <h1 class="text-lg font-semibold text-blue-900">Welcome to WNL</h1>
                    <p class="text-xs text-gray-600">Manage calendar events</p>
                </div>
            </div>
            <div class="w-10"></div> <!-- Spacer for centering -->
        </div>
    </div>

    <!-- Overlay for mobile -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden lg:hidden"></div>

    <div class="flex min-h-screen">
