<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $type_id = (int)$_POST['type_id'];
    $color = $_POST['color'];

    // Insert only date, type_id, and color
    $stmt = $conn->prepare("INSERT INTO special_dates (date, type_id, color) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $date, $type_id, $color);
    $stmt->execute();

    header("Location: index.php");
    exit;
}
?>
