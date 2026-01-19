<?php
include dirname(__DIR__) . '/../db.php';
include dirname(__DIR__) . '/../auth.php';

// Check authentication
checkAuth();

$successMessage = "";
$errorMessage = "";

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
$sql = "SELECT DISTINCT td.id, td.name, td.phone_number, td.email, td.extension, td.position, d.name AS department_name 
        FROM Telephone_Directory td 
        LEFT JOIN Department d ON td.department_id = d.id";

// Add search conditions if provided
if (!empty($search)) {
    $search_clean = $conn->real_escape_string($search);
    $where_condition = " WHERE td.name LIKE '%$search_clean%' 
                         OR td.phone_number LIKE '%$search_clean%' 
                         OR td.email LIKE '%$search_clean%' 
                         OR td.extension LIKE '%$search_clean%' 
                         OR td.position LIKE '%$search_clean%' 
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
        // Format phone numbers - split comma-separated numbers
        $phone_numbers = !empty($row['phone_number']) ? explode(',', $row['phone_number']) : [];
        $row['phone_numbers'] = $phone_numbers;
        $row['phone_display'] = !empty($phone_numbers) ? implode('<br>', $phone_numbers) : 'N/A';
        
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
    <link rel="icon" href="../../images/logo.jpg" type="image/png">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
        .main-content {
            min-height: calc(100vh - 64px);
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
        .phone-cell {
            max-width: 200px;
        }
        .phone-number {
            display: block;
            padding: 2px 0;
        }
        .copy-phone-btn {
            background: none;
            border: none;
            color: #3b82f6;
            cursor: pointer;
            padding: 2px 5px;
            margin-left: 5px;
            font-size: 0.8rem;
        }
        .copy-phone-btn:hover {
            color: #1d4ed8;
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
            <div class="max-w-7xl mx-auto">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Telephone Directory List</h1>

                <?php if ($successMessage): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded"><?php echo htmlspecialchars($successMessage); ?></div>
                <?php endif; ?>

                <?php if ($errorMessage): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded"><?php echo $errorMessage; ?></div>
                <?php endif; ?>

                <!-- Search and Add Button -->
                <div class="flex flex-col md:flex-row justify-between mb-6 gap-4">
                    <form method="GET" action="" class="flex w-full max-w-md">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, phone, email, position, extension, or department" class="flex-1 border border-gray-300 rounded-l-md shadow-sm p-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <button type="submit" class="py-2 px-4 bg-indigo-600 text-white hover:bg-indigo-700 transition">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="list_telephone_directory.php" class="py-2 px-4 bg-gray-600 text-white hover:bg-gray-700 transition">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        <?php endif; ?>
                    </form>
                    
                    <a href="add_telephone_directory.php" class="py-2 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold inline-flex items-center gap-2">
                        <i class="fas fa-plus"></i> Add New Contact
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
                    <?php if (empty($entries)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-address-book fa-4x text-gray-300 mb-4"></i>
                            <h3 class="text-xl font-semibold text-gray-600 mb-2">No Directory Entries Found</h3>
                            <p class="text-gray-500 mb-6">
                                <?php if (!empty($search)): ?>
                                    No entries match your search criteria.
                                <?php else: ?>
                                    Start by adding your first contact to the directory.
                                <?php endif; ?>
                            </p>
                            <a href="add_telephone_directory.php" class="inline-flex items-center gap-2 py-2 px-6 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                                <i class="fas fa-plus"></i> Add First Contact
                            </a>
                            <?php if (!empty($search)): ?>
                                <a href="list_telephone_directory.php" class="ml-4 inline-flex items-center gap-2 py-2 px-6 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                                    <i class="fas fa-list"></i> View All Entries
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left table-auto">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="p-3 font-semibold text-gray-700 border-b">ID</th>
                                        <th class="p-3 font-semibold text-gray-700 border-b">Name</th>
                                        <th class="p-3 font-semibold text-gray-700 border-b">Position</th>
                                        <th class="p-3 font-semibold text-gray-700 border-b">Phone Numbers</th>
                                        <th class="p-3 font-semibold text-gray-700 border-b">Email</th>
                                        <th class="p-3 font-semibold text-gray-700 border-b">Extension</th>
                                        <th class="p-3 font-semibold text-gray-700 border-b">Department</th>
                                        <th class="p-3 font-semibold text-gray-700 border-b">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($entries as $entry): ?>
                                        <tr class="border-b border-gray-200 hover:bg-gray-50 transition-colors">
                                            <td class="p-3 text-gray-600"><?php echo $entry['id']; ?></td>
                                            <td class="p-3">
                                                <div class="font-medium text-gray-800"><?php echo htmlspecialchars($entry['name']); ?></div>
                                            </td>
                                            <td class="p-3">
                                                <?php if (!empty($entry['position'])): ?>
                                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                                        <?php echo htmlspecialchars($entry['position']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-gray-400 text-sm">Not set</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-3 phone-cell">
                                                <?php if (!empty($entry['phone_numbers'])): ?>
                                                    <div class="space-y-1">
                                                        <?php foreach ($entry['phone_numbers'] as $phone): ?>
                                                            <div class="flex items-center justify-between phone-number">
                                                                <a href="tel:<?php echo htmlspecialchars($phone); ?>" class="text-blue-600 hover:text-blue-800 hover:underline">
                                                                    <?php echo htmlspecialchars($phone); ?>
                                                                </a>
                                                                <button class="copy-phone-btn" onclick="copyToClipboard('<?php echo htmlspecialchars($phone); ?>')" title="Copy number">
                                                                    <i class="fas fa-copy"></i>
                                                                </button>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-gray-400">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-3 email-cell">
                                                <?php if (!empty($entry['email'])): ?>
                                                    <a href="mailto:<?php echo htmlspecialchars($entry['email']); ?>" class="text-blue-600 hover:text-blue-800 hover:underline truncate block">
                                                        <?php echo htmlspecialchars($entry['email']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-gray-400">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-3">
                                                <?php if (!empty($entry['extension'])): ?>
                                                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded">
                                                        <?php echo htmlspecialchars($entry['extension']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-gray-400">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-3">
                                                <?php if (!empty($entry['department_name'])): ?>
                                                    <span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded">
                                                        <?php echo htmlspecialchars($entry['department_name']); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-gray-400">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-3">
                                                <div class="flex space-x-2">
                                                    <a href="edit_telephone_directory.php?id=<?php echo $entry['id']; ?>" class="inline-flex items-center gap-1 px-3 py-1 bg-blue-500 text-white text-sm rounded hover:bg-blue-600 transition" title="Edit">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this contact?');">
                                                        <input type="hidden" name="delete_id" value="<?php echo $entry['id']; ?>">
                                                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-1 bg-red-500 text-white text-sm rounded hover:bg-red-600 transition" title="Delete">
                                                            <i class="fas fa-trash"></i> Delete
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
                                    <a href="?page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-link">
                                        <i class="fas fa-angle-double-left"></i> First
                                    </a>
                                <?php else: ?>
                                    <span class="disabled">
                                        <i class="fas fa-angle-double-left"></i> First
                                    </span>
                                <?php endif; ?>

                                <!-- Previous page -->
                                <?php if ($current_page > 1): ?>
                                    <a href="?page=<?php echo $current_page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-link">
                                        <i class="fas fa-angle-left"></i> Previous
                                    </a>
                                <?php else: ?>
                                    <span class="disabled">
                                        <i class="fas fa-angle-left"></i> Previous
                                    </span>
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
                                    <a href="?page=<?php echo $current_page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-link">
                                        Next <i class="fas fa-angle-right"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="disabled">
                                        Next <i class="fas fa-angle-right"></i>
                                    </span>
                                <?php endif; ?>

                                <!-- Last page -->
                                <?php if ($current_page < $total_pages): ?>
                                    <a href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-link">
                                        Last <i class="fas fa-angle-double-right"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="disabled">
                                        Last <i class="fas fa-angle-double-right"></i>
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        // Auto-hide success/error messages after 5 seconds
        $(document).ready(function() {
            setTimeout(function() {
                $('.bg-green-100, .bg-red-100').fadeOut('slow');
            }, 5000);
        });

        // Copy phone number to clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show success message
                const btn = event.target.closest('button');
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i>';
                btn.style.color = '#10b981';
                
                setTimeout(function() {
                    btn.innerHTML = originalHtml;
                    btn.style.color = '';
                }, 1500);
            }, function(err) {
                console.error('Failed to copy: ', err);
                alert('Failed to copy phone number');
            });
        }

        // Copy all phone numbers for a contact
        function copyAllPhones(entryId) {
            // Get all phone numbers for this entry
            const phoneElements = document.querySelectorAll(`#entry-${entryId} .phone-number`);
            let allPhones = [];
            phoneElements.forEach(el => {
                const phone = el.querySelector('a').textContent.trim();
                allPhones.push(phone);
            });
            
            if (allPhones.length > 0) {
                copyToClipboard(allPhones.join(', '));
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>