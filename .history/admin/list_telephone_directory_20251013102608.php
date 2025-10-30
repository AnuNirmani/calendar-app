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

// Initialize search query
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql = "SELECT DISTINCT td.id, td.name, td.phone_number, d.name AS department_name 
        FROM Telephone_Directory td 
        LEFT JOIN Department d ON td.department_id = d.id";

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $sql .= " WHERE td.name LIKE '%$search%' 
              OR td.phone_number LIKE '%$search%' 
              OR d.name LIKE '%$search%'";
}

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
                    </form>
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
                <?php else: ?>
                    <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-6">No entries found in the telephone directory.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>