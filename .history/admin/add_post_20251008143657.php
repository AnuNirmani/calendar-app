<?php
include '../db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$successMessage = "";
$errorMessage = "";

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Fetch active categories for dropdown
function getActiveCategories() {
    global $conn;
    $categories = [];
    $sql = "SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $categories[] = $row;
        }
    }
    return $categories;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postTitle = mysqli_real_escape_string($conn, trim($_POST['postTitle']));
    $postCategory = intval($_POST['postCategory']);
    $postAuthor = mysqli_real_escape_string($conn, trim($_POST['postAuthor']));
    $postExcerpt = mysqli_real_escape_string($conn, trim($_POST['postExcerpt']));
    $postContent = mysqli_real_escape_string($conn, trim($_POST['postContent']));
    $publishDate = mysqli_real_escape_string($conn, $_POST['publishDate']);
    $status = isset($_POST['status']) ? mysqli_real_escape_string($conn, $_POST['status']) : 'published';
    
    $errors = [];
    
    if (empty($postTitle)) $errors[] = "Post title is required";
    if ($postCategory <= 0) $errors[] = "Please select a valid category";
    if (empty($postAuthor)) $errors[] = "Author name is required";
    if (empty($postContent)) $errors[] = "Post content is required";
    
    // Handle file upload
    $featuredImage = null;
    if (isset($_FILES['featuredImage']) && $_FILES['featuredImage']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $maxSize = 5 * 1024 * 1024;
        $fileType = $_FILES['featuredImage']['type'];
        $fileSize = $_FILES['featuredImage']['size'];
        
        if (!in_array($fileType, $allowedTypes)) {
            $errors[] = "Invalid file type. Only JPG, PNG, WebP, and GIF are allowed";
        }
        if ($fileSize > $maxSize) {
            $errors[] = "File size exceeds 5MB limit";
        }
        
        if (empty($errors)) {
            $uploadDir = '../Uploads/posts/';
            if (!file_exists($uploadDir)) mkdir($uploadDir, 0755, true);
            $fileExtension = pathinfo($_FILES['featuredImage']['name'], PATHINFO_EXTENSION);
            $fileName = 'post_' . time() . '_' . uniqid() . '.' . $fileExtension;
            $uploadPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['featuredImage']['tmp_name'], $uploadPath)) {
                $featuredImage = mysqli_real_escape_string($conn, $fileName);
            } else {
                $errors[] = "Failed to upload image";
            }
        }
    }
    
    if (empty($errors)) {
        $sql = "INSERT INTO posts (title, category_id, author, excerpt, content, featured_image, publish_date, status, created_at) 
                VALUES ('$postTitle', $postCategory, '$postAuthor', '$postExcerpt', '$postContent', " . 
                ($featuredImage ? "'$featuredImage'" : "NULL") . ", '$publishDate', '$status', NOW())";
        
        if (mysqli_query($conn, $sql)) {
            $_SESSION['success'] = $status === 'draft' ? "Draft saved successfully!" : "Post published successfully!";
            header("Location: add_post.php");
            exit();
        } else {
            $errorMessage = "Failed to save post: " . mysqli_error($conn);
        }
    } else {
        $errorMessage = implode("<br>", $errors);
    }
}

// Get messages from session
if (isset($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    $errorMessage = $_SESSION['error'];
    unset($_SESSION['error']);
}

$categories = getActiveCategories();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../images/logo.jpg" type="image/png">
    <title>Add New Post</title>
    
    <!-- jQuery and jQuery Validation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
    
    <!-- Quill Editor -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 50%, #f1f5f9 100%);
            min-height: 100vh;
            color: #1e293b;
            line-height: 1.6;
        }

        .main-container {
            display: flex;
            min-height: 100vh;
            width: 100%;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1e293b 0%, #334155 100%);
            color: #f1f5f9;
            padding: 20px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.15);
            display: flex;
            flex-direction: column;
            gap: 12px;
            transition: transform 0.3s ease;
            position: fixed;
            height: 100%;
            z-index: 1000;
        }

        .sidebar.hidden {
            transform: translateX(-100%);
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .sidebar-logo {
            width: 40px;
            height: 40px;
            border-radius: 8px;
        }

        .sidebar-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #ffffff;
        }

        .sidebar button {
            padding: 12px 16px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.1);
            color: #e2e8f0;
            text-align: left;
        }

        .sidebar button:hover, .sidebar button.active {
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
            color: #ffffff;
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .sidebar button svg {
            stroke: #e2e8f0;
            transition: stroke 0.3s ease;
        }

        .sidebar button:hover svg, .sidebar button.active svg {
            stroke: #ffffff;
        }

        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1100;
            background: #3b82f6;
            color: white;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .sidebar-toggle:hover {
            background: #2563eb;
        }

        .content-wrapper {
            flex: 1;
            margin-left: 280px;
            display: flex;
            flex-direction: column;
        }

        .header-section {
            background: #ffffff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid #e2e8f0;
            padding: 30px 40px;
            position: relative;
        }

        .logout-container {
            position: absolute;
            top: 20px;
            right: 20px;
        }

        .logout-btn {
            background: linear-gradient(90deg, #ef4444, #f87171);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 0.9rem;
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.2);
        }

        .logout-btn:hover {
            background: linear-gradient(90deg, #dc2626, #ef4444);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .logo-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 25px;
        }

        .logo-container img {
            width: 180px;
            height: auto;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .logo-container img:hover {
            transform: scale(1.02);
        }

        .page-title {
            text-align: center;
        }

        .page-title h1 {
            color: #1e293b;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.025em;
        }

        .page-title p {
            color: #64748b;
            font-size: 1.1rem;
            font-weight: 400;
        }

        .content-section {
            flex: 1;
            padding: 0 40px 40px;
            display: flex;
            justify-content: center;
        }

        .form-container {
            background: #ffffff;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            width: 100%;
            max-width: 1000px;
            transition: box-shadow 0.3s ease;
        }

        .form-container:hover {
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
        }

        .form-row {
            display: flex;
            gap: 25px;
            margin-bottom: 30px;
        }

        .form-group {
            flex: 1;
            margin-bottom: 20px;
        }

        .form-group.full-width {
            flex: 1 1 100%;
        }

        label {
            display: block;
            color: #1e293b;
            font-weight: 600;
            margin-bottom: 12px;
            font-size: 1.1rem;
        }

        .required-indicator {
            color: #dc2626;
            margin-left: 3px;
            font-weight: bold;
        }

        input, textarea, select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 400;
            transition: all 0.3s ease;
            background: #f9fafb;
            color: #1e293b;
            font-family: inherit;
        }

        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        input::placeholder, textarea::placeholder {
            color: #9ca3af;
        }

        textarea {
            resize: vertical;
            min-height: 140px;
        }

        #editor-container {
            height: 350px;
            margin-bottom: 20px;
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        #editor-container:focus-within {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .ql-toolbar.ql-snow {
            border: none;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 12px 16px;
        }

        .ql-container.ql-snow {
            border: none;
            font-family: inherit;
            font-size: 1rem;
        }

        .ql-editor {
            padding: 20px;
            min-height: 250px;
            line-height: 1.6;
            background: #ffffff;
        }

        .ql-editor.ql-blank::before {
            color: #9ca3af;
            font-style: normal;
            font-family: inherit;
        }

        .file-upload {
            position: relative;
            display: inline-block;
            cursor: pointer;
            width: 100%;
        }

        .file-upload input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-upload-label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            border: 2px dashed #d1d5db;
            border-radius: 10px;
            background: #f9fafb;
            transition: all 0.3s ease;
            text-align: center;
            color: #6b7280;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .file-upload:hover .file-upload-label {
            border-color: #3b82f6;
            background: #eff6ff;
            color: #3b82f6;
        }

        .file-preview {
            margin-top: 15px;
            padding: 12px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            color: #166534;
            font-size: 0.95rem;
            font-weight: 500;
            text-align: center;
            display: none;
        }

        .btn-group {
            display: flex;
            gap: 20px;
            margin-top: 30px;
            justify-content: center;
        }

        button {
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 150px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
            color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn-primary:hover {
            background: linear-gradient(90deg, #2563eb, #3b82f6);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .btn-secondary {
            background: #ffffff;
            color: #6b7280;
            border: 1px solid #d1d5db;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn-secondary:hover {
            background: #f9fafb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .btn-draft {
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
            color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn-draft:hover {
            background: linear-gradient(90deg, #d97706, #f59e0b);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }

        .success-message, .error-message, .draft-message {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(-30px);
            width: calc(100% - 40px);
            max-width: 500px;
            background: #ffffff;
            padding: 16px 20px;
            border-radius: 10px;
            text-align: center;
            font-weight: 600;
            font-size: 0.95rem;
            opacity: 0;
            z-index: 10000;
            transition: all 0.3s ease;
            display: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .success-message {
            color: #166534;
            border-left: 4px solid #10b981;
        }

        .draft-message {
            color: #92400e;
            border-left: 4px solid #f59e0b;
        }

        .error-message {
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }

        .success-message.show, .draft-message.show, .error-message.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
            display: block;
        }

        .section-divider {
            margin: 40px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .section-title {
            color: #1e293b;
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 25px;
            padding-bottom: 8px;
            border-bottom: 2px solid #3b82f6;
            display: inline-block;
            letter-spacing: -0.025em;
        }

        label.error {
            color: #dc2626;
            font-size: 0.875rem;
            margin-top: 5px;
            display: block;
            font-weight: 500;
        }

        input.error, textarea.error, select.error {
            border-color: #dc2626 !important;
            background-color: #fef2f2;
        }

        input.valid, textarea.valid, select.valid {
            border-color: #10b981;
        }

        .file-upload-label.drag-over {
            border-color: #3b82f6;
            background: #eff6ff;
            color: #3b82f6;
            transform: scale(1.02);
        }

        @media (max-width: 1024px) {
            .sidebar {
                width: 250px;
            }
            .content-wrapper {
                margin-left: 250px;
            }
        }

        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                transform: translateX(0);
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: center;
                padding: 10px;
                border-right: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }

            .sidebar.hidden {
                display: none;
            }

            .sidebar-header {
                display: none;
            }

            .sidebar button {
                flex: 1 1 45%;
                text-align: center;
                font-size: 0.9rem;
                padding: 10px;
            }

            .content-wrapper {
                margin-left: 0;
            }

            .sidebar-toggle {
                display: block;
            }

            .header-section {
                padding: 20px;
            }

            .form-container {
                padding: 30px 20px;
            }

            .form-row {
                flex-direction: column;
                gap: 15px;
            }

            .btn-group {
                flex-direction: column;
                align-items: center;
            }

            button {
                width: 100%;
                max-width: 300px;
            }

            .logo-container img {
                width: 160px;
            }

            .page-title h1 {
                font-size: 2rem;
            }

            .success-message, .error-message, .draft-message {
                width: calc(100% - 20px);
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .sidebar {
                flex-direction: column;
                align-items: center;
            }

            .sidebar button {
                flex: 1 1 100%;
                max-width: 300px;
            }

            .logo-container img {
                width: 140px;
            }

            .page-title h1 {
                font-size: 1.75rem;
            }

            input, textarea, select {
                padding: 12px 14px;
                font-size: 0.9rem;
            }

            .form-container {
                padding: 20px 15px;
            }

            button {
                padding: 12px 20px;
                font-size: 0.9rem;
            }

            #editor-container {
                height: 300px;
            }
        }

        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: #3b82f6;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #2563eb;
        }
    </style>
</head>
<body>
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 6h18M3 12h18M3 18h18"/>
        </svg>
    </button>

    <div class="main-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="../images/logo.jpg" alt="Logo" class="sidebar-logo">
                <span class="sidebar-title">Dashboard</span>
            </div>
            <button class="active" onclick="window.location.href='add_post.php'">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
                Create Post
            </button>
            <button onclick="window.location.href='list_posts.php'">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 7h18M3 12h18M3 17h18"/>
                </svg>
                List Posts
            </button>
            <button onclick="window.location.href='post_views.php'">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
                Post Views
            </button>
        </div>
        <div class="content-wrapper">
            <div class="header-section">
                <div class="logout-container">
                    <a href="?logout=true" class="logout-btn" onclick="return confirm('Are you sure you want to logout?')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        Logout
                    </a>
                </div>

                <div class="logo-container">
                    <img src="../images/logo.jpg" alt="Logo">
                </div>

                <div class="page-title">
                    <h1>Create New Post</h1>
                    <p>Fill in the details below to create a new blog post</p>
                </div>
            </div>

            <div class="content-section">
                <div class="form-container">
                    <?php if ($successMessage): ?>
                        <div class="success-message show" id="successMessage">
                            <?= htmlspecialchars($successMessage) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($errorMessage): ?>
                        <div class="error-message show" id="errorMessage">
                            <?= $errorMessage ?>
                        </div>
                    <?php endif; ?>

                    <form id="postForm" method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="status" id="postStatus" value="published">
                        <input type="hidden" name="postContent" id="postContent">
                        
                        <div class="section-title">Basic Information</div>
                        
                        <div class="form-group full-width">
                            <label for="postTitle">Post Title <span class="required-indicator">*</span></label>
                            <input type="text" id="postTitle" name="postTitle" placeholder="Enter an engaging title that captures attention...">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="postCategory">Category <span class="required-indicator">*</span></label>
                                <select id="postCategory" name="postCategory">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="postAuthor">Author <span class="required-indicator">*</span></label>
                                <input type="text" id="postAuthor" name="postAuthor" placeholder="Author name">
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label for="postExcerpt">Excerpt</label>
                            <textarea id="postExcerpt" name="postExcerpt" placeholder="Write a compelling summary that will make readers want to read more..."></textarea>
                        </div>

                        <div class="section-divider"></div>
                        <div class="section-title">Content</div>

                        <div class="form-group full-width">
                            <label for="editor-container">Post Content <span class="required-indicator">*</span></label>
                            <div id="editor-container"></div>
                        </div>

                        <div class="section-divider"></div>
                        <div class="section-title">Publishing Options</div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="publishDate">Publish Date & Time</label>
                                <input type="datetime-local" id="publishDate" name="publishDate">
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label for="featuredImage">Featured Image</label>
                            <div class="file-upload">
                                <input type="file" id="featuredImage" name="featuredImage" accept="image/*">
                                <div class="file-upload-label">
                                    <span>Click to upload featured image or drag and drop here<br>
                                    <small style="opacity: 0.7;">Recommended size: 1200x630px (JPG, PNG, WebP) - Max 5MB</small></span>
                                </div>
                            </div>
                            <div class="file-preview" id="filePreview"></div>
                        </div>

                        <div class="btn-group">
                            <button type="button" class="btn-draft" onclick="saveDraft()">Save as Draft</button>
                            <button type="submit" class="btn-primary">Publish Post</button>
                            <button type="button" class="btn-secondary" onclick="goBack()">Back to Categories</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Quill editor
        var quill = new Quill('#editor-container', {
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'indent': '-1'}, { 'indent': '+1' }],
                    ['blockquote', 'code-block'],
                    ['link', 'image'],
                    ['clean']
                ]
            },
            placeholder: 'Write your full post content here. Share your story, insights, or information in detail...',
            theme: 'snow'
        });

        $(document).ready(function() {
            // Set default publish date to now
            document.getElementById('publishDate').value = new Date().toISOString().slice(0, 16);

            // Initialize form validation
            $("#postForm").validate({
                rules: {
                    postTitle: { required: true, minlength: 3, maxlength: 200 },
                    postCategory: { required: true },
                    postAuthor: { required: true, minlength: 2, maxlength: 100 },
                    postExcerpt: { maxlength: 500 }
                },
                messages: {
                    postTitle: {
                        required: "This field is required.",
                        minlength: "Title must be at least 3 characters long.",
                        maxlength: "Title cannot exceed 200 characters."
                    },
                    postCategory: { required: "This field is required." },
                    postAuthor: {
                        required: "This field is required.",
                        minlength: "Author name must be at least 2 characters long.",
                        maxlength: "Author name cannot exceed 100 characters."
                    },
                    postExcerpt: { maxlength: "Excerpt cannot exceed 500 characters." }
                },
                errorElement: "label",
                errorClass: "error",
                validClass: "valid",
                errorPlacement: function(error, element) {
                    error.insertAfter(element);
                },
                submitHandler: function(form) {
                    var content = quill.root.innerHTML;
                    if (content === '<p><br></p>' || content.trim() === '') {
                        alert("Post content is required. Please add some content to your post.");
                        return false;
                    }
                    document.getElementById('postContent').value = content;
                    form.submit();
                }
            });
        });

        // File upload preview
        document.getElementById('featuredImage').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('filePreview');
            if (file) {
                preview.innerHTML = `Selected: <strong>${file.name}</strong> (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }
        });

        function saveDraft() {
            var content = quill.root.innerHTML;
            if (content === '<p><br></p>' || content.trim() === '') {
                alert("Post content is required. Please add some content to your post.");
                return false;
            }
            document.getElementById('postContent').value = content;
            document.getElementById('postStatus').value = 'draft';
            document.getElementById('postForm').submit();
        }

        function goBack() {
            if (confirm('Are you sure you want to leave? Any unsaved changes will be lost.')) {
                window.location.href = 'add_category_type.php';
            }
        }

        // Drag and drop functionality
        const fileUploadLabel = document.querySelector('.file-upload-label');
        ['dragover', 'dragenter'].forEach(eventName => {
            fileUploadLabel.addEventListener(eventName, function(e) {
                e.preventDefault();
                this.classList.add('drag-over');
            });
        });

        ['dragleave', 'dragend'].forEach(eventName => {
            fileUploadLabel.addEventListener(eventName, function(e) {
                e.preventDefault();
                this.classList.remove('drag-over');
            });
        });

        fileUploadLabel.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('drag-over');
            const files = e.dataTransfer.files;
            if (files.length > 0 && files[0].type.startsWith('image/')) {
                const fileInput = document.getElementById('featuredImage');
                fileInput.files = files;
                fileInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });

        // Sidebar toggle functionality
        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('hidden');
        }

        // Auto-hide messages after 5 seconds
        setTimeout(() => {
            const messages = document.querySelectorAll('.success-message, .error-message, .draft-message');
            messages.forEach(msg => msg.classList.remove('show'));
        }, 5000);
    </script>
</body>
</html>