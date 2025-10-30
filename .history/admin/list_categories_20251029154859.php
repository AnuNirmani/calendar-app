<?php
// ... [previous code remains the same]

// Fetch categories with error handling and check for status column
$categories = [];
$sql = "SELECT * FROM categories ORDER BY created_at DESC";
$result = mysqli_query($conn, $sql);

if ($result === false) {
    $errorMessage = "Database error: " . mysqli_error($conn) . "<br>Please make sure the 'categories' table exists in your database.";
} else {
    // Check if status column exists and has data
    $columns = [];
    $check_columns = mysqli_query($conn, "SHOW COLUMNS FROM categories");
    while ($col = mysqli_fetch_assoc($check_columns)) {
        $columns[] = $col['Field'];
    }
    
    $hasStatusColumn = in_array('status', $columns);
    
    // Check if status column exists but has empty values
    $hasStatusData = false;
    if ($hasStatusColumn) {
        $check_data = mysqli_query($conn, "SELECT COUNT(*) as count FROM categories WHERE status IS NOT NULL AND status != ''");
        $data_row = mysqli_fetch_assoc($check_data);
        $hasStatusData = ($data_row['count'] > 0);
    }
    
    if (!$hasStatusColumn) {
        $errorMessage = "Warning: The 'status' column is missing from the categories table.";
    } elseif (!$hasStatusData) {
        $errorMessage = "Warning: Status column exists but has no data. Please update the status values.";
    }
    
    while ($row = mysqli_fetch_assoc($result)) {
        // Handle missing or empty status
        if (!isset($row['status']) || $row['status'] === '') {
            $row['status'] = 'active'; // Default value
        }
        $categories[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- ... [head section remains the same] -->
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen">
        <!-- ... [sidebar remains the same] -->

        <!-- Main Content -->
        <div class="main-content flex-1 p-8 overflow-y-auto">
            <div class="max-w-4xl mx-auto">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">List of Categories</h1>

                <?php if ($successMessage): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                        <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                <?php endif; ?>

                <?php if ($errorMessage): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                        <?php echo $errorMessage; ?>
                        <div class="mt-2 flex space-x-2">
                            <?php if (strpos($errorMessage, 'missing') !== false): ?>
                                <button onclick="fixDatabaseColumn()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                                    Fix Database Column
                                </button>
                            <?php elseif (strpos($errorMessage, 'no data') !== false): ?>
                                <button onclick="updateStatusValues()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                                    Set All to Active
                                </button>
                            <?php endif; ?>
                            <button onclick="checkTableStructure()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                                Check Table Structure
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ... [rest of the content remains the same] -->
            </div>
        </div>
    </div>

    <script>
        function fixDatabaseColumn() {
            if (confirm('This will check and fix the status column. Continue?')) {
                fetch('add_status_column.php')
                    .then(response => response.text())
                    .then(data => {
                        alert(data);
                        location.reload();
                    })
                    .catch(error => {
                        alert('Error: ' + error);
                    });
            }
        }

        function updateStatusValues() {
            if (confirm('Set all categories to "active" status? Continue?')) {
                fetch('update_status_values.php')
                    .then(response => response.text())
                    .then(data => {
                        alert(data);
                        location.reload();
                    })
                    .catch(error => {
                        alert('Error: ' + error);
                    });
            }
        }

        function checkTableStructure() {
            fetch('check_table_structure.php')
                .then(response => response.text())
                .then(data => {
                    alert('Table Structure:\n' + data);
                })
                .catch(error => {
                    alert('Error: ' + error);
                });
        }

        // Auto-hide messages after 5 seconds
        $(document).ready(function() {
            setTimeout(function() {
                $('.bg-green-100, .bg-red-100').fadeOut('slow');
            }, 5000);
        });
    </script>
</body>
</html>