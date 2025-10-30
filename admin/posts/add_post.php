<?php
include dirname(__DIR__) . '/../db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$successMessage = "";
$errorMessage = "";

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../../login.php");
    exit();
}

// Fetch active categories for dropdown
function getActiveCategories() {
    global $conn;
    $categories = [];
    
    // Check if posts table exists, if not create it
    $table_check = "SHOW TABLES LIKE 'posts'";
    $table_result = mysqli_query($conn, $table_check);
    if (mysqli_num_rows($table_result) == 0) {
        // Create posts table
        $create_table = "CREATE TABLE posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            category_id INT NOT NULL,
            author VARCHAR(100) NOT NULL,
            excerpt TEXT,
            content LONGTEXT NOT NULL,
            featured_image VARCHAR(255),
            publish_date DATETIME,
            status ENUM('published', 'draft') DEFAULT 'published',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        mysqli_query($conn, $create_table);
    }
    
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
    if ($result_check) {
        $row_check = mysqli_fetch_assoc($result_check);
        
        if ($row_check['count'] == 0) {
            foreach ($predefined_categories as $category) {
                $name = mysqli_real_escape_string($conn, $category['name']);
                $sql_insert = "INSERT INTO categories (id, name, status, created_at) 
                              VALUES ({$category['id']}, '$name', 'active', NOW())";
                mysqli_query($conn, $sql_insert);
            }
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
    
    // Validation
    if (empty($postTitle)) $errors[] = "Post title is required";
    if ($postCategory <= 0) $errors[] = "Please select a valid category";
    if (empty($postAuthor)) $errors[] = "Author name is required";
    if (empty($postContent) || $postContent === '<p><br></p>') $errors[] = "Post content is required";
    
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
        // CORRECTED SQL QUERY - Fixed column names
        $sql = "INSERT INTO posts (title, category_id, author, excerpt, content, featured_image, publish_date, status, created_at) 
                VALUES ('$postTitle', $postCategory, '$postAuthor', '$postExcerpt', '$postContent', " . 
                ($featuredImage ? "'$featuredImage'" : "NULL") . ", '$publishDate', '$status', NOW())";
        
        if (mysqli_query($conn, $sql)) {
            $_SESSION['success'] = $status === 'draft' ? "Draft saved successfully!" : "Post published successfully!";
            header("Location: add_post.php");
            exit();
        } else {
            // Detailed error information
            $errorMessage = "Database error: " . mysqli_error($conn);
            // Log the actual SQL query for debugging
            error_log("SQL Error: " . $sql);
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
    <link rel="icon" href="../../images/logo.jpg" type="image/png">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
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
        }
        .preview-image {
            max-width: 200px;
            max-height: 150px;
            margin-top: 10px;
            border-radius: 0.375rem;
        }
        .char-counter {
            font-size: 0.75rem;
            color: #6b7280;
            text-align: right;
            margin-top: 0.25rem;
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
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                        <?php echo $errorMessage; ?>
                        <?php if (strpos($errorMessage, 'Unknown column') !== false): ?>
                            <div class="mt-2">
                                <p class="text-sm">Database table structure issue detected.</p>
                                <button onclick="createPostsTable()" class="mt-2 bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                                    Fix Database Table
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="bg-white p-6 rounded-lg shadow-md">
                    <form id="postForm" method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="status" id="postStatus" value="published">
                        <input type="hidden" name="postContent" id="postContent">
                        
                        <!-- Basic Information -->
                        <div class="mb-6">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">Basic Information</h2>
                            
                            <div class="mb-4">
                                <label for="postTitle" class="block text-gray-700 font-semibold mb-2">
                                    Post Title <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="postTitle" name="postTitle" 
                                       class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" 
                                       placeholder="Enter post title" 
                                       value="<?php echo isset($_POST['postTitle']) ? htmlspecialchars($_POST['postTitle']) : ''; ?>"
                                       maxlength="200"
                                       required>
                                <div class="char-counter" id="titleCounter">0/200 characters</div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="postCategory" class="block text-gray-700 font-semibold mb-2">
                                        Category <span class="text-red-500">*</span>
                                    </label>
                                    <select id="postCategory" name="postCategory" 
                                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                            required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" 
                                                <?php echo (isset($_POST['postCategory']) && $_POST['postCategory'] == $category['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="postAuthor" class="block text-gray-700 font-semibold mb-2">
                                        Author <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="postAuthor" name="postAuthor" 
                                           class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" 
                                           placeholder="Author name"
                                           value="<?php echo isset($_POST['postAuthor']) ? htmlspecialchars($_POST['postAuthor']) : ''; ?>"
                                           required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="postExcerpt" class="block text-gray-700 font-semibold mb-2">Excerpt</label>
                                <textarea id="postExcerpt" name="postExcerpt" 
                                          class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" 
                                          placeholder="Brief description of the post"
                                          rows="3"
                                          maxlength="500"><?php echo isset($_POST['postExcerpt']) ? htmlspecialchars($_POST['postExcerpt']) : ''; ?></textarea>
                                <div class="char-counter" id="excerptCounter">0/500 characters</div>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="mb-6">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">Content</h2>
                            <div class="mb-4">
                                <label class="block text-gray-700 font-semibold mb-2">
                                    Post Content <span class="text-red-500">*</span>
                                </label>
                                <div id="editor-container"><?php echo isset($_POST['postContent']) ? $_POST['postContent'] : ''; ?></div>
                            </div>
                        </div>

                        <!-- Publishing Options -->
                        <div class="mb-6">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">Publishing Options</h2>
                            
                            <div class="mb-4">
                                <label for="publishDate" class="block text-gray-700 font-semibold mb-2">Publish Date & Time</label>
                                <input type="datetime-local" id="publishDate" name="publishDate" 
                                       class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" 
                                       value="<?php echo isset($_POST['publishDate']) ? $_POST['publishDate'] : date('Y-m-d\TH:i'); ?>">
                            </div>
                            
                            <div class="mb-4">
                                <label for="featuredImage" class="block text-gray-700 font-semibold mb-2">Featured Image</label>
                                <input type="file" id="featuredImage" name="featuredImage" accept="image/*" class="hidden">
                                <label for="featuredImage" class="file-upload-label block cursor-pointer">
                                    <div class="text-center">
                                        <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                        <p class="text-gray-600 font-medium">Click to upload or drag and drop</p>
                                        <p class="text-gray-500 text-sm mt-1">JPG, PNG, WebP, GIF - Max 5MB</p>
                                    </div>
                                </label>
                                <div class="file-preview hidden" id="filePreview"></div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col sm:flex-row gap-4 justify-center pt-6 border-t border-gray-200">
                            <button type="button" class="py-3 px-6 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors font-semibold" onclick="saveDraft()">
                                <i class="fas fa-save mr-2"></i>Save as Draft
                            </button>
                            <button type="submit" class="py-3 px-6 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors font-semibold">
                                <i class="fas fa-paper-plane mr-2"></i>Publish Post
                            </button>
                            <a href="dashboard.php" class="py-3 px-6 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors font-semibold text-center">
                                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                            </a>
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

        // Set initial content if there was a form submission error
        <?php if (isset($_POST['postContent'])): ?>
            quill.root.innerHTML = `<?php echo addslashes($_POST['postContent']); ?>`;
        <?php endif; ?>

        // Character counters
        function updateCharacterCount(element, counterId, maxLength) {
            const count = element.value.length;
            const counter = document.getElementById(counterId);
            counter.textContent = `${count}/${maxLength} characters`;
            if (count > maxLength) {
                counter.classList.add('text-red-600');
            } else {
                counter.classList.remove('text-red-600');
            }
        }

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Set default publish date
            if (!document.getElementById('publishDate').value) {
                document.getElementById('publishDate').value = new Date().toISOString().slice(0, 16);
            }

            // Initialize character counters
            const titleInput = document.getElementById('postTitle');
            const excerptInput = document.getElementById('postExcerpt');
            
            updateCharacterCount(titleInput, 'titleCounter', 200);
            updateCharacterCount(excerptInput, 'excerptCounter', 500);
            
            titleInput.addEventListener('input', function() {
                updateCharacterCount(this, 'titleCounter', 200);
            });
            
            excerptInput.addEventListener('input', function() {
                updateCharacterCount(this, 'excerptCounter', 500);
            });

            // File upload handling
            document.getElementById('featuredImage').addEventListener('change', function(e) {
                const file = e.target.files[0];
                const preview = document.getElementById('filePreview');
                
                if (file) {
                    // Validate file
                    if (file.size > 5 * 1024 * 1024) {
                        alert('File size must be less than 5MB');
                        this.value = '';
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.innerHTML = `
                            <div class="flex items-center justify-between">
                                <div>
                                    <strong>Selected:</strong> ${file.name}<br>
                                    <small>Size: ${(file.size / 1024 / 1024).toFixed(2)} MB</small>
                                    <img src="${e.target.result}" class="preview-image" alt="Preview">
                                </div>
                                <button type="button" onclick="clearFilePreview()" class="text-red-500 hover:text-red-700">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        `;
                        preview.classList.remove('hidden');
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Auto-hide messages
            setTimeout(() => {
                document.querySelectorAll('.bg-green-100, .bg-red-100').forEach(el => {
                    el.style.display = 'none';
                });
            }, 5000);
        });

        // Drag and drop for file upload
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
            if (files.length > 0) {
                document.getElementById('featuredImage').files = files;
                document.getElementById('featuredImage').dispatchEvent(new Event('change'));
            }
        });

        function saveDraft() {
            document.getElementById('postStatus').value = 'draft';
            submitForm();
        }

        function submitForm() {
            const content = quill.root.innerHTML;
            if (content === '<p><br></p>' || content.trim() === '') {
                alert('Please enter post content');
                quill.focus();
                return false;
            }
            
            // Basic validation
            const title = document.getElementById('postTitle').value.trim();
            const category = document.getElementById('postCategory').value;
            const author = document.getElementById('postAuthor').value.trim();
            
            if (!title) {
                alert('Please enter a post title');
                document.getElementById('postTitle').focus();
                return false;
            }
            
            if (!category) {
                alert('Please select a category');
                document.getElementById('postCategory').focus();
                return false;
            }
            
            if (!author) {
                alert('Please enter author name');
                document.getElementById('postAuthor').focus();
                return false;
            }
            
            document.getElementById('postContent').value = content;
            document.getElementById('postForm').submit();
            return true;
        }

        function clearFilePreview() {
            document.getElementById('featuredImage').value = '';
            document.getElementById('filePreview').classList.add('hidden');
            document.getElementById('filePreview').innerHTML = '';
        }

        function createPostsTable() {
            if (confirm('This will create the posts table. Continue?')) {
                fetch('create_posts_table.php')
                    .then(response => response.text())
                    .then(data => {
                        alert(data);
                        location.reload();
                    })
                    .catch(error => {
                        alert('Error: ' + error);
                    });
            }
        }

        // Form submission
        document.getElementById('postForm').addEventListener('submit', function(e) {
            e.preventDefault();
            submitForm();
        });
    </script>
</body>
</html>