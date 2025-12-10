<?php
include dirname(__DIR__) . '/../db.php';

$success = '';
$error = '';

if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . ($conn->connect_error ?? 'Connection not established'));
}

// Fetch departments
$departments = $conn->query("SELECT id, name FROM Department");
if (!$departments) {
    die("Query failed: " . $conn->error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $phone_number = trim($_POST['phone_number']);
    $department_id = $_POST['department_id'];

    if (empty($name) || empty($phone_number) || empty($department_id)) {
        $error = "All fields are required.";
    } elseif (!preg_match("/^[0-9]{10}$/", $phone_number)) {
        $error = "Please enter a valid 10-digit phone number.";
    } else {
        $stmt_check = $conn->prepare("SELECT id FROM Department WHERE id = ?");
        $stmt_check->bind_param("i", $department_id);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows == 0) {
            $error = "The selected department does not exist.";
        } else {
            $stmt = $conn->prepare("UPDATE Telephone_Directory SET name = ?, phone_number = ?, department_id = ? WHERE id = ?");
            $stmt->bind_param("ssii", $name, $phone_number, $department_id, $id);
            if ($stmt->execute()) {
                $success = "Entry updated successfully!";
            } else {
                $error = "Error updating entry: " . $conn->error;
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}

// Fetch the entry to edit
$entry = null;
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT id, name, phone_number, department_id FROM Telephone_Directory WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $entry = $result->fetch_assoc();
    $stmt->close();

    if (!$entry) {
        die("Entry not found.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Telephone Directory</title>
    <link rel="icon" href="../../images/logo.jpg" type="image/png">
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
        <!-- Sidebar
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
        </div> -->

        <!-- Main Content -->
        <div class="main-content flex-1 p-8">
            <div class="max-w-4xl mx-auto">
                <h2 class="text-3xl font-bold text-gray-800 mb-6">Edit Telephone Directory Entry</h2>

                <?php if ($success): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($entry): ?>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <form method="POST" action="">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($entry['id']); ?>">
                            <div class="mb-3">
                                <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                                <input type="text" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" id="name" name="name" value="<?php echo htmlspecialchars($entry['name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone_number" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                <input type="text" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($entry['phone_number']); ?>" required pattern="[0-9]{10}">
                            </div>
                            <div class="mb-3">
                                <label for="department_id" class="block text-sm font-medium text-gray-700">Department</label>
                                <select class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" id="department_id" name="department_id" required>
                                    <?php
                                    // Reset departments pointer if needed
                                    $departments->data_seek(0);
                                    while ($dept = $departments->fetch_assoc()): ?>
                                        <option value="<?php echo $dept['id']; ?>" <?php echo $entry['department_id'] == $dept['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <button type="submit" class="py-2 px-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Update Telephone Directory</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>