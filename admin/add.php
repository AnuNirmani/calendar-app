<?php
include '../db.php';
include '../auth.php';

// Check if user is authenticated (both admin and super_admin can access)
checkAuth();

// Auto logout after inactivity
$timeout = 900; // 15 minutes
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout) {
    session_unset();
    session_destroy();
    header("Location: ../login.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// Fetch special types
$types = $conn->query("SELECT id, type FROM special_types");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin - Add Special Date</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <link rel="icon" href="../images/logo.jpg" type="image/png" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>

    <style>
        /* jQuery Validation Styles (Tailwind-friendly) */
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
            padding: 8px 12px;
            border-radius: 8px;
            margin-top: 6px;
            border-left: 4px solid #ef4444;
            font-size: 13px;
        }
        /* Radio error highlight */
        input[type="radio"].error {
            outline: 2px solid #ef4444;
            outline-offset: 2px;
            border-radius: 9999px;
        }
    </style>
</head>

<body class="bg-gray-100 font-sans">
<div class="flex min-h-screen">
    <?php
    $base_path = '../';
    include __DIR__ . '/includes/slidebar2.php';
    ?>

    <div class="flex-1 p-8">
        <!-- Title -->
        <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">➕ Add New Special Date</h1>

        <!-- Top Buttons (match your index page button style) -->
        <div class="flex flex-wrap gap-3 justify-center mb-6">
            <a href="dashboard.php"
               class="inline-flex items-center gap-2 bg-gradient-to-r from-indigo-500 to-purple-600 text-white px-5 py-2 rounded-full font-semibold text-sm hover:from-indigo-600 hover:to-purple-700 transition">
                <i class="fas fa-home"></i> Back to Dashboard
            </a>

            <a href="index.php"
               class="inline-flex items-center gap-2 bg-sky-500 text-white px-5 py-2 rounded-full font-semibold text-sm hover:bg-sky-600 transition">
                ← Back to Special Dates
            </a>
        </div>

        <!-- Form Card -->
        <div class="max-w-3xl mx-auto bg-white rounded-lg shadow-md p-6">
            <form action="save.php" method="POST" id="addSpecialDateForm" class="space-y-5">

                <!-- Date -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">📅 Date</label>
                    <input type="date" name="date"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <!-- Type -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">🏷️ Type</label>
                    <select name="type_id"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        <option value="">-- Select Type --</option>
                        <?php while($row = $types->fetch_assoc()): ?>
                            <option value="<?= (int)$row['id'] ?>">
                                <?= htmlspecialchars($row['type']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">📝 Description</label>
                    <input type="text" name="description" placeholder="Enter description"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <!-- Color -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">🎨 Select Color</label>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3" id="colorGroup">
                        <!-- Red -->
                        <label class="flex items-center gap-3 border border-gray-200 rounded-lg p-3 hover:bg-gray-50 cursor-pointer">
                            <input type="radio" name="color" value="#ff0000" class="w-4 h-4">
                            <span class="w-5 h-5 rounded-full border" style="background:#ff0000;"></span>
                            <span class="text-sm font-medium text-gray-700">Mercantile Holiday</span>
                        </label>

                        <!-- Yellow -->
                        <label class="flex items-center gap-3 border border-gray-200 rounded-lg p-3 hover:bg-gray-50 cursor-pointer">
                            <input type="radio" name="color" value="#ffea00" class="w-4 h-4">
                            <span class="w-5 h-5 rounded-full border" style="background:#ffea00;"></span>
                            <span class="text-sm font-medium text-gray-700">Poya Day</span>
                        </label>

                        <!-- Other -->
                        <label class="flex items-center gap-3 border border-gray-200 rounded-lg p-3 hover:bg-gray-50 cursor-pointer">
                            <input type="radio" name="color" value="#dbdbdbff" class="w-4 h-4">
                            <span class="w-5 h-5 rounded-full border" style="background:#dbdbdbff;"></span>
                            <span class="text-sm font-medium text-gray-700">Other</span>
                        </label>
                    </div>
                </div>

                <!-- Submit -->
                <div class="pt-2">
                    <button type="submit"
                            class="w-full bg-sky-500 text-white px-4 py-3 rounded-md font-semibold hover:bg-sky-600 transition">
                        <i class="fas fa-plus-circle"></i> Add Date
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
$(document).ready(function () {
    $("#addSpecialDateForm").validate({
        rules: {
            date: { required: true },
            type_id: { required: true },
            description: { required: true, minlength: 3, maxlength: 255 },
            color: { required: true }
        },
        messages: {
            date: { required: "📅 Please select a date" },
            type_id: { required: "🏷️ Please select a type" },
            description: {
                required: "📝 Description is required",
                minlength: "Description must be at least 3 characters",
                maxlength: "Description cannot exceed 255 characters"
            },
            color: { required: "🎨 Please select a color" }
        },
        errorElement: "div",
        errorClass: "error-message",
        validClass: "valid",
        errorPlacement: function (error, element) {
            if (element.attr("type") === "radio") {
                // place after color group
                error.insertAfter($("#colorGroup"));
            } else {
                error.insertAfter(element);
            }
        },
        success: function(label, element) {
            $(element).removeClass("error").addClass("valid");
            label.remove();
        },
        highlight: function(element, errorClass, validClass) {
            $(element).removeClass(validClass).addClass(errorClass);
        },
        unhighlight: function(element, errorClass, validClass) {
            $(element).removeClass(errorClass).addClass(validClass);
        }
    });

    // real-time validation
    $('input[name="date"]').on('change', function(){ $(this).valid(); });
    $('select[name="type_id"]').on('change', function(){ $(this).valid(); });
    $('input[name="description"]').on('input', function(){ $(this).valid(); });
    $('input[name="color"]').on('change', function(){ $('input[name="color"]').valid(); });
});
</script>

</body>
</html>
