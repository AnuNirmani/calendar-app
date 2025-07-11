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
?>
