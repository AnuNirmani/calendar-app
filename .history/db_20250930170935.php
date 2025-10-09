<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'calendar_app';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to check and create table if needed
function checkCategoriesTable($conn) {
    $result = $conn->query("SHOW TABLES LIKE 'categories'");
    if ($result->num_rows == 0) {
        // Create table
        $sql = "CREATE TABLE categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($sql) {
            return true;
        } else {
            return "Error creating table: " . $conn->error;
        }
    }
    return true;
}

// Check table on every load
checkCategoriesTable($conn);
?>