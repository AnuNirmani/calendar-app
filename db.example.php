<?php
// Example database configuration file
// Copy this to db.php and update with your actual credentials

$host = 'localhost';
$user = 'your_database_user';
$pass = 'your_database_password';
$dbname = 'calendar_app';

$conn = new mysqli($host, $user, $pass, $dbname);
$currentYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

