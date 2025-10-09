<?php
// add_category_type.php - Main API endpoint for category operations
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

// Get the request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Initialize response array
$response = [
    'success' => false,
    'data' => null,
    'message' => ''
];

try {
    switch ($method) {
        case 'GET':
            if ($action === 'list' || empty($action)) {
                $response = getCategories($conn);
            } else {
                $response['message'] = 'Invalid action for GET request';
                http_response_code(400);
            }
            break;
            
        case 'POST':
            if ($action === 'create') {
                $input = json_decode(file_get_contents('php://input'), true);
                $response = createCategory($conn, $input);
            } else {
                $response['message'] = 'Invalid action for POST request';
                http_response_code(400);
            }
            break;
            
        case 'DELETE':
            if ($action === 'delete' && isset($_GET['id'])) {
                $response = deleteCategory($conn, $_GET['id']);
            } else {
                $response['message'] = 'Invalid action or missing ID for DELETE request';
                http_response_code(400);
            }
            break;
            
        default:
            $response['message'] = 'Method not allowed';
            http_response_code(405);
            break;
    }
} catch (Exception $e) {
    $response['message'] = 'Server error: ' . $e->getMessage();
    http_response_code(500);
    error_log("Category API Error: " . $e->getMessage());
}

echo json_encode($response);

// Function to get all categories
function getCategories($conn) {
    $response = ['success' => false, 'data' => [], 'message' => ''];
    
    try {
        $sql = "SELECT id, name, created_at FROM categories ORDER BY created_at DESC";
        $result = $conn->query($sql);
        
        if ($result === false) {
            throw new Exception("Database query failed: " . $conn->error);
        }
        
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = [
                'id' => (int)$row['id'],
                'name' => htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'),
                'createdAt' => date('M j, Y g:i A', strtotime($row['created_at']))
            ];
        }
        
        $response['success'] = true;
        $response['data'] = $categories;
        $response['message'] = 'Categories retrieved successfully';
        
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
        error_log("Get Categories Error: " . $e->getMessage());
    }
    
    return $response;
}

// Function to create a new category
function createCategory($conn, $input) {
    $response = ['success' => false, 'data' => null, 'message' => ''];
    
    try {
        // Validate input
        if (!isset($input['name']) || empty(trim($input['name']))) {
            throw new Exception("Category name is required");
        }
        
        $categoryName = trim($input['name']);
        
        // Validate category name length
        if (strlen($categoryName) < 2) {
            throw new Exception("Category name must be at least 2 characters long");
        }
        
        if (strlen($categoryName) > 50) {
            throw new Exception("Category name must be less than 50 characters");
        }
        
        // Check if category already exists (case-insensitive)
        $checkSql = "SELECT id FROM categories WHERE LOWER(name) = LOWER(?)";
        $checkStmt = $conn->prepare($checkSql);
        
        if (!$checkStmt) {
            throw new Exception("Database prepare failed: " . $conn->error);
        }
        
        $checkStmt->bind_param("s", $categoryName);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception("Category with this name already exists");
        }
        
        $checkStmt->close();
        
        // Insert new category
        $insertSql = "INSERT INTO categories (name) VALUES (?)";
        $insertStmt = $conn->prepare($insertSql);
        
        if (!$insertStmt) {
            throw new Exception("Database prepare failed: " . $conn->error);
        }
        
        $insertStmt->bind_param("s", $categoryName);
        
        if ($insertStmt->execute()) {
            $categoryId = $conn->insert_id;
            
            // Get the created category
            $getSql = "SELECT id, name, created_at FROM categories WHERE id = ?";
            $getStmt = $conn->prepare($getSql);
            $getStmt->bind_param("i", $categoryId);
            $getStmt->execute();
            $getResult = $getStmt->get_result();
            $category = $getResult->fetch_assoc();
            
            $response['success'] = true;
            $response['data'] = [
                'id' => (int)$category['id'],
                'name' => htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8'),
                'createdAt' => date('M j, Y g:i A', strtotime($category['created_at']))
            ];
            $response['message'] = 'Category created successfully';
            
            $getStmt->close();
        } else {
            throw new Exception("Failed to insert category: " . $conn->error);
        }
        
        $insertStmt->close();
        
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
        error_log("Create Category Error: " . $e->getMessage());
    }
    
    return $response;
}

// Function to delete a category
function deleteCategory($conn, $categoryId) {
    $response = ['success' => false, 'data' => null, 'message' => ''];
    
    try {
        // Validate category ID
        if (!is_numeric($categoryId) || $categoryId <= 0) {
            throw new Exception("Invalid category ID");
        }
        
        $categoryId = (int)$categoryId;
        
        // Check if category exists
        $checkSql = "SELECT id FROM categories WHERE id = ?";
        $checkStmt = $conn->prepare($checkSql);
        
        if (!$checkStmt) {
            throw new Exception("Database prepare failed: " . $conn->error);
        }
        
        $checkStmt->bind_param("i", $categoryId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Category not found");
        }
        
        $checkStmt->close();
        
        // Delete the category
        $deleteSql = "DELETE FROM categories WHERE id = ?";
        $deleteStmt = $conn->prepare($deleteSql);
        
        if (!$deleteStmt) {
            throw new Exception("Database prepare failed: " . $conn->error);
        }
        
        $deleteStmt->bind_param("i", $categoryId);
        
        if ($deleteStmt->execute()) {
            if ($deleteStmt->affected_rows > 0) {
                $response['success'] = true;
                $response['message'] = 'Category deleted successfully';
            } else {
                throw new Exception("No category was deleted");
            }
        } else {
            throw new Exception("Failed to delete category: " . $conn->error);
        }
        
        $deleteStmt->close();
        
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
        error_log("Delete Category Error: " . $e->getMessage());
    }
    
    return $response;
}
?>