<?php
include '../db.php';
include '../auth.php';

// Check if user is authenticated (both admin and super_admin can access)
checkAuth();

// Auto logout after inactivity
$timeout = 900; // 15 minutes = 900 seconds
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout) {
    session_unset();
    session_destroy();
    header("Location: ../login.php"); // or "login.php" depending on path
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// Fetch special types
$types = $conn->query("SELECT id, type FROM special_types");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Add Special Date</title>
    <link rel="stylesheet" href="../css/fonts/fonts.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../images/logo.jpg" type="image/png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
    <style>
        /* jQuery Validation Styles */
        .add-form input.error,
        .add-form select.error {
            border-color: #f44336 !important;
            box-shadow: 0 0 5px rgba(244, 67, 54, 0.3) !important;
        }

        .add-form input.valid,
        .add-form select.valid {
            border-color: #4caf50 !important;
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.3) !important;
        }

        label.error {
            color: #f44336;
            font-size: 12px;
            margin-top: 5px;
            display: block;
            font-weight: normal;
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 8px 12px;
            border-radius: 5px;
            margin-top: 5px;
            border-left: 4px solid #f44336;
            font-size: 13px;
        }

        /* Radio button validation styling */
        .add-form input[type="radio"].error + span {
            border-color: #f44336 !important;
            box-shadow: 0 0 5px rgba(244, 67, 54, 0.3) !important;
        }

        .add-form input[type="radio"].valid + span {
            border-color: #4caf50 !important;
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.3) !important;
        }

        /* Custom radio button styling */
        .add-form input[type="radio"]:checked + span {
            border-color: #3b82f6 !important;
            background-color: #eff6ff !important;
        }

        .add-form input[type="radio"]:checked + span > span {
            opacity: 1 !important;
        }

        .add-form input[type="radio"] + span > span {
            opacity: 0.3;
            transition: opacity 0.2s ease;
        }

        /* Mobile responsive adjustments */
        @media (max-width: 640px) {
            .max-w-md {
                max-width: 95% !important;
                margin: 0 auto !important;
            }
        }
    </style>
</head>

<body class="admin-page">
    <div style="text-align: center; margin-bottom: 30px;">
        <!-- <a href="dashboard.php" style="background: #667eea; color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; position: absolute; left: 0; font-weight: 600;">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a href="index.php" style="background: #1976d2; color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; position: absolute; left: 140px; font-weight: 600;">
            ← Back
        </a> -->
    <h1 style="font-size: 28px;">➕ Add New Special Date</h1>
    <a href="dashboard.php" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important; 
        color: white !important; 
        padding: 10px 20px !important; 
        border-radius: 20px !important; 
        font-weight: 600 !important; 
        text-transform: uppercase !important; 
        letter-spacing: 0.5px !important; 
        margin: 10px !important; 
        display: inline-block !important; 
        transition: all 0.3s ease !important;
        font-size: 14px !important;">
        <i class="fas fa-home"></i> Back to Dashboard
    </a>
    </div>

    <div>
        <form action="save.php" method="POST" class="add-form space-y-4">
            <div class="space-y-2">
                <label for="date" class="block text-sm font-medium text-gray-700">📅 Date:</label>
                <input type="date" name="date" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div class="space-y-2">
                <label for="type_id" class="block text-sm font-medium text-gray-700">🏷️ Type:</label>
                <select name="type_id" required 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <option value="">-- Select Type --</option>
                    <?php while($row = $types->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>">
                            <?= htmlspecialchars($row['type']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="space-y-2">
                <label for="description" class="block text-sm font-medium text-gray-700">📝 Description:</label>
                <input type="text" name="description" placeholder="Enter description" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div class="space-y-3">
                <label class="block text-sm font-medium text-gray-700">🎨 Optional Color:</label>
                <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
                    <label class="flex items-center gap-2 cursor-pointer p-2 rounded-md hover:bg-gray-50 transition-colors">
                        <input type="radio" name="color" value="#ff0000" required class="sr-only">
                        <span class="w-5 h-5 rounded-full border-2 border-gray-300 flex items-center justify-center">
                            <span class="w-3 h-3 rounded-full bg-red-500"></span>
                        </span>
                        <span class="text-sm text-gray-700">Mercantile Holiday</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer p-2 rounded-md hover:bg-gray-50 transition-colors">
                        <input type="radio" name="color" value="#ffea00" required class="sr-only">
                        <span class="w-5 h-5 rounded-full border-2 border-gray-300 flex items-center justify-center">
                            <span class="w-3 h-3 rounded-full bg-yellow-400"></span>
                        </span>
                        <span class="text-sm text-gray-700">Poya Day</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer p-2 rounded-md hover:bg-gray-50 transition-colors">
                        <input type="radio" name="color" value="#dbdbdbff" required class="sr-only">
                        <span class="w-5 h-5 rounded-full border-2 border-gray-300 flex items-center justify-center">
                            <span class="w-3 h-3 rounded-full bg-gray-300"></span>
                        </span>
                        <span class="text-sm text-gray-700">Other</span>
                    </label>
                </div>
            </div>

            <button type="submit" 
                    class="w-full bg-gradient-to-r from-blue-500 to-purple-600 text-white py-3 px-4 rounded-md font-semibold hover:from-blue-600 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 flex items-center justify-center gap-2">
                <i class="fas fa-plus-circle"></i>
                Add Date
            </button>
        </form>
    </div>

    <div style="margin-top: 10px;">
        <span style="color: navy; padding: 8px 16px; border-radius: 20px; font-size: 18px; font-weight: 600;">
            <?= isSuperAdmin() ? '👑 Super Admin' : '👤 Admin' ?>: <?= htmlspecialchars($_SESSION['username']) ?>
        </span>
        <a href="../logout.php" style="background: #f44336; color: white; padding: 8px 16px; border-radius: 20px; font-size: 16px; font-weight: 600; text-decoration: none; margin-left: 10px;">
            🚪 Logout
        </a>
    </div>

    <div class="footer-divider"></div>
    <footer class="footer">
        © <?php echo date('Y'); ?> Developed and Maintained by WNL in collaboration with Web Publishing Department <br>
        © All rights reserved, 2008 - Wijeya Newspapers Ltd.
    </footer>

    <script>
        $(document).ready(function() {
            // jQuery Validation Setup
            $(".add-form").validate({
                rules: {
                    date: {
                        required: true
                    },
                    type_id: {
                        required: true
                    },
                    description: {
                        required: true,
                        minlength: 3,
                        maxlength: 255
                    },
                    color: {
                        required: true
                    }
                },
                messages: {
                    date: {
                        required: "📅 Please select a date"
                    },
                    type_id: {
                        required: "🏷️ Please select a type"
                    },
                    description: {
                        required: "📝 Description is required",
                        minlength: "Description must be at least 3 characters",
                        maxlength: "Description cannot exceed 255 characters"
                    },
                    color: {
                        required: "🎨 Please select a color"
                    }
                },
                errorElement: "div",
                errorClass: "error-message",
                validClass: "valid",
                errorPlacement: function(error, element) {
                    if (element.attr("type") === "radio") {
                        // For radio buttons, place error after the radio button group
                        error.insertAfter(element.closest('div'));
                    } else {
                        // For other inputs, place error after the input
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
                },
                submitHandler: function(form) {
                    // This will only run if client-side validation passes
                    form.submit();
                }
            });

            // Real-time validation for date field
            $('input[name="date"]').on('change', function() {
                $(this).valid();
            });

            // Real-time validation for select field
            $('select[name="type_id"]').on('change', function() {
                $(this).valid();
            });

            // Real-time validation for description field
            $('input[name="description"]').on('input', function() {
                $(this).valid();
            });

            // Real-time validation for radio buttons
            $('input[name="color"]').on('change', function() {
                $('input[name="color"]').valid();
            });
        });
    </script>
</body>
</html>