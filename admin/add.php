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

    <form action="save.php" method="POST" class="add-form">
        <label for="date">📅 Date:</label>
        <input type="date" name="date" required>

        <label for="type_id">🏷️ Type:</label>
        <select name="type_id" required>
            <option value="">-- Select Type --</option>
            <?php while($row = $types->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>">
                    <?= htmlspecialchars($row['type']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label for="description">📝 Description:</label>
        <input type="text" name="description" placeholder="Enter description" required>

        <label>🎨 Optional Color:</label>
        <div style="display: flex; gap: 20px; margin: 10px 0;">
            <label style="display: flex; align-items: center; gap: 8px;">
                <input type="radio" name="color" value="#ff0000" required>
                <span style="width: 20px; height: 20px; background: #ff0000; border-radius: 50%; border: 1px solid #333;"></span>
                Mercantile Holiday
            </label>
            <label style="display: flex; align-items: center; gap: 8px;">
                <input type="radio" name="color" value="#ffea00" required>
                <span style="width: 20px; height: 20px; background: #ffea00; border-radius: 50%; border: 1px solid #333;"></span>
                Poya Day
            </label>
            <label style="display: flex; align-items: center; gap: 8px;">
                <input type="radio" name="color" value="#dbdbdbff" required>
                <span style="width: 20px; height: 20px; background: #dbdbdbff; border-radius: 50%; border: 1px solid #333;"></span>
                Other
            </label>
        </div>

        <button type="submit">
        <i class="fas fa-plus-circle "></i>
         Add Date</button>
    </form>

    <div style="margin-top: 10px;">
        <span style="color: navy; padding: 8px 16px; border-radius: 20px; font-size: 18px; font-weight: 600;">
            <?= isSuperAdmin() ? '👑 Super Admin' : '👤 Admin' ?>: <?= htmlspecialchars($_SESSION['username']) ?>
        </span>
        <a href="../logout.php" style="background: #f44336; color: white; padding: 8px 16px; border-radius: 20px; font-size: 16px; font-weight: 600; text-decoration: none; margin-left: 10px;">
            🚪 Logout
        </a>
    </div>

    <!-- <div class="footer-divider"></div>
    <footer class="footer">
        © <?php echo date('Y'); ?> Developed and Maintained by WNL in collaboration with Web Publishing Department <br>
        © All rights reserved, 2008 - Wijeya Newspapers Ltd.
    </footer> -->

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

<div class="footer-divider"></div>
<?php include 'includes/footer.php'; ?>