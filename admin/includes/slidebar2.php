<?php
        // Get current page filename
        $currentPage = basename($_SERVER['PHP_SELF']);
        
        // Function to check if menu item is active
        function isActive($page) {
            global $currentPage;
            return $currentPage === $page ? 'bg-blue-800 bg-opacity-75 border-l-4 border-white font-semibold' : 'hover:bg-blue-800 hover:bg-opacity-50 hover:border-l-4 hover:border-white';
        }
        ?>
        <!-- Sidebar -->
        <aside id="sidebar" class="fixed lg:sticky top-0 left-0 h-screen w-72 bg-blue-900 text-white shadow-xl overflow-y-auto z-40 transform -translate-x-full lg:translate-x-0 transition-transform duration-300">
            <!-- Close button for mobile -->
            <button id="close-sidebar" class="lg:hidden absolute top-4 right-4 text-white text-2xl">
                <i class="fas fa-times"></i>
            </button>

            <div class="p-6 border-b border-blue-800">
                <h2 class="text-xl font-semibold text-center">
                    <i class="fas fa-calendar-alt"></i> Manage WNL Attendance Calendar
                </h2>
            </div>

            <div class="m-5 bg-blue-800 bg-opacity-50 p-4 rounded-xl text-center">
                <div class="text-lg font-semibold mb-1">👤 <?= htmlspecialchars($_SESSION['username']) ?></div>
                <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold mt-2 <?= isSuperAdmin() ? 'bg-gradient-to-r from-pink-500 to-red-500' : 'bg-gradient-to-r from-cyan-500 to-blue-500' ?>">
                    <?= isSuperAdmin() ? '👑 Super Admin' : '🔰 Admin' ?>
                </span>
            </div>

            <ul class="space-y-1 py-4">
                <li>
                    <a href="dashboard.php" class="flex items-center px-6 py-3 text-white <?= isActive('dashboard.php') ?> transition-all">
                        <i class="fas fa-home w-6 text-center mr-3"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="index.php" class="flex items-center px-6 py-3 text-white <?= isActive('index.php') ?> transition-all">
                        <i class="fas fa-calendar-day w-6 text-center mr-3"></i> See Special Dates
                    </a>
                </li>
                <li>
                    <a href="add.php" class="flex items-center px-6 py-3 text-white <?= isActive('add.php') ?> transition-all">
                        <i class="fas fa-plus-circle w-6 text-center mr-3"></i> Add Special Dates
                    </a>
                </li>
                <?php if (isSuperAdmin()): ?>
                    <li>
                        <a href="manage_users.php" class="flex items-center px-6 py-3 text-white <?= isActive('manage_users.php') ?> transition-all">
                            <i class="fas fa-users-cog w-6 text-center mr-3"></i> Manage Users
                        </a>
                    </li>
                    <li>
                        <a href="add_user.php" class="flex items-center px-6 py-3 text-white <?= isActive('add_user.php') ?> transition-all">
                            <i class="fas fa-user-plus w-6 text-center mr-3"></i> Add User
                        </a>
                    </li>

                    <li>
                        <a href="posts/create_category.php" class="flex items-center px-6 py-3 text-white <?= isActive('create_category.php') ?> transition-all">
                            <i class="fas fa-folder-plus w-6 text-center mr-3"></i> Create Category
                        </a>
                    </li>
                    <li>
                        <a href="posts/list_categories.php" class="flex items-center px-6 py-3 text-white <?= isActive('list_categories.php') ?> transition-all">
                            <i class="fas fa-list w-6 text-center mr-3"></i> List Categories
                        </a>
                    </li>
                    <li>
                        <a href="posts/add_post.php" class="flex items-center px-6 py-3 text-white <?= isActive('add_post.php') ?> transition-all">
                            <i class="fas fa-folder-plus w-6 text-center mr-3"></i> Add New Post
                        </a>
                    </li>
                    <li>
                        <a href="posts/list_posts.php" class="flex items-center px-6 py-3 text-white <?= isActive('list_posts.php') ?> transition-all">
                            <i class="fas fa-list w-6 text-center mr-3"></i> List Posts
                        </a>
                    </li>
                    <li>
                        <a href="posts/add_telephone_directory.php" class="flex items-center px-6 py-3 text-white <?= isActive('add_telephone_directory.php') ?> transition-all">
                            <i class="fas fa-folder-plus w-6 text-center mr-3"></i> Add Telephone Directory
                        </a>
                    </li>
                    <li>
                        <a href="posts/list_telephone_directory.php" class="flex items-center px-6 py-3 text-white <?= isActive('list_telephone_directory.php') ?> transition-all">
                            <i class="fas fa-list w-6 text-center mr-3"></i> List Telephone Directory
                        </a>
                    </li>

                <?php endif; ?>
                <li>
                    <a href="../index.php" class="flex items-center px-6 py-3 text-white hover:bg-blue-800 hover:bg-opacity-50 hover:border-l-4 hover:border-white transition-all">
                        <i class="fas fa-calendar w-6 text-center mr-3"></i> View Calendar
                    </a>
                </li>
            </ul>

            <a href="../logout.php" class="block mx-5 my-8 bg-red-600 hover:bg-red-700 text-white text-center py-3 px-6 rounded-full font-semibold border-2 border-red-600 hover:border-red-700 transition-all transform hover:-translate-y-1 hover:shadow-lg">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </aside>

        <!-- Main Content -->
        <main class="flex-1">
            <!-- Content Area -->
            <div class="p-6 lg:p-10 pt-20 lg:pt-6">