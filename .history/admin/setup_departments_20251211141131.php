<?php
// setup_departments.php
include dirname(__DIR__) . '/../db.php';
include dirname(__DIR__) . '/../auth.php';

// Check authentication
checkAuth();

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Department list exactly as requested
$departments = [
    'Chairman & Chairman\'s Staff',
    'Board of Directors',
    'Executive Directors',
    'Administration 1',
    'Administration 2',
    'HRD',
    'Ada/ Go/ Tharanaya',
    'Daily Lankadeepa',
    'Daily Mirror',
    'Daily FT',
    'Deshaya',
    'Hi Magazine',
    'LW Magazine',
    'Pariganaka/ EasyGuide',
    'Sirikatha',
    'Sunday Lankadeepa',
    'Sunday Times',
    'Tamil Mirror',
    'Tamil Vijey',
    'Wijeya/ Bilindu/ Braille',
    'Advertising Department',
    'Circulation Department',
    'Brands & Promotions',
    'DIGITAL MEDIA',
    'Account Department',
    'Credit Control',
    'Internal Audit Department',
    'Design Department',
    'Desktop Publishing (Typesetting)',
    'Despatch',
    'Electrical',
    'Hokandara Factory',
    'Information System Department',
    'Library',
    'Maintenance Department',
    'Photo Copy',
    'Press',
    'Production',
    'Security Department',
    'Stores',
    'Transport Department',
    'LHPP',
    'RS Printek (Pvt) Ltd',
    'Sarathi Ltd',
    'Wijeya Graphics',
    'Wijeya Networks (Pvt) Ltd',
    'Other Useful Numbers'
];

$added_count = 0;
$existing_count = 0;
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_departments'])) {
    // Check if table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'Department'");

    if ($table_check && $table_check->num_rows > 0) {
        // Check for existing departments
        $existing_departments = [];
        $result = $conn->query("SELECT name FROM Department");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $existing_departments[] = $row['name'];
            }
        }
        
        // Add new departments
        $stmt = $conn->prepare("INSERT INTO Department (name) VALUES (?)");
        
        foreach ($departments as $dept) {
            if (!in_array($dept, $existing_departments)) {
                $stmt->bind_param("s", $dept);
                if ($stmt->execute()) {
                    $added_count++;
                } else {
                    $errors[] = "Failed to add: $dept - " . $conn->error;
                }
            } else {
                $existing_count++;
            }
        }
        
        $stmt->close();
        
        // Set success message in session
        $_SESSION['setup_success'] = "Added $added_count new departments. $existing_count departments already existed.";
        header("Location: setup_departments.php");
        exit();
    } else {
        $errors[] = "Department table does not exist! Please create the Department table first.";
    }
}

// Get success message from session
$success = '';
if (isset($_SESSION['setup_success'])) {
    $success = $_SESSION['setup_success'];
    unset($_SESSION['setup_success']);
}

// Count current departments
$current_count = 0;
$result = $conn->query("SELECT COUNT(*) as count FROM Department");
if ($result) {
    $row = $result->fetch_assoc();
    $current_count = $row['count'];
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Departments</title>
    <link rel="icon" href="../../images/logo.jpg" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex min-h-screen">
        <?php 
        $base_path = '../';
        include dirname(__DIR__) . '/includes/slidebar2.php'; 
        ?>

        <!-- Main Content -->
        <div class="flex-1 p-8 overflow-y-auto">
            <div class="max-w-4xl mx-auto">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Setup Departments</h1>
                
                <?php if ($success): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-6">
                        <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                        <p class="font-bold"><i class="fas fa-exclamation-circle mr-2"></i>Errors:</p>
                        <ul class="list-disc ml-5 mt-2">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                    <div class="mb-6">
                        <h2 class="text-xl font-semibold text-gray-800 mb-2">Current Status</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-blue-50 p-4 rounded">
                                <p class="text-sm text-blue-600">Total Departments in System</p>
                                <p class="text-3xl font-bold text-blue-800"><?php echo $current_count; ?></p>
                            </div>
                            <div class="bg-green-50 p-4 rounded">
                                <p class="text-sm text-green-600">Departments Ready to Add</p>
                                <p class="text-3xl font-bold text-green-800"><?php echo count($departments); ?></p>
                            </div>
                            <div class="bg-yellow-50 p-4 rounded">
                                <p class="text-sm text-yellow-600">Departments Already Exist</p>
                                <p class="text-3xl font-bold text-yellow-800"><?php echo $existing_count; ?></p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="">
                        <div class="mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">Available Departments List</h3>
                            <div class="border border-gray-300 rounded p-4 max-h-80 overflow-y-auto">
                                <ul class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                    <?php foreach ($departments as $index => $dept): ?>
                                        <li class="flex items-center">
                                            <span class="text-gray-500 text-sm mr-2"><?php echo $index + 1; ?>.</span>
                                            <span class="text-gray-700"><?php echo htmlspecialchars($dept); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>

                        <div class="mb-6 p-4 bg-yellow-50 border-l-4 border-yellow-500 rounded">
                            <p class="font-semibold text-yellow-800"><i class="fas fa-info-circle mr-2"></i>Important Note</p>
                            <p class="text-yellow-700 mt-2">This will add <?php echo count($departments); ?> departments to your system. Only departments that don't already exist will be added.</p>
                        </div>

                        <div class="flex gap-4">
                            <button type="submit" name="add_departments" class="py-3 px-6 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold flex items-center">
                                <i class="fas fa-database mr-2"></i>Add All Departments
                            </button>
                            <a href="add_telephone_directory.php" class="py-3 px-6 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition font-semibold flex items-center">
                                <i class="fas fa-phone mr-2"></i>Go to Telephone Directory
                            </a>
                            <a href="create_department.php" class="py-3 px-6 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition font-semibold flex items-center">
                                <i class="fas fa-plus mr-2"></i>Add Single Department
                            </a>
                        </div>
                    </form>
                </div>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Quick Actions</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <a href="list_departments.php" class="p-4 border border-gray-300 rounded hover:bg-gray-50 transition flex items-center">
                            <i class="fas fa-list text-blue-500 text-xl mr-3"></i>
                            <div>
                                <p class="font-semibold">View All Departments</p>
                                <p class="text-sm text-gray-600">See all departments in the system</p>
                            </div>
                        </a>
                        <a href="list_telephone_directory.php" class="p-4 border border-gray-300 rounded hover:bg-gray-50 transition flex items-center">
                            <i class="fas fa-address-book text-green-500 text-xl mr-3"></i>
                            <div>
                                <p class="font-semibold">Telephone Directory</p>
                                <p class="text-sm text-gray-600">View all telephone entries</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>