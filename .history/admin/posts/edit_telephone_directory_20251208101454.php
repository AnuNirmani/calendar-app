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
    $email = trim($_POST['email']);
    $extension = trim($_POST['extension']);
    $department_id = $_POST['department_id'];

    if (empty($name) || empty($phone_number) || empty($department_id) || empty($email)) {
        $error = "Name, Phone Number, Email, and Department are required.";
    } elseif (!preg_match("/^[0-9]{10}$/", $phone_number)) {
        $error = "Please enter a valid 10-digit phone number.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (!empty($extension) && !preg_match("/^[0-9]{3,6}$/", $extension)) {
        $error = "Extension must be 3-6 digits if provided.";
    } else {
        $stmt_check = $conn->prepare("SELECT id FROM Department WHERE id = ?");
        $stmt_check->bind_param("i", $department_id);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows == 0) {
            $error = "The selected department does not exist.";
        } else {
            $stmt = $conn->prepare("UPDATE Telephone_Directory SET name = ?, phone_number = ?, email = ?, extension = ?, department_id = ? WHERE id = ?");
            $stmt->bind_param("ssssii", $name, $phone_number, $email, $extension, $department_id, $id);
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
    $stmt = $conn->prepare("SELECT id, name, phone_number, email, extension, department_id FROM Telephone_Directory WHERE id = ?");
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
        <!-- Main Content -->
        <div class="main-content flex-1 p-8">
            <div class="max-w-4xl mx-auto">
                <h2 class="text-3xl font-bold text-gray-800 mb-6">Edit Telephone Directory Entry</h2>

                <?php if ($success): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($entry): ?>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <form method="POST" action="">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($entry['id']); ?>">
                            
                            <div class="mb-4">
                                <label for="name" class="block text-sm font-medium text-gray-700">Name *</label>
                                <input type="text" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" id="name" name="name" value="<?php echo htmlspecialchars($entry['name']); ?>" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="phone_number" class="block text-sm font-medium text-gray-700">Phone Number *</label>
                                <input type="text" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($entry['phone_number']); ?>" required pattern="[0-9]{10}" title="10-digit phone number">
                                <p class="mt-1 text-xs text-gray-500">Format: 10 digits (e.g., 9876543210)</p>
                            </div>
                            
                            <div class="mb-4">
                                <label for="email" class="block text-sm font-medium text-gray-700">Email *</label>
                                <input type="email" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" id="email" name="email" value="<?php echo htmlspecialchars($entry['email']); ?>" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="extension" class="block text-sm font-medium text-gray-700">Extension</label>
                                <input type="text" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500" id="extension" name="extension" value="<?php echo htmlspecialchars($entry['extension']); ?>" pattern="[0-9]{3,6}" title="3-6 digit extension">
                                <p class="mt-1 text-xs text-gray-500">Optional: 3-6 digits</p>
                            </div>
                            
                            <div class="mb-6">
                                <label for="department_id" class="block text-sm font-medium text-gray-700">Department *</label>
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
                            
                            <div class="flex items-center space-x-4">
                                <button type="submit" class="py-2 px-6 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition duration-150">
                                    Update Entry
                                </button>
                                <a href="list_telephone_directory.php" class="py-2 px-6 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition duration-150">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const phoneNumber = document.getElementById('phone_number').value;
            const email = document.getElementById('email').value;
            const extension = document.getElementById('extension').value;
            
            // Validate phone number
            if (!/^[0-9]{10}$/.test(phoneNumber)) {
                alert('Please enter a valid 10-digit phone number.');
                e.preventDefault();
                return false;
            }
            
            // Validate email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Please enter a valid email address.');
                e.preventDefault();
                return false;
            }
            
            // Validate extension (if provided)
            if (extension && !/^[0-9]{3,6}$/.test(extension)) {
                alert('Extension must be 3-6 digits if provided.');
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>