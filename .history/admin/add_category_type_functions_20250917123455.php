<?php
require 'db.php';

// Fetch all categories
function getCategories() {
    global $conn;
    $result = $conn->query("SELECT * FROM categories ORDER BY id DESC");
    $categories = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    return $categories;
}

// Insert category
function createCategory($name) {
    global $conn;
    // Check duplicate
    $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        return "Category already exists!";
    }
    $stmt->close();

    // Insert
    $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
    $stmt->bind_param("s", $name);
    if ($stmt->execute()) {
        return true;
    } else {
        return "Failed to create category!";
    }
}
?>
