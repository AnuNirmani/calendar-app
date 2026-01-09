<?php
require_once '../../db.php';

function getCategories() {
    global $conn;
    $sql = "SELECT * FROM categories ORDER BY created_at DESC";
    $result = $conn->query($sql);
    
    if (!$result) {
        return ['error' => 'Database error: ' . $conn->error];
    }
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    return $categories;
}

function createCategory($categoryName, $status = 'active') {
    global $conn;
    
    // Validate status
    if (!in_array($status, ['active', 'inactive'])) {
        $status = 'active';
    }
    
    // Check if category already exists
    $checkSql = "SELECT id FROM categories WHERE name = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("s", $categoryName);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        return "Category name already exists!";
    }
    
    $sql = "INSERT INTO categories (name, status) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $categoryName, $status);
    
    if ($stmt->execute()) {
        return true;
    } else {
        return "Database error: " . $conn->error;
    }
}

function updateCategory($categoryId, $categoryName, $status) {
    global $conn;
    
    // Validate status
    if (!in_array($status, ['active', 'inactive'])) {
        return "Invalid status value.";
    }
    
    // Check if category name already exists (excluding current category)
    $checkSql = "SELECT id FROM categories WHERE name = ? AND id != ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("si", $categoryName, $categoryId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        return "Category name already exists!";
    }
    
    $sql = "UPDATE categories SET name = ?, status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $categoryName, $status, $categoryId);
    
    if ($stmt->execute()) {
        return true;
    } else {
        return "Database error: " . $conn->error;
    }
}

function deleteCategory($categoryId) {
    global $conn;
    
    // Check if the posts table exists first
    $tableCheck = $conn->query("SHOW TABLES LIKE 'posts'");
    
    if ($tableCheck && $tableCheck->num_rows > 0) {
        // Check if category is being used in posts
        $checkSql = "SELECT COUNT(*) as post_count FROM posts WHERE category_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("i", $categoryId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['post_count'] > 0) {
            return "Cannot delete: Category is being used by " . $row['post_count'] . " post(s)";
        }
    }
    
    // Delete the category
    $deleteSql = "DELETE FROM categories WHERE id = ?";
    $deleteStmt = $conn->prepare($deleteSql);
    $deleteStmt->bind_param("i", $categoryId);
    
    if ($deleteStmt->execute()) {
        return true;
    } else {
        return "Database error: " . $conn->error;
    }
}

function toggleCategoryStatus($categoryId, $status) {
    global $conn;
    
    // Validate status
    if (!in_array($status, ['active', 'inactive'])) {
        return "Invalid status value.";
    }
    
    $sql = "UPDATE categories SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $categoryId);
    
    if ($stmt->execute()) {
        return true;
    } else {
        return "Database error: " . $conn->error;
    }
}

function getCategoryStatus($categoryId) {
    global $conn;
    
    $sql = "SELECT status FROM categories WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['status'];
    }
    
    return false;
}

function getCategoryById($id) {
    global $conn;
    
    $sql = "SELECT * FROM categories WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row;
    }
    
    return false;
}

// function getCategoryBySlug($slug) {
//     global $conn;
    
//     $sql = "SELECT * FROM categories WHERE slug = ?";
//     $stmt = $conn->prepare($sql);
//     $stmt->bind_param("s", $slug);
//     $stmt->execute();
//     $result = $stmt->get_result();
    
//     if ($row = $result->fetch_assoc()) {
//         return $row;
//     }
    
//     return false;
// }
?>