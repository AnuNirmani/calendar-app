<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $typeId = (int)$_POST['type_id'];
    $color = $_POST['color']; 

    // Lookup type + description
    $stmt = $conn->prepare("SELECT type, description FROM special_types WHERE id = ?");
    $stmt->bind_param("i", $typeId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result) {
        $type = $result['type'];
        $description = $result['description'];

        $insert = $conn->prepare("INSERT INTO special_dates (date, type, description, color) VALUES (?, ?, ?, ?)");
        $insert->bind_param("ssss", $date, $type, $description, $color);
        $insert->execute();
    }

    header("Location: index.php");
    exit;
}
?>
