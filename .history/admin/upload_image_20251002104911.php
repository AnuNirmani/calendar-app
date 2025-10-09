<?php
include '../db.php';

header('Content-Type: application/json');

// Check if a file was uploaded
if (isset($_FILES['upload']) && $_FILES['upload']['error'] === UPLOAD_ERR_OK) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    $fileType = $_FILES['upload']['type'];
    $fileSize = $_FILES['upload']['size'];

    // Validate file type
    if (!in_array($fileType, $allowedTypes)) {
        echo json_encode(['error' => ['message' => 'Invalid file type. Only JPG, PNG, WebP, and GIF are allowed']]);
        exit;
    }

    // Validate file size
    if ($fileSize > $maxSize) {
        echo json_encode(['error' => ['message' => 'File size exceeds 5MB limit']]);
        exit;
    }

    // Create upload directory if it doesn't exist
    $uploadDir = '../Uploads/posts/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique filename
    $fileExtension = pathinfo($_FILES['upload']['name'], PATHINFO_EXTENSION);
    $fileName = 'post_image_' . time() . '_' . uniqid() . '.' . $fileExtension;
    $uploadPath = $uploadDir . $fileName;

    // Move the uploaded file
    if (move_uploaded_file($_FILES['upload']['tmp_name'], $uploadPath)) {
        // Return the URL to CKEditor
        // Adjust the base URL to match your site's structure
        $baseUrl = '/Uploads/posts/'; // Relative path; update to absolute if needed (e.g., 'http://example.com/Uploads/posts/')
        echo json_encode(['url' => $baseUrl . $fileName]);
    } else {
        echo json_encode(['error' => ['message' => 'Failed to upload image']]);
    }
} else {
    echo json_encode(['error' => ['message' => 'No file uploaded or upload error']]);
}
?>