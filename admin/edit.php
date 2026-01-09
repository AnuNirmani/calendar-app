<?php
include '../db.php';
include '../auth.php';
checkAuth(); // allow admin + super_admin (your auth.php decides)

// Auto logout after inactivity
$timeout = 900;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout) {
    session_unset();
    session_destroy();
    header("Location: ../login.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// Get ID
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}
$id = (int)$_GET['id'];

// Fetch current data
$stmt = $conn->prepare("SELECT * FROM special_dates WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    header("Location: index.php");
    exit;
}

// Fetch types
$types = $conn->query("SELECT id, type FROM special_types");

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_special_date'])) {
    $date        = $_POST['date'] ?? '';
    $type_id     = (int)($_POST['type_id'] ?? 0);
    $color       = $_POST['color'] ?? '';
    $description = trim($_POST['description'] ?? '');

    if (empty($date)) {
        $error = "Date is required!";
    } elseif (empty($type_id)) {
        $error = "Type is required!";
    } elseif (empty($description)) {
        $error = "Description is required!";
    } elseif (empty($color)) {
        $error = "Color is required!";
    } else {
        $up = $conn->prepare("UPDATE special_dates SET date=?, type_id=?, color=?, description=? WHERE id=?");
        $up->bind_param("sissi", $date, $type_id, $color, $description, $id);

        if ($up->execute()) {
            $success = "✅ Special date updated successfully!";
            header("refresh:2;url=index.php");
        } else {
            $error = "❌ Error updating special date!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Edit Special Date</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../images/logo.jpg" type="image/png">

    <link rel="stylesheet" href="../assets/css/fontawesome.min.css">
    <script src="../assets/js/tailwind.js"></script>

    <script src="../assets/js/jquery.min.js"></script>
    <script src="../assets/js/jquery.validate.min.js"></script>

    <style>
        /* jQuery Validation Styles (Tailwind friendly) */
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
    // Sidebar (same as your other pages)
    $base_path = '../';
    include __DIR__ . '/includes/slidebar2.php';
    ?>

    <div class="flex-1 p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">✏️ Edit Special Date</h1>

        <?php if (!empty($error)): ?>
            <div class="max-w-xl mx-auto bg-red-100 border-l-4 border-red-500 text-red-700 p-4 my-4 rounded">
                <strong>⚠️ Error:</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="max-w-xl mx-auto bg-green-100 border-l-4 border-green-500 text-green-800 p-4 my-4 rounded">
                <strong><?= htmlspecialchars($success) ?></strong>
                <div class="text-xs mt-1">Redirecting to Special Dates...</div>
            </div>
        <?php endif; ?>

        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-6">
            <form method="POST" id="editSpecialDateForm" class="space-y-5">
                <input type="hidden" name="update_special_date" value="1">

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">📅 Date</label>
                    <input type="date" name="date" id="date"
                           value="<?= htmlspecialchars($data['date']) ?>"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                           required>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">🏷️ Type</label>
                    <select name="type_id" id="type_id"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                            required>
                        <option value="">-- Select Type --</option>
                        <?php while($row = $types->fetch_assoc()): ?>
                            <option value="<?= (int)$row['id'] ?>" <?= ((int)$row['id'] === (int)$data['type_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['type']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">📝 Description</label>
                    <input type="text" name="description" id="description"
                           value="<?= htmlspecialchars($data['description']) ?>"
                           placeholder="Enter description"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                           required>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">🎨 Optional Color</label>

                    <div class="flex flex-col sm:flex-row gap-4 mt-2">
                        <label class="flex items-center gap-3 bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 cursor-pointer hover:bg-gray-100 transition">
                            <input type="radio" name="color" value="#ff0000"
                                   <?= ($data['color'] === '#ff0000') ? 'checked' : '' ?>
                                   required>
                            <span class="w-5 h-5 rounded-full border border-gray-700" style="background:#ff0000;"></span>
                            <span class="text-sm font-semibold text-gray-700">Mercantile Holiday</span>
                        </label>

                        <label class="flex items-center gap-3 bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 cursor-pointer hover:bg-gray-100 transition">
                            <input type="radio" name="color" value="#ffea00"
                                   <?= ($data['color'] === '#ffea00') ? 'checked' : '' ?>
                                   required>
                            <span class="w-5 h-5 rounded-full border border-gray-700" style="background:#ffea00;"></span>
                            <span class="text-sm font-semibold text-gray-700">Poya Day</span>
                        </label>

                        <label class="flex items-center gap-3 bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 cursor-pointer hover:bg-gray-100 transition">
                            <input type="radio" name="color" value="#dbdbdbff"
                                   <?= ($data['color'] === '#dbdbdbff') ? 'checked' : '' ?>
                                   required>
                            <span class="w-5 h-5 rounded-full border border-gray-700" style="background:#dbdbdbff;"></span>
                            <span class="text-sm font-semibold text-gray-700">Other</span>
                        </label>
                    </div>
                </div>

                <button type="submit"
                        class="w-full bg-sky-500 text-white px-4 py-3 rounded-md font-semibold hover:bg-sky-600 transition flex items-center justify-center gap-2">
                    <i class="fas fa-save"></i>
                    Save Changes
                </button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
    $(document).ready(function () {
        // Auto-hide server messages
        setTimeout(() => {
            $('.bg-red-100, .bg-green-100').hide();
        }, 2000);

        // Validation
        $("#editSpecialDateForm").validate({
            rules: {
                date: { required: true },
                type_id: { required: true },
                description: { required: true, minlength: 2, maxlength: 200 },
                color: { required: true }
            },
            messages: {
                date: "📅 Please select a date",
                type_id: "🏷️ Please select a type",
                description: {
                    required: "📝 Description is required",
                    minlength: "Description must be at least 2 characters",
                    maxlength: "Description cannot exceed 200 characters"
                },
                color: "🎨 Please select a color"
            },
            errorElement: "div",
            errorClass: "error-message",
            validClass: "valid",
            errorPlacement: function(error, element) {
                // For radio group, place after the group container
                if (element.attr("name") === "color") {
                    error.insertAfter(element.closest("div").parent());
                } else {
                    error.insertAfter(element);
                }
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
