<?php
include dirname(__DIR__) . '/../db.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

header('Content-Type: text/plain');

// First check if status column already exists
$check_sql = "SELECT COUNT(*) as count FROM information_schema.columns 
              WHERE table_schema = DATABASE() 
              AND table_name = 'categories' 
              AND column_name = 'status'";
              
$result = mysqli_query($conn, $check_sql);
$row = mysqli_fetch_assoc($result);

if ($row['count'] > 0) {
    // Column exists, modify it to ensure correct data type
    $modify_sql = "ALTER TABLE categories MODIFY COLUMN status ENUM('active', 'inactive') DEFAULT 'active'";
    
    if (mysqli_query($conn, $modify_sql)) {
        // Update any NULL or empty status values
        $update_sql = "UPDATE categories SET status = 'active' WHERE status IS NULL OR status = ''";
        mysqli_query($conn, $update_sql);
        
        echo "Status column already exists. Updated data type and set default values.";
    } else {
        echo "Error modifying status column: " . mysqli_error($conn);
    }
} else {
    // Column doesn't exist, add it
    $add_sql = "ALTER TABLE categories ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active' AFTER name";
    
    if (mysqli_query($conn, $add_sql)) {
        echo "Status column added successfully! All existing categories set to 'active'.";
    } else {
        echo "Error adding status column: " . mysqli_error($conn);
    }
}
?>