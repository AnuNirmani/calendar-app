<?php
include '../db.php';

if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . ($conn->connect_error ?? 'Connection not established'));
}

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $stmt = $conn->prepare("DELETE FROM Telephone_Directory WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $success = "Entry deleted successfully!";
    } else {
        $error = "Error deleting entry: " . $conn->error;
    }
    $stmt->close();
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
$sql = "SELECT DISTINCT td.id, td.name, td.phone_number, d.name AS department_name 
        FROM Telephone_Directory td 
        LEFT JOIN Department d ON td.department_id = d.id";

// Add search conditions if provided
if (!empty($search)) {
    $search_clean = $conn->real_escape_string($search);
    $where_condition = " WHERE td.name LIKE '%$search_clean%' 
                         OR td.phone_number LIKE '%$search_clean%' 
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telephone Directory</title>
    <link rel="icon" href="../images/logo.jpg" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
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
        .table-custom {
            font-size: 0.85rem !important;
            border-collapse: collapse !important;
        }
        .table-custom th,
        .table-custom td {
            padding: 6px !important;
            text-align: left;
            border: 1px solid #dee2e6 !important;
            font-weight: bold !important;
        }
        .table-custom th {
            background-color: #f8f9fa !important;
        }
        .table-custom thead th {
            border-bottom: 2px solid #dee2e6 !important;
        }
        .table-custom tbody tr:hover {
            background-color: #f1f1f1 !important;
        }
        .add-entry-btn {
            @apply py-2 px-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700;
        }
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 20px;
            gap: 10px;
        }
        .pagination a, .pagination span {
            padding: 8px 16px;
            border: 1px solid #ddd;
            text-decoration: none;
            color: #333;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .pagination a:hover {
            background-color: #e9ecef;
        }
        .pagination .current {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        .pagination .disabled {
            color: #6c757d;
            pointer-events: none;
            background-color: #f8f9fa;
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
            <div class="max-w-4xl mx-auto">
                <h2 class="text-3xl font-bold text-gray-800 mb-6">List of Telephone directories</h2>
                
                <?php if (isset($success)): ?>
                    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-6"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="flex justify-between mb-3">
                    <form method="GET" action="" class="flex w-full max-w-md">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, phone, or department" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <button type="submit" class="ml-2 py-2 px-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Search</button>
                        <?php if (!empty($search)): ?>
                            <a href="list_telephone_directory.php" class="ml-2 py-2 px-4 bg-gray-600 text-white rounded-lg hover:bg-gray-700">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Results Summary -->
                <div class="mb-4 text-sm text-gray-600">
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

                <?php if ($result->num_rows > 0): ?>
                    <div class="bg-white p-6 rounded-lg shadow-md overflow-x-auto">
                        <table class="table-custom w-full">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Phone Number</th>
                                    <th>Department</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                                        <td><?php echo htmlspecialchars($row['department_name'] ?: 'N/A'); ?></td>
                                        <td>
                                            <a href="edit_telephone_directory.php?id=<?php echo $row['id']; ?>" class="py-1 px-2 bg-blue-600 text-white rounded hover:bg-blue-700">Edit</a>
                                            <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this entry?');">
                                                <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                                <button type="submit" class="py-1 px-2 bg-red-600 text-white rounded hover:bg-red-700">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <!-- First page -->
                            <?php if ($current_page > 1): ?>
                                <a href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">First</a>
                            <?php else: ?>
                                <span class="disabled">First</span>
                            <?php endif; ?>

                            <!-- Previous page -->
                            <?php if ($current_page > 1): ?>
                                <a href="?page=<?php echo $current_page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Previous</a>
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
                                    <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <!-- Next page -->
                            <?php if ($current_page < $total_pages): ?>
                                <a href="?page=<?php echo $current_page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Next</a>
                            <?php else: ?>
                                <span class="disabled">Next</span>
                            <?php endif; ?>

                            <!-- Last page -->
                            <?php if ($current_page < $total_pages): ?>
                                <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">Last</a>
                            <?php else: ?>
                                <span class="disabled">Last</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-6">
                        No entries found in the telephone directory.
                        <?php if (!empty($search)): ?>
                            <a href="list_telephone_directory.php" class="ml-2 text-blue-800 underline">View all entries</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>