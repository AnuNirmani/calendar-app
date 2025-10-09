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
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            color: #1e293b;
            line-height: 1.6;
        }

        .main-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #1e293b 0%, #2d3748 100%);
            color: #f8fafc;
            padding: 30px 20px;
            position: fixed;
            height: 100%;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            z-index: 1000;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header img {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            margin-right: 10px;
        }

        .sidebar-header span {
            font-size: 1.5rem;
            font-weight: 700;
            color: #f8fafc;
        }

        .sidebar nav ul {
            list-style: none;
        }

        .sidebar nav ul li {
            margin-bottom: 10px;
        }

        .sidebar nav ul li a {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: #d1d5db;
            text-decoration: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .sidebar nav ul li a:hover,
        .sidebar nav ul li a.active {
            background: #3b82f6;
            color: #ffffff;
            transform: translateX(5px);
        }

        .sidebar nav ul li a svg {
            margin-right: 10px;
        }

        .sidebar .logout {
            margin-top: auto;
            padding: 12px 16px;
            background: #ef4444;
            color: #ffffff;
            border-radius: 8px;
            text-align: center;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .sidebar .logout:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }

        /* Content Styling */
        .content-wrapper {
            flex: 1;
            margin-left: 280px;
            padding: 30px;
            background: #f8fafc;
        }

        .header-section {
            background: #ffffff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .logo-container img {
            width: 150px;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .page-title h1 {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
        }

        .page-title p {
            font-size: 1.1rem;
            color: #64748b;
        }

        .form-container {
            background: #ffffff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            max-width: 900px;
            margin: 0 auto;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 8px;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            flex: 1;
        }

        .form-group.full-width {
            flex: 1 1 100%;
        }

        label {
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
        }

        .required-indicator {
            color: #dc2626;
        }

        input, textarea, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        input:focus, textarea:focus, select:focus {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 5px rgba(59, 130, 246, 0.2);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        #editor-container {
            height: 300px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
        }

        .ql-toolbar.ql-snow {
            border: none;
            border-bottom: 1px solid #d1d5db;
            background: #f8fafc;
        }

        .ql-container.ql-snow {
            border: none;
        }

        .file-upload-label {
            padding: 20px;
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-upload-label:hover {
            border-color: #3b82f6;
            background: #eff6ff;
        }

        .file-preview {
            margin-top: 10px;
            padding: 10px;
            background: #f0fdf4;
            border-radius: 8px;
            color: #166534;
            display: none;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }

        button {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #3b82f6;
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #ffffff;
            color: #6b7280;
            border: 1px solid #d1d5db;
        }

        .btn-secondary:hover {
            background: #f9fafb;
            transform: translateY(-2px);
        }

        .btn-draft {
            background: #f59e0b;
            color: #ffffff;
        }

        .btn-draft:hover {
            background: #d97706;
            transform: translateY(-2px);
        }

        .success-message, .error-message {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 15px;
            border-radius: 8px;
            font-weight: 600;
            z-index: 1000;
            display: none;
        }

        .success-message.show, .error-message.show {
            display: block;
        }

        .success-message {
            background: #d1fae5;
            color: #166534;
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
        }

        @media (max-width: 1024px) {
            .sidebar {
                width: 60px;
                padding: 20px 10px;
            }

            .sidebar-header span,
            .sidebar nav ul li a span {
                display: none;
            }

            .sidebar nav ul li a {
                justify-content: center;
                padding: 10px;
            }

            .content-wrapper {
                margin-left: 60px;
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 250px;
                z-index: 1001;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .content-wrapper {
                margin-left: 0;
            }

            .form-row {
                flex-direction: column;
            }

            .btn-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="sidebar">
            <div class="sidebar-header">
                <img src="../images/logo.jpg" alt="Logo">
                <span>Dashboard</span>
            </div>
            <nav>
                <ul>
                    <li><a href="list_posts.php"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h18M3 12h18M3 17h18"/></svg><span>List Posts</span></a></li>
                    
                    <li><a href="add_post.php" class="active"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg><span>Create Post</span></a></li>
                </ul>
            </nav>
            <a href="?logout=true" class="logout" onclick="return confirm('Are you sure you want to logout?')">Logout</a>
        </div>
        <div class="content-wrapper">
            <div class="header-section">
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
                        <div class="success-message show"><?= htmlspecialchars($successMessage) ?></div>
                    <?php endif; ?>
                    <?php if ($errorMessage): ?>
                        <div class="error-message show"><?= $errorMessage ?></div>
                    <?php endif; ?>
                    <form id="postForm" method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="status" id="postStatus" value="published">
                        <input type="hidden" name="postContent" id="postContent">
                        <div class="section-title">Basic Information</div>
                        <div class="form-group full-width">
                            <label for="postTitle">Post Title <span class="required-indicator">*</span></label>
                            <input type="text" id="postTitle" name="postTitle" placeholder="Enter an engaging title...">
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
                            <textarea id="postExcerpt" name="postExcerpt" placeholder="Write a compelling summary..."></textarea>
                        </div>
                        <div class="section-title">Content</div>
                        <div class="form-group full-width">
                            <label for="editor-container">Post Content <span class="required-indicator">*</span></label>
                            <div id="editor-container"></div>
                        </div>
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
                                    <span>Click to upload or drag and drop<br><small>Recommended: 1200x630px (JPG, PNG, WebP) - Max 5MB</small></span>
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
                    ['link', 'image'],
                    ['clean']
                ]
            },
            placeholder: 'Write your post content here...',
            theme: 'snow'
        });

        $(document).ready(function() {
            document.getElementById('publishDate').value = new Date().toISOString().slice(0, 16);
            $("#postForm").validate({
                rules: {
                    postTitle: { required: true, minlength: 3, maxlength: 200 },
                    postCategory: { required: true },
                    postAuthor: { required: true, minlength: 2, maxlength: 100 },
                    postExcerpt: { maxlength: 500 }
                },
                messages: {
                    postTitle: {
                        required: "Title is required.",
                        minlength: "Title must be at least 3 characters.",
                        maxlength: "Title cannot exceed 200 characters."
                    },
                    postCategory: { required: "Category is required." },
                    postAuthor: {
                        required: "Author is required.",
                        minlength: "Author name must be at least 2 characters.",
                        maxlength: "Author name cannot exceed 100 characters."
                    },
                    postExcerpt: { maxlength: "Excerpt cannot exceed 500 characters." }
                },
                submitHandler: function(form) {
                    var content = quill.root.innerHTML;
                    if (content === '<p><br></p>' || content.trim() === '') {
                        alert("Post content is required.");
                        return false;
                    }
                    document.getElementById('postContent').value = content;
                    form.submit();
                }
            });
        });

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
                alert("Post content is required.");
                return false;
            }
            document.getElementById('postContent').value = content;
            document.getElementById('postStatus').value = 'draft';
            document.getElementById('postForm').submit();
        }

        function goBack() {
            if (confirm('Are you sure you want to leave? Unsaved changes will be lost.')) {
                window.location.href = 'add_category_type.php';
            }
        }

        // Drag and drop
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

        // Auto-hide messages
        setTimeout(() => {
            document.querySelectorAll('.success-message, .error-message').forEach(msg => {
                msg.classList.remove('show');
            });
        }, 5000);

        // Sidebar toggle for mobile
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>