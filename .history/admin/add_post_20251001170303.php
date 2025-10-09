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
    
    if (empty($postTitle)) {
        $errors[] = "Post title is required";
    }
    
    if ($postCategory <= 0) {
        $errors[] = "Please select a valid category";
    }
    
    if (empty($postAuthor)) {
        $errors[] = "Author name is required";
    }
    
    if (empty($postContent)) {
        $errors[] = "Post content is required";
    }
    
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
            $uploadDir = '../uploads/posts/';
            
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
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
            if ($status === 'draft') {
                $_SESSION['success'] = "Draft saved successfully!";
            } else {
                $_SESSION['success'] = "Post published successfully!";
            }
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
            color: #334155;
            line-height: 1.6;
        }

        .main-container {
            min-height: 100vh;
            width: 100%;
            padding: 0;
            display: flex;
            flex-direction: column;
        }

        .header-section {
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid #e2e8f0;
            padding: 30px 40px;
            margin-bottom: 30px;
            position: relative;
        }

        .logout-container {
            position: absolute;
            top: 20px;
            right: 20px;
        }

        .logout-btn {
            background: #ef4444;
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
            background: #dc2626;
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
            width: 200px;
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
            font-size: 2.5em;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.025em;
        }

        .page-title p {
            color: #64748b;
            font-size: 1.1em;
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
            padding: 50px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            width: 100%;
            max-width: 1000px;
            position: relative;
            transition: box-shadow 0.3s ease;
        }

        .form-container:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
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
            color: #374151;
            font-weight: 600;
            margin-bottom: 12px;
            font-size: 1.1em;
        }

        .required-indicator {
            color: #dc2626;
            margin-left: 3px;
            font-weight: bold;
        }

        input, textarea, select {
            width: 100%;
            padding: 16px 18px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 400;
            transition: all 0.3s ease;
            background: #ffffff;
            color: #374151;
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

        .content-textarea {
            min-height: 250px;
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
            padding: 50px 25px;
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            background: #f9fafb;
            transition: all 0.3s ease;
            text-align: center;
            color: #6b7280;
            font-size: 1rem;
            font-weight: 500;
        }

        .file-upload:hover .file-upload-label {
            border-color: #3b82f6;
            background: #f0f9ff;
            color: #3b82f6;
        }

        .file-preview {
            margin-top: 15px;
            padding: 15px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 12px;
            color: #166534;
            font-size: 1rem;
            font-weight: 500;
            display: none;
            text-align: center;
        }

        .btn-group {
            display: flex;
            gap: 20px;
            margin-top: 30px;
            justify-content: center;
        }

        button {
            padding: 16px 32px;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 160px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .btn-secondary {
            background: #ffffff;
            color: #6b7280;
            border: 1px solid #d1d5db;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .btn-secondary:hover {
            background: #f9fafb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .btn-draft {
            background: #f59e0b;
            color: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .btn-draft:hover {
            background: #d97706;
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
            border-radius: 12px;
            text-align: center;
            font-weight: 600;
            font-size: 1rem;
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

        /* jQuery Validation Styles */
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

        @media (max-width: 768px) {
            .header-section {
                padding: 20px;
                margin-bottom: 20px;
            }

            .logout-container {
                position: static;
                text-align: center;
                margin-top: 15px;
                margin-bottom: 10px;
            }

            .logout-btn {
                display: inline-flex;
                padding: 8px 16px;
                font-size: 0.85rem;
            }

            .content-section {
                padding: 0 20px 20px;
            }

            .form-container {
                padding: 30px 25px;
            }
            
            .logo-container img {
                width: 160px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            .btn-group {
                flex-direction: column;
                align-items: center;
            }

            button {
                width: 100%;
                max-width: 300px;
            }
            
            .success-message, .error-message, .draft-message {
                width: calc(100% - 20px);
                padding: 12px 16px;
                font-size: 0.9rem;
            }

            .page-title h1 {
                font-size: 2rem;
            }

            .file-upload-label {
                padding: 40px 20px;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .logo-container img {
                width: 140px;
            }

            .page-title h1 {
                font-size: 1.75rem;
            }

            input, textarea, select {
                padding: 14px 16px;
                font-size: 0.9rem;
            }

            .form-container {
                padding: 25px 20px;
            }

            button {
                padding: 14px 24px;
                font-size: 0.9rem;
            }

            .logout-btn {
                padding: 6px 12px;
                font-size: 0.8rem;
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
            background: #cbd5e1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
    <!-- CKEditor CDN -->
    <script src="https://cdn.ckeditor.com/ckeditor5/41.0.0/classic/ckeditor.js"></script>
</head>
<body>
    <div class="main-container">
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
                        <label for="postContent">Post Content</label>
                        <textarea id="postContent" name="postContent" class="content-textarea" placeholder="Write your full post content here..." required></textarea>
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
                        <button type="button" class="btn-draft" onclick="saveDraft()">
                            Save as Draft
                        </button>
                        <button type="submit" class="btn-primary">
                            Publish Post
                        </button>
                        <button type="button" class="btn-secondary" onclick="goBack()">
                            Back to Categories
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Set default publish date to now
            document.getElementById('publishDate').value = new Date().toISOString().slice(0, 16);

            // Initialize form validation
            $("#postForm").validate({
                rules: {
                    postTitle: {
                        required: true,
                        minlength: 3,
                        maxlength: 200
                    },
                    postCategory: {
                        required: true
                    },
                    postAuthor: {
                        required: true,
                        minlength: 2,
                        maxlength: 100
                    },
                    postContent: {
                        required: true,
                        minlength: 10
                    },
                    postExcerpt: {
                        maxlength: 500
                    }
                },
                messages: {
                    postTitle: {
                        required: "This field is required.",
                        minlength: "Title must be at least 3 characters long.",
                        maxlength: "Title cannot exceed 200 characters."
                    },
                    postCategory: {
                        required: "This field is required."
                    },
                    postAuthor: {
                        required: "This field is required.",
                        minlength: "Author name must be at least 2 characters long.",
                        maxlength: "Author name cannot exceed 100 characters."
                    },
                    postContent: {
                        required: "This field is required.",
                        minlength: "Content must be at least 10 characters long."
                    },
                    postExcerpt: {
                        maxlength: "Excerpt cannot exceed 500 characters."
                    }
                },
                errorElement: "label",
                errorClass: "error",
                validClass: "valid",
                errorPlacement: function(error, element) {
                    error.insertAfter(element);
                },
                submitHandler: function(form) {
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
            if ($("#postForm").valid()) {
                document.getElementById('postStatus').value = 'draft';
                document.getElementById('postForm').submit();
            }
        }

        function goBack() {
            if (confirm('Are you sure you want to leave? Any unsaved changes will be lost.')) {
                window.location.href = 'add_category_type.php';
            }
        }

        // Add drag and drop functionality
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

        // Auto-hide messages after 5 seconds
        setTimeout(() => {
            const messages = document.querySelectorAll('.success-message, .error-message, .draft-message');
            messages.forEach(msg => {
                msg.classList.remove('show');
            });
        }, 5000);
    </script>
</body>
</html>