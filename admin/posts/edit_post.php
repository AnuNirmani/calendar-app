<?php
// admin/posts/edit_post.php

// ✅ Correct paths (this file is inside: /admin/posts/)
include dirname(__DIR__) . '/../db.php';        // -> /db.php
include dirname(__DIR__) . '/../auth.php';      // -> /auth.php  ✅ FIXED

// ✅ Use your auth system
checkAuth(); // or checkAuth('admin') / checkAuth('super_admin') if you want to restrict

// Auto logout after inactivity
$timeout = 900;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout) {
    session_unset();
    session_destroy();
    header("Location: ../../login.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

$successMessage = "";
$errorMessage   = "";

// Check if post ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid post ID.";
    header("Location: list_posts.php");
    exit();
}
$post_id = (int)$_GET['id'];

// Fetch post (prepared)
$stmt = $conn->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$res = $stmt->get_result();

if (!$res || $res->num_rows === 0) {
    $_SESSION['error'] = "Post not found.";
    header("Location: list_posts.php");
    exit();
}
$post = $res->fetch_assoc();

// Fetch active categories
$categories = [];
$catRes = $conn->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC");
if ($catRes) {
    while ($row = $catRes->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postTitle    = trim($_POST['postTitle'] ?? '');
    $postCategory = (int)($_POST['postCategory'] ?? 0);
    $postAuthor   = trim($_POST['postAuthor'] ?? '');
    $postExcerpt  = trim($_POST['postExcerpt'] ?? '');
    $postContent  = $_POST['postContent'] ?? '';
    $publishDate  = $_POST['publishDate'] ?? '';
    $status       = $_POST['status'] ?? 'published';

    $errors = [];

    if ($postTitle === '') $errors[] = "Post title is required";
    if ($postCategory <= 0) $errors[] = "Please select a valid category";
    if ($postAuthor === '') $errors[] = "Author name is required";
    if (trim(strip_tags($postContent)) === '') $errors[] = "Post content is required";

    // Handle file upload
    $featuredImage = $post['featured_image'] ?? null;

    if (isset($_FILES['featuredImage']) && $_FILES['featuredImage']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $maxSize = 5 * 1024 * 1024;

        $fileTmp  = $_FILES['featuredImage']['tmp_name'];
        $fileSize = $_FILES['featuredImage']['size'];

        // Safer mime detection
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $fileType = finfo_file($finfo, $fileTmp);
        finfo_close($finfo);

        if (!in_array($fileType, $allowedTypes)) $errors[] = "Invalid file type. Only JPG, PNG, WebP, and GIF are allowed";
        if ($fileSize > $maxSize) $errors[] = "File size exceeds 5MB limit";

        if (empty($errors)) {
            // Upload dir relative to this file: /admin/Uploads/posts/
            $uploadDir = dirname(__DIR__) . '/Uploads/posts/'; // ✅ FIXED path
            if (!file_exists($uploadDir)) mkdir($uploadDir, 0755, true);

            $ext = strtolower(pathinfo($_FILES['featuredImage']['name'], PATHINFO_EXTENSION));
            $fileName = 'post_' . time() . '_' . uniqid() . '.' . $ext;
            $uploadPath = $uploadDir . $fileName;

            if (move_uploaded_file($fileTmp, $uploadPath)) {
                // Delete old image if exists
                if (!empty($featuredImage)) {
                    $oldPath = $uploadDir . $featuredImage;
                    if (file_exists($oldPath)) unlink($oldPath);
                }
                $featuredImage = $fileName;
            } else {
                $errors[] = "Failed to upload image";
            }
        }
    }

    if (empty($errors)) {
        // Update with prepared statement
        $stmt = $conn->prepare("
            UPDATE posts SET
                title = ?,
                category_id = ?,
                author = ?,
                excerpt = ?,
                content = ?,
                featured_image = ?,
                publish_date = ?,
                status = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $featuredParam = $featuredImage ? $featuredImage : null;

        $stmt->bind_param(
            "sissssssi",
            $postTitle,
            $postCategory,
            $postAuthor,
            $postExcerpt,
            $postContent,
            $featuredParam,
            $publishDate,
            $status,
            $post_id
        );

        if ($stmt->execute()) {
            $_SESSION['success'] = ($status === 'draft') ? "Draft updated successfully!" : "Post updated successfully!";
            header("Location: list_posts.php");
            exit();
        } else {
            $errorMessage = "Failed to update post: " . htmlspecialchars($conn->error);
        }
    } else {
        $errorMessage = implode("<br>", array_map("htmlspecialchars", $errors));
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Post</title>
    <link rel="icon" href="../../images/logo.jpg" type="image/png">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>

    <!-- Quill Editor -->
    <link href="../../assets/css/quill.snow.css" rel="stylesheet">
    <script src="../../assets/js/quill.min.js"></script>

    <style>
        input.error, textarea.error, select.error {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15) !important;
            background: #fef2f2;
        }
        input.valid, textarea.valid, select.valid {
            border-color: #22c55e !important;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15) !important;
        }
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 10px 12px;
            border-radius: 8px;
            margin-top: 8px;
            border-left: 4px solid #ef4444;
            font-size: 13px;
        }

        #editor-container { height: 320px; }
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
            border-radius: 0.5rem;
            padding: 1.25rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #f9fafb;
        }
        .file-upload-label:hover {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        .file-preview {
            margin-top: 0.75rem;
            padding: 0.75rem;
            background: #f0fdf4;
            border-radius: 0.5rem;
            color: #166534;
            display: none;
            font-size: 14px;
        }
        .current-image img {
            max-width: 240px;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
        }
    </style>
</head>

<body class="bg-gray-100 font-sans">
<div class="flex min-h-screen">
    <?php
        // ✅ Sidebar include (posts folder -> go up one level -> /admin/includes/)
        $base_path = '../../';
        include dirname(__DIR__) . '/includes/slidebar2.php'; // ✅ FIXED
    ?>

    <div class="flex-1 p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">✏️ Edit Post</h1>

        <?php if ($successMessage): ?>
            <div class="max-w-4xl mx-auto bg-green-100 border-l-4 border-green-500 text-green-800 p-4 mb-6 rounded">
                <strong>✅ Success:</strong> <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="max-w-4xl mx-auto bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                <strong>⚠️ Error:</strong> <?= $errorMessage ?>
            </div>
        <?php endif; ?>

        <div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-md">
            <form id="postForm" method="POST" action="" enctype="multipart/form-data" class="space-y-8">
                <input type="hidden" name="status" id="postStatus" value="published">
                <input type="hidden" name="postContent" id="postContent">

                <!-- Basic Information -->
                <div>
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">🧾 Basic Information</h2>

                    <div class="mb-4">
                        <label for="postTitle" class="block text-sm font-semibold text-gray-700 mb-2">
                            🏷️ Post Title <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="postTitle" name="postTitle"
                               value="<?= htmlspecialchars($post['title']) ?>"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                               placeholder="Enter an engaging title...">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="postCategory" class="block text-sm font-semibold text-gray-700 mb-2">
                                🗂️ Category <span class="text-red-500">*</span>
                            </label>
                            <select id="postCategory" name="postCategory"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int)$category['id'] ?>" <?= ((int)$category['id'] === (int)$post['category_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="postAuthor" class="block text-sm font-semibold text-gray-700 mb-2">
                                ✍️ Author <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="postAuthor" name="postAuthor"
                                   value="<?= htmlspecialchars($post['author']) ?>"
                                   class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                   placeholder="Author name">
                        </div>
                    </div>

                    <div class="mt-4">
                        <label for="postExcerpt" class="block text-sm font-semibold text-gray-700 mb-2">📝 Excerpt</label>
                        <textarea id="postExcerpt" name="postExcerpt" rows="3"
                                  class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                  placeholder="Write a compelling summary..."><?= htmlspecialchars($post['excerpt']) ?></textarea>
                    </div>
                </div>

                <!-- Content -->
                <div>
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">📰 Content</h2>

                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        ✨ Post Content <span class="text-red-500">*</span>
                    </label>
                    <div id="editor-container"><?= $post['content'] ?></div>
                </div>

                <!-- Publishing -->
                <div>
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">📌 Publishing Options</h2>

                    <div class="mb-4">
                        <label for="publishDate" class="block text-sm font-semibold text-gray-700 mb-2">🕒 Publish Date & Time</label>
                        <input type="datetime-local" id="publishDate" name="publishDate"
                               value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($post['publish_date']))) ?>"
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">🖼️ Featured Image</label>

                        <?php if (!empty($post['featured_image'])): ?>
                            <div class="current-image mb-4">
                                <p class="text-sm text-gray-600 mb-2">Current Image:</p>
                                <!-- ✅ path for browser (from /admin/posts/ to /admin/Uploads/posts/) -->
                                <img src="../Uploads/posts/<?= htmlspecialchars($post['featured_image']) ?>" alt="Current Featured Image">
                            </div>
                        <?php endif; ?>

                        <input type="file" id="featuredImage" name="featuredImage" accept="image/*" class="hidden">

                        <div class="file-upload-label" id="fileUploadLabel">
                            <div class="text-gray-700 font-semibold">Click to upload or drag & drop</div>
                            <div class="text-xs text-gray-500 mt-1">Recommended: 1200x630px (JPG, PNG, WebP) • Max 5MB</div>
                        </div>

                        <div class="file-preview" id="filePreview"></div>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <button type="button"
                            class="py-3 px-6 bg-yellow-600 text-white rounded-md font-semibold hover:bg-yellow-700 transition"
                            onclick="saveDraft()">
                        <i class="fa-solid fa-file-pen mr-2"></i> Save as Draft
                    </button>

                    <button type="submit"
                            class="py-3 px-6 bg-sky-500 text-white rounded-md font-semibold hover:bg-sky-600 transition">
                        <i class="fa-solid fa-floppy-disk mr-2"></i> Update Post
                    </button>

                    <button type="button"
                            class="py-3 px-6 bg-gray-500 text-white rounded-md font-semibold hover:bg-gray-600 transition"
                            onclick="goBack()">
                        ← Back to Posts
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

<script>
    // Quill editor
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

    // Click label opens file picker
    document.getElementById('fileUploadLabel').addEventListener('click', function () {
        document.getElementById('featuredImage').click();
    });

    // Preview
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

    // Drag & drop
    const fileUploadLabel = document.getElementById('fileUploadLabel');
    ['dragover', 'dragenter'].forEach(eventName => {
        fileUploadLabel.addEventListener(eventName, function(e) {
            e.preventDefault();
            this.classList.add('border-indigo-500');
        });
    });
    ['dragleave', 'dragend'].forEach(eventName => {
        fileUploadLabel.addEventListener(eventName, function(e) {
            e.preventDefault();
            this.classList.remove('border-indigo-500');
        });
    });
    fileUploadLabel.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('border-indigo-500');
        const files = e.dataTransfer.files;
        if (files.length > 0 && files[0].type.startsWith('image/')) {
            const fileInput = document.getElementById('featuredImage');
            fileInput.files = files;
            fileInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });

    // Validation
    $(document).ready(function() {
        $("#postForm").validate({
            rules: {
                postTitle: { required: true, minlength: 3, maxlength: 200 },
                postCategory: { required: true },
                postAuthor: { required: true, minlength: 2, maxlength: 100 },
                postExcerpt: { maxlength: 500 }
            },
            messages: {
                postTitle: {
                    required: "🏷️ Title is required.",
                    minlength: "Title must be at least 3 characters.",
                    maxlength: "Title cannot exceed 200 characters."
                },
                postCategory: { required: "🗂️ Category is required." },
                postAuthor: {
                    required: "✍️ Author is required.",
                    minlength: "Author must be at least 2 characters.",
                    maxlength: "Author cannot exceed 100 characters."
                },
                postExcerpt: { maxlength: "Excerpt cannot exceed 500 characters." }
            },
            errorElement: "div",
            errorClass: "error-message",
            validClass: "valid",
            errorPlacement: function(error, element) {
                error.insertAfter(element);
            },
            submitHandler: function(form) {
                var content = quill.root.innerHTML;
                if (content === '<p><br></p>' || content.trim() === '') {
                    alert("❌ Post content is required.");
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

    function saveDraft() {
        var content = quill.root.innerHTML;
        if (content === '<p><br></p>' || content.trim() === '') {
            alert("❌ Post content is required.");
            return false;
        }
        document.getElementById('postContent').value = content;
        document.getElementById('postStatus').value = 'draft';
        document.getElementById('postForm').submit();
    }

    function goBack() {
        if (confirm('Are you sure you want to leave? Unsaved changes will be lost.')) {
            window.location.href = 'list_posts.php';
        }
    }
</script>
</body>
</html>
