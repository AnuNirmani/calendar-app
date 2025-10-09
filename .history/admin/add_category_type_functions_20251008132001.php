<?php


function createCategory($categoryName) {
    global $conn;
    
    // Check if category already exists
    $checkSql = "SELECT id FROM categories WHERE name = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("s", $categoryName);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        return "Category already exists!";
    }
    
    $sql = "INSERT INTO categories (name, status) VALUES (?, 'active')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $categoryName);
    
    if ($stmt->execute()) {
        return true;
    } else {
        return "Database error: " . $conn->error;
    }
}

function updateCategory($categoryId, $categoryName) {
    global $conn;
    
    // Check if category already exists (excluding current category)
    $checkSql = "SELECT id FROM categories WHERE name = ? AND id != ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("si", $categoryName, $categoryId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        return "Category name already exists!";
    }
    
    $sql = "UPDATE categories SET name = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $categoryName, $categoryId);
    
    if ($stmt->execute()) {
        return true;
    } else {
        return "Database error: " . $conn->error;
    }
}

function deleteCategory($categoryId) {
    global $conn;
    
    // FIXED: Check if the posts table exists first, if not, allow deletion
    $tableCheck = $conn->query("SHOW TABLES LIKE 'posts'");
    
    if ($tableCheck && $tableCheck->num_rows > 0) {
        // Check if category is being used in posts (only if posts table exists)
        $checkSql = "SELECT COUNT(*) as post_count FROM posts WHERE category_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("i", $categoryId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['post_count'] > 0) {
            return false; // Category is being used, cannot delete
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

// NEW FUNCTION: Toggle category status
function toggleCategoryStatus($categoryId, $status) {
    global $conn;
    
    $sql = "UPDATE categories SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $categoryId);
    
    if ($stmt->execute()) {
        return true;
    } else {
        return "Database error: " . $conn->error;
    }
}

// NEW FUNCTION: Get category status
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
?>