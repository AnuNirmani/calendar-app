<?php
include '../db.php';
include '../auth.php';

// Check if user is authenticated (both admin and super_admin can access)
checkAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $type_id = (int)$_POST['type_id'];
    $description = $_POST['description'] ?? '';
    $color = $_POST['color'] ?? '';

    // Insert date, type_id, description, and color
    $stmt = $conn->prepare("INSERT INTO special_dates (date, type_id, description, color) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siss", $date, $type_id, $description, $color);
    $stmt->execute();

    header("Location: index.php");
    exit;
}
?>