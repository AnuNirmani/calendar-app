<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'calendar_app';

$conn = new mysqli($host, $user, $pass, $dbname);
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if categories table exists, create if not
$table_check = $conn->query("SHOW TABLES LIKE 'categories'");
if ($table_check->num_rows == 0) {
    $create_table = "CREATE TABLE categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_table)) {
        error_log("Categories table created successfully");
    } else {
        error_log("Error creating categories table: " . $conn->error);
    }
}
?>