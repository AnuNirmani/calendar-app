this is add_telephone_directory.php code - <?php
include dirname(__DIR__) . '/../db.php';
include dirname(__DIR__) . '/../auth.php';

// Check authentication
checkAuth();

$success = '';
$error = '';

// Check database connection
if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . ($conn->connect_error ?? 'Connection not established'));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $phone_number = trim($_POST['phone_number']);
    $email = trim($_POST['email']);
    $extension = trim($_POST['extension']);
    $department_id = $_POST['department_id'];

    if (empty($name) || empty($phone_number) || empty($department_id)) {
        $error = "Name, Phone Number and Department fields are required.";
    } elseif (!preg_match("/^[0-9]{10}$/", $phone_number)) {
        $error = "Please enter a valid 10-digit phone number.";
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (!empty($extension) && !preg_match("/^[0-9]{1,6}$/", $extension)) {
        $error = "Extension must be numeric and up to 6 digits.";
    } else {
        // Check if department_id exists
        $stmt_check = $conn->prepare("SELECT id FROM Department WHERE id = ?");
        $stmt_check->bind_param("i", $department_id);
        $stmt_check->execute();
        
        if ($stmt_check->get_result()->num_rows == 0) {
            $error = "The selected department does not exist.";
        } else {
            // Insert telephone directory entry
            $stmt = $conn->prepare("INSERT INTO Telephone_Directory (name, phone_number, email, extension, department_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $name, $phone_number, $email, $extension, $department_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "Telephone entry added successfully!";
                header("Location: add_telephone_directory.php");
                exit();
            } else {
                $error = "Error adding entry: " . $conn->error;
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}

// Get success message from session
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Fetch departments from the database
$departments = $conn->query("SELECT id, name FROM Department ORDER BY name ASC");

if ($departments === false) {
    $error = "Database error: " . $conn->error . "<br>Please make sure the 'Department' table exists in your database.";
    $department_list = false;
} else {
    $department_list = $departments->num_rows > 0 ? $departments : false;
    if (!$department_list) {
        $error = "No departments available. Please add departments first.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Telephone Directory</title>
    <link rel="icon" href="../../images/logo.jpg" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .main-content {
            min-height: calc(100vh - 64px);
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex min-h-screen">
        <?php 
        // For sidebar path adjustments - we're in admin/posts/, sidebar links are relative to admin/
        $base_path = '../';
        include dirname(__DIR__) . '/includes/slidebar2.php'; 
        ?>

        <!-- Main Content -->
        <div class="main-content flex-1 p-8 overflow-y-auto">
            <div class="max-w-4xl mx-auto">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Add Telephone Directory</h1>
                
                <?php if ($success): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded mb-6">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-6">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($department_list): ?>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Enter Contact Information</h2>
                        <form method="POST" action="">
                            <div class="mb-4">
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                                <input type="text" 
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-indigo-500 focus:border-indigo-500" 
                                       id="name" 
                                       name="name" 
                                       placeholder="Enter full name"
                                       required
                                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                            </div>
                            
                            <div class="mb-4">
                                <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-2">Phone Number *</label>
                                <input type="text" 
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-indigo-500 focus:border-indigo-500" 
                                       id="phone_number" 
                                       name="phone_number" 
                                       placeholder="Enter 10-digit phone number"
                                       required 
                                       pattern="[0-9]{10}"
                                       maxlength="10"
                                       value="<?php echo isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : ''; ?>">
                                <p class="text-xs text-gray-500 mt-1">Format: 10 digits only (e.g., 0771234567)</p>
                            </div>
                            
                            <div class="mb-4">
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                <input type="email" 
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-indigo-500 focus:border-indigo-500" 
                                       id="email" 
                                       name="email" 
                                       placeholder="Enter email address"
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                <p class="text-xs text-gray-500 mt-1">Optional field</p>
                            </div>
                            
                            <div class="mb-4">
                                <label for="extension" class="block text-sm font-medium text-gray-700 mb-2">Extension</label>
                                <input type="text" 
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-indigo-500 focus:border-indigo-500" 
                                       id="extension" 
                                       name="extension" 
                                       placeholder="Enter extension number"
                                       pattern="[0-9]{1,6}"
                                       maxlength="6"
                                       value="<?php echo isset($_POST['extension']) ? htmlspecialchars($_POST['extension']) : ''; ?>">
                                <p class="text-xs text-gray-500 mt-1">Optional field - up to 6 digits</p>
                            </div>
                            
                            <div class="mb-6">
                                <label for="department_id" class="block text-sm font-medium text-gray-700 mb-2">Department *</label>
                                <select class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-indigo-500 focus:border-indigo-500" 
                                        id="department_id" 
                                        name="department_id" 
                                        required>
                                    <option value="">-- Select Department --</option>
                                    <?php 
                                    // Reset pointer to beginning
                                    $departments->data_seek(0);
                                    while ($row = $department_list->fetch_assoc()): 
                                        $selected = (isset($_POST['department_id']) && $_POST['department_id'] == $row['id']) ? 'selected' : '';
                                    ?>
                                        <option value="<?php echo $row['id']; ?>" <?php echo $selected; ?>>
                                            <?php echo htmlspecialchars($row['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="flex gap-4">
                                <button type="submit" class="py-3 px-6 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-semibold">
                                    Add to Directory
                                </button>
                                <a href="list_telephone_directory.php" class="py-3 px-6 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition font-semibold">
                                    View Directory
                                </a>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded">
                        <p class="font-semibold">No Departments Available</p>
                        <p class="mt-2">Please create departments first before adding telephone directory entries.</p>
                        <a href="create_department.php" class="mt-3 inline-block py-2 px-4 bg-yellow-600 text-white rounded hover:bg-yellow-700">
                            Create Department
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
<?php $conn->close(); ?> , I want to add these department to select department part; Chairman & Chairman's Staff, Board of Directors, Executive Directors, Administration 1, Administration 2, HRD, Ada/ Go/ Tharanaya, Daily Lankadeepa, Daily Mirror, Daily FT, Deshaya, Hi Magazine, LW Magazine, Pariganaka/ EasyGuide, Sirikatha, Sunday Lankadeepa, Sunday Times, Tamil Mirror, Tamil Vijey, Wijeya/ Bilindu/ Braille, Advertising Department, Circulation Department, Brands & Promotions, DIGITAL MEDIA, Account Department, Credit Control, Internal Audit Department, Design Department, Desktop Publishing (Typesetting), Despatch, Electrical, Hokandara Factory, Information System Department, Library, Maintenance Department, Photo Copy, Press, Production, Security Department, Stores, Transport Department, LHPP, RS Printek (Pvt) Ltd, Sarathi Ltd, Wijeya Graphics, Wijeya Networks (Pvt) Ltd, Other Useful Numbers