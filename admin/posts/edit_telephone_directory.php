<?php
include dirname(__DIR__) . '/../db.php';
include dirname(__DIR__) . '/../auth.php';

// Check authentication
checkAuth();

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
    $email = trim($_POST['email']);
    $extension = trim($_POST['extension']);
    $position = trim($_POST['position']);
    $department_id = $_POST['department_id'];
    
    // Get phone numbers - multiple inputs
    $phone_numbers = [];
    if (isset($_POST['phone_number']) && is_array($_POST['phone_number'])) {
        foreach ($_POST['phone_number'] as $phone) {
            $phone = trim($phone);
            if (!empty($phone)) {
                $phone_numbers[] = $phone;
            }
        }
    }
    
    // Validate phone numbers format if provided
    $valid_phones = true;
    foreach ($phone_numbers as $phone) {
        if (!empty($phone) && !preg_match("/^[0-9]{10}$/", $phone)) {
            $valid_phones = false;
            break;
        }
    }
    
    if (empty($name) || empty($department_id)) {
        $error = "Name and Department fields are required.";
    } elseif (!$valid_phones) {
        $error = "Please enter valid 10-digit phone numbers or leave them empty.";
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
            // Combine phone numbers into a comma-separated string
            $phone_numbers_str = !empty($phone_numbers) ? implode(',', $phone_numbers) : '';
            
            // Update telephone directory entry
            $stmt = $conn->prepare("UPDATE Telephone_Directory SET name = ?, phone_number = ?, email = ?, extension = ?, position = ?, department_id = ? WHERE id = ?");
            $stmt->bind_param("sssssii", $name, $phone_numbers_str, $email, $extension, $position, $department_id, $id);
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
    $stmt = $conn->prepare("SELECT id, name, phone_number, email, extension, position, department_id FROM Telephone_Directory WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $entry = $result->fetch_assoc();
    $stmt->close();

    if (!$entry) {
        die("Entry not found.");
    }
    
    // Split phone numbers if they exist
    if (!empty($entry['phone_number'])) {
        $entry['phone_numbers'] = explode(',', $entry['phone_number']);
    } else {
        $entry['phone_numbers'] = [''];
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
    <script src="../../assets/js/tailwind.js"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="../../assets/css/fontawesome.min.css">
    <style>
        .main-content {
            min-height: calc(100vh - 64px);
        }
        .phone-input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 8px;
        }
        .phone-input-wrapper {
            flex: 1;
        }
        .remove-phone-btn {
            padding: 10px 15px;
            background-color: #ef4444;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .remove-phone-btn:hover {
            background-color: #dc2626;
        }
        .add-phone-btn {
            margin-top: 8px;
            padding: 10px 20px;
            background-color: #10b981;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .add-phone-btn:hover {
            background-color: #059669;
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
                <h2 class="text-3xl font-bold text-gray-800 mb-6">Edit Telephone Directory Entry</h2>

                <?php if ($success): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if ($entry): ?>
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <form method="POST" action="" id="contactForm">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($entry['id']); ?>">
                            
                            <div class="mb-4">
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                                <input type="text" 
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-indigo-500 focus:border-indigo-500" 
                                       id="name" 
                                       name="name" 
                                       value="<?php echo htmlspecialchars($entry['name']); ?>" 
                                       required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="position" class="block text-sm font-medium text-gray-700 mb-2">Position/Post</label>
                                <input type="text" 
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-indigo-500 focus:border-indigo-500" 
                                       id="position" 
                                       name="position" 
                                       value="<?php echo htmlspecialchars($entry['position'] ?? ''); ?>" 
                                       placeholder="Enter position or post">
                                <p class="text-xs text-gray-500 mt-1">Optional field - e.g., Manager, Editor, Reporter, etc.</p>
                            </div>
                            
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Phone Numbers</label>
                                <div id="phoneNumbersContainer">
                                    <?php if (isset($entry['phone_numbers'])): ?>
                                        <?php foreach ($entry['phone_numbers'] as $index => $phone): ?>
                                            <div class="phone-input-group">
                                                <div class="phone-input-wrapper">
                                                    <input type="text" 
                                                           class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-indigo-500 focus:border-indigo-500" 
                                                           name="phone_number[]" 
                                                           value="<?php echo htmlspecialchars($phone); ?>"
                                                           placeholder="Enter 10-digit phone number"
                                                           pattern="[0-9]{10}"
                                                           maxlength="10">
                                                </div>
                                                <?php if ($index > 0): ?>
                                                    <button type="button" class="remove-phone-btn">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <button type="button" id="addPhoneBtn" class="add-phone-btn">
                                    <i class="fas fa-plus"></i> Add Another Phone Number
                                </button>
                                <p class="text-xs text-gray-500 mt-1">Optional field - You can add multiple phone numbers. Format: 10 digits only (e.g., 0771234567)</p>
                            </div>
                            
                            <div class="mb-4">
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                <input type="email" 
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-indigo-500 focus:border-indigo-500" 
                                       id="email" 
                                       name="email" 
                                       value="<?php echo htmlspecialchars($entry['email']); ?>">
                                <p class="text-xs text-gray-500 mt-1">Optional field</p>
                            </div>
                            
                            <div class="mb-4">
                                <label for="extension" class="block text-sm font-medium text-gray-700 mb-2">Extension</label>
                                <input type="text" 
                                       class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-indigo-500 focus:border-indigo-500" 
                                       id="extension" 
                                       name="extension" 
                                       value="<?php echo htmlspecialchars($entry['extension']); ?>" 
                                       pattern="[0-9]{1,6}"
                                       maxlength="6">
                                <p class="text-xs text-gray-500 mt-1">Optional field - up to 6 digits</p>
                            </div>
                            
                            <div class="mb-6">
                                <label for="department_id" class="block text-sm font-medium text-gray-700 mb-2">Department *</label>
                                <select class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-indigo-500 focus:border-indigo-500" 
                                        id="department_id" 
                                        name="department_id" 
                                        required>
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
                                <button type="submit" class="py-3 px-6 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-semibold">
                                    Update Entry
                                </button>
                                <a href="list_telephone_directory.php" class="py-3 px-6 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition font-semibold">
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
        document.addEventListener('DOMContentLoaded', function() {
            const phoneContainer = document.getElementById('phoneNumbersContainer');
            const addPhoneBtn = document.getElementById('addPhoneBtn');
            
            // Function to add new phone number input
            function addPhoneInput() {
                const phoneGroup = document.createElement('div');
                phoneGroup.className = 'phone-input-group';
                phoneGroup.innerHTML = `
                    <div class="phone-input-wrapper">
                        <input type="text" 
                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm p-3 focus:ring-indigo-500 focus:border-indigo-500" 
                               name="phone_number[]" 
                               placeholder="Enter 10-digit phone number"
                               pattern="[0-9]{10}"
                               maxlength="10">
                    </div>
                    <button type="button" class="remove-phone-btn">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                
                phoneContainer.appendChild(phoneGroup);
                
                // Add event listener to remove button
                const removeBtn = phoneGroup.querySelector('.remove-phone-btn');
                removeBtn.addEventListener('click', function() {
                    phoneGroup.remove();
                });
            }
            
            // Add event listener to add phone button
            addPhoneBtn.addEventListener('click', addPhoneInput);
            
            // Add event listeners to existing remove buttons
            const existingRemoveBtns = document.querySelectorAll('.remove-phone-btn');
            existingRemoveBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    this.closest('.phone-input-group').remove();
                });
            });
            
            // Add event listener to form submission for phone number validation
            const form = document.getElementById('contactForm');
            form.addEventListener('submit', function(event) {
                const phoneInputs = document.querySelectorAll('input[name="phone_number[]"]');
                let hasValidPhone = true;
                let hasAtLeastOnePhone = false;
                
                phoneInputs.forEach(input => {
                    if (input.value) {
                        hasAtLeastOnePhone = true;
                        if (!input.value.match(/^[0-9]{10}$/)) {
                            hasValidPhone = false;
                            input.style.borderColor = '#ef4444';
                        } else {
                            input.style.borderColor = '';
                        }
                    } else {
                        input.style.borderColor = '';
                    }
                });
                
                if (hasAtLeastOnePhone && !hasValidPhone) {
                    event.preventDefault();
                    alert('Please enter valid 10-digit phone numbers or leave them empty.');
                    return false;
                }
                
                return true;
            });
            
            // Auto-format phone numbers
            phoneContainer.addEventListener('input', function(event) {
                if (event.target.name === 'phone_number[]') {
                    let value = event.target.value.replace(/\D/g, '');
                    value = value.slice(0, 10);
                    event.target.value = value;
                }
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>