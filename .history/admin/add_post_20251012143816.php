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
    // Predefined categories
    $predefined_categories = [
        ['id' => 1, 'name' => 'News'],
        ['id' => 2, 'name' => 'Circulars'],
        ['id' => 3, 'name' => 'Events'],
        ['id' => 4, 'name' => 'Announcements'],
        ['id' => 5, 'name' => 'Holidays']
    ];
    
    // Check if categories table is empty, if so, insert predefined categories
    $sql_check = "SELECT COUNT(*) as count FROM categories";
    $result_check = mysqli_query($conn, $sql_check);
    $row_check = mysqli_fetch_assoc($result_check);
    
    if ($row_check['count'] == 0) {
        foreach ($predefined_categories as $category) {
            $name = mysqli_real_escape_string($conn, $category['name']);
            $sql_insert = "INSERT INTO categories (id, name, status, created_at) 
                          VALUES ({$category['id']}, '$name', 'active', NOW())";
            mysqli_query($conn, $sql_insert);
        }
    }
    
    // Fetch active categories
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
    $postContent = mysqli_real_escape_string($conn, $_POST['postContent']);
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
    <title>Create New Post</title>
    <link rel="icon" href="../images/logo.jpg" type="image/png">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- jQuery and jQuery Validation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
    <!-- Quill Editor -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <style>
        .sidebar {
            transition: all 0.3s ease;
        }
        .sidebar:hover {
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        .btn-nav {
            transition: all 0.2s ease;
        }
        .btn-nav:hover {
            transform: translateX(5px);
        }
        .main-content {
            min-height: calc(100vh - 64px);
        }
        .logout-btn svg {
            transition: transform 0.2s ease;
        }
        .logout-btn:hover svg {
            transform: translateX(4px);
        }
        .error {
            color: #dc2626;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        input.error, textarea.error, select.error {
            border-color: #dc2626 !important;
            background-color: #fef2f2;
        }
        input.valid, textarea.valid, select.valid {
            border-color: #10b981;
        }
        #editor-container {
            height: 300px;
        }
        .ql-toolbar.ql-snow {
            border: 1px solid #d1d5db;
            border-bottom: none;
            border-radius: 0.375rem 0.375rem 0 0;
            background: #f8fafc;
        }
        .ql-container.ql-snow {
            border: 1px solid #d1d5db;
            border-top: none;
            border-radius: 0 0 0.375rem 0.375rem;
        }
        .file-upload-label {
            border: 2px dashed #d1d5db;
            border-radius: 0.375rem;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .file-upload-label:hover {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        .file-preview {
            margin-top: 0.5rem;
            padding: 0.5rem;
            background: #f0fdf4;
            border-radius: 0.375rem;
            color: #166534;
            display: none;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="sidebar w-64 bg-white shadow-lg p-6 flex flex-col justify-between">
            <div>
                <div class="mb-8">
                    <img src="../images/logo.jpg" alt="Logo" class="w-16 mx-auto">
                    <h2 class="text-xl font-bold text-center text-gray-800 mt-2">Category Management</h2>
                </div>
                <nav class="space-y-4">
                    <a href="create_category.php" class="btn-nav block w-full text-left py-3 px-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        Create Category
                    </a>
                    <a href="list_categories.php" class="btn-nav block w-full text-left py-3 px-4 bg-teal-600 text-white rounded-lg hover:bg-teal-700">
                        List Categories
                    </a>
                    <a href="add_post.php" class="btn-nav block w-full text-left py-3 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        Add New Post
                    </a>
                    <a href="list_posts.php" class="btn-nav block w-full text-left py-3 px-4 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        List Posts
                    </a>
                    <a href="add_telephone_directory.php" class="btn-nav block w-full text-left py-3 px-4 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        Add Telephone Directory
                    </a>
                    <a href="list_telephone_directory.php" class="btn-nav block w-full text-left py-3 px-4 bg-teal-600 text-white rounded-lg hover:bg-teal-700">
                        List Telephone Directory
                    </a>
                    <a href="dashboard.php" class="btn-nav block w-full text-left py-3 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        Admin Dashboard
                    </a>
                </nav>
            </div>
            <div class="mt-auto">
                <a href="?logout=true" class="logout-btn flex items-center justify-center py-3 px-4 bg-red-600 text-white rounded-lg hover:bg-red-700" onclick="return confirm('Are you sure you want to logout?')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mr-2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content flex-1 p-8">
            <div class="max-w-4xl mx-auto">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Create New Post</h1>

                <?php if ($successMessage): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded"><?php echo htmlspecialchars($successMessage); ?></div>
                <?php endif; ?>

                <?php if ($errorMessage): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded"><?php echo $errorMessage; ?></div>
                <?php endif; ?>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <form id="postForm" method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="status" id="postStatus" value="published">
                        <input type="hidden" name="postContent" id="postContent">
                        <div class="mb-6">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">Basic Information</h2>
                            <div class="mb-4">
                                <label for="postTitle" class="block text-gray-700 font-semibold mb-2">
                                    Post Title <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="postTitle" name="postTitle" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Enter an engaging title...">
                            </div>
                            <div class="flex space-x-4 mb-4">
                                <div class="flex-1">
                                    <label for="postCategory" class="block text-gray-700 font-semibold mb-2">
                                        Category <span class="text-red-500">*</span>
                                    </label>
                                    <select id="postCategory" name="postCategory" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="flex-1">
                                    <label for="postAuthor" class="block text-gray-700 font-semibold mb-2">
                                        Author <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="postAuthor" name="postAuthor" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Author name">
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="postExcerpt" class="block text-gray-700 font-semibold mb-2">Excerpt</label>
                                <textarea id="postExcerpt" name="postExcerpt" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Write a compelling summary..."></textarea>
                            </div>
                        </div>
                        <div class="mb-6">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">Content</h2>
                            <div class="mb-4">
                                <label for="editor-container" class="block text-gray-700 font-semibold mb-2">
                                    Post Content <span class="text-red-500">*</span>
                                </label>
                                <div id="editor-container"></div>
                            </div>
                        </div>
                        <div class="mb-6">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">Publishing Options</h2>
                            <div class="mb-4">
                                <label for="publishDate" class="block text-gray-700 font-semibold mb-2">Publish Date & Time</label>
                                <input type="datetime-local" id="publishDate" name="publishDate" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div class="mb-4">
                                <label for="featuredImage" class="block text-gray-700 font-semibold mb-2">Featured Image</label>
                                <div class="file-upload">
                                    <input type="file" id="featuredImage" name="featuredImage" accept="image/*" class="hidden">
                                    <div class="file-upload-label">
                                        <span>Click to upload or drag and drop<br><small>Recommended: 1200x630px (JPG, PNG, WebP) - Max 5MB</small></span>
                                    </div>
                                </div>
                                <div class="file-preview" id="filePreview"></div>
                            </div>
                        </div>
                        <div class="flex space-x-4 justify-center">
                            <button type="button" class="py-3 px-6 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700" onclick="saveDraft()">Save as Draft</button>
                            <button type="submit" class="py-3 px-6 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Publish Post</button>
                            <button type="button" class="py-3 px-6 bg-gray-500 text-white rounded-lg hover:bg-gray-600" onclick="goBack()">Back to Categories</button>
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
                errorElement: "label",
                errorClass: "error",
                validClass: "valid",
                errorPlacement: function(error, element) {
                    error.insertAfter(element);
                },
                highlight: function(element) {
                    $(element).addClass('error').removeClass('valid');
                },
                unhighlight: function(element) {
                    $(element).removeClass('error').addClass('valid');
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

            // Auto-hide messages
            setTimeout(() => {
                $('.bg-green-100, .bg-red-100').fadeOut();
            }, 5000);
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

        // Drag and drop
        const fileUploadLabel = document.querySelector('.file-upload-label');
        ['dragover', 'dragenter'].forEach(eventName => {
            fileUploadLabel.addEventListener(eventName, function(e) {
                e.preventDefault();
                this.classList.add('border-indigo-500', 'bg-blue-50');
            });
        });
        ['dragleave', 'dragend'].forEach(eventName => {
            fileUploadLabel.addEventListener(eventName, function(e) {
                e.preventDefault();
                this.classList.remove('border-indigo-500', 'bg-blue-50');
            });
        });
        fileUploadLabel.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('border-indigo-500', 'bg-blue-50');
            const files = e.dataTransfer.files;
            if (files.length > 0 && files[0].type.startsWith('image/')) {
                const fileInput = document.getElementById('featuredImage');
                fileInput.files = files;
                fileInput.dispatchEvent(new Event('change', { bubbles: true }));
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
                window.location.href = 'create_category.php';
            }
        }
    </script>
</body>
</html>  