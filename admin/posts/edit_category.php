<?php
// admin/categories/edit_category.php  (or admin/posts/edit_category.php - adjust paths if needed)

require_once dirname(__DIR__) . '/../db.php';          // -> /db.php
require_once __DIR__ . '/categories.php';             // same folder as this file

// ✅ Use your main auth system (same style as other admin pages)
require_once dirname(__DIR__) . '/../auth.php';
checkAuth(); // or checkAuth('super_admin') if only super admin can edit categories

// Auto logout after inactivity
$timeout = 900;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout) {
    session_unset();
    session_destroy();
    header("Location: ../../login.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

$categoryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$category = getCategoryById($categoryId);

if (!$category) {
    header("Location: list_categories.php");
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryName = trim($_POST['name'] ?? '');
    $status = trim($_POST['status'] ?? '');

    if (empty($categoryName)) {
        $errors[] = "Category name is required.";
    }
    if (!in_array($status, ['active', 'inactive'], true)) {
        $errors[] = "Invalid status selected.";
    }

    if (empty($errors)) {
        $result = updateCategory($categoryId, $categoryName, $status);
        if ($result === true) {
            $success = "✅ Category updated successfully!";
            $category = getCategoryById($categoryId);
            header("refresh:2;url=list_categories.php");
        } else {
            $errors[] = (string)$result;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Category</title>
    <link rel="icon" href="../../images/logo.jpg" type="image/png" />

    <link rel="stylesheet" href="../../assets/css/fontawesome.min.css">
    <script src="../../assets/js/tailwind.js"></script>

    <script src="../../assets/js/jquery.min.js"></script>
    <script src="../../assets/js/jquery.validate.min.js"></script>

    <style>
        /* same validation style used in your other admin pages */
        input.error, select.error {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15) !important;
        }
        input.valid, select.valid {
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
    </style>
</head>

<body class="bg-gray-100 font-sans">
<div class="flex min-h-screen">
    <?php
        // ✅ Sidebar include: from /admin/categories/ -> go up one -> /admin/includes/
        $base_path = '../../';
        include dirname(__DIR__) . '/includes/slidebar2.php';
    ?>

    <div class="flex-1 p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">✏️ Edit Category</h1>

        <?php if (!empty($success)): ?>
            <div class="max-w-xl mx-auto bg-green-100 border-l-4 border-green-500 text-green-800 p-4 my-4 rounded">
                <strong><?= htmlspecialchars($success) ?></strong>
                <div class="text-xs mt-1">Redirecting to Categories...</div>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="max-w-xl mx-auto bg-red-100 border-l-4 border-red-500 text-red-700 p-4 my-4 rounded">
                <strong>⚠️ Please fix the following:</strong>
                <ul class="list-disc ml-6 mt-2">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="max-w-xl mx-auto bg-white rounded-lg shadow-md p-6">
            <form method="POST" id="editCategoryForm" class="space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">🏷️ Category Name</label>
                    <input type="text" name="name" id="name"
                           value="<?= htmlspecialchars($category['name'] ?? '') ?>"
                           placeholder="Enter category name"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                           required>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">✅ Status</label>
                    <select name="status" id="status"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                            required>
                        <option value="active" <?= (($category['status'] ?? '') === 'active') ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= (($category['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>

                <button type="submit"
                        class="w-full bg-sky-500 text-white px-4 py-3 rounded-md font-semibold hover:bg-sky-600 transition flex items-center justify-center gap-2">
                    <i class="fas fa-save"></i>
                    Update Category
                </button>
            </form>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>

<script>
$(document).ready(function () {
    // auto-hide messages
    setTimeout(() => {
        $('.bg-red-100, .bg-green-100').fadeOut();
    }, 2000);

    $("#editCategoryForm").validate({
        rules: {
            name: { required: true, minlength: 2, maxlength: 100 },
            status: { required: true }
        },
        messages: {
            name: {
                required: "🏷️ Category name is required",
                minlength: "Category name must be at least 2 characters",
                maxlength: "Category name cannot exceed 100 characters"
            },
            status: "✅ Please select a status"
        },
        errorElement: "div",
        errorClass: "error-message",
        validClass: "valid",
        errorPlacement: function(error, element) {
            error.insertAfter(element);
        },
        success: function(label, element) {
            $(element).removeClass("error").addClass("valid");
            label.remove();
        }
    });
});
</script>
</body>
</html>
