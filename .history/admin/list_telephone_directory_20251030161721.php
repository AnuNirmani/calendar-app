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

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM Telephone_Directory WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Entry deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting entry: " . $conn->error;
    }
    $stmt->close();
    header("Location: list_telephone_directory.php");
    exit();
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

// Pagination configuration
$records_per_page = 10;

// Get current page
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

// Calculate offset
$offset = ($current_page - 1) * $records_per_page;

// Initialize search query
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build base query for counting total records
$count_sql = "SELECT COUNT(DISTINCT td.id) as total 
              FROM Telephone_Directory td 
              LEFT JOIN Department d ON td.department_id = d.id";

// Build base query for fetching data
$sql = "SELECT DISTINCT td.id, td.name, td.phone_number, td.email, td.extension, d.name AS department_name 
        FROM Telephone_Directory td 
        LEFT JOIN Department d ON td.department_id = d.id";

// Add search conditions if provided
if (!empty($search)) {
    $search_clean = $conn->real_escape_string($search);
    $where_condition = " WHERE td.name LIKE '%$search_clean%' 
                         OR td.phone_number LIKE '%$search_clean%' 
                         OR td.email LIKE '%$search_clean%' 
                         OR td.extension LIKE '%$search_clean%' 
                         OR d.name LIKE '%$search_clean%'";
    
    $count_sql .= $where_condition;
    $sql .= $where_condition;
}

// Get total records
$count_result = $conn->query($count_sql);
if (!$count_result) {
    die("Count query failed: " . $conn->error);
}
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Ensure current page is within valid range
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
}

// Add pagination to main query
$sql .= " ORDER BY td.id LIMIT $offset, $records_per_page";

// Execute main query
$result = $conn->query($sql);
if (!$result) {
    die("Query failed: " . $conn->error);
}

$entries = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $entries[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telephone Directory</title>
    <link rel="icon" href="../images/logo.jpg" type="image/png">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
        .sidebar {
            transition: all 0.3s ease;
        }
        .sidebar:hover {
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        .btn-nav {
            transition: all 0.2s ease;
        }
        .btn-nav:hover {
            transform: translateX(5px);
        }
        .main-content {
            min-height: calc(100vh - 64px);
        }
        .logout-btn svg {
            transition: transform 0.2s ease;
        }
        .logout-btn:hover svg {
            transform: translateX(4px);
        }
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
            gap: 8px;
        }
        .pagination a, .pagination span {
            padding: 6px 12px;
            border: 1px solid #ddd;
            text-decoration: none;
            color: #333;
            border-radius: 4px;
            transition: all 0.3s;
            font-size: 0.875rem;
        }
        .pagination a:hover {
            background-color: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        .pagination .current {
            background-color: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        .pagination .disabled {
            color: #9ca3af;
            pointer-events: none;
            background-color: #f3f4f6;
            border-color: #d1d5db;
        }
        .email-cell {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="sidebar w-64 bg-white shadow-lg p-6 flex flex-col justify-between">
            <div>
                <div class="mb-8">
                    <img src="../images/logo.jpg" alt="Logo" class="w-16 mx-auto">
                    <h2 class="text-xl font-bold text-center text-gray-800 mt-2">Category Management</h2>
                </div>
                <nav class="space-y-4">
                    <a href="create_category.php" class="btn-nav block w-full text-left py-3 px-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        Create Category
                    </a>
                    <a href="list_categories.php" class="btn-nav block w-full text-left py-3 px-4 bg-teal-600 text-white rounded-lg hover:bg-teal-700">
                        List Categories
                    </a>
                    <a href="add_post.php" class="btn-nav block w-full text-left py-3 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        Add New Post
                    </a>
                    <a href="list_posts.php" class="btn-nav block w-full text-left py-3 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        List Posts
                    </a>
                    <a href="add_telephone_directory.php" class="btn-nav block w-full text-left py-3 px-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        Add Telephone Directory
                    </a>
                    <a href="list_telephone_directory.php" class="btn-nav block w-full text-left py-3 px-4 bg-teal-600 text-white rounded-lg hover:bg-teal-700">
                        List Telephone Directory
                    </a>
                    <a href="dashboard.php" class="btn-nav block w-full text-left py-3 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        Admin Dashboard
                    </a>
                </nav>
            </div>
            <div class="mt-auto">
                <a href="?logout=true" class="logout-btn flex items-center justify-center py-3 px-4 bg-red-600 text-white rounded-lg hover:bg-red-700" onclick="return confirm('Are you sure you want to logout?')">
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
        <div class="main-content flex-1 p-8">
            <div class="max-w-7xl mx-auto">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">List of Telephone Directories</h1>

                <?php if ($successMessage): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded"><?php echo htmlspecialchars($successMessage); ?></div>
                <?php endif; ?>

                <?php if ($errorMessage): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded"><?php echo $errorMessage; ?></div>
                <?php endif; ?>

                <!-- Search and Add Button -->
                <div class="flex justify-between mb-3">
                    <form method="GET" action="" class="flex w-full max-w-md">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, phone, email, extension, or department" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <button type="submit" class="ml-2 py-2 px-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Search</button>
                        <?php if (!empty($search)): ?>
                            <a href="list_telephone_directory.php" class="ml-2 py-2 px-4 bg-gray-600 text-white rounded-lg hover:bg-gray-700">Clear</a>
                        <?php endif; ?>
                    </form>
                    <a href="add_telephone_directory.php" class="py-2 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700 h-fit">
                        Add New Entry
                    </a>
                </div>

                <!-- Results Summary -->
                <div class="mb-4 text-sm text-gray-600 bg-white p-3 rounded-lg shadow">
                    <?php if ($total_records > 0): ?>
                        Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> entries
                        <?php if (!empty($search)): ?>
                            for "<strong><?php echo htmlspecialchars($search); ?></strong>"
                        <?php endif; ?>
                    <?php else: ?>
                        No entries found
                        <?php if (!empty($search)): ?>
                            for "<strong><?php echo htmlspecialchars($search); ?></strong>"
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">All Telephone Directory Entries</h2>
                    <?php if (empty($entries)): ?>
                        <p class="text-gray-600 flex items-center justify-center py-8">
                            <span class="text-2xl mr-2">📞</span> No entries found in the telephone directory.
                        </p>
                        <?php if (!empty($search)): ?>
                            <div class="text-center">
                                <a href="list_telephone_directory.php" class="text-blue-600 hover:text-blue-800 underline">View all entries</a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left table-auto">
                                <thead>
                                    <tr class="bg-gray-200">
                                        <th class="p-3 font-semibold">ID</th>
                                        <th class="p-3 font-semibold">Name</th>
                                        <th class="p-3 font-semibold">Phone Number</th>
                                        <th class="p-3 font-semibold">Email</th>
                                        <th class="p-3 font-semibold">Extension</th>
                                        <th class="p-3 font-semibold">Department</th>
                                        <th class="p-3 font-semibold">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($entries as $entry): ?>
                                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                                            <td class="p-3"><?php echo $entry['id']; ?></td>
                                            <td class="p-3 font-medium"><?php echo htmlspecialchars($entry['name']); ?></td>
                                            <td class="p-3"><?php echo htmlspecialchars($entry['phone_number']); ?></td>
                                            <td class="p-3 email-cell">
                                                <?php if (!empty($entry['email'])): ?>
                                                    <a href="mailto:<?php echo htmlspecialchars($entry['email']); ?>" class="text-blue-600 hover:text-blue-800">
                                                        <?php echo htmlspecialchars($entry['email']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-gray-400">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-3">
                                                <?php if (!empty($entry['extension'])): ?>
                                                    <?php echo htmlspecialchars($entry['extension']); ?>
                                                <?php else: ?>
                                                    <span class="text-gray-400">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-3"><?php echo htmlspecialchars($entry['department_name'] ?: 'N/A'); ?></td>
                                            <td class="p-3">
                                                <div class="flex space-x-2">
                                                    <a href="edit_telephone_directory.php?id=<?php echo $entry['id']; ?>" class="px-3 py-1 bg-blue-500 text-white text-sm rounded hover:bg-blue-600 transition">
                                                        Edit
                                                    </a>
                                                    <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this entry?');">
                                                        <input type="hidden" name="delete_id" value="<?php echo $entry['id']; ?>">
                                                        <button type="submit" class="px-3 py-1 bg-red-500 text-white text-sm rounded hover:bg-red-700 transition">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination mt-6">
                                <!-- First page -->
                                <?php if ($current_page > 1): ?>
                                    <a href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-link">First</a>
                                <?php else: ?>
                                    <span class="disabled">First</span>
                                <?php endif; ?>

                                <!-- Previous page -->
                                <?php if ($current_page > 1): ?>
                                    <a href="?page=<?php echo $current_page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-link">Previous</a>
                                <?php else: ?>
                                    <span class="disabled">Previous</span>
                                <?php endif; ?>

                                <!-- Page numbers -->
                                <?php
                                $start_page = max(1, $current_page - 2);
                                $end_page = min($total_pages, $current_page + 2);
                                
                                for ($i = $start_page; $i <= $end_page; $i++):
                                ?>
                                    <?php if ($i == $current_page): ?>
                                        <span class="current"><?php echo $i; ?></span>
                                    <?php else: ?>
                                        <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-link"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <!-- Next page -->
                                <?php if ($current_page < $total_pages): ?>
                                    <a href="?page=<?php echo $current_page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-link">Next</a>
                                <?php else: ?>
                                    <span class="disabled">Next</span>
                                <?php endif; ?>

                                <!-- Last page -->
                                <?php if ($current_page < $total_pages): ?>
                                    <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-link">Last</a>
                                <?php else: ?>
                                    <span class="disabled">Last</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-hide success/error messages after 5 seconds
        $(document).ready(function() {
            setTimeout(function() {
                $('.bg-green-100, .bg-red-100').fadeOut('slow');
            }, 5000);
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>