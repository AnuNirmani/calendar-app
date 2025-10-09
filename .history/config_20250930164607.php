<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

// Database configuration
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'calendar_app';

try {
    // Ensure category_type table exists
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS category_type (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ";
    $conn->query($createTableQuery);

    // Use session user_id OR fallback to 1
    $user_id = $_SESSION['user_id'] ?? 1;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');

        if (empty($name)) {
            echo json_encode(["status" => "error", "message" => "Category name is required"]);
            exit;
        }

        // Insert category into database
        $stmt = $conn->prepare("INSERT INTO category_type (name, user_id) VALUES (?, ?)");
        $stmt->bind_param("si", $name, $user_id);

        if ($stmt->execute()) {
            echo json_encode([
                "status" => "success",
                "message" => "Category created successfully",
                "data" => ["id" => $stmt->insert_id, "name" => $name]
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to insert category: " . $stmt->error
            ]);
        }

        $stmt->close();
        exit;
    }

    // GET request → Fetch categories
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $result = $conn->query("SELECT * FROM category_type ORDER BY id SC");
        $categories = [];

        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }

        echo json_encode([
            "status" => "success",
            "data" => $categories
        ]);
        exit;
    }

    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
